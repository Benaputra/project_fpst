<?php

namespace App\Policies;

use App\Models\DokumenPengajuan;
use App\Models\KesediaanBimbingan;
use App\Models\Seminar;
use App\Models\SidangSkripsi;
use App\Models\Skripsi;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class DokumenPengajuanPolicy
{
    public function view(User $user, DokumenPengajuan $dokumen): bool
    {
        $subjek = $dokumen->documentable;
        $skripsi = $this->skripsi($subjek);
        if ($skripsi === null) {
            return false;
        }

        $programStudiId = (int) $skripsi->mahasiswa->program_studi_id;
        if ($user->isAdminUtama()) {
            return true;
        }

        if ($user->isMahasiswa()) {
            return $user->mahasiswa()
                ->where('nim', $skripsi->nim)
                ->exists();
        }

        if ($user->isAdminProdi()) {
            return $user->memilikiAksesAdministratifKeProgramStudi($programStudiId);
        }

        return $user->isKetuaProdiUntuk($programStudiId);
    }

    public function download(User $user, DokumenPengajuan $dokumen): bool
    {
        return $this->view($user, $dokumen);
    }

    public function verify(User $user, DokumenPengajuan $dokumen): bool
    {
        $kesediaan = $dokumen->documentable;
        if (! $kesediaan instanceof KesediaanBimbingan) {
            return false;
        }

        $programStudiId = (int) $kesediaan->skripsi->mahasiswa->program_studi_id;

        return $user->isAdminUtama()
            || $user->memilikiAksesAdministratifKeProgramStudi($programStudiId)
            || $user->isKetuaProdiUntuk($programStudiId);
    }

    public function update(User $user, DokumenPengajuan $dokumen): bool
    {
        return false;
    }

    public function delete(User $user, DokumenPengajuan $dokumen): bool
    {
        return false;
    }

    private function skripsi(?Model $subjek): ?Skripsi
    {
        return match (true) {
            $subjek instanceof KesediaanBimbingan => $subjek->skripsi,
            $subjek instanceof Seminar => $subjek->skripsi,
            $subjek instanceof SidangSkripsi => $subjek->skripsi,
            default => null,
        };
    }
}
