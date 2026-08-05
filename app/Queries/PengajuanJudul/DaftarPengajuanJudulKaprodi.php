<?php

namespace App\Queries\PengajuanJudul;

use App\Enums\StatusPengajuanJudul;
use App\Models\PengajuanJudul;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class DaftarPengajuanJudulKaprodi
{
    /**
     * @return LengthAwarePaginator<int, PengajuanJudul>
     */
    public function execute(
        User $kaprodi,
        ?StatusPengajuanJudul $status = null,
        ?string $pencarian = null
    ): LengthAwarePaginator {
        $nidn = $kaprodi->dosen()->firstOrFail()->nidn;

        return PengajuanJudul::query()
            ->with([
                'mahasiswa.programStudi',
                'mahasiswa.pembimbingAkademik',
            ])
            ->whereHas(
                'mahasiswa.programStudi',
                fn ($query) => $query->where('ketua_prodi_id', $nidn)
            )
            ->when($status, fn ($query) => $query->where('status', $status->value))
            ->when($pencarian, function ($query, string $kataKunci) {
                $like = '%'.addcslashes($kataKunci, '%_\\').'%';

                $query->where(function ($query) use ($like) {
                    $query->where('judul', 'like', $like)
                        ->orWhereHas('mahasiswa', function ($query) use ($like) {
                            $query->where('nim', 'like', $like)
                                ->orWhere('nama', 'like', $like);
                        });
                });
            })
            ->latest()
            ->latest('id')
            ->paginate(15)
            ->withQueryString();
    }
}
