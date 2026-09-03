<?php

namespace App\Http\Controllers\Dosen;

use App\Enums\StatusPengajuan;
use App\Enums\StatusPenugasanDosen;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\AktivitasLog;
use App\Models\Notifikasi;
use App\Models\PenugasanDosen;
use App\Models\PengajuanSkripsi;
use App\Models\SeminarSkripsi;
use App\Models\SidangSkripsi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PenugasanDosenController extends Controller
{
    public function respon(Request $request, PenugasanDosen $penugasan): RedirectResponse
    {
        $user = $request->user();

        // 1. Otorisasi: Hanya dosen yang bersangkutan yang dapat merespon
        if ($penugasan->dosen_id !== $user->id) {
            abort(403, 'Anda tidak berwenang merespon penugasan ini.');
        }

        // 2. Mandat Admin Utama: Dosen tidak dapat menolak
        if ($penugasan->is_mandat_admin_utama) {
            return back()->with('error', 'Penugasan ini merupakan mandat langsung dari Admin Utama dan tidak dapat diubah atau ditolak.');
        }

        // 3. Cek apakah sudah direspon sebelumnya
        if ($penugasan->status !== StatusPenugasanDosen::Menunggu) {
            return back()->with('error', 'Penugasan ini telah direspon sebelumnya.');
        }

        $validated = $request->validate([
            'aksi' => ['required', 'in:terima,tolak'],
            'alasan_penolakan' => ['required_if:aksi,tolak', 'nullable', 'string', 'min:5', 'max:1000'],
            'rekomendasi_dosen_id' => ['nullable', 'exists:users,id', 'different:dosen_id'],
        ], [
            'alasan_penolakan.required_if' => 'Alasan penolakan wajib diisi jika Anda menolak penugasan.',
            'alasan_penolakan.min' => 'Alasan penolakan minimal 5 karakter.',
            'rekomendasi_dosen_id.different' => 'Rekomendasi dosen pengganti tidak boleh memilih diri sendiri.',
        ]);

        $assignable = $penugasan->assignable;
        $mhs = null;
        $prodiId = null;

        if ($assignable instanceof PengajuanSkripsi) {
            $mhs = $assignable->mahasiswa;
            $prodiId = $assignable->program_studi_id;
        } elseif ($assignable instanceof SeminarSkripsi) {
            $mhs = $assignable->pengajuanSkripsi->mahasiswa;
            $prodiId = $assignable->pengajuanSkripsi->program_studi_id;
        } elseif ($assignable instanceof SidangSkripsi) {
            $mhs = $assignable->pengajuanSkripsi->mahasiswa;
            $prodiId = $assignable->pengajuanSkripsi->program_studi_id;
        }

        $mhsName = $mhs ? $mhs->name : 'Mahasiswa';
        $mhsNim = $mhs ? $mhs->nomor_induk : '-';
        $labelPeran = $penugasan->labelPeran();

        if ($validated['aksi'] === 'terima') {
            $penugasan->update([
                'status' => StatusPenugasanDosen::Disetujui,
                'direspon_pada' => now(),
            ]);

            AktivitasLog::catat(
                $user,
                'Persetujuan Penugasan Dosen',
                "Dosen {$user->name} menyetujui penugasan sebagai {$labelPeran} untuk mahasiswa {$mhsName} ({$mhsNim})"
            );

            // Notifikasi ke Kaprodi dan Admin
            Notifikasi::kirimKePengelola(
                $prodiId,
                "Persetujuan {$labelPeran}",
                "Dosen {$user->name} telah MENYETUJUI penugasan sebagai {$labelPeran} untuk mahasiswa {$mhsName} ({$mhsNim}).",
                route('kaprodi.penetapan.index'),
                $user->id,
                [UserRole::AdminUtama, UserRole::Kaprodi, UserRole::AdminProdi]
            );

            return back()->with('success', "Terima kasih! Anda telah menyetujui penugasan sebagai {$labelPeran} untuk mahasiswa {$mhsName}.");
        }

        // AKSI: TOLAK
        $penugasan->update([
            'status' => StatusPenugasanDosen::Ditolak,
            'alasan_penolakan' => $validated['alasan_penolakan'],
            'rekomendasi_dosen_id' => $validated['rekomendasi_dosen_id'] ?? null,
            'direspon_pada' => now(),
        ]);

        // Buka kembali slot pada tabel utama sehingga Kaprodi / Admin Utama dapat menunjuk pengganti
        if ($assignable instanceof PengajuanSkripsi) {
            if ($penugasan->peran === 'pembimbing_1') {
                $assignable->update([
                    'pembimbing_1_id' => null,
                    'status' => StatusPengajuan::Diajukan,
                ]);
            } elseif ($penugasan->peran === 'pembimbing_2') {
                $assignable->update([
                    'pembimbing_2_id' => null,
                ]);
            }
        } elseif ($assignable instanceof SeminarSkripsi) {
            if ($penugasan->peran === 'penguji_seminar') {
                $assignable->update([
                    'penguji_seminar_id' => null,
                    'status' => StatusPengajuan::Diajukan,
                ]);
            }
        } elseif ($assignable instanceof SidangSkripsi) {
            if ($penugasan->peran === 'penguji_1') {
                $assignable->update([
                    'penguji_1_id' => null,
                    'status' => StatusPengajuan::Diajukan,
                ]);
            } elseif ($penugasan->peran === 'penguji_2') {
                $assignable->update([
                    'penguji_2_id' => null,
                    'status' => StatusPengajuan::Diajukan,
                ]);
            }
        }

        AktivitasLog::catat(
            $user,
            'Penolakan Penugasan Dosen',
            "Dosen {$user->name} menolak penugasan sebagai {$labelPeran} untuk mahasiswa {$mhsName} ({$mhsNim}). Alasan: {$validated['alasan_penolakan']}"
        );

        $pesanDasar = "Dosen {$user->name} MENOLAK penugasan sebagai {$labelPeran} untuk mahasiswa {$mhsName} ({$mhsNim}). Alasan: \"{$validated['alasan_penolakan']}\".";
        if (!empty($validated['rekomendasi_dosen_id'])) {
            $rekomendasiUser = \App\Models\User::find($validated['rekomendasi_dosen_id']);
            if ($rekomendasiUser) {
                $pesanDasar .= " (Dosen merekomendasikan: {$rekomendasiUser->name}).";
            }
        }

        // Notifikasi ke Kaprodi dan Admin Utama (termasuk ajakan menetapkan dosen pengganti)
        Notifikasi::kirimKePengelola(
            $prodiId,
            "Pemberitahuan: Penolakan {$labelPeran}",
            $pesanDasar . " Silakan tetapkan dosen pengganti.",
            route('kaprodi.penetapan.index'),
            $user->id,
            [UserRole::AdminUtama, UserRole::Kaprodi]
        );

        // Notifikasi ke Admin Prodi (hanya info penolakan, tanpa pesan "Silakan tetapkan dosen pengganti.")
        Notifikasi::kirimKePengelola(
            $prodiId,
            "Pemberitahuan: Penolakan {$labelPeran}",
            $pesanDasar,
            route('admin.administrasi.index'),
            $user->id,
            [UserRole::AdminProdi]
        );

        // Notifikasi penenang ke Mahasiswa
        if ($mhs) {
            Notifikasi::kirim(
                $mhs->id,
                'Penyesuaian Tim Pembimbing/Penguji',
                "Program Studi sedang melakukan penyesuaian tim pembimbing/penguji untuk pengajuan Anda. Silakan pantau berkala perkembangan melalui portal Anda.",
                null
            );
        }

        return back()->with('success', "Penolakan penugasan telah dicatat dengan alasan. Pemberitahuan telah diteruskan ke Kaprodi dan Admin Utama untuk penunjukan dosen pengganti.");
    }
}
