<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Services\Portal\CakupanDataPortal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PengajuanJudulController extends Controller
{
    public function __invoke(Request $request, CakupanDataPortal $cakupan): View|RedirectResponse
    {
        $user = $request->user();

        if ($user->isMahasiswa()) {
            return redirect()->route('mahasiswa.pengajuan-judul.index');
        }

        if ($user->isKetuaProdi()) {
            return redirect()->route('kaprodi.pengajuan-judul.index');
        }

        $pengajuanJudul = $cakupan->pengajuanJudul($user)
            ->with(['mahasiswa.programStudi'])
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('portal.pengajuan-judul.index', compact('pengajuanJudul'));
    }
}
