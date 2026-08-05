<?php

namespace App\Http\Controllers;

use App\Enums\StatusPengajuanJudul;
use App\Enums\StatusSeminar;
use App\Enums\StatusSkripsi;
use App\Models\AktivitasLog;
use App\Models\ProgramStudi;
use App\Models\User;
use App\Services\Portal\CakupanDataPortal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, CakupanDataPortal $cakupan): View
    {
        $user = $request->user();

        if ($user->isMahasiswa()) {
            $mahasiswa = $user->mahasiswa()
                ->with(['programStudi', 'pembimbingAkademik', 'pengajuanJudul.skripsi.seminar', 'pengajuanJudul.skripsi.sidangSkripsi'])
                ->first();

            return view('portal.dashboard.mahasiswa', compact('mahasiswa'));
        }

        if ($user->isKetuaProdi()) {
            $pengajuan = $cakupan->pengajuanJudul($user);
            $skripsi = $cakupan->skripsi($user);
            $seminar = $cakupan->seminar($user);
            $programStudi = $user->dosen?->programStudiDipimpin;

            return view('portal.dashboard.kaprodi', [
                'programStudi' => $programStudi,
                'tandaTanganTersedia' => $programStudi !== null
                    && $programStudi->ttd_ketua_prodi !== null
                    && Storage::disk('local')->exists($programStudi->ttd_ketua_prodi),
                'menungguJudul' => (clone $pengajuan)->where('status', StatusPengajuanJudul::Diajukan)->count(),
                'skripsiAktif' => (clone $skripsi)->where('status', '!=', StatusSkripsi::Selesai)->count(),
                'menungguSeminar' => (clone $seminar)->where('status', StatusSeminar::Diajukan)->count(),
                'suratTerbit' => $cakupan->surat($user)->count(),
                'pengajuanTerbaru' => $pengajuan->with('mahasiswa')->latest()->limit(5)->get(),
            ]);
        }

        if ($user->isAdminProdi()) {
            $pengajuan = $cakupan->pengajuanJudul($user);
            $skripsi = $cakupan->skripsi($user);
            $seminar = $cakupan->seminar($user);

            return view('portal.dashboard.admin-prodi', [
                'programStudi' => $user->programStudiAdministrasi()->orderBy('nama')->get(),
                'totalPengajuan' => $pengajuan->count(),
                'skripsiAktif' => (clone $skripsi)->where('status', '!=', StatusSkripsi::Selesai)->count(),
                'menungguSeminar' => (clone $seminar)->where('status', StatusSeminar::Diajukan)->count(),
                'suratTerbit' => $cakupan->surat($user)->count(),
            ]);
        }

        if ($user->isAdminUtama()) {
            return view('portal.dashboard.admin-utama', [
                'totalPengguna' => User::query()->count(),
                'totalProgramStudi' => ProgramStudi::query()->count(),
                'totalSkripsi' => $cakupan->skripsi($user)->count(),
                'totalSurat' => $cakupan->surat($user)->count(),
                'aktivitasTerbaru' => AktivitasLog::query()->with('user')->latest()->limit(6)->get(),
            ]);
        }

        $dosen = $user->dosen()->with('programStudi')->first();

        return view('portal.dashboard.dosen', [
            'dosen' => $dosen,
            'skripsiBimbingan' => $cakupan->skripsi($user)->count(),
            'seminarTerjadwal' => $cakupan->seminar($user)
                ->where('status', StatusSeminar::Dijadwalkan)
                ->count(),
            'seminarTerbaru' => $cakupan->seminar($user)
                ->with('skripsi.mahasiswa')
                ->latest('tanggal')
                ->limit(5)
                ->get(),
        ]);
    }
}
