<?php

namespace App\Http\Controllers;

use App\Actions\Surat\TerbitkanSkBimbingan;
use App\Http\Requests\Surat\TerbitkanSkBimbinganRequest;
use App\Models\Skripsi;
use Illuminate\Http\RedirectResponse;

class SkBimbinganController extends Controller
{
    public function store(
        TerbitkanSkBimbinganRequest $request,
        Skripsi $skripsi,
        TerbitkanSkBimbingan $action
    ): RedirectResponse {
        $surat = $action->execute($request->user(), $skripsi);

        return back()->with(
            'status',
            sprintf('SK bimbingan versi %d berhasil diterbitkan.', $surat->versi)
        );
    }
}
