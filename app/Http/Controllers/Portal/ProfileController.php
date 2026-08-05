<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function __invoke(Request $request): View
    {
        abort_unless($request->user()->isMahasiswa(), 403);

        $mahasiswa = $request->user()->mahasiswa()
            ->with(['programStudi', 'pembimbingAkademik'])
            ->first();

        return view('portal.profile.show', compact('mahasiswa'));
    }
}
