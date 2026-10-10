<?php

namespace App\Policies;

use App\Models\Surat;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SuratPolicy
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
     * Menentukan apakah user berhak melihat daftar surat/arsip dokumen.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isKaprodi();
    }

    /**
     * Menentukan apakah user berhak melihat / mengunduh surat tertentu.
     * - Mahasiswa: Surat yang terkait dengan pengajuan skripsinya.
     * - Kaprodi & Admin Prodi: Terbatas pada surat di prodi yang sama.
     * - Dosen: Surat pengajuan yang bersangkutan dengan bimbingan/ujiannya.
     */
    public function view(User $user, Surat $surat): bool
    {
        if ($user->isMahasiswa()) {
            return $user->id === $surat->pengajuanSkripsi?->mahasiswa_id;
        }

        if ($user->isKaprodi() || $user->isAdminProdi()) {
            return $user->isSameProdi($surat->program_studi_id);
        }

        if ($user->isDosen()) {
            $skripsi = $surat->pengajuanSkripsi;
            if (!$skripsi) {
                return false;
            }

            return $skripsi->isPembimbing($user) ||
                $surat->seminar?->isPenguji($user) ||
                $surat->sidang?->isPenguji($user);
        }

        return false;
    }

    /**
     * Menentukan apakah user dapat menerbitkan surat/SK.
     * Wewenang Admin Prodi se-prodi.
     */
    public function create(User $user, ?int $programStudiId = null): bool
    {
        if ($user->isAdminProdi()) {
            return $programStudiId === null || $user->isSameProdi($programStudiId);
        }

        return false;
    }

    /**
     * Menentukan apakah user dapat memperbarui surat/SK.
     * Wewenang Admin Prodi se-prodi.
     */
    public function update(User $user, Surat $surat): bool
    {
        return $user->isAdminProdi() && $user->isSameProdi($surat->program_studi_id);
    }

    /**
     * Menentukan apakah user dapat menghapus surat/SK.
     * Hanya Admin Utama yang berhak (via before).
     */
    public function delete(User $user, Surat $surat): bool
    {
        return false;
    }
}
