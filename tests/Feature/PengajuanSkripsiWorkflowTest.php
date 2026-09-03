<?php

namespace Tests\Feature;

use App\Enums\StatusPengajuan;
use App\Enums\UserRole;
use App\Models\AktivitasLog;
use App\Models\Notifikasi;
use App\Models\PengajuanSkripsi;
use App\Models\ProgramStudi;
use App\Models\SeminarSkripsi;
use App\Models\SidangSkripsi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PengajuanSkripsiWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private ProgramStudi $prodi;
    private User $mahasiswa;
    private User $kaprodi;
    private User $admin;
    private User $adminUtama;
    private User $dosen1;
    private User $dosen2;
    private User $dosen3;
    private User $dosen4;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        $this->prodi = ProgramStudi::create(['nama' => 'Agroteknologi', 'kode' => 'AGT']);

        $this->mahasiswa = User::create([
            'name' => 'Budi Santoso',
            'email' => 'budi@example.test',
            'nomor_induk' => '221000000010',
            'password' => 'password',
            'role' => UserRole::Mahasiswa,
            'program_studi_id' => $this->prodi->id,
        ]);

        $this->kaprodi = User::create([
            'name' => 'Dr. Ratna Wijaya',
            'email' => 'kaprodi@example.test',
            'nomor_induk' => '1000000001',
            'password' => 'password',
            'role' => UserRole::Kaprodi,
            'program_studi_id' => $this->prodi->id,
        ]);

        $this->admin = User::create([
            'name' => 'Admin Prodi TI',
            'email' => 'admin@example.test',
            'nomor_induk' => 'ADM001',
            'password' => 'password',
            'role' => UserRole::AdminProdi,
            'program_studi_id' => $this->prodi->id,
        ]);

        $this->adminUtama = User::create([
            'name' => 'Admin Utama FPST',
            'email' => 'admin.utama@example.test',
            'nomor_induk' => 'ADM-UTAMA',
            'password' => 'password',
            'role' => UserRole::AdminUtama,
        ]);

        $this->dosen1 = User::create([
            'name' => 'Dewi Lestari, M.Kom.',
            'email' => 'dewi@example.test',
            'nomor_induk' => '1000000003',
            'password' => 'password',
            'role' => UserRole::Dosen,
            'program_studi_id' => $this->prodi->id,
        ]);

        $this->dosen2 = User::create([
            'name' => 'Ahmad Fauzi, M.Kom.',
            'email' => 'ahmad@example.test',
            'nomor_induk' => '1000000004',
            'password' => 'password',
            'role' => UserRole::Dosen,
            'program_studi_id' => $this->prodi->id,
        ]);

        $this->dosen3 = User::create([
            'name' => 'Fajar Nugroho, M.Cs.',
            'email' => 'fajar@example.test',
            'nomor_induk' => '1000000006',
            'password' => 'password',
            'role' => UserRole::Dosen,
            'program_studi_id' => $this->prodi->id,
        ]);

        $this->dosen4 = User::create([
            'name' => 'Siti Aminah, M.Kom.',
            'email' => 'siti@example.test',
            'nomor_induk' => '1000000007',
            'password' => 'password',
            'role' => UserRole::Dosen,
            'program_studi_id' => $this->prodi->id,
        ]);
    }

    public function test_full_3_phase_lifecycle_end_to_end_with_logs_and_notifications(): void
    {
        // ==========================================
        // FASE 1: Pengajuan Judul & SK Bimbingan
        // ==========================================

        // 1. Mahasiswa ajukan judul + 3 berkas
        $response = $this->actingAs($this->mahasiswa)->post(route('mahasiswa.skripsi.store'), [
            'judul' => 'Pengembangan Sistem Rekomendasi E-Commerce Berbasis Deep Learning',
            'abstrak' => 'Penelitian ini membangun arsitektur collaborative filtering neural network.',
            'file_proposal' => UploadedFile::fake()->create('proposal.pdf', 500, 'application/pdf'),
            'file_transkrip' => UploadedFile::fake()->create('transkrip.pdf', 500, 'application/pdf'),
            'file_bukti_bayar' => UploadedFile::fake()->create('bukti_bayar.pdf', 500, 'application/pdf'),
        ]);
        $response->assertRedirect(route('mahasiswa.skripsi.index'));

        $skripsi = PengajuanSkripsi::first();
        $this->assertNotNull($skripsi);
        $this->assertEquals(StatusPengajuan::Diajukan, $skripsi->status);

        // Verifikasi Log tercatat
        $this->assertDatabaseHas('aktivitas_log', [
            'user_id' => $this->mahasiswa->id,
            'aksi' => 'Pengajuan Judul Skripsi',
        ]);

        // 2. Kaprodi menerima judul dan menetapkan Pembimbing 1 & 2
        $response = $this->actingAs($this->kaprodi)->post(route('kaprodi.skripsi.review', $skripsi->id), [
            'action' => 'terima',
            'pembimbing_1_id' => $this->dosen1->id,
            'pembimbing_2_id' => $this->dosen2->id,
        ]);
        $response->assertSessionHas('success');

        $skripsi->refresh();
        $this->assertEquals(StatusPengajuan::Diproses, $skripsi->status);

        // Verifikasi Notifikasi ke Mahasiswa & Dosen
        $this->assertDatabaseHas('notifikasi', [
            'user_id' => $this->mahasiswa->id,
            'judul' => 'Judul Disetujui & Pembimbing Ditetapkan',
        ]);
        $this->assertDatabaseHas('notifikasi', [
            'user_id' => $this->dosen1->id,
            'judul' => 'Penugasan Pembimbing Utama (1)',
        ]);

        // 2b. Admin mencoba terbitkan SK sebelum dosen menyetujui -> Diblokir!
        $responseBlocked = $this->actingAs($this->admin)->post(route('admin.skripsi.sk-bimbingan', $skripsi->id), [
            'nomor_sk_bimbingan' => 'SK/099/FPST/TI/2026',
            'tgl_sk_bimbingan' => '2026-09-01',
        ]);
        $responseBlocked->assertSessionHas('error');

        // 2c. Dosen 1 & 2 mengonfirmasi kesediaan (menyetujui)
        $penugasanP1 = \App\Models\PenugasanDosen::where('assignable_type', PengajuanSkripsi::class)->where('dosen_id', $this->dosen1->id)->first();
        $this->actingAs($this->dosen1)->post(route('dosen.penugasan.respon', $penugasanP1->id), ['aksi' => 'terima']);

        $penugasanP2 = \App\Models\PenugasanDosen::where('assignable_type', PengajuanSkripsi::class)->where('dosen_id', $this->dosen2->id)->first();
        $this->actingAs($this->dosen2)->post(route('dosen.penugasan.respon', $penugasanP2->id), ['aksi' => 'terima']);

        // 3. Admin menerbitkan SK Bimbingan setelah konfirmasi lengkap
        $response = $this->actingAs($this->admin)->post(route('admin.skripsi.sk-bimbingan', $skripsi->id), [
            'nomor_sk_bimbingan' => 'SK/099/FPST/TI/2026',
            'tgl_sk_bimbingan' => '2026-09-01',
            'file_sk_bimbingan' => UploadedFile::fake()->create('sk_bimbingan.pdf', 500, 'application/pdf'),
        ]);
        $response->assertSessionHas('success');

        $skripsi->refresh();
        $this->assertEquals(StatusPengajuan::Selesai, $skripsi->status);
        $this->assertTrue($skripsi->canAjukanSeminar());

        // Verifikasi Notifikasi SK Terbit
        $this->assertDatabaseHas('notifikasi', [
            'user_id' => $this->mahasiswa->id,
            'judul' => 'SK Bimbingan Resmi Diterbitkan',
        ]);

        // ==========================================
        // FASE 2: Pengajuan Seminar Skripsi
        // ==========================================

        // 4. Mahasiswa ajukan seminar + 4 berkas
        $response = $this->actingAs($this->mahasiswa)->post(route('mahasiswa.seminar.store'), [
            'file_naskah_seminar' => UploadedFile::fake()->create('naskah_seminar.pdf', 800, 'application/pdf'),
            'file_acc_pembimbing' => UploadedFile::fake()->create('acc_seminar.pdf', 300, 'application/pdf'),
            'file_bukti_bayar_seminar' => UploadedFile::fake()->create('bayar_seminar.pdf', 300, 'application/pdf'),
            'file_toefl' => UploadedFile::fake()->create('toefl.pdf', 300, 'application/pdf'),
        ]);
        $response->assertRedirect(route('mahasiswa.seminar.index'));

        $seminar = SeminarSkripsi::first();
        $this->assertNotNull($seminar);

        // 5. Kaprodi tetapkan Dosen Penguji Seminar
        $response = $this->actingAs($this->kaprodi)->post(route('kaprodi.seminar.penguji', $seminar->id), [
            'penguji_seminar_id' => $this->dosen3->id,
        ]);
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('notifikasi', [
            'user_id' => $this->dosen3->id,
            'judul' => 'Penugasan Dosen Penguji Seminar',
        ]);

        // 5b. Dosen Penguji Seminar menyetujui penugasan
        $penugasanSem = \App\Models\PenugasanDosen::where('assignable_type', SeminarSkripsi::class)->where('dosen_id', $this->dosen3->id)->first();
        $this->actingAs($this->dosen3)->post(route('dosen.penugasan.respon', $penugasanSem->id), ['aksi' => 'terima']);

        // 6. Admin input jadwal & SK seminar, kemudian finalisasi nilai seminar
        $this->actingAs($this->admin)->post(route('admin.seminar.jadwal-sk', $seminar->id), [
            'tgl_seminar' => '2026-09-15',
            'jam_seminar' => '09:00 - 10:30',
            'ruangan' => 'Ruang Seminar 101',
            'nomor_undangan_seminar' => 'UND/SEM/01/2026',
            'nomor_sk_seminar' => 'SK-SEM/01/2026',
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.seminar.selesai', $seminar->id), [
            'nilai_seminar' => 87.50,
            'catatan' => 'Lulus seminar dengan perbaikan minor pada BAB 3.',
        ]);
        $response->assertSessionHas('success');

        $seminar->refresh();
        $this->assertEquals(StatusPengajuan::Selesai, $seminar->status);

        $this->assertDatabaseHas('notifikasi', [
            'user_id' => $this->mahasiswa->id,
            'judul' => 'Hasil & Nilai Seminar Telah Keluar',
        ]);

        // ==========================================
        // FASE 3: Pengajuan Sidang Skripsi
        // ==========================================

        // 7. Mahasiswa ajukan sidang skripsi + 4 berkas
        $response = $this->actingAs($this->mahasiswa)->post(route('mahasiswa.sidang.store'), [
            'file_naskah_sidang' => UploadedFile::fake()->create('naskah_final.pdf', 1200, 'application/pdf'),
            'file_acc_sidang' => UploadedFile::fake()->create('acc_sidang.pdf', 300, 'application/pdf'),
            'file_bebas_revisi_seminar' => UploadedFile::fake()->create('bebas_revisi.pdf', 300, 'application/pdf'),
            'file_bukti_bayar_sidang' => UploadedFile::fake()->create('bayar_sidang.pdf', 300, 'application/pdf'),
        ]);
        $response->assertRedirect(route('mahasiswa.sidang.index'));

        $sidang = SidangSkripsi::first();
        $this->assertNotNull($sidang);

        // 8a. Coba tetapkan Pembimbing 2 sebagai penguji sidang -> Harus diblokir oleh aturan sistem!
        $responseBlocked = $this->actingAs($this->kaprodi)->post(route('kaprodi.sidang.penguji', $sidang->id), [
            'penguji_1_id' => $this->dosen2->id,
            'penguji_2_id' => $this->dosen3->id,
        ]);
        $responseBlocked->assertSessionHasErrors(['penguji_1_id']);

        // 8. Kaprodi tetapkan 2 Dosen Penguji Sidang yang sah (Dosen 4 dan Dosen 3 / Penguji Seminar)
        $response = $this->actingAs($this->kaprodi)->post(route('kaprodi.sidang.penguji', $sidang->id), [
            'penguji_1_id' => $this->dosen4->id,
            'penguji_2_id' => $this->dosen3->id,
        ]);
        $response->assertSessionHas('success');

        // 8b. Penguji 1 & 2 menyetujui penugasan sidang
        $penugasanSdg1 = \App\Models\PenugasanDosen::where('assignable_type', SidangSkripsi::class)->where('dosen_id', $this->dosen4->id)->first();
        $this->actingAs($this->dosen4)->post(route('dosen.penugasan.respon', $penugasanSdg1->id), ['aksi' => 'terima']);

        $penugasanSdg2 = \App\Models\PenugasanDosen::where('assignable_type', SidangSkripsi::class)->where('dosen_id', $this->dosen3->id)->first();
        $this->actingAs($this->dosen3)->post(route('dosen.penugasan.respon', $penugasanSdg2->id), ['aksi' => 'terima']);

        // 9. Admin input jadwal & SK sidang, kemudian finalisasi kelulusan
        $this->actingAs($this->admin)->post(route('admin.sidang.jadwal-sk', $sidang->id), [
            'tgl_sidang' => '2026-09-25',
            'jam_sidang' => '13:00 - 15:00',
            'ruangan' => 'Ruang Sidang Skripsi',
            'nomor_undangan_sidang' => 'UND-SDG/01/2026',
            'nomor_sk_sidang' => 'SK-SDG/01/2026',
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.sidang.selesai', $sidang->id), [
            'nilai_sidang' => 90.00,
            'catatan' => 'Dinyatakan LULUS Sidang Skripsi dengan Predikat Sangat Memuaskan (A).',
        ]);
        $response->assertSessionHas('success');

        $sidang->refresh();
        $this->assertEquals(StatusPengajuan::Selesai, $sidang->status);

        $this->assertDatabaseHas('notifikasi', [
            'user_id' => $this->mahasiswa->id,
            'judul' => 'Selamat! Anda Dinyatakan LULUS Sidang Skripsi',
        ]);
    }

    public function test_notifikasi_flow_and_mark_as_read(): void
    {
        $notif = Notifikasi::create([
            'user_id' => $this->mahasiswa->id,
            'judul' => 'Pemberitahuan Uji Coba',
            'pesan' => 'Pesan uji coba',
            'dibaca' => false,
        ]);

        // Cek halaman notifikasi index
        $response = $this->actingAs($this->mahasiswa)->get(route('notifikasi.index'));
        $response->assertStatus(200);
        $response->assertSee('Pemberitahuan Uji Coba');

        // Tandai sebagai dibaca
        $this->actingAs($this->mahasiswa)->post(route('notifikasi.baca', $notif->id));
        $notif->refresh();
        $this->assertTrue($notif->dibaca);

        // Tandai semua sebagai dibaca
        Notifikasi::create([
            'user_id' => $this->mahasiswa->id,
            'judul' => 'Pemberitahuan 2',
            'pesan' => 'Pesan 2',
            'dibaca' => false,
        ]);

        $this->actingAs($this->mahasiswa)->post(route('notifikasi.baca-semua'));
        $this->assertEquals(0, $this->mahasiswa->unreadNotifikasiCount());
    }

    public function test_admin_utama_can_view_log_aktivitas(): void
    {
        AktivitasLog::create([
            'user_id' => $this->mahasiswa->id,
            'aksi' => 'Pengajuan Judul Skripsi',
            'deskripsi' => 'Deskripsi log uji coba',
            'ip_address' => '127.0.0.1',
        ]);

        // Admin utama dapat membuka log aktivitas
        $response = $this->actingAs($this->adminUtama)->get(route('admin.log-aktivitas.index'));
        $response->assertStatus(200);
        $response->assertSee('Log Aktivitas Sistem (Audit Trail)');
        $response->assertSee('Deskripsi log uji coba');

        // Mahasiswa tidak memiliki akses ke log aktivitas (403)
        $response = $this->actingAs($this->mahasiswa)->get(route('admin.log-aktivitas.index'));
        $response->assertStatus(403);
    }
}
