<?php

namespace Tests\Feature;

use App\Enums\StatusPengajuanJudul;
use App\Enums\StatusSkripsi;
use App\Models\Dosen;
use App\Models\Mahasiswa;
use App\Models\PengajuanJudul;
use App\Models\ProgramStudi;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class TetapkanCalonPembimbingHttpTest extends TestCase
{
    use DatabaseTransactions;

    public function test_guest_dialihkan_ke_login(): void
    {
        $data = $this->dataAkademik();

        $this->post(route('pengajuan-judul.calon-pembimbing.store', $data['pengajuan']), [
            'pembimbing1_id' => $data['calon1']->nidn,
        ])->assertRedirect(route('login'));

        $this->assertDatabaseCount('skripsi', 0);
    }

    public function test_kaprodi_terkait_dapat_menetapkan_calon_melalui_endpoint_tipis(): void
    {
        $data = $this->dataAkademik();

        $this->actingAs($data['ketuaUser'])
            ->from(route('kaprodi.pengajuan-judul.show', $data['pengajuan']))
            ->post(route('pengajuan-judul.calon-pembimbing.store', $data['pengajuan']), [
                'pembimbing1_id' => $data['calon1']->nidn,
                'pembimbing2_id' => $data['calon2']->nidn,
                'status' => StatusSkripsi::Selesai->value,
                'nim' => 'NIM-PALSU',
            ])
            ->assertRedirect(route('kaprodi.pengajuan-judul.show', $data['pengajuan']))
            ->assertSessionHas('status', 'Calon pembimbing berhasil ditetapkan.');

        $this->assertDatabaseHas('skripsi', [
            'pengajuan_judul_id' => $data['pengajuan']->id,
            'nim' => $data['mahasiswa']->nim,
            'status' => StatusSkripsi::MenungguKesediaanPembimbing->value,
            'pembimbing1_id' => null,
            'pembimbing2_id' => null,
        ]);
        $this->assertDatabaseMissing('skripsi', ['nim' => 'NIM-PALSU']);
    }

    public function test_request_memvalidasi_p1_wajib_dan_calon_tidak_boleh_sama(): void
    {
        $data = $this->dataAkademik();

        $this->actingAs($data['ketuaUser'])
            ->from('/')
            ->post(route('pengajuan-judul.calon-pembimbing.store', $data['pengajuan']), [])
            ->assertRedirect('/')
            ->assertSessionHasErrors('pembimbing1_id');

        $this->actingAs($data['ketuaUser'])
            ->from('/')
            ->post(route('pengajuan-judul.calon-pembimbing.store', $data['pengajuan']), [
                'pembimbing1_id' => $data['calon1']->nidn,
                'pembimbing2_id' => $data['calon1']->nidn,
            ])
            ->assertRedirect('/')
            ->assertSessionHasErrors('pembimbing2_id');

        $this->assertDatabaseCount('skripsi', 0);
    }

    public function test_policy_membatasi_kaprodi_pada_prodi_terkait(): void
    {
        $data = $this->dataAkademik();
        $prodiLain = ProgramStudi::factory()->create();
        $userLain = User::factory()->dosen()->create();
        $ketuaLain = Dosen::factory()->create([
            'program_studi_id' => $prodiLain->id,
            'user_id' => $userLain->id,
        ]);
        $prodiLain->update(['ketua_prodi_id' => $ketuaLain->nidn]);

        $this->assertTrue(Gate::forUser($data['ketuaUser'])->allows(
            'tetapkanCalonPembimbing',
            $data['pengajuan']
        ));
        $this->assertFalse(Gate::forUser($userLain)->allows(
            'tetapkanCalonPembimbing',
            $data['pengajuan']
        ));

        $this->actingAs($userLain)
            ->post(route('pengajuan-judul.calon-pembimbing.store', $data['pengajuan']), [
                'pembimbing1_id' => $data['calon1']->nidn,
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('skripsi', 0);
    }

    public function test_endpoint_menolak_dosen_lintas_prodi_dan_double_submit(): void
    {
        $data = $this->dataAkademik();
        $dosenLintasProdi = Dosen::factory()->create();

        $this->actingAs($data['ketuaUser'])
            ->from('/')
            ->post(route('pengajuan-judul.calon-pembimbing.store', $data['pengajuan']), [
                'pembimbing1_id' => $dosenLintasProdi->nidn,
            ])
            ->assertRedirect('/')
            ->assertSessionHasErrors('pembimbing1_id');

        $payload = ['pembimbing1_id' => $data['calon1']->nidn];
        $this->actingAs($data['ketuaUser'])
            ->post(route('pengajuan-judul.calon-pembimbing.store', $data['pengajuan']), $payload)
            ->assertSessionHasNoErrors();
        $this->actingAs($data['ketuaUser'])
            ->from('/')
            ->post(route('pengajuan-judul.calon-pembimbing.store', $data['pengajuan']), $payload)
            ->assertRedirect('/')
            ->assertSessionHasErrors('pengajuan');

        $this->assertDatabaseCount('skripsi', 1);
        $this->assertDatabaseCount('kesediaan_bimbingan', 1);
    }

    /**
     * @return array{
     *   programStudi: ProgramStudi,
     *   ketuaUser: User,
     *   mahasiswa: Mahasiswa,
     *   pengajuan: PengajuanJudul,
     *   calon1: Dosen,
     *   calon2: Dosen
     * }
     */
    private function dataAkademik(): array
    {
        $programStudi = ProgramStudi::factory()->create();
        $ketuaUser = User::factory()->dosen()->create();
        $ketua = Dosen::factory()->create([
            'program_studi_id' => $programStudi->id,
            'user_id' => $ketuaUser->id,
        ]);
        $programStudi->update(['ketua_prodi_id' => $ketua->nidn]);
        $mahasiswa = Mahasiswa::factory()->create([
            'program_studi_id' => $programStudi->id,
            'pembimbing_akademik_id' => $ketua->nidn,
        ]);
        $pengajuan = PengajuanJudul::factory()->create([
            'nim' => $mahasiswa->nim,
            'status' => StatusPengajuanJudul::Diverifikasi,
            'diverifikasi_oleh' => $ketua->nidn,
            'diverifikasi_at' => now(),
        ]);
        $calon1 = Dosen::factory()->create(['program_studi_id' => $programStudi->id]);
        $calon2 = Dosen::factory()->create(['program_studi_id' => $programStudi->id]);

        return compact(
            'programStudi',
            'ketuaUser',
            'mahasiswa',
            'pengajuan',
            'calon1',
            'calon2'
        );
    }
}
