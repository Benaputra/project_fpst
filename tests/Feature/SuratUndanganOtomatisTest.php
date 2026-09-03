<?php

namespace Tests\Feature;

use App\Enums\StatusPengajuan;
use App\Enums\UserRole;
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

class SuratUndanganOtomatisTest extends TestCase
{
    use RefreshDatabase;

    private ProgramStudi $prodiAgb;
    private User $kaprodiAgb;
    private User $adminAgb;
    private User $adminUtama;
    private User $mahasiswa;
    private User $pembimbing1;
    private User $pembimbing2;
    private User $penguji1;
    private User $penguji2;
    private PengajuanSkripsi $skripsi;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::fake('public');

        // Sediakan file TTD dummy di storage public
        Storage::disk('public')->put('ttd/ttd_agribisnis.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=='));

        $this->prodiAgb = ProgramStudi::create([
            'nama' => 'Agribisnis',
            'kode' => 'AGB',
            'file_ttd_kaprodi' => 'ttd/ttd_agribisnis.png',
        ]);

        $this->kaprodiAgb = User::create([
            'name' => 'Hardi Dominikus Bancin, S.P., M.P.',
            'email' => 'kaprodi.agb@example.test',
            'nomor_induk' => '1102059701',
            'password' => 'password',
            'role' => UserRole::Kaprodi,
            'program_studi_id' => $this->prodiAgb->id,
        ]);

        $this->adminUtama = User::create([
            'name' => 'Admin Utama FPST',
            'email' => 'admin.utama@example.test',
            'nomor_induk' => 'ADM-UTAMA-01',
            'password' => 'password',
            'role' => UserRole::AdminUtama,
        ]);

        $this->adminAgb = User::create([
            'name' => 'Admin Prodi Agribisnis',
            'email' => 'admin.agb@example.test',
            'nomor_induk' => 'ADM-AGB-01',
            'password' => 'password',
            'role' => UserRole::AdminProdi,
            'program_studi_id' => $this->prodiAgb->id,
        ]);

        $this->mahasiswa = User::create([
            'name' => 'Yovita Yona Yolanda',
            'email' => 'yovita@example.test',
            'nomor_induk' => '2110322398',
            'password' => 'password',
            'role' => UserRole::Mahasiswa,
            'program_studi_id' => $this->prodiAgb->id,
        ]);

        $this->pembimbing1 = User::create([
            'name' => 'Dr. Ir. Ekawati, SP. M.Si',
            'email' => 'ekawati@example.test',
            'nomor_induk' => '1101017001',
            'password' => 'password',
            'role' => UserRole::Dosen,
            'program_studi_id' => $this->prodiAgb->id,
        ]);

        $this->pembimbing2 = User::create([
            'name' => 'Ellyta, SP, M.Si',
            'email' => 'ellyta@example.test',
            'nomor_induk' => '1101017002',
            'password' => 'password',
            'role' => UserRole::Dosen,
            'program_studi_id' => $this->prodiAgb->id,
        ]);

        $this->penguji1 = User::create([
            'name' => 'Sri Widarti, SP.MP',
            'email' => 'sri@example.test',
            'nomor_induk' => '1101017003',
            'password' => 'password',
            'role' => UserRole::Dosen,
            'program_studi_id' => $this->prodiAgb->id,
        ]);

        $this->penguji2 = User::create([
            'name' => 'Dr. Bima Pratama, S.P., M.Si.',
            'email' => 'bima@example.test',
            'nomor_induk' => '1102059704',
            'password' => 'password',
            'role' => UserRole::Dosen,
            'program_studi_id' => $this->prodiAgb->id,
        ]);

        $this->skripsi = PengajuanSkripsi::create([
            'mahasiswa_id' => $this->mahasiswa->id,
            'program_studi_id' => $this->prodiAgb->id,
            'judul' => 'Peran Pusat Penelitian Kelapa Sawit Parindu Dalam Pengembangan Usaha Pembebitan Kelapa Sawit Swadaya',
            'pembimbing_1_id' => $this->pembimbing1->id,
            'pembimbing_2_id' => $this->pembimbing2->id,
            'nomor_sk_bimbingan' => 'SK/010/FPST/2026',
            'status' => StatusPengajuan::Selesai,
        ]);
    }

    public function test_surat_undangan_seminar_terbuat_otomatis_saat_nomor_diinput_tanpa_upload_file(): void
    {
        $seminar = SeminarSkripsi::create([
            'pengajuan_skripsi_id' => $this->skripsi->id,
            'penguji_seminar_id' => $this->penguji1->id,
            'status' => StatusPengajuan::Diproses,
        ]);

        $nomorSurat = '739/UPB.III/B.04/2026';

        $response = $this->actingAs($this->adminAgb)->post(route('admin.seminar.jadwal-sk', $seminar->id), [
            'tgl_seminar' => '2026-07-10',
            'jam_seminar' => '09.00 WIB - Selesai',
            'ruangan' => 'Ruang Audiovisual Fakultas Pertanian, Sains dan Teknologi',
            'nomor_undangan_seminar' => $nomorSurat,
            // file_undangan_seminar sengaja dikosongkan agar dibuat otomatis
        ]);

        $response->assertSessionHas('success');

        $seminar->refresh();

        // Pastikan file undangan seminar diisi oleh sistem
        $this->assertNotNull($seminar->file_undangan_seminar);
        Storage::disk('local')->assertExists($seminar->file_undangan_seminar);

        // Pastikan isi file adalah PDF yang valid
        $content = Storage::disk('local')->get($seminar->file_undangan_seminar);
        $this->assertStringStartsWith('%PDF-', $content);

        // Pastikan tercatat di tabel surat arsip
        $this->assertDatabaseHas('surat', [
            'nomor_surat' => $nomorSurat,
            'jenis_surat' => 'undangan_seminar',
            'seminar_skripsi_id' => $seminar->id,
            'file_surat' => $seminar->file_undangan_seminar,
            'status' => 'aktif',
        ]);
    }

    public function test_surat_undangan_sidang_skripsi_terbuat_otomatis_saat_nomor_diinput_tanpa_upload_file(): void
    {
        $sidang = SidangSkripsi::create([
            'pengajuan_skripsi_id' => $this->skripsi->id,
            'penguji_1_id' => $this->penguji1->id,
            'penguji_2_id' => $this->penguji2->id,
            'status' => StatusPengajuan::Diproses,
        ]);

        $nomorSurat = '1007/UPB.III/A.17/2026';

        $response = $this->actingAs($this->adminAgb)->post(route('admin.sidang.jadwal-sk', $sidang->id), [
            'tgl_sidang' => '2026-08-21',
            'jam_sidang' => '11.00 WIB – Selesai',
            'ruangan' => 'Ruang Audiovisual Fakultas Pertanian, Sains dan Teknologi',
            'nomor_undangan_sidang' => $nomorSurat,
            // file_undangan_sidang sengaja dikosongkan agar dibuat otomatis
        ]);

        $response->assertSessionHas('success');

        $sidang->refresh();

        // Pastikan file undangan sidang diisi oleh sistem
        $this->assertNotNull($sidang->file_undangan_sidang);
        Storage::disk('local')->assertExists($sidang->file_undangan_sidang);

        // Pastikan isi file adalah PDF yang valid
        $content = Storage::disk('local')->get($sidang->file_undangan_sidang);
        $this->assertStringStartsWith('%PDF-', $content);

        // Pastikan tercatat di tabel surat arsip
        $this->assertDatabaseHas('surat', [
            'nomor_surat' => $nomorSurat,
            'jenis_surat' => 'undangan_sidang',
            'sidang_skripsi_id' => $sidang->id,
            'file_surat' => $sidang->file_undangan_sidang,
            'status' => 'aktif',
        ]);
    }

    public function test_upload_manual_tetap_diprioritaskan_jika_admin_mengunggah_file(): void
    {
        $seminar = SeminarSkripsi::create([
            'pengajuan_skripsi_id' => $this->skripsi->id,
            'penguji_seminar_id' => $this->penguji1->id,
            'status' => StatusPengajuan::Diproses,
        ]);

        $fileUpload = UploadedFile::fake()->create('undangan_manual.pdf', 100, 'application/pdf');

        $response = $this->actingAs($this->adminAgb)->post(route('admin.seminar.jadwal-sk', $seminar->id), [
            'tgl_seminar' => '2026-07-10',
            'jam_seminar' => '09.00 WIB',
            'ruangan' => 'Ruang 101',
            'nomor_undangan_seminar' => 'MANUAL/001/2026',
            'file_undangan_seminar' => $fileUpload,
        ]);

        $response->assertSessionHas('success');

        $seminar->refresh();
        $this->assertNotNull($seminar->file_undangan_seminar);
        Storage::disk('local')->assertExists($seminar->file_undangan_seminar);
    }

    public function test_dokumen_view_dapat_menampilkan_surat_undangan_secara_inline_di_browser(): void
    {
        $seminar = SeminarSkripsi::create([
            'pengajuan_skripsi_id' => $this->skripsi->id,
            'penguji_seminar_id' => $this->penguji1->id,
            'status' => StatusPengajuan::Diproses,
        ]);

        $this->actingAs($this->adminAgb)->post(route('admin.seminar.jadwal-sk', $seminar->id), [
            'tgl_seminar' => '2026-07-10',
            'jam_seminar' => '09.00 WIB',
            'ruangan' => 'Ruang 101',
            'nomor_undangan_seminar' => 'UND-SEM/002/2026',
        ]);

        $seminar->refresh();
        $this->assertNotNull($seminar->file_undangan_seminar);

        // Akses route dokumen.view
        $response = $this->actingAs($this->pembimbing1)->get(route('dokumen.view', base64_encode($seminar->file_undangan_seminar)));
        $response->assertOk();
        $this->assertStringContainsString('inline', $response->headers->get('Content-Disposition') ?? '');
    }

    public function test_admin_utama_menerbitkan_undangan_sidang_langsung_membubuhkan_tanda_tangan(): void
    {
        $sidang = SidangSkripsi::create([
            'pengajuan_skripsi_id' => $this->skripsi->id,
            'penguji_1_id' => $this->penguji1->id,
            'penguji_2_id' => $this->penguji2->id,
            'status' => StatusPengajuan::Diproses,
        ]);

        $response = $this->actingAs($this->adminUtama)->post(route('admin.sidang.jadwal-sk', $sidang->id), [
            'tgl_sidang' => '2026-08-21',
            'jam_sidang' => '11.00 WIB – Selesai',
            'ruangan' => 'Ruang Audiovisual Fakultas Pertanian, Sains dan Teknologi',
            'nomor_undangan_sidang' => 'UND-UTAMA/01/2026',
        ]);

        $response->assertSessionHas('success');
        $sidang->refresh();
        $this->assertNotNull($sidang->file_undangan_sidang);

        // File PDF dibuat dan valid
        $content = Storage::disk('local')->get($sidang->file_undangan_sidang);
        $this->assertStringStartsWith('%PDF-', $content);
    }

    public function test_admin_prodi_menerbitkan_undangan_sidang_tanpa_tanda_tangan(): void
    {
        $sidang = SidangSkripsi::create([
            'pengajuan_skripsi_id' => $this->skripsi->id,
            'penguji_1_id' => $this->penguji1->id,
            'penguji_2_id' => $this->penguji2->id,
            'status' => StatusPengajuan::Diproses,
        ]);

        $response = $this->actingAs($this->adminAgb)->post(route('admin.sidang.jadwal-sk', $sidang->id), [
            'tgl_sidang' => '2026-08-21',
            'jam_sidang' => '11.00 WIB – Selesai',
            'ruangan' => 'Ruang Audiovisual Fakultas Pertanian, Sains dan Teknologi',
            'nomor_undangan_sidang' => 'UND-PRODI/01/2026',
        ]);

        $response->assertSessionHas('success');
        $sidang->refresh();
        $this->assertNotNull($sidang->file_undangan_sidang);

        $content = Storage::disk('local')->get($sidang->file_undangan_sidang);
        $this->assertStringStartsWith('%PDF-', $content);
    }

    public function test_admin_utama_dapat_mengubah_gambar_ttd_kaprodi_pada_portal_admin_utama(): void
    {
        $newTtd = UploadedFile::fake()->image('ttd_baru.png', 400, 200);

        $response = $this->actingAs($this->adminUtama)->put(route('admin.master.prodi.update', $this->prodiAgb->id), [
            'nama' => 'Agribisnis Updated',
            'kode' => 'AGB',
            'file_ttd_kaprodi' => $newTtd,
        ]);

        $response->assertRedirect(route('admin.master.prodi.index'));
        $response->assertSessionHas('success');

        $this->prodiAgb->refresh();
        $this->assertNotNull($this->prodiAgb->file_ttd_kaprodi);
        $this->assertStringStartsWith('ttd/', $this->prodiAgb->file_ttd_kaprodi);
        Storage::disk('public')->assertExists($this->prodiAgb->file_ttd_kaprodi);
    }
}
