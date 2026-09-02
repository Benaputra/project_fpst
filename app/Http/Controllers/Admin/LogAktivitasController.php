<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AktivitasLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LogAktivitasController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        if (!$user->isAdminUtama()) {
            abort(403, 'Akses log aktivitas hanya diperuntukkan bagi Admin Utama.');
        }

        $search = $request->input('search');
        $aksiFilter = $request->input('aksi');

        $query = AktivitasLog::with('user');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('aksi', 'like', "%{$search}%")
                  ->orWhere('deskripsi', 'like', "%{$search}%")
                  ->orWhereHas('user', fn($uq) => $uq->where('name', 'like', "%{$search}%")->orWhere('nomor_induk', 'like', "%{$search}%"));
            });
        }

        if ($aksiFilter) {
            $query->where('aksi', $aksiFilter);
        }

        $logList = $query->latest()->paginate(20)->withQueryString();
        $daftarAksi = AktivitasLog::select('aksi')->distinct()->pluck('aksi');

        return view('admin.log_aktivitas.index', compact('logList', 'daftarAksi', 'search', 'aksiFilter', 'user'));
    }
}
