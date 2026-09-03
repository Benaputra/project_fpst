<?php

namespace App\Http\Controllers\Admin;

use App\Enums\StatusPengajuan;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\AktivitasLog;
use App\Models\Notifikasi;
use App\Models\PengajuanSkripsi;
use App\Models\ProgramStudi;
use App\Models\SeminarSkripsi;
use App\Models\SidangSkripsi;
use App\Models\Surat;
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
        $jenisSuratFilter = $request->input('jenis_surat');
        $cariSurat = $request->input('q_surat');

        // Tab 1: SK Bimbingan
        $querySkripsi = PengajuanSkripsi::with(['mahasiswa', 'programStudi', 'pembimbing1', 'pembimbing2']);
        if ($prodiFilter) {
            $querySkripsi->where('program_studi_id', $prodiFilter);
        }
        if ($statusFilter) {
            $querySkripsi->where('status', $statusFilter);
        }
        if ($searchSkripsi = $request->input('search_skripsi')) {
            $querySkripsi->where(function ($q) use ($searchSkripsi) {
                $q->where('judul', 'like', "%{$searchSkripsi}%")
                    ->orWhere('nomor_sk_bimbingan', 'like', "%{$searchSkripsi}%")
                    ->orWhereHas('mahasiswa', fn($m) => $m->where('name', 'like', "%{$searchSkripsi}%")->orWhere('nomor_induk', 'like', "%{$searchSkripsi}%"));
            });
        }
        if ($statusSkripsi = $request->input('status_skripsi')) {
            if ($statusSkripsi === 'menunggu') {
                $querySkripsi->whereNull('nomor_sk_bimbingan');
            } elseif ($statusSkripsi === 'selesai') {
                $querySkripsi->whereNotNull('nomor_sk_bimbingan');
            }
        }
        if ($request->input('sort_skripsi', 'fifo') === 'fifo') {
            $querySkripsi->oldest();
        } else {
            $querySkripsi->latest();
        }
        $daftarSkripsi = $querySkripsi->paginate(10, ['*'], 'page_skripsi')->withQueryString();

        // Tab 2: Jadwal & Dokumen Seminar
        $querySeminar = SeminarSkripsi::with(['pengajuanSkripsi.mahasiswa', 'penguji']);
        if ($prodiFilter) {
            $querySeminar->whereHas('pengajuanSkripsi', fn($q) => $q->where('program_studi_id', $prodiFilter));
        }
        if ($statusFilter) {
            $querySeminar->where('status', $statusFilter);
        }
        if ($searchSeminar = $request->input('search_seminar')) {
            $querySeminar->where(function ($q) use ($searchSeminar) {
                $q->where('nomor_undangan_seminar', 'like', "%{$searchSeminar}%")
                    ->orWhere('nomor_sk_seminar', 'like', "%{$searchSeminar}%")
                    ->orWhereHas('pengajuanSkripsi', fn($ps) => 
                        $ps->where('judul', 'like', "%{$searchSeminar}%")
                            ->orWhereHas('mahasiswa', fn($m) => $m->where('name', 'like', "%{$searchSeminar}%")->orWhere('nomor_induk', 'like', "%{$searchSeminar}%"))
                    );
            });
        }
        if ($statusSeminar = $request->input('status_seminar')) {
            if ($statusSeminar === 'menunggu_jadwal') {
                $querySeminar->whereNull('tgl_seminar');
            } elseif ($statusSeminar === 'menunggu_nilai') {
                $querySeminar->whereNotNull('tgl_seminar')->whereNull('nilai_seminar');
            } elseif ($statusSeminar === 'selesai') {
                $querySeminar->whereNotNull('nilai_seminar');
            }
        }
        if ($request->input('sort_seminar', 'fifo') === 'fifo') {
            $querySeminar->oldest();
        } else {
            $querySeminar->latest();
        }
        $daftarSeminar = $querySeminar->paginate(10, ['*'], 'page_seminar')->withQueryString();

        // Tab 3: Jadwal & Dokumen Sidang
        $querySidang = SidangSkripsi::with(['pengajuanSkripsi.mahasiswa', 'penguji1', 'penguji2']);
        if ($prodiFilter) {
            $querySidang->whereHas('pengajuanSkripsi', fn($q) => $q->where('program_studi_id', $prodiFilter));
        }
        if ($statusFilter) {
            $querySidang->where('status', $statusFilter);
        }
        if ($searchSidang = $request->input('search_sidang')) {
            $querySidang->where(function ($q) use ($searchSidang) {
                $q->where('nomor_undangan_sidang', 'like', "%{$searchSidang}%")
                    ->orWhere('nomor_sk_sidang', 'like', "%{$searchSidang}%")
                    ->orWhereHas('pengajuanSkripsi', fn($ps) => 
                        $ps->where('judul', 'like', "%{$searchSidang}%")
                            ->orWhereHas('mahasiswa', fn($m) => $m->where('name', 'like', "%{$searchSidang}%")->orWhere('nomor_induk', 'like', "%{$searchSidang}%"))
                    );
            });
        }
        if ($statusSidang = $request->input('status_sidang')) {
            if ($statusSidang === 'menunggu_jadwal') {
                $querySidang->whereNull('tgl_sidang');
            } elseif ($statusSidang === 'menunggu_nilai') {
                $querySidang->whereNotNull('tgl_sidang')->whereNull('nilai_sidang');
            } elseif ($statusSidang === 'selesai') {
                $querySidang->whereNotNull('nilai_sidang');
            }
        }
        if ($request->input('sort_sidang', 'fifo') === 'fifo') {
            $querySidang->oldest();
        } else {
            $querySidang->latest();
        }
        $daftarSidang = $querySidang->paginate(10, ['*'], 'page_sidang')->withQueryString();

        // Tab 4: Arsip Surat & SK
        $querySurat = Surat::with(['pengajuanSkripsi.mahasiswa', 'programStudi', 'penerbit']);
        if ($prodiFilter) {
            $querySurat->where('program_studi_id', $prodiFilter);
        }
        if ($jenisSuratFilter) {
            $querySurat->where('jenis_surat', $jenisSuratFilter);
        }
        if ($cariSurat) {
            $querySurat->where(function ($q) use ($cariSurat) {
                $q->where('nomor_surat', 'like', "%{$cariSurat}%")
                  ->orWhere('nama_surat', 'like', "%{$cariSurat}%");
            });
        }
        $daftarSurat = $querySurat->latest()->paginate(10, ['*'], 'page_surat')->withQueryString();

        $daftarProdi = ProgramStudi::all();

        // Hitung antrean pending
        $qPendingSk = PengajuanSkripsi::whereNull('nomor_sk_bimbingan')->whereNotNull('pembimbing_1_id');
        $qPendingSeminar = SeminarSkripsi::where(fn($q) => $q->whereNull('tgl_seminar')->orWhereNull('nilai_seminar'));
        $qPendingSidang = SidangSkripsi::where(fn($q) => $q->whereNull('tgl_sidang')->orWhereNull('nilai_sidang'));

        if ($prodiFilter) {
            $qPendingSk->where('program_studi_id', $prodiFilter);
            $qPendingSeminar->whereHas('pengajuanSkripsi', fn($q) => $q->where('program_studi_id', $prodiFilter));
            $qPendingSidang->whereHas('pengajuanSkripsi', fn($q) => $q->where('program_studi_id', $prodiFilter));
        }

        $pendingSkCount = $qPendingSk->count();
        $pendingSeminarCount = $qPendingSeminar->count();
        $pendingSidangCount = $qPendingSidang->count();

        return view('admin.administrasi.index', compact(
            'daftarSkripsi',
            'daftarSeminar',
            'daftarSidang',
            'daftarSurat',
            'daftarProdi',
            'prodiFilter',
            'statusFilter',
            'jenisSuratFilter',
            'cariSurat',
            'pendingSkCount',
            'pendingSeminarCount',
            'pendingSidangCount',
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

        $mhs = $skripsi->mahasiswa;
        $nim = $mhs->nomor_induk;

        // Aturan 2: Penamaan SK yang sudah diterbitkan jika ada update, maka berikan Nama baru
        $isUpdate = !empty($skripsi->nomor_sk_bimbingan) || Surat::where('pengajuan_skripsi_id', $skripsi->id)->where('jenis_surat', 'sk_bimbingan')->exists();
        $versi = Surat::where('pengajuan_skripsi_id', $skripsi->id)->where('jenis_surat', 'sk_bimbingan')->count() + 1;

        if ($isUpdate) {
            $namaSurat = "SK Pembimbing Skripsi (Pembaruan ke-{$versi}) - {$mhs->name}";
            Surat::where('pengajuan_skripsi_id', $skripsi->id)
                ->where('jenis_surat', 'sk_bimbingan')
                ->where('status', 'aktif')
                ->update(['status' => 'diperbarui']);
        } else {
            $namaSurat = "SK Pembimbing Skripsi - {$mhs->name}";
        }

        $data = [
            'nomor_sk_bimbingan' => $validated['nomor_sk_bimbingan'],
            'tgl_sk_bimbingan' => $validated['tgl_sk_bimbingan'],
            'status' => StatusPengajuan::Selesai,
        ];

        $filePath = $skripsi->file_sk_bimbingan;
        if ($request->hasFile('file_sk_bimbingan')) {
            $ext = $request->file('file_sk_bimbingan')->getClientOriginalExtension() ?: 'pdf';
            $fileName = $isUpdate
                ? "SK_Bimbingan_{$nim}_Rev{$versi}_" . time() . ".{$ext}"
                : "SK_Bimbingan_{$nim}_" . time() . ".{$ext}";
            $filePath = $request->file('file_sk_bimbingan')->storeAs('skripsi/sk_bimbingan', $fileName);
            $data['file_sk_bimbingan'] = $filePath;
        }

        $skripsi->update($data);

        // Aturan 4: Catat ke tabel surat
        Surat::create([
            'nomor_surat' => $validated['nomor_sk_bimbingan'],
            'jenis_surat' => 'sk_bimbingan',
            'nama_surat' => $namaSurat,
            'pengajuan_skripsi_id' => $skripsi->id,
            'program_studi_id' => $skripsi->program_studi_id,
            'tgl_surat' => $validated['tgl_sk_bimbingan'],
            'file_surat' => $filePath,
            'versi' => $versi,
            'status' => 'aktif',
            'diterbitkan_oleh' => $user->id,
            'keterangan' => $isUpdate ? "Pembaruan SK Pembimbing Skripsi ke-{$versi}" : 'Penerbitan awal SK Pembimbing Skripsi',
        ]);

        $actorRole = $user->isAdminUtama() ? 'Admin Utama' : 'Admin';
        $logAction = $isUpdate ? 'Pembaruan SK Bimbingan' : 'Penerbitan SK Bimbingan';
        AktivitasLog::catat(
            $user,
            $logAction,
            "{$actorRole} {$user->name} " . ($isUpdate ? "memperbarui" : "menerbitkan") . " SK Bimbingan No. {$validated['nomor_sk_bimbingan']} ('{$namaSurat}') untuk mahasiswa {$mhs->name} ({$nim})"
        );

        // Notifikasi ke Mahasiswa
        Notifikasi::kirim(
            $skripsi->mahasiswa_id,
            $isUpdate ? 'SK Bimbingan Telah Diperbarui' : 'SK Bimbingan Resmi Diterbitkan',
            "Surat Keputusan (SK) Bimbingan Anda No: {$validated['nomor_sk_bimbingan']} ({$namaSurat}) telah diterbitkan. Silakan unduh dokumen SK terbaru.",
            route('mahasiswa.skripsi.index')
        );

        // Notifikasi ke Pembimbing 1 & 2
        if ($skripsi->pembimbing_1_id) {
            Notifikasi::kirim(
                $skripsi->pembimbing_1_id,
                $isUpdate ? 'Pembaruan SK Bimbingan Mahasiswa' : 'SK Bimbingan Mahasiswa Diterbitkan',
                "SK Bimbingan untuk mahasiswa bimbingan Anda {$mhs->name} ({$nim}) telah " . ($isUpdate ? "diperbarui" : "diterbitkan") . " (No: {$validated['nomor_sk_bimbingan']}).",
                route('dosen.bimbingan.index')
            );
        }

        if ($skripsi->pembimbing_2_id) {
            Notifikasi::kirim(
                $skripsi->pembimbing_2_id,
                $isUpdate ? 'Pembaruan SK Bimbingan Mahasiswa' : 'SK Bimbingan Mahasiswa Diterbitkan',
                "SK Bimbingan untuk mahasiswa bimbingan Anda {$mhs->name} ({$nim}) telah " . ($isUpdate ? "diperbarui" : "diterbitkan") . " (No: {$validated['nomor_sk_bimbingan']}).",
                route('dosen.bimbingan.index')
            );
        }

        // Notifikasi ke Pengelola (Admin Utama & Kaprodi)
        Notifikasi::kirimKePengelola(
            $skripsi->program_studi_id,
            $isUpdate ? 'Pembaruan SK Bimbingan Mahasiswa' : 'Penerbitan SK Bimbingan Mahasiswa',
            "SK Bimbingan untuk mahasiswa {$mhs->name} ({$nim}) telah " . ($isUpdate ? "diperbarui" : "diterbitkan") . " (No: {$validated['nomor_sk_bimbingan']}).",
            null,
            $user->id,
            [UserRole::AdminUtama, UserRole::Kaprodi]
        );

        $msg = $isUpdate ? "SK Bimbingan berhasil diperbarui dengan nama baru ('{$namaSurat}')." : 'SK Bimbingan berhasil diterbitkan dan status diselesaikan.';
        return back()->with('success', $msg);
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

        $mhs = $seminar->pengajuanSkripsi->mahasiswa;
        $nim = $mhs->nomor_induk;

        $data = [
            'tgl_seminar' => $validated['tgl_seminar'],
            'jam_seminar' => $validated['jam_seminar'],
            'ruangan' => $validated['ruangan'],
            'nomor_undangan_seminar' => $validated['nomor_undangan_seminar'] ?? $seminar->nomor_undangan_seminar,
            'nomor_sk_seminar' => $validated['nomor_sk_seminar'] ?? $seminar->nomor_sk_seminar,
            'status' => StatusPengajuan::Diproses,
        ];

        $fileUndangan = $seminar->file_undangan_seminar;
        if ($request->hasFile('file_undangan_seminar')) {
            $ext = $request->file('file_undangan_seminar')->getClientOriginalExtension() ?: 'pdf';
            $fileUndangan = $request->file('file_undangan_seminar')->storeAs('seminar/undangan', "Undangan_Seminar_{$nim}_" . time() . ".{$ext}");
            $data['file_undangan_seminar'] = $fileUndangan;
        }

        $fileSk = $seminar->file_sk_seminar;
        if ($request->hasFile('file_sk_seminar')) {
            $ext = $request->file('file_sk_seminar')->getClientOriginalExtension() ?: 'pdf';
            $fileSk = $request->file('file_sk_seminar')->storeAs('seminar/sk', "SK_Seminar_{$nim}_" . time() . ".{$ext}");
            $data['file_sk_seminar'] = $fileSk;
        }

        $seminar->update($data);

        // Aturan 4: Catat Undangan Seminar ke tabel surat
        if (!empty($validated['nomor_undangan_seminar'])) {
            $versiUndangan = Surat::where('seminar_skripsi_id', $seminar->id)->where('jenis_surat', 'undangan_seminar')->count() + 1;
            Surat::where('seminar_skripsi_id', $seminar->id)
                ->where('jenis_surat', 'undangan_seminar')
                ->where('status', 'aktif')
                ->update(['status' => 'diperbarui']);

            Surat::create([
                'nomor_surat' => $validated['nomor_undangan_seminar'],
                'jenis_surat' => 'undangan_seminar',
                'nama_surat' => $versiUndangan > 1 ? "Surat Undangan Seminar (Pembaruan ke-{$versiUndangan}) - {$mhs->name}" : "Surat Undangan Seminar - {$mhs->name}",
                'pengajuan_skripsi_id' => $seminar->pengajuan_skripsi_id,
                'seminar_skripsi_id' => $seminar->id,
                'program_studi_id' => $seminar->pengajuanSkripsi->program_studi_id,
                'tgl_surat' => $validated['tgl_seminar'],
                'file_surat' => $fileUndangan,
                'versi' => $versiUndangan,
                'status' => 'aktif',
                'diterbitkan_oleh' => $user->id,
                'keterangan' => 'Surat Undangan Seminar Skripsi (Mahasiswa, Penguji Seminar, Pembimbing 1 & 2)',
            ]);
        }

        // Aturan 2 & 4: Catat SK Penguji Seminar ke tabel surat (dengan penamaan baru bila update)
        if (!empty($validated['nomor_sk_seminar'])) {
            $isUpdateSk = !empty($seminar->getOriginal('nomor_sk_seminar')) || Surat::where('seminar_skripsi_id', $seminar->id)->where('jenis_surat', 'sk_seminar')->exists();
            $versiSk = Surat::where('seminar_skripsi_id', $seminar->id)->where('jenis_surat', 'sk_seminar')->count() + 1;

            if ($isUpdateSk) {
                $namaSk = "SK Penguji Seminar (Pembaruan ke-{$versiSk}) - {$mhs->name}";
                Surat::where('seminar_skripsi_id', $seminar->id)
                    ->where('jenis_surat', 'sk_seminar')
                    ->where('status', 'aktif')
                    ->update(['status' => 'diperbarui']);
            } else {
                $namaSk = "SK Penguji Seminar - {$mhs->name}";
            }

            Surat::create([
                'nomor_surat' => $validated['nomor_sk_seminar'],
                'jenis_surat' => 'sk_seminar',
                'nama_surat' => $namaSk,
                'pengajuan_skripsi_id' => $seminar->pengajuan_skripsi_id,
                'seminar_skripsi_id' => $seminar->id,
                'program_studi_id' => $seminar->pengajuanSkripsi->program_studi_id,
                'tgl_surat' => $validated['tgl_seminar'],
                'file_surat' => $fileSk,
                'versi' => $versiSk,
                'status' => 'aktif',
                'diterbitkan_oleh' => $user->id,
                'keterangan' => $isUpdateSk ? "Pembaruan SK Penguji Seminar ke-{$versiSk}" : 'Penerbitan SK Penguji Seminar',
            ]);
        }

        AktivitasLog::catat(
            $user,
            'Penjadwalan Seminar Skripsi',
            "Admin {$user->name} menetapkan jadwal & dokumen seminar untuk mahasiswa {$mhs->name} pada {$validated['tgl_seminar']} ({$validated['jam_seminar']}) di {$validated['ruangan']}"
        );

        // Notifikasi ke Mahasiswa
        Notifikasi::kirim(
            $mhs->id,
            'Jadwal & Dokumen Seminar Diterbitkan',
            "Jadwal seminar proposal/hasil Anda telah ditetapkan: {$validated['tgl_seminar']} ({$validated['jam_seminar']}) di {$validated['ruangan']}. Silakan unduh surat undangan.",
            route('mahasiswa.seminar.index')
        );

        // Notifikasi ke Tim Seminar: Pembimbing 1 & 2 serta Penguji Seminar
        $skripsi = $seminar->pengajuanSkripsi;
        if ($skripsi->pembimbing_1_id) {
            Notifikasi::kirim(
                $skripsi->pembimbing_1_id,
                'Undangan Seminar Skripsi Mahasiswa Bimbingan',
                "Mahasiswa bimbingan Anda {$mhs->name} ({$nim}) telah dijadwalkan seminar proposal/hasil pada {$validated['tgl_seminar']} ({$validated['jam_seminar']}) di {$validated['ruangan']}. Surat undangan seminar telah diterbitkan.",
                route('dosen.bimbingan.index')
            );
        }

        if ($skripsi->pembimbing_2_id) {
            Notifikasi::kirim(
                $skripsi->pembimbing_2_id,
                'Undangan Seminar Skripsi Mahasiswa Bimbingan',
                "Mahasiswa bimbingan Anda {$mhs->name} ({$nim}) telah dijadwalkan seminar proposal/hasil pada {$validated['tgl_seminar']} ({$validated['jam_seminar']}) di {$validated['ruangan']}. Surat undangan seminar telah diterbitkan.",
                route('dosen.bimbingan.index')
            );
        }

        // Notifikasi ke Dosen Penguji Seminar
        if ($seminar->penguji_seminar_id) {
            Notifikasi::kirim(
                $seminar->penguji_seminar_id,
                'Undangan & Jadwal Ujian Seminar Mahasiswa',
                "Jadwal ujian seminar untuk mahasiswa {$mhs->name} ({$nim}) telah ditetapkan: {$validated['tgl_seminar']} ({$validated['jam_seminar']}) di {$validated['ruangan']}. Surat undangan seminar telah diterbitkan.",
                route('dosen.bimbingan.index')
            );
        }

        // Notifikasi ke Pengelola (Admin Utama & Kaprodi)
        Notifikasi::kirimKePengelola(
            $seminar->pengajuanSkripsi->program_studi_id,
            'Jadwal Seminar Skripsi Ditetapkan',
            "Jadwal seminar untuk mahasiswa {$mhs->name} ({$nim}) telah ditetapkan pada {$validated['tgl_seminar']} ({$validated['jam_seminar']}) di {$validated['ruangan']}.",
            null,
            $user->id,
            [UserRole::AdminUtama, UserRole::Kaprodi]
        );

        return back()->with('success', 'Jadwal dan dokumen surat/SK Seminar berhasil diperbarui. Undangan telah dikirimkan ke tim seminar (mahasiswa, penguji, dan pembimbing).');
    }

    public function selesaikanSeminar(Request $request, SeminarSkripsi $seminar): RedirectResponse
    {
        $user = $request->user();

        // Aturan 5: Selain admin utama, role lain tidak boleh merubah nilai jika sudah ditentukan
        if ($seminar->nilai_seminar !== null && !$user->isAdminUtama()) {
            return back()->with('error', 'Nilai seminar telah ditentukan dan terkunci. Hanya Admin Utama yang berwenang mengubah nilai.');
        }

        $validated = $request->validate([
            'nilai_seminar' => ['required', 'numeric', 'min:0', 'max:100'],
            'catatan' => ['nullable', 'string', 'max:1000'],
        ]);

        $isUpdateNilai = $seminar->nilai_seminar !== null;

        $seminar->update([
            'nilai_seminar' => $validated['nilai_seminar'],
            'catatan' => $validated['catatan'] ?? null,
            'status' => StatusPengajuan::Selesai,
        ]);

        $mhs = $seminar->pengajuanSkripsi->mahasiswa;
        $nim = $mhs->nomor_induk;
        $actorRole = $user->isAdminUtama() ? 'Admin Utama' : 'Admin';

        AktivitasLog::catat(
            $user,
            $isUpdateNilai ? 'Pembaruan Nilai Seminar' : 'Finalisasi Nilai Seminar',
            "{$actorRole} {$user->name} " . ($isUpdateNilai ? "memperbarui" : "menginput") . " nilai seminar ({$validated['nilai_seminar']}) untuk mahasiswa {$mhs->name}"
        );

        // Notifikasi ke Mahasiswa
        Notifikasi::kirim(
            $mhs->id,
            $isUpdateNilai ? 'Pembaruan Nilai Seminar' : 'Hasil & Nilai Seminar Telah Keluar',
            "Nilai seminar Anda: {$validated['nilai_seminar']}. Status dinyatakan LULUS seminar dan dapat melanjutkan pendaftaran Sidang Skripsi.",
            route('mahasiswa.sidang.index')
        );

        // Notifikasi ke Pengelola (Admin Utama & Kaprodi)
        Notifikasi::kirimKePengelola(
            $seminar->pengajuanSkripsi->program_studi_id,
            'Hasil & Nilai Seminar Mahasiswa',
            "Seminar mahasiswa {$mhs->name} ({$nim}) telah selesai dinilai dengan skor {$validated['nilai_seminar']}.",
            null,
            $user->id,
            [UserRole::AdminUtama, UserRole::Kaprodi]
        );

        $msg = $isUpdateNilai ? 'Nilai seminar berhasil diperbarui oleh Admin Utama.' : 'Nilai seminar berhasil disimpan dan status dinyatakan Selesai/Lulus.';
        return back()->with('success', $msg);
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

        $mhs = $sidang->pengajuanSkripsi->mahasiswa;
        $nim = $mhs->nomor_induk;

        $data = [
            'tgl_sidang' => $validated['tgl_sidang'],
            'jam_sidang' => $validated['jam_sidang'],
            'ruangan' => $validated['ruangan'],
            'nomor_undangan_sidang' => $validated['nomor_undangan_sidang'] ?? $sidang->nomor_undangan_sidang,
            'nomor_sk_sidang' => $validated['nomor_sk_sidang'] ?? $sidang->nomor_sk_sidang,
            'status' => StatusPengajuan::Diproses,
        ];

        $fileUndangan = $sidang->file_undangan_sidang;
        if ($request->hasFile('file_undangan_sidang')) {
            $ext = $request->file('file_undangan_sidang')->getClientOriginalExtension() ?: 'pdf';
            $fileUndangan = $request->file('file_undangan_sidang')->storeAs('sidang/undangan', "Undangan_Sidang_{$nim}_" . time() . ".{$ext}");
            $data['file_undangan_sidang'] = $fileUndangan;
        }

        $fileSk = $sidang->file_sk_sidang;
        if ($request->hasFile('file_sk_sidang')) {
            $ext = $request->file('file_sk_sidang')->getClientOriginalExtension() ?: 'pdf';
            $fileSk = $request->file('file_sk_sidang')->storeAs('sidang/sk', "SK_Sidang_{$nim}_" . time() . ".{$ext}");
            $data['file_sk_sidang'] = $fileSk;
        }

        $sidang->update($data);

        // Aturan 4: Catat Undangan Sidang ke tabel surat
        if (!empty($validated['nomor_undangan_sidang'])) {
            $versiUndangan = Surat::where('sidang_skripsi_id', $sidang->id)->where('jenis_surat', 'undangan_sidang')->count() + 1;
            Surat::where('sidang_skripsi_id', $sidang->id)
                ->where('jenis_surat', 'undangan_sidang')
                ->where('status', 'aktif')
                ->update(['status' => 'diperbarui']);

            Surat::create([
                'nomor_surat' => $validated['nomor_undangan_sidang'],
                'jenis_surat' => 'undangan_sidang',
                'nama_surat' => $versiUndangan > 1 ? "Surat Undangan Sidang (Pembaruan ke-{$versiUndangan}) - {$mhs->name}" : "Surat Undangan Sidang - {$mhs->name}",
                'pengajuan_skripsi_id' => $sidang->pengajuan_skripsi_id,
                'sidang_skripsi_id' => $sidang->id,
                'program_studi_id' => $sidang->pengajuanSkripsi->program_studi_id,
                'tgl_surat' => $validated['tgl_sidang'],
                'file_surat' => $fileUndangan,
                'versi' => $versiUndangan,
                'status' => 'aktif',
                'diterbitkan_oleh' => $user->id,
                'keterangan' => 'Surat Undangan Sidang Skripsi (Mahasiswa, Dewan Penguji, Pembimbing 1 & 2)',
            ]);
        }

        // Aturan 2 & 4: Catat SK Penguji Sidang ke tabel surat (dengan penamaan baru bila update)
        if (!empty($validated['nomor_sk_sidang'])) {
            $isUpdateSk = !empty($sidang->getOriginal('nomor_sk_sidang')) || Surat::where('sidang_skripsi_id', $sidang->id)->where('jenis_surat', 'sk_sidang')->exists();
            $versiSk = Surat::where('sidang_skripsi_id', $sidang->id)->where('jenis_surat', 'sk_sidang')->count() + 1;

            if ($isUpdateSk) {
                $namaSk = "SK Dewan Penguji Sidang (Pembaruan ke-{$versiSk}) - {$mhs->name}";
                Surat::where('sidang_skripsi_id', $sidang->id)
                    ->where('jenis_surat', 'sk_sidang')
                    ->where('status', 'aktif')
                    ->update(['status' => 'diperbarui']);
            } else {
                $namaSk = "SK Dewan Penguji Sidang - {$mhs->name}";
            }

            Surat::create([
                'nomor_surat' => $validated['nomor_sk_sidang'],
                'jenis_surat' => 'sk_sidang',
                'nama_surat' => $namaSk,
                'pengajuan_skripsi_id' => $sidang->pengajuan_skripsi_id,
                'sidang_skripsi_id' => $sidang->id,
                'program_studi_id' => $sidang->pengajuanSkripsi->program_studi_id,
                'tgl_surat' => $validated['tgl_sidang'],
                'file_surat' => $fileSk,
                'versi' => $versiSk,
                'status' => 'aktif',
                'diterbitkan_oleh' => $user->id,
                'keterangan' => $isUpdateSk ? "Pembaruan SK Dewan Penguji Sidang ke-{$versiSk}" : 'Penerbitan SK Dewan Penguji Sidang',
            ]);
        }

        AktivitasLog::catat(
            $user,
            'Penjadwalan Sidang Skripsi',
            "Admin {$user->name} menetapkan jadwal sidang untuk mahasiswa {$mhs->name} pada {$validated['tgl_sidang']} ({$validated['jam_sidang']}) di {$validated['ruangan']}"
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
                "Jadwal sidang meja hijau untuk mahasiswa {$mhs->name} ({$nim}) telah ditetapkan: {$validated['tgl_sidang']} ({$validated['jam_sidang']}) di {$validated['ruangan']}.",
                route('dosen.bimbingan.index')
            );
        }

        if ($sidang->penguji_2_id) {
            Notifikasi::kirim(
                $sidang->penguji_2_id,
                'Jadwal Sidang Skripsi Mahasiswa Ditetapkan',
                "Jadwal sidang meja hijau untuk mahasiswa {$mhs->name} ({$nim}) telah ditetapkan: {$validated['tgl_sidang']} ({$validated['jam_sidang']}) di {$validated['ruangan']}.",
                route('dosen.bimbingan.index')
            );
        }

        // Aturan 3: Pembimbing skripsi 1 dan 2 juga mendapatkan undangan sidang skripsi!
        $skripsi = $sidang->pengajuanSkripsi;
        if ($skripsi->pembimbing_1_id) {
            Notifikasi::kirim(
                $skripsi->pembimbing_1_id,
                'Undangan Sidang Skripsi Mahasiswa Bimbingan',
                "Mahasiswa bimbingan Anda {$mhs->name} ({$nim}) telah dijadwalkan sidang meja hijau pada {$validated['tgl_sidang']} ({$validated['jam_sidang']}) di {$validated['ruangan']}. Surat undangan sidang telah diterbitkan.",
                route('dosen.bimbingan.index')
            );
        }

        if ($skripsi->pembimbing_2_id) {
            Notifikasi::kirim(
                $skripsi->pembimbing_2_id,
                'Undangan Sidang Skripsi Mahasiswa Bimbingan',
                "Mahasiswa bimbingan Anda {$mhs->name} ({$nim}) telah dijadwalkan sidang meja hijau pada {$validated['tgl_sidang']} ({$validated['jam_sidang']}) di {$validated['ruangan']}. Surat undangan sidang telah diterbitkan.",
                route('dosen.bimbingan.index')
            );
        }

        // Notifikasi ke Pengelola (Admin Utama & Kaprodi)
        Notifikasi::kirimKePengelola(
            $sidang->pengajuanSkripsi->program_studi_id,
            'Jadwal Sidang Skripsi Ditetapkan',
            "Jadwal sidang meja hijau untuk mahasiswa {$mhs->name} ({$nim}) telah ditetapkan pada {$validated['tgl_sidang']} ({$validated['jam_sidang']}) di {$validated['ruangan']}.",
            null,
            $user->id,
            [UserRole::AdminUtama, UserRole::Kaprodi]
        );

        return back()->with('success', 'Jadwal dan dokumen surat/SK Sidang berhasil diperbarui. Undangan telah dikirimkan ke mahasiswa, penguji, dan pembimbing.');
    }

    public function selesaikanSidang(Request $request, SidangSkripsi $sidang): RedirectResponse
    {
        $user = $request->user();

        // Aturan 5: Selain admin utama, role lain tidak boleh merubah nilai jika sudah ditentukan
        if ($sidang->nilai_sidang !== null && !$user->isAdminUtama()) {
            return back()->with('error', 'Nilai sidang telah ditentukan dan terkunci. Hanya Admin Utama yang berwenang mengubah nilai.');
        }

        $validated = $request->validate([
            'nilai_sidang' => ['required', 'numeric', 'min:0', 'max:100'],
            'catatan' => ['nullable', 'string', 'max:1000'],
        ]);

        $isUpdateNilai = $sidang->nilai_sidang !== null;

        $sidang->update([
            'nilai_sidang' => $validated['nilai_sidang'],
            'catatan' => $validated['catatan'] ?? null,
            'status' => StatusPengajuan::Selesai,
        ]);

        $mhs = $sidang->pengajuanSkripsi->mahasiswa;
        $nim = $mhs->nomor_induk;
        $actorRole = $user->isAdminUtama() ? 'Admin Utama' : 'Admin';

        AktivitasLog::catat(
            $user,
            $isUpdateNilai ? 'Pembaruan Nilai Akhir Sidang' : 'Finalisasi Kelulusan Sidang',
            "{$actorRole} {$user->name} " . ($isUpdateNilai ? "memperbarui" : "menginput") . " nilai akhir sidang ({$validated['nilai_sidang']}) dan menetapkan kelulusan untuk mahasiswa {$mhs->name}"
        );

        // Notifikasi ke Mahasiswa
        Notifikasi::kirim(
            $mhs->id,
            $isUpdateNilai ? 'Pembaruan Nilai Sidang Skripsi' : 'Selamat! Anda Dinyatakan LULUS Sidang Skripsi',
            "Nilai akhir sidang meja hijau Anda adalah: {$validated['nilai_sidang']}. Anda resmi dinyatakan LULUS Sidang Skripsi.",
            route('mahasiswa.sidang.index')
        );

        // Notifikasi ke Pengelola (Admin Utama & Kaprodi)
        Notifikasi::kirimKePengelola(
            $sidang->pengajuanSkripsi->program_studi_id,
            'Hasil & Nilai Akhir Sidang Skripsi',
            "Sidang meja hijau mahasiswa {$mhs->name} ({$nim}) telah selesai dinilai dengan hasil skor {$validated['nilai_sidang']}.",
            null,
            $user->id,
            [UserRole::AdminUtama, UserRole::Kaprodi]
        );

        $msg = $isUpdateNilai ? 'Nilai sidang berhasil diperbarui oleh Admin Utama.' : 'Nilai sidang berhasil disimpan dan status dinyatakan Selesai / Lulus Sidang.';
        return back()->with('success', $msg);
    }
}
