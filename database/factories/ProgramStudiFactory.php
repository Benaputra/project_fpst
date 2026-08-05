<?php

namespace Database\Factories;

use App\Models\ProgramStudi;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProgramStudi>
 */
class ProgramStudiFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nama' => fake()->unique()->words(3, true),
            'ketua_prodi_id' => null,
            'ttd_ketua_prodi' => null,
        ];
    }
}
