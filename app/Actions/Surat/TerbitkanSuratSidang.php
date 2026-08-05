<?php

namespace App\Actions\Surat;

use App\Enums\JenisSurat;
use App\Enums\StatusSidangSkripsi;
use App\Enums\StatusSurat;
use App\Models\Dosen;
use App\Models\SidangSkripsi;
use App\Models\Surat;
use App\Models\User;
use App\Services\Pdf\SuratSeminarPdf;
use App\Services\Signature\TandaTanganKaprodi;
use App\Services\Surat\ArsipPdfSurat;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class TerbitkanSuratSidang
{
    public function __construct(
        private readonly SuratSeminarPdf $pdf,
        private readonly TandaTanganKaprodi $tandaTangan,
        private readonly ArsipPdfSurat $arsip
    ) {}

    public function execute(User $user, SidangSkripsi $sidang, JenisSurat $jenis): Surat
    {
        if (! in_array($jenis, [JenisSurat::UndanganSidang, JenisSurat::SuratTugasSidang], true)) {
            throw ValidationException::withMessages(['jenis_surat' => 'Jenis surat sidang tidak sah.']);
        }
        $path = null;
        try {
            return DB::transaction(function () use ($user, $sidang, $jenis, &$path) {
                $s = SidangSkripsi::query()->with(['skripsi.mahasiswa.programStudi.ketuaProdi', 'skripsi.pembimbing1', 'skripsi.pembimbing2', 'penguji1', 'penguji2'])->lockForUpdate()->findOrFail($sidang->id);
                Gate::forUser($user)->authorize('terbitkanSurat', $s);
                if ($s->status !== StatusSidangSkripsi::Dijadwalkan || ! $s->tanggal || ! $s->tempat || ! $s->penguji1 || ! $s->penguji2) {
                    throw ValidationException::withMessages(['sidang' => 'Surat hanya untuk sidang yang dijadwalkan lengkap.']);
                }
                $prodi = $s->skripsi->mahasiswa->programStudi;
                $signer = null;
                $ttd = null;
                if ($user->isKetuaProdiUntuk($prodi)) {
                    $signer = $prodi->ketuaProdi;
                    if (! $signer instanceof Dosen || $signer->user_id !== $user->id) {
                        throw ValidationException::withMessages(['tanda_tangan' => 'Identitas Kaprodi tidak konsisten.']);
                    }
                    $ttd = $this->tandaTangan->dataUri($prodi);
                }
                $lama = Surat::query()->whereMorphedTo('suratable', $s)->where('jenis_surat', $jenis)->lockForUpdate()->get();
                $versi = ((int) $lama->max('versi')) + 1;
                $waktu = now();
                $kode = $jenis === JenisSurat::UndanganSidang ? 'USD' : 'TSD';
                $nomor = sprintf('%s-%s-%05d-%010d-V%02d', $kode, $waktu->format('Y'), $prodi->id, $s->id, $versi);
                $bytes = $this->pdf->render($s, $jenis, $nomor, $waktu, $signer, $ttd);
                $hash = hash('sha256', $bytes);
                $path = "surat/sidang/{$s->id}/{$jenis->value}/v{$versi}-{$hash}.pdf";

                return $this->arsip->execute($user, $lama, $path, $bytes, ['suratable_type' => SidangSkripsi::class, 'suratable_id' => $s->id, 'program_studi_id' => $prodi->id,
                    'jenis_surat' => $jenis, 'no_surat' => $nomor, 'tujuan_surat' => $s->skripsi->mahasiswa->nama, 'versi' => $versi,
                    'status' => StatusSurat::Terverifikasi, 'generated_at' => $waktu,
                    'verified_by' => $user->id, 'verified_at' => $waktu, 'signed_by' => $signer?->nidn, 'signed_at' => $signer ? $waktu : null]);
            }, 3);
        } catch (Throwable $e) {
            if ($path && ! Surat::query()->where('file_path', $path)->exists()) {
                Storage::disk('local')->delete($path);
            }
            throw $e;
        }
    }
}
