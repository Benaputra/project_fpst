<?php

namespace Tests\Feature;

use App\Enums\StatusPengajuanJudul;
use App\Models\Dosen;
use App\Models\Mahasiswa;
use App\Models\PengajuanJudul;
use App\Models\ProgramStudi;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PengajuanJudulMahasiswaUiTest extends TestCase
{
    use DatabaseTransactions;

    public function test_akun_mahasiswa_belum_terhubung_mendapat_petunjuk_yang_jelas(): void
    {
        $user = User::factory()->mahasiswa()->create();

        $this->actingAs($user)
            ->get(route('mahasiswa.pengajuan-judul.index'))
            ->assertOk()
            ->assertSee('Akun Anda belum terhubung dengan data mahasiswa')
            ->assertDontSee('Ajukan judul pertama');
    }

    public function test_mahasiswa_belum_mengajukan_melihat_form_tanpa_input_nim_atau_status(): void
    {
        $data = $this->buatMahasiswa();

        $this->actingAs($data['user'])
            ->get(route('mahasiswa.pengajuan-judul.index'))
            ->assertOk()
            ->assertSee($data['mahasiswa']->nama)
            ->assertSee($data['mahasiswa']->nim)
            ->assertSee('Ajukan judul pertama')
            ->assertSee('name="judul"', false)
            ->assertDontSee('name="nim"', false)
            ->assertDontSee('name="status"', false);
    }

    public function test_input_invalid_dipertahankan_dan_error_ditampilkan_dekat_field(): void
    {
        $data = $this->buatMahasiswa();
        $judulTerlaluPanjang = str_repeat('A', 1001);

        $this->actingAs($data['user'])
            ->from(route('mahasiswa.pengajuan-judul.index'))
            ->post(route('pengajuan-judul.store'), ['judul' => $judulTerlaluPanjang])
            ->assertRedirect(route('mahasiswa.pengajuan-judul.index'))
            ->assertSessionHasErrors('judul')
            ->assertSessionHasInput('judul', $judulTerlaluPanjang);

        $this->get(route('mahasiswa.pengajuan-judul.index'))
            ->assertOk()
            ->assertSee($judulTerlaluPanjang)
            ->assertSee('field-error', false);
    }

    public function test_pengajuan_diajukan_ditampilkan_read_only(): void
    {
        $data = $this->buatMahasiswa(StatusPengajuanJudul::Diajukan);

        $this->actingAs($data['user'])
            ->get(route('mahasiswa.pengajuan-judul.index'))
            ->assertOk()
            ->assertSee('Menunggu verifikasi')
            ->assertSee($data['pengajuan']->judul)
            ->assertDontSee('Ajukan ulang')
            ->assertDontSee('name="judul"', false);
    }

    public function test_pengajuan_ditolak_menampilkan_alasan_dan_form_perbaikan_tanpa_id(): void
    {
        $data = $this->buatMahasiswa(StatusPengajuanJudul::Ditolak);

        $this->actingAs($data['user'])
            ->get(route('mahasiswa.pengajuan-judul.index'))
            ->assertOk()
            ->assertSee('Perlu diperbaiki')
            ->assertSee('Topik terlalu luas.')
            ->assertSee('Ajukan ulang')
            ->assertSee('action="'.route('mahasiswa.pengajuan-judul.update').'"', false)
            ->assertDontSee('/pengajuan-judul/'.$data['pengajuan']->id, false);
    }

    public function test_pengajuan_diverifikasi_ditampilkan_read_only_dengan_waktu(): void
    {
        $data = $this->buatMahasiswa(StatusPengajuanJudul::Diverifikasi);

        $this->actingAs($data['user'])
            ->get(route('mahasiswa.pengajuan-judul.index'))
            ->assertOk()
            ->assertSee('Terverifikasi')
            ->assertSee('Judul telah diverifikasi')
            ->assertDontSee('name="judul"', false);
    }

    public function test_endpoint_perbaikan_tanpa_id_tetap_dilindungi_policy(): void
    {
        $data = $this->buatMahasiswa(StatusPengajuanJudul::Ditolak);

        $this->actingAs($data['user'])
            ->from(route('mahasiswa.pengajuan-judul.index'))
            ->put(route('mahasiswa.pengajuan-judul.update'), [
                'judul' => 'Judul yang telah diperbaiki',
                'status' => StatusPengajuanJudul::Diverifikasi->value,
            ])
            ->assertRedirect(route('mahasiswa.pengajuan-judul.index'));

        $data['pengajuan']->refresh();
        $this->assertSame('Judul yang telah diperbaiki', $data['pengajuan']->judul);
        $this->assertSame(StatusPengajuanJudul::Diajukan, $data['pengajuan']->status);
    }

    public function test_role_non_mahasiswa_tidak_dapat_membuka_ui_mahasiswa(): void
    {
        $adminUtama = User::factory()->adminUtama()->create();

        $this->actingAs($adminUtama)
            ->get(route('mahasiswa.pengajuan-judul.index'))
            ->assertForbidden();
    }

    /**
     * @return array{user: User, mahasiswa: Mahasiswa, pengajuan: ?PengajuanJudul}
     */
    private function buatMahasiswa(?StatusPengajuanJudul $status = null): array
    {
        $programStudi = ProgramStudi::factory()->create();
        $ketuaUser = User::factory()->dosen()->create();
        $ketua = Dosen::factory()->create([
            'program_studi_id' => $programStudi->id,
            'user_id' => $ketuaUser->id,
        ]);
        $programStudi->update(['ketua_prodi_id' => $ketua->nidn]);
        $user = User::factory()->mahasiswa()->create();
        $mahasiswa = Mahasiswa::factory()->create([
            'program_studi_id' => $programStudi->id,
            'pembimbing_akademik_id' => $ketua->nidn,
            'user_id' => $user->id,
        ]);
        $pengajuan = $status
            ? PengajuanJudul::factory()->create([
                'nim' => $mahasiswa->nim,
                'status' => $status,
                'catatan_reject' => $status === StatusPengajuanJudul::Ditolak
                    ? 'Topik terlalu luas.'
                    : null,
                'diverifikasi_oleh' => $status === StatusPengajuanJudul::Diajukan
                    ? null
                    : $ketua->nidn,
                'diverifikasi_at' => $status === StatusPengajuanJudul::Diajukan
                    ? null
                    : now(),
            ])
            : null;

        return compact('user', 'mahasiswa', 'pengajuan');
    }
}
