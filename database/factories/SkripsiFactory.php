<?php

namespace Database\Factories;

use App\Enums\StatusPengajuanJudul;
use App\Enums\StatusSkripsi;
use App\Models\PengajuanJudul;
use App\Models\Skripsi;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Skripsi>
 */
class SkripsiFactory extends Factory
{
    public function definition(): array
    {
        return [
            'pengajuan_judul_id' => PengajuanJudul::factory()->state([
                'status' => StatusPengajuanJudul::Diverifikasi,
            ]),
            'nim' => fn (array $attributes) => PengajuanJudul::query()
                ->findOrFail($attributes['pengajuan_judul_id'])
                ->nim,
            'judul' => fn (array $attributes) => PengajuanJudul::query()
                ->findOrFail($attributes['pengajuan_judul_id'])
                ->judul,
            'pembimbing1_id' => null,
            'pembimbing2_id' => null,
            'status' => StatusSkripsi::MenungguKesediaanPembimbing,
        ];
    }
}
