<?php

namespace App\Http\Controllers\Kaprodi;

use App\Enums\StatusPengajuan;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\AktivitasLog;
use App\Models\Notifikasi;
use App\Models\PengajuanSkripsi;
use App\Models\ProgramStudi;
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
        if (!$user->isKaprodi() && !$user->isAdminUtama()) {
            abort(403, 'Akses khusus Kaprodi dan Admin Utama.');
        }

        $daftarProdi = ProgramStudi::all();
        $prodiFilter = $request->input('prodi_id', $user->isAdminUtama() ? ($daftarProdi->first()?->id) : $user->program_studi_id);
        $prodiId = $prodiFilter;

        // Filter & Search untuk Tab 1: Review Judul & Pembimbing
        $qJudul = PengajuanSkripsi::where('program_studi_id', $prodiId)
            ->with(['mahasiswa', 'pembimbing1', 'pembimbing2']);
        if ($searchJudul = $request->input('search_judul')) {
            $qJudul->where(function ($q) use ($searchJudul) {
                $q->where('judul', 'like', "%{$searchJudul}%")
                    ->orWhereHas('mahasiswa', fn($m) => $m->where('name', 'like', "%{$searchJudul}%")->orWhere('nomor_induk', 'like', "%{$searchJudul}%"));
            });
        }
        if ($statusJudul = $request->input('status_judul')) {
            if ($statusJudul === 'menunggu') {
                $qJudul->where('status', StatusPengajuan::Diajukan);
            } elseif ($statusJudul === 'selesai') {
                $qJudul->where('status', '!=', StatusPengajuan::Diajukan);
            }
        }
        if ($request->input('sort_judul', 'fifo') === 'fifo') {
            $qJudul->oldest();
        } else {
            $qJudul->latest();
        }
        $daftarJudul = $qJudul->paginate(10, ['*'], 'page_judul')->withQueryString();

        // Filter & Search untuk Tab 2: Penguji Seminar
        $qSeminar = SeminarSkripsi::whereHas('pengajuanSkripsi', fn($q) => $q->where('program_studi_id', $prodiId))
            ->with(['pengajuanSkripsi.mahasiswa', 'penguji']);
        if ($searchSeminar = $request->input('search_seminar')) {
            $qSeminar->where(function ($q) use ($searchSeminar) {
                $q->whereHas('pengajuanSkripsi', fn($ps) => 
                    $ps->where('judul', 'like', "%{$searchSeminar}%")
                        ->orWhereHas('mahasiswa', fn($m) => $m->where('name', 'like', "%{$searchSeminar}%")->orWhere('nomor_induk', 'like', "%{$searchSeminar}%"))
                );
            });
        }
        if ($statusSeminar = $request->input('status_seminar')) {
            if ($statusSeminar === 'menunggu') {
                $qSeminar->whereNull('penguji_seminar_id');
            } elseif ($statusSeminar === 'selesai') {
                $qSeminar->whereNotNull('penguji_seminar_id');
            }
        }
        if ($request->input('sort_seminar', 'fifo') === 'fifo') {
            $qSeminar->oldest();
        } else {
            $qSeminar->latest();
        }
        $daftarSeminar = $qSeminar->paginate(10, ['*'], 'page_seminar')->withQueryString();

        // Filter & Search untuk Tab 3: Penguji Sidang
        $qSidang = SidangSkripsi::whereHas('pengajuanSkripsi', fn($q) => $q->where('program_studi_id', $prodiId))
            ->with(['pengajuanSkripsi.mahasiswa', 'penguji1', 'penguji2']);
        if ($searchSidang = $request->input('search_sidang')) {
            $qSidang->where(function ($q) use ($searchSidang) {
                $q->whereHas('pengajuanSkripsi', fn($ps) => 
                    $ps->where('judul', 'like', "%{$searchSidang}%")
                        ->orWhereHas('mahasiswa', fn($m) => $m->where('name', 'like', "%{$searchSidang}%")->orWhere('nomor_induk', 'like', "%{$searchSidang}%"))
                );
            });
        }
        if ($statusSidang = $request->input('status_sidang')) {
            if ($statusSidang === 'menunggu') {
                $qSidang->where(fn($q) => $q->whereNull('penguji_1_id')->orWhereNull('penguji_2_id'));
            } elseif ($statusSidang === 'selesai') {
                $qSidang->whereNotNull('penguji_1_id')->whereNotNull('penguji_2_id');
            }
        }
        if ($request->input('sort_sidang', 'fifo') === 'fifo') {
            $qSidang->oldest();
        } else {
            $qSidang->latest();
        }
        $daftarSidang = $qSidang->paginate(10, ['*'], 'page_sidang')->withQueryString();

        $daftarDosen = User::whereIn('role', [UserRole::Dosen, UserRole::Kaprodi])
            ->where('program_studi_id', $prodiId)
            ->orderBy('name')
            ->get();

        $pendingJudulCount = PengajuanSkripsi::where('program_studi_id', $prodiId)->where('status', StatusPengajuan::Diajukan)->count();
        $pendingSeminarCount = SeminarSkripsi::whereHas('pengajuanSkripsi', fn($q) => $q->where('program_studi_id', $prodiId))->whereNull('penguji_seminar_id')->count();
        $pendingSidangCount = SidangSkripsi::whereHas('pengajuanSkripsi', fn($q) => $q->where('program_studi_id', $prodiId))->where(fn($q) => $q->whereNull('penguji_1_id')->orWhereNull('penguji_2_id'))->count();

        return view('kaprodi.penetapan.index', compact(
            'daftarJudul', 'daftarSeminar', 'daftarSidang', 
            'daftarDosen', 'daftarProdi', 'prodiFilter', 'user',
            'pendingJudulCount', 'pendingSeminarCount', 'pendingSidangCount'
        ));
    }

    public function updateJudul(Request $request, PengajuanSkripsi $skripsi): RedirectResponse
    {
        $user = $request->user();
        if (!$user->isKaprodi() && !$user->isAdminUtama()) {
            abort(403, 'Akses khusus Kaprodi dan Admin Utama.');
        }

        // Aturan 5: Selain admin utama, role lain tidak boleh merubah dosen pembimbing jika sudah ditentukan oleh kaprodi
        $isUpdatePembimbing = $skripsi->pembimbing_1_id !== null;
        if ($isUpdatePembimbing && !$user->isAdminUtama()) {
            return back()->with('error', 'Dosen pembimbing telah ditentukan oleh Kaprodi dan terkunci. Hanya Admin Utama yang berwenang mengubahnya.');
        }

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

            $actorRole = $user->isAdminUtama() ? 'Admin Utama' : 'Kaprodi';
            AktivitasLog::catat(
                $user,
                'Penolakan Judul Skripsi',
                "{$actorRole} {$user->name} menolak judul mahasiswa {$skripsi->mahasiswa->name} ({$skripsi->mahasiswa->nomor_induk}): '{$skripsi->judul}'. Catatan: {$request->input('catatan')}"
            );

            Notifikasi::kirim(
                $skripsi->mahasiswa_id,
                'Pengajuan Judul Ditolak / Perlu Revisi',
                "Pengajuan judul Anda '{$skripsi->judul}' ditolak oleh {$actorRole} dengan catatan: {$request->input('catatan')}",
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

        $actorRole = $user->isAdminUtama() ? 'Admin Utama' : 'Kaprodi';
        $logAction = $isUpdatePembimbing ? 'Pembaruan Dosen Pembimbing' : 'Penetapan Dosen Pembimbing';
        AktivitasLog::catat(
            $user,
            $logAction,
            "{$actorRole} {$user->name} " . ($isUpdatePembimbing ? "memperbarui penetapan" : "menyetujui judul & menetapkan") . " Dosen Pembimbing untuk mahasiswa {$skripsi->mahasiswa->name} ({$skripsi->mahasiswa->nomor_induk})"
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

        $msg = $isUpdatePembimbing
            ? 'Dosen Pembimbing berhasil diperbarui oleh Admin Utama.'
            : 'Judul disetujui & Dosen Pembimbing berhasil ditetapkan. Status berlanjut ke penerbitan SK oleh Admin.';

        return back()->with('success', $msg);
    }

    public function assignPengujiSeminar(Request $request, SeminarSkripsi $seminar): RedirectResponse
    {
        $user = $request->user();
        if (!$user->isKaprodi() && !$user->isAdminUtama()) {
            abort(403, 'Akses khusus Kaprodi dan Admin Utama.');
        }

        // Aturan 5: Selain admin utama, role lain tidak boleh merubah dosen penguji jika sudah ditentukan oleh kaprodi
        $isUpdatePenguji = $seminar->penguji_seminar_id !== null;
        if ($isUpdatePenguji && !$user->isAdminUtama()) {
            return back()->with('error', 'Dosen penguji seminar telah ditentukan oleh Kaprodi dan terkunci. Hanya Admin Utama yang berwenang mengubahnya.');
        }

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
        $actorRole = $user->isAdminUtama() ? 'Admin Utama' : 'Kaprodi';
        $logAction = $isUpdatePenguji ? 'Pembaruan Penguji Seminar' : 'Penetapan Penguji Seminar';

        AktivitasLog::catat(
            $user,
            $logAction,
            "{$actorRole} {$user->name} " . ($isUpdatePenguji ? "memperbarui" : "menetapkan") . " Dosen Penguji Seminar untuk mahasiswa {$mhs->name} ({$mhs->nomor_induk})"
        );

        // Notifikasi ke Mahasiswa
        Notifikasi::kirim(
            $mhs->id,
            'Dosen Penguji Seminar Ditetapkan',
            "Dosen Penguji Seminar Anda telah ditetapkan. Menunggu pengaturan jadwal pelaksanaan dari Admin.",
            route('mahasiswa.seminar.index')
        );

        // Notifikasi ke Dosen Penguji
        Notifikasi::kirim(
            $validated['penguji_seminar_id'],
            'Penugasan Dosen Penguji Seminar',
            "Anda ditugaskan sebagai Penguji Seminar Skripsi untuk mahasiswa {$mhs->name} ({$mhs->nomor_induk}) dengan judul '{$seminar->pengajuanSkripsi->judul}'.",
            route('dosen.bimbingan.index')
        );

        // Notifikasi ke Admin (Prodi & Utama) untuk persiapan jadwal
        Notifikasi::kirimKePengelola(
            $seminar->pengajuanSkripsi->program_studi_id,
            'Dosen Penguji Seminar Telah Ditetapkan',
            "Dosen Penguji Seminar untuk mahasiswa {$mhs->name} ({$mhs->nomor_induk}) telah ditetapkan. Silakan atur jadwal dan terbitkan dokumen seminar.",
            null,
            $user->id,
            [UserRole::AdminUtama, UserRole::AdminProdi]
        );

        $msg = $isUpdatePenguji
            ? 'Dosen Penguji Seminar berhasil diperbarui.'
            : 'Dosen Penguji Seminar berhasil ditetapkan.';

        return back()->with('success', $msg);
    }

    public function assignPengujiSidang(Request $request, SidangSkripsi $sidang): RedirectResponse
    {
        $user = $request->user();
        if (!$user->isKaprodi() && !$user->isAdminUtama()) {
            abort(403, 'Akses khusus Kaprodi dan Admin Utama.');
        }

        // Aturan 5: Selain admin utama, role lain tidak boleh merubah dewan penguji jika sudah ditentukan oleh kaprodi
        $isUpdatePenguji = ($sidang->penguji_1_id !== null || $sidang->penguji_2_id !== null);
        if ($isUpdatePenguji && !$user->isAdminUtama()) {
            return back()->with('error', 'Dewan penguji sidang telah ditentukan oleh Kaprodi dan terkunci. Hanya Admin Utama yang berwenang mengubahnya.');
        }

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
        $actorRole = $user->isAdminUtama() ? 'Admin Utama' : 'Kaprodi';
        $logAction = $isUpdatePenguji ? 'Pembaruan 2 Penguji Sidang' : 'Penetapan 2 Penguji Sidang';

        AktivitasLog::catat(
            $user,
            $logAction,
            "{$actorRole} {$user->name} " . ($isUpdatePenguji ? "memperbarui" : "menetapkan") . " 2 Penguji Sidang Meja Hijau untuk mahasiswa {$mhs->name} ({$mhs->nomor_induk})"
        );

        // Notifikasi ke Mahasiswa
        Notifikasi::kirim(
            $mhs->id,
            '2 Dosen Penguji Sidang Ditetapkan',
            "2 Orang Dosen Penguji Sidang Skripsi telah ditetapkan. Menunggu pengaturan jadwal pelaksanaan dari Admin.",
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

        // Notifikasi ke Admin (Prodi & Utama) untuk persiapan jadwal
        Notifikasi::kirimKePengelola(
            $sidang->pengajuanSkripsi->program_studi_id,
            'Dewan Penguji Sidang Telah Ditetapkan',
            "Dewan Penguji Sidang Skripsi untuk mahasiswa {$mhs->name} ({$mhs->nomor_induk}) telah ditetapkan. Silakan atur jadwal dan terbitkan dokumen sidang.",
            null,
            $user->id,
            [UserRole::AdminUtama, UserRole::AdminProdi]
        );

        $msg = $isUpdatePenguji
            ? '2 Orang Dosen Penguji Sidang Skripsi berhasil diperbarui.'
            : '2 Orang Dosen Penguji Sidang Skripsi berhasil ditetapkan.';

        return back()->with('success', $msg);
    }
}
