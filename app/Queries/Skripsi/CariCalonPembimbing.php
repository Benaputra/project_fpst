<?php

namespace App\Queries\Skripsi;

use App\Models\Dosen;
use App\Models\PengajuanJudul;
use Illuminate\Database\Eloquent\Collection;

class CariCalonPembimbing
{
    /**
     * @param  list<string>  $sertakanNidn
     * @return Collection<int, Dosen>
     */
    public function execute(
        PengajuanJudul $pengajuanJudul,
        ?string $pencarian = null,
        array $sertakanNidn = []
    ): Collection {
        $programStudiId = (int) $pengajuanJudul->mahasiswa()->value('program_studi_id');
        $pencarian = trim((string) $pencarian);

        $hasil = Dosen::query()
            ->select(['nidn', 'nama', 'nuptk', 'program_studi_id'])
            ->where('program_studi_id', $programStudiId)
            ->when($pencarian !== '', function ($query) use ($pencarian) {
                $pattern = '%'.addcslashes($pencarian, '\\%_').'%';

                $query->where(function ($query) use ($pattern) {
                    $query->where('nama', 'like', $pattern)
                        ->orWhere('nidn', 'like', $pattern)
                        ->orWhere('nuptk', 'like', $pattern);
                });
            })
            ->orderBy('nama')
            ->orderBy('nidn')
            ->limit(20)
            ->get();

        $sertakanNidn = array_values(array_unique(array_filter($sertakanNidn)));
        if ($sertakanNidn === []) {
            return $hasil;
        }

        $pilihanTersimpan = Dosen::query()
            ->select(['nidn', 'nama', 'nuptk', 'program_studi_id'])
            ->where('program_studi_id', $programStudiId)
            ->whereIn('nidn', $sertakanNidn)
            ->get();

        return $hasil
            ->concat($pilihanTersimpan)
            ->unique('nidn')
            ->sortBy([['nama', 'asc'], ['nidn', 'asc']])
            ->values();
    }
}
