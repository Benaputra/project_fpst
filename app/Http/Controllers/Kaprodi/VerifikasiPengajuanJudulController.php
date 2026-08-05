<?php

namespace App\Http\Controllers\Kaprodi;

use App\Actions\PengajuanJudul\TerimaJudul;
use App\Actions\PengajuanJudul\TolakJudul;
use App\Http\Controllers\Controller;
use App\Http\Requests\PengajuanJudul\TolakJudulRequest;
use App\Models\PengajuanJudul;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class VerifikasiPengajuanJudulController extends Controller
{
    public function terima(
        Request $request,
        PengajuanJudul $pengajuanJudul,
        TerimaJudul $action
    ): RedirectResponse {
        Gate::forUser($request->user())->authorize('terima', $pengajuanJudul);
        $action->execute($request->user(), $pengajuanJudul);

        return back()->with('status', 'Judul berhasil diterima.');
    }

    public function tolak(
        TolakJudulRequest $request,
        PengajuanJudul $pengajuanJudul,
        TolakJudul $action
    ): RedirectResponse {
        $action->execute(
            $request->user(),
            $pengajuanJudul,
            (string) $request->validated('alasan')
        );

        return back()->with('status', 'Judul berhasil ditolak.');
    }
}
