<?php

namespace App\Services\Surat;

use App\Enums\StatusSurat;
use App\Models\Surat;
use App\Models\User;
use App\Services\Audit\CatatAktivitas;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ArsipPdfSurat
{
    public function __construct(private readonly CatatAktivitas $audit) {}

    /**
     * @param  Collection<int, Surat>  $versiLama
     * @param  array<string, mixed>  $metadata
     */
    public function execute(
        User $user,
        Collection $versiLama,
        string $path,
        string $pdf,
        array $metadata
    ): Surat {
        $hash = hash('sha256', $pdf);
        if (Storage::disk('local')->exists($path)) {
            throw ValidationException::withMessages([
                'surat' => 'Path arsip surat sudah digunakan dan tidak boleh ditimpa.',
            ]);
        }
        if (! Storage::disk('local')->put($path, $pdf)
            || ! Storage::disk('local')->exists($path)
            || ! hash_equals($hash, hash('sha256', Storage::disk('local')->get($path)))) {
            throw ValidationException::withMessages([
                'surat' => 'PDF surat gagal disimpan secara utuh.',
            ]);
        }

        foreach ($versiLama as $suratLama) {
            if ($suratLama->status !== StatusSurat::Dibatalkan) {
                $suratLama->forceFill(['status' => StatusSurat::Dibatalkan])->save();
            }
        }

        $surat = Surat::query()->forceCreate([
            ...$metadata,
            'file_path' => $path,
            'file_hash' => $hash,
        ]);
        $this->audit->execute($user, $surat, 'surat_diterbitkan', [], [
            'jenis_surat' => $surat->jenis_surat->value,
            'versi' => $surat->versi,
            'status' => $surat->status->value,
            'ditandatangani' => $surat->signed_by !== null,
        ]);

        return $surat;
    }
}
