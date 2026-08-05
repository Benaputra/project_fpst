<?php

namespace Database\Factories;

use App\Enums\PeranKesediaanBimbingan;
use App\Enums\StatusKesediaanBimbingan;
use App\Models\Dosen;
use App\Models\KesediaanBimbingan;
use App\Models\Skripsi;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KesediaanBimbingan>
 */
class KesediaanBimbinganFactory extends Factory
{
    public function definition(): array
    {
        return [
            'skripsi_id' => Skripsi::factory(),
            'dosen_id' => Dosen::factory(),
            'peran' => PeranKesediaanBimbingan::Pembimbing1,
            'siklus' => 1,
            'status' => StatusKesediaanBimbingan::Ditunjuk,
            'hasil' => null,
            'catatan_mahasiswa' => null,
            'catatan_verifikasi' => null,
            'uploaded_at' => null,
            'diverifikasi_oleh' => null,
            'diverifikasi_at' => null,
        ];
    }

    public function pembimbing2(): static
    {
        return $this->state(fn () => [
            'peran' => PeranKesediaanBimbingan::Pembimbing2,
        ]);
    }
}
