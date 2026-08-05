<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Services\Portal\CakupanDataPortal;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SuratController extends Controller
{
    public function __invoke(Request $request, CakupanDataPortal $cakupan): View
    {
        $user = $request->user();
        abort_unless($user->isAdminUtama() || $user->isAdminProdi() || $user->isKetuaProdi(), 403);

        $surat = $cakupan->surat($user)
            ->with(['programStudi', 'verifikator'])
            ->latest('generated_at')
            ->paginate(15)
            ->withQueryString();

        return view('portal.surat.index', compact('surat'));
    }
}
