<?php

namespace App\Policies;

use App\Models\Skripsi;
use App\Models\User;

class SkripsiPolicy
{
    public function downloadSuratKesediaanGabungan(User $user, Skripsi $skripsi): bool
    {
        return $user->isMahasiswa()
            && $user->mahasiswa()->where('nim', $skripsi->nim)->exists();
    }

    public function terbitkanSk(User $user, Skripsi $skripsi): bool
    {
        $programStudiId = (int) $skripsi->mahasiswa->program_studi_id;

        return $user->isAdminUtama()
            || $user->memilikiAksesAdministratifKeProgramStudi($programStudiId)
            || $user->isKetuaProdiUntuk($programStudiId);
    }

    public function finalisasiPembimbing(User $user, Skripsi $skripsi): bool
    {
        return $user->isKetuaProdiUntuk(
            (int) $skripsi->mahasiswa->program_studi_id
        );
    }
}
