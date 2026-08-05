<?php

namespace App\Actions\Skripsi;

use App\Actions\Surat\TerbitkanSuratKesediaan;
use App\Enums\HasilKesediaanBimbingan;
use App\Enums\StatusKesediaanBimbingan;
use App\Models\Dosen;
use App\Models\KesediaanBimbingan;
use App\Models\Skripsi;
use App\Models\Surat;
use App\Models\User;
use App\Services\Audit\CatatAktivitas;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class GantiCalonPembimbing
{
    public function __construct(
        private readonly TerbitkanSuratKesediaan $terbitkanSurat,
        private readonly CatatAktivitas $audit
    ) {}

    /**
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function execute(
        User $user,
        KesediaanBimbingan $kesediaanDitolak,
        string $calonPenggantiId
    ): KesediaanBimbingan {
        $calonPenggantiId = trim($calonPenggantiId);
        $pathSuratBaru = null;

        try {
            return DB::transaction(function () use (
                $user,
                $kesediaanDitolak,
                $calonPenggantiId,
                &$pathSuratBaru
            ) {
                $kesediaanAwal = KesediaanBimbingan::query()
                    ->findOrFail($kesediaanDitolak->getKey());
                $skripsi = Skripsi::query()
                    ->with('mahasiswa')
                    ->lockForUpdate()
                    ->findOrFail($kesediaanAwal->skripsi_id);
                $riwayat = KesediaanBimbingan::query()
                    ->where('skripsi_id', $skripsi->id)
                    ->lockForUpdate()
                    ->orderBy('peran')
                    ->orderBy('siklus')
                    ->get();
                $kesediaanTerkunci = $riwayat->firstWhere('id', $kesediaanAwal->id);

                if (! $kesediaanTerkunci instanceof KesediaanBimbingan) {
                    throw ValidationException::withMessages([
                        'kesediaan' => 'Riwayat calon pembimbing tidak ditemukan.',
                    ]);
                }

                $kesediaanTerkunci->setRelation('skripsi', $skripsi);
                Gate::forUser($user)->authorize('gantiCalon', $kesediaanTerkunci);
                $this->pastikanSiklusDapatDiganti($kesediaanTerkunci, $riwayat);

                if ($calonPenggantiId === '') {
                    throw ValidationException::withMessages([
                        'calon_pengganti_id' => 'Calon pembimbing pengganti wajib dipilih.',
                    ]);
                }

                $calonPengganti = Dosen::query()
                    ->lockForUpdate()
                    ->find($calonPenggantiId);
                $programStudiId = (int) $skripsi->mahasiswa->program_studi_id;
                if (! $calonPengganti instanceof Dosen
                    || (int) $calonPengganti->program_studi_id !== $programStudiId) {
                    throw ValidationException::withMessages([
                        'calon_pengganti_id' => 'Calon pengganti harus dosen dari program studi mahasiswa.',
                    ]);
                }

                if ($riwayat->contains('dosen_id', $calonPenggantiId)) {
                    throw ValidationException::withMessages([
                        'calon_pengganti_id' => 'Dosen tersebut sudah tercatat dalam riwayat calon pembimbing.',
                    ]);
                }

                $pengganti = KesediaanBimbingan::query()->create([
                    'skripsi_id' => $skripsi->id,
                    'dosen_id' => $calonPenggantiId,
                    'peran' => $kesediaanTerkunci->peran,
                    'siklus' => $kesediaanTerkunci->siklus + 1,
                ]);
                $surat = $this->terbitkanSurat->execute($user, $pengganti);
                $pathSuratBaru = $surat->file_path;

                $this->audit->execute($user, $pengganti, 'calon_pembimbing_diganti', [
                    'kesediaan_sebelumnya_id' => $kesediaanTerkunci->id,
                    'dosen_id' => $kesediaanTerkunci->dosen_id,
                    'siklus' => $kesediaanTerkunci->siklus,
                ], [
                    'dosen_id' => $pengganti->dosen_id,
                    'siklus' => $pengganti->siklus,
                ]);

                return $pengganti->refresh()->load([
                    'dosen',
                    'skripsi.pengajuanJudul',
                    'surat',
                ]);
            }, 3);
        } catch (UniqueConstraintViolationException $exception) {
            $this->hapusFileYangTerrollback($pathSuratBaru);

            throw ValidationException::withMessages([
                'kesediaan' => 'Siklus pengganti untuk posisi ini sudah dibuat.',
            ]);
        } catch (Throwable $exception) {
            $this->hapusFileYangTerrollback($pathSuratBaru);

            throw $exception;
        }
    }

    /**
     * @param  Collection<int, KesediaanBimbingan>  $riwayat
     *
     * @throws ValidationException
     */
    private function pastikanSiklusDapatDiganti(
        KesediaanBimbingan $kesediaan,
        $riwayat
    ): void {
        $siklusTerakhir = (int) $riwayat
            ->where('peran', $kesediaan->peran)
            ->max('siklus');

        if ($kesediaan->siklus !== $siklusTerakhir
            || $kesediaan->status !== StatusKesediaanBimbingan::Ditolak
            || $kesediaan->hasil !== HasilKesediaanBimbingan::TidakBersedia) {
            throw ValidationException::withMessages([
                'kesediaan' => 'Pengganti hanya dapat dibuat untuk penolakan terbaru pada posisi tersebut.',
            ]);
        }

        $statusAktif = [
            StatusKesediaanBimbingan::Ditunjuk,
            StatusKesediaanBimbingan::SuratTerbit,
            StatusKesediaanBimbingan::MenungguUpload,
            StatusKesediaanBimbingan::MenungguVerifikasi,
            StatusKesediaanBimbingan::UploadTidakValid,
        ];
        if ($riwayat->where('peran', $kesediaan->peran)
            ->contains(fn (KesediaanBimbingan $item) => in_array(
                $item->status,
                $statusAktif,
                true
            ))) {
            throw ValidationException::withMessages([
                'kesediaan' => 'Masih ada siklus aktif untuk posisi pembimbing ini.',
            ]);
        }
    }

    private function hapusFileYangTerrollback(?string $path): void
    {
        if ($path !== null && ! Surat::query()->where('file_path', $path)->exists()) {
            Storage::disk('local')->delete($path);
        }
    }
}
