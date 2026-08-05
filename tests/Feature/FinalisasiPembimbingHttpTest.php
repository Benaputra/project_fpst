<?php

namespace Tests\Feature;

use App\Enums\HasilKesediaanBimbingan;
use App\Enums\StatusDokumenPengajuan;
use App\Enums\StatusKesediaanBimbingan;
use App\Models\DokumenPengajuan;
use App\Models\Dosen;
use App\Models\KesediaanBimbingan;
use App\Models\Mahasiswa;
use App\Models\PengajuanJudul;
use App\Models\ProgramStudi;
use App\Models\Skripsi;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class FinalisasiPembimbingHttpTest extends TestCase
{
    use DatabaseTransactions;

    public function test_endpoint_menolak_role_lain_dan_tidak_memercayai_field_request(): void
    {
        $data = $this->dataSiap();
        $url = route('skripsi.finalisasi-pembimbing.store', $data['skripsi']);
        $this->post($url)->assertRedirect(route('login'));
        $this->actingAs(User::factory()->adminUtama()->create())
            ->post($url)
            ->assertForbidden();

        $this->actingAs($data['ketuaUser'])
            ->from('/dashboard')
            ->post($url, [
                'pembimbing1_id' => 'ID-BEBAS',
                'pembimbing2_id' => 'ID-BEBAS',
                'status' => 'selesai',
            ])
            ->assertRedirect('/dashboard')
            ->assertSessionHas('status');

        $this->assertDatabaseHas('skripsi', [
            'id' => $data['skripsi']->id,
            'pembimbing1_id' => $data['kesediaan']->dosen_id,
            'pembimbing2_id' => null,
            'status' => 'bimbingan_aktif',
        ]);
    }

    /** @return array<string, mixed> */
    private function dataSiap(): array
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
        $verifikator = User::factory()->adminUtama()->create();
        $kesediaan = KesediaanBimbingan::factory()->for($skripsi)->create([
            'dosen_id' => Dosen::factory()->create([
                'program_studi_id' => $programStudi->id,
            ])->nidn,
            'status' => StatusKesediaanBimbingan::Diterima,
            'hasil' => HasilKesediaanBimbingan::Bersedia,
            'diverifikasi_oleh' => $verifikator->id,
            'diverifikasi_at' => now(),
        ]);
        DokumenPengajuan::factory()->for($kesediaan, 'documentable')->create([
            'status' => StatusDokumenPengajuan::Terverifikasi,
            'verified_by' => $verifikator->id,
            'verified_at' => now(),
        ]);

        return compact('ketuaUser', 'skripsi', 'kesediaan');
    }
}
