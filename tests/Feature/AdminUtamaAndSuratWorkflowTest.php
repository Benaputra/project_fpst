<?php

namespace Tests\Feature;

use App\Enums\StatusPengajuan;
use App\Enums\UserRole;
use App\Models\Notifikasi;
use App\Models\PengajuanSkripsi;
use App\Models\ProgramStudi;
use App\Models\SeminarSkripsi;
use App\Models\SidangSkripsi;
use App\Models\Surat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminUtamaAndSuratWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private ProgramStudi $prodiTI;
    private ProgramStudi $prodiSI;
    private User $mahasiswaTI;
    private User $kaprodiTI;
    private User $adminTI;
    private User $adminUtama;
    private User $dosen1;
    private User $dosen2;
    private User $dosen3;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        $this->prodiTI = ProgramStudi::create(['nama' => 'Teknik Informatika', 'kode' => 'TI']);
        $this->prodiSI = ProgramStudi::create(['nama' => 'Sistem Informasi', 'kode' => 'SI']);

        $this->adminUtama = User::create([
            'name' => 'Admin Utama FPST',
            'email' => 'admin.utama@example.test',
            'nomor_induk' => 'ADM-UTAMA',
            'password' => 'password',
            'role' => UserRole::AdminUtama,
        ]);

        $this->kaprodiTI = User::create([
            'name' => 'Dr. Ratna Wijaya',
            'email' => 'kaprodi.ti@example.test',
            'nomor_induk' => '1000000001',
            'password' => 'password',
            'role' => UserRole::Kaprodi,
            'program_studi_id' => $this->prodiTI->id,
        ]);

        $this->adminTI = User::create([
            'name' => 'Admin Prodi TI',
            'email' => 'admin.ti@example.test',
            'nomor_induk' => 'ADM-TI',
            'password' => 'password',
            'role' => UserRole::AdminProdi,
            'program_studi_id' => $this->prodiTI->id,
        ]);

        $this->dosen1 = User::create([
            'name' => 'Dewi Lestari, M.Kom.',
            'email' => 'dewi@example.test',
            'nomor_induk' => '1000000003',
            'password' => 'password',
            'role' => UserRole::Dosen,
            'program_studi_id' => $this->prodiTI->id,
        ]);

        $this->dosen2 = User::create([
            'name' => 'Ahmad Fauzi, M.Kom.',
            'email' => 'ahmad@example.test',
            'nomor_induk' => '1000000004',
            'password' => 'password',
            'role' => UserRole::Dosen,
            'program_studi_id' => $this->prodiTI->id,
        ]);

        $this->dosen3 = User::create([
            'name' => 'Fajar Nugroho, M.Cs.',
            'email' => 'fajar@example.test',
            'nomor_induk' => '1000000006',
            'password' => 'password',
            'role' => UserRole::Dosen,
            'program_studi_id' => $this->prodiTI->id,
        ]);

        $this->mahasiswaTI = User::create([
            'name' => 'Budi Santoso',
            'email' => 'budi@example.test',
            'nomor_induk' => '221000000010',
            'password' => 'password',
            'role' => UserRole::Mahasiswa,
            'program_studi_id' => $this->prodiTI->id,
        ]);
    }

    /**
     * Butir 1: Akses admin utama adalah semua hal yang dapat dilakukan oleh role,
     * termasuk menentukan dosen penguji seminar dan penguji sidang skripsi.
     */
    public function test_admin_utama_can_access_penetapan_and_assign_penguji_seminar_and_sidang(): void
    {
        $skripsi = PengajuanSkripsi::create([
            'mahasiswa_id' => $this->mahasiswaTI->id,
            'program_studi_id' => $this->prodiTI->id,
            'judul' => 'Sistem Deteksi Anomali Jaringan Komputer',
            'pembimbing_1_id' => $this->dosen1->id,
            'nomor_sk_bimbingan' => 'SK/01/2026',
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

        // 1. Admin utama dapat membuka halaman penetapan
        $response = $this->actingAs($this->adminUtama)->get(route('kaprodi.penetapan.index', ['prodi_id' => $this->prodiTI->id]));
        $response->assertStatus(200);
        $response->assertSee('Akses Penuh Admin Utama');

        // 2. Admin utama dapat menentukan dosen penguji seminar
        $response = $this->actingAs($this->adminUtama)->post(route('kaprodi.seminar.penguji', $seminar->id), [
            'penguji_seminar_id' => $this->dosen2->id,
        ]);
        $response->assertSessionHas('success');
        $seminar->refresh();
        $this->assertEquals($this->dosen2->id, $seminar->penguji_seminar_id);

        // 3. Admin utama dapat menentukan 2 penguji sidang skripsi
        $response = $this->actingAs($this->adminUtama)->post(route('kaprodi.sidang.penguji', $sidang->id), [
            'penguji_1_id' => $this->dosen2->id,
            'penguji_2_id' => $this->dosen3->id,
        ]);
        $response->assertSessionHas('success');
        $sidang->refresh();
        $this->assertEquals($this->dosen2->id, $sidang->penguji_1_id);
        $this->assertEquals($this->dosen3->id, $sidang->penguji_2_id);
    }

    /**
     * Butir 2 & 4: Penamaan SK baru jika ada update dan pencatatan ke tabel surat.
     */
    public function test_sk_update_gives_new_name_version_and_records_in_surat_table(): void
    {
        $skripsi = PengajuanSkripsi::create([
            'mahasiswa_id' => $this->mahasiswaTI->id,
            'program_studi_id' => $this->prodiTI->id,
            'judul' => 'Sistem Rekomendasi Buku',
            'pembimbing_1_id' => $this->dosen1->id,
            'status' => StatusPengajuan::Diproses,
        ]);

        // Penerbitan SK Bimbingan pertama (Versi 1)
        $this->actingAs($this->adminTI)->post(route('admin.skripsi.sk-bimbingan', $skripsi->id), [
            'nomor_sk_bimbingan' => 'SK/001/FPST/2026',
            'tgl_sk_bimbingan' => '2026-09-01',
            'file_sk_bimbingan' => UploadedFile::fake()->create('sk_v1.pdf', 300, 'application/pdf'),
        ]);

        $this->assertDatabaseHas('surat', [
            'nomor_surat' => 'SK/001/FPST/2026',
            'jenis_surat' => 'sk_bimbingan',
            'nama_surat' => "SK Pembimbing Skripsi - {$this->mahasiswaTI->name}",
            'versi' => 1,
            'status' => 'aktif',
        ]);

        // Update SK Bimbingan (Versi 2) -> Harus menghasilkan Nama baru dan versi baru!
        $skripsi->refresh();
        $this->actingAs($this->adminTI)->post(route('admin.skripsi.sk-bimbingan', $skripsi->id), [
            'nomor_sk_bimbingan' => 'SK/001-REV/FPST/2026',
            'tgl_sk_bimbingan' => '2026-09-05',
            'file_sk_bimbingan' => UploadedFile::fake()->create('sk_v2.pdf', 300, 'application/pdf'),
        ]);

        // Verifikasi record versi 1 berubah status menjadi 'diperbarui'
        $this->assertDatabaseHas('surat', [
            'nomor_surat' => 'SK/001/FPST/2026',
            'versi' => 1,
            'status' => 'diperbarui',
        ]);

        // Verifikasi record versi 2 terdaftar dengan nama baru
        $this->assertDatabaseHas('surat', [
            'nomor_surat' => 'SK/001-REV/FPST/2026',
            'jenis_surat' => 'sk_bimbingan',
            'nama_surat' => "SK Pembimbing Skripsi (Pembaruan ke-2) - {$this->mahasiswaTI->name}",
            'versi' => 2,
            'status' => 'aktif',
        ]);

        // Cek halaman arsip surat di administrasi index (Tab 4)
        $response = $this->actingAs($this->adminTI)->get(route('admin.administrasi.index'));
        $response->assertStatus(200);
        $response->assertSee('SK Pembimbing Skripsi (Pembaruan ke-2)');
    }

    /**
     * Undangan seminar diberikan kepada tim seminar: Pembimbing 1, Pembimbing 2, dan Penguji Seminar.
     */
    public function test_seminar_team_receives_seminar_invitation_and_notification(): void
    {
        $skripsi = PengajuanSkripsi::create([
            'mahasiswa_id' => $this->mahasiswaTI->id,
            'program_studi_id' => $this->prodiTI->id,
            'judul' => 'Analisis Sentimen Menggunakan Transformer',
            'pembimbing_1_id' => $this->dosen1->id,
            'pembimbing_2_id' => $this->dosen2->id,
            'nomor_sk_bimbingan' => 'SK/010/FPST/2026',
            'status' => StatusPengajuan::Selesai,
        ]);

        $seminar = SeminarSkripsi::create([
            'pengajuan_skripsi_id' => $skripsi->id,
            'penguji_seminar_id' => $this->dosen3->id,
            'status' => StatusPengajuan::Diproses,
        ]);

        // Admin menetapkan jadwal dan surat undangan seminar
        $response = $this->actingAs($this->adminTI)->post(route('admin.seminar.jadwal-sk', $seminar->id), [
            'tgl_seminar' => '2026-09-15',
            'jam_seminar' => '09:00 - 10:30',
            'ruangan' => 'Ruang Seminar 101',
            'nomor_undangan_seminar' => 'UND-SEM/088/2026',
            'file_undangan_seminar' => UploadedFile::fake()->create('undangan_seminar.pdf', 300, 'application/pdf'),
        ]);
        $response->assertSessionHas('success');

        // Verifikasi notifikasi undangan seminar terkirim ke Pembimbing 1
        $this->assertDatabaseHas('notifikasi', [
            'user_id' => $this->dosen1->id,
            'judul' => 'Undangan Seminar Skripsi Mahasiswa Bimbingan',
        ]);

        // Verifikasi notifikasi undangan seminar terkirim ke Pembimbing 2
        $this->assertDatabaseHas('notifikasi', [
            'user_id' => $this->dosen2->id,
            'judul' => 'Undangan Seminar Skripsi Mahasiswa Bimbingan',
        ]);

        // Verifikasi notifikasi undangan seminar terkirim ke Dosen Penguji Seminar
        $this->assertDatabaseHas('notifikasi', [
            'user_id' => $this->dosen3->id,
            'judul' => 'Undangan & Jadwal Ujian Seminar Mahasiswa',
        ]);

        // Verifikasi Pembimbing 1 dapat melihat undangan seminar di halaman bimbingan
        $response = $this->actingAs($this->dosen1)->get(route('dosen.bimbingan.index'));
        $response->assertStatus(200);
        $response->assertSee('Undangan Seminar Proposal/Hasil Mahasiswa Bimbingan');
        $response->assertSee('UND-SEM/088/2026');
        $response->assertSee('Unduh Undangan Seminar (PDF)');

        // Verifikasi Penguji Seminar dapat melihat undangan seminar di tab tugas penguji
        $response = $this->actingAs($this->dosen3)->get(route('dosen.bimbingan.index'));
        $response->assertStatus(200);
        $response->assertSee('Unduh Undangan Seminar (PDF)');

        // Verifikasi tercatat di tabel surat sebagai jenis undangan_seminar
        $this->assertDatabaseHas('surat', [
            'nomor_surat' => 'UND-SEM/088/2026',
            'jenis_surat' => 'undangan_seminar',
            'seminar_skripsi_id' => $seminar->id,
        ]);
    }

    /**
     * Butir 3: Pembimbing skripsi 1 dan 2 juga mendapatkan undangan sidang skripsi.
     */
    public function test_pembimbing_1_and_2_receive_defense_invitation_and_notification(): void
    {
        $skripsi = PengajuanSkripsi::create([
            'mahasiswa_id' => $this->mahasiswaTI->id,
            'program_studi_id' => $this->prodiTI->id,
            'judul' => 'Penerapan AI pada Analisis Citra Satelit',
            'pembimbing_1_id' => $this->dosen1->id,
            'pembimbing_2_id' => $this->dosen2->id,
            'status' => StatusPengajuan::Selesai,
        ]);

        $sidang = SidangSkripsi::create([
            'pengajuan_skripsi_id' => $skripsi->id,
            'penguji_1_id' => $this->dosen3->id,
            'penguji_2_id' => $this->kaprodiTI->id,
            'status' => StatusPengajuan::Diproses,
        ]);

        // Admin menetapkan jadwal dan surat undangan sidang
        $response = $this->actingAs($this->adminTI)->post(route('admin.sidang.jadwal-sk', $sidang->id), [
            'tgl_sidang' => '2026-09-20',
            'jam_sidang' => '10:00 - 12:00',
            'ruangan' => 'Ruang Sidang Meja Hijau',
            'nomor_undangan_sidang' => 'UND-SDG/099/2026',
            'file_undangan_sidang' => UploadedFile::fake()->create('undangan_sidang.pdf', 300, 'application/pdf'),
        ]);
        $response->assertSessionHas('success');

        // Verifikasi notifikasi undangan sidang terkirim ke Pembimbing 1
        $this->assertDatabaseHas('notifikasi', [
            'user_id' => $this->dosen1->id,
            'judul' => 'Undangan Sidang Skripsi Mahasiswa Bimbingan',
        ]);

        // Verifikasi notifikasi undangan sidang terkirim ke Pembimbing 2
        $this->assertDatabaseHas('notifikasi', [
            'user_id' => $this->dosen2->id,
            'judul' => 'Undangan Sidang Skripsi Mahasiswa Bimbingan',
        ]);

        // Verifikasi Pembimbing 1 dapat melihat undangan sidang di halaman bimbingan
        $response = $this->actingAs($this->dosen1)->get(route('dosen.bimbingan.index'));
        $response->assertStatus(200);
        $response->assertSee('Undangan Sidang Meja Hijau Mahasiswa Bimbingan');
        $response->assertSee('UND-SDG/099/2026');
        $response->assertSee('Unduh Berkas Undangan (PDF)');

        // Verifikasi tercatat di tabel surat sebagai jenis undangan_sidang
        $this->assertDatabaseHas('surat', [
            'nomor_surat' => 'UND-SDG/099/2026',
            'jenis_surat' => 'undangan_sidang',
            'sidang_skripsi_id' => $sidang->id,
        ]);
    }

    /**
     * Butir 5: Selain admin utama, role lain tidak boleh merubah nilai,
     * dosen pembimbing dan penguji, pada menu seminar dan skripsi jika sudah ditentukan oleh kaprodi.
     */
    public function test_locking_rules_enforced_except_for_admin_utama(): void
    {
        // -------------------------------------------------------------
        // A. Penguncian Dosen Pembimbing
        // -------------------------------------------------------------
        $skripsi = PengajuanSkripsi::create([
            'mahasiswa_id' => $this->mahasiswaTI->id,
            'program_studi_id' => $this->prodiTI->id,
            'judul' => 'Sistem IoT Pertanian Cerdas',
            'status' => StatusPengajuan::Diajukan,
        ]);

        // Kaprodi menetapkan pembimbing pertama kali -> Berhasil
        $this->actingAs($this->kaprodiTI)->post(route('kaprodi.skripsi.review', $skripsi->id), [
            'action' => 'terima',
            'pembimbing_1_id' => $this->dosen1->id,
            'pembimbing_2_id' => $this->dosen2->id,
        ]);
        $skripsi->refresh();
        $this->assertEquals($this->dosen1->id, $skripsi->pembimbing_1_id);

        // Kaprodi mencoba mengubah pembimbing yang sudah ditentukan -> Ditolak!
        $response = $this->actingAs($this->kaprodiTI)->post(route('kaprodi.skripsi.review', $skripsi->id), [
            'action' => 'terima',
            'pembimbing_1_id' => $this->dosen3->id,
        ]);
        $response->assertSessionHas('error');
        $skripsi->refresh();
        $this->assertEquals($this->dosen1->id, $skripsi->pembimbing_1_id); // Tidak berubah

        // Admin Utama mengubah pembimbing -> Berhasil!
        $response = $this->actingAs($this->adminUtama)->post(route('kaprodi.skripsi.review', $skripsi->id), [
            'action' => 'terima',
            'pembimbing_1_id' => $this->dosen3->id,
        ]);
        $response->assertSessionHas('success');
        $skripsi->refresh();
        $this->assertEquals($this->dosen3->id, $skripsi->pembimbing_1_id); // Berhasil diubah

        // -------------------------------------------------------------
        // B. Penguncian Dosen Penguji Seminar
        // -------------------------------------------------------------
        $seminar = SeminarSkripsi::create([
            'pengajuan_skripsi_id' => $skripsi->id,
            'status' => StatusPengajuan::Diajukan,
        ]);

        // Kaprodi menetapkan penguji seminar pertama kali -> Berhasil
        $this->actingAs($this->kaprodiTI)->post(route('kaprodi.seminar.penguji', $seminar->id), [
            'penguji_seminar_id' => $this->dosen1->id,
        ]);
        $seminar->refresh();
        $this->assertEquals($this->dosen1->id, $seminar->penguji_seminar_id);

        // Kaprodi mencoba mengubah penguji seminar -> Ditolak!
        $response = $this->actingAs($this->kaprodiTI)->post(route('kaprodi.seminar.penguji', $seminar->id), [
            'penguji_seminar_id' => $this->dosen2->id,
        ]);
        $response->assertSessionHas('error');
        $seminar->refresh();
        $this->assertEquals($this->dosen1->id, $seminar->penguji_seminar_id);

        // Admin Utama mengubah penguji seminar -> Berhasil!
        $response = $this->actingAs($this->adminUtama)->post(route('kaprodi.seminar.penguji', $seminar->id), [
            'penguji_seminar_id' => $this->dosen2->id,
        ]);
        $response->assertSessionHas('success');
        $seminar->refresh();
        $this->assertEquals($this->dosen2->id, $seminar->penguji_seminar_id);

        // -------------------------------------------------------------
        // C. Penguncian Dosen Penguji Sidang
        // -------------------------------------------------------------
        $sidang = SidangSkripsi::create([
            'pengajuan_skripsi_id' => $skripsi->id,
            'status' => StatusPengajuan::Diajukan,
        ]);

        // Kaprodi menetapkan 2 penguji sidang pertama kali -> Berhasil
        $this->actingAs($this->kaprodiTI)->post(route('kaprodi.sidang.penguji', $sidang->id), [
            'penguji_1_id' => $this->dosen1->id,
            'penguji_2_id' => $this->dosen2->id,
        ]);
        $sidang->refresh();
        $this->assertEquals($this->dosen1->id, $sidang->penguji_1_id);

        // Kaprodi mencoba mengubah penguji sidang -> Ditolak!
        $response = $this->actingAs($this->kaprodiTI)->post(route('kaprodi.sidang.penguji', $sidang->id), [
            'penguji_1_id' => $this->dosen2->id,
            'penguji_2_id' => $this->dosen3->id,
        ]);
        $response->assertSessionHas('error');
        $sidang->refresh();
        $this->assertEquals($this->dosen1->id, $sidang->penguji_1_id);

        // Admin Utama mengubah penguji sidang -> Berhasil!
        $response = $this->actingAs($this->adminUtama)->post(route('kaprodi.sidang.penguji', $sidang->id), [
            'penguji_1_id' => $this->dosen2->id,
            'penguji_2_id' => $this->dosen3->id,
        ]);
        $response->assertSessionHas('success');
        $sidang->refresh();
        $this->assertEquals($this->dosen2->id, $sidang->penguji_1_id);
        $this->assertEquals($this->dosen3->id, $sidang->penguji_2_id);

        // -------------------------------------------------------------
        // D. Penguncian Nilai Seminar
        // -------------------------------------------------------------
        // Admin Prodi input nilai pertama kali -> Berhasil
        $this->actingAs($this->adminTI)->post(route('admin.seminar.selesai', $seminar->id), [
            'nilai_seminar' => 80.00,
            'catatan' => 'Lulus',
        ]);
        $seminar->refresh();
        $this->assertEquals(80.00, (float) $seminar->nilai_seminar);

        // Admin Prodi mencoba mengubah nilai -> Ditolak!
        $response = $this->actingAs($this->adminTI)->post(route('admin.seminar.selesai', $seminar->id), [
            'nilai_seminar' => 90.00,
        ]);
        $response->assertSessionHas('error');
        $seminar->refresh();
        $this->assertEquals(80.00, (float) $seminar->nilai_seminar);

        // Admin Utama mengubah nilai seminar -> Berhasil!
        $response = $this->actingAs($this->adminUtama)->post(route('admin.seminar.selesai', $seminar->id), [
            'nilai_seminar' => 95.00,
        ]);
        $response->assertSessionHas('success');
        $seminar->refresh();
        $this->assertEquals(95.00, (float) $seminar->nilai_seminar);

        // -------------------------------------------------------------
        // E. Penguncian Nilai Sidang
        // -------------------------------------------------------------
        // Admin Prodi input nilai sidang pertama kali -> Berhasil
        $this->actingAs($this->adminTI)->post(route('admin.sidang.selesai', $sidang->id), [
            'nilai_sidang' => 82.50,
            'catatan' => 'Lulus',
        ]);
        $sidang->refresh();
        $this->assertEquals(82.50, (float) $sidang->nilai_sidang);

        // Admin Prodi mencoba mengubah nilai sidang -> Ditolak!
        $response = $this->actingAs($this->adminTI)->post(route('admin.sidang.selesai', $sidang->id), [
            'nilai_sidang' => 92.00,
        ]);
        $response->assertSessionHas('error');
        $sidang->refresh();
        $this->assertEquals(82.50, (float) $sidang->nilai_sidang);

        // Admin Utama mengubah nilai sidang -> Berhasil!
        $response = $this->actingAs($this->adminUtama)->post(route('admin.sidang.selesai', $sidang->id), [
            'nilai_sidang' => 98.00,
        ]);
        $response->assertSessionHas('success');
        $sidang->refresh();
        $this->assertEquals(98.00, (float) $sidang->nilai_sidang);
    }
}
