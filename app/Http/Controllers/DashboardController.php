<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->isMahasiswa()) {
            return redirect()->route('mahasiswa.pengajuan-judul.index');
        }

        if ($user->isKetuaProdi()) {
            return redirect()->route('kaprodi.pengajuan-judul.index');
        }

        return redirect()->route('home');
    }
}
