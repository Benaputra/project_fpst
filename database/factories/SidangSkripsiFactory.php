<?php

namespace Database\Factories;

use App\Enums\StatusSidangSkripsi;
use App\Models\SidangSkripsi;
use App\Models\Skripsi;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SidangSkripsi> */
class SidangSkripsiFactory extends Factory
{
    public function definition(): array
    {
        return ['skripsi_id' => Skripsi::factory(), 'penguji1_id' => null, 'penguji2_id' => null,
            'tanggal' => null, 'tempat' => null, 'status' => StatusSidangSkripsi::Diajukan,
            'catatan_reject' => null, 'verified_by' => null, 'verified_at' => null];
    }
}
