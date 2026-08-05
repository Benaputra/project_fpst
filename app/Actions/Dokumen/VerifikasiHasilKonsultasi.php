<?php

namespace App\Actions\Dokumen;

use App\Enums\HasilKesediaanBimbingan;
use App\Enums\JenisDokumenPengajuan;
use App\Enums\KeputusanHasilKonsultasi;
use App\Enums\StatusDokumenPengajuan;
use App\Enums\StatusKesediaanBimbingan;
use App\Models\DokumenPengajuan;
use App\Models\KesediaanBimbingan;
use App\Models\User;
use App\Services\Audit\CatatAktivitas;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class VerifikasiHasilKonsultasi
{
    public function __construct(private readonly CatatAktivitas $audit) {}

    /**
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function execute(
        User $user,
        DokumenPengajuan $dokumen,
        KeputusanHasilKonsultasi $keputusan,
        ?string $catatan = null
    ): KesediaanBimbingan {
        $catatan = trim((string) $catatan);
        $catatan = $catatan === '' ? null : $catatan;

        if ($keputusan->membutuhkanCatatan() && $catatan === null) {
            throw ValidationException::withMessages([
                'catatan_verifikasi' => 'Catatan wajib diisi untuk keputusan ini.',
            ]);
        }

        return DB::transaction(function () use ($user, $dokumen, $keputusan, $catatan) {
            $dokumenTerkunci = DokumenPengajuan::query()
                ->lockForUpdate()
                ->findOrFail($dokumen->getKey());

            if ($dokumenTerkunci->documentable_type !== KesediaanBimbingan::class
                || $dokumenTerkunci->jenis !== JenisDokumenPengajuan::HasilKonsultasi) {
                throw ValidationException::withMessages([
                    'dokumen' => 'Dokumen ini bukan hasil konsultasi calon pembimbing.',
                ]);
            }

            $kesediaanTerkunci = KesediaanBimbingan::query()
                ->with('skripsi.pengajuanJudul')
                ->lockForUpdate()
                ->findOrFail($dokumenTerkunci->documentable_id);
            $dokumenTerkunci->setRelation('documentable', $kesediaanTerkunci);

            Gate::forUser($user)->authorize('verify', $dokumenTerkunci);
            $this->pastikanDapatDiverifikasi($dokumenTerkunci, $kesediaanTerkunci);

            if (! Storage::disk('local')->exists($dokumenTerkunci->file_path)
                || ! hash_equals(
                    $dokumenTerkunci->file_hash,
                    hash('sha256', Storage::disk('local')->get($dokumenTerkunci->file_path))
                )) {
                throw ValidationException::withMessages([
                    'dokumen' => 'Integritas file tidak valid sehingga keputusan tidak dapat dicatat.',
                ]);
            }

            $waktu = now();
            [$statusDokumen, $statusKesediaan, $hasil] = match ($keputusan) {
                KeputusanHasilKonsultasi::ValidBersedia => [
                    StatusDokumenPengajuan::Terverifikasi,
                    StatusKesediaanBimbingan::Diterima,
                    HasilKesediaanBimbingan::Bersedia,
                ],
                KeputusanHasilKonsultasi::ValidTidakBersedia => [
                    StatusDokumenPengajuan::Terverifikasi,
                    StatusKesediaanBimbingan::Ditolak,
                    HasilKesediaanBimbingan::TidakBersedia,
                ],
                KeputusanHasilKonsultasi::UploadTidakValid => [
                    StatusDokumenPengajuan::Ditolak,
                    StatusKesediaanBimbingan::UploadTidakValid,
                    null,
                ],
            };

            $dokumenTerkunci->forceFill([
                'status' => $statusDokumen,
                'verified_by' => $user->id,
                'verified_at' => $waktu,
                'catatan_verifikasi' => $catatan,
            ])->save();
            $kesediaanTerkunci->forceFill([
                'status' => $statusKesediaan,
                'hasil' => $hasil,
                'catatan_verifikasi' => $catatan,
                'diverifikasi_oleh' => $user->id,
                'diverifikasi_at' => $waktu,
            ])->save();

            $this->audit->execute($user, $dokumenTerkunci, 'hasil_konsultasi_diverifikasi', [
                'status' => StatusDokumenPengajuan::MenungguVerifikasi->value,
            ], [
                'status' => $statusDokumen->value,
                'keputusan' => $keputusan->value,
                'status_kesediaan' => $statusKesediaan->value,
                'memiliki_catatan' => $catatan !== null,
            ]);

            return $kesediaanTerkunci->refresh()->load('skripsi');
        });
    }

    /**
     * @throws ValidationException
     */
    private function pastikanDapatDiverifikasi(
        DokumenPengajuan $dokumen,
        KesediaanBimbingan $kesediaan
    ): void {
        $dokumenTerakhirId = DokumenPengajuan::query()
            ->where('documentable_type', KesediaanBimbingan::class)
            ->where('documentable_id', $kesediaan->id)
            ->where('jenis', JenisDokumenPengajuan::HasilKonsultasi)
            ->orderByDesc('versi')
            ->value('id');

        if ($dokumen->documentable_type !== KesediaanBimbingan::class
            || $dokumen->jenis !== JenisDokumenPengajuan::HasilKonsultasi
            || $dokumen->id !== $dokumenTerakhirId
            || $dokumen->status !== StatusDokumenPengajuan::MenungguVerifikasi
            || $kesediaan->status !== StatusKesediaanBimbingan::MenungguVerifikasi) {
            throw ValidationException::withMessages([
                'dokumen' => 'Hanya upload terbaru yang sedang menunggu verifikasi yang dapat diputuskan.',
            ]);
        }
    }
}
