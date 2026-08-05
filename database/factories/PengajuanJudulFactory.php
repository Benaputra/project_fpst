<?php

namespace Database\Factories;

use App\Enums\StatusPengajuanJudul;
use App\Models\Dosen;
use App\Models\Mahasiswa;
use App\Models\PengajuanJudul;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PengajuanJudul>
 */
class PengajuanJudulFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nim' => Mahasiswa::factory(),
            'judul' => rtrim(fake()->sentence(10), '.'),
            'status' => StatusPengajuanJudul::Diajukan,
            'catatan_reject' => null,
            'diverifikasi_oleh' => null,
            'diverifikasi_at' => null,
        ];
    }

    public function diverifikasi(?Dosen $verifikator = null): static
    {
        return $this->state(fn () => [
            'status' => StatusPengajuanJudul::Diverifikasi,
            'catatan_reject' => null,
            'diverifikasi_oleh' => $verifikator?->nidn ?? Dosen::factory(),
            'diverifikasi_at' => now(),
        ]);
    }

    public function ditolak(?Dosen $verifikator = null): static
    {
        return $this->state(fn () => [
            'status' => StatusPengajuanJudul::Ditolak,
            'catatan_reject' => fake()->sentence(),
            'diverifikasi_oleh' => $verifikator?->nidn ?? Dosen::factory(),
            'diverifikasi_at' => now(),
        ]);
    }
}
