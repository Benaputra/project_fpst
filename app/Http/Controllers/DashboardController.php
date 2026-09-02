<?php

namespace App\Http\Controllers;

use App\Enums\StatusPengajuan;
use App\Models\PengajuanSkripsi;
use App\Models\SeminarSkripsi;
use App\Models\SidangSkripsi;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        $data = [
            'user' => $user,
        ];

        if ($user->isMahasiswa()) {
            $skripsi = $user->pengajuanSkripsi()
                ->with(['programStudi', 'pembimbing1', 'pembimbing2', 'seminar.penguji', 'sidang.penguji1', 'sidang.penguji2'])
                ->first();
            $data['skripsi'] = $skripsi;
        } elseif ($user->isKaprodi()) {
            $prodiId = $user->program_studi_id;
            $data['pendingJudulCount'] = PengajuanSkripsi::where('program_studi_id', $prodiId)
                ->where('status', StatusPengajuan::Diajukan)
                ->count();
            $data['pendingSeminarPengujiCount'] = SeminarSkripsi::whereHas('pengajuanSkripsi', function ($q) use ($prodiId) {
                $q->where('program_studi_id', $prodiId);
            })->whereNull('penguji_seminar_id')->where('status', '!=', StatusPengajuan::Ditolak)->count();
            $data['pendingSidangPengujiCount'] = SidangSkripsi::whereHas('pengajuanSkripsi', function ($q) use ($prodiId) {
                $q->where('program_studi_id', $prodiId);
            })->where(function ($q) {
                $q->whereNull('penguji_1_id')->orWhereNull('penguji_2_id');
            })->where('status', '!=', StatusPengajuan::Ditolak)->count();

            $data['recentJudul'] = PengajuanSkripsi::where('program_studi_id', $prodiId)
                ->with('mahasiswa')
                ->latest()
                ->take(5)
                ->get();
        } elseif ($user->isAdmin()) {
            $prodiId = $user->program_studi_id;
            $querySkripsi = PengajuanSkripsi::query();
            $querySeminar = SeminarSkripsi::query();
            $querySidang = SidangSkripsi::query();

            if ($user->isAdminProdi() && $prodiId) {
                $querySkripsi->where('program_studi_id', $prodiId);
                $querySeminar->whereHas('pengajuanSkripsi', fn($q) => $q->where('program_studi_id', $prodiId));
                $querySidang->whereHas('pengajuanSkripsi', fn($q) => $q->where('program_studi_id', $prodiId));
            }

            $data['skripsiCount'] = (clone $querySkripsi)->count();
            $data['pendingSkBimbinganCount'] = (clone $querySkripsi)->whereNotNull('pembimbing_1_id')->whereNull('nomor_sk_bimbingan')->count();
            $data['pendingJadwalSeminarCount'] = (clone $querySeminar)->whereNull('tgl_seminar')->count();
            $data['pendingJadwalSidangCount'] = (clone $querySidang)->whereNull('tgl_sidang')->count();

            $data['recentSkripsi'] = (clone $querySkripsi)->with(['mahasiswa', 'pembimbing1', 'pembimbing2'])->latest()->take(5)->get();
        } elseif ($user->isDosen()) {
            $data['bimbingan1Count'] = PengajuanSkripsi::where('pembimbing_1_id', $user->id)->count();
            $data['bimbingan2Count'] = PengajuanSkripsi::where('pembimbing_2_id', $user->id)->count();
            $data['ujiSeminarCount'] = SeminarSkripsi::where('penguji_seminar_id', $user->id)->count();
            $data['ujiSidangCount'] = SidangSkripsi::where('penguji_1_id', $user->id)->orWhere('penguji_2_id', $user->id)->count();
            
            $data['bimbinganAktif'] = PengajuanSkripsi::where(function ($q) use ($user) {
                $q->where('pembimbing_1_id', $user->id)->orWhere('pembimbing_2_id', $user->id);
            })->with('mahasiswa')->latest()->take(5)->get();
        }

        return view('dashboard', $data);
    }
}
