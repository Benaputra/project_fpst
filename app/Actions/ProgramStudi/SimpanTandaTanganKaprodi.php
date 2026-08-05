<?php

namespace App\Actions\ProgramStudi;

use App\Models\ProgramStudi;
use App\Models\User;
use App\Services\Audit\CatatAktivitas;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class SimpanTandaTanganKaprodi
{
    public function __construct(private readonly CatatAktivitas $audit) {}

    /**
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function execute(User $user, UploadedFile $file): ProgramStudi
    {
        $programStudi = $user->dosen?->programStudiDipimpin;
        if (! $programStudi instanceof ProgramStudi
            || ! $user->isKetuaProdiUntuk((int) $programStudi->getKey())) {
            throw new AuthorizationException('Tanda tangan hanya dapat dikelola oleh Kaprodi aktif.');
        }

        $bytes = $file->get();
        $info = @getimagesizefromstring($bytes);
        $mime = is_array($info) ? ($info['mime'] ?? null) : null;
        $extension = match ($mime) {
            'image/png' => 'png',
            'image/jpeg' => 'jpg',
            default => throw ValidationException::withMessages([
                'tanda_tangan' => 'File tanda tangan harus berupa PNG atau JPEG yang valid.',
            ]),
        };
        $hash = hash('sha256', $bytes);
        $path = "tanda-tangan/kaprodi/{$programStudi->getKey()}/ttd-{$hash}.{$extension}";
        $pathLama = trim((string) $programStudi->ttd_ketua_prodi);

        if (! Storage::disk('local')->put($path, $bytes)) {
            throw ValidationException::withMessages([
                'tanda_tangan' => 'File tanda tangan gagal disimpan.',
            ]);
        }

        try {
            DB::transaction(function () use ($user, $programStudi, $path, $pathLama): void {
                $programStudi->forceFill(['ttd_ketua_prodi' => $path])->save();
                $this->audit->execute($user, $programStudi, 'tanda_tangan_kaprodi_diperbarui', [
                    'tersedia' => $pathLama !== '',
                ], [
                    'tersedia' => true,
                ]);
            });
        } catch (Throwable $exception) {
            if ($path !== $pathLama) {
                Storage::disk('local')->delete($path);
            }

            throw $exception;
        }

        $prefix = "tanda-tangan/kaprodi/{$programStudi->getKey()}/";
        if ($pathLama !== ''
            && $pathLama !== $path
            && str_starts_with($pathLama, $prefix)
            && ! str_contains($pathLama, '..')) {
            Storage::disk('local')->delete($pathLama);
        }

        return $programStudi->refresh();
    }
}
