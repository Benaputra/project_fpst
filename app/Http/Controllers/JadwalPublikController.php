<?php

namespace App\Http\Controllers;

use App\Models\ProgramStudi;
use App\Services\JadwalPublikService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class JadwalPublikController extends Controller
{
    public function __invoke(Request $request, JadwalPublikService $jadwalService): View
    {
        $jenis = $request->query('jenis', 'all');
        $prodiId = $request->filled('prodi_id') ? (int) $request->query('prodi_id') : null;
        $periode = $request->query('periode', 'semua');
        $search = $request->query('search');

        $allEntries = collect($jadwalService->getAllScheduledEntries());
        $daftarJadwal = $jadwalService->getFilteredSchedules(
            $jenis === 'all' ? null : $jenis,
            $prodiId,
            in_array($periode, ['mendatang', 'semua'], true) ? $periode : 'semua',
            $search
        );

        $daftarProdi = ProgramStudi::orderBy('nama')->get(['id', 'nama', 'kode']);

        $summary = [
            'total' => $allEntries->count(),
            'mendatang' => $allEntries->where('is_mendatang', true)->count(),
            'seminar' => $allEntries->where('jenis', 'seminar')->count(),
            'sidang' => $allEntries->where('jenis', 'sidang')->count(),
            'pl' => $allEntries->where('jenis', 'pl')->count(),
        ];

        return view('jadwal.index', compact(
            'daftarJadwal',
            'daftarProdi',
            'summary',
            'jenis',
            'prodiId',
            'periode',
            'search'
        ));
    }
}
