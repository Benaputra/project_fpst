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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class NotifikasiRoleTest extends TestCase
{
    use RefreshDatabase;

    private ProgramStudi $prodiTI;
    private ProgramStudi $prodiSI;
    private User $mahasiswa;
    private User $kaprodiTI;
    private User $kaprodiSI;
    private User $adminProdiTI;
    private User $adminProdiSI;
    private User $adminUtama;
    private User $pembimbing1;
    private User $pembimbing2;
    private User $pengujiSeminar;
    private User $pengujiSidang1;
    private User $pengujiSidang2;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        $this->prodiTI = ProgramStudi::create([
            'nama' => 'Teknik Informatika',
            'kode' => 'TI',
        ]);

        $this->prodiSI = ProgramStudi::create([
            'nama' => 'Sistem Informasi',
            'kode' => 'SI',
        ]);

        $this->mahasiswa = User::create([
            'name' => 'Budi Mahasiswa',
            'email' => 'mahasiswa@example.test',
            'nomor_induk' => '221000000010',
            'password' => 'password',
            'role' => UserRole::Mahasiswa,
            'program_studi_id' => $this->prodiTI->id,
        ]);

        $this->kaprodiTI = User::create([
            'name' => 'Dr. Ratna Wijaya (Kaprodi TI)',
            'email' => 'kaprodi.ti@example.test',
            'nomor_induk' => '1000000001',
            'password' => 'password',
            'role' => UserRole::Kaprodi,
            'program_studi_id' => $this->prodiTI->id,
        ]);

        $this->kaprodiSI = User::create([
            'name' => 'Dr. Surya (Kaprodi SI)',
            'email' => 'kaprodi.si@example.test',
            'nomor_induk' => '1000000002',
            'password' => 'password',
            'role' => UserRole::Kaprodi,
            'program_studi_id' => $this->prodiSI->id,
        ]);

        $this->adminProdiTI = User::create([
            'name' => 'Siti Admin Prodi TI',
            'email' => 'admin.prodi.ti@example.test',
            'nomor_induk' => 'ADM001',
            'password' => 'password',
            'role' => UserRole::AdminProdi,
            'program_studi_id' => $this->prodiTI->id,
        ]);

        $this->adminProdiSI = User::create([
            'name' => 'Joko Admin Prodi SI',
            'email' => 'admin.prodi.si@example.test',
            'nomor_induk' => 'ADM002',
            'password' => 'password',
            'role' => UserRole::AdminProdi,
            'program_studi_id' => $this->prodiSI->id,
        ]);

        $this->adminUtama = User::create([
            'name' => 'Bambang Admin Utama',
            'email' => 'admin.utama@example.test',
            'nomor_induk' => 'ADM-UTAMA',
            'password' => 'password',
            'role' => UserRole::AdminUtama,
        ]);

        $this->pembimbing1 = User::create([
            'name' => 'Dewi Lestari, M.Kom. (Dosen 1)',
            'email' => 'dewi@example.test',
            'nomor_induk' => '1000000003',
            'password' => 'password',
            'role' => UserRole::Dosen,
            'program_studi_id' => $this->prodiTI->id,
        ]);

        $this->pembimbing2 = User::create([
            'name' => 'Ahmad Fauzi, M.Kom. (Dosen 2)',
            'email' => 'ahmad@example.test',
            'nomor_induk' => '1000000004',
            'password' => 'password',
            'role' => UserRole::Dosen,
            'program_studi_id' => $this->prodiTI->id,
        ]);

        $this->pengujiSeminar = User::create([
            'name' => 'Fajar Nugroho, M.Cs. (Penguji Seminar)',
            'email' => 'fajar@example.test',
            'nomor_induk' => '1000000006',
            'password' => 'password',
            'role' => UserRole::Dosen,
            'program_studi_id' => $this->prodiTI->id,
        ]);

        $this->pengujiSidang1 = User::create([
            'name' => 'Dr. Hendra (Penguji Sidang 1)',
            'email' => 'hendra@example.test',
            'nomor_induk' => '1000000007',
            'password' => 'password',
            'role' => UserRole::Dosen,
            'program_studi_id' => $this->prodiTI->id,
        ]);

        $this->pengujiSidang2 = User::create([
            'name' => 'Dr. Maya (Penguji Sidang 2)',
            'email' => 'maya@example.test',
            'nomor_induk' => '1000000008',
            'password' => 'password',
            'role' => UserRole::Dosen,
            'program_studi_id' => $this->prodiTI->id,
        ]);
    }

    /**
     * Test notifikasi saat Mahasiswa mengajukan judul skripsi baru.
     * Admin Utama, Kaprodi TI, dan Admin Prodi TI menerima notif.
     * Kaprodi & Admin Prodi prodi lain (SI) TIDAK menerima notif.
     */
    public function test_notifikasi_saat_mahasiswa_mengajukan_skripsi(): void
    {
        $this->actingAs($this->mahasiswa)->post(route('mahasiswa.skripsi.store'), [
            'judul' => 'Sistem Informasi Akademik Berbasis Web',
            'abstrak' => 'Penelitian ini mengembangkan SIAKAD.',
            'file_proposal' => UploadedFile::fake()->create('proposal.pdf', 500, 'application/pdf'),
            'file_transkrip' => UploadedFile::fake()->create('transkrip.pdf', 500, 'application/pdf'),
            'file_bukti_bayar' => UploadedFile::fake()->create('bukti_bayar.pdf', 500, 'application/pdf'),
        ]);

        // Verifikasi penerima notifikasi pengajuan baru:
        $this->assertEquals(1, $this->adminUtama->notifikasi()->where('judul', 'Pengajuan Judul Skripsi Baru')->count(), 'Admin Utama harus menerima notif');
        $this->assertEquals(1, $this->kaprodiTI->notifikasi()->where('judul', 'Pengajuan Judul Skripsi Baru')->count(), 'Kaprodi TI harus menerima notif');
        $this->assertEquals(1, $this->adminProdiTI->notifikasi()->where('judul', 'Pengajuan Judul Skripsi Baru')->count(), 'Admin Prodi TI harus menerima notif');

        // Prodi lain tidak menerima notif
        $this->assertEquals(0, $this->kaprodiSI->notifikasi()->count(), 'Kaprodi SI tidak boleh menerima notif TI');
        $this->assertEquals(0, $this->adminProdiSI->notifikasi()->count(), 'Admin Prodi SI tidak boleh menerima notif TI');
    }

    /**
     * Test distribusi notifikasi saat Kaprodi menetapkan Dosen Pembimbing Skripsi.
     * Sesuai ketentuan: Hubungan langsung Kaprodi ke Mahasiswa & Pembimbing.
     * Hasil: Mahasiswa, Pembimbing 1 & 2 menerima notif; Admin Utama, Kaprodi, Admin Prodi TIDAK menerima notif.
     */
    public function test_notifikasi_distribusi_saat_kaprodi_menetapkan_pembimbing_skripsi(): void
    {
        $skripsi = PengajuanSkripsi::create([
            'mahasiswa_id' => $this->mahasiswa->id,
            'program_studi_id' => $this->prodiTI->id,
            'judul' => 'Sistem Informasi Akademik Berbasis Web',
            'status' => StatusPengajuan::Diajukan,
            'file_proposal' => 'skripsi/proposal/dummy.pdf',
            'file_transkrip' => 'skripsi/transkrip/dummy.pdf',
            'file_bukti_bayar' => 'skripsi/bukti_bayar/dummy.pdf',
        ]);

        $this->actingAs($this->kaprodiTI)->post(route('kaprodi.skripsi.review', $skripsi->id), [
            'action' => 'terima',
            'pembimbing_1_id' => $this->pembimbing1->id,
            'pembimbing_2_id' => $this->pembimbing2->id,
            'catatan' => 'Silakan lanjutkan bimbingan.',
        ]);

        // Role yang MENERIMA notifikasi:
        $this->assertEquals(1, $this->mahasiswa->notifikasi()->where('judul', 'Judul Disetujui & Pembimbing Ditetapkan')->count());
        $this->assertEquals(1, $this->pembimbing1->notifikasi()->where('judul', 'Penugasan Pembimbing Utama (1)')->count());
        $this->assertEquals(1, $this->pembimbing2->notifikasi()->where('judul', 'Penugasan Pembimbing Pendamping (2)')->count());

        // Role yang TIDAK MENERIMA notifikasi (sesuai arahan user):
        $this->assertEquals(0, $this->adminUtama->notifikasi()->count(), 'Admin Utama tidak menerima penetapan pembimbing');
        $this->assertEquals(0, $this->kaprodiTI->notifikasi()->count(), 'Kaprodi tidak menerima penetapan pembimbing');
        $this->assertEquals(0, $this->adminProdiTI->notifikasi()->count(), 'Admin Prodi tidak menerima penetapan pembimbing');
    }

    /**
     * Test distribusi notifikasi saat Admin menerbitkan SK Bimbingan.
     * Hasil: Mahasiswa, Pembimbing 1 & 2, serta Kaprodi TI dan Admin Utama menerima notif.
     */
    public function test_notifikasi_distribusi_saat_admin_menerbitkan_sk_bimbingan(): void
    {
        $skripsi = PengajuanSkripsi::create([
            'mahasiswa_id' => $this->mahasiswa->id,
            'program_studi_id' => $this->prodiTI->id,
            'judul' => 'Sistem Informasi Akademik Berbasis Web',
            'pembimbing_1_id' => $this->pembimbing1->id,
            'pembimbing_2_id' => $this->pembimbing2->id,
            'status' => StatusPengajuan::Diproses,
            'file_proposal' => 'skripsi/proposal/dummy.pdf',
            'file_transkrip' => 'skripsi/transkrip/dummy.pdf',
            'file_bukti_bayar' => 'skripsi/bukti_bayar/dummy.pdf',
        ]);

        $this->actingAs($this->adminProdiTI)->post(route('admin.skripsi.sk-bimbingan', $skripsi->id), [
            'nomor_sk_bimbingan' => 'SK/001/FPST/2026',
            'tgl_sk_bimbingan' => '2026-09-01',
            'file_sk_bimbingan' => UploadedFile::fake()->create('sk.pdf', 500, 'application/pdf'),
        ]);

        // Mahasiswa & Dosen menerima notif SK
        $this->assertEquals(1, $this->mahasiswa->notifikasi()->where('judul', 'SK Bimbingan Resmi Diterbitkan')->count());
        $this->assertEquals(1, $this->pembimbing1->notifikasi()->where('judul', 'SK Bimbingan Mahasiswa Diterbitkan')->count());
        $this->assertEquals(1, $this->pembimbing2->notifikasi()->where('judul', 'SK Bimbingan Mahasiswa Diterbitkan')->count());

        // Kaprodi TI & Admin Utama menerima notif penerbitan SK
        $this->assertEquals(1, $this->kaprodiTI->notifikasi()->where('judul', 'Penerbitan SK Bimbingan Mahasiswa')->count());
        $this->assertEquals(1, $this->adminUtama->notifikasi()->where('judul', 'Penerbitan SK Bimbingan Mahasiswa')->count());
    }

    /**
     * Test notifikasi saat Mahasiswa mendaftar/mengajukan seminar.
     * Hasil: Admin Utama, Kaprodi TI, dan Admin Prodi TI menerima notifikasi pendaftaran seminar.
     */
    public function test_notifikasi_saat_mahasiswa_mengajukan_seminar(): void
    {
        $skripsi = PengajuanSkripsi::create([
            'mahasiswa_id' => $this->mahasiswa->id,
            'program_studi_id' => $this->prodiTI->id,
            'judul' => 'Sistem Informasi Akademik Berbasis Web',
            'pembimbing_1_id' => $this->pembimbing1->id,
            'pembimbing_2_id' => $this->pembimbing2->id,
            'nomor_sk_bimbingan' => 'SK/001/FPST/2026',
            'file_sk_bimbingan' => 'skripsi/sk/dummy.pdf',
            'status' => StatusPengajuan::Selesai,
            'file_proposal' => 'skripsi/proposal/dummy.pdf',
            'file_transkrip' => 'skripsi/transkrip/dummy.pdf',
            'file_bukti_bayar' => 'skripsi/bukti_bayar/dummy.pdf',
        ]);

        $this->actingAs($this->mahasiswa)->post(route('mahasiswa.seminar.store'), [
            'file_naskah_seminar' => UploadedFile::fake()->create('naskah.pdf', 500, 'application/pdf'),
            'file_acc_pembimbing' => UploadedFile::fake()->create('acc.pdf', 500, 'application/pdf'),
            'file_bukti_bayar_seminar' => UploadedFile::fake()->create('bayar.pdf', 500, 'application/pdf'),
            'file_toefl' => UploadedFile::fake()->create('toefl.pdf', 500, 'application/pdf'),
        ]);

        // Verifikasi notifikasi pengajuan seminar ke pengelola
        $this->assertEquals(1, $this->adminUtama->notifikasi()->where('judul', 'Pendaftaran Seminar Skripsi Baru')->count());
        $this->assertEquals(1, $this->kaprodiTI->notifikasi()->where('judul', 'Pendaftaran Seminar Skripsi Baru')->count());
        $this->assertEquals(1, $this->adminProdiTI->notifikasi()->where('judul', 'Pendaftaran Seminar Skripsi Baru')->count());
    }

    /**
     * Test distribusi notifikasi saat Kaprodi menetapkan Dosen Penguji Seminar.
     * Hasil: Mahasiswa, Dosen Penguji, Admin Prodi TI, dan Admin Utama menerima notif.
     */
    public function test_notifikasi_distribusi_saat_kaprodi_menetapkan_penguji_seminar(): void
    {
        $skripsi = PengajuanSkripsi::create([
            'mahasiswa_id' => $this->mahasiswa->id,
            'program_studi_id' => $this->prodiTI->id,
            'judul' => 'Sistem Informasi Akademik Berbasis Web',
            'pembimbing_1_id' => $this->pembimbing1->id,
            'pembimbing_2_id' => $this->pembimbing2->id,
            'status' => StatusPengajuan::Selesai,
            'file_proposal' => 'skripsi/proposal/dummy.pdf',
            'file_transkrip' => 'skripsi/transkrip/dummy.pdf',
            'file_bukti_bayar' => 'skripsi/bukti_bayar/dummy.pdf',
        ]);

        $seminar = SeminarSkripsi::create([
            'pengajuan_skripsi_id' => $skripsi->id,
            'status' => StatusPengajuan::Diajukan,
            'file_naskah_seminar' => 'seminar/naskah/dummy.pdf',
            'file_acc_pembimbing' => 'seminar/acc/dummy.pdf',
            'file_bukti_bayar_seminar' => 'seminar/bayar/dummy.pdf',
            'file_toefl' => 'seminar/toefl/dummy.pdf',
        ]);

        $this->actingAs($this->kaprodiTI)->post(route('kaprodi.seminar.penguji', $seminar->id), [
            'penguji_seminar_id' => $this->pengujiSeminar->id,
        ]);

        // Role yang menerima notifikasi:
        $this->assertEquals(1, $this->mahasiswa->notifikasi()->where('judul', 'Dosen Penguji Seminar Ditetapkan')->count());
        $this->assertEquals(1, $this->pengujiSeminar->notifikasi()->where('judul', 'Penugasan Dosen Penguji Seminar')->count());
        $this->assertEquals(1, $this->adminProdiTI->notifikasi()->where('judul', 'Dosen Penguji Seminar Telah Ditetapkan')->count());
        $this->assertEquals(1, $this->adminUtama->notifikasi()->where('judul', 'Dosen Penguji Seminar Telah Ditetapkan')->count());
    }

    /**
     * Test distribusi notifikasi saat Admin menetapkan Jadwal & SK Seminar.
     * Hasil: Mahasiswa, Pembimbing 1 & 2, Dosen Penguji Seminar, Kaprodi TI, dan Admin Utama menerima notifikasi.
     */
    public function test_notifikasi_distribusi_saat_admin_menjadwalkan_seminar(): void
    {
        $skripsi = PengajuanSkripsi::create([
            'mahasiswa_id' => $this->mahasiswa->id,
            'program_studi_id' => $this->prodiTI->id,
            'judul' => 'Sistem Informasi Akademik Berbasis Web',
            'pembimbing_1_id' => $this->pembimbing1->id,
            'pembimbing_2_id' => $this->pembimbing2->id,
            'status' => StatusPengajuan::Selesai,
            'file_proposal' => 'skripsi/proposal/dummy.pdf',
            'file_transkrip' => 'skripsi/transkrip/dummy.pdf',
            'file_bukti_bayar' => 'skripsi/bukti_bayar/dummy.pdf',
        ]);

        $seminar = SeminarSkripsi::create([
            'pengajuan_skripsi_id' => $skripsi->id,
            'penguji_seminar_id' => $this->pengujiSeminar->id,
            'status' => StatusPengajuan::Diproses,
            'file_naskah_seminar' => 'seminar/naskah/dummy.pdf',
            'file_acc_pembimbing' => 'seminar/acc/dummy.pdf',
            'file_bukti_bayar_seminar' => 'seminar/bayar/dummy.pdf',
            'file_toefl' => 'seminar/toefl/dummy.pdf',
        ]);

        $this->actingAs($this->adminProdiTI)->post(route('admin.seminar.jadwal-sk', $seminar->id), [
            'tgl_seminar' => '2026-09-15',
            'jam_seminar' => '09:00 - 10:30',
            'ruangan' => 'Ruang Seminar 101',
            'nomor_undangan_seminar' => 'UND/SEM/01/2026',
            'nomor_sk_seminar' => 'SK-SEM/01/2026',
        ]);

        // Undangan ke tim seminar:
        $this->assertEquals(1, $this->mahasiswa->notifikasi()->where('judul', 'Jadwal & Dokumen Seminar Diterbitkan')->count());
        $this->assertEquals(1, $this->pembimbing1->notifikasi()->where('judul', 'Undangan Seminar Skripsi Mahasiswa Bimbingan')->count());
        $this->assertEquals(1, $this->pembimbing2->notifikasi()->where('judul', 'Undangan Seminar Skripsi Mahasiswa Bimbingan')->count());
        $this->assertEquals(1, $this->pengujiSeminar->notifikasi()->where('judul', 'Undangan & Jadwal Ujian Seminar Mahasiswa')->count());

        // Notifikasi ke Kaprodi TI & Admin Utama:
        $this->assertEquals(1, $this->kaprodiTI->notifikasi()->where('judul', 'Jadwal Seminar Skripsi Ditetapkan')->count());
        $this->assertEquals(1, $this->adminUtama->notifikasi()->where('judul', 'Jadwal Seminar Skripsi Ditetapkan')->count());
    }

    /**
     * Test distribusi notifikasi saat Admin menginput nilai & menyelesaikan seminar.
     * Hasil: Mahasiswa, Kaprodi TI, dan Admin Utama menerima notifikasi hasil nilai seminar.
     */
    public function test_notifikasi_distribusi_saat_admin_menyelesaikan_seminar(): void
    {
        $skripsi = PengajuanSkripsi::create([
            'mahasiswa_id' => $this->mahasiswa->id,
            'program_studi_id' => $this->prodiTI->id,
            'judul' => 'Sistem Informasi Akademik Berbasis Web',
            'pembimbing_1_id' => $this->pembimbing1->id,
            'pembimbing_2_id' => $this->pembimbing2->id,
            'status' => StatusPengajuan::Selesai,
            'file_proposal' => 'skripsi/proposal/dummy.pdf',
            'file_transkrip' => 'skripsi/transkrip/dummy.pdf',
            'file_bukti_bayar' => 'skripsi/bukti_bayar/dummy.pdf',
        ]);

        $seminar = SeminarSkripsi::create([
            'pengajuan_skripsi_id' => $skripsi->id,
            'penguji_seminar_id' => $this->pengujiSeminar->id,
            'tgl_seminar' => '2026-09-15',
            'jam_seminar' => '09:00 - 10:30',
            'ruangan' => 'Ruang Seminar 101',
            'status' => StatusPengajuan::Diproses,
            'file_naskah_seminar' => 'seminar/naskah/dummy.pdf',
            'file_acc_pembimbing' => 'seminar/acc/dummy.pdf',
            'file_bukti_bayar_seminar' => 'seminar/bayar/dummy.pdf',
            'file_toefl' => 'seminar/toefl/dummy.pdf',
        ]);

        $this->actingAs($this->adminProdiTI)->post(route('admin.seminar.selesai', $seminar->id), [
            'nilai_seminar' => 88.00,
            'catatan' => 'Lulus seminar.',
        ]);

        // Mahasiswa menerima notif kelulusan
        $this->assertEquals(1, $this->mahasiswa->notifikasi()->where('judul', 'Hasil & Nilai Seminar Telah Keluar')->count());

        // Kaprodi TI & Admin Utama menerima notif hasil seminar
        $this->assertEquals(1, $this->kaprodiTI->notifikasi()->where('judul', 'Hasil & Nilai Seminar Mahasiswa')->count());
        $this->assertEquals(1, $this->adminUtama->notifikasi()->where('judul', 'Hasil & Nilai Seminar Mahasiswa')->count());
    }

    /**
     * Test notifikasi saat Mahasiswa mendaftar/mengajukan sidang.
     * Hasil: Admin Utama, Kaprodi TI, dan Admin Prodi TI menerima notifikasi pendaftaran sidang.
     */
    public function test_notifikasi_saat_mahasiswa_mengajukan_sidang(): void
    {
        $skripsi = PengajuanSkripsi::create([
            'mahasiswa_id' => $this->mahasiswa->id,
            'program_studi_id' => $this->prodiTI->id,
            'judul' => 'Sistem Informasi Akademik Berbasis Web',
            'pembimbing_1_id' => $this->pembimbing1->id,
            'pembimbing_2_id' => $this->pembimbing2->id,
            'status' => StatusPengajuan::Selesai,
            'file_proposal' => 'skripsi/proposal/dummy.pdf',
            'file_transkrip' => 'skripsi/transkrip/dummy.pdf',
            'file_bukti_bayar' => 'skripsi/bukti_bayar/dummy.pdf',
        ]);

        SeminarSkripsi::create([
            'pengajuan_skripsi_id' => $skripsi->id,
            'penguji_seminar_id' => $this->pengujiSeminar->id,
            'nilai_seminar' => 88.00,
            'status' => StatusPengajuan::Selesai,
            'file_naskah_seminar' => 'seminar/naskah/dummy.pdf',
            'file_acc_pembimbing' => 'seminar/acc/dummy.pdf',
            'file_bukti_bayar_seminar' => 'seminar/bayar/dummy.pdf',
            'file_toefl' => 'seminar/toefl/dummy.pdf',
        ]);

        $this->actingAs($this->mahasiswa)->post(route('mahasiswa.sidang.store'), [
            'file_naskah_sidang' => UploadedFile::fake()->create('naskah_sidang.pdf', 500, 'application/pdf'),
            'file_acc_sidang' => UploadedFile::fake()->create('acc_sidang.pdf', 500, 'application/pdf'),
            'file_bebas_revisi_seminar' => UploadedFile::fake()->create('bebas_revisi.pdf', 500, 'application/pdf'),
            'file_bukti_bayar_sidang' => UploadedFile::fake()->create('bayar_sidang.pdf', 500, 'application/pdf'),
        ]);

        // Verifikasi notifikasi pendaftaran sidang ke pengelola
        $this->assertEquals(1, $this->adminUtama->notifikasi()->where('judul', 'Pendaftaran Sidang Skripsi Baru')->count());
        $this->assertEquals(1, $this->kaprodiTI->notifikasi()->where('judul', 'Pendaftaran Sidang Skripsi Baru')->count());
        $this->assertEquals(1, $this->adminProdiTI->notifikasi()->where('judul', 'Pendaftaran Sidang Skripsi Baru')->count());
    }

    /**
     * Test distribusi notifikasi saat Kaprodi menetapkan 2 Penguji Sidang.
     * Hasil: Mahasiswa, Penguji 1 & 2, Admin Prodi TI, dan Admin Utama menerima notif.
     */
    public function test_notifikasi_distribusi_saat_kaprodi_menetapkan_penguji_sidang(): void
    {
        $skripsi = PengajuanSkripsi::create([
            'mahasiswa_id' => $this->mahasiswa->id,
            'program_studi_id' => $this->prodiTI->id,
            'judul' => 'Sistem Informasi Akademik Berbasis Web',
            'pembimbing_1_id' => $this->pembimbing1->id,
            'pembimbing_2_id' => $this->pembimbing2->id,
            'status' => StatusPengajuan::Selesai,
            'file_proposal' => 'skripsi/proposal/dummy.pdf',
            'file_transkrip' => 'skripsi/transkrip/dummy.pdf',
            'file_bukti_bayar' => 'skripsi/bukti_bayar/dummy.pdf',
        ]);

        $sidang = SidangSkripsi::create([
            'pengajuan_skripsi_id' => $skripsi->id,
            'status' => StatusPengajuan::Diajukan,
            'file_naskah_sidang' => 'sidang/naskah/dummy.pdf',
            'file_acc_sidang' => 'sidang/acc/dummy.pdf',
            'file_bebas_revisi_seminar' => 'sidang/revisi/dummy.pdf',
            'file_bukti_bayar_sidang' => 'sidang/bayar/dummy.pdf',
        ]);

        $this->actingAs($this->kaprodiTI)->post(route('kaprodi.sidang.penguji', $sidang->id), [
            'penguji_1_id' => $this->pengujiSidang1->id,
            'penguji_2_id' => $this->pengujiSidang2->id,
        ]);

        // Mahasiswa & Penguji menerima notifikasi
        $this->assertEquals(1, $this->mahasiswa->notifikasi()->where('judul', '2 Dosen Penguji Sidang Ditetapkan')->count());
        $this->assertEquals(1, $this->pengujiSidang1->notifikasi()->where('judul', 'Penugasan Penguji 1 Sidang Skripsi')->count());
        $this->assertEquals(1, $this->pengujiSidang2->notifikasi()->where('judul', 'Penugasan Penguji 2 Sidang Skripsi')->count());

        // Admin Prodi TI & Admin Utama menerima notifikasi
        $this->assertEquals(1, $this->adminProdiTI->notifikasi()->where('judul', 'Dewan Penguji Sidang Telah Ditetapkan')->count());
        $this->assertEquals(1, $this->adminUtama->notifikasi()->where('judul', 'Dewan Penguji Sidang Telah Ditetapkan')->count());
    }

    /**
     * Test distribusi notifikasi pada tahap Sidang Skripsi (Penjadwalan & SK Sidang).
     * Hasil: Mahasiswa, Pembimbing 1 & 2, Penguji 1 & 2, Kaprodi TI, dan Admin Utama menerima notifikasi.
     */
    public function test_notifikasi_distribusi_saat_sidang_skripsi_dijadwalkan(): void
    {
        $skripsi = PengajuanSkripsi::create([
            'mahasiswa_id' => $this->mahasiswa->id,
            'program_studi_id' => $this->prodiTI->id,
            'judul' => 'Sistem Informasi Akademik Berbasis Web',
            'pembimbing_1_id' => $this->pembimbing1->id,
            'pembimbing_2_id' => $this->pembimbing2->id,
            'status' => StatusPengajuan::Selesai,
            'file_proposal' => 'skripsi/proposal/dummy.pdf',
            'file_transkrip' => 'skripsi/transkrip/dummy.pdf',
            'file_bukti_bayar' => 'skripsi/bukti_bayar/dummy.pdf',
        ]);

        $sidang = SidangSkripsi::create([
            'pengajuan_skripsi_id' => $skripsi->id,
            'penguji_1_id' => $this->pengujiSidang1->id,
            'penguji_2_id' => $this->pengujiSidang2->id,
            'status' => StatusPengajuan::Diproses,
            'file_naskah_sidang' => 'sidang/naskah/dummy.pdf',
            'file_acc_sidang' => 'sidang/acc/dummy.pdf',
            'file_bebas_revisi_seminar' => 'sidang/revisi/dummy.pdf',
            'file_bukti_bayar_sidang' => 'sidang/bayar/dummy.pdf',
        ]);

        $this->actingAs($this->adminProdiTI)->post(route('admin.sidang.jadwal-sk', $sidang->id), [
            'tgl_sidang' => '2026-09-25',
            'jam_sidang' => '13:00 - 15:00',
            'ruangan' => 'Ruang Sidang Utama',
            'nomor_undangan_sidang' => 'UND-SDG/01/2026',
            'nomor_sk_sidang' => 'SK-SDG/01/2026',
        ]);

        // Mahasiswa, Pembimbing 1 & 2, Penguji 1 & 2 menerima notif undangan sidang
        $this->assertEquals(1, $this->mahasiswa->notifikasi()->where('judul', 'Jadwal & Dokumen Sidang Diterbitkan')->count());
        $this->assertEquals(1, $this->pembimbing1->notifikasi()->where('judul', 'Undangan Sidang Skripsi Mahasiswa Bimbingan')->count());
        $this->assertEquals(1, $this->pembimbing2->notifikasi()->where('judul', 'Undangan Sidang Skripsi Mahasiswa Bimbingan')->count());
        $this->assertEquals(1, $this->pengujiSidang1->notifikasi()->where('judul', 'Jadwal Sidang Skripsi Mahasiswa Ditetapkan')->count());
        $this->assertEquals(1, $this->pengujiSidang2->notifikasi()->where('judul', 'Jadwal Sidang Skripsi Mahasiswa Ditetapkan')->count());

        // Kaprodi TI & Admin Utama menerima notifikasi
        $this->assertEquals(1, $this->kaprodiTI->notifikasi()->where('judul', 'Jadwal Sidang Skripsi Ditetapkan')->count());
        $this->assertEquals(1, $this->adminUtama->notifikasi()->where('judul', 'Jadwal Sidang Skripsi Ditetapkan')->count());
    }

    /**
     * Test distribusi notifikasi saat Admin menginput nilai akhir & menyelesaikan sidang.
     * Hasil: Mahasiswa, Kaprodi TI, dan Admin Utama menerima notifikasi kelulusan.
     */
    public function test_notifikasi_distribusi_saat_admin_menyelesaikan_sidang(): void
    {
        $skripsi = PengajuanSkripsi::create([
            'mahasiswa_id' => $this->mahasiswa->id,
            'program_studi_id' => $this->prodiTI->id,
            'judul' => 'Sistem Informasi Akademik Berbasis Web',
            'pembimbing_1_id' => $this->pembimbing1->id,
            'pembimbing_2_id' => $this->pembimbing2->id,
            'status' => StatusPengajuan::Selesai,
            'file_proposal' => 'skripsi/proposal/dummy.pdf',
            'file_transkrip' => 'skripsi/transkrip/dummy.pdf',
            'file_bukti_bayar' => 'skripsi/bukti_bayar/dummy.pdf',
        ]);

        $sidang = SidangSkripsi::create([
            'pengajuan_skripsi_id' => $skripsi->id,
            'penguji_1_id' => $this->pengujiSidang1->id,
            'penguji_2_id' => $this->pengujiSidang2->id,
            'tgl_sidang' => '2026-09-25',
            'jam_sidang' => '13:00 - 15:00',
            'ruangan' => 'Ruang Sidang Utama',
            'status' => StatusPengajuan::Diproses,
            'file_naskah_sidang' => 'sidang/naskah/dummy.pdf',
            'file_acc_sidang' => 'sidang/acc/dummy.pdf',
            'file_bebas_revisi_seminar' => 'sidang/revisi/dummy.pdf',
            'file_bukti_bayar_sidang' => 'sidang/bayar/dummy.pdf',
        ]);

        $this->actingAs($this->adminProdiTI)->post(route('admin.sidang.selesai', $sidang->id), [
            'nilai_sidang' => 92.50,
            'catatan' => 'Lulus dengan predikat Pujian.',
        ]);

        // Mahasiswa menerima notif kelulusan
        $this->assertEquals(1, $this->mahasiswa->notifikasi()->where('judul', 'Selamat! Anda Dinyatakan LULUS Sidang Skripsi')->count());

        // Kaprodi TI & Admin Utama menerima notif hasil akhir sidang
        $this->assertEquals(1, $this->kaprodiTI->notifikasi()->where('judul', 'Hasil & Nilai Akhir Sidang Skripsi')->count());
        $this->assertEquals(1, $this->adminUtama->notifikasi()->where('judul', 'Hasil & Nilai Akhir Sidang Skripsi')->count());
    }
}
