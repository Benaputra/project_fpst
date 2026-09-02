<?php

namespace App\Http\Controllers;

use App\Models\Notifikasi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotifikasiController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $notifikasiList = $user->notifikasi()->paginate(15);

        return view('notifikasi.index', compact('notifikasiList', 'user'));
    }

    public function markAsRead(Request $request, Notifikasi $notifikasi): RedirectResponse
    {
        if ($notifikasi->user_id !== $request->user()->id) {
            abort(403);
        }

        $notifikasi->tandaiDibaca();

        if ($notifikasi->link) {
            return redirect($notifikasi->link);
        }

        return back()->with('success', 'Notifikasi telah ditandai dibaca.');
    }

    public function markAllAsRead(Request $request): RedirectResponse
    {
        $request->user()->notifikasi()->where('dibaca', false)->update([
            'dibaca' => true,
            'dibaca_at' => now(),
        ]);

        return back()->with('success', 'Semua notifikasi telah ditandai dibaca.');
    }
}
