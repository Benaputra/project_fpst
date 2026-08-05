<?php

namespace App\Http\Controllers;

use App\Actions\Surat\TerbitkanSuratKesediaan;
use App\Http\Requests\Surat\TerbitkanSuratKesediaanRequest;
use App\Models\KesediaanBimbingan;
use App\Models\Seminar;
use App\Models\SidangSkripsi;
use App\Models\Skripsi;
use App\Models\Surat;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SuratKesediaanController extends Controller
{
    public function store(
        TerbitkanSuratKesediaanRequest $request,
        KesediaanBimbingan $kesediaanBimbingan,
        TerbitkanSuratKesediaan $action
    ): RedirectResponse {
        $surat = $action->execute($request->user(), $kesediaanBimbingan);

        return back()->with(
            'status',
            sprintf('Surat kesediaan versi %d berhasil diterbitkan.', $surat->versi)
        );
    }

    public function download(Request $request, Surat $surat): StreamedResponse
    {
        Gate::forUser($request->user())->authorize('download', $surat);
        abort_if($surat->file_path === null || $surat->file_hash === null, 404);
        abort_unless(Storage::disk('local')->exists($surat->file_path), 404);

        $hashAktual = hash('sha256', Storage::disk('local')->get($surat->file_path));
        abort_if(! hash_equals($surat->file_hash, $hashAktual), 409, 'Integritas file surat tidak valid.');

        $nim = match (true) {
            $surat->suratable instanceof KesediaanBimbingan => $surat->suratable->skripsi->nim,
            $surat->suratable instanceof Skripsi => $surat->suratable->nim,
            $surat->suratable instanceof Seminar => $surat->suratable->skripsi->nim,
            $surat->suratable instanceof SidangSkripsi => $surat->suratable->skripsi->nim,
            default => abort(404),
        };
        $prefix = match (true) {
            $surat->suratable instanceof Skripsi => 'sk-bimbingan',
            $surat->suratable instanceof Seminar => $surat->jenis_surat->value,
            $surat->suratable instanceof SidangSkripsi => $surat->jenis_surat->value,
            default => 'surat-kesediaan',
        };
        $namaFile = sprintf('%s-%s-v%d.pdf', $prefix, $nim, $surat->versi);

        return Storage::disk('local')->download(
            $surat->file_path,
            $namaFile,
            ['Content-Type' => 'application/pdf']
        );
    }
}
