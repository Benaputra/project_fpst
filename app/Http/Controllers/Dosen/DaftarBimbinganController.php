<?php

namespace App\Http\Controllers\Dosen;

use App\Enums\StatusPenugasanDosen;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\PengajuanSkripsi;
use App\Models\PenugasanDosen;
use App\Models\SeminarSkripsi;
use App\Models\SidangSkripsi;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DaftarBimbinganController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        // Permintaan Penugasan yang butuh konfirmasi (Menunggu)
        $permintaanPenugasan = PenugasanDosen::where('dosen_id', $user->id)
            ->where('status', StatusPenugasanDosen::Menunggu)
            ->with(['assignable', 'ditugaskanOleh'])
            ->latest()
            ->get();

        // Daftar Dosen untuk rekomendasi jika dosen menolak
        $rekomendasiDosenList = User::whereIn('role', [UserRole::Dosen, UserRole::Kaprodi])
            ->where('id', '!=', $user->id)
            ->when($user->program_studi_id, fn($q) => $q->where('program_studi_id', $user->program_studi_id))
            ->orderBy('name')
            ->get();

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

        return view('dosen.bimbingan.index', compact(
            'permintaanPenugasan',
            'rekomendasiDosenList',
            'daftarBimbingan',
            'jadwalUjiSeminar',
            'jadwalUjiSidang',
            'user'
        ));
    }
}
