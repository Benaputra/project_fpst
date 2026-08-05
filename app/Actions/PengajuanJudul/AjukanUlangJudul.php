<?php

namespace App\Actions\PengajuanJudul;

use App\Enums\StatusPengajuanJudul;
use App\Models\PengajuanJudul;
use App\Models\User;
use App\Services\Audit\CatatAktivitas;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AjukanUlangJudul
{
    public function __construct(private readonly CatatAktivitas $audit) {}

    /**
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function execute(User $user, PengajuanJudul $pengajuanJudul, string $judul): PengajuanJudul
    {
        $judulTernormalisasi = Str::squish($judul);

        if ($judulTernormalisasi === '') {
            throw ValidationException::withMessages([
                'judul' => 'Judul wajib diisi.',
            ]);
        }

        return DB::transaction(function () use ($user, $pengajuanJudul, $judulTernormalisasi) {
            $pengajuanTerkunci = PengajuanJudul::query()
                ->lockForUpdate()
                ->findOrFail($pengajuanJudul->getKey());

            if (! $this->adalahPemilik($user, $pengajuanTerkunci)) {
                throw new AuthorizationException('Pengajuan hanya dapat diperbaiki oleh pemiliknya.');
            }

            if ($pengajuanTerkunci->status !== StatusPengajuanJudul::Ditolak) {
                throw ValidationException::withMessages([
                    'judul' => 'Hanya pengajuan yang ditolak yang dapat diajukan ulang.',
                ]);
            }

            $statusSebelum = $pengajuanTerkunci->status->value;
            $pengajuanTerkunci->judul = $judulTernormalisasi;
            $pengajuanTerkunci->status = StatusPengajuanJudul::Diajukan;
            $pengajuanTerkunci->catatan_reject = null;
            $pengajuanTerkunci->diverifikasi_oleh = null;
            $pengajuanTerkunci->diverifikasi_at = null;
            $pengajuanTerkunci->save();

            $this->audit->execute($user, $pengajuanTerkunci, 'judul_diajukan_ulang', [
                'status' => $statusSebelum,
            ], [
                'status' => StatusPengajuanJudul::Diajukan->value,
            ]);

            return $pengajuanTerkunci->refresh();
        });
    }

    private function adalahPemilik(User $user, PengajuanJudul $pengajuanJudul): bool
    {
        if (! $user->isMahasiswa()) {
            return false;
        }

        return $user->mahasiswa()
            ->where('nim', $pengajuanJudul->nim)
            ->exists();
    }
}
