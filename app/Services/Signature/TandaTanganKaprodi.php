<?php

namespace App\Services\Signature;

use App\Models\ProgramStudi;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class TandaTanganKaprodi
{
    /** @throws ValidationException */
    public function dataUri(ProgramStudi $programStudi): string
    {
        $path = trim((string) $programStudi->ttd_ketua_prodi);
        $prefix = "tanda-tangan/kaprodi/{$programStudi->id}/";
        if ($path === ''
            || str_contains($path, '..')
            || ! str_starts_with($path, $prefix)
            || ! Storage::disk('local')->exists($path)) {
            throw ValidationException::withMessages([
                'tanda_tangan' => 'File tanda tangan Kaprodi privat belum tersedia atau path tidak valid.',
            ]);
        }

        $bytes = Storage::disk('local')->get($path);
        $info = @getimagesizefromstring($bytes);
        $mime = is_array($info) ? ($info['mime'] ?? null) : null;
        if (! in_array($mime, ['image/png', 'image/jpeg'], true)) {
            throw ValidationException::withMessages([
                'tanda_tangan' => 'File tanda tangan Kaprodi harus berupa PNG atau JPEG yang valid.',
            ]);
        }

        return sprintf('data:%s;base64,%s', $mime, base64_encode($bytes));
    }
}
