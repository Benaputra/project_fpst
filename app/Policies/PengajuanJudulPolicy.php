<?php

namespace App\Policies;

use App\Enums\StatusPengajuanJudul;
use App\Models\PengajuanJudul;
use App\Models\User;

class PengajuanJudulPolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->isAdminUtama()) {
            return true;
        }

        if ($user->isMahasiswa()) {
            return true;
        }

        if ($user->isAdminProdi()) {
            return $user->programStudiAdministrasi()->exists();
        }

        return $user->isKetuaProdi();
    }

    public function view(User $user, PengajuanJudul $pengajuanJudul): bool
    {
        if ($user->isAdminUtama()) {
            return true;
        }

        if ($this->adalahPemilik($user, $pengajuanJudul)) {
            return true;
        }

        $programStudiId = $this->programStudiId($pengajuanJudul);

        if ($user->isAdminProdi()) {
            return $user->memilikiAksesAdministratifKeProgramStudi($programStudiId);
        }

        return $user->isKetuaProdiUntuk($programStudiId);
    }

    public function create(User $user): bool
    {
        if (! $user->isMahasiswa()) {
            return false;
        }

        return $user->mahasiswa()
            ->whereDoesntHave('pengajuanJudul')
            ->exists();
    }

    public function update(User $user, PengajuanJudul $pengajuanJudul): bool
    {
        return $pengajuanJudul->status === StatusPengajuanJudul::Ditolak
            && $this->adalahPemilik($user, $pengajuanJudul);
    }

    public function terima(User $user, PengajuanJudul $pengajuanJudul): bool
    {
        return $user->isKetuaProdiUntuk($this->programStudiId($pengajuanJudul));
    }

    public function tolak(User $user, PengajuanJudul $pengajuanJudul): bool
    {
        return $user->isKetuaProdiUntuk($this->programStudiId($pengajuanJudul));
    }

    public function tetapkanCalonPembimbing(User $user, PengajuanJudul $pengajuanJudul): bool
    {
        return $user->isKetuaProdiUntuk($this->programStudiId($pengajuanJudul));
    }

    public function delete(User $user, PengajuanJudul $pengajuanJudul): bool
    {
        return false;
    }

    public function restore(User $user, PengajuanJudul $pengajuanJudul): bool
    {
        return false;
    }

    public function forceDelete(User $user, PengajuanJudul $pengajuanJudul): bool
    {
        return false;
    }

    private function adalahPemilik(User $user, PengajuanJudul $pengajuanJudul): bool
    {
        if (! $user->isMahasiswa()) {
            return false;
        }

        return $user->mahasiswa()
            ->where('nim', $pengajuanJudul->nim)
            ->exists();
    }

    private function programStudiId(PengajuanJudul $pengajuanJudul): int
    {
        return (int) $pengajuanJudul->mahasiswa()
            ->firstOrFail()
            ->program_studi_id;
    }
}
