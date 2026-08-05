<?php

namespace App\Services\Portal;

use App\Models\PengajuanJudul;
use App\Models\Seminar;
use App\Models\SidangSkripsi;
use App\Models\Skripsi;
use App\Models\Surat;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class CakupanDataPortal
{
    /** @return Builder<PengajuanJudul> */
    public function pengajuanJudul(User $user): Builder
    {
        $query = PengajuanJudul::query();

        if ($user->isAdminUtama()) {
            return $query;
        }

        if ($user->isMahasiswa()) {
            $nim = $user->mahasiswa()->value('nim');

            return $nim ? $query->where('nim', $nim) : $query->whereRaw('1 = 0');
        }

        $programStudiIds = $this->programStudiIds($user);

        return $programStudiIds === []
            ? $query->whereRaw('1 = 0')
            : $query->whereHas('mahasiswa', fn (Builder $mahasiswa) => $mahasiswa
                ->whereIn('program_studi_id', $programStudiIds));
    }

    /** @return Builder<Skripsi> */
    public function skripsi(User $user): Builder
    {
        $query = Skripsi::query();

        if ($user->isAdminUtama()) {
            return $query;
        }

        if ($user->isMahasiswa()) {
            $nim = $user->mahasiswa()->value('nim');

            return $nim ? $query->where('nim', $nim) : $query->whereRaw('1 = 0');
        }

        $programStudiIds = $this->programStudiIds($user);
        if ($programStudiIds !== []) {
            return $query->whereHas('mahasiswa', fn (Builder $mahasiswa) => $mahasiswa
                ->whereIn('program_studi_id', $programStudiIds));
        }

        $nidn = $user->dosen()->value('nidn');

        return $nidn
            ? $query->where(fn (Builder $skripsi) => $skripsi
                ->where('pembimbing1_id', $nidn)
                ->orWhere('pembimbing2_id', $nidn))
            : $query->whereRaw('1 = 0');
    }

    /** @return Builder<Seminar> */
    public function seminar(User $user): Builder
    {
        $query = Seminar::query();

        if ($user->isAdminUtama()) {
            return $query;
        }

        if ($user->isMahasiswa()) {
            $nim = $user->mahasiswa()->value('nim');

            return $nim
                ? $query->whereHas('skripsi', fn (Builder $skripsi) => $skripsi->where('nim', $nim))
                : $query->whereRaw('1 = 0');
        }

        $programStudiIds = $this->programStudiIds($user);
        if ($programStudiIds !== []) {
            return $query->whereHas('skripsi.mahasiswa', fn (Builder $mahasiswa) => $mahasiswa
                ->whereIn('program_studi_id', $programStudiIds));
        }

        $nidn = $user->dosen()->value('nidn');

        return $nidn
            ? $query->where(fn (Builder $seminar) => $seminar
                ->where('penguji1_id', $nidn)
                ->orWhere('penguji2_id', $nidn)
                ->orWhereHas('skripsi', fn (Builder $skripsi) => $skripsi
                    ->where('pembimbing1_id', $nidn)
                    ->orWhere('pembimbing2_id', $nidn)))
            : $query->whereRaw('1 = 0');
    }

    /** @return Builder<SidangSkripsi> */
    public function sidang(User $user): Builder
    {
        $query = SidangSkripsi::query();

        if ($user->isAdminUtama()) {
            return $query;
        }

        if ($user->isMahasiswa()) {
            $nim = $user->mahasiswa()->value('nim');

            return $nim
                ? $query->whereHas('skripsi', fn (Builder $skripsi) => $skripsi->where('nim', $nim))
                : $query->whereRaw('1 = 0');
        }

        $programStudiIds = $this->programStudiIds($user);
        if ($programStudiIds !== []) {
            return $query->whereHas('skripsi.mahasiswa', fn (Builder $mahasiswa) => $mahasiswa
                ->whereIn('program_studi_id', $programStudiIds));
        }

        $nidn = $user->dosen()->value('nidn');

        return $nidn
            ? $query->where(fn (Builder $sidang) => $sidang
                ->where('penguji1_id', $nidn)
                ->orWhere('penguji2_id', $nidn)
                ->orWhereHas('skripsi', fn (Builder $skripsi) => $skripsi
                    ->where('pembimbing1_id', $nidn)
                    ->orWhere('pembimbing2_id', $nidn)))
            : $query->whereRaw('1 = 0');
    }

    /** @return Builder<Surat> */
    public function surat(User $user): Builder
    {
        $query = Surat::query();

        if ($user->isAdminUtama()) {
            return $query;
        }

        $programStudiIds = $this->programStudiIds($user);

        return $programStudiIds === []
            ? $query->whereRaw('1 = 0')
            : $query->whereIn('program_studi_id', $programStudiIds);
    }

    /** @return list<int> */
    public function programStudiIds(User $user): array
    {
        if ($user->isAdminProdi()) {
            return $user->programStudiAdministrasi()
                ->pluck('program_studi.id')
                ->map(fn ($id) => (int) $id)
                ->all();
        }

        if ($user->isKetuaProdi()) {
            $id = $user->dosen?->programStudiDipimpin?->getKey();

            return $id ? [(int) $id] : [];
        }

        return [];
    }
}
