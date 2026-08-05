<?php

namespace App\Policies;

use App\Models\Seminar;
use App\Models\User;

class SeminarPolicy
{
    public function schedule(User $user, Seminar $seminar): bool
    {
        return $this->verify($user, $seminar);
    }

    public function terbitkanSurat(User $user, Seminar $seminar): bool
    {
        return $this->verify($user, $seminar);
    }

    public function submit(User $user, Seminar $seminar): bool
    {
        return $user->isMahasiswa()
            && $user->mahasiswa()->where('nim', $seminar->skripsi->nim)->exists();
    }

    public function verify(User $user, Seminar $seminar): bool
    {
        $programStudiId = (int) $seminar->skripsi->mahasiswa->program_studi_id;

        return $user->isAdminUtama()
            || $user->memilikiAksesAdministratifKeProgramStudi($programStudiId)
            || $user->isKetuaProdiUntuk($programStudiId);
    }

    public function view(User $user, Seminar $seminar): bool
    {
        return $this->submit($user, $seminar) || $this->verify($user, $seminar);
    }
}
