<?php

namespace App\Http\Controllers\Kaprodi;

use App\Actions\Skripsi\GantiCalonPembimbing;
use App\Actions\Skripsi\TetapkanCalonPembimbing;
use App\Http\Controllers\Controller;
use App\Http\Requests\Skripsi\CariCalonPembimbingRequest;
use App\Http\Requests\Skripsi\GantiCalonPembimbingRequest;
use App\Http\Requests\Skripsi\TetapkanCalonPembimbingRequest;
use App\Models\KesediaanBimbingan;
use App\Models\PengajuanJudul;
use App\Queries\Skripsi\CariCalonPembimbing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class CalonPembimbingController extends Controller
{
    public function replace(
        GantiCalonPembimbingRequest $request,
        KesediaanBimbingan $kesediaanBimbingan,
        GantiCalonPembimbing $action
    ): RedirectResponse {
        $action->execute(
            $request->user(),
            $kesediaanBimbingan,
            (string) $request->validated('calon_pengganti_id')
        );

        return back()->with(
            'status',
            'Calon pengganti ditetapkan dan surat kesediaan siklus baru diterbitkan.'
        );
    }

    public function search(
        CariCalonPembimbingRequest $request,
        PengajuanJudul $pengajuanJudul,
        CariCalonPembimbing $query
    ): JsonResponse {
        return response()->json([
            'data' => $query->execute($pengajuanJudul, $request->pencarian())
                ->map(fn ($dosen) => [
                    'id' => $dosen->nidn,
                    'nama' => $dosen->nama,
                    'nuptk' => $dosen->nuptk,
                ])
                ->values(),
        ]);
    }

    public function store(
        TetapkanCalonPembimbingRequest $request,
        PengajuanJudul $pengajuanJudul,
        TetapkanCalonPembimbing $action
    ): RedirectResponse {
        $action->execute(
            $request->user(),
            $pengajuanJudul,
            (string) $request->validated('pembimbing1_id'),
            $request->validated('pembimbing2_id')
        );

        return back()->with('status', 'Calon pembimbing berhasil ditetapkan.');
    }
}
