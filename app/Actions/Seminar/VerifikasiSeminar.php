<?php

namespace App\Actions\Seminar;

use App\Enums\KeputusanVerifikasiPengajuan;
use App\Enums\StatusDokumenPengajuan;
use App\Enums\StatusSeminar;
use App\Models\DokumenPengajuan;
use App\Models\Seminar;
use App\Models\User;
use App\Services\Audit\CatatAktivitas;
use App\Services\Document\PastikanIntegritasDokumen;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class VerifikasiSeminar
{
    public function __construct(
        private readonly PastikanIntegritasDokumen $integritas,
        private readonly CatatAktivitas $audit
    ) {}

    public function execute(
        User $user,
        Seminar $seminar,
        KeputusanVerifikasiPengajuan $keputusan,
        ?string $catatan = null
    ): Seminar {
        $catatan = trim((string) $catatan) ?: null;
        if ($keputusan === KeputusanVerifikasiPengajuan::Tolak && $catatan === null) {
            throw ValidationException::withMessages([
                'catatan_reject' => 'Alasan penolakan wajib diisi.',
            ]);
        }

        return DB::transaction(function () use ($user, $seminar, $keputusan, $catatan) {
            $terkunci = Seminar::query()->with('skripsi.mahasiswa')
                ->lockForUpdate()->findOrFail($seminar->id);
            Gate::forUser($user)->authorize('verify', $terkunci);
            if ($terkunci->status !== StatusSeminar::Diajukan) {
                throw ValidationException::withMessages([
                    'seminar' => 'Hanya seminar berstatus diajukan yang dapat diverifikasi.',
                ]);
            }
            $waktu = now();
            $diterima = $keputusan === KeputusanVerifikasiPengajuan::Terima;
            $terkunci->forceFill([
                'status' => $diterima ? StatusSeminar::Diverifikasi : StatusSeminar::Ditolak,
                'catatan_reject' => $diterima ? null : $catatan,
                'verified_by' => $user->id,
                'verified_at' => $waktu,
            ])->save();

            $dokumen = DokumenPengajuan::query()
                ->where('documentable_type', Seminar::class)
                ->where('documentable_id', $terkunci->id)
                ->where('status', StatusDokumenPengajuan::MenungguVerifikasi)
                ->lockForUpdate()->get();
            foreach ($dokumen as $item) {
                $this->integritas->execute($item);
            }

            foreach ($dokumen as $item) {
                $item->forceFill([
                    'status' => $diterima
                        ? StatusDokumenPengajuan::Terverifikasi
                        : StatusDokumenPengajuan::Ditolak,
                    'verified_by' => $user->id,
                    'verified_at' => $waktu,
                    'catatan_verifikasi' => $diterima ? null : $catatan,
                ])->save();
            }

            $this->audit->execute($user, $terkunci, 'seminar_diverifikasi', [
                'status' => StatusSeminar::Diajukan->value,
            ], [
                'status' => $terkunci->status->value,
                'keputusan' => $keputusan->value,
            ]);

            return $terkunci->refresh()->load('dokumenPengajuan');
        }, 3);
    }
}
