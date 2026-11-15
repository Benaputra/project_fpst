<?php

use App\Http\Controllers\Admin\AdministrasiSkripsiController;
use App\Http\Controllers\Admin\LogAktivitasController;
use App\Http\Controllers\Admin\Master\AdminProdiController;
use App\Http\Controllers\Admin\Master\DosenController;
use App\Http\Controllers\Admin\Master\MahasiswaController;
use App\Http\Controllers\Admin\Master\ProgramStudiController;
use App\Http\Controllers\Admin\Master\UserController;
use App\Http\Controllers\Admin\PersuratanController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DokumenController;
use App\Http\Controllers\Dosen\DaftarBimbinganController;
use App\Http\Controllers\Dosen\PenugasanDosenController;
use App\Http\Controllers\JadwalPublikController;
use App\Http\Controllers\Kaprodi\PenetapanController;
use App\Http\Controllers\Kelola\AudiensSeminarController as KelolaAudiensSeminarController;
use App\Http\Controllers\Kelola\PraktikLapangController as KelolaPraktikLapangController;
use App\Http\Controllers\Mahasiswa\AudiensSeminarController;
use App\Http\Controllers\Mahasiswa\PengajuanSkripsiController;
use App\Http\Controllers\Mahasiswa\PengajuanSuratController;
use App\Http\Controllers\Mahasiswa\PraktikLapangController;
use App\Http\Controllers\Mahasiswa\SeminarSkripsiController;
use App\Http\Controllers\Mahasiswa\SidangSkripsiController;
use App\Http\Controllers\NotifikasiController;
use App\Http\Controllers\VerifikasiSuratController;
use Illuminate\Support\Facades\Route;

// Halaman Awal (Landing Page)
Route::view('/', 'landing')->name('home');

// Preview Landing Page
Route::view('/preview-landing', 'landing')->name('landing.preview');

// Portal Publik Tanpa Login (Jadwal Seminar & Sidang + Verifikasi QR Surat)
Route::get('/jadwal', JadwalPublikController::class)->name('jadwal.publik');
Route::get('/verifikasi-surat/{token}', VerifikasiSuratController::class)->name('surat.verifikasi');

// Autentikasi
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});

// Area Terotentikasi
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    // Download File & SK / Lihat Dokumen (dengan otorisasi kepemilikan di DokumenController)
    Route::get('/dokumen/download/{path}', [DokumenController::class, 'download'])->name('dokumen.download');
    Route::get('/dokumen/view/{path}', [DokumenController::class, 'view'])->name('dokumen.view');

    // Notifikasi
    Route::get('/notifikasi', [NotifikasiController::class, 'index'])->name('notifikasi.index');
    Route::post('/notifikasi/{notifikasi}/baca', [NotifikasiController::class, 'markAsRead'])->name('notifikasi.baca');
    Route::post('/notifikasi/baca-semua', [NotifikasiController::class, 'markAllAsRead'])->name('notifikasi.baca-semua');

    // Route Mahasiswa
    Route::middleware('role:mahasiswa')->prefix('mahasiswa')->name('mahasiswa.')->group(function () {
        // Fase 1: Judul & SK Bimbingan
        Route::get('/skripsi', [PengajuanSkripsiController::class, 'index'])->name('skripsi.index');
        Route::get('/skripsi/create', [PengajuanSkripsiController::class, 'create'])->name('skripsi.create');
        Route::get('/skripsi/cek-kemiripan', [PengajuanSkripsiController::class, 'cekKemiripan'])->name('skripsi.cek-kemiripan');
        Route::post('/skripsi', [PengajuanSkripsiController::class, 'store'])->name('skripsi.store');

        // Pendaftaran Penonton & Penyanggah Seminar (Kartu Kendali)
        Route::get('/seminar-audiens', [AudiensSeminarController::class, 'index'])->name('audiens.index');
        Route::post('/seminar-audiens/{seminar}/daftar', [AudiensSeminarController::class, 'daftar'])->name('audiens.daftar');
        Route::delete('/seminar-audiens/{audiens}', [AudiensSeminarController::class, 'batalkan'])->name('audiens.batalkan');
        Route::post('/seminar-audiens/{audiens}/presensi-mandiri', [AudiensSeminarController::class, 'presensiMandiri'])->name('audiens.presensi-mandiri');

        // Fase 2: Seminar Proposal / Hasil
        Route::get('/seminar', [SeminarSkripsiController::class, 'index'])->name('seminar.index');
        Route::get('/seminar/create', [SeminarSkripsiController::class, 'create'])->name('seminar.create');
        Route::post('/seminar', [SeminarSkripsiController::class, 'store'])->name('seminar.store');

        // Fase 3: Sidang Skripsi
        Route::get('/sidang', [SidangSkripsiController::class, 'index'])->name('sidang.index');
        Route::get('/sidang/create', [SidangSkripsiController::class, 'create'])->name('sidang.create');
        Route::post('/sidang', [SidangSkripsiController::class, 'store'])->name('sidang.store');

        // Pengajuan Surat Internal ke Fakultas
        Route::get('/surat-internal', [PengajuanSuratController::class, 'index'])->name('surat-internal.index');
        Route::get('/surat-internal/create', [PengajuanSuratController::class, 'create'])->name('surat-internal.create');
        Route::post('/surat-internal', [PengajuanSuratController::class, 'store'])->name('surat-internal.store');

        // Praktik Lapang (PL) & Katalog Tempat PL (Modul 2)
        Route::get('/praktik-lapang', [PraktikLapangController::class, 'index'])->name('pl.index');
        Route::post('/praktik-lapang', [PraktikLapangController::class, 'daftar'])->name('pl.daftar');
        Route::post('/praktik-lapang/{pl}/surat-pengantar', [PraktikLapangController::class, 'ajukanSuratPengantar'])->name('pl.surat-pengantar');
        Route::post('/praktik-lapang/{pl}/balasan', [PraktikLapangController::class, 'unggahBalasan'])->name('pl.balasan');
        Route::post('/praktik-lapang/{pl}/logbook', [PraktikLapangController::class, 'tambahLogbook'])->name('pl.logbook');
        Route::post('/praktik-lapang/{pl}/laporan', [PraktikLapangController::class, 'unggahLaporan'])->name('pl.laporan');
    });

    // Route Kaprodi (dan Admin Utama)
    Route::middleware('role:kaprodi,admin_utama')->prefix('kaprodi')->name('kaprodi.')->group(function () {
        Route::get('/penetapan', [PenetapanController::class, 'index'])->name('penetapan.index');
        Route::post('/skripsi/{skripsi}/review', [PenetapanController::class, 'updateJudul'])->name('skripsi.review');
        Route::post('/seminar/{seminar}/penguji', [PenetapanController::class, 'assignPengujiSeminar'])->name('seminar.penguji');
        Route::post('/sidang/{sidang}/penguji', [PenetapanController::class, 'assignPengujiSidang'])->name('sidang.penguji');
    });

    // Route Admin (Prodi & Utama)
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::middleware('role:admin_prodi,admin_utama')->group(function () {
            Route::get('/administrasi', [AdministrasiSkripsiController::class, 'index'])->name('administrasi.index');
            Route::post('/skripsi/{skripsi}/sk-bimbingan', [AdministrasiSkripsiController::class, 'updateSkBimbingan'])->name('skripsi.sk-bimbingan');
            Route::post('/seminar/{seminar}/jadwal-sk', [AdministrasiSkripsiController::class, 'updateJadwalDanSkSeminar'])->name('seminar.jadwal-sk');
            Route::post('/seminar/{seminar}/selesai', [AdministrasiSkripsiController::class, 'selesaikanSeminar'])->name('seminar.selesai');
            Route::post('/seminar/{seminar}/bebas-revisi', [AdministrasiSkripsiController::class, 'bebasRevisiSeminar'])->name('seminar.bebas-revisi');
            Route::post('/sidang/{sidang}/jadwal-sk', [AdministrasiSkripsiController::class, 'updateJadwalDanSkSidang'])->name('sidang.jadwal-sk');
            Route::post('/sidang/{sidang}/selesai', [AdministrasiSkripsiController::class, 'selesaikanSidang'])->name('sidang.selesai');
            Route::post('/sidang/{sidang}/bebas-revisi', [AdministrasiSkripsiController::class, 'bebasRevisiSidang'])->name('sidang.bebas-revisi');
        });

        Route::middleware('role:admin_prodi,admin_utama,tendik,kaprodi,pimpinan_fakultas')->prefix('persuratan')->name('persuratan.')->group(function () {
            Route::get('/', [PersuratanController::class, 'index'])->name('index');
            Route::post('/{pengajuanSurat}/verifikasi', [PersuratanController::class, 'verifikasi'])->name('verifikasi');
            Route::post('/{pengajuanSurat}/setujui', [PersuratanController::class, 'setujui'])->name('setujui');
            Route::post('/{pengajuanSurat}/terbitkan', [PersuratanController::class, 'terbitkan'])->name('terbitkan');
            Route::post('/{pengajuanSurat}/tolak', [PersuratanController::class, 'tolak'])->name('tolak');
        });

        // Log Aktivitas Audit Trail (Khusus Admin Utama)
        Route::middleware('admin_utama')->get('/log-aktivitas', [LogAktivitasController::class, 'index'])->name('log-aktivitas.index');

        // Master Data (Khusus Admin Utama)
        Route::middleware('admin_utama')->prefix('master')->name('master.')->group(function () {
            // 1. Data Mahasiswa (Single & Batch CSV)
            Route::get('/mahasiswa/template-csv', [MahasiswaController::class, 'downloadTemplate'])->name('mahasiswa.template-csv');
            Route::post('/mahasiswa/import-csv', [MahasiswaController::class, 'importCsv'])->name('mahasiswa.import-csv');
            Route::resource('mahasiswa', MahasiswaController::class)->only(['index', 'store', 'update', 'destroy']);

            // 2. Data Dosen & Kaprodi
            Route::resource('dosen', DosenController::class)->only(['index', 'store', 'update', 'destroy']);

            // 3. User & Pergantian Role
            Route::resource('user', UserController::class)->only(['index', 'store', 'update', 'destroy']);

            // 4. Program Studi
            Route::get('/prodi/{prodi}/ttd', [ProgramStudiController::class, 'showTtd'])->name('prodi.ttd');
            Route::resource('prodi', ProgramStudiController::class)->only(['index', 'store', 'update', 'destroy']);

            // 5. Admin Prodi
            Route::resource('admin-prodi', AdminProdiController::class)->only(['index', 'store', 'update', 'destroy']);
        });
    });

    // Route Dosen (termasuk Kaprodi yang merangkap pembimbing/penguji)
    Route::middleware('role:dosen,kaprodi')->prefix('dosen')->name('dosen.')->group(function () {
        Route::get('/bimbingan', [DaftarBimbinganController::class, 'index'])->name('bimbingan.index');
        Route::post('/penugasan/{penugasan}/respon', [PenugasanDosenController::class, 'respon'])->name('penugasan.respon');
    });

    // Validasi Daftar Hadir Penonton & Penyanggah Seminar + Pengelolaan Praktik Lapang
    Route::middleware('role:admin_utama,admin_prodi,tendik,kaprodi,dosen')->prefix('kelola')->name('kelola.')->group(function () {
        Route::get('/seminar-audiens/{seminar}', [KelolaAudiensSeminarController::class, 'show'])->name('audiens.show');
        Route::post('/seminar-audiens/{audiens}/validasi', [KelolaAudiensSeminarController::class, 'validasi'])->name('audiens.validasi');

        // Praktik Lapang (Katalog Mitra, Penetapan DPL, SK DPL, Logbook, Seminar PL & Penilaian)
        Route::get('/praktik-lapang', [KelolaPraktikLapangController::class, 'index'])->name('pl.index');
        Route::post('/praktik-lapang/lokasi', [KelolaPraktikLapangController::class, 'storeLokasi'])->name('pl.lokasi.store');
        Route::post('/praktik-lapang/lokasi/{lokasi}/verifikasi', [KelolaPraktikLapangController::class, 'verifikasiLokasi'])->name('pl.lokasi.verifikasi');
        Route::post('/praktik-lapang/{pl}/dpl', [KelolaPraktikLapangController::class, 'tetapkanDpl'])->name('pl.dpl');
        Route::post('/praktik-lapang/{pl}/sk-dpl', [KelolaPraktikLapangController::class, 'terbitkanSkDpl'])->name('pl.sk-dpl');
        Route::post('/praktik-lapang/logbook/{logbook}/verifikasi', [KelolaPraktikLapangController::class, 'verifikasiLogbook'])->name('pl.logbook.verifikasi');
        Route::post('/praktik-lapang/{pl}/seminar', [KelolaPraktikLapangController::class, 'jadwalkanSeminar'])->name('pl.seminar');
        Route::post('/praktik-lapang/{pl}/penilaian', [KelolaPraktikLapangController::class, 'simpanPenilaian'])->name('pl.penilaian');
    });
});
