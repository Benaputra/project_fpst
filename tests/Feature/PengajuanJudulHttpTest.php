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

class PengajuanJudulHttpTest extends TestCase
{
    use DatabaseTransactions;

    public function test_guest_dialihkan_dan_tidak_dapat_mengubah_data(): void
    {
        $this->post(route('pengajuan-judul.store'), ['judul' => 'Judul guest'])
            ->assertRedirect(route('login'));

        $this->assertDatabaseCount('pengajuan_judul', 0);
    }

    public function test_mahasiswa_mengajukan_judul_tanpa_dapat_memanipulasi_nim_atau_status(): void
    {
        $data = $this->buatPengajuanData(buatPengajuan: false);

        $this->actingAs($data['mahasiswaUser'])
            ->from('/')
            ->post(route('pengajuan-judul.store'), [
                'judul' => 'Judul dari mahasiswa',
                'nim' => 'NIM-PALSU',
                'status' => StatusPengajuanJudul::Diverifikasi->value,
                'diverifikasi_oleh' => $data['ketua']->nidn,
            ])
            ->assertRedirect('/')
            ->assertSessionHas('status', 'Judul berhasil diajukan.');

        $this->assertDatabaseHas('pengajuan_judul', [
            'nim' => $data['mahasiswa']->nim,
            'judul' => 'Judul dari mahasiswa',
            'status' => StatusPengajuanJudul::Diajukan->value,
            'diverifikasi_oleh' => null,
        ]);
        $this->assertDatabaseMissing('pengajuan_judul', ['nim' => 'NIM-PALSU']);
    }

    public function test_pengajuan_awal_memvalidasi_judul(): void
    {
        $data = $this->buatPengajuanData(buatPengajuan: false);

        $this->actingAs($data['mahasiswaUser'])
            ->from('/')
            ->post(route('pengajuan-judul.store'), ['judul' => ''])
            ->assertRedirect('/')
            ->assertSessionHasErrors('judul');

        $this->assertDatabaseCount('pengajuan_judul', 0);
    }

    public function test_mahasiswa_memperbaiki_pengajuan_ditolak_miliknya(): void
    {
        $data = $this->buatPengajuanData(StatusPengajuanJudul::Ditolak);

        $this->actingAs($data['mahasiswaUser'])
            ->from('/')
            ->put(route('pengajuan-judul.update', $data['pengajuan']), [
                'judul' => 'Judul hasil perbaikan',
                'nim' => 'NIM-PALSU',
                'status' => StatusPengajuanJudul::Diverifikasi->value,
            ])
            ->assertRedirect('/')
            ->assertSessionHas('status', 'Perbaikan judul berhasil diajukan.');

        $data['pengajuan']->refresh();
        $this->assertSame($data['mahasiswa']->nim, $data['pengajuan']->nim);
        $this->assertSame(StatusPengajuanJudul::Diajukan, $data['pengajuan']->status);
        $this->assertNull($data['pengajuan']->diverifikasi_oleh);
    }

    public function test_mahasiswa_lain_ditolak_saat_memanipulasi_id_pengajuan(): void
    {
        $data = $this->buatPengajuanData(StatusPengajuanJudul::Ditolak);
        $userLain = User::factory()->mahasiswa()->create();
        Mahasiswa::factory()->create([
            'program_studi_id' => $data['programStudi']->id,
            'pembimbing_akademik_id' => $data['ketua']->nidn,
            'user_id' => $userLain->id,
        ]);

        $this->actingAs($userLain)
            ->put(route('pengajuan-judul.update', $data['pengajuan']), [
                'judul' => 'Manipulasi ID',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('pengajuan_judul', ['judul' => 'Manipulasi ID']);
    }

    public function test_kaprodi_terkait_menerima_tanpa_dapat_memanipulasi_verifikator(): void
    {
        $data = $this->buatPengajuanData();

        $this->actingAs($data['ketuaUser'])
            ->from('/')
            ->post(route('pengajuan-judul.terima', $data['pengajuan']), [
                'status' => StatusPengajuanJudul::Ditolak->value,
                'diverifikasi_oleh' => 'NIDN-PALSU',
            ])
            ->assertRedirect('/')
            ->assertSessionHas('status', 'Judul berhasil diterima.');

        $data['pengajuan']->refresh();
        $this->assertSame(StatusPengajuanJudul::Diverifikasi, $data['pengajuan']->status);
        $this->assertSame($data['ketua']->nidn, $data['pengajuan']->diverifikasi_oleh);
    }

    public function test_kaprodi_lintas_prodi_dan_dosen_biasa_ditolak(): void
    {
        $data = $this->buatPengajuanData();
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

        $this->actingAs($kaprodiLainUser)
            ->post(route('pengajuan-judul.terima', $data['pengajuan']))
            ->assertForbidden();
        $this->actingAs($dosenBiasaUser)
            ->post(route('pengajuan-judul.tolak', $data['pengajuan']), ['alasan' => 'Tidak sah'])
            ->assertForbidden();

        $this->assertDatabaseHas('pengajuan_judul', [
            'id' => $data['pengajuan']->id,
            'status' => StatusPengajuanJudul::Diajukan->value,
        ]);
    }

    public function test_penolakan_memvalidasi_alasan(): void
    {
        $data = $this->buatPengajuanData();

        $this->actingAs($data['ketuaUser'])
            ->from('/')
            ->post(route('pengajuan-judul.tolak', $data['pengajuan']), ['alasan' => ''])
            ->assertRedirect('/')
            ->assertSessionHasErrors('alasan');

        $this->assertDatabaseHas('pengajuan_judul', [
            'id' => $data['pengajuan']->id,
            'status' => StatusPengajuanJudul::Diajukan->value,
        ]);
    }

    /**
     * @return array{
     *     programStudi: ProgramStudi,
     *     ketuaUser: User,
     *     ketua: Dosen,
     *     mahasiswaUser: User,
     *     mahasiswa: Mahasiswa,
     *     pengajuan: PengajuanJudul|null
     * }
     */
    private function buatPengajuanData(
        StatusPengajuanJudul $status = StatusPengajuanJudul::Diajukan,
        bool $buatPengajuan = true
    ): array {
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
        $pengajuan = $buatPengajuan
            ? PengajuanJudul::factory()->create([
                'nim' => $mahasiswa->nim,
                'status' => $status,
                'catatan_reject' => $status === StatusPengajuanJudul::Ditolak
                    ? 'Perbaiki judul.'
                    : null,
                'diverifikasi_oleh' => $status === StatusPengajuanJudul::Diajukan
                    ? null
                    : $ketua->nidn,
                'diverifikasi_at' => $status === StatusPengajuanJudul::Diajukan
                    ? null
                    : now(),
            ])
            : null;

        return compact(
            'programStudi',
            'ketuaUser',
            'ketua',
            'mahasiswaUser',
            'mahasiswa',
            'pengajuan'
        );
    }
}
