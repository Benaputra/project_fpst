<?php

namespace App\Actions\Seminar;

use App\Enums\JenisDokumenPengajuan;
use App\Enums\StatusDokumenPengajuan;
use App\Enums\StatusSeminar;
use App\Enums\StatusSkripsi;
use App\Models\DokumenPengajuan;
use App\Models\Seminar;
use App\Models\Skripsi;
use App\Models\User;
use App\Services\Audit\CatatAktivitas;
use App\Services\Upload\ValidasiUploadPrivat;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class AjukanSeminar
{
    public function __construct(
        private readonly ValidasiUploadPrivat $validator,
        private readonly CatatAktivitas $audit
    ) {}

    public function execute(User $user, Skripsi $skripsi, ?UploadedFile $file = null): Seminar
    {
        $metadata = $file === null ? null : $this->validator->execute($file, 'berkas_seminar');
        $pathBaru = null;

        try {
            return DB::transaction(function () use ($user, $skripsi, $metadata, &$pathBaru) {
                $skripsiTerkunci = Skripsi::query()
                    ->with('mahasiswa')
                    ->lockForUpdate()
                    ->findOrFail($skripsi->getKey());
                $seminar = Seminar::query()
                    ->where('skripsi_id', $skripsiTerkunci->id)
                    ->lockForUpdate()
                    ->first();
                if ($seminar === null) {
                    $seminar = new Seminar;
                    $seminar->forceFill(['skripsi_id' => $skripsiTerkunci->id]);
                }
                $statusSebelum = $seminar->exists ? $seminar->status->value : null;
                $seminar->setRelation('skripsi', $skripsiTerkunci);
                Gate::forUser($user)->authorize('submit', $seminar);

                if ($skripsiTerkunci->status !== StatusSkripsi::SiapSeminar) {
                    throw ValidationException::withMessages([
                        'skripsi' => 'Skripsi belum berstatus siap seminar.',
                    ]);
                }
                if ($seminar->exists && $seminar->status !== StatusSeminar::Ditolak) {
                    throw ValidationException::withMessages([
                        'seminar' => 'Pengajuan seminar sedang atau sudah diproses.',
                    ]);
                }

                $seminar->forceFill([
                    'status' => StatusSeminar::Diajukan,
                    'catatan_reject' => null,
                    'verified_by' => null,
                    'verified_at' => null,
                ])->save();

                if ($metadata !== null) {
                    $versi = ((int) DokumenPengajuan::query()
                        ->where('documentable_type', Seminar::class)
                        ->where('documentable_id', $seminar->id)
                        ->where('jenis', JenisDokumenPengajuan::BerkasSeminar)
                        ->lockForUpdate()
                        ->max('versi')) + 1;
                    $pathBaru = sprintf(
                        'dokumen/seminar/%d/v%d-%s.%s',
                        $seminar->id,
                        $versi,
                        $metadata['hash'],
                        $metadata['extension']
                    );
                    if (Storage::disk('local')->exists($pathBaru)
                        || ! Storage::disk('local')->put($pathBaru, $metadata['content'])) {
                        throw ValidationException::withMessages([
                            'berkas_seminar' => 'Berkas gagal disimpan secara aman.',
                        ]);
                    }
                    DokumenPengajuan::query()->forceCreate([
                        'documentable_type' => Seminar::class,
                        'documentable_id' => $seminar->id,
                        'jenis' => JenisDokumenPengajuan::BerkasSeminar,
                        'versi' => $versi,
                        'file_path' => $pathBaru,
                        'file_hash' => $metadata['hash'],
                        'status' => StatusDokumenPengajuan::MenungguVerifikasi,
                        'uploaded_by' => $user->id,
                        'uploaded_at' => now(),
                    ]);
                }

                $this->audit->execute($user, $seminar, 'seminar_diajukan', [
                    'status' => $statusSebelum,
                ], [
                    'status' => StatusSeminar::Diajukan->value,
                    'memiliki_berkas_baru' => $metadata !== null,
                ]);

                return $seminar->refresh()->load(['skripsi.pembimbing1', 'dokumenPengajuan']);
            }, 3);
        } catch (Throwable $exception) {
            if ($pathBaru !== null
                && ! DokumenPengajuan::query()->where('file_path', $pathBaru)->exists()) {
                Storage::disk('local')->delete($pathBaru);
            }

            throw $exception;
        }
    }
}
