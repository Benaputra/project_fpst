<?php

namespace App\Policies;

use App\Models\KesediaanBimbingan;
use App\Models\Seminar;
use App\Models\SidangSkripsi;
use App\Models\Skripsi;
use App\Models\Surat;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class SuratPolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->isAdminUtama() || $user->isKetuaProdi()) {
            return true;
        }

        if ($user->isAdminProdi()) {
            return $user->programStudiAdministrasi()->exists();
        }

        return $user->isMahasiswa() && $user->mahasiswa()->exists();
    }

    public function view(User $user, Surat $surat): bool
    {
        $programStudiId = $this->programStudiIdSubjek($surat);
        if ($programStudiId === null || $programStudiId !== (int) $surat->program_studi_id) {
            return false;
        }

        if ($user->isAdminUtama()) {
            return true;
        }

        if ($user->isMahasiswa()) {
            $nimPemilik = $this->nimPemilik($surat->suratable);

            return $nimPemilik !== null
                && $user->mahasiswa()->where('nim', $nimPemilik)->exists();
        }

        if ($user->isAdminProdi()) {
            return $user->memilikiAksesAdministratifKeProgramStudi($programStudiId);
        }

        return $user->isKetuaProdiUntuk($programStudiId);
    }

    public function download(User $user, Surat $surat): bool
    {
        return $this->view($user, $surat);
    }

    public function verify(User $user, Surat $surat): bool
    {
        $programStudiId = $this->programStudiIdSubjek($surat);
        if ($programStudiId === null || $programStudiId !== (int) $surat->program_studi_id) {
            return false;
        }

        return $user->isAdminUtama()
            || $user->memilikiAksesAdministratifKeProgramStudi($programStudiId)
            || $user->isKetuaProdiUntuk($programStudiId);
    }

    public function sign(User $user, Surat $surat): bool
    {
        $programStudiId = $this->programStudiIdSubjek($surat);

        return $programStudiId !== null
            && $programStudiId === (int) $surat->program_studi_id
            && $user->isKetuaProdiUntuk($programStudiId);
    }

    public function update(User $user, Surat $surat): bool
    {
        return false;
    }

    public function delete(User $user, Surat $surat): bool
    {
        return false;
    }

    public function restore(User $user, Surat $surat): bool
    {
        return false;
    }

    public function forceDelete(User $user, Surat $surat): bool
    {
        return false;
    }

    private function programStudiIdSubjek(Surat $surat): ?int
    {
        $subject = $surat->suratable;

        return match (true) {
            $subject instanceof KesediaanBimbingan => (int) $subject->skripsi->mahasiswa->program_studi_id,
            $subject instanceof Skripsi => (int) $subject->mahasiswa->program_studi_id,
            $subject instanceof Seminar => (int) $subject->skripsi->mahasiswa->program_studi_id,
            $subject instanceof SidangSkripsi => (int) $subject->skripsi->mahasiswa->program_studi_id,
            default => null,
        };
    }

    private function nimPemilik(?Model $subject): ?string
    {
        return match (true) {
            $subject instanceof KesediaanBimbingan => $subject->skripsi->nim,
            $subject instanceof Skripsi => $subject->nim,
            $subject instanceof Seminar => $subject->skripsi->nim,
            $subject instanceof SidangSkripsi => $subject->skripsi->nim,
            default => null,
        };
    }
}
