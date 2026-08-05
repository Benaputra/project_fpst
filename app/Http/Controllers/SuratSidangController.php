<?php

namespace App\Http\Controllers;

use App\Actions\Surat\TerbitkanSuratSidang;
use App\Enums\JenisSurat;
use App\Http\Requests\Surat\TerbitkanSuratSidangRequest;
use App\Models\SidangSkripsi;
use Illuminate\Http\RedirectResponse;

class SuratSidangController extends Controller
{
    public function store(TerbitkanSuratSidangRequest $request, SidangSkripsi $sidang, TerbitkanSuratSidang $action): RedirectResponse
    {
        $action->execute($request->user(), $sidang, JenisSurat::from($request->validated('jenis_surat')));

        return back()->with('status', 'Surat sidang berhasil diterbitkan.');
    }
}
