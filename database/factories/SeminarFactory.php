<?php

namespace Database\Factories;

use App\Enums\StatusSeminar;
use App\Models\Seminar;
use App\Models\Skripsi;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Seminar> */
class SeminarFactory extends Factory
{
    public function definition(): array
    {
        return [
            'skripsi_id' => Skripsi::factory(),
            'penguji1_id' => null,
            'penguji2_id' => null,
            'tanggal' => null,
            'tempat' => null,
            'status' => StatusSeminar::Diajukan,
            'catatan_reject' => null,
            'verified_by' => null,
            'verified_at' => null,
        ];
    }
}
