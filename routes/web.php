<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HasilKonsultasiController;
use App\Http\Controllers\Kaprodi\CalonPembimbingController;
use App\Http\Controllers\Kaprodi\FinalisasiPembimbingController;
use App\Http\Controllers\Kaprodi\PengajuanJudulController as PengajuanJudulKaprodiController;
use App\Http\Controllers\Kaprodi\VerifikasiPengajuanJudulController;
use App\Http\Controllers\Mahasiswa\PengajuanJudulController;
use App\Http\Controllers\Portal\AktivitasLogController;
use App\Http\Controllers\Portal\PengajuanJudulController as PengajuanJudulPortalController;
use App\Http\Controllers\Portal\ProfileController;
use App\Http\Controllers\Portal\SeminarController as SeminarPortalController;
use App\Http\Controllers\Portal\SidangController as SidangPortalController;
use App\Http\Controllers\Portal\SkripsiController as SkripsiPortalController;
use App\Http\Controllers\Portal\SuratController as SuratPortalController;
use App\Http\Controllers\SeminarController;
use App\Http\Controllers\SidangSkripsiController;
use App\Http\Controllers\SkBimbinganController;
use App\Http\Controllers\SuratKesediaanController;
use App\Http\Controllers\SuratSeminarController;
use App\Http\Controllers\SuratSidangController;
use App\Http\Controllers\VerifikasiHasilKonsultasiController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::get('/portal/pengajuan-judul', PengajuanJudulPortalController::class)
        ->name('portal.pengajuan-judul.index');
    Route::get('/portal/skripsi', SkripsiPortalController::class)->name('portal.skripsi.index');
    Route::get('/portal/seminar', SeminarPortalController::class)->name('portal.seminar.index');
    Route::get('/portal/sidang', SidangPortalController::class)->name('portal.sidang.index');
    Route::get('/portal/surat', SuratPortalController::class)->name('portal.surat.index');
    Route::get('/portal/log-aktivitas', AktivitasLogController::class)->name('portal.aktivitas-log.index');
    Route::get('/profil', ProfileController::class)->name('portal.profile.show');
    Route::get('/kaprodi/pengajuan-judul', [PengajuanJudulKaprodiController::class, 'index'])
        ->name('kaprodi.pengajuan-judul.index');
    Route::get('/kaprodi/pengajuan-judul/{pengajuanJudul}', [PengajuanJudulKaprodiController::class, 'show'])
        ->name('kaprodi.pengajuan-judul.show');
    Route::get('/mahasiswa/pengajuan-judul', [PengajuanJudulController::class, 'index'])
        ->name('mahasiswa.pengajuan-judul.index');
    Route::put('/mahasiswa/pengajuan-judul', [PengajuanJudulController::class, 'updateMilikSaya'])
        ->name('mahasiswa.pengajuan-judul.update');
    Route::post('/pengajuan-judul', [PengajuanJudulController::class, 'store'])
        ->name('pengajuan-judul.store');
    Route::put('/pengajuan-judul/{pengajuanJudul}', [PengajuanJudulController::class, 'update'])
        ->name('pengajuan-judul.update');
    Route::post(
        '/pengajuan-judul/{pengajuanJudul}/terima',
        [VerifikasiPengajuanJudulController::class, 'terima']
    )->name('pengajuan-judul.terima');
    Route::post(
        '/pengajuan-judul/{pengajuanJudul}/tolak',
        [VerifikasiPengajuanJudulController::class, 'tolak']
    )->name('pengajuan-judul.tolak');
    Route::post(
        '/pengajuan-judul/{pengajuanJudul}/calon-pembimbing',
        [CalonPembimbingController::class, 'store']
    )->name('pengajuan-judul.calon-pembimbing.store');
    Route::post(
        '/kesediaan-bimbingan/{kesediaanBimbingan}/calon-pengganti',
        [CalonPembimbingController::class, 'replace']
    )->name('kesediaan-bimbingan.calon-pengganti.store');
    Route::post(
        '/skripsi/{skripsi}/finalisasi-pembimbing',
        [FinalisasiPembimbingController::class, 'store']
    )->name('skripsi.finalisasi-pembimbing.store');
    Route::post('/skripsi/{skripsi}/sk-bimbingan', [SkBimbinganController::class, 'store'])
        ->name('skripsi.sk-bimbingan.store');
    Route::post('/skripsi/{skripsi}/seminar', [SeminarController::class, 'store'])
        ->name('skripsi.seminar.store');
    Route::post('/seminar/{seminar}/verifikasi', [SeminarController::class, 'verify'])
        ->name('seminar.verifikasi.store');
    Route::post('/seminar/{seminar}/jadwal', [SeminarController::class, 'schedule'])
        ->name('seminar.jadwal.store');
    Route::post('/seminar/{seminar}/surat', [SuratSeminarController::class, 'store'])
        ->name('seminar.surat.store');
    Route::post('/skripsi/{skripsi}/sidang', [SidangSkripsiController::class, 'store'])
        ->name('skripsi.sidang.store');
    Route::post('/sidang/{sidang}/verifikasi', [SidangSkripsiController::class, 'verify'])
        ->name('sidang.verifikasi.store');
    Route::post('/sidang/{sidang}/jadwal', [SidangSkripsiController::class, 'schedule'])
        ->name('sidang.jadwal.store');
    Route::post('/sidang/{sidang}/surat', [SuratSidangController::class, 'store'])
        ->name('sidang.surat.store');
    Route::get(
        '/pengajuan-judul/{pengajuanJudul}/calon-pembimbing/cari',
        [CalonPembimbingController::class, 'search']
    )->name('pengajuan-judul.calon-pembimbing.search');
    Route::post(
        '/kesediaan-bimbingan/{kesediaanBimbingan}/surat',
        [SuratKesediaanController::class, 'store']
    )->name('kesediaan-bimbingan.surat.store');
    Route::get('/surat/{surat}/download', [SuratKesediaanController::class, 'download'])
        ->name('surat.download');
    Route::post(
        '/kesediaan-bimbingan/{kesediaanBimbingan}/hasil-konsultasi',
        [HasilKonsultasiController::class, 'store']
    )->name('kesediaan-bimbingan.hasil-konsultasi.store');
    Route::get(
        '/dokumen-pengajuan/{dokumen}/download',
        [HasilKonsultasiController::class, 'download']
    )->name('dokumen-pengajuan.download');
    Route::post(
        '/dokumen-pengajuan/{dokumen}/verifikasi-hasil-konsultasi',
        [VerifikasiHasilKonsultasiController::class, 'store']
    )->name('dokumen-pengajuan.verifikasi-hasil-konsultasi.store');
});
