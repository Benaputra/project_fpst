<?php

namespace App\Actions\Surat;

use App\Enums\JenisSurat;
use App\Enums\PeranKesediaanBimbingan;
use App\Enums\StatusKesediaanBimbingan;
use App\Enums\StatusSurat;
use App\Models\KesediaanBimbingan;
use App\Models\Surat;
use App\Models\User;
use App\Services\Pdf\SuratKesediaanPdf;
use App\Services\Surat\ArsipPdfSurat;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class TerbitkanSuratKesediaan
{
    public function __construct(
        private readonly SuratKesediaanPdf $pdf,
        private readonly ArsipPdfSurat $arsip
    ) {}

    /**
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function execute(User $user, KesediaanBimbingan $kesediaan): Surat
    {
        $pathBaru = null;

        try {
            return DB::transaction(function () use ($user, $kesediaan, &$pathBaru) {
                $kesediaanTerkunci = KesediaanBimbingan::query()
                    ->with([
                        'dosen',
                        'skripsi.mahasiswa.programStudi',
                    ])
                    ->lockForUpdate()
                    ->findOrFail($kesediaan->getKey());

                Gate::forUser($user)->authorize('terbitkanSurat', $kesediaanTerkunci);
                $this->pastikanCalonAktif($kesediaanTerkunci);

                $suratSebelumnya = Surat::query()
                    ->where('suratable_type', KesediaanBimbingan::class)
                    ->where('suratable_id', $kesediaanTerkunci->id)
                    ->where('jenis_surat', JenisSurat::KesediaanPembimbing)
                    ->lockForUpdate()
                    ->orderByDesc('versi')
                    ->get();
                $versi = ((int) $suratSebelumnya->max('versi')) + 1;
                $tanggalTerbit = Carbon::now();
                $programStudiId = (int) $kesediaanTerkunci
                    ->skripsi
                    ->mahasiswa
                    ->program_studi_id;
                $nomorSurat = $this->nomorSurat(
                    $kesediaanTerkunci,
                    $programStudiId,
                    $versi,
                    $tanggalTerbit
                );
                $pdf = $this->pdf->render($kesediaanTerkunci, $nomorSurat, $tanggalTerbit);
                $hash = hash('sha256', $pdf);
                $pathBaru = sprintf(
                    'surat/kesediaan/%d/%d/v%d-%s.pdf',
                    $programStudiId,
                    $kesediaanTerkunci->id,
                    $versi,
                    $hash
                );

                $surat = $this->arsip->execute($user, $suratSebelumnya, $pathBaru, $pdf, [
                    'suratable_type' => KesediaanBimbingan::class,
                    'suratable_id' => $kesediaanTerkunci->id,
                    'program_studi_id' => $programStudiId,
                    'jenis_surat' => JenisSurat::KesediaanPembimbing,
                    'no_surat' => $nomorSurat,
                    'tujuan_surat' => $kesediaanTerkunci->dosen->nama,
                    'versi' => $versi,
                    'status' => StatusSurat::Diterbitkan,
                    'generated_at' => $tanggalTerbit,
                    'verified_by' => null,
                    'verified_at' => null,
                    'signed_by' => null,
                    'signed_at' => null,
                ]);

                $kesediaanTerkunci->forceFill([
                    'status' => StatusKesediaanBimbingan::MenungguUpload,
                ])->save();

                return $surat->load([
                    'suratable.dosen',
                    'suratable.skripsi.mahasiswa',
                    'programStudi',
                ]);
            });
        } catch (Throwable $exception) {
            if ($pathBaru !== null
                && ! Surat::query()->where('file_path', $pathBaru)->exists()) {
                Storage::disk('local')->delete($pathBaru);
            }

            throw $exception;
        }
    }

    /**
     * @throws ValidationException
     */
    private function pastikanCalonAktif(KesediaanBimbingan $kesediaan): void
    {
        $siklusTerakhir = (int) KesediaanBimbingan::query()
            ->where('skripsi_id', $kesediaan->skripsi_id)
            ->where('peran', $kesediaan->peran)
            ->max('siklus');
        $statusAktif = [
            StatusKesediaanBimbingan::Ditunjuk,
            StatusKesediaanBimbingan::SuratTerbit,
            StatusKesediaanBimbingan::MenungguUpload,
        ];

        if ($kesediaan->siklus !== $siklusTerakhir
            || ! in_array($kesediaan->status, $statusAktif, true)) {
            throw ValidationException::withMessages([
                'kesediaan' => 'Surat hanya dapat diterbitkan untuk calon pembimbing yang aktif.',
            ]);
        }
    }

    private function nomorSurat(
        KesediaanBimbingan $kesediaan,
        int $programStudiId,
        int $versi,
        Carbon $tanggalTerbit
    ): string {
        $kodePeran = $kesediaan->peran === PeranKesediaanBimbingan::Pembimbing1
            ? 'P1'
            : 'P2';

        return sprintf(
            'KSB-%s-%05d-%010d-%s-S%02d-V%02d',
            $tanggalTerbit->format('Y'),
            $programStudiId,
            $kesediaan->id,
            $kodePeran,
            $kesediaan->siklus,
            $versi
        );
    }
}
