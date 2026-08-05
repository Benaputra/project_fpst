<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Actions\PengajuanJudul\AjukanJudul;
use App\Actions\PengajuanJudul\AjukanUlangJudul;
use App\Http\Controllers\Controller;
use App\Http\Requests\PengajuanJudul\AjukanJudulRequest;
use App\Http\Requests\PengajuanJudul\AjukanUlangJudulRequest;
use App\Models\PengajuanJudul;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PengajuanJudulController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->isMahasiswa(), 403);
        Gate::forUser($request->user())->authorize('viewAny', PengajuanJudul::class);

        $mahasiswa = $request->user()->mahasiswa()
            ->with([
                'programStudi',
                'pengajuanJudul.skripsi.kesediaanBimbingan' => fn ($query) => $query
                    ->orderBy('peran')
                    ->orderByDesc('siklus'),
                'pengajuanJudul.skripsi.kesediaanBimbingan.dosen',
                'pengajuanJudul.skripsi.kesediaanBimbingan.surat',
                'pengajuanJudul.skripsi.kesediaanBimbingan.dokumenPengajuan',
            ])
            ->first();

        return view('mahasiswa.pengajuan-judul.index', [
            'mahasiswa' => $mahasiswa,
            'pengajuan' => $mahasiswa?->pengajuanJudul,
        ]);
    }

    public function store(AjukanJudulRequest $request, AjukanJudul $action): RedirectResponse
    {
        $action->execute($request->user(), (string) $request->validated('judul'));

        return back()->with('status', 'Judul berhasil diajukan.');
    }

    public function update(
        AjukanUlangJudulRequest $request,
        PengajuanJudul $pengajuanJudul,
        AjukanUlangJudul $action
    ): RedirectResponse {
        $action->execute(
            $request->user(),
            $pengajuanJudul,
            (string) $request->validated('judul')
        );

        return back()->with('status', 'Perbaikan judul berhasil diajukan.');
    }

    public function updateMilikSaya(
        AjukanUlangJudulRequest $request,
        AjukanUlangJudul $action
    ): RedirectResponse {
        $action->execute(
            $request->user(),
            $request->pengajuanJudul(),
            (string) $request->validated('judul')
        );

        return back()->with('status', 'Perbaikan judul berhasil diajukan.');
    }
}
