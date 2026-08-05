<?php

namespace App\Actions\PengajuanJudul;

use App\Enums\StatusPengajuanJudul;
use App\Models\Mahasiswa;
use App\Models\PengajuanJudul;
use App\Models\User;
use App\Services\Audit\CatatAktivitas;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AjukanJudul
{
    public function __construct(private readonly CatatAktivitas $audit) {}

    /**
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function execute(User $user, string $judul): PengajuanJudul
    {
        $mahasiswa = $this->mahasiswaTerautentikasi($user);
        $judulTernormalisasi = Str::squish($judul);

        if ($judulTernormalisasi === '') {
            throw ValidationException::withMessages([
                'judul' => 'Judul wajib diisi.',
            ]);
        }

        $pengajuan = new PengajuanJudul([
            'judul' => $judulTernormalisasi,
        ]);
        $pengajuan->nim = $mahasiswa->nim;
        $pengajuan->status = StatusPengajuanJudul::Diajukan;

        try {
            DB::transaction(function () use ($user, $pengajuan) {
                $pengajuan->save();

                $this->audit->execute($user, $pengajuan, 'judul_diajukan', [], [
                    'status' => StatusPengajuanJudul::Diajukan->value,
                ]);
            });
        } catch (QueryException $exception) {
            if ($this->adalahDuplikasiPengajuan($exception)) {
                throw ValidationException::withMessages([
                    'judul' => 'Anda sudah memiliki pengajuan judul.',
                ]);
            }

            throw $exception;
        }

        return $pengajuan->refresh();
    }

    /**
     * @throws AuthorizationException
     */
    private function mahasiswaTerautentikasi(User $user): Mahasiswa
    {
        if (! $user->isMahasiswa()) {
            throw new AuthorizationException('Hanya mahasiswa yang dapat mengajukan judul.');
        }

        $mahasiswa = $user->mahasiswa()->first();

        if (! $mahasiswa) {
            throw new AuthorizationException('Akun belum terhubung dengan data mahasiswa.');
        }

        return $mahasiswa;
    }

    private function adalahDuplikasiPengajuan(QueryException $exception): bool
    {
        return (int) ($exception->errorInfo[1] ?? 0) === 1062
            && str_contains($exception->getMessage(), 'pengajuan_judul_nim_unique');
    }
}
