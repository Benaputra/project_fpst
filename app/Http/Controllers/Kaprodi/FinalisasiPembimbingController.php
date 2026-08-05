<?php

namespace App\Http\Controllers\Kaprodi;

use App\Actions\Skripsi\FinalisasiPembimbing;
use App\Http\Controllers\Controller;
use App\Http\Requests\Skripsi\FinalisasiPembimbingRequest;
use App\Models\Skripsi;
use Illuminate\Http\RedirectResponse;

class FinalisasiPembimbingController extends Controller
{
    public function store(
        FinalisasiPembimbingRequest $request,
        Skripsi $skripsi,
        FinalisasiPembimbing $action
    ): RedirectResponse {
        $action->execute($request->user(), $skripsi);

        return back()->with('status', 'Pembimbing resmi berhasil ditetapkan.');
    }
}
