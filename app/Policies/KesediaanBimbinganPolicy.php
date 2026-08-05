<?php

namespace App\Policies;

use App\Enums\StatusKesediaanBimbingan;
use App\Models\KesediaanBimbingan;
use App\Models\User;

class KesediaanBimbinganPolicy
{
    public function gantiCalon(User $user, KesediaanBimbingan $kesediaan): bool
    {
        $programStudiId = (int) $kesediaan->skripsi->mahasiswa->program_studi_id;

        return $user->isKetuaProdiUntuk($programStudiId);
    }

    public function uploadHasilKonsultasi(User $user, KesediaanBimbingan $kesediaan): bool
    {
        if (! $user->isMahasiswa()
            || ! in_array($kesediaan->status, [
                StatusKesediaanBimbingan::MenungguUpload,
                StatusKesediaanBimbingan::UploadTidakValid,
            ], true)) {
            return false;
        }

        return $user->mahasiswa()
            ->where('nim', $kesediaan->skripsi->nim)
            ->exists();
    }

    public function terbitkanSurat(User $user, KesediaanBimbingan $kesediaan): bool
    {
        $programStudiId = (int) $kesediaan->skripsi->mahasiswa->program_studi_id;

        return $user->isAdminUtama()
            || $user->memilikiAksesAdministratifKeProgramStudi($programStudiId)
            || $user->isKetuaProdiUntuk($programStudiId);
    }
}
