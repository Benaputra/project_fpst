<?php

namespace App\Http\Controllers\Kaprodi;

use App\Enums\StatusPengajuan;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\AktivitasLog;
use App\Models\Notifikasi;
use App\Models\PengajuanSkripsi;
use App\Models\SeminarSkripsi;
use App\Models\SidangSkripsi;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PenetapanController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $prodiId = $user->program_studi_id;

        $daftarJudul = PengajuanSkripsi::where('program_studi_id', $prodiId)
            ->with(['mahasiswa', 'pembimbing1', 'pembimbing2'])
            ->latest()
            ->paginate(10, ['*'], 'page_judul');

        $daftarSeminar = SeminarSkripsi::whereHas('pengajuanSkripsi', fn($q) => $q->where('program_studi_id', $prodiId))
            ->with(['pengajuanSkripsi.mahasiswa', 'penguji'])
            ->latest()
            ->paginate(10, ['*'], 'page_seminar');

        $daftarSidang = SidangSkripsi::whereHas('pengajuanSkripsi', fn($q) => $q->where('program_studi_id', $prodiId))
            ->with(['pengajuanSkripsi.mahasiswa', 'penguji1', 'penguji2'])
            ->latest()
            ->paginate(10, ['*'], 'page_sidang');

        $daftarDosen = User::whereIn('role', [UserRole::Dosen, UserRole::Kaprodi])
            ->where('program_studi_id', $prodiId)
            ->orderBy('name')
            ->get();

        return view('kaprodi.penetapan.index', compact('daftarJudul', 'daftarSeminar', 'daftarSidang', 'daftarDosen', 'user'));
    }

    public function updateJudul(Request $request, PengajuanSkripsi $skripsi): RedirectResponse
    {
        $user = $request->user();
        $action = $request->input('action'); // 'terima' or 'tolak'

        if ($action === 'tolak') {
            $request->validate([
                'catatan' => ['required', 'string', 'max:1000'],
            ], [
                'catatan.required' => 'Catatan penolakan / alasan wajib diisi.',
            ]);

            $skripsi->update([
                'status' => StatusPengajuan::Ditolak,
                'catatan' => $request->input('catatan'),
            ]);

            AktivitasLog::catat(
                $user,
                'Penolakan Judul Skripsi',
                "Kaprodi {$user->name} menolak judul mahasiswa {$skripsi->mahasiswa->name} ({$skripsi->mahasiswa->nomor_induk}): '{$skripsi->judul}'. Catatan: {$request->input('catatan')}"
            );

            Notifikasi::kirim(
                $skripsi->mahasiswa_id,
                'Pengajuan Judul Ditolak / Perlu Revisi',
                "Pengajuan judul Anda '{$skripsi->judul}' ditolak oleh Kaprodi dengan catatan: {$request->input('catatan')}",
                route('mahasiswa.skripsi.index')
            );

            return back()->with('success', 'Pengajuan judul berhasil ditolak dengan catatan.');
        }

        $validated = $request->validate([
            'pembimbing_1_id' => ['required', 'exists:users,id'],
            'pembimbing_2_id' => ['nullable', 'exists:users,id', 'different:pembimbing_1_id'],
            'catatan' => ['nullable', 'string', 'max:1000'],
        ], [
            'pembimbing_1_id.required' => 'Pembimbing Utama (1) wajib dipilih.',
            'pembimbing_2_id.different' => 'Pembimbing 2 tidak boleh sama dengan Pembimbing 1.',
        ]);

        $skripsi->update([
            'pembimbing_1_id' => $validated['pembimbing_1_id'],
            'pembimbing_2_id' => $validated['pembimbing_2_id'] ?? null,
            'status' => StatusPengajuan::Diproses, // Menunggu SK Bimbingan dari Admin
            'catatan' => $validated['catatan'] ?? null,
        ]);

        AktivitasLog::catat(
            $user,
            'Penetapan Dosen Pembimbing',
            "Kaprodi {$user->name} menyetujui judul & menetapkan Dosen Pembimbing untuk mahasiswa {$skripsi->mahasiswa->name} ({$skripsi->mahasiswa->nomor_induk})"
        );

        // Notifikasi ke Mahasiswa
        Notifikasi::kirim(
            $skripsi->mahasiswa_id,
            'Judul Disetujui & Pembimbing Ditetapkan',
            "Pengajuan judul Anda '{$skripsi->judul}' telah disetujui. Dosen Pembimbing telah ditetapkan dan menunggu penerbitan SK Bimbingan.",
            route('mahasiswa.skripsi.index')
        );

        // Notifikasi ke Pembimbing 1
        Notifikasi::kirim(
            $validated['pembimbing_1_id'],
            'Penugasan Pembimbing Utama (1)',
            "Anda ditetapkan sebagai Pembimbing Utama untuk mahasiswa {$skripsi->mahasiswa->name} ({$skripsi->mahasiswa->nomor_induk}) dengan judul '{$skripsi->judul}'.",
            route('dosen.bimbingan.index')
        );

        // Notifikasi ke Pembimbing 2 (jika ada)
        if (!empty($validated['pembimbing_2_id'])) {
            Notifikasi::kirim(
                $validated['pembimbing_2_id'],
                'Penugasan Pembimbing Pendamping (2)',
                "Anda ditetapkan sebagai Pembimbing Pendamping untuk mahasiswa {$skripsi->mahasiswa->name} ({$skripsi->mahasiswa->nomor_induk}) dengan judul '{$skripsi->judul}'.",
                route('dosen.bimbingan.index')
            );
        }

        return back()->with('success', 'Judul disetujui & Dosen Pembimbing berhasil ditetapkan. Status berlanjut ke penerbitan SK oleh Admin.');
    }

    public function assignPengujiSeminar(Request $request, SeminarSkripsi $seminar): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'penguji_seminar_id' => ['required', 'exists:users,id'],
        ], [
            'penguji_seminar_id.required' => 'Dosen Penguji Seminar wajib dipilih.',
        ]);

        $seminar->update([
            'penguji_seminar_id' => $validated['penguji_seminar_id'],
            'status' => StatusPengajuan::Diproses,
        ]);

        $mhs = $seminar->pengajuanSkripsi->mahasiswa;

        AktivitasLog::catat(
            $user,
            'Penetapan Penguji Seminar',
            "Kaprodi {$user->name} menetapkan Dosen Penguji Seminar untuk mahasiswa {$mhs->name} ({$mhs->nomor_induk})"
        );

        // Notifikasi ke Mahasiswa
        Notifikasi::kirim(
            $mhs->id,
            'Dosen Penguji Seminar Ditetapkan',
            "Dosen Penguji Seminar Anda telah ditetapkan oleh Kaprodi. Menunggu pengaturan jadwal pelaksanaan dari Admin.",
            route('mahasiswa.seminar.index')
        );

        // Notifikasi ke Dosen Penguji
        Notifikasi::kirim(
            $validated['penguji_seminar_id'],
            'Penugasan Dosen Penguji Seminar',
            "Anda ditugaskan sebagai Penguji Seminar Skripsi untuk mahasiswa {$mhs->name} ({$mhs->nomor_induk}) dengan judul '{$seminar->pengajuanSkripsi->judul}'.",
            route('dosen.bimbingan.index')
        );

        return back()->with('success', 'Dosen Penguji Seminar berhasil ditetapkan.');
    }

    public function assignPengujiSidang(Request $request, SidangSkripsi $sidang): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'penguji_1_id' => ['required', 'exists:users,id'],
            'penguji_2_id' => ['required', 'exists:users,id', 'different:penguji_1_id'],
        ], [
            'penguji_1_id.required' => 'Dosen Penguji 1 wajib dipilih.',
            'penguji_2_id.required' => 'Dosen Penguji 2 wajib dipilih.',
            'penguji_2_id.different' => 'Penguji 2 tidak boleh sama dengan Penguji 1.',
        ]);

        $sidang->update([
            'penguji_1_id' => $validated['penguji_1_id'],
            'penguji_2_id' => $validated['penguji_2_id'],
            'status' => StatusPengajuan::Diproses,
        ]);

        $mhs = $sidang->pengajuanSkripsi->mahasiswa;

        AktivitasLog::catat(
            $user,
            'Penetapan 2 Penguji Sidang',
            "Kaprodi {$user->name} menetapkan 2 Penguji Sidang Meja Hijau untuk mahasiswa {$mhs->name} ({$mhs->nomor_induk})"
        );

        // Notifikasi ke Mahasiswa
        Notifikasi::kirim(
            $mhs->id,
            '2 Dosen Penguji Sidang Ditetapkan',
            "2 Orang Dosen Penguji Sidang Skripsi telah ditetapkan oleh Kaprodi. Menunggu pengaturan jadwal pelaksanaan dari Admin.",
            route('mahasiswa.sidang.index')
        );

        // Notifikasi ke Penguji 1 & 2
        Notifikasi::kirim(
            $validated['penguji_1_id'],
            'Penugasan Penguji 1 Sidang Skripsi',
            "Anda ditugaskan sebagai Penguji 1 Sidang Skripsi untuk mahasiswa {$mhs->name} ({$mhs->nomor_induk}).",
            route('dosen.bimbingan.index')
        );

        Notifikasi::kirim(
            $validated['penguji_2_id'],
            'Penugasan Penguji 2 Sidang Skripsi',
            "Anda ditugaskan sebagai Penguji 2 Sidang Skripsi untuk mahasiswa {$mhs->name} ({$mhs->nomor_induk}).",
            route('dosen.bimbingan.index')
        );

        return back()->with('success', '2 Orang Dosen Penguji Sidang Skripsi berhasil ditetapkan.');
    }
}
