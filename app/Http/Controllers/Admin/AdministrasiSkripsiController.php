<?php

namespace App\Http\Controllers\Admin;

use App\Enums\StatusPengajuan;
use App\Http\Controllers\Controller;
use App\Models\AktivitasLog;
use App\Models\Notifikasi;
use App\Models\PengajuanSkripsi;
use App\Models\ProgramStudi;
use App\Models\SeminarSkripsi;
use App\Models\SidangSkripsi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdministrasiSkripsiController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $prodiFilter = $request->input('prodi_id', $user->isAdminProdi() ? $user->program_studi_id : null);
        $statusFilter = $request->input('status');

        $querySkripsi = PengajuanSkripsi::with(['mahasiswa', 'programStudi', 'pembimbing1', 'pembimbing2']);
        $querySeminar = SeminarSkripsi::with(['pengajuanSkripsi.mahasiswa', 'penguji']);
        $querySidang = SidangSkripsi::with(['pengajuanSkripsi.mahasiswa', 'penguji1', 'penguji2']);

        if ($prodiFilter) {
            $querySkripsi->where('program_studi_id', $prodiFilter);
            $querySeminar->whereHas('pengajuanSkripsi', fn($q) => $q->where('program_studi_id', $prodiFilter));
            $querySidang->whereHas('pengajuanSkripsi', fn($q) => $q->where('program_studi_id', $prodiFilter));
        }

        if ($statusFilter) {
            $querySkripsi->where('status', $statusFilter);
            $querySeminar->where('status', $statusFilter);
            $querySidang->where('status', $statusFilter);
        }

        $daftarSkripsi = $querySkripsi->latest()->paginate(10, ['*'], 'page_skripsi');
        $daftarSeminar = $querySeminar->latest()->paginate(10, ['*'], 'page_seminar');
        $daftarSidang = $querySidang->latest()->paginate(10, ['*'], 'page_sidang');

        $daftarProdi = ProgramStudi::all();

        return view('admin.administrasi.index', compact(
            'daftarSkripsi',
            'daftarSeminar',
            'daftarSidang',
            'daftarProdi',
            'prodiFilter',
            'statusFilter',
            'user'
        ));
    }

    public function updateSkBimbingan(Request $request, PengajuanSkripsi $skripsi): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'nomor_sk_bimbingan' => ['required', 'string', 'max:100'],
            'tgl_sk_bimbingan' => ['required', 'date'],
            'file_sk_bimbingan' => ['nullable', 'file', 'mimes:pdf', 'max:5120'],
        ], [
            'nomor_sk_bimbingan.required' => 'Nomor SK Bimbingan wajib diisi.',
            'tgl_sk_bimbingan.required' => 'Tanggal SK Bimbingan wajib diisi.',
        ]);

        $data = [
            'nomor_sk_bimbingan' => $validated['nomor_sk_bimbingan'],
            'tgl_sk_bimbingan' => $validated['tgl_sk_bimbingan'],
            'status' => StatusPengajuan::Selesai, // SK terbit = status selesai
        ];

        if ($request->hasFile('file_sk_bimbingan')) {
            $data['file_sk_bimbingan'] = $request->file('file_sk_bimbingan')->store('skripsi/sk_bimbingan');
        }

        $skripsi->update($data);

        AktivitasLog::catat(
            $user,
            'Penerbitan SK Bimbingan',
            "Admin {$user->name} menerbitkan SK Bimbingan No. {$validated['nomor_sk_bimbingan']} untuk mahasiswa {$skripsi->mahasiswa->name} ({$skripsi->mahasiswa->nomor_induk})"
        );

        // Notifikasi ke Mahasiswa
        Notifikasi::kirim(
            $skripsi->mahasiswa_id,
            'SK Bimbingan Resmi Diterbitkan',
            "Surat Keputusan (SK) Bimbingan Anda No: {$validated['nomor_sk_bimbingan']} telah resmi diterbitkan. Anda dapat mengunduh dokumen SK dan memulai proses bimbingan.",
            route('mahasiswa.skripsi.index')
        );

        // Notifikasi ke Pembimbing 1 & 2
        if ($skripsi->pembimbing_1_id) {
            Notifikasi::kirim(
                $skripsi->pembimbing_1_id,
                'SK Bimbingan Mahasiswa Diterbitkan',
                "SK Bimbingan resmi untuk mahasiswa bimbingan Anda {$skripsi->mahasiswa->name} ({$skripsi->mahasiswa->nomor_induk}) telah diterbitkan (No: {$validated['nomor_sk_bimbingan']}).",
                route('dosen.bimbingan.index')
            );
        }

        if ($skripsi->pembimbing_2_id) {
            Notifikasi::kirim(
                $skripsi->pembimbing_2_id,
                'SK Bimbingan Mahasiswa Diterbitkan',
                "SK Bimbingan resmi untuk mahasiswa bimbingan Anda {$skripsi->mahasiswa->name} ({$skripsi->mahasiswa->nomor_induk}) telah diterbitkan (No: {$validated['nomor_sk_bimbingan']}).",
                route('dosen.bimbingan.index')
            );
        }

        return back()->with('success', 'SK Bimbingan berhasil diterbitkan dan status diselesaikan.');
    }

    public function updateJadwalDanSkSeminar(Request $request, SeminarSkripsi $seminar): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'tgl_seminar' => ['required', 'date'],
            'jam_seminar' => ['required', 'string'],
            'ruangan' => ['required', 'string', 'max:100'],
            'nomor_undangan_seminar' => ['nullable', 'string', 'max:100'],
            'file_undangan_seminar' => ['nullable', 'file', 'mimes:pdf', 'max:5120'],
            'nomor_sk_seminar' => ['nullable', 'string', 'max:100'],
            'file_sk_seminar' => ['nullable', 'file', 'mimes:pdf', 'max:5120'],
        ]);

        $data = [
            'tgl_seminar' => $validated['tgl_seminar'],
            'jam_seminar' => $validated['jam_seminar'],
            'ruangan' => $validated['ruangan'],
            'nomor_undangan_seminar' => $validated['nomor_undangan_seminar'] ?? null,
            'nomor_sk_seminar' => $validated['nomor_sk_seminar'] ?? null,
            'status' => StatusPengajuan::Diproses,
        ];

        if ($request->hasFile('file_undangan_seminar')) {
            $data['file_undangan_seminar'] = $request->file('file_undangan_seminar')->store('seminar/undangan');
        }

        if ($request->hasFile('file_sk_seminar')) {
            $data['file_sk_seminar'] = $request->file('file_sk_seminar')->store('seminar/sk');
        }

        $seminar->update($data);

        $mhs = $seminar->pengajuanSkripsi->mahasiswa;

        AktivitasLog::catat(
            $user,
            'Penjadwalan Seminar Skripsi',
            "Admin {$user->name} menetapkan jadwal seminar untuk mahasiswa {$mhs->name} pada {$validated['tgl_seminar']} ({$validated['jam_seminar']}) di {$validated['ruangan']}"
        );

        // Notifikasi ke Mahasiswa
        Notifikasi::kirim(
            $mhs->id,
            'Jadwal & Dokumen Seminar Diterbitkan',
            "Jadwal seminar proposal/hasil Anda telah ditetapkan: {$validated['tgl_seminar']} ({$validated['jam_seminar']}) di {$validated['ruangan']}. Silakan unduh surat undangan.",
            route('mahasiswa.seminar.index')
        );

        // Notifikasi ke Dosen Penguji Seminar
        if ($seminar->penguji_seminar_id) {
            Notifikasi::kirim(
                $seminar->penguji_seminar_id,
                'Jadwal Ujian Seminar Mahasiswa Ditetapkan',
                "Jadwal ujian seminar untuk mahasiswa {$mhs->name} ({$mhs->nomor_induk}) telah ditetapkan: {$validated['tgl_seminar']} ({$validated['jam_seminar']}) di {$validated['ruangan']}.",
                route('dosen.bimbingan.index')
            );
        }

        return back()->with('success', 'Jadwal dan dokumen surat/SK Seminar berhasil diperbarui.');
    }

    public function selesaikanSeminar(Request $request, SeminarSkripsi $seminar): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'nilai_seminar' => ['required', 'numeric', 'min:0', 'max:100'],
            'catatan' => ['nullable', 'string', 'max:1000'],
        ]);

        $seminar->update([
            'nilai_seminar' => $validated['nilai_seminar'],
            'catatan' => $validated['catatan'] ?? null,
            'status' => StatusPengajuan::Selesai,
        ]);

        $mhs = $seminar->pengajuanSkripsi->mahasiswa;

        AktivitasLog::catat(
            $user,
            'Finalisasi Nilai Seminar',
            "Admin {$user->name} menginput nilai seminar ({$validated['nilai_seminar']}) untuk mahasiswa {$mhs->name}"
        );

        // Notifikasi ke Mahasiswa
        Notifikasi::kirim(
            $mhs->id,
            'Hasil & Nilai Seminar Telah Keluar',
            "Selamat! Nilai seminar Anda telah diinput: {$validated['nilai_seminar']}. Anda telah dinyatakan LULUS seminar dan dapat melanjutkan pendaftaran Sidang Skripsi.",
            route('mahasiswa.sidang.index')
        );

        return back()->with('success', 'Nilai seminar berhasil disimpan dan status dinyatakan Selesai/Lulus.');
    }

    public function updateJadwalDanSkSidang(Request $request, SidangSkripsi $sidang): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'tgl_sidang' => ['required', 'date'],
            'jam_sidang' => ['required', 'string'],
            'ruangan' => ['required', 'string', 'max:100'],
            'nomor_undangan_sidang' => ['nullable', 'string', 'max:100'],
            'file_undangan_sidang' => ['nullable', 'file', 'mimes:pdf', 'max:5120'],
            'nomor_sk_sidang' => ['nullable', 'string', 'max:100'],
            'file_sk_sidang' => ['nullable', 'file', 'mimes:pdf', 'max:5120'],
        ]);

        $data = [
            'tgl_sidang' => $validated['tgl_sidang'],
            'jam_sidang' => $validated['jam_sidang'],
            'ruangan' => $validated['ruangan'],
            'nomor_undangan_sidang' => $validated['nomor_undangan_sidang'] ?? null,
            'nomor_sk_sidang' => $validated['nomor_sk_sidang'] ?? null,
            'status' => StatusPengajuan::Diproses,
        ];

        if ($request->hasFile('file_undangan_sidang')) {
            $data['file_undangan_sidang'] = $request->file('file_undangan_sidang')->store('sidang/undangan');
        }

        if ($request->hasFile('file_sk_sidang')) {
            $data['file_sk_sidang'] = $request->file('file_sk_sidang')->store('sidang/sk');
        }

        $sidang->update($data);

        $mhs = $sidang->pengajuanSkripsi->mahasiswa;

        AktivitasLog::catat(
            $user,
            'Penjadwalan Sidang Skripsi',
            "Admin {$user->name} menetapkan jadwal sidang meja hijau untuk mahasiswa {$mhs->name} pada {$validated['tgl_sidang']} ({$validated['jam_sidang']}) di {$validated['ruangan']}"
        );

        // Notifikasi ke Mahasiswa
        Notifikasi::kirim(
            $mhs->id,
            'Jadwal & Dokumen Sidang Diterbitkan',
            "Jadwal sidang meja hijau Anda telah ditetapkan: {$validated['tgl_sidang']} ({$validated['jam_sidang']}) di {$validated['ruangan']}. Silakan unduh surat undangan sidang.",
            route('mahasiswa.sidang.index')
        );

        // Notifikasi ke Penguji 1 & 2
        if ($sidang->penguji_1_id) {
            Notifikasi::kirim(
                $sidang->penguji_1_id,
                'Jadwal Sidang Skripsi Mahasiswa Ditetapkan',
                "Jadwal sidang meja hijau untuk mahasiswa {$mhs->name} ({$mhs->nomor_induk}) telah ditetapkan: {$validated['tgl_sidang']} ({$validated['jam_sidang']}) di {$validated['ruangan']}.",
                route('dosen.bimbingan.index')
            );
        }

        if ($sidang->penguji_2_id) {
            Notifikasi::kirim(
                $sidang->penguji_2_id,
                'Jadwal Sidang Skripsi Mahasiswa Ditetapkan',
                "Jadwal sidang meja hijau untuk mahasiswa {$mhs->name} ({$mhs->nomor_induk}) telah ditetapkan: {$validated['tgl_sidang']} ({$validated['jam_sidang']}) di {$validated['ruangan']}.",
                route('dosen.bimbingan.index')
            );
        }

        return back()->with('success', 'Jadwal dan dokumen surat/SK Sidang berhasil diperbarui.');
    }

    public function selesaikanSidang(Request $request, SidangSkripsi $sidang): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'nilai_sidang' => ['required', 'numeric', 'min:0', 'max:100'],
            'catatan' => ['nullable', 'string', 'max:1000'],
        ]);

        $sidang->update([
            'nilai_sidang' => $validated['nilai_sidang'],
            'catatan' => $validated['catatan'] ?? null,
            'status' => StatusPengajuan::Selesai,
        ]);

        $mhs = $sidang->pengajuanSkripsi->mahasiswa;

        AktivitasLog::catat(
            $user,
            'Finalisasi Kelulusan Sidang',
            "Admin {$user->name} menginput nilai akhir sidang ({$validated['nilai_sidang']}) dan menetapkan kelulusan untuk mahasiswa {$mhs->name}"
        );

        // Notifikasi ke Mahasiswa
        Notifikasi::kirim(
            $mhs->id,
            'Selamat! Anda Dinyatakan LULUS Sidang Skripsi',
            "Selamat atas perjuangan Anda! Nilai akhir sidang meja hijau Anda adalah: {$validated['nilai_sidang']}. Anda resmi dinyatakan LULUS Sidang Skripsi.",
            route('mahasiswa.sidang.index')
        );

        return back()->with('success', 'Nilai sidang berhasil disimpan dan status dinyatakan Selesai / Lulus Sidang.');
    }
}
