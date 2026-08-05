<?php

namespace App\Services\Document;

use App\Models\DokumenPengajuan;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class PastikanIntegritasDokumen
{
    /** @throws ValidationException */
    public function execute(DokumenPengajuan $dokumen): void
    {
        if (! Storage::disk('local')->exists($dokumen->file_path)) {
            throw ValidationException::withMessages([
                'dokumen' => 'File dokumen tidak ditemukan di storage privat.',
            ]);
        }

        $hash = hash('sha256', Storage::disk('local')->get($dokumen->file_path));
        if (! hash_equals($dokumen->file_hash, $hash)) {
            throw ValidationException::withMessages([
                'dokumen' => 'Hash dokumen tidak cocok sehingga verifikasi dibatalkan.',
            ]);
        }
    }
}
