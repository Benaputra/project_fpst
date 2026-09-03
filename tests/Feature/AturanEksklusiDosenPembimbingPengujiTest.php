<?php

namespace Tests\Feature;

use App\Enums\StatusPengajuan;
use App\Enums\UserRole;
use App\Models\PengajuanSkripsi;
use App\Models\ProgramStudi;
use App\Models\SeminarSkripsi;
use App\Models\SidangSkripsi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AturanEksklusiDosenPembimbingPengujiTest extends TestCase
{
    use RefreshDatabase;

    private ProgramStudi $prodi;
    private User $kaprodi;
    private User $adminUtama;
    private User $mahasiswaA;
    private User $mahasiswaB;
    private User $dosen1;
    private User $dosen2;
    private User $dosen3;
    private User $dosen4;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prodi = ProgramStudi::create(['nama' => 'Agroteknologi', 'kode' => 'AGT']);

        $this->kaprodi = User::create([
            'name' => 'Dr. Ratna Wijaya',
            'email' => 'kaprodi@example.test',
            'nomor_induk' => '1000000001',
            'password' => 'password',
            'role' => UserRole::Kaprodi,
            'program_studi_id' => $this->prodi->id,
        ]);

        $this->adminUtama = User::create([
            'name' => 'Admin Utama FPST',
            'email' => 'admin.utama@example.test',
            'nomor_induk' => 'ADM-UTAMA',
            'password' => 'password',
            'role' => UserRole::AdminUtama,
        ]);

        $this->mahasiswaA = User::create([
            'name' => 'Mahasiswa A',
            'email' => 'mhs.a@example.test',
            'nomor_induk' => '22100000001',
            'password' => 'password',
            'role' => UserRole::Mahasiswa,
            'program_studi_id' => $this->prodi->id,
        ]);

        $this->mahasiswaB = User::create([
            'name' => 'Mahasiswa B',
            'email' => 'mhs.b@example.test',
            'nomor_induk' => '22100000002',
            'password' => 'password',
            'role' => UserRole::Mahasiswa,
            'program_studi_id' => $this->prodi->id,
        ]);

        $this->dosen1 = User::create([
            'name' => 'Dosen 1, M.Kom.',
            'email' => 'dosen1@example.test',
            'nomor_induk' => '1000000003',
            'password' => 'password',
            'role' => UserRole::Dosen,
            'program_studi_id' => $this->prodi->id,
        ]);

        $this->dosen2 = User::create([
            'name' => 'Dosen 2, M.Kom.',
            'email' => 'dosen2@example.test',
            'nomor_induk' => '1000000004',
            'password' => 'password',
            'role' => UserRole::Dosen,
            'program_studi_id' => $this->prodi->id,
        ]);

        $this->dosen3 = User::create([
            'name' => 'Dosen 3, M.Cs.',
            'email' => 'dosen3@example.test',
            'nomor_induk' => '1000000005',
            'password' => 'password',
            'role' => UserRole::Dosen,
            'program_studi_id' => $this->prodi->id,
        ]);

        $this->dosen4 = User::create([
            'name' => 'Dosen 4, M.T.',
            'email' => 'dosen4@example.test',
            'nomor_induk' => '1000000006',
            'password' => 'password',
            'role' => UserRole::Dosen,
            'program_studi_id' => $this->prodi->id,
        ]);
    }

    public function test_pembimbing_1_dan_2_tidak_boleh_dosen_yang_sama(): void
    {
        $skripsi = PengajuanSkripsi::create([
            'mahasiswa_id' => $this->mahasiswaA->id,
            'program_studi_id' => $this->prodi->id,
            'judul' => 'Judul Skripsi Mahasiswa A',
            'status' => StatusPengajuan::Diajukan,
        ]);

        $response = $this->actingAs($this->kaprodi)->post(route('kaprodi.skripsi.review', $skripsi->id), [
            'action' => 'terima',
            'pembimbing_1_id' => $this->dosen1->id,
            'pembimbing_2_id' => $this->dosen1->id, // Dosen sama
        ]);

        $response->assertSessionHasErrors(['pembimbing_2_id']);
    }

    public function test_dosen_pembimbing_tidak_dapat_dipilih_sebagai_penguji_seminar_pada_mahasiswa_yang_sama(): void
    {
        $skripsi = PengajuanSkripsi::create([
            'mahasiswa_id' => $this->mahasiswaA->id,
            'program_studi_id' => $this->prodi->id,
            'judul' => 'Judul Skripsi Mahasiswa A',
            'pembimbing_1_id' => $this->dosen1->id,
            'pembimbing_2_id' => $this->dosen2->id,
            'status' => StatusPengajuan::Selesai,
        ]);

        $seminar = SeminarSkripsi::create([
            'pengajuan_skripsi_id' => $skripsi->id,
            'status' => StatusPengajuan::Diajukan,
        ]);

        // 1. Coba tetapkan Pembimbing 1 sebagai Penguji Seminar -> Ditolak!
        $res1 = $this->actingAs($this->kaprodi)->post(route('kaprodi.seminar.penguji', $seminar->id), [
            'penguji_seminar_id' => $this->dosen1->id,
        ]);
        $res1->assertSessionHasErrors(['penguji_seminar_id']);

        // 2. Coba tetapkan Pembimbing 2 sebagai Penguji Seminar -> Ditolak!
        $res2 = $this->actingAs($this->kaprodi)->post(route('kaprodi.seminar.penguji', $seminar->id), [
            'penguji_seminar_id' => $this->dosen2->id,
        ]);
        $res2->assertSessionHasErrors(['penguji_seminar_id']);

        // 3. Tetapkan Dosen 3 (bukan pembimbing) -> Berhasil!
        $res3 = $this->actingAs($this->kaprodi)->post(route('kaprodi.seminar.penguji', $seminar->id), [
            'penguji_seminar_id' => $this->dosen3->id,
        ]);
        $res3->assertSessionHas('success');
        $this->assertEquals($this->dosen3->id, $seminar->fresh()->penguji_seminar_id);
    }

    public function test_dosen_pembimbing_tidak_dapat_dipilih_sebagai_penguji_sidang_pada_mahasiswa_yang_sama(): void
    {
        $skripsi = PengajuanSkripsi::create([
            'mahasiswa_id' => $this->mahasiswaA->id,
            'program_studi_id' => $this->prodi->id,
            'judul' => 'Judul Skripsi Mahasiswa A',
            'pembimbing_1_id' => $this->dosen1->id,
            'pembimbing_2_id' => $this->dosen2->id,
            'status' => StatusPengajuan::Selesai,
        ]);

        $sidang = SidangSkripsi::create([
            'pengajuan_skripsi_id' => $skripsi->id,
            'status' => StatusPengajuan::Diajukan,
        ]);

        // 1. Coba tetapkan Pembimbing 1 sebagai Penguji 1 Sidang -> Ditolak!
        $res1 = $this->actingAs($this->kaprodi)->post(route('kaprodi.sidang.penguji', $sidang->id), [
            'penguji_1_id' => $this->dosen1->id,
            'penguji_2_id' => $this->dosen3->id,
        ]);
        $res1->assertSessionHasErrors(['penguji_1_id']);

        // 2. Coba tetapkan Pembimbing 2 sebagai Penguji 2 Sidang -> Ditolak!
        $res2 = $this->actingAs($this->kaprodi)->post(route('kaprodi.sidang.penguji', $sidang->id), [
            'penguji_1_id' => $this->dosen3->id,
            'penguji_2_id' => $this->dosen2->id,
        ]);
        $res2->assertSessionHasErrors(['penguji_2_id']);

        // 3. Tetapkan Penguji 1 = Dosen 3 dan Penguji 2 = Dosen 4 -> Berhasil!
        $res3 = $this->actingAs($this->kaprodi)->post(route('kaprodi.sidang.penguji', $sidang->id), [
            'penguji_1_id' => $this->dosen3->id,
            'penguji_2_id' => $this->dosen4->id,
        ]);
        $res3->assertSessionHas('success');
        $this->assertEquals($this->dosen3->id, $sidang->fresh()->penguji_1_id);
        $this->assertEquals($this->dosen4->id, $sidang->fresh()->penguji_2_id);
    }

    public function test_penguji_1_dan_2_sidang_tidak_boleh_dosen_yang_sama(): void
    {
        $skripsi = PengajuanSkripsi::create([
            'mahasiswa_id' => $this->mahasiswaA->id,
            'program_studi_id' => $this->prodi->id,
            'judul' => 'Judul Skripsi Mahasiswa A',
            'pembimbing_1_id' => $this->dosen1->id,
            'status' => StatusPengajuan::Selesai,
        ]);

        $sidang = SidangSkripsi::create([
            'pengajuan_skripsi_id' => $skripsi->id,
            'status' => StatusPengajuan::Diajukan,
        ]);

        $response = $this->actingAs($this->kaprodi)->post(route('kaprodi.sidang.penguji', $sidang->id), [
            'penguji_1_id' => $this->dosen3->id,
            'penguji_2_id' => $this->dosen3->id, // Dosen sama
        ]);

        $response->assertSessionHasErrors(['penguji_2_id']);
    }

    public function test_penguji_seminar_boleh_menjadi_penguji_sidang_pada_mahasiswa_yang_sama(): void
    {
        $skripsi = PengajuanSkripsi::create([
            'mahasiswa_id' => $this->mahasiswaA->id,
            'program_studi_id' => $this->prodi->id,
            'judul' => 'Judul Skripsi Mahasiswa A',
            'pembimbing_1_id' => $this->dosen1->id,
            'pembimbing_2_id' => $this->dosen2->id,
            'status' => StatusPengajuan::Selesai,
        ]);

        // Dosen 3 adalah penguji seminar
        $seminar = SeminarSkripsi::create([
            'pengajuan_skripsi_id' => $skripsi->id,
            'penguji_seminar_id' => $this->dosen3->id,
            'status' => StatusPengajuan::Selesai,
        ]);

        $sidang = SidangSkripsi::create([
            'pengajuan_skripsi_id' => $skripsi->id,
            'status' => StatusPengajuan::Diajukan,
        ]);

        // Dosen 3 (penguji seminar) diangkat sebagai Penguji 1 Sidang -> Diperbolehkan!
        $response = $this->actingAs($this->kaprodi)->post(route('kaprodi.sidang.penguji', $sidang->id), [
            'penguji_1_id' => $this->dosen3->id,
            'penguji_2_id' => $this->dosen4->id,
        ]);

        $response->assertSessionHas('success');
        $this->assertEquals($this->dosen3->id, $sidang->fresh()->penguji_1_id);
    }

    public function test_dosen_yang_menjadi_pembimbing_pada_mahasiswa_a_dapat_menjadi_pembimbing_atau_penguji_pada_mahasiswa_b(): void
    {
        // Mahasiswa A dibimbing oleh Dosen 1 & Dosen 2
        $skripsiA = PengajuanSkripsi::create([
            'mahasiswa_id' => $this->mahasiswaA->id,
            'program_studi_id' => $this->prodi->id,
            'judul' => 'Skripsi Mahasiswa A',
            'pembimbing_1_id' => $this->dosen1->id,
            'pembimbing_2_id' => $this->dosen2->id,
            'status' => StatusPengajuan::Selesai,
        ]);

        // Mahasiswa B mengajukan skripsi
        $skripsiB = PengajuanSkripsi::create([
            'mahasiswa_id' => $this->mahasiswaB->id,
            'program_studi_id' => $this->prodi->id,
            'judul' => 'Skripsi Mahasiswa B',
            'status' => StatusPengajuan::Diajukan,
        ]);

        // Dosen 1 TETAP BISA menjadi Pembimbing 1 untuk Mahasiswa B
        $resBimbinganB = $this->actingAs($this->kaprodi)->post(route('kaprodi.skripsi.review', $skripsiB->id), [
            'action' => 'terima',
            'pembimbing_1_id' => $this->dosen1->id,
            'pembimbing_2_id' => $this->dosen3->id,
        ]);
        $resBimbinganB->assertSessionHas('success');
        $this->assertEquals($this->dosen1->id, $skripsiB->fresh()->pembimbing_1_id);

        // Dan Dosen 2 (yang pembimbing di Mahasiswa A) BISA menjadi Penguji Seminar di Mahasiswa B
        $seminarB = SeminarSkripsi::create([
            'pengajuan_skripsi_id' => $skripsiB->id,
            'status' => StatusPengajuan::Diajukan,
        ]);

        $resSeminarB = $this->actingAs($this->kaprodi)->post(route('kaprodi.seminar.penguji', $seminarB->id), [
            'penguji_seminar_id' => $this->dosen2->id, // Dosen 2 bukan pembimbing Mahasiswa B
        ]);
        $resSeminarB->assertSessionHas('success');
        $this->assertEquals($this->dosen2->id, $seminarB->fresh()->penguji_seminar_id);
    }

    public function test_halaman_penetapan_menyediakan_payload_pembimbing_untuk_eksklusi_options_js(): void
    {
        $skripsi = PengajuanSkripsi::create([
            'mahasiswa_id' => $this->mahasiswaA->id,
            'program_studi_id' => $this->prodi->id,
            'judul' => 'Sistem IoT Pertanian',
            'pembimbing_1_id' => $this->dosen1->id,
            'pembimbing_2_id' => $this->dosen2->id,
            'status' => StatusPengajuan::Selesai,
        ]);

        $seminar = SeminarSkripsi::create([
            'pengajuan_skripsi_id' => $skripsi->id,
            'status' => StatusPengajuan::Diajukan,
        ]);

        $sidang = SidangSkripsi::create([
            'pengajuan_skripsi_id' => $skripsi->id,
            'status' => StatusPengajuan::Diajukan,
        ]);

        // Buka tab seminar
        $responseSem = $this->actingAs($this->kaprodi)->get(route('kaprodi.penetapan.index', ['tab' => 'seminar']));
        $responseSem->assertStatus(200);
        // Pastikan pembimbing_1_id dan pembimbing_2_id diteruskan ke JSON openDrawerSeminar
        $responseSem->assertSee('&quot;pembimbing_1_id&quot;:' . $this->dosen1->id, false);
        $responseSem->assertSee('&quot;pembimbing_2_id&quot;:' . $this->dosen2->id, false);

        // Buka tab sidang
        $responseSdg = $this->actingAs($this->kaprodi)->get(route('kaprodi.penetapan.index', ['tab' => 'sidang']));
        $responseSdg->assertStatus(200);
        // Pastikan pembimbing_1_id dan pembimbing_2_id diteruskan ke JSON openDrawerSidang
        $responseSdg->assertSee('&quot;pembimbing_1_id&quot;:' . $this->dosen1->id, false);
        $responseSdg->assertSee('&quot;pembimbing_2_id&quot;:' . $this->dosen2->id, false);
    }
}
