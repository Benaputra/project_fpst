<?php

namespace Tests\Feature;

use App\Enums\StatusPengajuanJudul;
use App\Models\Dosen;
use App\Models\Mahasiswa;
use App\Models\PengajuanJudul;
use App\Models\ProgramStudi;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PengajuanJudulModelTest extends TestCase
{
    use DatabaseTransactions;

    public function test_factory_menghasilkan_pengajuan_valid_dengan_cast_kanonis(): void
    {
        $pengajuan = PengajuanJudul::factory()->create();

        $this->assertSame(StatusPengajuanJudul::Diajukan, $pengajuan->status);
        $this->assertInstanceOf(Mahasiswa::class, $pengajuan->mahasiswa);
        $this->assertNull($pengajuan->verifikator);
        $this->assertNull($pengajuan->diverifikasi_at);
    }

    public function test_relasi_mahasiswa_dan_dosen_verifikator_terhubung_dua_arah(): void
    {
        $programStudi = ProgramStudi::factory()->create();
        $dosen = Dosen::factory()->create([
            'program_studi_id' => $programStudi->id,
        ]);
        $mahasiswa = Mahasiswa::factory()->create([
            'program_studi_id' => $programStudi->id,
            'pembimbing_akademik_id' => $dosen->nidn,
        ]);
        $pengajuan = PengajuanJudul::factory()
            ->for($mahasiswa, 'mahasiswa')
            ->diverifikasi($dosen)
            ->create();

        $this->assertTrue($pengajuan->mahasiswa->is($mahasiswa));
        $this->assertTrue($pengajuan->verifikator->is($dosen));
        $this->assertTrue($mahasiswa->pengajuanJudul->is($pengajuan));
        $this->assertTrue($dosen->pengajuanJudulDiverifikasi->contains($pengajuan));
        $this->assertSame(StatusPengajuanJudul::Diverifikasi, $pengajuan->status);
        $this->assertNotNull($pengajuan->diverifikasi_at);
    }

    public function test_mass_assignment_tidak_dapat_mengubah_nim_atau_status(): void
    {
        $pengajuan = new PengajuanJudul([
            'nim' => 'NIM-MANIPULASI',
            'judul' => 'Judul yang diperbolehkan',
            'status' => StatusPengajuanJudul::Diverifikasi->value,
        ]);

        $this->assertNull($pengajuan->nim);
        $this->assertNull($pengajuan->status);
        $this->assertSame('Judul yang diperbolehkan', $pengajuan->judul);
    }

    public function test_factory_tetap_mematuhi_unique_satu_mahasiswa(): void
    {
        $mahasiswa = Mahasiswa::factory()->create();
        PengajuanJudul::factory()->for($mahasiswa, 'mahasiswa')->create();

        $this->expectException(QueryException::class);

        PengajuanJudul::factory()->for($mahasiswa, 'mahasiswa')->create();
    }
}
