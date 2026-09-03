<?php

namespace Tests\Feature;

use App\Enums\StatusPengajuan;
use App\Enums\UserRole;
use App\Models\PengajuanSkripsi;
use App\Models\ProgramStudi;
use App\Models\Surat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArsipSuratPaginationTest extends TestCase
{
    use RefreshDatabase;

    private ProgramStudi $prodiTI;
    private User $adminUtama;
    private User $mahasiswa;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prodiTI = ProgramStudi::create(['nama' => 'Agroteknologi', 'kode' => 'AGT']);

        $this->adminUtama = User::create([
            'name' => 'Admin Utama FPST',
            'email' => 'admin.utama@example.test',
            'nomor_induk' => 'ADM-UTAMA',
            'password' => 'password',
            'role' => UserRole::AdminUtama,
        ]);

        $this->mahasiswa = User::create([
            'name' => 'Budi Santoso',
            'email' => 'budi@example.test',
            'nomor_induk' => '221000000010',
            'password' => 'password',
            'role' => UserRole::Mahasiswa,
            'program_studi_id' => $this->prodiTI->id,
        ]);
    }

    public function test_arsip_surat_pagination_renders_clean_and_proportional_controls(): void
    {
        $skripsi = PengajuanSkripsi::create([
            'mahasiswa_id' => $this->mahasiswa->id,
            'program_studi_id' => $this->prodiTI->id,
            'judul' => 'Sistem Deteksi Anomali Jaringan Komputer',
            'nomor_sk_bimbingan' => 'SK/01/2026',
            'status' => StatusPengajuan::Selesai,
        ]);

        // Buat 15 data surat agar terjadi paginasi (10 per halaman)
        for ($i = 1; $i <= 15; $i++) {
            Surat::create([
                'nomor_surat' => "SK/00{$i}/FPST/TI/2026",
                'jenis_surat' => 'sk_bimbingan',
                'nama_surat' => "SK Pembimbing Skripsi #{$i}",
                'pengajuan_skripsi_id' => $skripsi->id,
                'program_studi_id' => $this->prodiTI->id,
                'tgl_surat' => now(),
                'versi' => 1,
                'status' => 'aktif',
                'diterbitkan_oleh' => $this->adminUtama->id,
            ]);
        }

        // 1. Akses halaman 1 pada tab surat
        $response = $this->actingAs($this->adminUtama)
            ->get(route('admin.administrasi.index', ['tab' => 'surat']));

        $response->assertOk();

        // Pastikan view pagination kustom ter-render dengan kelas-kelas proporsional
        $response->assertSee('pagination-wrapper');
        $response->assertSee('pagination-info');
        $response->assertSee('pagination-controls');
        $response->assertSee('pagination-btn');
        $response->assertSee('pagination-icon');
        $response->assertSee('pagination-btn-disabled'); // Tombol 'Sebelumnya' disabled pada hal 1
        $response->assertSee('Sebelumnya');
        $response->assertSee('Berikutnya');

        // Pastikan info paginasi sesuai
        $response->assertSee('Menampilkan');
        $response->assertSee('dari');
        $response->assertSee('15');

        // Pastikan link tombol 'Berikutnya' mempertahankan parameter tab=surat dan page_surat=2
        $response->assertSee('page_surat=2');
        $response->assertSee('tab=surat');

        // 2. Akses halaman 2 pada tab surat
        $responsePage2 = $this->actingAs($this->adminUtama)
            ->get(route('admin.administrasi.index', ['tab' => 'surat', 'page_surat' => 2]));

        $responsePage2->assertOk();

        // Di halaman 2, tombol 'Sebelumnya' aktif dan tombol 'Berikutnya' disabled
        $responsePage2->assertSee('page_surat=1');
        $responsePage2->assertSee('tab=surat');
    }
}
