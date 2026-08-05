<?php

namespace App\Http\Controllers;

use App\Actions\Seminar\AjukanSeminar;
use App\Actions\Seminar\JadwalkanSeminar;
use App\Actions\Seminar\VerifikasiSeminar;
use App\Enums\KeputusanVerifikasiPengajuan;
use App\Http\Requests\Seminar\AjukanSeminarRequest;
use App\Http\Requests\Seminar\JadwalkanSeminarRequest;
use App\Http\Requests\Seminar\VerifikasiSeminarRequest;
use App\Models\Seminar;
use App\Models\Skripsi;
use Illuminate\Http\RedirectResponse;

class SeminarController extends Controller
{
    public function schedule(
        JadwalkanSeminarRequest $request,
        Seminar $seminar,
        JadwalkanSeminar $action
    ): RedirectResponse {
        $action->execute(
            $request->user(),
            $seminar,
            $request->validated('penguji1_id'),
            $request->validated('penguji2_id'),
            $request->tanggal(),
            $request->validated('tempat')
        );

        return back()->with('status', 'Seminar berhasil dijadwalkan.');
    }

    public function store(
        AjukanSeminarRequest $request,
        Skripsi $skripsi,
        AjukanSeminar $action
    ): RedirectResponse {
        $action->execute($request->user(), $skripsi, $request->file('berkas_seminar'));

        return back()->with('status', 'Pengajuan seminar berhasil dikirim.');
    }

    public function verify(
        VerifikasiSeminarRequest $request,
        Seminar $seminar,
        VerifikasiSeminar $action
    ): RedirectResponse {
        $action->execute(
            $request->user(),
            $seminar,
            KeputusanVerifikasiPengajuan::from($request->validated('keputusan')),
            $request->validated('catatan_reject')
        );

        return back()->with('status', 'Pengajuan seminar berhasil diverifikasi.');
    }
}
