<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\ProgramStudi;
use App\Models\SeminarSkripsi;
use App\Models\SidangSkripsi;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\Storage;

class SuratUndanganService
{
    /**
     * Generate PDF Surat Undangan Seminar Rencana Penelitian / Proposal.
     *
     * @param SeminarSkripsi $seminar
     * @param string $nomorUndangan
     * @param bool $withSignature
     * @return string Relative storage path
     */
    public function generateUndanganSeminar(SeminarSkripsi $seminar, string $nomorUndangan, bool $withSignature = false): string
    {
        $skripsi = $seminar->pengajuanSkripsi;
        $mahasiswa = $skripsi->mahasiswa;
        $prodi = $skripsi->programStudi;
        $nim = $mahasiswa->nomor_induk;

        // Ambil data Kaprodi dinamis sesuai program studi
        $kaprodi = User::where('role', UserRole::Kaprodi)
            ->where('program_studi_id', $prodi->id)
            ->first();

        // Susun tim seminar dinamis
        $timSeminar = [];
        if ($skripsi->pembimbing1) {
            $timSeminar[] = [
                'nama' => $skripsi->pembimbing1->name,
                'peran' => 'Pembimbing Pertama',
            ];
        }
        if ($skripsi->pembimbing2) {
            $timSeminar[] = [
                'nama' => $skripsi->pembimbing2->name,
                'peran' => 'Pembimbing Kedua',
            ];
        }
        if ($seminar->penguji) {
            $timSeminar[] = [
                'nama' => $seminar->penguji->name,
                'peran' => 'Penguji Seminar',
            ];
        }

        $tanggalTerbit = $this->formatTanggalIndonesia(now());
        $hariTanggal = $seminar->tgl_seminar
            ? $this->formatHariTanggalIndonesia($seminar->tgl_seminar)
            : '-';

        $data = [
            'nomorSurat' => $nomorUndangan,
            'tanggalSurat' => $tanggalTerbit,
            'perihal' => 'Undangan Seminar Rencana Penelitian',
            'timSeminar' => $timSeminar,
            'mahasiswa' => $mahasiswa,
            'programStudi' => $prodi,
            'hariTanggal' => $hariTanggal,
            'jam' => $seminar->jam_seminar ?? '09.00 WIB – Selesai',
            'ruangan' => $seminar->ruangan ?? 'Ruang Audiovisual Fakultas Pertanian, Sains dan Teknologi',
            'skripsi' => $skripsi,
            'kaprodi' => $kaprodi,
            'logoBase64' => $this->getLogoBase64(),
            'ttdBase64' => $this->getTtdBase64($prodi, $withSignature),
        ];

        $html = view('pdf.surat-undangan-seminar', $data)->render();

        $dompdf = $this->createDompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $fileName = "Undangan_Seminar_{$nim}_" . time() . '.pdf';
        $relativePath = "seminar/undangan/{$fileName}";

        Storage::disk('local')->put($relativePath, $dompdf->output());

        return $relativePath;
    }

    /**
     * Generate PDF Surat Undangan Tim Penguji Sidang Skripsi.
     *
     * @param SidangSkripsi $sidang
     * @param string $nomorUndangan
     * @param bool $withSignature
     * @return string Relative storage path
     */
    public function generateUndanganSidang(SidangSkripsi $sidang, string $nomorUndangan, bool $withSignature = false): string
    {
        $skripsi = $sidang->pengajuanSkripsi;
        $mahasiswa = $skripsi->mahasiswa;
        $prodi = $skripsi->programStudi;
        $nim = $mahasiswa->nomor_induk;

        // Ambil data Kaprodi dinamis sesuai program studi
        $kaprodi = User::where('role', UserRole::Kaprodi)
            ->where('program_studi_id', $prodi->id)
            ->first();

        // Susun tim penguji sidang dinamis
        $timPenguji = [];
        if ($skripsi->pembimbing1) {
            $timPenguji[] = [
                'nama' => $skripsi->pembimbing1->name,
                'peran' => 'Pembimbing Pertama',
            ];
        }
        if ($skripsi->pembimbing2) {
            $timPenguji[] = [
                'nama' => $skripsi->pembimbing2->name,
                'peran' => 'Pembimbing Kedua',
            ];
        }
        if ($sidang->penguji1) {
            $timPenguji[] = [
                'nama' => $sidang->penguji1->name,
                'peran' => 'Penguji Pertama',
            ];
        }
        if ($sidang->penguji2) {
            $timPenguji[] = [
                'nama' => $sidang->penguji2->name,
                'peran' => 'Penguji Kedua',
            ];
        }

        $tanggalTerbit = $this->formatTanggalIndonesia(now());
        $hariTanggal = $sidang->tgl_sidang
            ? $this->formatHariTanggalIndonesia($sidang->tgl_sidang)
            : '-';

        $data = [
            'nomorSurat' => $nomorUndangan,
            'tanggalSurat' => $tanggalTerbit,
            'perihal' => 'Surat Undangan Sidang Skripsi',
            'timPenguji' => $timPenguji,
            'mahasiswa' => $mahasiswa,
            'programStudi' => $prodi,
            'hariTanggal' => $hariTanggal,
            'jam' => $sidang->jam_sidang ?? '11.00 WIB – Selesai',
            'ruangan' => $sidang->ruangan ?? 'Ruang Audiovisual Fakultas Pertanian, Sains dan Teknologi',
            'skripsi' => $skripsi,
            'kaprodi' => $kaprodi,
            'logoBase64' => $this->getLogoBase64(),
            'ttdBase64' => $this->getTtdBase64($prodi, $withSignature),
        ];

        $html = view('pdf.surat-undangan-sidang', $data)->render();

        $dompdf = $this->createDompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $fileName = "Undangan_Sidang_{$nim}_" . time() . '.pdf';
        $relativePath = "sidang/undangan/{$fileName}";

        Storage::disk('local')->put($relativePath, $dompdf->output());

        return $relativePath;
    }

    /**
     * Inisialisasi Dompdf dengan konfigurasi standar.
     */
    protected function createDompdf(): Dompdf
    {
        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('defaultFont', 'Times-Roman');

        return new Dompdf($options);
    }

    /**
     * Dapatkan logo UPB sebagai base64 string.
     */
    protected function getLogoBase64(): string
    {
        $logoPath = public_path('images/logo.png');
        if (file_exists($logoPath)) {
            return 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));
        }

        return '';
    }

    /**
     * Dapatkan tanda tangan digital Kaprodi sebagai base64 string jika diizinkan dan tersedia.
     */
    protected function getTtdBase64(?ProgramStudi $prodi, bool $withSignature): string
    {
        if (! $withSignature || ! $prodi || ! $prodi->file_ttd_kaprodi) {
            return '';
        }

        if (Storage::disk('public')->exists($prodi->file_ttd_kaprodi)) {
            $content = Storage::disk('public')->get($prodi->file_ttd_kaprodi);
            $mime = Storage::disk('public')->mimeType($prodi->file_ttd_kaprodi) ?: 'image/png';

            return 'data:' . $mime . ';base64,' . base64_encode($content);
        }

        $publicPath = public_path('storage/' . $prodi->file_ttd_kaprodi);
        if (file_exists($publicPath)) {
            $mime = mime_content_type($publicPath) ?: 'image/png';

            return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($publicPath));
        }

        $imgFallback = public_path('images/' . $prodi->file_ttd_kaprodi);
        if (file_exists($imgFallback)) {
            $mime = mime_content_type($imgFallback) ?: 'image/png';

            return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($imgFallback));
        }

        return '';
    }

    /**
     * Format tanggal Indonesia: "20 Agustus 2026".
     */
    public function formatTanggalIndonesia(CarbonInterface|string $date): string
    {
        $c = is_string($date) ? Carbon::parse($date) : $date;
        $bulanList = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        $b = $bulanList[$c->month] ?? $c->format('F');

        return "{$c->day} {$b} {$c->year}";
    }

    /**
     * Format hari & tanggal Indonesia: "Jumat / 21 Agustus 2026".
     */
    public function formatHariTanggalIndonesia(CarbonInterface|string $date): string
    {
        $c = is_string($date) ? Carbon::parse($date) : $date;

        $hariList = [
            0 => 'Minggu',
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
        ];

        $hari = $hariList[$c->dayOfWeek] ?? 'Senin';
        $tgl = $this->formatTanggalIndonesia($c);

        return "{$hari} / {$tgl}";
    }
}
