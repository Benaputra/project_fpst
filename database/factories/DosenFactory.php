<?php

namespace Database\Factories;

use App\Models\Dosen;
use App\Models\ProgramStudi;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Dosen>
 */
class DosenFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nidn' => fake()->unique()->numerify('##########'),
            'nama' => fake()->name(),
            'nuptk' => fake()->unique()->numerify('################'),
            'program_studi_id' => ProgramStudi::factory(),
            'no_hp' => fake()->numerify('08##########'),
            'tempat_lahir' => fake()->city(),
            'tanggal_lahir' => fake()->dateTimeBetween('-65 years', '-25 years')->format('Y-m-d'),
            'jabatan_fungsional' => fake()->randomElement([
                'Asisten Ahli',
                'Lektor',
                'Lektor Kepala',
            ]),
            'user_id' => null,
        ];
    }
}
