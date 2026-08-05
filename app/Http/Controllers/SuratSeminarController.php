<?php

namespace App\Http\Controllers;

use App\Actions\Surat\TerbitkanSuratSeminar;
use App\Enums\JenisSurat;
use App\Http\Requests\Surat\TerbitkanSuratSeminarRequest;
use App\Models\Seminar;
use Illuminate\Http\RedirectResponse;

class SuratSeminarController extends Controller
{
    public function store(TerbitkanSuratSeminarRequest $request, Seminar $seminar, TerbitkanSuratSeminar $action): RedirectResponse
    {
        $action->execute($request->user(), $seminar, JenisSurat::from($request->validated('jenis_surat')));

        return back()->with('status', 'Surat seminar berhasil diterbitkan.');
    }
}
