<?php

namespace App\Policies;

use App\Models\SeminarSkripsi;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SeminarSkripsiPolicy
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
     * Menentukan apakah user berhak melihat daftar seminar skripsi.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Menentukan apakah user berhak melihat detail seminar skripsi.
     * - Mahasiswa: Hanya milik sendiri.
     * - Kaprodi & Admin Prodi: Terbatas pada program studi yang sama.
     * - Dosen: Penguji seminar atau dosen pembimbing mahasiswa terkait.
     */
    public function view(User $user, SeminarSkripsi $seminar): bool
    {
        $skripsi = $seminar->pengajuanSkripsi;
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
            return $seminar->isPenguji($user) || $skripsi->isPembimbing($user);
        }

        return false;
    }

    /**
     * Menentukan apakah user dapat mendaftarkan seminar skripsi.
     * Khusus mahasiswa pemilik yang SK bimbingannya telah terbit.
     */
    public function create(User $user): bool
    {
        return $user->isMahasiswa();
    }

    /**
     * Menentukan apakah user dapat memperbarui pendaftaran seminar.
     * - Mahasiswa: Hanya jika status masih 'diajukan' atau 'ditolak'.
     * - Admin Prodi: Terbatas pada prodi yang sama untuk penjadwalan.
     */
    public function update(User $user, SeminarSkripsi $seminar): bool
    {
        $skripsi = $seminar->pengajuanSkripsi;
        if (!$skripsi) {
            return false;
        }

        if ($user->isMahasiswa()) {
            return $user->id === $skripsi->mahasiswa_id && ($seminar->isDiajukan() || $seminar->isDitolak());
        }

        if ($user->isAdminProdi()) {
            return $user->isSameProdi($skripsi->program_studi_id);
        }

        return false;
    }

    /**
     * Menentukan apakah user dapat menetapkan dosen penguji seminar.
     * Wewenang eksklusif Kaprodi se-prodi.
     */
    public function assignPenguji(User $user, SeminarSkripsi $seminar): bool
    {
        $skripsi = $seminar->pengajuanSkripsi;
        return $skripsi && $user->isKaprodi() && $user->isSameProdi($skripsi->program_studi_id);
    }

    /**
     * Menentukan apakah user dapat mengatur jadwal & menerbitkan undangan/SK seminar.
     * Wewenang Admin Prodi se-prodi.
     */
    public function schedule(User $user, SeminarSkripsi $seminar): bool
    {
        $skripsi = $seminar->pengajuanSkripsi;
        return $skripsi && $user->isAdminProdi() && $user->isSameProdi($skripsi->program_studi_id);
    }

    /**
     * Menentukan apakah user dapat menginput / menyelesaikan nilai seminar.
     * Wewenang Admin Prodi se-prodi (hanya Admin Utama yang berhak override setelah terkunci).
     */
    public function finalizeScore(User $user, SeminarSkripsi $seminar): bool
    {
        $skripsi = $seminar->pengajuanSkripsi;
        if (!$skripsi || !$user->isAdminProdi() || !$user->isSameProdi($skripsi->program_studi_id)) {
            return false;
        }

        // Jika nilai sudah ada, hanya Admin Utama yang boleh edit (ditangani oleh before)
        return $seminar->nilai_seminar === null;
    }

    /**
     * Menentukan apakah user dapat menghapus pendaftaran seminar.
     */
    public function delete(User $user, SeminarSkripsi $seminar): bool
    {
        $skripsi = $seminar->pengajuanSkripsi;
        if ($skripsi && $user->isMahasiswa()) {
            return $user->id === $skripsi->mahasiswa_id && ($seminar->isDiajukan() || $seminar->isDitolak());
        }

        return false;
    }
}
