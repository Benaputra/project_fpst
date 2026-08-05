<?php

namespace App\Http\Controllers;

use App\Actions\Dokumen\VerifikasiHasilKonsultasi;
use App\Http\Requests\Dokumen\VerifikasiHasilKonsultasiRequest;
use App\Models\DokumenPengajuan;
use Illuminate\Http\RedirectResponse;

class VerifikasiHasilKonsultasiController extends Controller
{
    public function store(
        VerifikasiHasilKonsultasiRequest $request,
        DokumenPengajuan $dokumen,
        VerifikasiHasilKonsultasi $action
    ): RedirectResponse {
        $action->execute(
            $request->user(),
            $dokumen,
            $request->keputusan(),
            $request->validated('catatan_verifikasi')
        );

        return back()->with('status', 'Hasil konsultasi berhasil diverifikasi.');
    }
}
