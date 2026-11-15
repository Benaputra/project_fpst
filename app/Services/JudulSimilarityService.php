<?php

namespace App\Services;

use App\Enums\StatusPengajuan;
use App\Models\PengajuanSkripsi;
use Illuminate\Support\Collection;

class JudulSimilarityService
{
    /**
     * Kata hubung umum Bahasa Indonesia yang diabaikan saat membandingkan kemiripan topik.
     */
    private const STOPWORDS = [
        'dan', 'atau', 'yang', 'di', 'ke', 'dari', 'pada', 'untuk', 'dengan', 'terhadap',
        'dalam', 'sebagai', 'tentang', 'antara', 'melalui', 'oleh', 'bagi', 'guna',
        'kabupaten', 'kota', 'kecamatan', 'provinsi', 'kalimantan', 'barat', 'studi', 'kasus',
    ];

    /**
     * Hitung skor kemiripan (0.0 - 100.0%) antara dua judul skripsi.
     */
    public function calculateSimilarity(string $judulA, string $judulB): float
    {
        $cleanA = $this->normalize($judulA);
        $cleanB = $this->normalize($judulB);

        if ($cleanA === '' || $cleanB === '') {
            return 0.0;
        }

        if ($cleanA === $cleanB) {
            return 100.0;
        }

        $tokensA = $this->tokenize($cleanA);
        $tokensB = $this->tokenize($cleanB);

        $jaccardScore = 0.0;
        if (! empty($tokensA) && ! empty($tokensB)) {
            $intersection = count(array_intersect($tokensA, $tokensB));
            $union = count(array_unique(array_merge($tokensA, $tokensB)));
            $jaccardScore = $union > 0 ? ($intersection / $union) * 100.0 : 0.0;
        }

        similar_text($cleanA, $cleanB, $charPercent);

        return round(($jaccardScore * 0.65) + ($charPercent * 0.35), 1);
    }

    /**
     * Cari judul skripsi serupa pada repositori lintas program studi (AGT & AGB).
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function findSimilarTitles(
        string $candidateTitle,
        ?int $excludeSkripsiId = null,
        float $minThreshold = 35.0,
        int $limit = 5
    ): Collection {
        if (mb_strlen(trim($candidateTitle)) < 6) {
            return collect();
        }

        $records = PengajuanSkripsi::query()
            ->with(['mahasiswa:id,name', 'programStudi:id,nama,kode'])
            ->where('status', '!=', StatusPengajuan::Ditolak)
            ->when($excludeSkripsiId, fn ($q) => $q->where('id', '!=', $excludeSkripsiId))
            ->get(['id', 'mahasiswa_id', 'program_studi_id', 'judul', 'status', 'created_at']);

        return $records
            ->map(function (PengajuanSkripsi $row) use ($candidateTitle): array {
                $score = $this->calculateSimilarity($candidateTitle, $row->judul);

                return [
                    'skripsi_id' => $row->id,
                    'judul' => $row->judul,
                    'mahasiswa_nama' => $row->mahasiswa?->name ?? '-',
                    'prodi_kode' => $row->programStudi?->kode ?? '-',
                    'prodi_nama' => $row->programStudi?->nama ?? '-',
                    'status' => $row->status->label(),
                    'tahun' => $row->created_at?->year,
                    'skor_kemiripan' => $score,
                ];
            })
            ->filter(fn (array $item): bool => $item['skor_kemiripan'] >= $minThreshold)
            ->sortByDesc('skor_kemiripan')
            ->take($limit)
            ->values();
    }

    private function normalize(string $text): string
    {
        $lower = mb_strtolower(trim($text));
        $alphaNum = preg_replace('/[^a-z0-9\s]/u', ' ', $lower) ?? '';

        return trim(preg_replace('/\s+/', ' ', $alphaNum) ?? '');
    }

    /**
     * @return array<int, string>
     */
    private function tokenize(string $normalized): array
    {
        $parts = explode(' ', $normalized);
        $filtered = array_filter(
            $parts,
            fn (string $word): bool => $word !== '' && ! in_array($word, self::STOPWORDS, true)
        );

        return array_values(array_unique($filtered));
    }
}
