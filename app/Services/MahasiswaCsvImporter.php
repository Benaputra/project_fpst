<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\ProgramStudi;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class MahasiswaCsvImporter
{
    /**
     * Import data mahasiswa dari file CSV.
     *
     * @return array{total_rows: int, imported: int, skipped: int, errors: array<string>, success: bool}
     */
    public function import(UploadedFile|string $file): array
    {
        $path = $file instanceof UploadedFile ? $file->getRealPath() : $file;

        if (! file_exists($path) || ! is_readable($path)) {
            return [
                'total_rows' => 0,
                'imported' => 0,
                'skipped' => 0,
                'errors' => ['File CSV tidak dapat dibaca atau tidak ditemukan.'],
                'success' => false,
            ];
        }

        $content = file_get_contents($path);
        // Hapus UTF-8 BOM jika ada
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);

        // Pisahkan baris
        $lines = preg_split('/\r\n|\r|\n/', trim($content));
        if (empty($lines)) {
            return [
                'total_rows' => 0,
                'imported' => 0,
                'skipped' => 0,
                'errors' => ['File CSV kosong.'],
                'success' => false,
            ];
        }

        // Deteksi delimiter dari baris pertama (header)
        $firstLine = $lines[0];
        $delimiter = str_contains($firstLine, ';') ? ';' : ',';

        $rawHeader = str_getcsv($firstLine, $delimiter);
        $header = array_map(fn ($col) => strtolower(trim($col)), $rawHeader);

        // Cari index kolom yang relevan
        $nameIndex = $this->findHeaderIndex($header, ['nama', 'name', 'nama_lengkap', 'nama lengkap']);
        $nimIndex = $this->findHeaderIndex($header, ['nim', 'nomor_induk', 'nomor induk']);
        $emailIndex = $this->findHeaderIndex($header, ['email']);
        $prodiIndex = $this->findHeaderIndex($header, ['kode_prodi', 'kode prodi', 'prodi', 'program_studi', 'program studi']);
        $hpIndex = $this->findHeaderIndex($header, ['no_hp', 'hp', 'no hp', 'telepon', 'whatsapp']);
        $passwordIndex = $this->findHeaderIndex($header, ['password', 'sandi', 'pass']);

        if ($nameIndex === null || $nimIndex === null || $emailIndex === null || $prodiIndex === null) {
            return [
                'total_rows' => 0,
                'imported' => 0,
                'skipped' => 0,
                'errors' => [
                    'Header CSV tidak valid. Pastikan terdapat kolom: nama, nim, email, kode_prodi (opsional: no_hp, password).',
                ],
                'success' => false,
            ];
        }

        // Cache prodi untuk lookup cepat
        $allProdi = ProgramStudi::all();
        $prodiMap = [];
        foreach ($allProdi as $p) {
            if ($p->kode) {
                $prodiMap[strtolower(trim($p->kode))] = $p;
            }
            $prodiMap[strtolower(trim($p->nama))] = $p;
        }

        $imported = 0;
        $skipped = 0;
        $errors = [];
        $rowsToInsert = [];
        $seenNims = [];
        $seenEmails = [];

        // Loop baris data
        for ($i = 1; $i < count($lines); $i++) {
            $rowNum = $i + 1;
            $line = trim($lines[$i]);
            if ($line === '') {
                continue;
            }

            $row = str_getcsv($line, $delimiter);
            if (count(array_filter($row)) === 0) {
                continue;
            }

            $nama = isset($row[$nameIndex]) ? trim($row[$nameIndex]) : '';
            $nim = isset($row[$nimIndex]) ? trim($row[$nimIndex]) : '';
            $email = isset($row[$emailIndex]) ? trim($row[$emailIndex]) : '';
            $prodiStr = isset($row[$prodiIndex]) ? strtolower(trim($row[$prodiIndex])) : '';
            $hp = ($hpIndex !== null && isset($row[$hpIndex])) ? trim($row[$hpIndex]) : null;
            $pass = ($passwordIndex !== null && isset($row[$passwordIndex])) ? trim($row[$passwordIndex]) : '';

            // Validasi kelengkapan
            if ($nama === '' || $nim === '' || $email === '' || $prodiStr === '') {
                $errors[] = "Baris {$rowNum}: Data tidak lengkap (Nama, NIM, Email, dan Prodi wajib diisi).";
                $skipped++;
                continue;
            }

            // Validasi format email
            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Baris {$rowNum}: Format email '{$email}' tidak valid.";
                $skipped++;
                continue;
            }

            // Validasi prodi
            if (! isset($prodiMap[$prodiStr])) {
                $errors[] = "Baris {$rowNum}: Program studi/kode '{$row[$prodiIndex]}' tidak ditemukan.";
                $skipped++;
                continue;
            }
            $prodiObj = $prodiMap[$prodiStr];

            // Validasi duplikasi internal file CSV
            if (isset($seenNims[$nim])) {
                $errors[] = "Baris {$rowNum}: NIM '{$nim}' duplikat di dalam file CSV ini.";
                $skipped++;
                continue;
            }
            if (isset($seenEmails[strtolower($email)])) {
                $errors[] = "Baris {$rowNum}: Email '{$email}' duplikat di dalam file CSV ini.";
                $skipped++;
                continue;
            }

            // Validasi duplikasi database
            if (User::where('nomor_induk', $nim)->exists()) {
                $errors[] = "Baris {$rowNum}: NIM '{$nim}' sudah terdaftar di sistem.";
                $skipped++;
                continue;
            }
            if (User::where('email', $email)->exists()) {
                $errors[] = "Baris {$rowNum}: Email '{$email}' sudah terdaftar di sistem.";
                $skipped++;
                continue;
            }

            $seenNims[$nim] = true;
            $seenEmails[strtolower($email)] = true;

            $rowsToInsert[] = [
                'name' => $nama,
                'email' => $email,
                'nomor_induk' => $nim,
                'program_studi_id' => $prodiObj->id,
                'no_hp' => $hp ?: null,
                'password' => Hash::make($pass ?: 'password'),
                'role' => UserRole::Mahasiswa,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Eksekusi insert batch
        if (! empty($rowsToInsert)) {
            DB::transaction(function () use ($rowsToInsert) {
                foreach ($rowsToInsert as $data) {
                    User::create($data);
                }
            });
            $imported = count($rowsToInsert);
        }

        return [
            'total_rows' => count($lines) - 1,
            'imported' => $imported,
            'skipped' => $skipped,
            'errors' => $errors,
            'success' => $imported > 0,
        ];
    }

    /**
     * Cari index header berdasarkan alias.
     *
     * @param array<string> $header
     * @param array<string> $candidates
     */
    private function findHeaderIndex(array $header, array $candidates): ?int
    {
        foreach ($candidates as $cand) {
            $idx = array_search($cand, $header, true);
            if ($idx !== false) {
                return $idx;
            }
        }

        return null;
    }
}
