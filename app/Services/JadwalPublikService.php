<?php

namespace App\Services;

use App\Enums\PeranAudiensSeminar;
use App\Enums\StatusAudiensSeminar;
use App\Enums\StatusPengajuan;
use App\Enums\StatusPraktikLapang;
use App\Models\PraktikLapang;
use App\Models\SeminarSkripsi;
use App\Models\SidangSkripsi;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Throwable;

class JadwalPublikService
{
    public const CACHE_KEY = 'fpst:jadwal_publik:v1';

    /**
     * Ambil seluruh agenda Seminar Skripsi, Sidang Skripsi, dan Seminar Praktik Lapang (PL)
     * dengan prinsip Data Minimization, bebas N+1 query (withCount), dan cache stampede lock.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getAllScheduledEntries(): array
    {
        $cached = Cache::get(self::CACHE_KEY);
        if (is_array($cached)) {
            return $cached;
        }

        $ttl = now()->addSeconds((int) config('fpst.peak_performance.jadwal_cache_ttl_seconds', 300));

        try {
            return Cache::lock(self::CACHE_KEY . ':lock', 10)->block(5, function () use ($ttl): array {
                $secondRead = Cache::get(self::CACHE_KEY);
                if (is_array($secondRead)) {
                    return $secondRead;
                }

                $entries = $this->buildScheduledEntries();
                Cache::put(self::CACHE_KEY, $entries, $ttl);

                return $entries;
            });
        } catch (Throwable) {
            return Cache::remember(self::CACHE_KEY, $ttl, fn (): array => $this->buildScheduledEntries());
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildScheduledEntries(): array
    {
        $today = Carbon::today();
        $entries = [];

        $seminars = SeminarSkripsi::query()
            ->with([
                'pengajuanSkripsi.mahasiswa:id,name',
                'pengajuanSkripsi.programStudi:id,nama,kode',
                'pengajuanSkripsi.pembimbing1:id,name',
                'pengajuanSkripsi.pembimbing2:id,name',
                'penguji:id,name',
            ])
            ->withCount([
                'audiens as terisi_total_count' => fn ($q) => $q->whereIn('status', [
                    StatusAudiensSeminar::Terdaftar->value,
                    StatusAudiensSeminar::Hadir->value,
                ]),
                'audiens as terisi_penyanggah_count' => fn ($q) => $q->whereIn('status', [
                    StatusAudiensSeminar::Terdaftar->value,
                    StatusAudiensSeminar::Hadir->value,
                ])->where('peran', PeranAudiensSeminar::Penyanggah->value),
            ])
            ->whereNotNull('tgl_seminar')
            ->where('status', '!=', StatusPengajuan::Ditolak)
            ->orderBy('tgl_seminar')
            ->get();

        foreach ($seminars as $seminar) {
            $skripsi = $seminar->pengajuanSkripsi;
            if (! $skripsi || ! $skripsi->mahasiswa || ! $skripsi->programStudi || ! $seminar->tgl_seminar) {
                continue;
            }

            $tanggal = $seminar->tgl_seminar instanceof Carbon
                ? $seminar->tgl_seminar
                : Carbon::parse($seminar->tgl_seminar);

            $pembimbing = collect([
                $skripsi->pembimbing1?->name,
                $skripsi->pembimbing2?->name,
            ])->filter()->values()->all();

            $penguji = collect([
                $seminar->penguji?->name,
            ])->filter()->values()->all();

            $kapasitasTotal = $seminar->kapasitasEfektif();
            $terisiTotal = (int) ($seminar->terisi_total_count ?? 0);
            $kapasitasPenyanggah = $seminar->kapasitasPenyanggahEfektif();
            $terisiPenyanggah = (int) ($seminar->terisi_penyanggah_count ?? 0);

            $entries[] = [
                'jenis' => 'seminar',
                'jenis_label' => 'Seminar Skripsi',
                'mahasiswa_nama' => $skripsi->mahasiswa->name,
                'prodi_id' => $skripsi->programStudi->id,
                'prodi_nama' => $skripsi->programStudi->nama,
                'prodi_kode' => $skripsi->programStudi->kode,
                'judul' => $skripsi->judul,
                'tanggal_iso' => $tanggal->toDateString(),
                'tanggal_label' => $tanggal->translatedFormat('l, d F Y'),
                'jam' => $seminar->jam_seminar ?: 'Sesuai Jadwal Prodi',
                'ruangan' => $seminar->ruangan ?: 'Ruang Sidang / Seminar FPST',
                'pembimbing' => $pembimbing,
                'penguji' => $penguji,
                'status' => $seminar->status->value,
                'status_label' => $seminar->status === StatusPengajuan::Selesai ? 'Terlaksana' : 'Terjadwal',
                'is_mendatang' => $tanggal->greaterThanOrEqualTo($today) && $seminar->status !== StatusPengajuan::Selesai,
                'seminar_id' => $seminar->id,
                'pendaftaran_audiens_dibuka' => (bool) $seminar->pendaftaran_audiens_dibuka,
                'kuota_penonton' => $kapasitasTotal,
                'terisi_penonton' => $terisiTotal,
                'sisa_penonton' => max(0, $kapasitasTotal - $terisiTotal),
                'kuota_penyanggah' => $kapasitasPenyanggah,
                'sisa_penyanggah' => max(0, min($kapasitasPenyanggah - $terisiPenyanggah, max(0, $kapasitasTotal - $terisiTotal))),
            ];
        }

        $sidangs = SidangSkripsi::query()
            ->with([
                'pengajuanSkripsi.mahasiswa:id,name',
                'pengajuanSkripsi.programStudi:id,nama,kode',
                'pengajuanSkripsi.pembimbing1:id,name',
                'pengajuanSkripsi.pembimbing2:id,name',
                'penguji1:id,name',
                'penguji2:id,name',
            ])
            ->whereNotNull('tgl_sidang')
            ->where('status', '!=', StatusPengajuan::Ditolak)
            ->orderBy('tgl_sidang')
            ->get();

        foreach ($sidangs as $sidang) {
            $skripsi = $sidang->pengajuanSkripsi;
            if (! $skripsi || ! $skripsi->mahasiswa || ! $skripsi->programStudi || ! $sidang->tgl_sidang) {
                continue;
            }

            $tanggal = $sidang->tgl_sidang instanceof Carbon
                ? $sidang->tgl_sidang
                : Carbon::parse($sidang->tgl_sidang);

            $pembimbing = collect([
                $skripsi->pembimbing1?->name,
                $skripsi->pembimbing2?->name,
            ])->filter()->values()->all();

            $penguji = collect([
                $sidang->penguji1?->name,
                $sidang->penguji2?->name,
            ])->filter()->values()->all();

            $entries[] = [
                'jenis' => 'sidang',
                'jenis_label' => 'Sidang Skripsi',
                'mahasiswa_nama' => $skripsi->mahasiswa->name,
                'prodi_id' => $skripsi->programStudi->id,
                'prodi_nama' => $skripsi->programStudi->nama,
                'prodi_kode' => $skripsi->programStudi->kode,
                'judul' => $skripsi->judul,
                'tanggal_iso' => $tanggal->toDateString(),
                'tanggal_label' => $tanggal->translatedFormat('l, d F Y'),
                'jam' => $sidang->jam_sidang ?: 'Sesuai Jadwal Prodi',
                'ruangan' => $sidang->ruangan ?: 'Ruang Sidang Skripsi FPST',
                'pembimbing' => $pembimbing,
                'penguji' => $penguji,
                'status' => $sidang->status->value,
                'status_label' => $sidang->status === StatusPengajuan::Selesai ? 'Terlaksana' : 'Terjadwal',
                'is_mendatang' => $tanggal->greaterThanOrEqualTo($today) && $sidang->status !== StatusPengajuan::Selesai,
            ];
        }

        $praktikLapangs = PraktikLapang::query()
            ->with([
                'mahasiswa:id,name',
                'programStudi:id,nama,kode',
                'dpl:id,name',
                'lokasiPl:id,nama_instansi',
            ])
            ->whereNotNull('tgl_seminar_pl')
            ->where('status', '!=', StatusPraktikLapang::Ditolak)
            ->orderBy('tgl_seminar_pl')
            ->get();

        foreach ($praktikLapangs as $pl) {
            if (! $pl->mahasiswa || ! $pl->programStudi || ! $pl->tgl_seminar_pl) {
                continue;
            }

            $tanggal = $pl->tgl_seminar_pl instanceof Carbon
                ? $pl->tgl_seminar_pl
                : Carbon::parse($pl->tgl_seminar_pl);

            $entries[] = [
                'jenis' => 'pl',
                'jenis_label' => 'Seminar Praktik Lapang',
                'mahasiswa_nama' => $pl->mahasiswa->name,
                'prodi_id' => $pl->programStudi->id,
                'prodi_nama' => $pl->programStudi->nama,
                'prodi_kode' => $pl->programStudi->kode,
                'judul' => $pl->judul_kegiatan . ($pl->lokasiPl ? " ({$pl->lokasiPl->nama_instansi})" : ''),
                'tanggal_iso' => $tanggal->toDateString(),
                'tanggal_label' => $tanggal->translatedFormat('l, d F Y'),
                'jam' => $pl->jam_seminar_pl ?: 'Sesuai Jadwal Prodi',
                'ruangan' => $pl->ruangan_seminar_pl ?: 'Ruang Seminar FPST',
                'pembimbing' => array_values(array_filter([$pl->dpl?->name])),
                'penguji' => [],
                'status' => $pl->status->value,
                'status_label' => $pl->status === StatusPraktikLapang::Selesai ? 'Terlaksana' : 'Terjadwal',
                'is_mendatang' => $tanggal->greaterThanOrEqualTo($today) && $pl->status !== StatusPraktikLapang::Selesai,
            ];
        }

        usort($entries, function (array $a, array $b): int {
            if ($a['is_mendatang'] !== $b['is_mendatang']) {
                return $a['is_mendatang'] ? -1 : 1;
            }

            return strcmp($a['tanggal_iso'], $b['tanggal_iso']);
        });

        return $entries;
    }

    /**
     * Filter entri jadwal publik sesuai query pengguna.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function getFilteredSchedules(
        ?string $jenis = null,
        ?int $prodiId = null,
        string $periode = 'semua',
        ?string $search = null
    ): Collection {
        $collection = collect($this->getAllScheduledEntries());

        if ($jenis && in_array($jenis, ['seminar', 'sidang', 'pl'], true)) {
            $collection = $collection->where('jenis', $jenis);
        }

        if ($prodiId) {
            $collection = $collection->where('prodi_id', $prodiId);
        }

        if ($periode === 'mendatang') {
            $collection = $collection->where('is_mendatang', true);
        }

        if ($search) {
            $needle = mb_strtolower(trim($search));
            $collection = $collection->filter(function (array $item) use ($needle): bool {
                $haystack = mb_strtolower(implode(' ', array_merge(
                    [$item['mahasiswa_nama'], $item['judul'], $item['ruangan'], $item['prodi_nama']],
                    $item['pembimbing'],
                    $item['penguji']
                )));

                return str_contains($haystack, $needle);
            });
        }

        return $collection->values();
    }

    public static function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
