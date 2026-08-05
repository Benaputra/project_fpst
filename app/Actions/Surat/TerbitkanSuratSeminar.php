<?php

namespace App\Actions\Surat;

use App\Enums\JenisSurat;
use App\Enums\StatusSeminar;
use App\Enums\StatusSurat;
use App\Models\Dosen;
use App\Models\Seminar;
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

class TerbitkanSuratSeminar
{
    public function __construct(
        private readonly SuratSeminarPdf $pdf,
        private readonly TandaTanganKaprodi $tandaTangan,
        private readonly ArsipPdfSurat $arsip
    ) {}

    public function execute(User $user, Seminar $seminar, JenisSurat $jenis): Surat
    {
        if (! in_array($jenis, [JenisSurat::UndanganSeminar, JenisSurat::SuratTugasSeminar], true)) {
            throw ValidationException::withMessages(['jenis_surat' => 'Jenis surat seminar tidak sah.']);
        }
        $path = null;
        try {
            return DB::transaction(function () use ($user, $seminar, $jenis, &$path) {
                $locked = Seminar::query()->with(['skripsi.mahasiswa.programStudi.ketuaProdi', 'skripsi.pembimbing1', 'skripsi.pembimbing2', 'penguji1', 'penguji2'])
                    ->lockForUpdate()->findOrFail($seminar->id);
                Gate::forUser($user)->authorize('terbitkanSurat', $locked);
                if ($locked->status !== StatusSeminar::Dijadwalkan || $locked->tanggal === null || $locked->tempat === null || $locked->penguji1 === null || $locked->penguji2 === null) {
                    throw ValidationException::withMessages(['seminar' => 'Surat hanya untuk seminar yang lengkap dan dijadwalkan.']);
                }
                $prodi = $locked->skripsi->mahasiswa->programStudi;
                $signer = null;
                $ttd = null;
                if ($user->isKetuaProdiUntuk($prodi)) {
                    $signer = $prodi->ketuaProdi;
                    if (! $signer instanceof Dosen || $signer->user_id !== $user->id) {
                        throw ValidationException::withMessages(['tanda_tangan' => 'Identitas Kaprodi tidak konsisten.']);
                    }
                    $ttd = $this->tandaTangan->dataUri($prodi);
                }
                $lama = Surat::query()->whereMorphedTo('suratable', $locked)->where('jenis_surat', $jenis)
                    ->lockForUpdate()->orderByDesc('versi')->get();
                $versi = ((int) $lama->max('versi')) + 1;
                $waktu = now();
                $kode = $jenis === JenisSurat::UndanganSeminar ? 'USM' : 'TSM';
                $nomor = sprintf('%s-%s-%05d-%010d-V%02d', $kode, $waktu->format('Y'), $prodi->id, $locked->id, $versi);
                $bytes = $this->pdf->render($locked, $jenis, $nomor, $waktu, $signer, $ttd);
                $hash = hash('sha256', $bytes);
                $path = "surat/seminar/{$locked->id}/{$jenis->value}/v{$versi}-{$hash}.pdf";

                return $this->arsip->execute($user, $lama, $path, $bytes, [
                    'suratable_type' => Seminar::class, 'suratable_id' => $locked->id,
                    'program_studi_id' => $prodi->id, 'jenis_surat' => $jenis,
                    'no_surat' => $nomor, 'tujuan_surat' => $locked->skripsi->mahasiswa->nama,
                    'versi' => $versi, 'status' => StatusSurat::Terverifikasi,
                    'generated_at' => $waktu,
                    'verified_by' => $user->id, 'verified_at' => $waktu,
                    'signed_by' => $signer?->nidn, 'signed_at' => $signer ? $waktu : null,
                ]);
            }, 3);
        } catch (Throwable $e) {
            if ($path !== null && ! Surat::query()->where('file_path', $path)->exists()) {
                Storage::disk('local')->delete($path);
            }
            throw $e;
        }
    }
}
