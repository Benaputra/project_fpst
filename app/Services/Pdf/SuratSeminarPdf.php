<?php

namespace App\Services\Pdf;

use App\Enums\JenisSurat;
use App\Models\Dosen;
use App\Models\Seminar;
use App\Models\SidangSkripsi;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Support\Carbon;
use RuntimeException;

class SuratSeminarPdf
{
    public function __construct(private readonly ViewFactory $view) {}

    public function render(Seminar|SidangSkripsi $seminar, JenisSurat $jenis, string $nomor, Carbon $terbit, ?Dosen $signer, ?string $ttd): string
    {
        $options = new Options;
        $options->set('isRemoteEnabled', false);
        $options->set('isPhpEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($this->renderHtml($seminar, $jenis, $nomor, $terbit, $signer, $ttd), 'UTF-8');
        $dompdf->setPaper('A4');
        $dompdf->render();
        $pdf = $dompdf->output();
        if (! str_starts_with($pdf, '%PDF-')) {
            throw new RuntimeException('Renderer tidak menghasilkan surat seminar yang valid.');
        }

        return $pdf;
    }

    public function renderHtml(Seminar|SidangSkripsi $seminar, JenisSurat $jenis, string $nomor, Carbon $terbit, ?Dosen $signer, ?string $ttd): string
    {
        $seminar->loadMissing(['skripsi.mahasiswa.programStudi', 'skripsi.pembimbing1', 'skripsi.pembimbing2', 'penguji1', 'penguji2']);

        return $this->view->make('pdf.surat-seminar', [
            'proses' => $seminar,
            ...compact('jenis', 'nomor', 'terbit', 'signer', 'ttd'),
        ])->render();
    }
}
