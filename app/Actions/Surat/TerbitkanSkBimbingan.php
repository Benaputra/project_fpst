<?php

namespace App\Actions\Surat;

use App\Enums\JenisSurat;
use App\Enums\StatusSkripsi;
use App\Enums\StatusSurat;
use App\Models\Dosen;
use App\Models\Skripsi;
use App\Models\Surat;
use App\Models\User;
use App\Services\Pdf\SkBimbinganPdf;
use App\Services\Signature\TandaTanganKaprodi;
use App\Services\Surat\ArsipPdfSurat;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class TerbitkanSkBimbingan
{
    public function __construct(
        private readonly SkBimbinganPdf $pdf,
        private readonly TandaTanganKaprodi $tandaTangan,
        private readonly ArsipPdfSurat $arsip
    ) {}

    /**
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function execute(User $user, Skripsi $skripsi): Surat
    {
        $pathBaru = null;

        try {
            return DB::transaction(function () use ($user, $skripsi, &$pathBaru) {
                $skripsiTerkunci = Skripsi::query()
                    ->with([
                        'mahasiswa.programStudi.ketuaProdi',
                        'pembimbing1',
                        'pembimbing2',
                    ])
                    ->lockForUpdate()
                    ->findOrFail($skripsi->getKey());
                Gate::forUser($user)->authorize('terbitkanSk', $skripsiTerkunci);
                $this->pastikanSkripsiFinal($skripsiTerkunci);

                $programStudi = $skripsiTerkunci->mahasiswa->programStudi;
                $penandaTangan = null;
                $dataTandaTangan = null;
                if ($user->isKetuaProdiUntuk($programStudi)) {
                    $penandaTangan = $programStudi->ketuaProdi;
                    if (! $penandaTangan instanceof Dosen
                        || $penandaTangan->user_id !== $user->id) {
                        throw ValidationException::withMessages([
                            'tanda_tangan' => 'Identitas Kaprodi aktif tidak konsisten.',
                        ]);
                    }
                    $dataTandaTangan = $this->tandaTangan->dataUri($programStudi);
                }

                $suratSebelumnya = Surat::query()
                    ->where('suratable_type', Skripsi::class)
                    ->where('suratable_id', $skripsiTerkunci->id)
                    ->where('jenis_surat', JenisSurat::SkBimbingan)
                    ->lockForUpdate()
                    ->orderByDesc('versi')
                    ->get();
                $versi = ((int) $suratSebelumnya->max('versi')) + 1;
                $waktu = Carbon::now();
                $nomor = sprintf(
                    'SKB-%s-%05d-%010d-V%02d',
                    $waktu->format('Y'),
                    $programStudi->id,
                    $skripsiTerkunci->id,
                    $versi
                );
                $pdf = $this->pdf->render(
                    $skripsiTerkunci,
                    $nomor,
                    $waktu,
                    $penandaTangan,
                    $dataTandaTangan
                );
                $hash = hash('sha256', $pdf);
                $pathBaru = sprintf(
                    'surat/sk-bimbingan/%d/%d/v%d-%s.pdf',
                    $programStudi->id,
                    $skripsiTerkunci->id,
                    $versi,
                    $hash
                );

                return $this->arsip->execute($user, $suratSebelumnya, $pathBaru, $pdf, [
                    'suratable_type' => Skripsi::class,
                    'suratable_id' => $skripsiTerkunci->id,
                    'program_studi_id' => $programStudi->id,
                    'jenis_surat' => JenisSurat::SkBimbingan,
                    'no_surat' => $nomor,
                    'tujuan_surat' => $skripsiTerkunci->mahasiswa->nama,
                    'versi' => $versi,
                    'status' => StatusSurat::Terverifikasi,
                    'generated_at' => $waktu,
                    'verified_by' => $user->id,
                    'verified_at' => $waktu,
                    'signed_by' => $penandaTangan?->nidn,
                    'signed_at' => $penandaTangan === null ? null : $waktu,
                ]);
            }, 3);
        } catch (Throwable $exception) {
            if ($pathBaru !== null
                && ! Surat::query()->where('file_path', $pathBaru)->exists()) {
                Storage::disk('local')->delete($pathBaru);
            }

            throw $exception;
        }
    }

    /** @throws ValidationException */
    private function pastikanSkripsiFinal(Skripsi $skripsi): void
    {
        if ($skripsi->status !== StatusSkripsi::BimbinganAktif
            || $skripsi->pembimbing1_id === null
            || $skripsi->pembimbing1 === null
            || ($skripsi->pembimbing2_id !== null && $skripsi->pembimbing2 === null)) {
            throw ValidationException::withMessages([
                'skripsi' => 'SK hanya dapat diterbitkan setelah pembimbing resmi ditetapkan.',
            ]);
        }
    }
}
