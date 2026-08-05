<?php

namespace App\Services\Pdf;

use App\Models\KesediaanBimbingan;
use App\Models\Surat;
use App\Services\Signature\TandaTanganKaprodi;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Support\Collection;
use RuntimeException;

class SuratKesediaanGabunganPdf
{
    public function __construct(
        private readonly ViewFactory $view,
        private readonly TandaTanganKaprodi $tandaTangan
    ) {}

    /** @param Collection<int, Surat> $surat */
    public function render(Collection $surat): string
    {
        $items = $surat->map(function (Surat $item): array {
            $item->loadMissing([
                'suratable.dosen',
                'suratable.skripsi.mahasiswa.programStudi.ketuaProdi',
                'penandaTangan',
            ]);
            $kesediaan = $item->suratable;
            if (! $kesediaan instanceof KesediaanBimbingan) {
                throw new RuntimeException('Subjek surat kesediaan tidak valid.');
            }

            $programStudi = $kesediaan->skripsi->mahasiswa->programStudi;
            $dataTandaTangan = null;
            if ($item->penandaTangan !== null
                && $programStudi->ketua_prodi_id === $item->signed_by) {
                $dataTandaTangan = $this->tandaTangan->dataUri($programStudi);
            }

            return [
                'surat' => $item,
                'kesediaan' => $kesediaan,
                'skripsi' => $kesediaan->skripsi,
                'mahasiswa' => $kesediaan->skripsi->mahasiswa,
                'dosen' => $kesediaan->dosen,
                'programStudi' => $programStudi,
                'penandaTangan' => $item->penandaTangan,
                'dataTandaTangan' => $dataTandaTangan,
            ];
        });

        $options = new Options;
        $options->set('isRemoteEnabled', false);
        $options->set('isPhpEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml(
            $this->view->make('pdf.surat-kesediaan-gabungan', compact('items'))->render(),
            'UTF-8'
        );
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $pdf = $dompdf->output();

        if (! str_starts_with($pdf, '%PDF-')) {
            throw new RuntimeException('Renderer tidak menghasilkan dokumen PDF gabungan yang valid.');
        }

        return $pdf;
    }
}
