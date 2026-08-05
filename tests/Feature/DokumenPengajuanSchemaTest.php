<?php

namespace Tests\Feature;

use App\Enums\JenisDokumenPengajuan;
use App\Models\DokumenPengajuan;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DokumenPengajuanSchemaTest extends TestCase
{
    use DatabaseTransactions;

    public function test_schema_dokumen_pengajuan_sesuai_kontrak_kanonis(): void
    {
        $columns = collect(DB::select(<<<'SQL'
            SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'dokumen_pengajuan'
            ORDER BY ORDINAL_POSITION
            SQL))->keyBy('COLUMN_NAME');

        $this->assertSame([
            'id',
            'documentable_id',
            'documentable_type',
            'jenis',
            'versi',
            'file_path',
            'file_hash',
            'status',
            'uploaded_by',
            'uploaded_at',
            'verified_by',
            'verified_at',
            'catatan_verifikasi',
            'created_at',
            'updated_at',
        ], $columns->keys()->all());
        $this->assertSame('bigint unsigned', $columns['documentable_id']->COLUMN_TYPE);
        $this->assertSame('int unsigned', $columns['versi']->COLUMN_TYPE);
        $this->assertSame('1', $columns['versi']->COLUMN_DEFAULT);
        $this->assertSame('char(64)', $columns['file_hash']->COLUMN_TYPE);
        $this->assertSame('diunggah', $columns['status']->COLUMN_DEFAULT);
        $this->assertSame('NO', $columns['uploaded_at']->IS_NULLABLE);
        $this->assertSame('YES', $columns['verified_by']->IS_NULLABLE);
        $this->assertSame('YES', $columns['verified_at']->IS_NULLABLE);
    }

    public function test_tabel_proses_tidak_memiliki_foreign_key_balik_dokumen(): void
    {
        foreach (['skripsi', 'kesediaan_bimbingan'] as $table) {
            $this->assertFalse(Schema::hasColumn($table, 'dokumen_pengajuan_id'));
            $this->assertFalse(Schema::hasColumn($table, 'hasil_konsultasi_id'));
        }
    }

    public function test_versi_unik_per_subjek_dan_jenis(): void
    {
        $dokumen = DokumenPengajuan::factory()->create();

        $this->expectException(QueryException::class);
        DokumenPengajuan::factory()->create([
            'documentable_type' => $dokumen->documentable_type,
            'documentable_id' => $dokumen->documentable_id,
            'jenis' => JenisDokumenPengajuan::HasilKonsultasi,
            'versi' => 1,
        ]);
    }

    public function test_versi_harus_positif_dan_hash_harus_sha256(): void
    {
        try {
            DokumenPengajuan::factory()->create(['versi' => 0]);
            $this->fail('Versi nol diterima.');
        } catch (QueryException) {
            $this->assertDatabaseCount('dokumen_pengajuan', 0);
        }

        $this->expectException(QueryException::class);
        DokumenPengajuan::factory()->create(['file_hash' => 'hash-tidak-valid']);
    }

    public function test_pengunggah_dan_verifikator_yang_tercatat_tidak_dapat_dihapus(): void
    {
        $dokumen = DokumenPengajuan::factory()->create();

        $this->expectException(QueryException::class);
        DB::table('users')->where('id', $dokumen->uploaded_by)->delete();
    }
}
