<?php

namespace Database\Factories;

use App\Enums\JenisSurat;
use App\Enums\StatusSurat;
use App\Models\KesediaanBimbingan;
use App\Models\Surat;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Surat>
 */
class SuratFactory extends Factory
{
    public function definition(): array
    {
        return [
            'suratable_id' => KesediaanBimbingan::factory(),
            'suratable_type' => KesediaanBimbingan::class,
            'program_studi_id' => fn (array $attributes) => KesediaanBimbingan::query()
                ->with('skripsi.mahasiswa')
                ->findOrFail($attributes['suratable_id'])
                ->skripsi
                ->mahasiswa
                ->program_studi_id,
            'jenis_surat' => JenisSurat::KesediaanPembimbing,
            'no_surat' => 'SURAT-FAKE-'.Str::uuid(),
            'tujuan_surat' => fake()->name(),
            'versi' => 1,
            'status' => StatusSurat::Draft,
            'file_path' => null,
            'file_hash' => null,
            'generated_at' => null,
            'verified_by' => null,
            'verified_at' => null,
            'signed_by' => null,
            'signed_at' => null,
        ];
    }

    public function diterbitkan(): static
    {
        return $this->state(function () {
            $hash = hash('sha256', fake()->uuid());

            return [
                'status' => StatusSurat::Diterbitkan,
                'file_path' => 'surat/testing/'.$hash.'.pdf',
                'file_hash' => $hash,
                'generated_at' => now(),
            ];
        });
    }
}
