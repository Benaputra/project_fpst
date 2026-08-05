<?php

namespace Tests\Feature;

use App\Enums\JenisSurat;
use App\Models\Surat;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SuratSchemaTest extends TestCase
{
    use DatabaseTransactions;

    public function test_schema_surat_sesuai_kontrak_kanonis(): void
    {
        $columns = collect(DB::select(<<<'SQL'
            SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'surat'
            ORDER BY ORDINAL_POSITION
            SQL))->keyBy('COLUMN_NAME');

        $this->assertSame([
            'id',
            'suratable_id',
            'suratable_type',
            'program_studi_id',
            'jenis_surat',
            'no_surat',
            'tujuan_surat',
            'versi',
            'status',
            'file_path',
            'file_hash',
            'generated_at',
            'verified_by',
            'verified_at',
            'signed_by',
            'signed_at',
            'created_at',
            'updated_at',
        ], $columns->keys()->all());
        $this->assertSame('bigint unsigned', $columns['suratable_id']->COLUMN_TYPE);
        $this->assertSame('int unsigned', $columns['versi']->COLUMN_TYPE);
        $this->assertSame('1', $columns['versi']->COLUMN_DEFAULT);
        $this->assertSame('draft', $columns['status']->COLUMN_DEFAULT);
        $this->assertSame('char(64)', $columns['file_hash']->COLUMN_TYPE);
        $this->assertSame('YES', $columns['file_path']->IS_NULLABLE);
        $this->assertSame('YES', $columns['verified_by']->IS_NULLABLE);
        $this->assertSame('YES', $columns['signed_by']->IS_NULLABLE);
    }

    public function test_tabel_proses_tidak_memiliki_foreign_key_balik_surat(): void
    {
        foreach (['skripsi', 'kesediaan_bimbingan'] as $table) {
            $this->assertFalse(Schema::hasColumn($table, 'surat_id'));
            $this->assertFalse(Schema::hasColumn($table, 'surat_undangan_id'));
        }
    }

    public function test_nomor_surat_harus_unik(): void
    {
        $surat = Surat::factory()->create();

        $this->expectException(QueryException::class);
        Surat::factory()->create(['no_surat' => $surat->no_surat]);
    }

    public function test_versi_unik_per_subjek_dan_jenis_surat(): void
    {
        $surat = Surat::factory()->create();

        $this->expectException(QueryException::class);
        Surat::factory()->create([
            'suratable_type' => $surat->suratable_type,
            'suratable_id' => $surat->suratable_id,
            'program_studi_id' => $surat->program_studi_id,
            'jenis_surat' => $surat->jenis_surat,
            'versi' => $surat->versi,
        ]);
    }

    public function test_versi_harus_positif_dan_hash_harus_sha256_lowercase(): void
    {
        try {
            Surat::factory()->create(['versi' => 0]);
            $this->fail('Versi nol diterima database.');
        } catch (QueryException) {
            $this->assertDatabaseCount('surat', 0);
        }

        $this->expectException(QueryException::class);
        Surat::factory()->create(['file_hash' => 'bukan-sha-256']);
    }

    public function test_foreign_key_program_verifikator_dan_penanda_tangan_menjaga_arsip(): void
    {
        $surat = Surat::factory()->diterbitkan()->create();
        $programStudi = $surat->programStudi;

        $this->expectException(QueryException::class);
        DB::table('program_studi')->where('id', $programStudi->id)->delete();
    }

    public function test_jenis_dan_versi_lain_dapat_diarsipkan_pada_subjek_sama(): void
    {
        $surat = Surat::factory()->create();
        Surat::factory()->create([
            'suratable_type' => $surat->suratable_type,
            'suratable_id' => $surat->suratable_id,
            'program_studi_id' => $surat->program_studi_id,
            'jenis_surat' => JenisSurat::KesediaanPembimbing,
            'versi' => 2,
        ]);

        $this->assertDatabaseCount('surat', 2);
    }
}
