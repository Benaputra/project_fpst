<?php

use App\Http\Controllers\Admin\AdministrasiSkripsiController;
use App\Http\Controllers\Admin\LogAktivitasController;
use App\Http\Controllers\Admin\Master\AdminProdiController;
use App\Http\Controllers\Admin\Master\DosenController;
use App\Http\Controllers\Admin\Master\MahasiswaController;
use App\Http\Controllers\Admin\Master\ProgramStudiController;
use App\Http\Controllers\Admin\Master\UserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DokumenController;
use App\Http\Controllers\Dosen\DaftarBimbinganController;
use App\Http\Controllers\Dosen\PenugasanDosenController;
use App\Http\Controllers\Kaprodi\PenetapanController;
use App\Http\Controllers\Mahasiswa\PengajuanSkripsiController;
use App\Http\Controllers\Mahasiswa\SeminarSkripsiController;
use App\Http\Controllers\Mahasiswa\SidangSkripsiController;
use App\Http\Controllers\NotifikasiController;
use Illuminate\Support\Facades\Route;

// Redirect Home
Route::get('/', function () {
    return auth()->check() ? redirect()->route('dashboard') : redirect()->route('login');
})->name('home');

// Autentikasi
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});

// Area Terotentikasi
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    // Download File & SK
    Route::get('/dokumen/download/{path}', [DokumenController::class, 'download'])->name('dokumen.download');

    // Notifikasi
    Route::get('/notifikasi', [NotifikasiController::class, 'index'])->name('notifikasi.index');
    Route::post('/notifikasi/{notifikasi}/baca', [NotifikasiController::class, 'markAsRead'])->name('notifikasi.baca');
    Route::post('/notifikasi/baca-semua', [NotifikasiController::class, 'markAllAsRead'])->name('notifikasi.baca-semua');

    // Route Mahasiswa
    Route::prefix('mahasiswa')->name('mahasiswa.')->group(function () {
        // Fase 1: Judul & SK Bimbingan
        Route::get('/skripsi', [PengajuanSkripsiController::class, 'index'])->name('skripsi.index');
        Route::get('/skripsi/create', [PengajuanSkripsiController::class, 'create'])->name('skripsi.create');
        Route::post('/skripsi', [PengajuanSkripsiController::class, 'store'])->name('skripsi.store');

        // Fase 2: Seminar Proposal / Hasil
        Route::get('/seminar', [SeminarSkripsiController::class, 'index'])->name('seminar.index');
        Route::get('/seminar/create', [SeminarSkripsiController::class, 'create'])->name('seminar.create');
        Route::post('/seminar', [SeminarSkripsiController::class, 'store'])->name('seminar.store');

        // Fase 3: Sidang Skripsi
        Route::get('/sidang', [SidangSkripsiController::class, 'index'])->name('sidang.index');
        Route::get('/sidang/create', [SidangSkripsiController::class, 'create'])->name('sidang.create');
        Route::post('/sidang', [SidangSkripsiController::class, 'store'])->name('sidang.store');
    });

    // Route Kaprodi
    Route::prefix('kaprodi')->name('kaprodi.')->group(function () {
        Route::get('/penetapan', [PenetapanController::class, 'index'])->name('penetapan.index');
        Route::post('/skripsi/{skripsi}/review', [PenetapanController::class, 'updateJudul'])->name('skripsi.review');
        Route::post('/seminar/{seminar}/penguji', [PenetapanController::class, 'assignPengujiSeminar'])->name('seminar.penguji');
        Route::post('/sidang/{sidang}/penguji', [PenetapanController::class, 'assignPengujiSidang'])->name('sidang.penguji');
    });

    // Route Admin (Prodi & Utama)
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/administrasi', [AdministrasiSkripsiController::class, 'index'])->name('administrasi.index');
        Route::post('/skripsi/{skripsi}/sk-bimbingan', [AdministrasiSkripsiController::class, 'updateSkBimbingan'])->name('skripsi.sk-bimbingan');
        Route::post('/seminar/{seminar}/jadwal-sk', [AdministrasiSkripsiController::class, 'updateJadwalDanSkSeminar'])->name('seminar.jadwal-sk');
        Route::post('/seminar/{seminar}/selesai', [AdministrasiSkripsiController::class, 'selesaikanSeminar'])->name('seminar.selesai');
        Route::post('/sidang/{sidang}/jadwal-sk', [AdministrasiSkripsiController::class, 'updateJadwalDanSkSidang'])->name('sidang.jadwal-sk');
        Route::post('/sidang/{sidang}/selesai', [AdministrasiSkripsiController::class, 'selesaikanSidang'])->name('sidang.selesai');

        // Log Aktivitas Audit Trail (Khusus Admin Utama)
        Route::get('/log-aktivitas', [LogAktivitasController::class, 'index'])->name('log-aktivitas.index');

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
            Route::resource('prodi', ProgramStudiController::class)->only(['index', 'store', 'update', 'destroy']);

            // 5. Admin Prodi
            Route::resource('admin-prodi', AdminProdiController::class)->only(['index', 'store', 'update', 'destroy']);
        });
    });

    // Route Dosen
    Route::prefix('dosen')->name('dosen.')->group(function () {
        Route::get('/bimbingan', [DaftarBimbinganController::class, 'index'])->name('bimbingan.index');
        Route::post('/penugasan/{penugasan}/respon', [PenugasanDosenController::class, 'respon'])->name('penugasan.respon');
    });
});
