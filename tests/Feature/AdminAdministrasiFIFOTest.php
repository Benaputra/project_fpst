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
use Tests\TestCase;

class AdminAdministrasiFIFOTest extends TestCase
{
    use RefreshDatabase;

    private ProgramStudi $prodiAgt;
    private User $adminAgt;
    private User $dosen1;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prodiAgt = ProgramStudi::create(['nama' => 'Agroteknologi', 'kode' => 'AGT']);

        $this->adminAgt = User::create([
            'name' => 'Admin Prodi Agroteknologi',
            'email' => 'admin.agt@example.test',
            'nomor_induk' => 'ADM-AGT-01',
            'password' => 'password',
            'role' => UserRole::AdminProdi,
            'program_studi_id' => $this->prodiAgt->id,
        ]);

        $this->dosen1 = User::create([
            'name' => 'Dr. Hendra Wijaya, S.P., M.Si.',
            'email' => 'hendra@example.test',
            'nomor_induk' => '1000000010',
            'password' => 'password',
            'role' => UserRole::Dosen,
            'program_studi_id' => $this->prodiAgt->id,
        ]);
    }

    public function test_admin_administrasi_fifo_queue_on_sk_bimbingan(): void
    {
        $mhs1 = User::create([
            'name' => 'Andi Pratama',
            'email' => 'andi.p@example.test',
            'nomor_induk' => '221000000011',
            'password' => 'password',
            'role' => UserRole::Mahasiswa,
            'program_studi_id' => $this->prodiAgt->id,
        ]);

        $mhs2 = User::create([
            'name' => 'Bambang Tri',
            'email' => 'bambang.t@example.test',
            'nomor_induk' => '221000000012',
            'password' => 'password',
            'role' => UserRole::Mahasiswa,
            'program_studi_id' => $this->prodiAgt->id,
        ]);

        $sk1 = PengajuanSkripsi::create([
            'mahasiswa_id' => $mhs1->id,
            'program_studi_id' => $this->prodiAgt->id,
            'judul' => 'Sistem Sensor Deteksi Hama Tanaman Padi FPST',
            'abstrak' => 'Abstrak deteksi hama padi',
            'status' => StatusPengajuan::Diproses,
            'pembimbing_1_id' => $this->dosen1->id,
            'created_at' => now()->subDays(4),
        ]);

        $sk2 = PengajuanSkripsi::create([
            'mahasiswa_id' => $mhs2->id,
            'program_studi_id' => $this->prodiAgt->id,
            'judul' => 'Analisis Rantai Pasok dan Efisiensi Pemasaran Jagung',
            'abstrak' => 'Abstrak rantai pasok jagung',
            'status' => StatusPengajuan::Diproses,
            'pembimbing_1_id' => $this->dosen1->id,
            'created_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($this->adminAgt)->get(route('admin.administrasi.index', ['sort_skripsi' => 'fifo']));
        $response->assertStatus(200);
        $response->assertSeeInOrder(['Andi Pratama', 'Bambang Tri']);

        // Search test
        $responseSearch = $this->actingAs($this->adminAgt)->get(route('admin.administrasi.index', ['search_skripsi' => 'Sensor']));
        $responseSearch->assertStatus(200);
        $responseSearch->assertSee('Sistem Sensor Deteksi Hama Tanaman Padi FPST');
        $responseSearch->assertDontSee('Analisis Rantai Pasok dan Efisiensi Pemasaran Jagung');
    }

    public function test_admin_administrasi_seminar_and_sidang_tabs(): void
    {
        $mhs = User::create([
            'name' => 'Citra Lestari',
            'email' => 'citra.l@example.test',
            'nomor_induk' => '221000000013',
            'password' => 'password',
            'role' => UserRole::Mahasiswa,
            'program_studi_id' => $this->prodiAgt->id,
        ]);

        $sk = PengajuanSkripsi::create([
            'mahasiswa_id' => $mhs->id,
            'program_studi_id' => $this->prodiAgt->id,
            'judul' => 'Klasifikasi Morfologi Citra Benih Padi FPST',
            'abstrak' => 'Abstrak citra benih padi',
            'status' => StatusPengajuan::Diproses,
            'pembimbing_1_id' => $this->dosen1->id,
        ]);

        $seminar = SeminarSkripsi::create([
            'pengajuan_skripsi_id' => $sk->id,
            'status' => StatusPengajuan::Diajukan,
            'created_at' => now()->subDays(2),
        ]);

        $sidang = SidangSkripsi::create([
            'pengajuan_skripsi_id' => $sk->id,
            'status' => StatusPengajuan::Diajukan,
            'created_at' => now()->subDay(),
        ]);

        $responseSeminar = $this->actingAs($this->adminAgt)->get(route('admin.administrasi.index', ['tab' => 'seminar']));
        $responseSeminar->assertStatus(200);
        $responseSeminar->assertSee('Klasifikasi Morfologi Citra Benih Padi FPST');
        $responseSeminar->assertSee('Citra Lestari');

        $responseSidang = $this->actingAs($this->adminAgt)->get(route('admin.administrasi.index', ['tab' => 'sidang']));
        $responseSidang->assertStatus(200);
        $responseSidang->assertSee('Klasifikasi Morfologi Citra Benih Padi FPST');
    }
}

