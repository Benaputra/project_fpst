<?php

namespace App\Http\Controllers\Dosen;

use App\Http\Controllers\Controller;
use App\Models\PengajuanSkripsi;
use App\Models\SeminarSkripsi;
use App\Models\SidangSkripsi;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DaftarBimbinganController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $daftarBimbingan = PengajuanSkripsi::where(function ($q) use ($user) {
            $q->where('pembimbing_1_id', $user->id)
              ->orWhere('pembimbing_2_id', $user->id);
        })
        ->with(['mahasiswa', 'programStudi', 'seminar', 'sidang'])
        ->latest()
        ->paginate(10, ['*'], 'page_bimbingan');

        $jadwalUjiSeminar = SeminarSkripsi::where('penguji_seminar_id', $user->id)
            ->with(['pengajuanSkripsi.mahasiswa', 'pengajuanSkripsi.pembimbing1', 'pengajuanSkripsi.pembimbing2'])
            ->latest()
            ->paginate(10, ['*'], 'page_seminar');

        $jadwalUjiSidang = SidangSkripsi::where(function ($q) use ($user) {
            $q->where('penguji_1_id', $user->id)
              ->orWhere('penguji_2_id', $user->id);
        })
        ->with(['pengajuanSkripsi.mahasiswa', 'pengajuanSkripsi.pembimbing1', 'pengajuanSkripsi.pembimbing2', 'penguji1', 'penguji2'])
        ->latest()
        ->paginate(10, ['*'], 'page_sidang');

        return view('dosen.bimbingan.index', compact('daftarBimbingan', 'jadwalUjiSeminar', 'jadwalUjiSidang', 'user'));
    }
}
