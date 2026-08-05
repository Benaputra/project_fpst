<?php

namespace Database\Factories;

use App\Models\Dosen;
use App\Models\Mahasiswa;
use App\Models\ProgramStudi;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Mahasiswa>
 */
class MahasiswaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nim' => fake()->unique()->numerify('############'),
            'nama' => fake()->name(),
            'program_studi_id' => ProgramStudi::factory(),
            'no_hp' => fake()->numerify('08##########'),
            'tempat_lahir' => fake()->city(),
            'tanggal_lahir' => fake()->dateTimeBetween('-30 years', '-18 years')->format('Y-m-d'),
            'angkatan' => fake()->numberBetween(2018, (int) date('Y')),
            'pembimbing_akademik_id' => Dosen::factory(),
            'user_id' => null,
        ];
    }
}
