<?php

namespace App\Services;

use App\Enums\StatusPengajuan;
use App\Enums\StatusPraktikLapang;
use App\Models\PraktikLapang;
use App\Models\SeminarSkripsi;
use App\Models\SidangSkripsi;
use App\Models\User;

class JadwalBentrokService
{
    /**
     * Periksa bentrok ruangan maupun bentrok jadwal dosen lintas Seminar Skripsi & Sidang Skripsi.
     * Mengembalikan pesan error deskriptif pertama jika bentrok ditemukan, atau null jika aman.
     *
     * @param array<int, int|null> $dosenIds
     */
    public function detectConflict(
        string $tanggalIso,
        string $jamString,
        string $ruangan,
        array $dosenIds,
        ?int $ignoreSeminarId = null,
        ?int $ignoreSidangId = null,
        ?int $ignorePlId = null
    ): ?string {
        $normalizedRoom = mb_strtolower(trim($ruangan));
        $targetDosenIds = array_values(array_unique(array_filter(array_map('intval', $dosenIds))));

        $seminars = SeminarSkripsi::query()
            ->with(['pengajuanSkripsi.mahasiswa:id,name'])
            ->whereDate('tgl_seminar', $tanggalIso)
            ->where('status', '!=', StatusPengajuan::Ditolak)
            ->when($ignoreSeminarId, fn ($q) => $q->where('id', '!=', $ignoreSeminarId))
            ->get();

        foreach ($seminars as $other) {
            if (! $this->isTimeOverlapping($jamString, (string) $other->jam_seminar)) {
                continue;
            }

            if ($normalizedRoom !== '' && mb_strtolower(trim((string) $other->ruangan)) === $normalizedRoom) {
                $mhsName = $other->pengajuanSkripsi?->mahasiswa?->name ?? 'mahasiswa lain';

                return "Bentrok jadwal ruangan: Ruang {$other->ruangan} sudah digunakan pada {$tanggalIso} ({$other->jam_seminar}) untuk Seminar Skripsi {$mhsName}.";
            }

            $otherDosenIds = array_filter([
                $other->pengajuanSkripsi?->pembimbing_1_id,
                $other->pengajuanSkripsi?->pembimbing_2_id,
                $other->penguji_seminar_id,
            ]);

            $conflictDosen = array_values(array_intersect($targetDosenIds, array_map('intval', $otherDosenIds)));
            if (! empty($conflictDosen)) {
                $dosenName = User::whereKey($conflictDosen[0])->value('name') ?? 'Dosen terkait';

                return "Bentrok jadwal dosen: {$dosenName} sudah terjadwal pada Seminar Skripsi lain di tanggal {$tanggalIso} ({$other->jam_seminar}).";
            }
        }

        $sidangs = SidangSkripsi::query()
            ->with(['pengajuanSkripsi.mahasiswa:id,name'])
            ->whereDate('tgl_sidang', $tanggalIso)
            ->where('status', '!=', StatusPengajuan::Ditolak)
            ->when($ignoreSidangId, fn ($q) => $q->where('id', '!=', $ignoreSidangId))
            ->get();

        foreach ($sidangs as $other) {
            if (! $this->isTimeOverlapping($jamString, (string) $other->jam_sidang)) {
                continue;
            }

            if ($normalizedRoom !== '' && mb_strtolower(trim((string) $other->ruangan)) === $normalizedRoom) {
                $mhsName = $other->pengajuanSkripsi?->mahasiswa?->name ?? 'mahasiswa lain';

                return "Bentrok jadwal ruangan: Ruang {$other->ruangan} sudah digunakan pada {$tanggalIso} ({$other->jam_sidang}) untuk Sidang Skripsi {$mhsName}.";
            }

            $otherDosenIds = array_filter([
                $other->pengajuanSkripsi?->pembimbing_1_id,
                $other->pengajuanSkripsi?->pembimbing_2_id,
                $other->penguji_1_id,
                $other->penguji_2_id,
            ]);

            $conflictDosen = array_values(array_intersect($targetDosenIds, array_map('intval', $otherDosenIds)));
            if (! empty($conflictDosen)) {
                $dosenName = User::whereKey($conflictDosen[0])->value('name') ?? 'Dosen terkait';

                return "Bentrok jadwal dosen: {$dosenName} sudah terjadwal pada Sidang Skripsi lain di tanggal {$tanggalIso} ({$other->jam_sidang}).";
            }
        }

        $praktikLapangs = PraktikLapang::query()
            ->with(['mahasiswa:id,name'])
            ->whereDate('tgl_seminar_pl', $tanggalIso)
            ->where('status', '!=', StatusPraktikLapang::Ditolak)
            ->when($ignorePlId, fn ($q) => $q->where('id', '!=', $ignorePlId))
            ->get();

        foreach ($praktikLapangs as $other) {
            if (! $this->isTimeOverlapping($jamString, (string) $other->jam_seminar_pl)) {
                continue;
            }

            if ($normalizedRoom !== '' && mb_strtolower(trim((string) $other->ruangan_seminar_pl)) === $normalizedRoom) {
                $mhsName = $other->mahasiswa?->name ?? 'mahasiswa lain';

                return "Bentrok jadwal ruangan: Ruang {$other->ruangan_seminar_pl} sudah digunakan pada {$tanggalIso} ({$other->jam_seminar_pl}) untuk Seminar Praktik Lapang {$mhsName}.";
            }

            if ($other->dpl_id && in_array((int) $other->dpl_id, $targetDosenIds, true)) {
                $dosenName = User::whereKey((int) $other->dpl_id)->value('name') ?? 'Dosen terkait';

                return "Bentrok jadwal dosen: {$dosenName} sudah terjadwal sebagai DPL pada Seminar Praktik Lapang tanggal {$tanggalIso} ({$other->jam_seminar_pl}).";
            }
        }

        return null;
    }

    /**
     * Bandingkan dua string rentang jam (mis. "09:00 - 11:00" vs "09.30 WIB - Selesai").
     */
    public function isTimeOverlapping(string $rangeA, string $rangeB): bool
    {
        [$startA, $endA] = $this->parseTimeRange($rangeA);
        [$startB, $endB] = $this->parseTimeRange($rangeB);

        if ($startA === null || $startB === null) {
            return mb_strtolower(trim($rangeA)) === mb_strtolower(trim($rangeB));
        }

        return $startA < $endB && $startB < $endA;
    }

    /**
     * @return array{0: ?int, 1: int} Menit dari 00:00 [start, end]
     */
    public function parseTimeRange(string $input): array
    {
        if (! preg_match_all('/\b([01]?\d|2[0-3])[:\.]([0-5]\d)\b/', $input, $matches, PREG_SET_ORDER)) {
            return [null, 0];
        }

        $startMinutes = ((int) $matches[0][1] * 60) + (int) $matches[0][2];

        if (isset($matches[1])) {
            $endMinutes = ((int) $matches[1][1] * 60) + (int) $matches[1][2];
            if ($endMinutes > $startMinutes) {
                return [$startMinutes, $endMinutes];
            }
        }

        // Default durasi 90 menit jika hanya jam mulai atau "- Selesai" yang dicantumkan
        return [$startMinutes, $startMinutes + 90];
    }
}
