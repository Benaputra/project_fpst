<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Dosen;
use App\Services\Portal\CakupanDataPortal;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SeminarController extends Controller
{
    public function __invoke(Request $request, CakupanDataPortal $cakupan): View
    {
        $user = $request->user();
        $seminar = $cakupan->seminar($user)
            ->with(['skripsi.mahasiswa.programStudi', 'penguji1', 'penguji2', 'dokumenPengajuan', 'surat'])
            ->latest()
            ->paginate(15)
            ->withQueryString();
        $skripsiMahasiswa = $user->isMahasiswa()
            ? $cakupan->skripsi($user)->with('seminar')->first()
            : null;
        $programStudiIds = $seminar->getCollection()
            ->pluck('skripsi.mahasiswa.program_studi_id')
            ->filter()
            ->unique();
        $dosenPerProdi = Dosen::query()
            ->whereIn('program_studi_id', $programStudiIds)
            ->orderBy('nama')
            ->get()
            ->groupBy('program_studi_id');

        return view('portal.seminar.index', compact('seminar', 'skripsiMahasiswa', 'dosenPerProdi'));
    }
}
