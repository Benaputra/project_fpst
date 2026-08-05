<?php

namespace App\Actions\Sidang;

use App\Enums\JenisDokumenPengajuan;
use App\Enums\StatusDokumenPengajuan;
use App\Enums\StatusSeminar;
use App\Enums\StatusSidangSkripsi;
use App\Enums\StatusSkripsi;
use App\Models\DokumenPengajuan;
use App\Models\Seminar;
use App\Models\SidangSkripsi;
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

class AjukanSidang
{
    public function __construct(
        private readonly ValidasiUploadPrivat $validator,
        private readonly CatatAktivitas $audit
    ) {}

    public function execute(User $user, Skripsi $skripsi, ?UploadedFile $file = null): SidangSkripsi
    {
        $meta = $file ? $this->validator->execute($file, 'berkas_sidang') : null;
        $path = null;
        try {
            return DB::transaction(function () use ($user, $skripsi, $meta, &$path) {
                $s = Skripsi::query()->with('mahasiswa')->lockForUpdate()->findOrFail($skripsi->id);
                $seminar = Seminar::query()->where('skripsi_id', $s->id)->lockForUpdate()->first();
                $sidang = SidangSkripsi::query()->where('skripsi_id', $s->id)->lockForUpdate()->first() ?? new SidangSkripsi;
                $sidang->forceFill(['skripsi_id' => $s->id]);
                $statusSebelum = $sidang->exists ? $sidang->status->value : null;
                $sidang->setRelation('skripsi', $s);
                Gate::forUser($user)->authorize('submit', $sidang);
                if ($s->status !== StatusSkripsi::SiapSidang || $seminar?->status !== StatusSeminar::Selesai) {
                    throw ValidationException::withMessages(['skripsi' => 'Skripsi dan seminar belum memenuhi status untuk sidang.']);
                }
                if ($sidang->exists && $sidang->status !== StatusSidangSkripsi::Ditolak) {
                    throw ValidationException::withMessages(['sidang' => 'Pengajuan sidang sedang atau sudah diproses.']);
                }
                $sidang->forceFill(['status' => StatusSidangSkripsi::Diajukan, 'catatan_reject' => null, 'verified_by' => null, 'verified_at' => null])->save();
                if ($meta) {
                    $versi = ((int) DokumenPengajuan::query()->whereMorphedTo('documentable', $sidang)->where('jenis', JenisDokumenPengajuan::BerkasSidang)->lockForUpdate()->max('versi')) + 1;
                    $path = "dokumen/sidang/{$sidang->id}/v{$versi}-{$meta['hash']}.{$meta['extension']}";
                    if (Storage::disk('local')->exists($path) || ! Storage::disk('local')->put($path, $meta['content'])) {
                        throw ValidationException::withMessages(['berkas_sidang' => 'Berkas sidang gagal disimpan.']);
                    }
                    DokumenPengajuan::query()->forceCreate(['documentable_type' => SidangSkripsi::class, 'documentable_id' => $sidang->id,
                        'jenis' => JenisDokumenPengajuan::BerkasSidang, 'versi' => $versi, 'file_path' => $path,
                        'file_hash' => $meta['hash'], 'status' => StatusDokumenPengajuan::MenungguVerifikasi,
                        'uploaded_by' => $user->id, 'uploaded_at' => now()]);
                }

                $this->audit->execute($user, $sidang, 'sidang_diajukan', [
                    'status' => $statusSebelum,
                ], [
                    'status' => StatusSidangSkripsi::Diajukan->value,
                    'memiliki_berkas_baru' => $meta !== null,
                ]);

                return $sidang->refresh()->load('dokumenPengajuan');
            }, 3);
        } catch (Throwable $e) {
            if ($path !== null && ! DokumenPengajuan::query()->where('file_path', $path)->exists()) {
                Storage::disk('local')->delete($path);
            }
            throw $e;
        }
    }
}
