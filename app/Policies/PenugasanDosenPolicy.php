<?php

namespace App\Policies;

use App\Models\PenugasanDosen;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PenugasanDosenPolicy
{
    use HandlesAuthorization;

    /**
     * Bypassing role Admin Utama secara global.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->isAdminUtama()) {
            return true;
        }

        return null;
    }

    /**
     * Menentukan apakah user berhak melihat daftar penugasan.
     */
    public function viewAny(User $user): bool
    {
        return $user->isDosen() || $user->isKaprodi() || $user->isAdmin();
    }

    /**
     * Menentukan apakah user berhak melihat penugasan tertentu.
     */
    public function view(User $user, PenugasanDosen $penugasan): bool
    {
        if ($user->id === $penugasan->dosen_id) {
            return true;
        }

        if ($user->isKaprodi() || $user->isAdminProdi()) {
            $assignable = $penugasan->assignable;
            $prodiId = $assignable?->program_studi_id ?? $assignable?->pengajuanSkripsi?->program_studi_id;
            return $user->isSameProdi($prodiId);
        }

        return false;
    }

    /**
     * Menentukan apakah user dapat merespon (konfirmasi / tolak) penugasan dosen.
     * Khusus dosen yang bersangkutan dan tiket masih menunggu.
     */
    public function respond(User $user, PenugasanDosen $penugasan): bool
    {
        return $user->id === $penugasan->dosen_id;
    }
}
