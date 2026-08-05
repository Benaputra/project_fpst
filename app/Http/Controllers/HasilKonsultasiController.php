<?php

namespace App\Http\Controllers;

use App\Actions\Dokumen\UploadHasilKonsultasi;
use App\Enums\JenisDokumenPengajuan;
use App\Http\Requests\Dokumen\UploadHasilKonsultasiRequest;
use App\Models\DokumenPengajuan;
use App\Models\KesediaanBimbingan;
use App\Models\Seminar;
use App\Models\SidangSkripsi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class HasilKonsultasiController extends Controller
{
    public function store(
        UploadHasilKonsultasiRequest $request,
        KesediaanBimbingan $kesediaanBimbingan,
        UploadHasilKonsultasi $action
    ): RedirectResponse {
        $action->execute(
            $request->user(),
            $kesediaanBimbingan,
            $request->file('hasil_konsultasi'),
            $request->validated('catatan_mahasiswa')
        );

        return back()->with('status', 'Hasil konsultasi berhasil diunggah dan menunggu verifikasi.');
    }

    public function download(Request $request, DokumenPengajuan $dokumen): Response|StreamedResponse
    {
        Gate::forUser($request->user())->authorize('download', $dokumen);
        abort_unless(in_array($dokumen->jenis, [
            JenisDokumenPengajuan::HasilKonsultasi,
            JenisDokumenPengajuan::BerkasSeminar,
            JenisDokumenPengajuan::BerkasSidang,
        ], true), 404);
        abort_unless(Storage::disk('local')->exists($dokumen->file_path), 404);
        $content = Storage::disk('local')->get($dokumen->file_path);
        abort_if(! hash_equals($dokumen->file_hash, hash('sha256', $content)), 409);

        $extension = strtolower(pathinfo($dokumen->file_path, PATHINFO_EXTENSION));
        $contentType = match ($extension) {
            'pdf' => 'application/pdf',
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            default => abort(404),
        };
        $subjek = $dokumen->documentable;
        $nim = match (true) {
            $subjek instanceof KesediaanBimbingan => $subjek->skripsi->nim,
            $subjek instanceof Seminar => $subjek->skripsi->nim,
            $subjek instanceof SidangSkripsi => $subjek->skripsi->nim,
            default => abort(404),
        };
        $prefix = match ($dokumen->jenis) {
            JenisDokumenPengajuan::HasilKonsultasi => 'hasil-konsultasi',
            JenisDokumenPengajuan::BerkasSeminar => 'berkas-seminar',
            JenisDokumenPengajuan::BerkasSidang => 'berkas-sidang',
        };
        $namaFile = sprintf('%s-%s-v%d.%s', $prefix, $nim, $dokumen->versi, $extension);

        if ($dokumen->jenis === JenisDokumenPengajuan::HasilKonsultasi) {
            return response($content, 200, [
                'Content-Type' => $contentType,
                'Content-Disposition' => sprintf('inline; filename="%s"', $namaFile),
                'X-Content-Type-Options' => 'nosniff',
                'Content-Security-Policy' => "default-src 'none'; img-src 'self'; style-src 'unsafe-inline'",
            ]);
        }

        return Storage::disk('local')->download(
            $dokumen->file_path,
            $namaFile,
            [
                'Content-Type' => $contentType,
                'X-Content-Type-Options' => 'nosniff',
            ]
        );
    }
}
