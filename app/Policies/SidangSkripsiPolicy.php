<?php

namespace App\Policies;

use App\Models\SidangSkripsi;
use App\Models\User;

class SidangSkripsiPolicy
{
    public function submit(User $user, SidangSkripsi $sidang): bool
    {
        return $user->isMahasiswa() && $user->mahasiswa()->where('nim', $sidang->skripsi->nim)->exists();
    }

    public function verify(User $user, SidangSkripsi $sidang): bool
    {
        $prodi = (int) $sidang->skripsi->mahasiswa->program_studi_id;

        return $user->isAdminUtama() || $user->memilikiAksesAdministratifKeProgramStudi($prodi) || $user->isKetuaProdiUntuk($prodi);
    }

    public function schedule(User $user, SidangSkripsi $sidang): bool
    {
        return $this->verify($user, $sidang);
    }

    public function terbitkanSurat(User $user, SidangSkripsi $sidang): bool
    {
        return $this->verify($user, $sidang);
    }
}
