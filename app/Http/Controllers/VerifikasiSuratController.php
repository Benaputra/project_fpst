<?php

namespace App\Http\Controllers;

use App\Models\Surat;
use Illuminate\View\View;

class VerifikasiSuratController extends Controller
{
    public function __invoke(string $token): View
    {
        if (! config('fpst.surat_internal.qr_verifikasi_enabled', false)) {
            return view('verifikasi.surat', [
                'surat' => null,
                'token' => $token,
                'isValidSignature' => false,
                'pemilikNama' => '-',
                'qrDisabled' => true,
            ]);
        }

        $surat = Surat::query()
            ->with([
                'programStudi:id,nama,kode',
                'pengajuanSkripsi.mahasiswa:id,name',
                'pengajuanSurat.mahasiswa:id,name,nomor_induk',
                'penandaTangan:id,name,jabatan',
            ])
            ->where('verification_token', $token)
            ->first();

        $isValidSignature = false;
        $pemilikNama = '-';

        if ($surat) {
            $pemilikNama = $surat->pengajuanSurat?->mahasiswa?->name
                ?? $surat->pengajuanSkripsi?->mahasiswa?->name
                ?? '-';

            if ($surat->pengajuanSurat && $surat->verification_hash) {
                $expectedHash = hash_hmac('sha256', implode('|', [
                    $surat->nomor_surat,
                    $surat->jenis_surat,
                    (string) ($surat->pengajuanSurat->mahasiswa?->nomor_induk),
                    (string) $surat->verification_token,
                ]), (string) config('app.key'));

                $isValidSignature = hash_equals($expectedHash, $surat->verification_hash);
            } else {
                $isValidSignature = true;
            }
        }

        return view('verifikasi.surat', compact('surat', 'token', 'isValidSignature', 'pemilikNama'));
    }
}
