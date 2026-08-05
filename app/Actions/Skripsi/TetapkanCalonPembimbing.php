<?php

namespace App\Actions\Skripsi;

use App\Enums\PeranKesediaanBimbingan;
use App\Enums\StatusPengajuanJudul;
use App\Models\Dosen;
use App\Models\PengajuanJudul;
use App\Models\Skripsi;
use App\Models\User;
use App\Services\Audit\CatatAktivitas;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TetapkanCalonPembimbing
{
    public function __construct(private readonly CatatAktivitas $audit) {}

    /**
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function execute(
        User $user,
        PengajuanJudul $pengajuanJudul,
        string $pembimbing1Id,
        ?string $pembimbing2Id = null
    ): Skripsi {
        $pembimbing1Id = trim($pembimbing1Id);
        $pembimbing2Id = $pembimbing2Id === null ? null : trim($pembimbing2Id);
        $pembimbing2Id = $pembimbing2Id === '' ? null : $pembimbing2Id;

        try {
            return DB::transaction(function () use (
                $user,
                $pengajuanJudul,
                $pembimbing1Id,
                $pembimbing2Id
            ) {
                $pengajuanTerkunci = PengajuanJudul::query()
                    ->with('mahasiswa')
                    ->lockForUpdate()
                    ->findOrFail($pengajuanJudul->getKey());
                $programStudiId = (int) $pengajuanTerkunci->mahasiswa->program_studi_id;

                if (! $user->isKetuaProdiUntuk($programStudiId)) {
                    throw new AuthorizationException(
                        'Calon pembimbing hanya dapat ditetapkan oleh Kaprodi terkait.'
                    );
                }

                if ($pengajuanTerkunci->status !== StatusPengajuanJudul::Diverifikasi) {
                    throw ValidationException::withMessages([
                        'pengajuan' => 'Calon pembimbing hanya dapat ditetapkan untuk judul terverifikasi.',
                    ]);
                }

                if ($pembimbing1Id === '') {
                    throw ValidationException::withMessages([
                        'pembimbing1_id' => 'Calon Pembimbing 1 wajib dipilih.',
                    ]);
                }

                if ($pembimbing2Id !== null && $pembimbing1Id === $pembimbing2Id) {
                    throw ValidationException::withMessages([
                        'pembimbing2_id' => 'Calon Pembimbing 2 harus berbeda dari Pembimbing 1.',
                    ]);
                }

                if (Skripsi::query()
                    ->where('pengajuan_judul_id', $pengajuanTerkunci->id)
                    ->lockForUpdate()
                    ->exists()) {
                    throw ValidationException::withMessages([
                        'pengajuan' => 'Calon pembimbing untuk pengajuan ini sudah ditetapkan.',
                    ]);
                }

                $ids = array_values(array_filter([$pembimbing1Id, $pembimbing2Id]));
                $dosen = Dosen::query()
                    ->whereIn('nidn', $ids)
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('nidn');

                $pembimbing1 = $dosen->get($pembimbing1Id);
                $pembimbing2 = $pembimbing2Id === null ? null : $dosen->get($pembimbing2Id);

                $this->pastikanDosenValid($pembimbing1, 'pembimbing1_id', $programStudiId);
                if ($pembimbing2Id !== null) {
                    $this->pastikanDosenValid($pembimbing2, 'pembimbing2_id', $programStudiId);
                }

                $skripsi = Skripsi::query()->create([
                    'pengajuan_judul_id' => $pengajuanTerkunci->id,
                    'nim' => $pengajuanTerkunci->nim,
                    'judul' => $pengajuanTerkunci->judul,
                ]);

                $skripsi->kesediaanBimbingan()->create([
                    'dosen_id' => $pembimbing1Id,
                    'peran' => PeranKesediaanBimbingan::Pembimbing1,
                    'siklus' => 1,
                ]);

                if ($pembimbing2Id !== null) {
                    $skripsi->kesediaanBimbingan()->create([
                        'dosen_id' => $pembimbing2Id,
                        'peran' => PeranKesediaanBimbingan::Pembimbing2,
                        'siklus' => 1,
                    ]);
                }

                $this->audit->execute($user, $skripsi, 'calon_pembimbing_ditetapkan', [], [
                    'pembimbing1_id' => $pembimbing1Id,
                    'pembimbing2_id' => $pembimbing2Id,
                    'siklus' => 1,
                ]);

                return $skripsi->refresh()->load([
                    'pengajuanJudul',
                    'mahasiswa',
                    'kesediaanBimbingan.dosen',
                ]);
            }, 3);
        } catch (UniqueConstraintViolationException $exception) {
            if ($this->adalahDuplikasiSkripsi($exception)) {
                throw ValidationException::withMessages([
                    'pengajuan' => 'Calon pembimbing untuk pengajuan ini sudah ditetapkan.',
                ]);
            }

            throw $exception;
        }
    }

    /**
     * @throws ValidationException
     */
    private function pastikanDosenValid(?Dosen $dosen, string $field, int $programStudiId): void
    {
        if ($dosen === null) {
            throw ValidationException::withMessages([
                $field => 'Dosen yang dipilih tidak ditemukan.',
            ]);
        }

        if ((int) $dosen->program_studi_id !== $programStudiId) {
            throw ValidationException::withMessages([
                $field => 'Calon pembimbing harus berasal dari program studi mahasiswa.',
            ]);
        }
    }

    private function adalahDuplikasiSkripsi(UniqueConstraintViolationException $exception): bool
    {
        return str_contains($exception->getMessage(), 'skripsi_pengajuan_judul_id_unique')
            || str_contains($exception->getMessage(), 'skripsi_nim_unique');
    }
}
