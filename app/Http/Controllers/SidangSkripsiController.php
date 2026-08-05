<?php

namespace App\Http\Controllers;

use App\Actions\Sidang\AjukanSidang;
use App\Actions\Sidang\JadwalkanSidang;
use App\Actions\Sidang\VerifikasiSidang;
use App\Enums\KeputusanVerifikasiPengajuan;
use App\Http\Requests\Sidang\AjukanSidangRequest;
use App\Http\Requests\Sidang\JadwalkanSidangRequest;
use App\Http\Requests\Sidang\VerifikasiSidangRequest;
use App\Models\SidangSkripsi;
use App\Models\Skripsi;
use Illuminate\Http\RedirectResponse;

class SidangSkripsiController extends Controller
{
    public function schedule(JadwalkanSidangRequest $request, SidangSkripsi $sidang, JadwalkanSidang $action): RedirectResponse
    {
        $action->execute($request->user(), $sidang, $request->validated('penguji1_id'), $request->validated('penguji2_id'), $request->tanggal(), $request->validated('tempat'));

        return back()->with('status', 'Sidang berhasil dijadwalkan.');
    }

    public function store(AjukanSidangRequest $request, Skripsi $skripsi, AjukanSidang $action): RedirectResponse
    {
        $action->execute($request->user(), $skripsi, $request->file('berkas_sidang'));

        return back()->with('status', 'Pengajuan sidang berhasil dikirim.');
    }

    public function verify(VerifikasiSidangRequest $request, SidangSkripsi $sidang, VerifikasiSidang $action): RedirectResponse
    {
        $action->execute($request->user(), $sidang, KeputusanVerifikasiPengajuan::from($request->validated('keputusan')), $request->validated('catatan_reject'));

        return back()->with('status', 'Pengajuan sidang berhasil diverifikasi.');
    }
}
