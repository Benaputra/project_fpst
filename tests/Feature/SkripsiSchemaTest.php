<?php

namespace Tests\Feature;

use App\Enums\PeranKesediaanBimbingan;
use App\Models\Dosen;
use App\Models\PengajuanJudul;
use App\Models\Skripsi;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SkripsiSchemaTest extends TestCase
{
    use DatabaseTransactions;

    public function test_schema_skripsi_sesuai_kontrak_kanonis_fase_dua(): void
    {
        $columns = $this->columns('skripsi');

        $this->assertSame([
            'id',
            'pengajuan_judul_id',
            'nim',
            'judul',
            'pembimbing1_id',
            'pembimbing2_id',
            'status',
            'created_at',
            'updated_at',
        ], $columns->keys()->all());
        $this->assertSame('bigint unsigned', $columns['pengajuan_judul_id']->COLUMN_TYPE);
        $this->assertSame('varchar(20)', $columns['nim']->COLUMN_TYPE);
        $this->assertSame('YES', $columns['pembimbing1_id']->IS_NULLABLE);
        $this->assertSame('YES', $columns['pembimbing2_id']->IS_NULLABLE);
        $this->assertSame('menunggu_kesediaan_pembimbing', $columns['status']->COLUMN_DEFAULT);
    }

    public function test_schema_kesediaan_bimbingan_sesuai_kontrak_kanonis(): void
    {
        $columns = $this->columns('kesediaan_bimbingan');

        $this->assertSame([
            'id',
            'skripsi_id',
            'dosen_id',
            'peran',
            'siklus',
            'status',
            'hasil',
            'catatan_mahasiswa',
            'catatan_verifikasi',
            'uploaded_at',
            'diverifikasi_oleh',
            'diverifikasi_at',
            'created_at',
            'updated_at',
        ], $columns->keys()->all());
        $this->assertSame('int unsigned', $columns['siklus']->COLUMN_TYPE);
        $this->assertSame('ditunjuk', $columns['status']->COLUMN_DEFAULT);
        $this->assertSame('YES', $columns['hasil']->IS_NULLABLE);
        $this->assertSame('YES', $columns['diverifikasi_oleh']->IS_NULLABLE);
    }

    public function test_skripsi_awal_memiliki_snapshot_dan_pembimbing_final_belum_diisi(): void
    {
        $pengajuan = PengajuanJudul::factory()->diverifikasi()->create();

        $id = DB::table('skripsi')->insertGetId([
            'pengajuan_judul_id' => $pengajuan->id,
            'nim' => $pengajuan->nim,
            'judul' => $pengajuan->judul,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $skripsi = DB::table('skripsi')->find($id);

        $this->assertSame('menunggu_kesediaan_pembimbing', $skripsi->status);
        $this->assertSame($pengajuan->judul, $skripsi->judul);
        $this->assertNull($skripsi->pembimbing1_id);
        $this->assertNull($skripsi->pembimbing2_id);
    }

    public function test_satu_pengajuan_dan_mahasiswa_hanya_memiliki_satu_skripsi(): void
    {
        $skripsi = Skripsi::factory()->create();

        try {
            Skripsi::factory()->create([
                'pengajuan_judul_id' => $skripsi->pengajuan_judul_id,
            ]);
            $this->fail('Unique pengajuan_judul_id tidak bekerja.');
        } catch (QueryException) {
            $this->assertDatabaseCount('skripsi', 1);
        }

        $pengajuanLain = PengajuanJudul::factory()->diverifikasi()->create();
        $this->expectException(QueryException::class);

        Skripsi::factory()->create([
            'pengajuan_judul_id' => $pengajuanLain->id,
            'nim' => $skripsi->nim,
        ]);
    }

    public function test_peran_dan_siklus_hanya_unik_dalam_skripsi_yang_sama(): void
    {
        $skripsi = Skripsi::factory()->create();
        $dosen1 = Dosen::factory()->create();
        $dosen2 = Dosen::factory()->create();

        DB::table('kesediaan_bimbingan')->insert($this->kesediaan($skripsi, $dosen1));

        $this->expectException(QueryException::class);
        DB::table('kesediaan_bimbingan')->insert($this->kesediaan($skripsi, $dosen2));
    }

    public function test_siklus_harus_bilangan_positif(): void
    {
        $skripsi = Skripsi::factory()->create();
        $dosen = Dosen::factory()->create();

        $this->expectException(QueryException::class);

        DB::table('kesediaan_bimbingan')->insert($this->kesediaan($skripsi, $dosen, 0));
    }

    public function test_foreign_key_menjaga_riwayat_fase_dua(): void
    {
        $skripsi = Skripsi::factory()->create();
        $dosen = Dosen::factory()->create();
        DB::table('kesediaan_bimbingan')->insert($this->kesediaan($skripsi, $dosen));

        try {
            DB::table('dosen')->where('nidn', $dosen->nidn)->delete();
            $this->fail('Dosen calon yang memiliki riwayat dapat dihapus.');
        } catch (QueryException) {
            $this->assertDatabaseHas('dosen', ['nidn' => $dosen->nidn]);
        }

        $this->expectException(QueryException::class);
        DB::table('pengajuan_judul')->where('id', $skripsi->pengajuan_judul_id)->delete();
    }

    /**
     * @return Collection<string, object>
     */
    private function columns(string $table): Collection
    {
        return collect(DB::select(<<<'SQL'
            SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
            ORDER BY ORDINAL_POSITION
            SQL, [$table]))->keyBy('COLUMN_NAME');
    }

    /**
     * @return array<string, mixed>
     */
    private function kesediaan(Skripsi $skripsi, Dosen $dosen, int $siklus = 1): array
    {
        return [
            'skripsi_id' => $skripsi->id,
            'dosen_id' => $dosen->nidn,
            'peran' => PeranKesediaanBimbingan::Pembimbing1->value,
            'siklus' => $siklus,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
