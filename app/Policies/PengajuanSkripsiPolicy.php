<?php

namespace App\Policies;

use App\Enums\StatusPengajuan;
use App\Models\PengajuanSkripsi;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PengajuanSkripsiPolicy
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
     * Menentukan apakah user berhak melihat daftar pengajuan skripsi.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Menentukan apakah user berhak melihat detail pengajuan skripsi.
     * - Mahasiswa: Hanya milik sendiri.
     * - Kaprodi & Admin Prodi: Terbatas pada program studi yang sama.
     * - Dosen: Terbatas pada mahasiswa bimbingan terkait.
     */
    public function view(User $user, PengajuanSkripsi $skripsi): bool
    {
        if ($user->isMahasiswa()) {
            return $user->id === $skripsi->mahasiswa_id;
        }

        if ($user->isKaprodi() || $user->isAdminProdi()) {
            return $user->isSameProdi($skripsi->program_studi_id);
        }

        if ($user->isDosen()) {
            return $skripsi->isPembimbing($user);
        }

        return false;
    }

    /**
     * Menentukan apakah user dapat membuat pengajuan skripsi baru.
     * Khusus mahasiswa yang terdaftar pada program studi resmi FPST.
     */
    public function create(User $user): bool
    {
        return $user->isMahasiswa() && !empty($user->program_studi_id);
    }

    /**
     * Menentukan apakah user dapat memperbarui pengajuan skripsi.
     * - Mahasiswa: Hanya jika status masih 'diajukan' atau 'ditolak' (sebelum disetujui / diproses).
     * - Kaprodi: Untuk mereview judul & catatan pada prodi yang sama.
     * - Admin Prodi: Untuk administrasi pengajuan pada prodi yang sama.
     */
    public function update(User $user, PengajuanSkripsi $skripsi): bool
    {
        if ($user->isMahasiswa()) {
            return $user->id === $skripsi->mahasiswa_id && ($skripsi->isDiajukan() || $skripsi->isDitolak());
        }

        if ($user->isKaprodi() || $user->isAdminProdi()) {
            return $user->isSameProdi($skripsi->program_studi_id);
        }

        return false;
    }

    /**
     * Menentukan apakah user dapat menyetujui pengajuan judul.
     * Wewenang eksklusif Kaprodi pada program studi yang bersangkutan.
     */
    public function approve(User $user, PengajuanSkripsi $skripsi): bool
    {
        return $user->isKaprodi() && $user->isSameProdi($skripsi->program_studi_id);
    }

    /**
     * Menentukan apakah user dapat menolak pengajuan judul.
     * Wewenang eksklusif Kaprodi pada program studi yang bersangkutan.
     */
    public function reject(User $user, PengajuanSkripsi $skripsi): bool
    {
        return $user->isKaprodi() && $user->isSameProdi($skripsi->program_studi_id);
    }

    /**
     * Menentukan apakah user dapat menetapkan / mengubah dosen pembimbing.
     * Wewenang eksklusif Kaprodi se-prodi.
     */
    public function assignPembimbing(User $user, PengajuanSkripsi $skripsi): bool
    {
        return $user->isKaprodi() && $user->isSameProdi($skripsi->program_studi_id);
    }

    /**
     * Menentukan apakah user dapat menerbitkan SK Pembimbing Skripsi.
     * Wewenang Admin Prodi se-prodi (memerlukan konfirmasi pembimbing).
     */
    public function issueSk(User $user, PengajuanSkripsi $skripsi): bool
    {
        return $user->isAdminProdi() && $user->isSameProdi($skripsi->program_studi_id);
    }

    /**
     * Menentukan apakah user dapat menghapus pengajuan skripsi.
     * Mahasiswa hanya boleh membatalkan jika belum disetujui / diproses.
     */
    public function delete(User $user, PengajuanSkripsi $skripsi): bool
    {
        if ($user->isMahasiswa()) {
            return $user->id === $skripsi->mahasiswa_id && ($skripsi->isDiajukan() || $skripsi->isDitolak());
        }

        return false;
    }
}
