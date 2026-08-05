<?php

namespace Tests\Feature;

use App\Models\Dosen;
use App\Models\Mahasiswa;
use App\Models\ProgramStudi;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PengajuanJudulSchemaTest extends TestCase
{
    use DatabaseTransactions;

    public function test_schema_pengajuan_judul_sesuai_kontrak_kanonis(): void
    {
        $columns = collect(DB::select(<<<'SQL'
            SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'pengajuan_judul'
            ORDER BY ORDINAL_POSITION
            SQL))->keyBy('COLUMN_NAME');

        $this->assertSame([
            'id',
            'nim',
            'judul',
            'status',
            'catatan_reject',
            'diverifikasi_oleh',
            'diverifikasi_at',
            'created_at',
            'updated_at',
        ], $columns->keys()->all());
        $this->assertSame('varchar(20)', $columns['nim']->COLUMN_TYPE);
        $this->assertSame('NO', $columns['nim']->IS_NULLABLE);
        $this->assertSame('diajukan', $columns['status']->COLUMN_DEFAULT);
        $this->assertSame('YES', $columns['catatan_reject']->IS_NULLABLE);
        $this->assertSame('YES', $columns['diverifikasi_oleh']->IS_NULLABLE);
        $this->assertSame('YES', $columns['diverifikasi_at']->IS_NULLABLE);
    }

    public function test_status_awal_selalu_diajukan_dan_field_verifikasi_boleh_null(): void
    {
        ['mahasiswa' => $mahasiswa] = $this->buatDataAkademik();

        $id = DB::table('pengajuan_judul')->insertGetId([
            'nim' => $mahasiswa->nim,
            'judul' => 'Analisis Sistem Administrasi Skripsi',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $pengajuan = DB::table('pengajuan_judul')->find($id);

        $this->assertSame('diajukan', $pengajuan->status);
        $this->assertNull($pengajuan->catatan_reject);
        $this->assertNull($pengajuan->diverifikasi_oleh);
        $this->assertNull($pengajuan->diverifikasi_at);
    }

    public function test_satu_mahasiswa_hanya_memiliki_satu_pengajuan(): void
    {
        ['mahasiswa' => $mahasiswa] = $this->buatDataAkademik();
        $this->buatPengajuan($mahasiswa);

        $this->expectException(QueryException::class);

        $this->buatPengajuan($mahasiswa, 'Judul Kedua');
    }

    public function test_nim_pengajuan_harus_merujuk_ke_mahasiswa_valid(): void
    {
        $this->expectException(QueryException::class);

        DB::table('pengajuan_judul')->insert([
            'nim' => 'NIM-TIDAK-ADA',
            'judul' => 'Pengajuan tanpa mahasiswa',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_mahasiswa_dengan_pengajuan_tidak_dapat_dihapus(): void
    {
        ['mahasiswa' => $mahasiswa] = $this->buatDataAkademik();
        $this->buatPengajuan($mahasiswa);

        $this->expectException(QueryException::class);

        DB::table('mahasiswa')->where('nim', $mahasiswa->nim)->delete();
    }

    public function test_verifikator_yang_tercatat_tidak_dapat_dihapus(): void
    {
        ['dosen' => $dosen, 'mahasiswa' => $mahasiswa] = $this->buatDataAkademik();
        DB::table('pengajuan_judul')->insert([
            'nim' => $mahasiswa->nim,
            'judul' => 'Pengajuan yang telah diputuskan',
            'status' => 'diverifikasi',
            'diverifikasi_oleh' => $dosen->nidn,
            'diverifikasi_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->expectException(QueryException::class);

        DB::table('dosen')->where('nidn', $dosen->nidn)->delete();
    }

    /**
     * @return array{dosen: Dosen, mahasiswa: Mahasiswa}
     */
    private function buatDataAkademik(): array
    {
        $programStudi = ProgramStudi::factory()->create();
        $dosen = Dosen::factory()->create([
            'program_studi_id' => $programStudi->id,
        ]);
        $mahasiswa = Mahasiswa::factory()->create([
            'program_studi_id' => $programStudi->id,
            'pembimbing_akademik_id' => $dosen->nidn,
        ]);

        return compact('dosen', 'mahasiswa');
    }

    private function buatPengajuan(Mahasiswa $mahasiswa, string $judul = 'Judul Pertama'): void
    {
        DB::table('pengajuan_judul')->insert([
            'nim' => $mahasiswa->nim,
            'judul' => $judul,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
