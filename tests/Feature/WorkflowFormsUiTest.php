<?php

namespace Tests\Feature;

use App\Enums\HasilKesediaanBimbingan;
use App\Enums\StatusKesediaanBimbingan;
use App\Enums\StatusPengajuanJudul;
use App\Enums\StatusSeminar;
use App\Enums\StatusSidangSkripsi;
use App\Enums\StatusSkripsi;
use App\Models\DokumenPengajuan;
use App\Models\Dosen;
use App\Models\KesediaanBimbingan;
use App\Models\Mahasiswa;
use App\Models\PengajuanJudul;
use App\Models\ProgramStudi;
use App\Models\Seminar;
use App\Models\SidangSkripsi;
use App\Models\Skripsi;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class WorkflowFormsUiTest extends TestCase
{
    use DatabaseTransactions;

    public function test_form_seminar_mengikuti_transisi_status(): void
    {
        $diajukan = $this->alurSkripsi();
        $seminarDiajukan = Seminar::factory()->for($diajukan['skripsi'])->create(['status' => StatusSeminar::Diajukan]);
        $this->actingAs($diajukan['kaprodiUser'])->get(route('portal.seminar.index'))
            ->assertOk()
            ->assertSee(route('seminar.verifikasi.store', $seminarDiajukan), false)
            ->assertSee('name="keputusan"', false);

        $terverifikasi = $this->alurSkripsi();
        $seminarTerverifikasi = Seminar::factory()->for($terverifikasi['skripsi'])->create(['status' => StatusSeminar::Diverifikasi]);
        $this->actingAs($terverifikasi['kaprodiUser'])->get(route('portal.seminar.index'))
            ->assertSee(route('seminar.jadwal.store', $seminarTerverifikasi), false)
            ->assertSee('name="penguji1_id"', false)
            ->assertSee('name="tanggal"', false);

        $terjadwal = $this->alurSkripsi();
        $seminarTerjadwal = Seminar::factory()->for($terjadwal['skripsi'])->create(['status' => StatusSeminar::Dijadwalkan]);
        $this->actingAs($terjadwal['kaprodiUser'])->get(route('portal.seminar.index'))
            ->assertSee(route('seminar.surat.store', $seminarTerjadwal), false)
            ->assertSee('undangan_seminar')
            ->assertSee('surat_tugas_seminar');
    }

    public function test_form_sidang_mengikuti_transisi_status(): void
    {
        $diajukan = $this->alurSkripsi();
        $sidangDiajukan = SidangSkripsi::factory()->for($diajukan['skripsi'])->create(['status' => StatusSidangSkripsi::Diajukan]);
        $this->actingAs($diajukan['kaprodiUser'])->get(route('portal.sidang.index'))
            ->assertOk()
            ->assertSee(route('sidang.verifikasi.store', $sidangDiajukan), false);

        $terverifikasi = $this->alurSkripsi();
        $sidangTerverifikasi = SidangSkripsi::factory()->for($terverifikasi['skripsi'])->create(['status' => StatusSidangSkripsi::Diverifikasi]);
        $this->actingAs($terverifikasi['kaprodiUser'])->get(route('portal.sidang.index'))
            ->assertSee(route('sidang.jadwal.store', $sidangTerverifikasi), false)
            ->assertSee('name="penguji2_id"', false);

        $terjadwal = $this->alurSkripsi();
        $sidangTerjadwal = SidangSkripsi::factory()->for($terjadwal['skripsi'])->create(['status' => StatusSidangSkripsi::Dijadwalkan]);
        $this->actingAs($terjadwal['kaprodiUser'])->get(route('portal.sidang.index'))
            ->assertSee(route('sidang.surat.store', $sidangTerjadwal), false)
            ->assertSee('undangan_sidang')
            ->assertSee('surat_tugas_sidang');
    }

    public function test_form_operasional_pembimbing_muncul_hanya_pada_status_relevan(): void
    {
        $data = $this->alurSkripsi();
        $ditunjuk = KesediaanBimbingan::factory()->for($data['skripsi'])->create([
            'dosen_id' => $data['dosen']->nidn,
            'status' => StatusKesediaanBimbingan::Ditunjuk,
        ]);

        $response = $this->actingAs($data['kaprodiUser'])->get(route('portal.skripsi.index'));
        $response->assertOk()
            ->assertSee(route('kesediaan-bimbingan.surat.store', $ditunjuk), false)
            ->assertSee('Terbitkan surat kesediaan');

        $ditunjuk->forceFill(['status' => StatusKesediaanBimbingan::MenungguVerifikasi])->save();
        $dokumen = DokumenPengajuan::factory()->create([
            'documentable_id' => $ditunjuk->id,
            'uploaded_by' => $data['mahasiswaUser']->id,
        ]);
        $this->actingAs($data['kaprodiUser'])->get(route('portal.skripsi.index'))
            ->assertSee(route('dokumen-pengajuan.verifikasi-hasil-konsultasi.store', $dokumen), false)
            ->assertSee('valid_bersedia');

        $ditunjuk->forceFill([
            'status' => StatusKesediaanBimbingan::Ditolak,
            'hasil' => HasilKesediaanBimbingan::TidakBersedia,
        ])->save();
        $this->actingAs($data['kaprodiUser'])->get(route('portal.skripsi.index'))
            ->assertSee(route('kesediaan-bimbingan.calon-pengganti.store', $ditunjuk), false)
            ->assertSee('name="calon_pengganti_id"', false);
    }

    public function test_form_finalisasi_dan_sk_dibatasi_status_skripsi(): void
    {
        $data = $this->alurSkripsi();
        KesediaanBimbingan::factory()->for($data['skripsi'])->create([
            'dosen_id' => $data['dosen']->nidn,
            'status' => StatusKesediaanBimbingan::Diterima,
            'hasil' => HasilKesediaanBimbingan::Bersedia,
        ]);
        $this->actingAs($data['kaprodiUser'])->get(route('portal.skripsi.index'))
            ->assertSee(route('skripsi.finalisasi-pembimbing.store', $data['skripsi']), false);

        $data['skripsi']->forceFill([
            'status' => StatusSkripsi::BimbinganAktif,
            'pembimbing1_id' => $data['dosen']->nidn,
        ])->save();
        $this->actingAs($data['kaprodiUser'])->get(route('portal.skripsi.index'))
            ->assertSee(route('skripsi.sk-bimbingan.store', $data['skripsi']), false)
            ->assertDontSee(route('skripsi.finalisasi-pembimbing.store', $data['skripsi']), false);
    }

    public function test_mahasiswa_melihat_form_pengajuan_seminar_dan_sidang_saat_memenuhi_syarat(): void
    {
        $seminar = $this->alurSkripsi();
        $seminar['skripsi']->forceFill(['status' => StatusSkripsi::SiapSeminar])->save();
        $this->actingAs($seminar['mahasiswaUser'])->get(route('portal.seminar.index'))
            ->assertOk()
            ->assertSee(route('skripsi.seminar.store', $seminar['skripsi']), false)
            ->assertSee('name="berkas_seminar"', false)
            ->assertDontSee('name="keputusan"', false);

        $sidang = $this->alurSkripsi();
        $sidang['skripsi']->forceFill(['status' => StatusSkripsi::SiapSidang])->save();
        Seminar::factory()->for($sidang['skripsi'])->create(['status' => StatusSeminar::Selesai]);
        $this->actingAs($sidang['mahasiswaUser'])->get(route('portal.sidang.index'))
            ->assertOk()
            ->assertSee(route('skripsi.sidang.store', $sidang['skripsi']), false)
            ->assertSee('name="berkas_sidang"', false)
            ->assertDontSee('name="keputusan"', false);
    }

    private function alurSkripsi(): array
    {
        $programStudi = ProgramStudi::factory()->create();
        $kaprodiUser = User::factory()->dosen()->create();
        $kaprodi = Dosen::factory()->create([
            'program_studi_id' => $programStudi->id,
            'user_id' => $kaprodiUser->id,
        ]);
        $programStudi->update(['ketua_prodi_id' => $kaprodi->nidn]);
        $dosen = Dosen::factory()->create(['program_studi_id' => $programStudi->id]);
        Dosen::factory()->create(['program_studi_id' => $programStudi->id]);
        $mahasiswaUser = User::factory()->mahasiswa()->create();
        $mahasiswa = Mahasiswa::factory()->create([
            'program_studi_id' => $programStudi->id,
            'pembimbing_akademik_id' => $dosen->nidn,
            'user_id' => $mahasiswaUser->id,
        ]);
        $pengajuan = PengajuanJudul::factory()->create([
            'nim' => $mahasiswa->nim,
            'judul' => 'Sistem Informasi Akademik Terintegrasi',
            'status' => StatusPengajuanJudul::Diverifikasi,
            'diverifikasi_oleh' => $kaprodi->nidn,
            'diverifikasi_at' => now(),
        ]);
        $skripsi = Skripsi::factory()->create([
            'pengajuan_judul_id' => $pengajuan->id,
            'nim' => $mahasiswa->nim,
            'judul' => $pengajuan->judul,
            'status' => StatusSkripsi::MenungguKesediaanPembimbing,
        ]);

        return compact('programStudi', 'kaprodiUser', 'kaprodi', 'dosen', 'mahasiswaUser', 'mahasiswa', 'pengajuan', 'skripsi');
    }
}
