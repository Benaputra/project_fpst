<?php

namespace App\Http\Controllers\Kaprodi;

use App\Enums\StatusPengajuanJudul;
use App\Http\Controllers\Controller;
use App\Http\Requests\PengajuanJudul\DaftarPengajuanJudulKaprodiRequest;
use App\Models\PengajuanJudul;
use App\Queries\PengajuanJudul\DaftarPengajuanJudulKaprodi;
use App\Queries\Skripsi\CariCalonPembimbing;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PengajuanJudulController extends Controller
{
    public function index(
        DaftarPengajuanJudulKaprodiRequest $request,
        DaftarPengajuanJudulKaprodi $query
    ): View {
        return view('kaprodi.pengajuan-judul.index', [
            'pengajuanJudul' => $query->execute(
                $request->user(),
                $request->status(),
                $request->pencarian()
            ),
        ]);
    }

    public function show(
        Request $request,
        PengajuanJudul $pengajuanJudul,
        CariCalonPembimbing $query
    ): View {
        abort_unless($request->user()->isKetuaProdi(), 403);
        Gate::forUser($request->user())->authorize('view', $pengajuanJudul);

        $pengajuanJudul->loadMissing([
            'mahasiswa.programStudi',
            'mahasiswa.pembimbingAkademik',
            'verifikator',
            'skripsi.kesediaanBimbingan.dosen',
        ]);

        $pilihanLama = array_values(array_filter([
            $request->old('pembimbing1_id'),
            $request->old('pembimbing2_id'),
        ]));

        return view('kaprodi.pengajuan-judul.show', [
            'pengajuan' => $pengajuanJudul,
            'calonPembimbing' => $pengajuanJudul->status === StatusPengajuanJudul::Diverifikasi
                && $pengajuanJudul->skripsi === null
                ? $query->execute($pengajuanJudul, sertakanNidn: $pilihanLama)
                : collect(),
        ]);
    }
}
