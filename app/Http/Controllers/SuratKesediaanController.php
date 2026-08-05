<?php

namespace App\Http\Controllers;

use App\Actions\Surat\TerbitkanSuratKesediaan;
use App\Enums\JenisSurat;
use App\Enums\StatusSurat;
use App\Http\Requests\Surat\TerbitkanSuratKesediaanRequest;
use App\Models\KesediaanBimbingan;
use App\Models\Seminar;
use App\Models\SidangSkripsi;
use App\Models\Skripsi;
use App\Models\Surat;
use App\Services\Pdf\SuratKesediaanGabunganPdf;
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

    public function downloadGabungan(
        Request $request,
        Skripsi $skripsi,
        SuratKesediaanGabunganPdf $pdf
    ): StreamedResponse {
        Gate::forUser($request->user())->authorize('downloadSuratKesediaanGabungan', $skripsi);
        $skripsi->load([
            'mahasiswa.programStudi.ketuaProdi',
            'kesediaanBimbingan.dosen',
            'kesediaanBimbingan.surat.penandaTangan',
        ]);

        $kesediaanAktif = $skripsi->kesediaanBimbingan
            ->groupBy(fn (KesediaanBimbingan $item) => $item->peran->value)
            ->map(fn ($riwayat) => $riwayat->sortByDesc('siklus')->first())
            ->sortBy(fn (KesediaanBimbingan $item) => $item->peran->value)
            ->values();
        abort_if($kesediaanAktif->isEmpty(), 404);

        $surat = $kesediaanAktif->map(function (KesediaanBimbingan $item): Surat {
            $suratAktif = $item->surat
                ->where('jenis_surat', JenisSurat::KesediaanPembimbing)
                ->whereIn('status', [StatusSurat::Diterbitkan, StatusSurat::Terverifikasi])
                ->sortByDesc('versi')
                ->first();
            abort_unless($suratAktif instanceof Surat, 409, 'Surat kesediaan P1/P2 belum lengkap.');
            abort_unless(Storage::disk('local')->exists($suratAktif->file_path), 404);
            $content = Storage::disk('local')->get($suratAktif->file_path);
            abort_if(! hash_equals($suratAktif->file_hash, hash('sha256', $content)), 409);

            return $suratAktif;
        });
        $content = $pdf->render($surat);

        return response()->streamDownload(
            static function () use ($content): void {
                echo $content;
            },
            sprintf('surat-kesediaan-pembimbing-%s.pdf', $skripsi->nim),
            [
                'Content-Type' => 'application/pdf',
                'X-Content-Type-Options' => 'nosniff',
            ]
        );
    }
}
