<?php

namespace Tests\Feature;

use App\Enums\StatusPengajuan;
use App\Enums\StatusPenugasanDosen;
use App\Enums\UserRole;
use App\Models\AktivitasLog;
use App\Models\Notifikasi;
use App\Models\PengajuanSkripsi;
use App\Models\PenugasanDosen;
use App\Models\ProgramStudi;
use App\Models\SeminarSkripsi;
use App\Models\SidangSkripsi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PenugasanDosenWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private ProgramStudi $prodi;
    private User $kaprodi;
    private User $adminUtama;
    private User $adminProdi;
    private User $dosen1;
    private User $dosen2;
    private User $dosen3;
    private User $mahasiswa;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prodi = ProgramStudi::create([
            'nama' => 'Agroteknologi',
            'kode' => 'AGT',
            'jenjang' => 'S1',
        ]);

        $this->kaprodi = User::factory()->create([
            'role' => UserRole::Kaprodi,
            'program_studi_id' => $this->prodi->id,
            'name' => 'Kaprodi TI',
            'email' => 'kaprodi@fpst.ac.id',
            'nomor_induk' => 'KAPRODI001',
        ]);

        $this->adminUtama = User::factory()->create([
            'role' => UserRole::AdminUtama,
            'program_studi_id' => null,
            'name' => 'Super Admin FPST',
            'email' => 'superadmin@fpst.ac.id',
            'nomor_induk' => 'ADMIN000',
        ]);

        $this->adminProdi = User::factory()->create([
            'role' => UserRole::AdminProdi,
            'program_studi_id' => $this->prodi->id,
            'name' => 'Admin Prodi TI',
            'email' => 'admin.ti@fpst.ac.id',
            'nomor_induk' => 'ADMINTI01',
        ]);

        $this->dosen1 = User::factory()->create([
            'role' => UserRole::Dosen,
            'program_studi_id' => $this->prodi->id,
            'name' => 'Dr. Budi Santoso, M.Kom',
            'email' => 'budi@fpst.ac.id',
            'nomor_induk' => 'DOSEN001',
        ]);

        $this->dosen2 = User::factory()->create([
            'role' => UserRole::Dosen,
            'program_studi_id' => $this->prodi->id,
            'name' => 'Siti Aminah, M.T',
            'email' => 'siti@fpst.ac.id',
            'nomor_induk' => 'DOSEN002',
        ]);

        $this->dosen3 = User::factory()->create([
            'role' => UserRole::Dosen,
            'program_studi_id' => $this->prodi->id,
            'name' => 'Rudi Hermawan, Ph.D',
            'email' => 'rudi@fpst.ac.id',
            'nomor_induk' => 'DOSEN003',
        ]);

        $this->mahasiswa = User::factory()->create([
            'role' => UserRole::Mahasiswa,
            'program_studi_id' => $this->prodi->id,
            'name' => 'Ahmad Dani',
            'email' => 'ahmad@fpst.ac.id',
            'nomor_induk' => 'NIM2026001',
        ]);
    }

    public function test_penugasan_kaprodi_membuat_status_menunggu_dan_dosen_bisa_menyetujui(): void
    {
        $skripsi = PengajuanSkripsi::create([
            'mahasiswa_id' => $this->mahasiswa->id,
            'program_studi_id' => $this->prodi->id,
            'judul' => 'Perancangan Sistem Rekomendasi Kampus Merdeka',
            'abstrak' => 'Abstrak penelitian...',
            'status' => StatusPengajuan::Diajukan,
        ]);

        // Kaprodi menetapkan Dosen 1 sebagai pembimbing 1
        $response = $this->actingAs($this->kaprodi)->post(route('kaprodi.skripsi.review', $skripsi->id), [
            'action' => 'terima',
            'pembimbing_1_id' => $this->dosen1->id,
        ]);
        $response->assertSessionHas('success');

        // Pastikan record di tabel penugasan_dosen tercipta dengan status MENUNGGU
        $penugasan = PenugasanDosen::where('assignable_type', PengajuanSkripsi::class)
            ->where('assignable_id', $skripsi->id)
            ->where('dosen_id', $this->dosen1->id)
            ->first();

        $this->assertNotNull($penugasan);
        $this->assertEquals(StatusPenugasanDosen::Menunggu, $penugasan->status);
        $this->assertFalse($penugasan->is_mandat_admin_utama);
        $this->assertEquals('pembimbing_1', $penugasan->peran);
        $this->assertEquals($this->kaprodi->id, $penugasan->ditugaskan_oleh);

        // Notifikasi penugasan masuk ke Dosen 1
        $this->assertDatabaseHas('notifikasi', [
            'user_id' => $this->dosen1->id,
            'judul' => 'Penugasan Pembimbing Utama (1)',
        ]);

        // Pembimbing belum terkonfirmasi
        $skripsi->refresh();
        $this->assertFalse($skripsi->isPembimbingConfirmed());

        // Dosen 1 menyetujui penugasan
        $responseDosen = $this->actingAs($this->dosen1)->post(route('dosen.penugasan.respon', $penugasan->id), [
            'aksi' => 'terima',
        ]);
        $responseDosen->assertSessionHas('success');

        $penugasan->refresh();
        $this->assertEquals(StatusPenugasanDosen::Disetujui, $penugasan->status);
        $this->assertNotNull($penugasan->direspon_pada);

        // Sekarang pembimbing sudah confirmed
        $skripsi->refresh();
        $this->assertTrue($skripsi->isPembimbingConfirmed());

        // Log aktivitas mencatat respon penerimaan dosen
        $this->assertDatabaseHas('aktivitas_log', [
            'user_id' => $this->dosen1->id,
            'aksi' => 'Persetujuan Penugasan Dosen',
        ]);
    }

    public function test_dosen_dapat_menolak_penugasan_dengan_alasan_dan_slot_terbuka_kembali(): void
    {
        $skripsi = PengajuanSkripsi::create([
            'mahasiswa_id' => $this->mahasiswa->id,
            'program_studi_id' => $this->prodi->id,
            'judul' => 'Implementasi Deep Learning Pada Deteksi Anomali Jaringan',
            'abstrak' => 'Abstrak...',
            'status' => StatusPengajuan::Diajukan,
        ]);

        // Kaprodi menetapkan Dosen 1 sebagai pembimbing 1
        $this->actingAs($this->kaprodi)->post(route('kaprodi.skripsi.review', $skripsi->id), [
            'action' => 'terima',
            'pembimbing_1_id' => $this->dosen1->id,
        ]);

        $penugasan = PenugasanDosen::where('assignable_type', PengajuanSkripsi::class)
            ->where('dosen_id', $this->dosen1->id)
            ->first();

        // Validasi: Alasan wajib minimal 5 karakter
        $resInvalid = $this->actingAs($this->dosen1)->post(route('dosen.penugasan.respon', $penugasan->id), [
            'aksi' => 'tolak',
            'alasan_penolakan' => 'Tdk',
        ]);
        $resInvalid->assertSessionHasErrors('alasan_penolakan');

        // Dosen 1 menolak dengan alasan lengkap dan merekomendasikan Dosen 2
        $resTolak = $this->actingAs($this->dosen1)->post(route('dosen.penugasan.respon', $penugasan->id), [
            'aksi' => 'tolak',
            'alasan_penolakan' => 'Kuota bimbingan saya sudah penuh semester ini dan topik jaringan lebih tepat dibimbing Dosen Siti.',
            'rekomendasi_dosen_id' => $this->dosen2->id,
        ]);
        $resTolak->assertSessionHas('success');

        $penugasan->refresh();
        $this->assertEquals(StatusPenugasanDosen::Ditolak, $penugasan->status);
        $this->assertEquals('Kuota bimbingan saya sudah penuh semester ini dan topik jaringan lebih tepat dibimbing Dosen Siti.', $penugasan->alasan_penolakan);
        $this->assertEquals($this->dosen2->id, $penugasan->rekomendasi_dosen_id);

        // Foreign Key di pengajuan_skripsi di-null-kan agar Kaprodi bisa assign dosen lain
        $skripsi->refresh();
        $this->assertNull($skripsi->pembimbing_1_id);
        $this->assertEquals(StatusPengajuan::Diajukan, $skripsi->status);

        // Notifikasi darurat penolakan terkirim ke Kaprodi dan Admin Utama (dengan pesan tetapkan pengganti)
        $notifKaprodi = Notifikasi::where('user_id', $this->kaprodi->id)->latest('id')->first();
        $this->assertNotNull($notifKaprodi);
        $this->assertStringContainsString('Silakan tetapkan dosen pengganti.', $notifKaprodi->pesan);

        $notifAdminUtama = Notifikasi::where('user_id', $this->adminUtama->id)->latest('id')->first();
        $this->assertNotNull($notifAdminUtama);
        $this->assertStringContainsString('Silakan tetapkan dosen pengganti.', $notifAdminUtama->pesan);

        // Notifikasi ke Admin Prodi: HANYA info penolakan, TANPA 'Silakan tetapkan dosen pengganti.'
        $notifAdminProdi = Notifikasi::where('user_id', $this->adminProdi->id)->latest('id')->first();
        $this->assertNotNull($notifAdminProdi);
        $this->assertStringNotContainsString('Silakan tetapkan dosen pengganti.', $notifAdminProdi->pesan);

        // Kaprodi menetapkan Dosen 2 sebagai pembimbing pengganti
        $resKaprodiUlang = $this->actingAs($this->kaprodi)->post(route('kaprodi.skripsi.review', $skripsi->id), [
            'action' => 'terima',
            'pembimbing_1_id' => $this->dosen2->id,
        ]);
        $resKaprodiUlang->assertSessionHas('success');

        $skripsi->refresh();
        $this->assertEquals($this->dosen2->id, $skripsi->pembimbing_1_id);

        // Ada 2 tiket penugasan di riwayat: 1 ditolak (dosen1), 1 menunggu (dosen2)
        $this->assertEquals(2, $skripsi->penugasanDosen()->count());
    }

    public function test_mandat_admin_utama_otomatis_disetujui_dan_dosen_tidak_dapat_menolak(): void
    {
        $skripsi = PengajuanSkripsi::create([
            'mahasiswa_id' => $this->mahasiswa->id,
            'program_studi_id' => $this->prodi->id,
            'judul' => 'Audit Keamanan Siber Menggunakan Metode NIST',
            'abstrak' => 'Abstrak...',
            'status' => StatusPengajuan::Diajukan,
        ]);

        // Admin Utama menetapkan Dosen 1 (Fast-Track / Mandat Langsung)
        $response = $this->actingAs($this->adminUtama)->post(route('kaprodi.skripsi.review', $skripsi->id), [
            'action' => 'terima',
            'pembimbing_1_id' => $this->dosen1->id,
        ]);
        $response->assertSessionHas('success');

        // Status penugasan langsung DISETUJUI dan IS_MANDAT_ADMIN_UTAMA = true
        $penugasan = PenugasanDosen::where('assignable_type', PengajuanSkripsi::class)
            ->where('dosen_id', $this->dosen1->id)
            ->first();

        $this->assertNotNull($penugasan);
        $this->assertEquals(StatusPenugasanDosen::Disetujui, $penugasan->status);
        $this->assertTrue($penugasan->is_mandat_admin_utama);
        $this->assertNotNull($penugasan->direspon_pada);

        // Skripsi pembimbing sudah terkonfirmasi
        $skripsi->refresh();
        $this->assertTrue($skripsi->isPembimbingConfirmed());

        // Dosen 1 mencoba menolak penugasan mandat Admin Utama -> Sistem menolak!
        $responseTolak = $this->actingAs($this->dosen1)->post(route('dosen.penugasan.respon', $penugasan->id), [
            'aksi' => 'tolak',
            'alasan_penolakan' => 'Saya sedang sibuk riset dan tidak mau membimbing.',
        ]);
        $responseTolak->assertSessionHas('error');

        // Status tetap DISETUJUI
        $penugasan->refresh();
        $this->assertEquals(StatusPenugasanDosen::Disetujui, $penugasan->status);
        $this->assertEquals($this->dosen1->id, $skripsi->fresh()->pembimbing_1_id);
    }

    public function test_penugasan_penguji_seminar_workflow_dan_mandat_admin_utama(): void
    {
        $skripsi = PengajuanSkripsi::create([
            'mahasiswa_id' => $this->mahasiswa->id,
            'program_studi_id' => $this->prodi->id,
            'judul' => 'Analisis Sentimen Menggunakan IndoBERT',
            'pembimbing_1_id' => $this->dosen1->id,
            'status' => StatusPengajuan::Selesai,
        ]);

        $seminar = SeminarSkripsi::create([
            'pengajuan_skripsi_id' => $skripsi->id,
            'status' => StatusPengajuan::Diajukan,
            'file_naskah_seminar' => 'dummy_naskah.pdf',
            'file_acc_pembimbing' => 'dummy_acc.pdf',
            'file_bukti_bayar_seminar' => 'dummy_bayar.pdf',
            'file_toefl' => 'dummy_toefl.pdf',
        ]);

        // Kaprodi tetapkan penguji seminar
        $this->actingAs($this->kaprodi)->post(route('kaprodi.seminar.penguji', $seminar->id), [
            'penguji_seminar_id' => $this->dosen2->id,
        ]);

        $penugasan = PenugasanDosen::where('assignable_type', SeminarSkripsi::class)
            ->where('dosen_id', $this->dosen2->id)
            ->first();

        $this->assertNotNull($penugasan);
        $this->assertEquals(StatusPenugasanDosen::Menunggu, $penugasan->status);
        $this->assertFalse($seminar->isPengujiConfirmed());

        // Dosen 2 menerima
        $this->actingAs($this->dosen2)->post(route('dosen.penugasan.respon', $penugasan->id), [
            'aksi' => 'terima',
        ]);
        $this->assertTrue($seminar->fresh()->isPengujiConfirmed());

        // Sekarang uji kasus Mandat Admin Utama pada seminar
        $seminar2 = SeminarSkripsi::create([
            'pengajuan_skripsi_id' => $skripsi->id,
            'status' => StatusPengajuan::Diajukan,
            'file_naskah_seminar' => 'dummy_naskah2.pdf',
            'file_acc_pembimbing' => 'dummy_acc2.pdf',
            'file_bukti_bayar_seminar' => 'dummy_bayar2.pdf',
            'file_toefl' => 'dummy_toefl2.pdf',
        ]);

        $this->actingAs($this->adminUtama)->post(route('kaprodi.seminar.penguji', $seminar2->id), [
            'penguji_seminar_id' => $this->dosen3->id,
        ]);

        $penugasanMandat = PenugasanDosen::where('assignable_type', SeminarSkripsi::class)
            ->where('assignable_id', $seminar2->id)
            ->where('dosen_id', $this->dosen3->id)
            ->first();

        $this->assertEquals(StatusPenugasanDosen::Disetujui, $penugasanMandat->status);
        $this->assertTrue($penugasanMandat->is_mandat_admin_utama);
        $this->assertTrue($seminar2->fresh()->isPengujiConfirmed());
    }

    public function test_penugasan_penguji_sidang_workflow(): void
    {
        $skripsi = PengajuanSkripsi::create([
            'mahasiswa_id' => $this->mahasiswa->id,
            'program_studi_id' => $this->prodi->id,
            'judul' => 'Sistem Deteksi Penyakit Tanaman',
            'pembimbing_1_id' => $this->dosen1->id,
            'status' => StatusPengajuan::Selesai,
        ]);

        $sidang = SidangSkripsi::create([
            'pengajuan_skripsi_id' => $skripsi->id,
            'status' => StatusPengajuan::Diajukan,
            'file_naskah_sidang' => 'dummy_naskah.pdf',
            'file_acc_sidang' => 'dummy_acc.pdf',
            'file_bebas_revisi_seminar' => 'dummy_bebas.pdf',
            'file_bukti_bayar_sidang' => 'dummy_bayar.pdf',
        ]);

        // Kaprodi tetapkan dewan penguji sidang
        $this->actingAs($this->kaprodi)->post(route('kaprodi.sidang.penguji', $sidang->id), [
            'penguji_1_id' => $this->dosen2->id,
            'penguji_2_id' => $this->dosen3->id,
        ]);

        $this->assertFalse($sidang->fresh()->isDewanPengujiConfirmed());

        $penugasan1 = PenugasanDosen::where('assignable_type', SidangSkripsi::class)->where('dosen_id', $this->dosen2->id)->first();
        $penugasan2 = PenugasanDosen::where('assignable_type', SidangSkripsi::class)->where('dosen_id', $this->dosen3->id)->first();

        // Penguji 1 menyetujui, Penguji 2 menolak
        $this->actingAs($this->dosen2)->post(route('dosen.penugasan.respon', $penugasan1->id), ['aksi' => 'terima']);
        $this->assertFalse($sidang->fresh()->isDewanPengujiConfirmed());

        $this->actingAs($this->dosen3)->post(route('dosen.penugasan.respon', $penugasan2->id), [
            'aksi' => 'tolak',
            'alasan_penolakan' => 'Jadwal bentrok dengan konferensi internasional.',
        ]);

        $sidang->refresh();
        $this->assertNull($sidang->penguji_2_id);
        $this->assertEquals($this->dosen2->id, $sidang->penguji_1_id);
    }

    public function test_dosen_lain_tidak_bisa_merespon_penugasan_milik_dosen_berbeda(): void
    {
        $skripsi = PengajuanSkripsi::create([
            'mahasiswa_id' => $this->mahasiswa->id,
            'program_studi_id' => $this->prodi->id,
            'judul' => 'Sistem IoT untuk Smart Greenhouse',
            'status' => StatusPengajuan::Diajukan,
        ]);

        $this->actingAs($this->kaprodi)->post(route('kaprodi.skripsi.review', $skripsi->id), [
            'action' => 'terima',
            'pembimbing_1_id' => $this->dosen1->id,
        ]);

        $penugasan = PenugasanDosen::where('dosen_id', $this->dosen1->id)->first();

        // Dosen 2 mencoba menyetujui penugasan Dosen 1 -> 403 Forbidden!
        $response = $this->actingAs($this->dosen2)->post(route('dosen.penugasan.respon', $penugasan->id), [
            'aksi' => 'terima',
        ]);
        $response->assertForbidden();
    }

    public function test_admin_prodi_diblokir_jika_dosen_belum_konfirmasi_tetapi_admin_utama_bisa_override(): void
    {
        $skripsi = PengajuanSkripsi::create([
            'mahasiswa_id' => $this->mahasiswa->id,
            'program_studi_id' => $this->prodi->id,
            'judul' => 'Perancangan Blockchain pada Supply Chain',
            'status' => StatusPengajuan::Diajukan,
        ]);

        // Kaprodi menugaskan dosen 1
        $this->actingAs($this->kaprodi)->post(route('kaprodi.skripsi.review', $skripsi->id), [
            'action' => 'terima',
            'pembimbing_1_id' => $this->dosen1->id,
        ]);

        // Status masih MENUNGGU konfirmasi dosen 1
        $this->assertFalse($skripsi->fresh()->isPembimbingConfirmed());

        // 1. Admin Prodi mencoba menerbitkan SK Bimbingan -> DIBLOKIR!
        $resAdminProdi = $this->actingAs($this->adminProdi)->post(route('admin.skripsi.sk-bimbingan', $skripsi->id), [
            'nomor_sk_bimbingan' => 'SK/TI/001/2026',
            'tgl_sk_bimbingan' => '2026-09-04',
        ]);
        $resAdminProdi->assertSessionHas('error');

        // 2. Admin Utama (punya hak bypass/override) menerbitkan SK -> DIIZINKAN!
        $resAdminUtama = $this->actingAs($this->adminUtama)->post(route('admin.skripsi.sk-bimbingan', $skripsi->id), [
            'nomor_sk_bimbingan' => 'SK/OVERRIDE/TI/001/2026',
            'tgl_sk_bimbingan' => '2026-09-04',
        ]);
        $resAdminUtama->assertSessionHas('success');
    }

    public function test_status_pembimbing_yang_sudah_menyetujui_tidak_berubah_saat_kaprodi_memilih_pembimbing_lain(): void
    {
        $skripsi = PengajuanSkripsi::create([
            'mahasiswa_id' => $this->mahasiswa->id,
            'program_studi_id' => $this->prodi->id,
            'judul' => 'Sistem Rekomendasi Preservasi Status Dosen',
            'status' => StatusPengajuan::Diajukan,
        ]);

        // Kaprodi menugaskan Dosen 1 (P1) dan Dosen 2 (P2)
        $this->actingAs($this->kaprodi)->post(route('kaprodi.skripsi.review', $skripsi->id), [
            'action' => 'terima',
            'pembimbing_1_id' => $this->dosen1->id,
            'pembimbing_2_id' => $this->dosen2->id,
        ]);

        $penugasanP1 = PenugasanDosen::where('assignable_id', $skripsi->id)->where('peran', 'pembimbing_1')->first();
        $penugasanP2 = PenugasanDosen::where('assignable_id', $skripsi->id)->where('peran', 'pembimbing_2')->first();

        // 1. Dosen 2 MENYETUJUI penugasan
        $this->actingAs($this->dosen2)->post(route('dosen.penugasan.respon', $penugasanP2->id), [
            'aksi' => 'terima',
        ]);
        $this->assertTrue($penugasanP2->fresh()->isDisetujui());

        // 2. Dosen 1 MENOLAK penugasan
        $this->actingAs($this->dosen1)->post(route('dosen.penugasan.respon', $penugasanP1->id), [
            'aksi' => 'tolak',
            'alasan_penolakan' => 'Beban bimbingan semester ini sudah penuh.',
            'rekomendasi_dosen_id' => $this->dosen3->id,
        ]);
        $this->assertTrue($penugasanP1->fresh()->isDitolak());
        $this->assertNull($skripsi->fresh()->pembimbing_1_id);
        $this->assertEquals($this->dosen2->id, $skripsi->fresh()->pembimbing_2_id);

        // Catat jumlah tiket penugasan untuk Dosen 2 sebelum Kaprodi submit ulang
        $countTiketDosen2Sebelum = PenugasanDosen::where('assignable_id', $skripsi->id)->where('dosen_id', $this->dosen2->id)->count();
        $this->assertEquals(1, $countTiketDosen2Sebelum);

        // 3. Kaprodi menunjuk Dosen 3 sebagai P1 baru dan MEMPERTAHANKAN Dosen 2 sebagai P2
        $response = $this->actingAs($this->kaprodi)->post(route('kaprodi.skripsi.review', $skripsi->id), [
            'action' => 'terima',
            'pembimbing_1_id' => $this->dosen3->id,
            'pembimbing_2_id' => $this->dosen2->id,
        ]);
        $response->assertSessionHas('success');

        // 4. Verifikasi Status Dosen 2 TIDAK BERUBAH (tetap Disetujui, tidak ada duplikasi tiket)
        $countTiketDosen2Sesudah = PenugasanDosen::where('assignable_id', $skripsi->id)->where('dosen_id', $this->dosen2->id)->count();
        $this->assertEquals(1, $countTiketDosen2Sesudah, 'Tidak boleh ada pembuatan tiket baru untuk Dosen 2 yang sudah menyetujui');

        $penugasanP2Terbaru = $skripsi->fresh()->latestPenugasanPembimbing2();
        $this->assertTrue($penugasanP2Terbaru->isDisetujui(), 'Status Dosen 2 harus tetap Disetujui (tidak boleh reset ke Menunggu)');
        $this->assertEquals($this->dosen2->id, $penugasanP2Terbaru->dosen_id);

        // 5. Verifikasi Dosen 3 baru dibuatkan tiket dengan status Menunggu
        $penugasanP1Terbaru = $skripsi->fresh()->latestPenugasanPembimbing1();
        $this->assertEquals($this->dosen3->id, $penugasanP1Terbaru->dosen_id);
        $this->assertTrue($penugasanP1Terbaru->isMenunggu());

        // 6. Dosen 3 menyetujui penugasan -> Sekarang kedua pembimbing confirmed!
        $this->actingAs($this->dosen3)->post(route('dosen.penugasan.respon', $penugasanP1Terbaru->id), [
            'aksi' => 'terima',
        ]);
        $this->assertTrue($skripsi->fresh()->isPembimbingConfirmed());
    }

    public function test_status_penguji_seminar_yang_sudah_menyetujui_tetap_terjaga_saat_penetapan_ulang(): void
    {
        $skripsi = PengajuanSkripsi::create([
            'mahasiswa_id' => $this->mahasiswa->id,
            'program_studi_id' => $this->prodi->id,
            'judul' => 'Sistem Preservasi Status Penguji Seminar',
            'status' => StatusPengajuan::Selesai,
            'pembimbing_1_id' => $this->dosen1->id,
        ]);

        $seminar = SeminarSkripsi::create([
            'pengajuan_skripsi_id' => $skripsi->id,
            'status' => StatusPengajuan::Diajukan,
            'file_naskah_seminar' => 'naskah.pdf',
        ]);

        // Kaprodi menetapkan Dosen 2 sebagai Penguji Seminar
        $this->actingAs($this->kaprodi)->post(route('kaprodi.seminar.penguji', $seminar->id), [
            'penguji_seminar_id' => $this->dosen2->id,
        ]);

        $penugasan = $seminar->latestPenugasanPenguji();
        $this->assertTrue($penugasan->isMenunggu());

        // Dosen 2 menyetujui
        $this->actingAs($this->dosen2)->post(route('dosen.penugasan.respon', $penugasan->id), [
            'aksi' => 'terima',
        ]);
        $this->assertTrue($penugasan->fresh()->isDisetujui());

        // Admin Utama memperbarui penetapan dengan Dosen 2 yang sama
        $this->actingAs($this->adminUtama)->post(route('kaprodi.seminar.penguji', $seminar->id), [
            'penguji_seminar_id' => $this->dosen2->id,
        ]);

        // Status Dosen 2 tetap Disetujui
        $penugasanTerbaru = $seminar->fresh()->latestPenugasanPenguji();
        $this->assertTrue($penugasanTerbaru->isDisetujui());
        $this->assertEquals(1, PenugasanDosen::where('assignable_id', $seminar->id)->where('dosen_id', $this->dosen2->id)->count());
    }

    public function test_status_penguji_sidang_yang_sudah_menyetujui_tidak_berubah_saat_penguji_lain_diganti(): void
    {
        $skripsi = PengajuanSkripsi::create([
            'mahasiswa_id' => $this->mahasiswa->id,
            'program_studi_id' => $this->prodi->id,
            'judul' => 'Sistem Preservasi Status Penguji Sidang',
            'status' => StatusPengajuan::Selesai,
            'pembimbing_1_id' => $this->kaprodi->id,
        ]);

        $sidang = SidangSkripsi::create([
            'pengajuan_skripsi_id' => $skripsi->id,
            'status' => StatusPengajuan::Diajukan,
            'file_naskah_sidang' => 'sidang.pdf',
        ]);

        // Kaprodi menetapkan Dosen 1 (Penguji 1) dan Dosen 2 (Penguji 2)
        $this->actingAs($this->kaprodi)->post(route('kaprodi.sidang.penguji', $sidang->id), [
            'penguji_1_id' => $this->dosen1->id,
            'penguji_2_id' => $this->dosen2->id,
        ]);

        $penugasanP1 = $sidang->latestPenugasanPenguji1();
        $penugasanP2 = $sidang->latestPenugasanPenguji2();

        // 1. Penguji 1 (Dosen 1) menyetujui
        $this->actingAs($this->dosen1)->post(route('dosen.penugasan.respon', $penugasanP1->id), [
            'aksi' => 'terima',
        ]);
        $this->assertTrue($penugasanP1->fresh()->isDisetujui());

        // 2. Penguji 2 (Dosen 2) menolak
        $this->actingAs($this->dosen2)->post(route('dosen.penugasan.respon', $penugasanP2->id), [
            'aksi' => 'tolak',
            'alasan_penolakan' => 'Ada jadwal dinas luar kota.',
            'rekomendasi_dosen_id' => $this->dosen3->id,
        ]);
        $this->assertTrue($penugasanP2->fresh()->isDitolak());
        $this->assertNull($sidang->fresh()->penguji_2_id);
        $this->assertEquals($this->dosen1->id, $sidang->fresh()->penguji_1_id);

        // 3. Kaprodi menunjuk Dosen 3 sebagai Penguji 2 baru, mempertahankan Dosen 1 sebagai Penguji 1
        $response = $this->actingAs($this->kaprodi)->post(route('kaprodi.sidang.penguji', $sidang->id), [
            'penguji_1_id' => $this->dosen1->id,
            'penguji_2_id' => $this->dosen3->id,
        ]);
        $response->assertSessionHas('success');

        // 4. Verifikasi Status Penguji 1 (Dosen 1) TETAP DISETUJUI, tidak ada duplikasi tiket
        $countTiketDosen1 = PenugasanDosen::where('assignable_id', $sidang->id)->where('dosen_id', $this->dosen1->id)->count();
        $this->assertEquals(1, $countTiketDosen1);
        $this->assertTrue($sidang->fresh()->latestPenugasanPenguji1()->isDisetujui());

        // 5. Verifikasi Penguji 2 (Dosen 3) berstatus Menunggu
        $this->assertTrue($sidang->fresh()->latestPenugasanPenguji2()->isMenunggu());
        $this->assertEquals($this->dosen3->id, $sidang->fresh()->latestPenugasanPenguji2()->dosen_id);

        // 6. Dosen 3 menyetujui -> Kedua penguji sidang confirmed!
        $this->actingAs($this->dosen3)->post(route('dosen.penugasan.respon', $sidang->fresh()->latestPenugasanPenguji2()->id), [
            'aksi' => 'terima',
        ]);
        $this->assertTrue($sidang->fresh()->isDewanPengujiConfirmed());
    }
}

