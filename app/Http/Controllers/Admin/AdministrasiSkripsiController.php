<?php

namespace App\Http\Controllers\Admin;

use App\Enums\HasilEvaluasiAkademik;
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
use App\Models\User;
use App\Services\JadwalBentrokService;
use App\Services\JadwalPublikService;
use App\Services\SuratUndanganService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Enum;
use Illuminate\View\View;

class AdministrasiSkripsiController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $this->authorizeAdminAccess($user);

        $prodiFilter = $user->isAdminProdi()
            ? $user->program_studi_id
            : $request->input('prodi_id');
        $statusFilter = $request->input('status');
        $jenisSuratFilter = $request->input('jenis_surat');
        $cariSurat = $request->input('q_surat');

        // Tab 1: SK Bimbingan
        $querySkripsi = PengajuanSkripsi::with(['mahasiswa', 'programStudi', 'pembimbing1', 'pembimbing2', 'penugasanDosen.dosen']);
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
        $this->authorizeAdminAccess($user, (int) $skripsi->program_studi_id);

        $validated = $request->validate([
            'nomor_sk_bimbingan' => ['required', 'string', 'max:100'],
            'tgl_sk_bimbingan' => ['required', 'date'],
            'file_sk_bimbingan' => ['nullable', 'file', 'mimes:pdf', 'max:5120'],
        ], [
            'nomor_sk_bimbingan.required' => 'Nomor SK Bimbingan wajib diisi.',
            'tgl_sk_bimbingan.required' => 'Tanggal SK Bimbingan wajib diisi.',
        ]);

        $createdFiles = [];

        try {
            $result = DB::transaction(function () use ($request, $user, $skripsi, $validated, &$createdFiles): array {
                $lockedSkripsi = PengajuanSkripsi::with('mahasiswa')->whereKey($skripsi->id)->lockForUpdate()->firstOrFail();

                // Cek konfirmasi pembimbing (Admin Utama berhak bypass/override)
                if (!$user->isAdminUtama() && !$lockedSkripsi->isPembimbingConfirmed()) {
                    return ['error' => 'SK Bimbingan belum dapat diterbitkan karena penunjukan dosen pembimbing masih menunggu konfirmasi kesediaan atau belum disetujui.'];
                }

                $mhs = $lockedSkripsi->mahasiswa;
                $nim = $mhs->nomor_induk;

                // Aturan 2: Penamaan SK yang sudah diterbitkan jika ada update, maka berikan Nama baru
                $existingCount = Surat::where('pengajuan_skripsi_id', $lockedSkripsi->id)
                    ->where('jenis_surat', 'sk_bimbingan')
                    ->lockForUpdate()
                    ->count();
                $isUpdate = !empty($lockedSkripsi->nomor_sk_bimbingan) || $existingCount > 0;
                $versi = $existingCount + 1;

                if ($isUpdate) {
                    $namaSurat = "SK Pembimbing Skripsi (Pembaruan ke-{$versi}) - {$mhs->name}";
                    Surat::where('pengajuan_skripsi_id', $lockedSkripsi->id)
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

                $filePath = $lockedSkripsi->file_sk_bimbingan;
                if ($request->hasFile('file_sk_bimbingan')) {
                    $ext = $request->file('file_sk_bimbingan')->getClientOriginalExtension() ?: 'pdf';
                    $suffix = time() . '_' . Str::lower(Str::random(6));
                    $fileName = $isUpdate
                        ? "SK_Bimbingan_{$nim}_Rev{$versi}_{$suffix}.{$ext}"
                        : "SK_Bimbingan_{$nim}_{$suffix}.{$ext}";
                    $filePath = $request->file('file_sk_bimbingan')->storeAs('skripsi/sk_bimbingan', $fileName, 'local');
                    $createdFiles[] = $filePath;
                    $data['file_sk_bimbingan'] = $filePath;
                }

                $lockedSkripsi->update($data);

                // Aturan 4: Catat ke tabel surat
                Surat::create([
                    'nomor_surat' => $validated['nomor_sk_bimbingan'],
                    'jenis_surat' => 'sk_bimbingan',
                    'nama_surat' => $namaSurat,
                    'pengajuan_skripsi_id' => $lockedSkripsi->id,
                    'program_studi_id' => $lockedSkripsi->program_studi_id,
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
                    "{$actorRole} {$user->name} " . ($isUpdate ? 'memperbarui' : 'menerbitkan') . " SK Bimbingan No. {$validated['nomor_sk_bimbingan']} ('{$namaSurat}') untuk mahasiswa {$mhs->name} ({$nim})"
                );

                // Notifikasi ke Mahasiswa
                Notifikasi::kirim(
                    $lockedSkripsi->mahasiswa_id,
                    $isUpdate ? 'SK Bimbingan Telah Diperbarui' : 'SK Bimbingan Resmi Diterbitkan',
                    "Surat Keputusan (SK) Bimbingan Anda No: {$validated['nomor_sk_bimbingan']} ({$namaSurat}) telah diterbitkan. Silakan unduh dokumen SK terbaru.",
                    route('mahasiswa.skripsi.index')
                );

                // Notifikasi ke Pembimbing 1 & 2
                if ($lockedSkripsi->pembimbing_1_id) {
                    Notifikasi::kirim(
                        $lockedSkripsi->pembimbing_1_id,
                        $isUpdate ? 'Pembaruan SK Bimbingan Mahasiswa' : 'SK Bimbingan Mahasiswa Diterbitkan',
                        "SK Bimbingan untuk mahasiswa bimbingan Anda {$mhs->name} ({$nim}) telah " . ($isUpdate ? 'diperbarui' : 'diterbitkan') . " (No: {$validated['nomor_sk_bimbingan']}).",
                        route('dosen.bimbingan.index')
                    );
                }

                if ($lockedSkripsi->pembimbing_2_id) {
                    Notifikasi::kirim(
                        $lockedSkripsi->pembimbing_2_id,
                        $isUpdate ? 'Pembaruan SK Bimbingan Mahasiswa' : 'SK Bimbingan Mahasiswa Diterbitkan',
                        "SK Bimbingan untuk mahasiswa bimbingan Anda {$mhs->name} ({$nim}) telah " . ($isUpdate ? 'diperbarui' : 'diterbitkan') . " (No: {$validated['nomor_sk_bimbingan']}).",
                        route('dosen.bimbingan.index')
                    );
                }

                // Notifikasi ke Pengelola (Admin Utama & Kaprodi)
                Notifikasi::kirimKePengelola(
                    $lockedSkripsi->program_studi_id,
                    $isUpdate ? 'Pembaruan SK Bimbingan Mahasiswa' : 'Penerbitan SK Bimbingan Mahasiswa',
                    "SK Bimbingan untuk mahasiswa {$mhs->name} ({$nim}) telah " . ($isUpdate ? 'diperbarui' : 'diterbitkan') . " (No: {$validated['nomor_sk_bimbingan']}).",
                    null,
                    $user->id,
                    [UserRole::AdminUtama, UserRole::Kaprodi]
                );

                $msg = $isUpdate
                    ? "SK Bimbingan berhasil diperbarui dengan nama baru ('{$namaSurat}')."
                    : 'SK Bimbingan berhasil diterbitkan dan status diselesaikan.';

                return ['success' => $msg];
            });
        } catch (\Throwable $e) {
            Storage::disk('local')->delete($createdFiles);
            throw $e;
        }

        if (isset($result['error'])) {
            Storage::disk('local')->delete($createdFiles);

            return back()->with('error', $result['error']);
        }

        JadwalPublikService::clearCache();

        return back()->with('success', $result['success']);
    }

    public function updateJadwalDanSkSeminar(
        Request $request,
        SeminarSkripsi $seminar,
        SuratUndanganService $suratService,
        JadwalBentrokService $bentrokService
    ): RedirectResponse {
        $user = $request->user();
        $seminar->loadMissing('pengajuanSkripsi');
        $this->authorizeAdminAccess($user, (int) $seminar->pengajuanSkripsi->program_studi_id);

        $maksKuotaSesi = (int) config('fpst.seminar_audiens.maks_kuota_sesi', 35);

        $validated = $request->validate([
            'tgl_seminar' => ['required', 'date'],
            'jam_seminar' => ['required', 'string'],
            'ruangan' => ['required', 'string', 'max:100'],
            'kuota_penonton' => ['nullable', 'integer', 'min:1', "max:{$maksKuotaSesi}"],
            'kuota_penyanggah' => ['nullable', 'integer', 'min:0', 'max:10'],
            'pendaftaran_audiens_dibuka' => ['nullable', 'boolean'],
            'nomor_undangan_seminar' => ['nullable', 'string', 'max:100'],
            'file_undangan_seminar' => ['nullable', 'file', 'mimes:pdf', 'max:5120'],
            'nomor_sk_seminar' => ['nullable', 'string', 'max:100'],
            'file_sk_seminar' => ['nullable', 'file', 'mimes:pdf', 'max:5120'],
        ], [
            'kuota_penonton.max' => "Kuota penonton seminar maksimal {$maksKuotaSesi} orang.",
        ]);

        $createdFiles = [];

        try {
            $result = DB::transaction(function () use ($request, $user, $seminar, $validated, $suratService, $bentrokService, $maksKuotaSesi, &$createdFiles): array {
                $lockedSeminar = SeminarSkripsi::with([
                    'pengajuanSkripsi.mahasiswa',
                    'pengajuanSkripsi.programStudi',
                    'pengajuanSkripsi.pembimbing1',
                    'pengajuanSkripsi.pembimbing2',
                    'penguji',
                ])->whereKey($seminar->id)->lockForUpdate()->firstOrFail();

                // Cek konfirmasi penguji seminar (Admin Utama berhak bypass/override)
                if (!$user->isAdminUtama() && !$lockedSeminar->isPengujiConfirmed()) {
                    return ['error' => 'Jadwal dan dokumen seminar belum dapat diterbitkan karena penugasan dosen penguji seminar masih menunggu konfirmasi kesediaan atau belum disetujui.'];
                }

                // Cek bentrok ruangan dan bentrok dosen lintas Seminar & Sidang Skripsi
                $conflict = $bentrokService->detectConflict(
                    $validated['tgl_seminar'],
                    $validated['jam_seminar'],
                    $validated['ruangan'],
                    [
                        $lockedSeminar->pengajuanSkripsi->pembimbing_1_id,
                        $lockedSeminar->pengajuanSkripsi->pembimbing_2_id,
                        $lockedSeminar->penguji_seminar_id,
                    ],
                    ignoreSeminarId: $lockedSeminar->id
                );
                if ($conflict !== null) {
                    return ['error' => $conflict];
                }

                $kuotaPenonton = (int) ($validated['kuota_penonton'] ?? $lockedSeminar->kuota_penonton ?? $maksKuotaSesi);
                $kuotaPenonton = max(1, min($kuotaPenonton, $maksKuotaSesi));
                $kuotaPenyanggah = (int) ($validated['kuota_penyanggah'] ?? $lockedSeminar->kuota_penyanggah ?? 2);
                $kuotaPenyanggah = max(0, min($kuotaPenyanggah, $kuotaPenonton));

                $mhs = $lockedSeminar->pengajuanSkripsi->mahasiswa;
                $nim = $mhs->nomor_induk;

                $data = [
                    'tgl_seminar' => $validated['tgl_seminar'],
                    'jam_seminar' => $validated['jam_seminar'],
                    'ruangan' => $validated['ruangan'],
                    'kuota_penonton' => $kuotaPenonton,
                    'kuota_penyanggah' => $kuotaPenyanggah,
                    'pendaftaran_audiens_dibuka' => $request->has('pendaftaran_audiens_dibuka')
                        ? (bool) $request->boolean('pendaftaran_audiens_dibuka')
                        : ($lockedSeminar->pendaftaran_audiens_dibuka ?? true),
                    'token_presensi' => $lockedSeminar->token_presensi ?: strtoupper(Str::random(10)),
                    'nomor_undangan_seminar' => $validated['nomor_undangan_seminar'] ?? $lockedSeminar->nomor_undangan_seminar,
                    'nomor_sk_seminar' => $validated['nomor_sk_seminar'] ?? $lockedSeminar->nomor_sk_seminar,
                    'status' => StatusPengajuan::Diproses,
                ];

                $fileUndangan = $lockedSeminar->file_undangan_seminar;
                if ($request->hasFile('file_undangan_seminar')) {
                    $ext = $request->file('file_undangan_seminar')->getClientOriginalExtension() ?: 'pdf';
                    $suffix = time() . '_' . Str::lower(Str::random(6));
                    $fileUndangan = $request->file('file_undangan_seminar')->storeAs('seminar/undangan', "Undangan_Seminar_{$nim}_{$suffix}.{$ext}", 'local');
                    $createdFiles[] = $fileUndangan;
                    $data['file_undangan_seminar'] = $fileUndangan;
                } elseif (!empty($validated['nomor_undangan_seminar'])) {
                    // Otomatis buat surat undangan seminar jika nomor undangan diisi dan file tidak diunggah manual
                    $lockedSeminar->fill($data);
                    $fileUndangan = $suratService->generateUndanganSeminar(
                        $lockedSeminar,
                        $validated['nomor_undangan_seminar'],
                        withSignature: $user->isAdminUtama()
                    );
                    $createdFiles[] = $fileUndangan;
                    $data['file_undangan_seminar'] = $fileUndangan;
                }

                $fileSk = $lockedSeminar->file_sk_seminar;
                if ($request->hasFile('file_sk_seminar')) {
                    $ext = $request->file('file_sk_seminar')->getClientOriginalExtension() ?: 'pdf';
                    $suffix = time() . '_' . Str::lower(Str::random(6));
                    $fileSk = $request->file('file_sk_seminar')->storeAs('seminar/sk', "SK_Seminar_{$nim}_{$suffix}.{$ext}", 'local');
                    $createdFiles[] = $fileSk;
                    $data['file_sk_seminar'] = $fileSk;
                }

                $originalNomorSk = $lockedSeminar->getOriginal('nomor_sk_seminar');
                $lockedSeminar->update($data);

                // Aturan 4: Catat Undangan Seminar ke tabel surat
                if (!empty($validated['nomor_undangan_seminar'])) {
                    $versiUndangan = Surat::where('seminar_skripsi_id', $lockedSeminar->id)
                        ->where('jenis_surat', 'undangan_seminar')
                        ->lockForUpdate()
                        ->count() + 1;
                    Surat::where('seminar_skripsi_id', $lockedSeminar->id)
                        ->where('jenis_surat', 'undangan_seminar')
                        ->where('status', 'aktif')
                        ->update(['status' => 'diperbarui']);

                    Surat::create([
                        'nomor_surat' => $validated['nomor_undangan_seminar'],
                        'jenis_surat' => 'undangan_seminar',
                        'nama_surat' => $versiUndangan > 1 ? "Surat Undangan Seminar (Pembaruan ke-{$versiUndangan}) - {$mhs->name}" : "Surat Undangan Seminar - {$mhs->name}",
                        'pengajuan_skripsi_id' => $lockedSeminar->pengajuan_skripsi_id,
                        'seminar_skripsi_id' => $lockedSeminar->id,
                        'program_studi_id' => $lockedSeminar->pengajuanSkripsi->program_studi_id,
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
                    $existingSkCount = Surat::where('seminar_skripsi_id', $lockedSeminar->id)
                        ->where('jenis_surat', 'sk_seminar')
                        ->lockForUpdate()
                        ->count();
                    $isUpdateSk = !empty($originalNomorSk) || $existingSkCount > 0;
                    $versiSk = $existingSkCount + 1;

                    if ($isUpdateSk) {
                        $namaSk = "SK Penguji Seminar (Pembaruan ke-{$versiSk}) - {$mhs->name}";
                        Surat::where('seminar_skripsi_id', $lockedSeminar->id)
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
                        'pengajuan_skripsi_id' => $lockedSeminar->pengajuan_skripsi_id,
                        'seminar_skripsi_id' => $lockedSeminar->id,
                        'program_studi_id' => $lockedSeminar->pengajuanSkripsi->program_studi_id,
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
                $skripsi = $lockedSeminar->pengajuanSkripsi;
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
                if ($lockedSeminar->penguji_seminar_id) {
                    Notifikasi::kirim(
                        $lockedSeminar->penguji_seminar_id,
                        'Undangan & Jadwal Ujian Seminar Mahasiswa',
                        "Jadwal ujian seminar untuk mahasiswa {$mhs->name} ({$nim}) telah ditetapkan: {$validated['tgl_seminar']} ({$validated['jam_seminar']}) di {$validated['ruangan']}. Surat undangan seminar telah diterbitkan.",
                        route('dosen.bimbingan.index')
                    );
                }

                // Notifikasi ke Pengelola (Admin Utama & Kaprodi)
                Notifikasi::kirimKePengelola(
                    $lockedSeminar->pengajuanSkripsi->program_studi_id,
                    'Jadwal Seminar Skripsi Ditetapkan',
                    "Jadwal seminar untuk mahasiswa {$mhs->name} ({$nim}) telah ditetapkan pada {$validated['tgl_seminar']} ({$validated['jam_seminar']}) di {$validated['ruangan']}.",
                    null,
                    $user->id,
                    [UserRole::AdminUtama, UserRole::Kaprodi]
                );

                return ['success' => 'Jadwal dan dokumen surat/SK Seminar berhasil diperbarui. Undangan telah dikirimkan ke tim seminar (mahasiswa, penguji, dan pembimbing).'];
            });
        } catch (\Throwable $e) {
            Storage::disk('local')->delete($createdFiles);
            throw $e;
        }

        if (isset($result['error'])) {
            Storage::disk('local')->delete($createdFiles);

            return back()->with('error', $result['error']);
        }

        JadwalPublikService::clearCache();

        return back()->with('success', $result['success']);
    }

    public function selesaikanSeminar(Request $request, SeminarSkripsi $seminar): RedirectResponse
    {
        $user = $request->user();
        $seminar->loadMissing('pengajuanSkripsi');
        $this->authorizeAdminAccess($user, (int) $seminar->pengajuanSkripsi->program_studi_id);

        $validated = $request->validate([
            'nilai_seminar' => ['required', 'numeric', 'min:0', 'max:100'],
            'hasil_evaluasi' => ['nullable', new Enum(HasilEvaluasiAkademik::class)],
            'nilai_pembimbing_1' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'nilai_pembimbing_2' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'nilai_penguji' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'catatan' => ['nullable', 'string', 'max:1000'],
            'catatan_revisi' => ['nullable', 'string', 'max:2000'],
            'tenggat_revisi' => ['nullable', 'date'],
        ]);

        $result = DB::transaction(function () use ($user, $seminar, $validated): array {
            $lockedSeminar = SeminarSkripsi::with('pengajuanSkripsi.mahasiswa')->whereKey($seminar->id)->lockForUpdate()->firstOrFail();

            // Aturan 5: Selain admin utama, role lain tidak boleh merubah nilai jika sudah ditentukan
            if ($lockedSeminar->nilai_seminar !== null && !$user->isAdminUtama()) {
                return ['error' => 'Nilai seminar telah ditentukan dan terkunci. Hanya Admin Utama yang berwenang mengubah nilai.'];
            }

            $isUpdateNilai = $lockedSeminar->nilai_seminar !== null;
            $hasilEval = isset($validated['hasil_evaluasi'])
                ? HasilEvaluasiAkademik::from($validated['hasil_evaluasi'])
                : HasilEvaluasiAkademik::Lulus;

            $rincianNilai = array_filter([
                'pembimbing_1' => $validated['nilai_pembimbing_1'] ?? null,
                'pembimbing_2' => $validated['nilai_pembimbing_2'] ?? null,
                'penguji' => $validated['nilai_penguji'] ?? null,
            ], fn ($v) => $v !== null);

            $statusBaru = $hasilEval === HasilEvaluasiAkademik::Mengulang
                ? StatusPengajuan::Diproses
                : StatusPengajuan::Selesai;

            $lockedSeminar->update([
                'nilai_seminar' => $validated['nilai_seminar'],
                'hasil_evaluasi' => $hasilEval,
                'rincian_nilai' => ! empty($rincianNilai) ? $rincianNilai : $lockedSeminar->rincian_nilai,
                'catatan' => $validated['catatan'] ?? null,
                'catatan_revisi' => $validated['catatan_revisi'] ?? null,
                'tenggat_revisi' => $validated['tenggat_revisi'] ?? null,
                'bebas_revisi_at' => $hasilEval === HasilEvaluasiAkademik::Lulus ? now() : null,
                'bebas_revisi_oleh' => $hasilEval === HasilEvaluasiAkademik::Lulus ? $user->id : null,
                'status' => $statusBaru,
            ]);

            $mhs = $lockedSeminar->pengajuanSkripsi->mahasiswa;
            $nim = $mhs->nomor_induk;
            $actorRole = $user->isAdminUtama() ? 'Admin Utama' : 'Admin';

            AktivitasLog::catat(
                $user,
                $isUpdateNilai ? 'Pembaruan Nilai Seminar' : 'Finalisasi Nilai Seminar',
                "{$actorRole} {$user->name} " . ($isUpdateNilai ? 'memperbarui' : 'menginput') . " nilai seminar ({$validated['nilai_seminar']} • {$hasilEval->label()}) untuk mahasiswa {$mhs->name}"
            );

            // Notifikasi ke Mahasiswa
            $pesanMhs = $hasilEval === HasilEvaluasiAkademik::Mengulang
                ? "Nilai seminar Anda: {$validated['nilai_seminar']} ({$hasilEval->label()}). Silakan berkonsultasi dengan pembimbing untuk penjadwalan ulang."
                : "Nilai seminar Anda: {$validated['nilai_seminar']} ({$hasilEval->label()}). " . ($hasilEval === HasilEvaluasiAkademik::LulusRevisi ? 'Harap selesaikan revisi sebelum tenggat yang ditentukan.' : 'Anda dapat melanjutkan pendaftaran Sidang Skripsi.');

            Notifikasi::kirim(
                $mhs->id,
                $isUpdateNilai ? 'Pembaruan Nilai Seminar' : 'Hasil & Nilai Seminar Telah Keluar',
                $pesanMhs,
                route('mahasiswa.seminar.index')
            );

            // Notifikasi ke Pengelola (Admin Utama & Kaprodi)
            Notifikasi::kirimKePengelola(
                $lockedSeminar->pengajuanSkripsi->program_studi_id,
                'Hasil & Nilai Seminar Mahasiswa',
                "Seminar mahasiswa {$mhs->name} ({$nim}) dinilai dengan skor {$validated['nilai_seminar']} ({$hasilEval->label()}).",
                null,
                $user->id,
                [UserRole::AdminUtama, UserRole::Kaprodi]
            );

            $msg = $isUpdateNilai
                ? 'Nilai dan berita acara seminar berhasil diperbarui oleh Admin Utama.'
                : 'Nilai dan berita acara seminar berhasil disimpan.';

            return ['success' => $msg];
        });

        if (isset($result['error'])) {
            return back()->with('error', $result['error']);
        }

        JadwalPublikService::clearCache();

        return back()->with('success', $result['success']);
    }

    public function updateJadwalDanSkSidang(
        Request $request,
        SidangSkripsi $sidang,
        SuratUndanganService $suratService,
        JadwalBentrokService $bentrokService
    ): RedirectResponse {
        $user = $request->user();
        $sidang->loadMissing('pengajuanSkripsi');
        $this->authorizeAdminAccess($user, (int) $sidang->pengajuanSkripsi->program_studi_id);

        $validated = $request->validate([
            'tgl_sidang' => ['required', 'date'],
            'jam_sidang' => ['required', 'string'],
            'ruangan' => ['required', 'string', 'max:100'],
            'nomor_undangan_sidang' => ['nullable', 'string', 'max:100'],
            'file_undangan_sidang' => ['nullable', 'file', 'mimes:pdf', 'max:5120'],
            'nomor_sk_sidang' => ['nullable', 'string', 'max:100'],
            'file_sk_sidang' => ['nullable', 'file', 'mimes:pdf', 'max:5120'],
        ]);

        $createdFiles = [];

        try {
            $result = DB::transaction(function () use ($request, $user, $sidang, $validated, $suratService, $bentrokService, &$createdFiles): array {
                $lockedSidang = SidangSkripsi::with([
                    'pengajuanSkripsi.mahasiswa',
                    'pengajuanSkripsi.programStudi',
                    'pengajuanSkripsi.pembimbing1',
                    'pengajuanSkripsi.pembimbing2',
                    'penguji1',
                    'penguji2',
                ])->whereKey($sidang->id)->lockForUpdate()->firstOrFail();

                // Cek konfirmasi dewan penguji sidang (Admin Utama berhak bypass/override)
                if (!$user->isAdminUtama() && !$lockedSidang->isDewanPengujiConfirmed()) {
                    return ['error' => 'Jadwal dan dokumen sidang belum dapat diterbitkan karena penugasan dewan penguji sidang masih menunggu konfirmasi kesediaan atau belum disetujui.'];
                }

                $conflict = $bentrokService->detectConflict(
                    $validated['tgl_sidang'],
                    $validated['jam_sidang'],
                    $validated['ruangan'],
                    [
                        $lockedSidang->pengajuanSkripsi->pembimbing_1_id,
                        $lockedSidang->pengajuanSkripsi->pembimbing_2_id,
                        $lockedSidang->penguji_1_id,
                        $lockedSidang->penguji_2_id,
                    ],
                    ignoreSidangId: $lockedSidang->id
                );
                if ($conflict !== null) {
                    return ['error' => $conflict];
                }

                $mhs = $lockedSidang->pengajuanSkripsi->mahasiswa;
                $nim = $mhs->nomor_induk;

                $data = [
                    'tgl_sidang' => $validated['tgl_sidang'],
                    'jam_sidang' => $validated['jam_sidang'],
                    'ruangan' => $validated['ruangan'],
                    'nomor_undangan_sidang' => $validated['nomor_undangan_sidang'] ?? $lockedSidang->nomor_undangan_sidang,
                    'nomor_sk_sidang' => $validated['nomor_sk_sidang'] ?? $lockedSidang->nomor_sk_sidang,
                    'status' => StatusPengajuan::Diproses,
                ];

                $fileUndangan = $lockedSidang->file_undangan_sidang;
                if ($request->hasFile('file_undangan_sidang')) {
                    $ext = $request->file('file_undangan_sidang')->getClientOriginalExtension() ?: 'pdf';
                    $suffix = time() . '_' . Str::lower(Str::random(6));
                    $fileUndangan = $request->file('file_undangan_sidang')->storeAs('sidang/undangan', "Undangan_Sidang_{$nim}_{$suffix}.{$ext}", 'local');
                    $createdFiles[] = $fileUndangan;
                    $data['file_undangan_sidang'] = $fileUndangan;
                } elseif (!empty($validated['nomor_undangan_sidang'])) {
                    // Otomatis buat surat undangan sidang jika nomor undangan diisi dan file tidak diunggah manual
                    $lockedSidang->fill($data);
                    $fileUndangan = $suratService->generateUndanganSidang(
                        $lockedSidang,
                        $validated['nomor_undangan_sidang'],
                        withSignature: $user->isAdminUtama()
                    );
                    $createdFiles[] = $fileUndangan;
                    $data['file_undangan_sidang'] = $fileUndangan;
                }

                $fileSk = $lockedSidang->file_sk_sidang;
                if ($request->hasFile('file_sk_sidang')) {
                    $ext = $request->file('file_sk_sidang')->getClientOriginalExtension() ?: 'pdf';
                    $suffix = time() . '_' . Str::lower(Str::random(6));
                    $fileSk = $request->file('file_sk_sidang')->storeAs('sidang/sk', "SK_Sidang_{$nim}_{$suffix}.{$ext}", 'local');
                    $createdFiles[] = $fileSk;
                    $data['file_sk_sidang'] = $fileSk;
                }

                $originalNomorSk = $lockedSidang->getOriginal('nomor_sk_sidang');
                $lockedSidang->update($data);

                // Aturan 4: Catat Undangan Sidang ke tabel surat
                if (!empty($validated['nomor_undangan_sidang'])) {
                    $versiUndangan = Surat::where('sidang_skripsi_id', $lockedSidang->id)
                        ->where('jenis_surat', 'undangan_sidang')
                        ->lockForUpdate()
                        ->count() + 1;
                    Surat::where('sidang_skripsi_id', $lockedSidang->id)
                        ->where('jenis_surat', 'undangan_sidang')
                        ->where('status', 'aktif')
                        ->update(['status' => 'diperbarui']);

                    Surat::create([
                        'nomor_surat' => $validated['nomor_undangan_sidang'],
                        'jenis_surat' => 'undangan_sidang',
                        'nama_surat' => $versiUndangan > 1 ? "Surat Undangan Sidang (Pembaruan ke-{$versiUndangan}) - {$mhs->name}" : "Surat Undangan Sidang - {$mhs->name}",
                        'pengajuan_skripsi_id' => $lockedSidang->pengajuan_skripsi_id,
                        'sidang_skripsi_id' => $lockedSidang->id,
                        'program_studi_id' => $lockedSidang->pengajuanSkripsi->program_studi_id,
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
                    $existingSkCount = Surat::where('sidang_skripsi_id', $lockedSidang->id)
                        ->where('jenis_surat', 'sk_sidang')
                        ->lockForUpdate()
                        ->count();
                    $isUpdateSk = !empty($originalNomorSk) || $existingSkCount > 0;
                    $versiSk = $existingSkCount + 1;

                    if ($isUpdateSk) {
                        $namaSk = "SK Dewan Penguji Sidang (Pembaruan ke-{$versiSk}) - {$mhs->name}";
                        Surat::where('sidang_skripsi_id', $lockedSidang->id)
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
                        'pengajuan_skripsi_id' => $lockedSidang->pengajuan_skripsi_id,
                        'sidang_skripsi_id' => $lockedSidang->id,
                        'program_studi_id' => $lockedSidang->pengajuanSkripsi->program_studi_id,
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
                    "Jadwal sidang skripsi Anda telah ditetapkan: {$validated['tgl_sidang']} ({$validated['jam_sidang']}) di {$validated['ruangan']}. Silakan unduh surat undangan sidang.",
                    route('mahasiswa.sidang.index')
                );

                // Notifikasi ke Penguji 1 & 2
                if ($lockedSidang->penguji_1_id) {
                    Notifikasi::kirim(
                        $lockedSidang->penguji_1_id,
                        'Jadwal Sidang Skripsi Mahasiswa Ditetapkan',
                        "Jadwal sidang skripsi untuk mahasiswa {$mhs->name} ({$nim}) telah ditetapkan: {$validated['tgl_sidang']} ({$validated['jam_sidang']}) di {$validated['ruangan']}.",
                        route('dosen.bimbingan.index')
                    );
                }

                if ($lockedSidang->penguji_2_id) {
                    Notifikasi::kirim(
                        $lockedSidang->penguji_2_id,
                        'Jadwal Sidang Skripsi Mahasiswa Ditetapkan',
                        "Jadwal sidang skripsi untuk mahasiswa {$mhs->name} ({$nim}) telah ditetapkan: {$validated['tgl_sidang']} ({$validated['jam_sidang']}) di {$validated['ruangan']}.",
                        route('dosen.bimbingan.index')
                    );
                }

                // Aturan 3: Pembimbing skripsi 1 dan 2 juga mendapatkan undangan sidang skripsi!
                $skripsi = $lockedSidang->pengajuanSkripsi;
                if ($skripsi->pembimbing_1_id) {
                    Notifikasi::kirim(
                        $skripsi->pembimbing_1_id,
                        'Undangan Sidang Skripsi Mahasiswa Bimbingan',
                        "Mahasiswa bimbingan Anda {$mhs->name} ({$nim}) telah dijadwalkan sidang skripsi pada {$validated['tgl_sidang']} ({$validated['jam_sidang']}) di {$validated['ruangan']}. Surat undangan sidang telah diterbitkan.",
                        route('dosen.bimbingan.index')
                    );
                }

                if ($skripsi->pembimbing_2_id) {
                    Notifikasi::kirim(
                        $skripsi->pembimbing_2_id,
                        'Undangan Sidang Skripsi Mahasiswa Bimbingan',
                        "Mahasiswa bimbingan Anda {$mhs->name} ({$nim}) telah dijadwalkan sidang skripsi pada {$validated['tgl_sidang']} ({$validated['jam_sidang']}) di {$validated['ruangan']}. Surat undangan sidang telah diterbitkan.",
                        route('dosen.bimbingan.index')
                    );
                }

                // Notifikasi ke Pengelola (Admin Utama & Kaprodi)
                Notifikasi::kirimKePengelola(
                    $lockedSidang->pengajuanSkripsi->program_studi_id,
                    'Jadwal Sidang Skripsi Ditetapkan',
                    "Jadwal sidang skripsi untuk mahasiswa {$mhs->name} ({$nim}) telah ditetapkan pada {$validated['tgl_sidang']} ({$validated['jam_sidang']}) di {$validated['ruangan']}.",
                    null,
                    $user->id,
                    [UserRole::AdminUtama, UserRole::Kaprodi]
                );

                return ['success' => 'Jadwal dan dokumen surat/SK Sidang berhasil diperbarui. Undangan telah dikirimkan ke mahasiswa, penguji, dan pembimbing.'];
            });
        } catch (\Throwable $e) {
            Storage::disk('local')->delete($createdFiles);
            throw $e;
        }

        if (isset($result['error'])) {
            Storage::disk('local')->delete($createdFiles);

            return back()->with('error', $result['error']);
        }

        JadwalPublikService::clearCache();

        return back()->with('success', $result['success']);
    }

    public function selesaikanSidang(Request $request, SidangSkripsi $sidang): RedirectResponse
    {
        $user = $request->user();
        $sidang->loadMissing('pengajuanSkripsi');
        $this->authorizeAdminAccess($user, (int) $sidang->pengajuanSkripsi->program_studi_id);

        $validated = $request->validate([
            'nilai_sidang' => ['required', 'numeric', 'min:0', 'max:100'],
            'hasil_evaluasi' => ['nullable', new Enum(HasilEvaluasiAkademik::class)],
            'predikat_yudisium' => ['nullable', 'string', 'max:64'],
            'nilai_pembimbing_1' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'nilai_pembimbing_2' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'nilai_penguji_1' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'nilai_penguji_2' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'catatan' => ['nullable', 'string', 'max:1000'],
            'catatan_revisi' => ['nullable', 'string', 'max:2000'],
            'tenggat_revisi' => ['nullable', 'date'],
        ]);

        $result = DB::transaction(function () use ($user, $sidang, $validated): array {
            $lockedSidang = SidangSkripsi::with('pengajuanSkripsi.mahasiswa')->whereKey($sidang->id)->lockForUpdate()->firstOrFail();

            // Aturan 5: Selain admin utama, role lain tidak boleh merubah nilai jika sudah ditentukan
            if ($lockedSidang->nilai_sidang !== null && !$user->isAdminUtama()) {
                return ['error' => 'Nilai sidang telah ditentukan dan terkunci. Hanya Admin Utama yang berwenang mengubah nilai.'];
            }

            $isUpdateNilai = $lockedSidang->nilai_sidang !== null;
            $hasilEval = isset($validated['hasil_evaluasi'])
                ? HasilEvaluasiAkademik::from($validated['hasil_evaluasi'])
                : HasilEvaluasiAkademik::Lulus;

            $rincianNilai = array_filter([
                'pembimbing_1' => $validated['nilai_pembimbing_1'] ?? null,
                'pembimbing_2' => $validated['nilai_pembimbing_2'] ?? null,
                'penguji_1' => $validated['nilai_penguji_1'] ?? null,
                'penguji_2' => $validated['nilai_penguji_2'] ?? null,
            ], fn ($v) => $v !== null);

            $statusBaru = $hasilEval === HasilEvaluasiAkademik::Mengulang
                ? StatusPengajuan::Diproses
                : StatusPengajuan::Selesai;

            $predikat = $validated['predikat_yudisium'] ?? match (true) {
                $hasilEval === HasilEvaluasiAkademik::Mengulang => null,
                (float) $validated['nilai_sidang'] >= 85 => 'Dengan Pujian',
                (float) $validated['nilai_sidang'] >= 75 => 'Sangat Memuaskan',
                default => 'Memuaskan',
            };

            $lockedSidang->update([
                'nilai_sidang' => $validated['nilai_sidang'],
                'hasil_evaluasi' => $hasilEval,
                'predikat_yudisium' => $predikat,
                'rincian_nilai' => ! empty($rincianNilai) ? $rincianNilai : $lockedSidang->rincian_nilai,
                'catatan' => $validated['catatan'] ?? null,
                'catatan_revisi' => $validated['catatan_revisi'] ?? null,
                'tenggat_revisi' => $validated['tenggat_revisi'] ?? null,
                'bebas_revisi_at' => $hasilEval === HasilEvaluasiAkademik::Lulus ? now() : null,
                'bebas_revisi_oleh' => $hasilEval === HasilEvaluasiAkademik::Lulus ? $user->id : null,
                'status' => $statusBaru,
            ]);

            $mhs = $lockedSidang->pengajuanSkripsi->mahasiswa;
            $nim = $mhs->nomor_induk;
            $actorRole = $user->isAdminUtama() ? 'Admin Utama' : 'Admin';

            AktivitasLog::catat(
                $user,
                $isUpdateNilai ? 'Pembaruan Nilai Akhir Sidang' : 'Finalisasi Kelulusan Sidang',
                "{$actorRole} {$user->name} " . ($isUpdateNilai ? 'memperbarui' : 'menginput') . " nilai akhir sidang ({$validated['nilai_sidang']} • {$hasilEval->label()}) untuk mahasiswa {$mhs->name}"
            );

            // Notifikasi ke Mahasiswa
            $judulNotifSidang = match (true) {
                $isUpdateNilai => 'Pembaruan Nilai Sidang Skripsi',
                $hasilEval === HasilEvaluasiAkademik::Mengulang => 'Hasil & Penjadwalan Ulang Sidang Skripsi',
                default => 'Selamat! Anda Dinyatakan LULUS Sidang Skripsi',
            };

            Notifikasi::kirim(
                $mhs->id,
                $judulNotifSidang,
                "Nilai akhir Sidang Skripsi Anda: {$validated['nilai_sidang']} ({$hasilEval->label()}" . ($predikat ? " • Predikat: {$predikat}" : '') . ').',
                route('mahasiswa.sidang.index')
            );

            // Notifikasi ke Pengelola (Admin Utama & Kaprodi)
            Notifikasi::kirimKePengelola(
                $lockedSidang->pengajuanSkripsi->program_studi_id,
                'Hasil & Nilai Akhir Sidang Skripsi',
                "Sidang Skripsi mahasiswa {$mhs->name} ({$nim}) dinilai dengan skor {$validated['nilai_sidang']} ({$hasilEval->label()}).",
                null,
                $user->id,
                [UserRole::AdminUtama, UserRole::Kaprodi]
            );

            $msg = $isUpdateNilai
                ? 'Nilai dan berita acara Sidang Skripsi berhasil diperbarui oleh Admin Utama.'
                : 'Nilai dan yudisium Sidang Skripsi berhasil disimpan.';

            return ['success' => $msg];
        });

        if (isset($result['error'])) {
            return back()->with('error', $result['error']);
        }

        JadwalPublikService::clearCache();

        return back()->with('success', $result['success']);
    }

    public function bebasRevisiSeminar(Request $request, SeminarSkripsi $seminar): RedirectResponse
    {
        $user = $request->user();
        $seminar->loadMissing('pengajuanSkripsi.mahasiswa');
        $this->authorizeAdminAccess($user, (int) $seminar->pengajuanSkripsi->program_studi_id);

        if ($seminar->bebas_revisi_at !== null) {
            return back()->with('info', 'Bebas revisi seminar sudah tercatat sebelumnya.');
        }

        $seminar->update([
            'bebas_revisi_at' => now(),
            'bebas_revisi_oleh' => $user->id,
            'hasil_evaluasi' => $seminar->hasil_evaluasi === HasilEvaluasiAkademik::LulusRevisi ? HasilEvaluasiAkademik::Lulus : $seminar->hasil_evaluasi,
        ]);

        AktivitasLog::catat(
            $user,
            'Validasi Bebas Revisi Seminar',
            "{$user->name} memvalidasi bebas revisi seminar untuk {$seminar->pengajuanSkripsi->mahasiswa->name}"
        );

        Notifikasi::kirim(
            $seminar->pengajuanSkripsi->mahasiswa_id,
            'Bebas Revisi Seminar Disahkan',
            'Revisi Seminar Skripsi Anda telah diverifikasi tuntas (Bebas Revisi Digital). Anda dapat melanjutkan tahap berikutnya.',
            route('mahasiswa.seminar.index')
        );

        return back()->with('success', 'Bebas revisi Seminar Skripsi berhasil disahkan secara digital.');
    }

    public function bebasRevisiSidang(Request $request, SidangSkripsi $sidang): RedirectResponse
    {
        $user = $request->user();
        $sidang->loadMissing('pengajuanSkripsi.mahasiswa');
        $this->authorizeAdminAccess($user, (int) $sidang->pengajuanSkripsi->program_studi_id);

        if ($sidang->bebas_revisi_at !== null) {
            return back()->with('info', 'Bebas revisi Sidang Skripsi sudah tercatat sebelumnya.');
        }

        $sidang->update([
            'bebas_revisi_at' => now(),
            'bebas_revisi_oleh' => $user->id,
            'hasil_evaluasi' => $sidang->hasil_evaluasi === HasilEvaluasiAkademik::LulusRevisi ? HasilEvaluasiAkademik::Lulus : $sidang->hasil_evaluasi,
        ]);

        AktivitasLog::catat(
            $user,
            'Validasi Bebas Revisi Sidang Skripsi',
            "{$user->name} memvalidasi bebas revisi Sidang Skripsi untuk {$sidang->pengajuanSkripsi->mahasiswa->name}"
        );

        Notifikasi::kirim(
            $sidang->pengajuanSkripsi->mahasiswa_id,
            'Bebas Revisi Sidang Skripsi Disahkan',
            'Revisi Sidang Skripsi Anda telah diverifikasi tuntas (Bebas Revisi Digital).',
            route('mahasiswa.sidang.index')
        );

        return back()->with('success', 'Bebas revisi Sidang Skripsi berhasil disahkan secara digital.');
    }

    private function authorizeAdminAccess(User $user, ?int $programStudiId = null): void
    {
        if (!$user->isAdminProdi() && !$user->isAdminUtama()) {
            abort(403, 'Akses khusus Admin Program Studi dan Admin Utama.');
        }

        if ($programStudiId !== null && $user->isAdminProdi() && (int) $user->program_studi_id !== $programStudiId) {
            abort(403, 'Anda tidak berwenang mengelola administrasi program studi lain.');
        }
    }
}
