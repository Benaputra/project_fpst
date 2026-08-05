<?php

namespace App\Actions\Skripsi;

use App\Enums\HasilKesediaanBimbingan;
use App\Enums\JenisDokumenPengajuan;
use App\Enums\PeranKesediaanBimbingan;
use App\Enums\StatusDokumenPengajuan;
use App\Enums\StatusKesediaanBimbingan;
use App\Enums\StatusPengajuanJudul;
use App\Enums\StatusSkripsi;
use App\Models\DokumenPengajuan;
use App\Models\KesediaanBimbingan;
use App\Models\Skripsi;
use App\Models\User;
use App\Services\Audit\CatatAktivitas;
use App\Services\Skripsi\PenyimpanPembimbingFinal;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class FinalisasiPembimbing
{
    public function __construct(
        private readonly PenyimpanPembimbingFinal $penyimpan,
        private readonly CatatAktivitas $audit
    ) {}

    /**
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function execute(User $user, Skripsi $skripsi): Skripsi
    {
        return DB::transaction(function () use ($user, $skripsi) {
            $skripsiTerkunci = Skripsi::query()
                ->with(['mahasiswa', 'pengajuanJudul'])
                ->lockForUpdate()
                ->findOrFail($skripsi->getKey());
            Gate::forUser($user)->authorize('finalisasiPembimbing', $skripsiTerkunci);

            $riwayat = KesediaanBimbingan::query()
                ->where('skripsi_id', $skripsiTerkunci->id)
                ->with('dosen')
                ->lockForUpdate()
                ->orderBy('peran')
                ->orderBy('siklus')
                ->get();
            [$pembimbing1, $pembimbing2] = $this->pastikanPrasyarat(
                $skripsiTerkunci,
                $riwayat
            );

            if ($skripsiTerkunci->status === StatusSkripsi::BimbinganAktif
                && $skripsiTerkunci->pembimbing1_id === $pembimbing1->dosen_id
                && $skripsiTerkunci->pembimbing2_id === $pembimbing2?->dosen_id) {
                return $skripsiTerkunci->load(['pembimbing1', 'pembimbing2']);
            }

            if ($skripsiTerkunci->status !== StatusSkripsi::MenungguKesediaanPembimbing
                || $skripsiTerkunci->pembimbing1_id !== null
                || $skripsiTerkunci->pembimbing2_id !== null) {
                throw ValidationException::withMessages([
                    'skripsi' => 'Status atau pembimbing final tidak konsisten untuk finalisasi.',
                ]);
            }

            $this->penyimpan->execute(
                $skripsiTerkunci,
                $pembimbing1->dosen_id,
                $pembimbing2?->dosen_id
            );

            $this->audit->execute($user, $skripsiTerkunci, 'pembimbing_difinalisasi', [
                'status' => StatusSkripsi::MenungguKesediaanPembimbing->value,
                'pembimbing1_id' => null,
                'pembimbing2_id' => null,
            ], [
                'status' => StatusSkripsi::BimbinganAktif->value,
                'pembimbing1_id' => $pembimbing1->dosen_id,
                'pembimbing2_id' => $pembimbing2?->dosen_id,
            ]);

            return $skripsiTerkunci->refresh()->load([
                'pembimbing1',
                'pembimbing2',
                'kesediaanBimbingan',
            ]);
        }, 3);
    }

    /**
     * @param  Collection<int, KesediaanBimbingan>  $riwayat
     * @return array{KesediaanBimbingan, ?KesediaanBimbingan}
     *
     * @throws ValidationException
     */
    private function pastikanPrasyarat(Skripsi $skripsi, Collection $riwayat): array
    {
        if ($skripsi->pengajuanJudul->status !== StatusPengajuanJudul::Diverifikasi
            || $skripsi->pengajuanJudul->diverifikasi_oleh === null
            || $skripsi->pengajuanJudul->diverifikasi_at === null
            || $skripsi->judul !== $skripsi->pengajuanJudul->judul) {
            throw ValidationException::withMessages([
                'skripsi' => 'Judul harus tetap terverifikasi dan sesuai snapshot skripsi.',
            ]);
        }

        $statusAktif = [
            StatusKesediaanBimbingan::Ditunjuk,
            StatusKesediaanBimbingan::SuratTerbit,
            StatusKesediaanBimbingan::MenungguUpload,
            StatusKesediaanBimbingan::MenungguVerifikasi,
            StatusKesediaanBimbingan::UploadTidakValid,
        ];
        if ($riwayat->contains(fn (KesediaanBimbingan $item) => in_array(
            $item->status,
            $statusAktif,
            true
        ))) {
            throw ValidationException::withMessages([
                'skripsi' => 'Masih ada proses kesediaan pembimbing yang aktif.',
            ]);
        }

        $pembimbing1 = $this->terakhirUntukPeran(
            $riwayat,
            PeranKesediaanBimbingan::Pembimbing1
        );
        $pembimbing2 = $this->terakhirUntukPeran(
            $riwayat,
            PeranKesediaanBimbingan::Pembimbing2
        );
        $this->pastikanDiterimaDanTerverifikasi($pembimbing1, 'Pembimbing 1');
        if ($pembimbing2 !== null) {
            $this->pastikanDiterimaDanTerverifikasi($pembimbing2, 'Pembimbing 2');
        }

        $programStudiId = (int) $skripsi->mahasiswa->program_studi_id;
        if ((int) $pembimbing1->dosen->program_studi_id !== $programStudiId
            || ($pembimbing2 !== null
                && (int) $pembimbing2->dosen->program_studi_id !== $programStudiId)
            || ($pembimbing2 !== null
                && $pembimbing1->dosen_id === $pembimbing2->dosen_id)) {
            throw ValidationException::withMessages([
                'skripsi' => 'Calon pembimbing final memiliki konflik identitas atau program studi.',
            ]);
        }

        return [$pembimbing1, $pembimbing2];
    }

    /** @param Collection<int, KesediaanBimbingan> $riwayat */
    private function terakhirUntukPeran(
        Collection $riwayat,
        PeranKesediaanBimbingan $peran
    ): ?KesediaanBimbingan {
        return $riwayat
            ->filter(fn (KesediaanBimbingan $item) => $item->peran === $peran)
            ->sortByDesc('siklus')
            ->first();
    }

    /** @throws ValidationException */
    private function pastikanDiterimaDanTerverifikasi(
        ?KesediaanBimbingan $kesediaan,
        string $label
    ): void {
        if (! $kesediaan instanceof KesediaanBimbingan
            || $kesediaan->status !== StatusKesediaanBimbingan::Diterima
            || $kesediaan->hasil !== HasilKesediaanBimbingan::Bersedia
            || $kesediaan->diverifikasi_oleh === null
            || $kesediaan->diverifikasi_at === null) {
            throw ValidationException::withMessages([
                'skripsi' => "{$label} belum diterima dan diverifikasi secara sah.",
            ]);
        }

        $dokumen = DokumenPengajuan::query()
            ->where('documentable_type', KesediaanBimbingan::class)
            ->where('documentable_id', $kesediaan->id)
            ->where('jenis', JenisDokumenPengajuan::HasilKonsultasi)
            ->lockForUpdate()
            ->orderByDesc('versi')
            ->first();
        if (! $dokumen instanceof DokumenPengajuan
            || $dokumen->status !== StatusDokumenPengajuan::Terverifikasi
            || $dokumen->verified_by === null
            || $dokumen->verified_at === null) {
            throw ValidationException::withMessages([
                'skripsi' => "Dokumen {$label} belum terverifikasi.",
            ]);
        }
    }
}
