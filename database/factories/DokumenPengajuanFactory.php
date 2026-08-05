<?php

namespace Database\Factories;

use App\Enums\JenisDokumenPengajuan;
use App\Enums\StatusDokumenPengajuan;
use App\Models\DokumenPengajuan;
use App\Models\KesediaanBimbingan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<DokumenPengajuan>
 */
class DokumenPengajuanFactory extends Factory
{
    public function definition(): array
    {
        $hash = hash('sha256', Str::uuid()->toString());

        return [
            'documentable_id' => KesediaanBimbingan::factory(),
            'documentable_type' => KesediaanBimbingan::class,
            'jenis' => JenisDokumenPengajuan::HasilKonsultasi,
            'versi' => 1,
            'file_path' => 'dokumen/testing/'.$hash.'.pdf',
            'file_hash' => $hash,
            'status' => StatusDokumenPengajuan::MenungguVerifikasi,
            'uploaded_by' => User::factory()->mahasiswa(),
            'uploaded_at' => now(),
            'verified_by' => null,
            'verified_at' => null,
            'catatan_verifikasi' => null,
        ];
    }
}
