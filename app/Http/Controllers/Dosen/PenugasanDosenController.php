<?php

namespace App\Http\Controllers\Dosen;

use App\Enums\StatusPengajuan;
use App\Enums\StatusPenugasanDosen;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\AktivitasLog;
use App\Models\Notifikasi;
use App\Enums\StatusPraktikLapang;
use App\Models\PengajuanSkripsi;
use App\Models\PenugasanDosen;
use App\Models\PraktikLapang;
use App\Models\SeminarSkripsi;
use App\Models\SidangSkripsi;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PenugasanDosenController extends Controller
{
    public function respon(Request $request, PenugasanDosen $penugasan): RedirectResponse
    {
        $user = $request->user();

        // 1. Otorisasi: Hanya dosen yang bersangkutan yang dapat merespon
        if ((int) $penugasan->dosen_id !== (int) $user->id) {
            abort(403, 'Anda tidak berwenang merespon penugasan ini.');
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

        $result = DB::transaction(function () use ($user, $penugasan, $validated): array {
            $lockedPenugasan = PenugasanDosen::whereKey($penugasan->id)->lockForUpdate()->firstOrFail();

            if ((int) $lockedPenugasan->dosen_id !== (int) $user->id) {
                abort(403, 'Anda tidak berwenang merespon penugasan ini.');
            }

            // 2. Mandat Admin Utama: Dosen tidak dapat menolak
            if ($lockedPenugasan->is_mandat_admin_utama) {
                return ['error' => 'Penugasan ini merupakan mandat langsung dari Admin Utama dan tidak dapat diubah atau ditolak.'];
            }

            // 3. Cek apakah sudah direspon sebelumnya
            if ($lockedPenugasan->status !== StatusPenugasanDosen::Menunggu) {
                return ['error' => 'Penugasan ini telah direspon sebelumnya.'];
            }

            $assignable = $lockedPenugasan->assignable;
            $mhs = null;
            $prodiId = null;

            if ($assignable instanceof PengajuanSkripsi) {
                $assignable = PengajuanSkripsi::with('mahasiswa')->whereKey($assignable->id)->lockForUpdate()->first();
                $mhs = $assignable?->mahasiswa;
                $prodiId = $assignable?->program_studi_id;
            } elseif ($assignable instanceof SeminarSkripsi) {
                $assignable = SeminarSkripsi::with('pengajuanSkripsi.mahasiswa')->whereKey($assignable->id)->lockForUpdate()->first();
                $mhs = $assignable?->pengajuanSkripsi?->mahasiswa;
                $prodiId = $assignable?->pengajuanSkripsi?->program_studi_id;
            } elseif ($assignable instanceof SidangSkripsi) {
                $assignable = SidangSkripsi::with('pengajuanSkripsi.mahasiswa')->whereKey($assignable->id)->lockForUpdate()->first();
                $mhs = $assignable?->pengajuanSkripsi?->mahasiswa;
                $prodiId = $assignable?->pengajuanSkripsi?->program_studi_id;
            } elseif ($assignable instanceof PraktikLapang) {
                $assignable = PraktikLapang::with('mahasiswa')->whereKey($assignable->id)->lockForUpdate()->first();
                $mhs = $assignable?->mahasiswa;
                $prodiId = $assignable?->program_studi_id;
            }

            $mhsName = $mhs ? $mhs->name : 'Mahasiswa';
            $mhsNim = $mhs ? $mhs->nomor_induk : '-';
            $labelPeran = $lockedPenugasan->labelPeran();

            if ($validated['aksi'] === 'terima') {
                $lockedPenugasan->update([
                    'status' => StatusPenugasanDosen::Disetujui,
                    'direspon_pada' => now(),
                ]);

                if ($assignable instanceof PraktikLapang && $lockedPenugasan->peran === 'dpl' && $assignable->status === StatusPraktikLapang::MenungguDpl) {
                    $assignable->update(['status' => StatusPraktikLapang::Berjalan]);
                }

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

                return ['success' => "Terima kasih! Anda telah menyetujui penugasan sebagai {$labelPeran} untuk mahasiswa {$mhsName}."];
            }

            // AKSI: TOLAK
            $lockedPenugasan->update([
                'status' => StatusPenugasanDosen::Ditolak,
                'alasan_penolakan' => $validated['alasan_penolakan'],
                'rekomendasi_dosen_id' => $validated['rekomendasi_dosen_id'] ?? null,
                'direspon_pada' => now(),
            ]);

            // Buka kembali slot pada tabel utama sehingga Kaprodi / Admin Utama dapat menunjuk pengganti
            if ($assignable instanceof PengajuanSkripsi) {
                if ($lockedPenugasan->peran === 'pembimbing_1') {
                    $assignable->update([
                        'pembimbing_1_id' => null,
                        'status' => StatusPengajuan::Diajukan,
                    ]);
                } elseif ($lockedPenugasan->peran === 'pembimbing_2') {
                    $assignable->update([
                        'pembimbing_2_id' => null,
                    ]);
                }
            } elseif ($assignable instanceof SeminarSkripsi) {
                if ($lockedPenugasan->peran === 'penguji_seminar') {
                    $assignable->update([
                        'penguji_seminar_id' => null,
                        'status' => StatusPengajuan::Diajukan,
                    ]);
                }
            } elseif ($assignable instanceof SidangSkripsi) {
                if ($lockedPenugasan->peran === 'penguji_1') {
                    $assignable->update([
                        'penguji_1_id' => null,
                        'status' => StatusPengajuan::Diajukan,
                    ]);
                } elseif ($lockedPenugasan->peran === 'penguji_2') {
                    $assignable->update([
                        'penguji_2_id' => null,
                        'status' => StatusPengajuan::Diajukan,
                    ]);
                }
            } elseif ($assignable instanceof PraktikLapang) {
                if ($lockedPenugasan->peran === 'dpl') {
                    $assignable->update([
                        'dpl_id' => null,
                        'status' => StatusPraktikLapang::MenungguDpl,
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
                $rekomendasiUser = User::find($validated['rekomendasi_dosen_id']);
                if ($rekomendasiUser) {
                    $pesanDasar .= " (Dosen merekomendasikan: {$rekomendasiUser->name}).";
                }
            }

            // Notifikasi ke Kaprodi dan Admin Utama (termasuk ajakan menetapkan dosen pengganti)
            Notifikasi::kirimKePengelola(
                $prodiId,
                "Pemberitahuan: Penolakan {$labelPeran}",
                $pesanDasar . ' Silakan tetapkan dosen pengganti.',
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
                    'Program Studi sedang melakukan penyesuaian tim pembimbing/penguji untuk pengajuan Anda. Silakan pantau berkala perkembangan melalui portal Anda.',
                    null
                );
            }

            return ['success' => 'Penolakan penugasan telah dicatat dengan alasan. Pemberitahuan telah diteruskan ke Kaprodi dan Admin Utama untuk penunjukan dosen pengganti.'];
        });

        if (isset($result['error'])) {
            return back()->with('error', $result['error']);
        }

        return back()->with('success', $result['success']);
    }
}
