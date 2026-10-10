<?php

namespace App\Policies;

use App\Models\SidangSkripsi;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SidangSkripsiPolicy
{
    use HandlesAuthorization;

    /**
     * Bypassing role Admin Utama secara global pada seluruh aksi.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->isAdminUtama()) {
            return true;
        }

        return null;
    }

    /**
     * Menentukan apakah user berhak melihat daftar sidang skripsi.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Menentukan apakah user berhak melihat detail sidang skripsi.
     * - Mahasiswa: Hanya milik sendiri.
     * - Kaprodi & Admin Prodi: Terbatas pada program studi yang sama.
     * - Dosen: Dewan penguji sidang atau dosen pembimbing mahasiswa terkait.
     */
    public function view(User $user, SidangSkripsi $sidang): bool
    {
        $skripsi = $sidang->pengajuanSkripsi;
        if (!$skripsi) {
            return false;
        }

        if ($user->isMahasiswa()) {
            return $user->id === $skripsi->mahasiswa_id;
        }

        if ($user->isKaprodi() || $user->isAdminProdi()) {
            return $user->isSameProdi($skripsi->program_studi_id);
        }

        if ($user->isDosen()) {
            return $sidang->isPenguji($user) || $skripsi->isPembimbing($user);
        }

        return false;
    }

    /**
     * Menentukan apakah user dapat mendaftarkan sidang skripsi.
     * Khusus mahasiswa yang telah lulus seminar skripsi.
     */
    public function create(User $user): bool
    {
        return $user->isMahasiswa();
    }

    /**
     * Menentukan apakah user dapat memperbarui berkas/pendaftaran sidang skripsi.
     * - Mahasiswa: Hanya jika status masih 'diajukan' atau 'ditolak'.
     * - Admin Prodi: Terbatas pada prodi yang sama untuk penjadwalan.
     */
    public function update(User $user, SidangSkripsi $sidang): bool
    {
        $skripsi = $sidang->pengajuanSkripsi;
        if (!$skripsi) {
            return false;
        }

        if ($user->isMahasiswa()) {
            return $user->id === $skripsi->mahasiswa_id && ($sidang->isDiajukan() || $sidang->isDitolak());
        }

        if ($user->isAdminProdi()) {
            return $user->isSameProdi($skripsi->program_studi_id);
        }

        return false;
    }

    /**
     * Menentukan apakah user dapat menetapkan 2 orang dosen penguji sidang.
     * Wewenang eksklusif Kaprodi se-prodi.
     */
    public function assignPenguji(User $user, SidangSkripsi $sidang): bool
    {
        $skripsi = $sidang->pengajuanSkripsi;
        return $skripsi && $user->isKaprodi() && $user->isSameProdi($skripsi->program_studi_id);
    }

    /**
     * Menentukan apakah user dapat mengatur jadwal & menerbitkan undangan/SK sidang.
     * Wewenang Admin Prodi se-prodi.
     */
    public function schedule(User $user, SidangSkripsi $sidang): bool
    {
        $skripsi = $sidang->pengajuanSkripsi;
        return $skripsi && $user->isAdminProdi() && $user->isSameProdi($skripsi->program_studi_id);
    }

    /**
     * Menentukan apakah user dapat menginput / menyelesaikan nilai akhir sidang skripsi.
     * Wewenang Admin Prodi se-prodi (hanya Admin Utama yang berhak override setelah terkunci).
     */
    public function finalizeScore(User $user, SidangSkripsi $sidang): bool
    {
        $skripsi = $sidang->pengajuanSkripsi;
        if (!$skripsi || !$user->isAdminProdi() || !$user->isSameProdi($skripsi->program_studi_id)) {
            return false;
        }

        // Jika nilai sudah ada, hanya Admin Utama yang boleh edit (ditangani oleh before)
        return $sidang->nilai_sidang === null;
    }

    /**
     * Menentukan apakah user dapat menghapus pendaftaran sidang skripsi.
     */
    public function delete(User $user, SidangSkripsi $sidang): bool
    {
        $skripsi = $sidang->pengajuanSkripsi;
        if ($skripsi && $user->isMahasiswa()) {
            return $user->id === $skripsi->mahasiswa_id && ($sidang->isDiajukan() || $sidang->isDitolak());
        }

        return false;
    }
}
