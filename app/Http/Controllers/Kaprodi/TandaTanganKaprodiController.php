<?php

namespace App\Http\Controllers\Kaprodi;

use App\Actions\ProgramStudi\SimpanTandaTanganKaprodi;
use App\Http\Controllers\Controller;
use App\Http\Requests\Kaprodi\UploadTandaTanganKaprodiRequest;
use Illuminate\Http\RedirectResponse;

class TandaTanganKaprodiController extends Controller
{
    public function store(
        UploadTandaTanganKaprodiRequest $request,
        SimpanTandaTanganKaprodi $action
    ): RedirectResponse {
        $action->execute($request->user(), $request->file('tanda_tangan'));

        return back()->with('status', 'Tanda tangan Kaprodi berhasil disimpan secara privat.');
    }
}
