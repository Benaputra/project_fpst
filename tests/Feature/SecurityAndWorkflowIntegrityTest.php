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

class SecurityAndWorkflowIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private ProgramStudi $prodiAgt;
    private ProgramStudi $prodiAgb;
    private User $mhsAgt1;
    private User $mhsAgt2;
    private User $mhsAgb;
    private User $kaprodiAgt;
    private User $kaprodiAgb;
    private User $adminAgt;
    private User $adminAgb;
    private User $adminUtama;
    private User $dosenAgt;
    private User $dosenLain;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        $this->prodiAgt = ProgramStudi::create(['nama' => 'Agroteknologi', 'kode' => 'AGT']);
        $this->prodiAgb = ProgramStudi::create(['nama' => 'Agribisnis', 'kode' => 'AGB']);

        $this->adminUtama = User::create([
            'name' => 'Admin Utama FPST',
            'email' => 'admin.utama@sec.test',
            'nomor_induk' => 'ADM-001',
            'password' => 'password',
            'role' => UserRole::AdminUtama,
        ]);

        $this->kaprodiAgt = User::create([
            'name' => 'Kaprodi AGT',
            'email' => 'kaprodi.agt@sec.test',
            'nomor_induk' => '100001',
            'password' => 'password',
            'role' => UserRole::Kaprodi,
            'program_studi_id' => $this->prodiAgt->id,
        ]);

        $this->kaprodiAgb = User::create([
            'name' => 'Kaprodi AGB',
            'email' => 'kaprodi.agb@sec.test',
            'nomor_induk' => '100002',
            'password' => 'password',
            'role' => UserRole::Kaprodi,
            'program_studi_id' => $this->prodiAgb->id,
        ]);

        $this->adminAgt = User::create([
            'name' => 'Admin AGT',
            'email' => 'admin.agt@sec.test',
            'nomor_induk' => 'ADM-AGT',
            'password' => 'password',
            'role' => UserRole::AdminProdi,
            'program_studi_id' => $this->prodiAgt->id,
        ]);

        $this->adminAgb = User::create([
            'name' => 'Admin AGB',
            'email' => 'admin.agb@sec.test',
            'nomor_induk' => 'ADM-AGB',
            'password' => 'password',
            'role' => UserRole::AdminProdi,
            'program_studi_id' => $this->prodiAgb->id,
        ]);

        $this->dosenAgt = User::create([
            'name' => 'Dosen Pembimbing AGT',
            'email' => 'dosen.agt@sec.test',
            'nomor_induk' => '200001',
            'password' => 'password',
            'role' => UserRole::Dosen,
            'program_studi_id' => $this->prodiAgt->id,
        ]);

        $this->dosenLain = User::create([
            'name' => 'Dosen Tidak Terkait',
            'email' => 'dosen.lain@sec.test',
            'nomor_induk' => '200002',
            'password' => 'password',
            'role' => UserRole::Dosen,
            'program_studi_id' => $this->prodiAgt->id,
        ]);

        $this->mhsAgt1 = User::create([
            'name' => 'Mahasiswa AGT Satu',
            'email' => 'mhs1@sec.test',
            'nomor_induk' => '221001',
            'password' => 'password',
            'role' => UserRole::Mahasiswa,
            'program_studi_id' => $this->prodiAgt->id,
        ]);

        $this->mhsAgt2 = User::create([
            'name' => 'Mahasiswa AGT Dua',
            'email' => 'mhs2@sec.test',
            'nomor_induk' => '221002',
            'password' => 'password',
            'role' => UserRole::Mahasiswa,
            'program_studi_id' => $this->prodiAgt->id,
        ]);

        $this->mhsAgb = User::create([
            'name' => 'Mahasiswa AGB',
            'email' => 'mhs.agb@sec.test',
            'nomor_induk' => '222001',
            'password' => 'password',
            'role' => UserRole::Mahasiswa,
            'program_studi_id' => $this->prodiAgb->id,
        ]);
    }

    public function test_dokumen_controller_mencegah_idor_dan_path_traversal(): void
    {
        $pathProposal = UploadedFile::fake()->create('proposal.pdf', 100, 'application/pdf')
            ->store('skripsi/proposal', 'local');
        $pathTranskrip = UploadedFile::fake()->create('transkrip.pdf', 100, 'application/pdf')
            ->store('skripsi/transkrip', 'local');
        $pathBayar = UploadedFile::fake()->create('bayar.pdf', 100, 'application/pdf')
            ->store('skripsi/bukti_bayar', 'local');

        Storage::disk('local')->put('rahasia/internal.txt', 'secret');

        PengajuanSkripsi::create([
            'mahasiswa_id' => $this->mhsAgt1->id,
            'program_studi_id' => $this->prodiAgt->id,
            'judul' => 'Analisis Kesuburan Tanah Gambut di Kalimantan Barat',
            'file_proposal' => $pathProposal,
            'file_transkrip' => $pathTranskrip,
            'file_bukti_bayar' => $pathBayar,
            'pembimbing_1_id' => $this->dosenAgt->id,
            'status' => StatusPengajuan::Diproses,
        ]);

        $encoded = base64_encode($pathProposal);

        // 1. Pemilik, Pembimbing, Kaprodi se-prodi, Admin se-prodi, Admin Utama -> DIIZINKAN
        $this->actingAs($this->mhsAgt1)->get(route('dokumen.download', $encoded))->assertOk();
        $this->actingAs($this->dosenAgt)->get(route('dokumen.view', $encoded))->assertOk();
        $this->actingAs($this->kaprodiAgt)->get(route('dokumen.download', $encoded))->assertOk();
        $this->actingAs($this->adminAgt)->get(route('dokumen.download', $encoded))->assertOk();
        $this->actingAs($this->adminUtama)->get(route('dokumen.download', $encoded))->assertOk();

        // 2. Mahasiswa lain, Dosen tidak terkait, Kaprodi/Admin prodi lain -> DITOLAK (403)
        $this->actingAs($this->mhsAgt2)->get(route('dokumen.download', $encoded))->assertForbidden();
        $this->actingAs($this->mhsAgb)->get(route('dokumen.view', $encoded))->assertForbidden();
        $this->actingAs($this->dosenLain)->get(route('dokumen.download', $encoded))->assertForbidden();
        $this->actingAs($this->kaprodiAgb)->get(route('dokumen.download', $encoded))->assertForbidden();
        $this->actingAs($this->adminAgb)->get(route('dokumen.download', $encoded))->assertForbidden();

        // 3. Path traversal & direktori tidak terdaftar -> DITOLAK (403)
        $this->actingAs($this->adminUtama)->get(route('dokumen.download', base64_encode('../.env')))->assertForbidden();
        $this->actingAs($this->adminUtama)->get(route('dokumen.download', base64_encode('skripsi/../rahasia/internal.txt')))->assertForbidden();
        $this->actingAs($this->adminUtama)->get(route('dokumen.download', base64_encode('rahasia/internal.txt')))->assertForbidden();
    }

    public function test_route_role_middleware_memblokir_akses_lintas_peran(): void
    {
        $skripsi = PengajuanSkripsi::create([
            'mahasiswa_id' => $this->mhsAgt1->id,
            'program_studi_id' => $this->prodiAgt->id,
            'judul' => 'Optimasi Pemupukan Kelapa Sawit pada Lahan Rawa',
            'status' => StatusPengajuan::Diajukan,
        ]);

        // Mahasiswa tidak boleh mengakses area admin, kaprodi, atau dosen
        $this->actingAs($this->mhsAgt1)->get(route('admin.administrasi.index'))->assertForbidden();
        $this->actingAs($this->mhsAgt1)->post(route('admin.skripsi.sk-bimbingan', $skripsi->id), [])->assertForbidden();
        $this->actingAs($this->mhsAgt1)->get(route('kaprodi.penetapan.index'))->assertForbidden();
        $this->actingAs($this->mhsAgt1)->get(route('dosen.bimbingan.index'))->assertForbidden();

        // Dosen tidak boleh mengakses area mahasiswa atau admin administrasi
        $this->actingAs($this->dosenAgt)->get(route('mahasiswa.skripsi.index'))->assertForbidden();
        $this->actingAs($this->dosenAgt)->get(route('admin.administrasi.index'))->assertForbidden();

        // Admin Prodi tidak boleh mengakses area mahasiswa, kaprodi, log aktivitas, atau dosen
        $this->actingAs($this->adminAgt)->get(route('mahasiswa.skripsi.index'))->assertForbidden();
        $this->actingAs($this->adminAgt)->get(route('kaprodi.penetapan.index'))->assertForbidden();
        $this->actingAs($this->adminAgt)->get(route('admin.log-aktivitas.index'))->assertForbidden();
        $this->actingAs($this->adminAgt)->get(route('dosen.bimbingan.index'))->assertForbidden();
    }

    public function test_isolasi_lintas_prodi_untuk_kaprodi_dan_admin_prodi(): void
    {
        $skripsiAgb = PengajuanSkripsi::create([
            'mahasiswa_id' => $this->mhsAgb->id,
            'program_studi_id' => $this->prodiAgb->id,
            'judul' => 'Analisis Rantai Pasok Komoditas Karet Rakyat',
            'status' => StatusPengajuan::Diajukan,
        ]);

        $seminarAgb = SeminarSkripsi::create([
            'pengajuan_skripsi_id' => $skripsiAgb->id,
            'status' => StatusPengajuan::Diajukan,
        ]);

        $sidangAgb = SidangSkripsi::create([
            'pengajuan_skripsi_id' => $skripsiAgb->id,
            'status' => StatusPengajuan::Diajukan,
        ]);

        // Kaprodi AGT tidak boleh mereview/menetapkan penguji milik prodi AGB
        $this->actingAs($this->kaprodiAgt)
            ->post(route('kaprodi.skripsi.review', $skripsiAgb->id), [
                'action' => 'terima',
                'pembimbing_1_id' => $this->dosenAgt->id,
            ])
            ->assertForbidden();

        $this->actingAs($this->kaprodiAgt)
            ->post(route('kaprodi.seminar.penguji', $seminarAgb->id), [
                'penguji_seminar_id' => $this->dosenAgt->id,
            ])
            ->assertForbidden();

        $this->actingAs($this->kaprodiAgt)
            ->post(route('kaprodi.sidang.penguji', $sidangAgb->id), [
                'penguji_1_id' => $this->dosenAgt->id,
                'penguji_2_id' => $this->dosenLain->id,
            ])
            ->assertForbidden();

        // Admin Prodi AGT tidak boleh memproses administrasi prodi AGB
        $this->actingAs($this->adminAgt)
            ->post(route('admin.skripsi.sk-bimbingan', $skripsiAgb->id), [
                'nomor_sk_bimbingan' => 'SK/ILLEGAL/001',
                'tgl_sk_bimbingan' => '2026-09-01',
            ])
            ->assertForbidden();

        $this->actingAs($this->adminAgt)
            ->post(route('admin.seminar.selesai', $seminarAgb->id), [
                'nilai_seminar' => 90,
            ])
            ->assertForbidden();

        $this->actingAs($this->adminAgt)
            ->post(route('admin.sidang.selesai', $sidangAgb->id), [
                'nilai_sidang' => 90,
            ])
            ->assertForbidden();

        // Parameter ?prodi_id= pada halaman index tidak membocorkan data prodi lain
        $resKaprodi = $this->actingAs($this->kaprodiAgt)
            ->get(route('kaprodi.penetapan.index', ['prodi_id' => $this->prodiAgb->id]));
        $resKaprodi->assertOk();
        $resKaprodi->assertDontSee('Analisis Rantai Pasok Komoditas Karet Rakyat');

        $resAdmin = $this->actingAs($this->adminAgt)
            ->get(route('admin.administrasi.index', ['prodi_id' => $this->prodiAgb->id]));
        $resAdmin->assertOk();
        $resAdmin->assertDontSee('Analisis Rantai Pasok Komoditas Karet Rakyat');
    }

    public function test_mahasiswa_tidak_dapat_submit_ganda_atau_menimpa_status_aktif(): void
    {
        // 1. Pengajuan judul aktif tidak boleh disubmit ganda lewat POST langsung
        $skripsi = PengajuanSkripsi::create([
            'mahasiswa_id' => $this->mhsAgt1->id,
            'program_studi_id' => $this->prodiAgt->id,
            'judul' => 'Judul Skripsi Pertama yang Aktif dan Sah',
            'pembimbing_1_id' => $this->dosenAgt->id,
            'nomor_sk_bimbingan' => 'SK/001/AGT/2026',
            'status' => StatusPengajuan::Selesai,
        ]);

        $resSkripsi = $this->actingAs($this->mhsAgt1)->post(route('mahasiswa.skripsi.store'), [
            'judul' => 'Judul Skripsi Kedua Ilegal Untuk Duplikasi',
            'file_proposal' => UploadedFile::fake()->create('prop2.pdf', 100, 'application/pdf'),
            'file_transkrip' => UploadedFile::fake()->create('trans2.pdf', 100, 'application/pdf'),
            'file_bukti_bayar' => UploadedFile::fake()->create('bayar2.pdf', 100, 'application/pdf'),
        ]);
        $resSkripsi->assertRedirect(route('mahasiswa.skripsi.index'));
        $resSkripsi->assertSessionHas('error');
        $this->assertEquals(1, PengajuanSkripsi::where('mahasiswa_id', $this->mhsAgt1->id)->count());

        // 2. Seminar yang sudah Diproses/Selesai tidak boleh ditimpa lewat POST store()
        $seminar = SeminarSkripsi::create([
            'pengajuan_skripsi_id' => $skripsi->id,
            'nilai_seminar' => 88.50,
            'status' => StatusPengajuan::Selesai,
        ]);

        $resSeminar = $this->actingAs($this->mhsAgt1)->post(route('mahasiswa.seminar.store'), [
            'file_naskah_seminar' => UploadedFile::fake()->create('naskah.pdf', 100, 'application/pdf'),
            'file_acc_pembimbing' => UploadedFile::fake()->create('acc.pdf', 100, 'application/pdf'),
            'file_bukti_bayar_seminar' => UploadedFile::fake()->create('bayar.pdf', 100, 'application/pdf'),
            'file_toefl' => UploadedFile::fake()->create('toefl.pdf', 100, 'application/pdf'),
        ]);
        $resSeminar->assertRedirect(route('mahasiswa.seminar.index'));
        $resSeminar->assertSessionHas('error');
        $seminar->refresh();
        $this->assertEquals(StatusPengajuan::Selesai, $seminar->status);

        // 3. Sidang yang sudah Diproses/Selesai tidak boleh ditimpa lewat POST store()
        $sidang = SidangSkripsi::create([
            'pengajuan_skripsi_id' => $skripsi->id,
            'status' => StatusPengajuan::Diproses,
        ]);

        $resSidang = $this->actingAs($this->mhsAgt1)->post(route('mahasiswa.sidang.store'), [
            'file_naskah_sidang' => UploadedFile::fake()->create('naskah_sidang.pdf', 100, 'application/pdf'),
            'file_acc_sidang' => UploadedFile::fake()->create('acc_sidang.pdf', 100, 'application/pdf'),
            'file_bebas_revisi_seminar' => UploadedFile::fake()->create('bebas.pdf', 100, 'application/pdf'),
            'file_bukti_bayar_sidang' => UploadedFile::fake()->create('bayar_sidang.pdf', 100, 'application/pdf'),
        ]);
        $resSidang->assertRedirect(route('mahasiswa.sidang.index'));
        $resSidang->assertSessionHas('error');
        $sidang->refresh();
        $this->assertEquals(StatusPengajuan::Diproses, $sidang->status);
    }
}
