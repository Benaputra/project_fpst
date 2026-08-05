<?php

namespace Tests\Feature;

use App\Enums\HasilKesediaanBimbingan;
use App\Enums\StatusKesediaanBimbingan;
use App\Models\Dosen;
use App\Models\KesediaanBimbingan;
use App\Models\Mahasiswa;
use App\Models\PengajuanJudul;
use App\Models\ProgramStudi;
use App\Models\Skripsi;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GantiCalonPembimbingHttpTest extends TestCase
{
    use DatabaseTransactions;

    public function test_endpoint_hanya_menerima_kaprodi_terkait_dan_input_server_side(): void
    {
        Storage::fake('local');
        $data = $this->dataSiklus();
        $pengganti = Dosen::factory()->create([
            'program_studi_id' => $data['programStudi']->id,
        ]);

        $this->post(route('kesediaan-bimbingan.calon-pengganti.store', $data['ditolak']), [
            'calon_pengganti_id' => $pengganti->nidn,
        ])->assertRedirect(route('login'));

        $admin = User::factory()->adminUtama()->create();
        $this->actingAs($admin)
            ->post(route('kesediaan-bimbingan.calon-pengganti.store', $data['ditolak']), [
                'calon_pengganti_id' => $pengganti->nidn,
            ])->assertForbidden();

        $this->actingAs($data['ketuaUser'])
            ->from('/dashboard')
            ->post(route('kesediaan-bimbingan.calon-pengganti.store', $data['ditolak']), [
                'calon_pengganti_id' => $pengganti->nidn,
                'siklus' => 99,
                'peran' => 'pembimbing2',
                'status' => 'diterima',
            ])
            ->assertRedirect('/dashboard')
            ->assertSessionHas('status');

        $this->assertDatabaseHas('kesediaan_bimbingan', [
            'skripsi_id' => $data['skripsi']->id,
            'dosen_id' => $pengganti->nidn,
            'peran' => 'pembimbing1',
            'siklus' => 2,
            'status' => 'menunggu_upload',
        ]);
    }

    /** @return array<string, mixed> */
    private function dataSiklus(): array
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
        $ditolak = KesediaanBimbingan::factory()->for($skripsi)->create([
            'dosen_id' => Dosen::factory()->create([
                'program_studi_id' => $programStudi->id,
            ])->nidn,
            'status' => StatusKesediaanBimbingan::Ditolak,
            'hasil' => HasilKesediaanBimbingan::TidakBersedia,
        ]);

        return compact('programStudi', 'ketuaUser', 'skripsi', 'ditolak');
    }
}
