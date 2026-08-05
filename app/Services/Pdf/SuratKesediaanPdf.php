<?php

namespace App\Services\Pdf;

use App\Models\KesediaanBimbingan;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Support\Carbon;
use RuntimeException;

class SuratKesediaanPdf
{
    public function __construct(private readonly ViewFactory $view) {}

    public function render(
        KesediaanBimbingan $kesediaan,
        string $nomorSurat,
        Carbon $tanggalTerbit
    ): string {
        $options = new Options;
        $options->set('isRemoteEnabled', false);
        $options->set('isPhpEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml(
            $this->renderHtml($kesediaan, $nomorSurat, $tanggalTerbit),
            'UTF-8'
        );
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $pdf = $dompdf->output();

        if (! str_starts_with($pdf, '%PDF-')) {
            throw new RuntimeException('Renderer tidak menghasilkan dokumen PDF yang valid.');
        }

        return $pdf;
    }

    public function renderHtml(
        KesediaanBimbingan $kesediaan,
        string $nomorSurat,
        Carbon $tanggalTerbit
    ): string {
        $kesediaan->loadMissing([
            'dosen',
            'skripsi.mahasiswa.programStudi',
        ]);

        return $this->view->make('pdf.surat-kesediaan', [
            'kesediaan' => $kesediaan,
            'skripsi' => $kesediaan->skripsi,
            'mahasiswa' => $kesediaan->skripsi->mahasiswa,
            'dosen' => $kesediaan->dosen,
            'programStudi' => $kesediaan->skripsi->mahasiswa->programStudi,
            'nomorSurat' => $nomorSurat,
            'tanggalTerbit' => $tanggalTerbit,
        ])->render();
    }
}
