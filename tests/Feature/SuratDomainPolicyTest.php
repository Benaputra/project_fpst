<?php

namespace Tests\Feature;

use App\Enums\JenisSurat;
use App\Enums\StatusSurat;
use App\Models\Dosen;
use App\Models\KesediaanBimbingan;
use App\Models\Mahasiswa;
use App\Models\PengajuanJudul;
use App\Models\ProgramStudi;
use App\Models\Skripsi;
use App\Models\Surat;
use App\Models\User;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class SuratDomainPolicyTest extends TestCase
{
    use DatabaseTransactions;

    public function test_factory_cast_dan_relasi_polymorphic_terhubung_dua_arah(): void
    {
        $surat = Surat::factory()->diterbitkan()->create();
        $kesediaan = $surat->suratable;

        $this->assertInstanceOf(KesediaanBimbingan::class, $kesediaan);
        $this->assertSame(JenisSurat::KesediaanPembimbing, $surat->jenis_surat);
        $this->assertSame(StatusSurat::Diterbitkan, $surat->status);
        $this->assertSame(1, $surat->versi);
        $this->assertNotNull($surat->generated_at);
        $this->assertTrue($kesediaan->surat->contains($surat));
        $this->assertTrue($surat->programStudi->surat->contains($surat));

        $suratSkripsi = Surat::factory()
            ->for($kesediaan->skripsi, 'suratable')
            ->create([
                'program_studi_id' => $surat->program_studi_id,
                'jenis_surat' => JenisSurat::SkBimbingan,
            ]);

        $this->assertInstanceOf(Skripsi::class, $suratSkripsi->suratable);
        $this->assertTrue($kesediaan->skripsi->surat->contains($suratSkripsi));
    }

    public function test_relasi_verifikator_dan_penanda_tangan_terhubung_dua_arah(): void
    {
        $data = $this->dataSurat();
        $surat = $data['surat'];
        $surat->forceFill([
            'verified_by' => $data['adminUtama']->id,
            'verified_at' => now(),
            'signed_by' => $data['ketua']->nidn,
            'signed_at' => now(),
        ])->save();
        $surat->refresh();

        $this->assertTrue($surat->verifikator->is($data['adminUtama']));
        $this->assertTrue($surat->penandaTangan->is($data['ketua']));
        $this->assertTrue($data['adminUtama']->suratDiverifikasi->contains($surat));
        $this->assertTrue($data['ketua']->suratDitandatangani->contains($surat));
        $this->assertNotNull($surat->verified_at);
        $this->assertNotNull($surat->signed_at);
    }

    public function test_mass_assignment_tidak_dapat_mengisi_field_arsip_dan_keputusan(): void
    {
        $this->expectException(MassAssignmentException::class);

        new Surat([
            'suratable_id' => 1,
            'suratable_type' => Skripsi::class,
            'program_studi_id' => 1,
            'jenis_surat' => JenisSurat::SkBimbingan->value,
            'no_surat' => 'MANIPULASI',
            'versi' => 99,
            'status' => StatusSurat::Terverifikasi->value,
            'file_path' => '../../public/file.pdf',
            'file_hash' => str_repeat('a', 64),
            'verified_by' => 1,
            'signed_by' => 'NIDN-MANIPULASI',
        ]);
    }

    public function test_mahasiswa_hanya_dapat_melihat_dan_mengunduh_surat_miliknya(): void
    {
        $data = $this->dataSurat();
        $mahasiswaLain = User::factory()->mahasiswa()->create();
        Mahasiswa::factory()->create(['user_id' => $mahasiswaLain->id]);

        $this->assertTrue(Gate::forUser($data['mahasiswaUser'])->allows('view', $data['surat']));
        $this->assertTrue(Gate::forUser($data['mahasiswaUser'])->allows('download', $data['surat']));
        $this->assertFalse(Gate::forUser($mahasiswaLain)->allows('view', $data['surat']));
        $this->assertFalse(Gate::forUser($mahasiswaLain)->allows('download', $data['surat']));
        $this->assertFalse(Gate::forUser($data['mahasiswaUser'])->allows('verify', $data['surat']));
        $this->assertFalse(Gate::forUser($data['mahasiswaUser'])->allows('sign', $data['surat']));
    }

    public function test_admin_prodi_hanya_dapat_melihat_dan_memverifikasi_prodi_yang_dipetakan(): void
    {
        $data = $this->dataSurat();
        $adminProdi = User::factory()->adminProdi()->create();
        $adminProdi->programStudiAdministrasi()->attach($data['programStudi']);
        $adminProdiLain = User::factory()->adminProdi()->create();
        $adminProdiLain->programStudiAdministrasi()->attach(ProgramStudi::factory()->create());

        $this->assertTrue(Gate::forUser($adminProdi)->allows('view', $data['surat']));
        $this->assertTrue(Gate::forUser($adminProdi)->allows('verify', $data['surat']));
        $this->assertFalse(Gate::forUser($adminProdi)->allows('sign', $data['surat']));
        $this->assertFalse(Gate::forUser($adminProdiLain)->allows('view', $data['surat']));
        $this->assertFalse(Gate::forUser($adminProdiLain)->allows('verify', $data['surat']));
    }

    public function test_kaprodi_terkait_satu_satunya_role_yang_dapat_menandatangani(): void
    {
        $data = $this->dataSurat();
        $prodiLain = ProgramStudi::factory()->create();
        $kaprodiLainUser = User::factory()->dosen()->create();
        $kaprodiLain = Dosen::factory()->create([
            'program_studi_id' => $prodiLain->id,
            'user_id' => $kaprodiLainUser->id,
        ]);
        $prodiLain->update(['ketua_prodi_id' => $kaprodiLain->nidn]);
        $dosenBiasaUser = User::factory()->dosen()->create();
        Dosen::factory()->create([
            'program_studi_id' => $data['programStudi']->id,
            'user_id' => $dosenBiasaUser->id,
        ]);

        $this->assertTrue(Gate::forUser($data['ketuaUser'])->allows('view', $data['surat']));
        $this->assertTrue(Gate::forUser($data['ketuaUser'])->allows('verify', $data['surat']));
        $this->assertTrue(Gate::forUser($data['ketuaUser'])->allows('sign', $data['surat']));
        $this->assertFalse(Gate::forUser($kaprodiLainUser)->allows('sign', $data['surat']));
        $this->assertFalse(Gate::forUser($dosenBiasaUser)->allows('sign', $data['surat']));
        $this->assertFalse(Gate::forUser($data['adminUtama'])->allows('sign', $data['surat']));
    }

    public function test_admin_utama_global_tetapi_tidak_dapat_menjadi_kaprodi(): void
    {
        $data = $this->dataSurat();

        $this->assertTrue(Gate::forUser($data['adminUtama'])->allows('view', $data['surat']));
        $this->assertTrue(Gate::forUser($data['adminUtama'])->allows('verify', $data['surat']));
        $this->assertTrue(Gate::forUser($data['adminUtama'])->allows('download', $data['surat']));
        $this->assertFalse(Gate::forUser($data['adminUtama'])->allows('sign', $data['surat']));
    }

    public function test_ketidaksesuaian_prodi_subjek_menutup_semua_akses(): void
    {
        $data = $this->dataSurat();
        $data['surat']->forceFill([
            'program_studi_id' => ProgramStudi::factory()->create()->id,
        ])->save();

        foreach ([$data['mahasiswaUser'], $data['ketuaUser'], $data['adminUtama']] as $user) {
            $this->assertFalse(Gate::forUser($user)->allows('view', $data['surat']));
            $this->assertFalse(Gate::forUser($user)->allows('verify', $data['surat']));
            $this->assertFalse(Gate::forUser($user)->allows('sign', $data['surat']));
        }
    }

    public function test_surat_tidak_dapat_diubah_atau_dihapus_melalui_policy_generik(): void
    {
        $data = $this->dataSurat();

        foreach ([$data['mahasiswaUser'], $data['ketuaUser'], $data['adminUtama']] as $user) {
            $this->assertFalse(Gate::forUser($user)->allows('update', $data['surat']));
            $this->assertFalse(Gate::forUser($user)->allows('delete', $data['surat']));
        }
    }

    /**
     * @return array{
     *   programStudi: ProgramStudi,
     *   ketuaUser: User,
     *   ketua: Dosen,
     *   mahasiswaUser: User,
     *   surat: Surat,
     *   adminUtama: User
     * }
     */
    private function dataSurat(): array
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
        $kesediaan = KesediaanBimbingan::factory()->for($skripsi)->create([
            'dosen_id' => Dosen::factory()->create([
                'program_studi_id' => $programStudi->id,
            ])->nidn,
        ]);
        $surat = Surat::factory()->for($kesediaan, 'suratable')->create([
            'program_studi_id' => $programStudi->id,
        ]);
        $adminUtama = User::factory()->adminUtama()->create();

        return compact(
            'programStudi',
            'ketuaUser',
            'ketua',
            'mahasiswaUser',
            'surat',
            'adminUtama'
        );
    }
}
