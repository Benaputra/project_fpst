<?php

namespace App\Actions\Dokumen;

use App\Enums\JenisDokumenPengajuan;
use App\Enums\StatusDokumenPengajuan;
use App\Enums\StatusKesediaanBimbingan;
use App\Models\DokumenPengajuan;
use App\Models\KesediaanBimbingan;
use App\Models\User;
use App\Services\Audit\CatatAktivitas;
use App\Services\Upload\ValidasiHasilKonsultasi;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class UploadHasilKonsultasi
{
    public function __construct(
        private readonly ValidasiHasilKonsultasi $validator,
        private readonly CatatAktivitas $audit
    ) {}

    /**
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function execute(
        User $user,
        KesediaanBimbingan $kesediaan,
        UploadedFile $file,
        ?string $catatanMahasiswa = null
    ): DokumenPengajuan {
        Gate::forUser($user)->authorize('uploadHasilKonsultasi', $kesediaan);
        $berkas = $this->validator->execute($file);
        $catatanMahasiswa = trim((string) $catatanMahasiswa);
        $catatanMahasiswa = $catatanMahasiswa === '' ? null : $catatanMahasiswa;
        $pathBaru = null;

        try {
            return DB::transaction(function () use (
                $user,
                $kesediaan,
                $berkas,
                $catatanMahasiswa,
                &$pathBaru
            ) {
                $kesediaanTerkunci = KesediaanBimbingan::query()
                    ->with('skripsi.mahasiswa')
                    ->lockForUpdate()
                    ->findOrFail($kesediaan->getKey());
                Gate::forUser($user)->authorize(
                    'uploadHasilKonsultasi',
                    $kesediaanTerkunci
                );

                $dokumenSebelumnya = DokumenPengajuan::query()
                    ->where('documentable_type', KesediaanBimbingan::class)
                    ->where('documentable_id', $kesediaanTerkunci->id)
                    ->where('jenis', JenisDokumenPengajuan::HasilKonsultasi)
                    ->lockForUpdate()
                    ->orderByDesc('versi')
                    ->get();

                if ($dokumenSebelumnya->contains(
                    fn (DokumenPengajuan $dokumen) => $dokumen->status
                        === StatusDokumenPengajuan::Terverifikasi
                )) {
                    throw ValidationException::withMessages([
                        'hasil_konsultasi' => 'File yang telah terverifikasi tidak dapat diganti.',
                    ]);
                }

                $versi = ((int) $dokumenSebelumnya->max('versi')) + 1;
                $programStudiId = (int) $kesediaanTerkunci
                    ->skripsi
                    ->mahasiswa
                    ->program_studi_id;
                $pathBaru = sprintf(
                    'dokumen/kesediaan/%d/%d/hasil-konsultasi/v%d-%s.%s',
                    $programStudiId,
                    $kesediaanTerkunci->id,
                    $versi,
                    $berkas['hash'],
                    $berkas['extension']
                );

                if (Storage::disk('local')->exists($pathBaru)) {
                    throw ValidationException::withMessages([
                        'hasil_konsultasi' => 'Path arsip dokumen sudah digunakan dan tidak boleh ditimpa.',
                    ]);
                }

                $tersimpan = Storage::disk('local')->put($pathBaru, $berkas['content']);
                if (! $tersimpan
                    || ! Storage::disk('local')->exists($pathBaru)
                    || hash('sha256', Storage::disk('local')->get($pathBaru)) !== $berkas['hash']) {
                    throw ValidationException::withMessages([
                        'hasil_konsultasi' => 'File gagal disimpan secara utuh.',
                    ]);
                }

                foreach ($dokumenSebelumnya as $dokumenLama) {
                    if (in_array($dokumenLama->status, [
                        StatusDokumenPengajuan::Diunggah,
                        StatusDokumenPengajuan::MenungguVerifikasi,
                    ], true)) {
                        $dokumenLama->forceFill([
                            'status' => StatusDokumenPengajuan::Dibatalkan,
                        ])->save();
                    }
                }

                $waktuUpload = now();
                $dokumen = DokumenPengajuan::query()->forceCreate([
                    'documentable_type' => KesediaanBimbingan::class,
                    'documentable_id' => $kesediaanTerkunci->id,
                    'jenis' => JenisDokumenPengajuan::HasilKonsultasi,
                    'versi' => $versi,
                    'file_path' => $pathBaru,
                    'file_hash' => $berkas['hash'],
                    'status' => StatusDokumenPengajuan::MenungguVerifikasi,
                    'uploaded_by' => $user->id,
                    'uploaded_at' => $waktuUpload,
                    'verified_by' => null,
                    'verified_at' => null,
                    'catatan_verifikasi' => null,
                ]);

                $kesediaanTerkunci->forceFill([
                    'status' => StatusKesediaanBimbingan::MenungguVerifikasi,
                    'hasil' => null,
                    'catatan_mahasiswa' => $catatanMahasiswa,
                    'catatan_verifikasi' => null,
                    'uploaded_at' => $waktuUpload,
                    'diverifikasi_oleh' => null,
                    'diverifikasi_at' => null,
                ])->save();

                $this->audit->execute($user, $dokumen, 'hasil_konsultasi_diunggah', [], [
                    'jenis' => JenisDokumenPengajuan::HasilKonsultasi->value,
                    'versi' => $versi,
                    'status' => StatusDokumenPengajuan::MenungguVerifikasi->value,
                ]);

                return $dokumen->load(['documentable', 'pengunggah']);
            });
        } catch (Throwable $exception) {
            if ($pathBaru !== null
                && ! DokumenPengajuan::query()->where('file_path', $pathBaru)->exists()) {
                Storage::disk('local')->delete($pathBaru);
            }

            throw $exception;
        }
    }
}
