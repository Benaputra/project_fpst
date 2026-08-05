<?php

namespace Tests\Feature;

use App\Enums\StatusPengajuanJudul;
use App\Models\Dosen;
use App\Models\Mahasiswa;
use App\Models\PengajuanJudul;
use App\Models\ProgramStudi;
use App\Models\Skripsi;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CalonPembimbingUiTest extends TestCase
{
    use DatabaseTransactions;

    public function test_detail_terverifikasi_menampilkan_form_dan_dosen_valid(): void
    {
        $data = $this->dataAkademik();
        $calon1 = Dosen::factory()->create([
            'nama' => 'Dr. Calon Satu',
            'program_studi_id' => $data['programStudi']->id,
        ]);
        $calon2 = Dosen::factory()->create([
            'nama' => 'Dr. Calon Dua',
            'program_studi_id' => $data['programStudi']->id,
        ]);
        $dosenLintasProdi = Dosen::factory()->create(['nama' => 'Dosen Lintas Prodi']);

        $this->actingAs($data['ketuaUser'])
            ->get(route('kaprodi.pengajuan-judul.show', $data['pengajuan']))
            ->assertOk()
            ->assertSee($data['mahasiswa']->nama)
            ->assertSee($data['mahasiswa']->nim)
            ->assertSee($data['pengajuan']->judul)
            ->assertSee('Tetapkan calon pembimbing')
            ->assertSee('name="pembimbing1_id"', false)
            ->assertSee('name="pembimbing2_id"', false)
            ->assertSee($calon1->nama)
            ->assertSee($calon2->nama)
            ->assertDontSee($dosenLintasProdi->nama)
            ->assertSee('Konfirmasi calon pembimbing')
            ->assertSee(route('pengajuan-judul.calon-pembimbing.search', $data['pengajuan']))
            ->assertDontSee('name="status"', false)
            ->assertDontSee('name="pembimbing1_final"', false);
    }

    public function test_form_hanya_tampil_untuk_judul_terverifikasi_yang_belum_diproses(): void
    {
        $dataDiajukan = $this->dataAkademik(StatusPengajuanJudul::Diajukan);

        $this->actingAs($dataDiajukan['ketuaUser'])
            ->get(route('kaprodi.pengajuan-judul.show', $dataDiajukan['pengajuan']))
            ->assertOk()
            ->assertDontSee('name="pembimbing1_id"', false);

        $dataDitolak = $this->dataAkademik(StatusPengajuanJudul::Ditolak);
        $this->actingAs($dataDitolak['ketuaUser'])
            ->get(route('kaprodi.pengajuan-judul.show', $dataDitolak['pengajuan']))
            ->assertOk()
            ->assertDontSee('name="pembimbing1_id"', false);
    }

    public function test_setelah_ditetapkan_ui_menampilkan_ringkasan_read_only(): void
    {
        $data = $this->dataAkademik();
        $calon = Dosen::factory()->create([
            'nama' => 'Dr. Pembimbing Arsip',
            'program_studi_id' => $data['programStudi']->id,
        ]);
        $skripsi = Skripsi::factory()->for($data['pengajuan'], 'pengajuanJudul')->create([
            'nim' => $data['mahasiswa']->nim,
            'judul' => $data['pengajuan']->judul,
        ]);
        $skripsi->kesediaanBimbingan()->create([
            'dosen_id' => $calon->nidn,
            'peran' => 'pembimbing1',
            'siklus' => 1,
        ]);

        $this->actingAs($data['ketuaUser'])
            ->get(route('kaprodi.pengajuan-judul.show', $data['pengajuan']))
            ->assertOk()
            ->assertSee('Calon pembimbing telah ditetapkan')
            ->assertSee($calon->nama)
            ->assertSee($calon->nidn)
            ->assertDontSee('name="pembimbing1_id"', false)
            ->assertDontSee('Tinjau dan tetapkan');
    }

    public function test_pencarian_server_terbatas_pada_prodi_dan_maksimal_dua_puluh(): void
    {
        $data = $this->dataAkademik();
        foreach (range(1, 22) as $nomor) {
            Dosen::factory()->create([
                'nama' => sprintf('Dosen Pencarian %02d', $nomor),
                'program_studi_id' => $data['programStudi']->id,
            ]);
        }
        Dosen::factory()->create(['nama' => 'Dosen Pencarian Lintas Prodi']);

        $response = $this->actingAs($data['ketuaUser'])
            ->getJson(route('pengajuan-judul.calon-pembimbing.search', [
                'pengajuanJudul' => $data['pengajuan'],
                'q' => 'Dosen Pencarian',
            ]))
            ->assertOk()
            ->assertJsonCount(20, 'data');

        $this->assertNotContains(
            'Dosen Pencarian Lintas Prodi',
            collect($response->json('data'))->pluck('nama')->all()
        );

        $target = Dosen::factory()->create([
            'nama' => 'Nama Sangat Khusus',
            'nuptk' => 'NUPTK-UI-UNIK',
            'program_studi_id' => $data['programStudi']->id,
        ]);
        $this->getJson(route('pengajuan-judul.calon-pembimbing.search', [
            'pengajuanJudul' => $data['pengajuan'],
            'q' => 'NUPTK-UI-UNIK',
        ]))
            ->assertOk()
            ->assertJsonPath('data.0.id', $target->nidn)
            ->assertJsonPath('data.0.nama', 'Nama Sangat Khusus');
    }

    public function test_pencarian_dilindungi_policy_dan_validasi_query(): void
    {
        $data = $this->dataAkademik();
        $kaprodiLain = $this->dataAkademik();

        $this->getJson(route('pengajuan-judul.calon-pembimbing.search', $data['pengajuan']))
            ->assertUnauthorized();

        $this->actingAs($kaprodiLain['ketuaUser'])
            ->getJson(route('pengajuan-judul.calon-pembimbing.search', $data['pengajuan']))
            ->assertForbidden();

        $this->actingAs($data['ketuaUser'])
            ->getJson(route('pengajuan-judul.calon-pembimbing.search', [
                'pengajuanJudul' => $data['pengajuan'],
                'q' => str_repeat('x', 101),
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('q');
    }

    public function test_jumlah_query_pencarian_tidak_bertambah_mengikuti_jumlah_dosen(): void
    {
        $data = $this->dataAkademik();
        Dosen::factory()->create(['program_studi_id' => $data['programStudi']->id]);
        $this->actingAs($data['ketuaUser']);
        $jumlahAwal = $this->jumlahQueryPencarian($data['pengajuan']);

        Dosen::factory()->count(15)->create(['program_studi_id' => $data['programStudi']->id]);
        $jumlahSetelahBertambah = $this->jumlahQueryPencarian($data['pengajuan']);

        $this->assertSame($jumlahAwal, $jumlahSetelahBertambah);
    }

    /**
     * @return array{
     *   programStudi: ProgramStudi,
     *   ketuaUser: User,
     *   mahasiswa: Mahasiswa,
     *   pengajuan: PengajuanJudul
     * }
     */
    private function dataAkademik(
        StatusPengajuanJudul $status = StatusPengajuanJudul::Diverifikasi
    ): array {
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
            'status' => $status,
            'catatan_reject' => $status === StatusPengajuanJudul::Ditolak
                ? 'Judul ditolak.'
                : null,
            'diverifikasi_oleh' => $status === StatusPengajuanJudul::Diajukan
                ? null
                : $ketua->nidn,
            'diverifikasi_at' => $status === StatusPengajuanJudul::Diajukan
                ? null
                : now(),
        ]);

        return compact('programStudi', 'ketuaUser', 'mahasiswa', 'pengajuan');
    }

    private function jumlahQueryPencarian(PengajuanJudul $pengajuanJudul): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->getJson(route('pengajuan-judul.calon-pembimbing.search', $pengajuanJudul))->assertOk();
        $jumlah = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $jumlah;
    }
}
