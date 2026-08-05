<?php

namespace Tests\Feature;

use App\Enums\JenisDokumenPengajuan;
use App\Enums\StatusDokumenPengajuan;
use App\Models\DokumenPengajuan;
use App\Models\Dosen;
use App\Models\KesediaanBimbingan;
use App\Models\Mahasiswa;
use App\Models\PengajuanJudul;
use App\Models\ProgramStudi;
use App\Models\Skripsi;
use App\Models\User;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class DokumenPengajuanDomainPolicyTest extends TestCase
{
    use DatabaseTransactions;

    public function test_factory_cast_dan_relasi_polymorphic_terhubung_dua_arah(): void
    {
        $data = $this->dataDokumen();
        $dokumen = DokumenPengajuan::factory()->for($data['kesediaan'], 'documentable')->create([
            'uploaded_by' => $data['mahasiswaUser']->id,
        ]);

        $this->assertInstanceOf(KesediaanBimbingan::class, $dokumen->documentable);
        $this->assertSame(JenisDokumenPengajuan::HasilKonsultasi, $dokumen->jenis);
        $this->assertSame(StatusDokumenPengajuan::MenungguVerifikasi, $dokumen->status);
        $this->assertSame(1, $dokumen->versi);
        $this->assertNotNull($dokumen->uploaded_at);
        $this->assertTrue($data['kesediaan']->dokumenPengajuan->contains($dokumen));
        $this->assertTrue($dokumen->pengunggah->is($data['mahasiswaUser']));
        $this->assertTrue($data['mahasiswaUser']->dokumenPengajuanDiunggah->contains($dokumen));
    }

    public function test_mass_assignment_seluruh_metadata_ditolak(): void
    {
        $this->expectException(MassAssignmentException::class);

        new DokumenPengajuan([
            'documentable_id' => 1,
            'file_path' => '../../public/malware.php',
            'status' => StatusDokumenPengajuan::Terverifikasi->value,
            'verified_by' => 1,
        ]);
    }

    public function test_mahasiswa_hanya_melihat_dan_mengunduh_dokumen_miliknya(): void
    {
        $data = $this->dataDokumen();
        $dokumen = DokumenPengajuan::factory()->for($data['kesediaan'], 'documentable')->create([
            'uploaded_by' => $data['mahasiswaUser']->id,
        ]);
        $mahasiswaLain = User::factory()->mahasiswa()->create();
        Mahasiswa::factory()->create(['user_id' => $mahasiswaLain->id]);

        $this->assertTrue(Gate::forUser($data['mahasiswaUser'])->allows('view', $dokumen));
        $this->assertTrue(Gate::forUser($data['mahasiswaUser'])->allows('download', $dokumen));
        $this->assertFalse(Gate::forUser($mahasiswaLain)->allows('view', $dokumen));
        $this->assertFalse(Gate::forUser($mahasiswaLain)->allows('download', $dokumen));
    }

    public function test_admin_dan_kaprodi_hanya_dalam_cakupan_yang_sah(): void
    {
        $data = $this->dataDokumen();
        $dokumen = DokumenPengajuan::factory()->for($data['kesediaan'], 'documentable')->create([
            'uploaded_by' => $data['mahasiswaUser']->id,
        ]);
        $adminProdi = User::factory()->adminProdi()->create();
        $adminProdi->programStudiAdministrasi()->attach($data['programStudi']);
        $adminLain = User::factory()->adminProdi()->create();
        $adminLain->programStudiAdministrasi()->attach(ProgramStudi::factory()->create());
        $adminUtama = User::factory()->adminUtama()->create();

        $this->assertTrue(Gate::forUser($data['ketuaUser'])->allows('view', $dokumen));
        $this->assertTrue(Gate::forUser($adminProdi)->allows('view', $dokumen));
        $this->assertTrue(Gate::forUser($adminUtama)->allows('view', $dokumen));
        $this->assertFalse(Gate::forUser($adminLain)->allows('view', $dokumen));
    }

    public function test_hanya_pemilik_dengan_status_upload_yang_dapat_mengunggah(): void
    {
        $data = $this->dataDokumen();
        $mahasiswaLain = User::factory()->mahasiswa()->create();
        Mahasiswa::factory()->create(['user_id' => $mahasiswaLain->id]);

        $this->assertTrue(Gate::forUser($data['mahasiswaUser'])->allows(
            'uploadHasilKonsultasi',
            $data['kesediaan']
        ));
        $this->assertFalse(Gate::forUser($mahasiswaLain)->allows(
            'uploadHasilKonsultasi',
            $data['kesediaan']
        ));
        $data['kesediaan']->forceFill(['status' => 'menunggu_verifikasi'])->save();
        $this->assertFalse(Gate::forUser($data['mahasiswaUser'])->allows(
            'uploadHasilKonsultasi',
            $data['kesediaan']->fresh()
        ));
    }

    /**
     * @return array{
     *   programStudi: ProgramStudi,
     *   ketuaUser: User,
     *   mahasiswaUser: User,
     *   kesediaan: KesediaanBimbingan
     * }
     */
    private function dataDokumen(): array
    {
        $programStudi = ProgramStudi::factory()->create();
        $ketuaUser = User::factory()->dosen()->create();
        $ketua = Dosen::factory()->create([
            'program_studi_id' => $programStudi->id,
            'user_id' => $ketuaUser->id,
        ]);
        $programStudi->update(['ketua_prodi_id' => $ketua->nidn]);
        $mahasiswaUser = User::factory()->mahasiswa()->create();
        $mahasiswa = Mahasiswa::factory()->create([
            'program_studi_id' => $programStudi->id,
            'pembimbing_akademik_id' => $ketua->nidn,
            'user_id' => $mahasiswaUser->id,
        ]);
        $pengajuan = PengajuanJudul::factory()->diverifikasi($ketua)->create([
            'nim' => $mahasiswa->nim,
        ]);
        $skripsi = Skripsi::factory()->for($pengajuan, 'pengajuanJudul')->create([
            'nim' => $mahasiswa->nim,
            'judul' => $pengajuan->judul,
        ]);
        $calon = Dosen::factory()->create(['program_studi_id' => $programStudi->id]);
        $kesediaan = KesediaanBimbingan::factory()->for($skripsi)->create([
            'dosen_id' => $calon->nidn,
            'status' => 'menunggu_upload',
        ]);

        return compact('programStudi', 'ketuaUser', 'mahasiswaUser', 'kesediaan');
    }
}
