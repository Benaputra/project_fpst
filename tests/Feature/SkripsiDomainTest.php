<?php

namespace Tests\Feature;

use App\Enums\HasilKesediaanBimbingan;
use App\Enums\PeranKesediaanBimbingan;
use App\Enums\StatusKesediaanBimbingan;
use App\Enums\StatusSkripsi;
use App\Models\Dosen;
use App\Models\KesediaanBimbingan;
use App\Models\PengajuanJudul;
use App\Models\Skripsi;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SkripsiDomainTest extends TestCase
{
    use DatabaseTransactions;

    public function test_factory_skripsi_membuat_snapshot_dengan_status_awal_kanonis(): void
    {
        $skripsi = Skripsi::factory()->create();

        $this->assertSame(StatusSkripsi::MenungguKesediaanPembimbing, $skripsi->status);
        $this->assertSame($skripsi->pengajuanJudul->judul, $skripsi->judul);
        $this->assertSame($skripsi->pengajuanJudul->nim, $skripsi->nim);
        $this->assertTrue($skripsi->mahasiswa->is($skripsi->pengajuanJudul->mahasiswa));
        $this->assertTrue($skripsi->pengajuanJudul->skripsi->is($skripsi));
        $this->assertTrue($skripsi->mahasiswa->skripsi->is($skripsi));
        $this->assertNull($skripsi->pembimbing1);
        $this->assertNull($skripsi->pembimbing2);
    }

    public function test_factory_kesediaan_membentuk_relasi_dan_cast_kanonis(): void
    {
        $kesediaan = KesediaanBimbingan::factory()->create();

        $this->assertSame(PeranKesediaanBimbingan::Pembimbing1, $kesediaan->peran);
        $this->assertSame(StatusKesediaanBimbingan::Ditunjuk, $kesediaan->status);
        $this->assertNull($kesediaan->hasil);
        $this->assertSame(1, $kesediaan->siklus);
        $this->assertTrue($kesediaan->skripsi->kesediaanBimbingan->contains($kesediaan));
        $this->assertTrue($kesediaan->dosen->kesediaanBimbingan->contains($kesediaan));
    }

    public function test_relasi_pembimbing_final_dan_verifikator_terhubung_dua_arah(): void
    {
        $pembimbing1 = Dosen::factory()->create();
        $pembimbing2 = Dosen::factory()->create();
        $verifikator = User::factory()->adminProdi()->create();
        $skripsi = Skripsi::factory()->create([
            'pembimbing1_id' => $pembimbing1->nidn,
            'pembimbing2_id' => $pembimbing2->nidn,
            'status' => StatusSkripsi::BimbinganAktif,
        ]);
        $kesediaan = KesediaanBimbingan::factory()->for($skripsi)->create([
            'dosen_id' => $pembimbing1->nidn,
            'status' => StatusKesediaanBimbingan::Diterima,
            'hasil' => HasilKesediaanBimbingan::Bersedia,
            'diverifikasi_oleh' => $verifikator->id,
            'diverifikasi_at' => now(),
        ]);

        $this->assertTrue($skripsi->pembimbing1->is($pembimbing1));
        $this->assertTrue($skripsi->pembimbing2->is($pembimbing2));
        $this->assertTrue($pembimbing1->skripsiSebagaiPembimbing1->contains($skripsi));
        $this->assertTrue($pembimbing2->skripsiSebagaiPembimbing2->contains($skripsi));
        $this->assertTrue($kesediaan->verifikator->is($verifikator));
        $this->assertTrue($verifikator->kesediaanBimbinganDiverifikasi->contains($kesediaan));
        $this->assertInstanceOf(Carbon::class, $kesediaan->diverifikasi_at);
    }

    public function test_mass_assignment_tidak_dapat_menetapkan_status_hasil_atau_pembimbing_final(): void
    {
        $skripsi = new Skripsi([
            'pengajuan_judul_id' => 99,
            'nim' => 'NIM-AMAN',
            'judul' => 'Snapshot aman',
            'pembimbing1_id' => 'DOSEN-MANIPULASI',
            'status' => StatusSkripsi::Selesai->value,
        ]);
        $kesediaan = new KesediaanBimbingan([
            'skripsi_id' => 99,
            'dosen_id' => 'DOSEN-CALON',
            'peran' => PeranKesediaanBimbingan::Pembimbing1->value,
            'siklus' => 1,
            'status' => StatusKesediaanBimbingan::Diterima->value,
            'hasil' => HasilKesediaanBimbingan::Bersedia->value,
            'diverifikasi_oleh' => 10,
        ]);

        $this->assertNull($skripsi->pembimbing1_id);
        $this->assertNull($skripsi->status);
        $this->assertNull($kesediaan->status);
        $this->assertNull($kesediaan->hasil);
        $this->assertNull($kesediaan->diverifikasi_oleh);
    }

    public function test_skripsi_tetap_menyimpan_snapshot_saat_judul_pengajuan_berubah(): void
    {
        $pengajuan = PengajuanJudul::factory()->diverifikasi()->create();
        $skripsi = Skripsi::factory()->for($pengajuan, 'pengajuanJudul')->create([
            'nim' => $pengajuan->nim,
            'judul' => $pengajuan->judul,
        ]);
        $snapshot = $skripsi->judul;

        $pengajuan->forceFill(['judul' => 'Judul sumber berubah'])->save();

        $this->assertSame($snapshot, $skripsi->fresh()->judul);
        $this->assertNotSame($pengajuan->fresh()->judul, $skripsi->fresh()->judul);
    }
}
