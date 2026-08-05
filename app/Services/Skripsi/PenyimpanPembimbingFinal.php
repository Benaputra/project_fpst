<?php

namespace App\Services\Skripsi;

use App\Enums\StatusSkripsi;
use App\Models\Skripsi;

class PenyimpanPembimbingFinal
{
    public function execute(
        Skripsi $skripsi,
        string $pembimbing1Id,
        ?string $pembimbing2Id
    ): void {
        $skripsi->forceFill([
            'pembimbing1_id' => $pembimbing1Id,
            'pembimbing2_id' => $pembimbing2Id,
            'status' => StatusSkripsi::BimbinganAktif,
        ])->save();
    }
}
