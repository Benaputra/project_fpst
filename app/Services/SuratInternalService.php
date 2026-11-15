<?php

namespace App\Services;

use App\Enums\JenisSuratInternal;
use App\Enums\StatusPengajuanSurat;
use App\Enums\UserRole;
use App\Models\PengajuanSurat;
use App\Models\PenomoranSuratCounter;
use App\Models\ProgramStudi;
use App\Models\Surat;
use App\Models\User;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SuratInternalService
{
    public function __construct(
        private readonly QrCodeService $qrCodeService,
    ) {}

    /**
     * Hasilkan nomor surat resmi secara atomik (aman dari race condition).
     */
    public function generateNomorSurat(JenisSuratInternal $jenis, ?ProgramStudi $prodi = null, ?int $tahun = null): string
    {
        $tahun = $tahun ?? (int) now()->year;
        $prodiKode = $prodi?->kode ?: 'FPST';
        $kodeUnit = $jenis->kodeKlasifikasi() . '/' . $prodiKode;

        $counter = PenomoranSuratCounter::query()
            ->where('kode_unit', $kodeUnit)
            ->where('tahun', $tahun)
            ->lockForUpdate()
            ->first();

        if (! $counter) {
            PenomoranSuratCounter::firstOrCreate(
                ['kode_unit' => $kodeUnit, 'tahun' => $tahun],
                ['last_number' => 0]
            );

            $counter = PenomoranSuratCounter::query()
                ->where('kode_unit', $kodeUnit)
                ->where('tahun', $tahun)
                ->lockForUpdate()
                ->firstOrFail();
        }

        $nextNumber = $counter->last_number + 1;
        $counter->update(['last_number' => $nextNumber]);

        $urutan = str_pad((string) $nextNumber, 3, '0', STR_PAD_LEFT);
        $bulanRomawi = $this->bulanRomawi((int) now()->month);

        return "{$urutan}/{$kodeUnit}/FPST-UPB/{$bulanRomawi}/{$tahun}";
    }

    /**
     * Terbitkan PDF surat resmi ber-KOP FPST dengan QR Code verifikasi, simpan di disk privat,
     * dan catat ke tabel `surat`.
     *
     * @return array{surat: Surat, path: string}
     */
    public function terbitkanSurat(
        PengajuanSurat $pengajuan,
        User $penerbit,
        ?User $penandaTangan = null,
        ?string $nomorManual = null
    ): array {
        $pengajuan->loadMissing(['mahasiswa', 'programStudi', 'penyetuju']);
        $mahasiswa = $pengajuan->mahasiswa;
        $prodi = $pengajuan->programStudi;

        $resolvedSigner = $penandaTangan ?? $this->resolvePenandaTangan($pengajuan);
        $jabatanPenandaTangan = $resolvedSigner?->jabatan ?: $this->resolveJabatanPenandaTangan($pengajuan, $resolvedSigner);

        $nomorSurat = $nomorManual && trim($nomorManual) !== ''
            ? trim($nomorManual)
            : $this->generateNomorSurat($pengajuan->jenis_surat, $prodi);

        $qrEnabled = (bool) config('fpst.surat_internal.qr_verifikasi_enabled', false);
        $token = Str::random(40);
        $verificationUrl = route('surat.verifikasi', ['token' => $token]);
        $verificationHash = hash_hmac('sha256', implode('|', [
            $nomorSurat,
            $pengajuan->jenis_surat->value,
            (string) $mahasiswa->nomor_induk,
            $token,
        ]), (string) config('app.key'));

        $qrDataUri = $qrEnabled ? $this->qrCodeService->makeSvgDataUri($verificationUrl) : '';
        $tanggalSurat = $this->formatTanggalIndonesia(now());

        $html = view('pdf.surat-internal', [
            'pengajuan' => $pengajuan,
            'mahasiswa' => $mahasiswa,
            'programStudi' => $prodi,
            'nomorSurat' => $nomorSurat,
            'tanggalSurat' => $tanggalSurat,
            'penandaTangan' => $resolvedSigner,
            'jabatanPenandaTangan' => $jabatanPenandaTangan,
            'verificationUrl' => $verificationUrl,
            'verificationToken' => $token,
            'qrEnabled' => $qrEnabled,
            'qrDataUri' => $qrDataUri,
            'logoBase64' => $this->getLogoBase64(),
            'ttdBase64' => $pengajuan->jenis_surat === JenisSuratInternal::IzinPenelitian
                ? $this->getProdiTtdBase64($prodi)
                : '',
        ])->render();

        $dompdf = $this->createDompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $nimSafe = preg_replace('/[^A-Za-z0-9_-]/', '_', (string) ($mahasiswa->nomor_induk ?: $mahasiswa->id));
        $fileName = 'Surat_' . strtoupper($pengajuan->jenis_surat->kodeKlasifikasi()) . "_{$nimSafe}_" . now()->format('YmdHis') . '_' . Str::random(6) . '.pdf';
        $relativePath = "surat_internal/terbit/{$fileName}";

        Storage::disk('local')->put($relativePath, $dompdf->output());

        $latestVersion = Surat::where('pengajuan_surat_id', $pengajuan->id)->max('versi') ?? 0;
        if ($latestVersion > 0) {
            Surat::where('pengajuan_surat_id', $pengajuan->id)
                ->where('status', 'aktif')
                ->update(['status' => 'diperbarui']);
        }

        $surat = Surat::create([
            'nomor_surat' => $nomorSurat,
            'jenis_surat' => $pengajuan->jenis_surat->value,
            'nama_surat' => "{$pengajuan->jenis_surat->label()} - {$mahasiswa->name}",
            'pengajuan_surat_id' => $pengajuan->id,
            'program_studi_id' => $prodi->id,
            'tgl_surat' => now(),
            'file_surat' => $relativePath,
            'versi' => $latestVersion + 1,
            'status' => 'aktif',
            'verification_token' => $token,
            'verification_hash' => $verificationHash,
            'diterbitkan_oleh' => $penerbit->id,
            'penanda_tangan_id' => $resolvedSigner?->id,
            'jabatan_penanda_tangan' => $jabatanPenandaTangan,
            'keterangan' => "Penerbitan {$pengajuan->jenis_surat->label()} untuk {$pengajuan->tujuan_instansi}",
        ]);

        $pengajuan->update([
            'status' => StatusPengajuanSurat::Diterbitkan,
            'diterbitkan_oleh' => $penerbit->id,
            'diterbitkan_pada' => now(),
            'nomor_surat_terbit' => $nomorSurat,
            'file_surat_terbit' => $relativePath,
            'verification_token' => $token,
        ]);

        return [
            'surat' => $surat,
            'path' => $relativePath,
        ];
    }

    private function resolvePenandaTangan(PengajuanSurat $pengajuan): ?User
    {
        if ($pengajuan->penyetuju) {
            return $pengajuan->penyetuju;
        }

        if ($pengajuan->jenis_surat === JenisSuratInternal::IzinPenelitian) {
            $kaprodi = User::query()
                ->whereHasRoles([UserRole::Kaprodi])
                ->where('program_studi_id', $pengajuan->program_studi_id)
                ->first();

            if ($kaprodi) {
                return $kaprodi;
            }
        }

        return User::query()->whereHasRoles([UserRole::PimpinanFakultas])->first()
            ?? User::query()->whereHasRoles([UserRole::Kaprodi])->where('program_studi_id', $pengajuan->program_studi_id)->first();
    }

    private function resolveJabatanPenandaTangan(PengajuanSurat $pengajuan, ?User $signer): string
    {
        if ($pengajuan->jenis_surat === JenisSuratInternal::IzinPenelitian || ($signer && $signer->isKaprodi() && ! $signer->isPimpinanFakultas())) {
            return 'Ketua Program Studi ' . ($pengajuan->programStudi?->nama ?? 'FPST');
        }

        return 'Dekan / Pimpinan Fakultas Pertanian, Sains dan Teknologi';
    }

    private function createDompdf(): Dompdf
    {
        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('defaultFont', 'Times-Roman');

        return new Dompdf($options);
    }

    private function getLogoBase64(): string
    {
        $logoPath = public_path('images/logo.png');
        if (file_exists($logoPath)) {
            return 'data:image/png;base64,' . base64_encode((string) file_get_contents($logoPath));
        }

        return '';
    }

    private function getProdiTtdBase64(?ProgramStudi $prodi): string
    {
        if (! $prodi || ! $prodi->file_ttd_kaprodi) {
            return '';
        }

        if (Storage::disk('local')->exists($prodi->file_ttd_kaprodi)) {
            $content = Storage::disk('local')->get($prodi->file_ttd_kaprodi);
            $mime = Storage::disk('local')->mimeType($prodi->file_ttd_kaprodi) ?: 'image/png';

            return 'data:' . $mime . ';base64,' . base64_encode((string) $content);
        }

        return '';
    }

    private function bulanRomawi(int $bulan): string
    {
        return match ($bulan) {
            1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV',
            5 => 'V', 6 => 'VI', 7 => 'VII', 8 => 'VIII',
            9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII',
            default => 'I',
        };
    }

    private function formatTanggalIndonesia(\Carbon\CarbonInterface $date): string
    {
        $bulanList = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        $b = $bulanList[$date->month] ?? $date->format('F');

        return "{$date->day} {$b} {$date->year}";
    }
}
