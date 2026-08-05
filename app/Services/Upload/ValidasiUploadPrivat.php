<?php

namespace App\Services\Upload;

use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

class ValidasiUploadPrivat
{
    private const MAX_BYTES = 5 * 1024 * 1024;

    /** @return array{content: string, extension: string, hash: string} */
    public function execute(UploadedFile $file, string $field): array
    {
        if (! $file->isValid() || $file->getSize() <= 0 || $file->getSize() > self::MAX_BYTES) {
            $this->gagal($field, 'File harus valid, tidak kosong, dan berukuran maksimal 5 MB.');
        }
        $path = $file->getRealPath();
        $content = $path === false ? false : file_get_contents($path);
        if (! is_string($content) || $content === '') {
            $this->gagal($field, 'Isi file tidak dapat dibaca.');
        }
        $mime = strtolower((string) $file->getMimeType());
        $extension = strtolower($file->getClientOriginalExtension());
        $valid = match ($mime) {
            'application/pdf' => $extension === 'pdf' && str_starts_with($content, '%PDF-'),
            'image/jpeg' => in_array($extension, ['jpg', 'jpeg'], true)
                && str_starts_with($content, "\xFF\xD8\xFF"),
            'image/png' => $extension === 'png'
                && str_starts_with($content, "\x89PNG\r\n\x1A\n"),
            default => false,
        };
        if (! $valid) {
            $this->gagal($field, 'File harus berupa PDF, JPG, JPEG, atau PNG yang isinya valid.');
        }

        return compact('content', 'extension') + ['hash' => hash('sha256', $content)];
    }

    private function gagal(string $field, string $pesan): never
    {
        throw ValidationException::withMessages([$field => $pesan]);
    }
}
