<?php

namespace App\Services\Pdf;

use App\Models\Dosen;
use App\Models\Skripsi;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Support\Carbon;
use RuntimeException;

class SkBimbinganPdf
{
    public function __construct(private readonly ViewFactory $view) {}

    public function render(
        Skripsi $skripsi,
        string $nomorSurat,
        Carbon $tanggalTerbit,
        ?Dosen $penandaTangan,
        ?string $dataTandaTangan
    ): string {
        $options = new Options;
        $options->set('isRemoteEnabled', false);
        $options->set('isPhpEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($this->renderHtml(
            $skripsi,
            $nomorSurat,
            $tanggalTerbit,
            $penandaTangan,
            $dataTandaTangan
        ), 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $pdf = $dompdf->output();

        if (! str_starts_with($pdf, '%PDF-')) {
            throw new RuntimeException('Renderer tidak menghasilkan SK bimbingan yang valid.');
        }

        return $pdf;
    }

    public function renderHtml(
        Skripsi $skripsi,
        string $nomorSurat,
        Carbon $tanggalTerbit,
        ?Dosen $penandaTangan,
        ?string $dataTandaTangan
    ): string {
        $skripsi->loadMissing([
            'mahasiswa.programStudi.ketuaProdi',
            'pembimbing1',
            'pembimbing2',
        ]);

        return $this->view->make('pdf.sk-bimbingan', [
            'skripsi' => $skripsi,
            'mahasiswa' => $skripsi->mahasiswa,
            'programStudi' => $skripsi->mahasiswa->programStudi,
            'pembimbing1' => $skripsi->pembimbing1,
            'pembimbing2' => $skripsi->pembimbing2,
            'nomorSurat' => $nomorSurat,
            'tanggalTerbit' => $tanggalTerbit,
            'penandaTangan' => $penandaTangan,
            'dataTandaTangan' => $dataTandaTangan,
        ])->render();
    }
}
