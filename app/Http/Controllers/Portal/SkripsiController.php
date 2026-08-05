<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Dosen;
use App\Services\Portal\CakupanDataPortal;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SkripsiController extends Controller
{
    public function __invoke(Request $request, CakupanDataPortal $cakupan): View
    {
        $skripsi = $cakupan->skripsi($request->user())
            ->with([
                'mahasiswa.programStudi',
                'pengajuanJudul',
                'pembimbing1',
                'pembimbing2',
                'seminar',
                'sidangSkripsi',
                'surat',
                'kesediaanBimbingan.dosen',
                'kesediaanBimbingan.surat',
                'kesediaanBimbingan.dokumenPengajuan',
            ])
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $programStudiIds = $skripsi->getCollection()
            ->pluck('mahasiswa.program_studi_id')
            ->filter()
            ->unique();
        $dosenPerProdi = Dosen::query()
            ->whereIn('program_studi_id', $programStudiIds)
            ->orderBy('nama')
            ->get()
            ->groupBy('program_studi_id');

        return view('portal.skripsi.index', compact('skripsi', 'dosenPerProdi'));
    }
}
