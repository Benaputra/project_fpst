<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Dosen;
use App\Services\Portal\CakupanDataPortal;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SidangController extends Controller
{
    public function __invoke(Request $request, CakupanDataPortal $cakupan): View
    {
        $user = $request->user();
        $sidang = $cakupan->sidang($user)
            ->with(['skripsi.mahasiswa.programStudi', 'penguji1', 'penguji2', 'dokumenPengajuan', 'surat'])
            ->latest()
            ->paginate(15)
            ->withQueryString();
        $skripsiMahasiswa = $user->isMahasiswa()
            ? $cakupan->skripsi($user)->with(['seminar', 'sidangSkripsi'])->first()
            : null;
        $programStudiIds = $sidang->getCollection()
            ->pluck('skripsi.mahasiswa.program_studi_id')
            ->filter()
            ->unique();
        $dosenPerProdi = Dosen::query()
            ->whereIn('program_studi_id', $programStudiIds)
            ->orderBy('nama')
            ->get()
            ->groupBy('program_studi_id');

        return view('portal.sidang.index', compact('sidang', 'skripsiMahasiswa', 'dosenPerProdi'));
    }
}
