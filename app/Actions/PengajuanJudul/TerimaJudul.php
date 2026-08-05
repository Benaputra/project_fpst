<?php

namespace App\Actions\PengajuanJudul;

use App\Enums\StatusPengajuanJudul;
use App\Models\Dosen;
use App\Models\PengajuanJudul;
use App\Models\User;
use App\Services\Audit\CatatAktivitas;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TerimaJudul
{
    public function __construct(private readonly CatatAktivitas $audit) {}

    /**
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function execute(User $user, PengajuanJudul $pengajuanJudul): PengajuanJudul
    {
        return DB::transaction(function () use ($user, $pengajuanJudul) {
            $pengajuanTerkunci = PengajuanJudul::query()
                ->lockForUpdate()
                ->findOrFail($pengajuanJudul->getKey());
            $verifikator = $this->kaprodiTerkait($user, $pengajuanTerkunci);

            if ($pengajuanTerkunci->status !== StatusPengajuanJudul::Diajukan) {
                throw ValidationException::withMessages([
                    'pengajuan' => 'Hanya pengajuan berstatus diajukan yang dapat diterima.',
                ]);
            }

            $pengajuanTerkunci->status = StatusPengajuanJudul::Diverifikasi;
            $pengajuanTerkunci->catatan_reject = null;
            $pengajuanTerkunci->diverifikasi_oleh = $verifikator->nidn;
            $pengajuanTerkunci->diverifikasi_at = now();
            $pengajuanTerkunci->save();

            $this->audit->execute($user, $pengajuanTerkunci, 'judul_diterima', [
                'status' => StatusPengajuanJudul::Diajukan->value,
            ], [
                'status' => StatusPengajuanJudul::Diverifikasi->value,
                'diverifikasi_oleh' => $verifikator->nidn,
            ]);

            return $pengajuanTerkunci->refresh();
        });
    }

    /**
     * @throws AuthorizationException
     */
    private function kaprodiTerkait(User $user, PengajuanJudul $pengajuanJudul): Dosen
    {
        $programStudiId = (int) $pengajuanJudul->mahasiswa()
            ->firstOrFail()
            ->program_studi_id;

        if (! $user->isKetuaProdiUntuk($programStudiId)) {
            throw new AuthorizationException('Keputusan hanya dapat dibuat oleh Kaprodi terkait.');
        }

        return $user->dosen()->firstOrFail();
    }
}
