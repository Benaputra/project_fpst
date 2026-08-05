<?php

namespace Tests\Feature;

use App\Models\DokumenPengajuan;
use App\Models\Seminar;
use App\Models\SidangSkripsi;
use App\Models\Surat;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LegacyCleanupParityTest extends TestCase
{
    use DatabaseTransactions;

    public function test_schema_tidak_memiliki_kolom_legacy_yang_dilarang_ditulis(): void
    {
        foreach (['nim', 'judul', 'pembimbing', 'bukti_bayar', 'acc', 'surat_undangan_id'] as $column) {
            $this->assertFalse(Schema::hasColumn('seminar', $column));
        }
        foreach (['penguji1_id', 'penguji2_id', 'tanggal', 'tempat', 'bukti_bayar', 'transkrip', 'toefl', 'surat_undangan_id'] as $column) {
            $this->assertFalse(Schema::hasColumn('skripsi', $column));
        }
    }

    public function test_relasi_kanonis_lengkap_dan_semua_file_tercatat_memiliki_hash_valid(): void
    {
        $this->assertSame(0, Seminar::query()->whereDoesntHave('skripsi')->count());
        $this->assertSame(0, SidangSkripsi::query()->whereDoesntHave('skripsi')->count());

        foreach (DokumenPengajuan::all() as $dokumen) {
            $this->assertNotNull($dokumen->documentable);
            Storage::disk('local')->assertExists($dokumen->file_path);
            $this->assertSame(
                $dokumen->file_hash,
                hash('sha256', Storage::disk('local')->get($dokumen->file_path))
            );
        }
        foreach (Surat::query()->whereNotNull('file_path')->get() as $surat) {
            $this->assertNotNull($surat->suratable);
            Storage::disk('local')->assertExists($surat->file_path);
            $this->assertSame(
                $surat->file_hash,
                hash('sha256', Storage::disk('local')->get($surat->file_path))
            );
        }
    }
}
