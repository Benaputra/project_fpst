<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\AktivitasLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AktivitasLogController extends Controller
{
    public function __invoke(Request $request): View
    {
        abort_unless($request->user()->isAdminUtama(), 403);

        $aktivitas = AktivitasLog::query()
            ->with('user')
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('portal.aktivitas-log.index', compact('aktivitas'));
    }
}
