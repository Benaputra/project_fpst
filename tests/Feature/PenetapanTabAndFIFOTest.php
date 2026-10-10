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

class PenetapanTabAndFIFOTest extends TestCase
{
    use RefreshDatabase;

    private ProgramStudi $prodiAgt;
    private User $kaprodiAgt;
    private User $dosen1;
    private User $dosen2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prodiAgt = ProgramStudi::create(['nama' => 'Agroteknologi', 'kode' => 'AGT']);

        $this->kaprodiAgt = User::create([
            'name' => 'Dr. Ir. Ratna Wijaya, M.P.',
            'email' => 'kaprodi.agt@example.test',
            'nomor_induk' => '1000000001',
            'password' => 'password',
            'role' => UserRole::Kaprodi,
            'program_studi_id' => $this->prodiAgt->id,
        ]);

        $this->dosen1 = User::create([
            'name' => 'Dr. Dewi Lestari, S.P., M.P.',
            'email' => 'dosen1@example.test',
            'nomor_induk' => '1000000002',
            'password' => 'password',
            'role' => UserRole::Dosen,
            'program_studi_id' => $this->prodiAgt->id,
        ]);

        $this->dosen2 = User::create([
            'name' => 'Ahmad Fauzi, S.P., M.Sc.',
            'email' => 'dosen2@example.test',
            'nomor_induk' => '1000000003',
            'password' => 'password',
            'role' => UserRole::Dosen,
            'program_studi_id' => $this->prodiAgt->id,
        ]);
    }

    public function test_fifo_sorting_and_search_on_tab_judul(): void
    {
        $mhs1 = User::create([
            'name' => 'Andi Saputra',
            'email' => 'andi@example.test',
            'nomor_induk' => '221000000001',
            'password' => 'password',
            'role' => UserRole::Mahasiswa,
            'program_studi_id' => $this->prodiAgt->id,
        ]);

        $mhs2 = User::create([
            'name' => 'Budi Santoso',
            'email' => 'budi@example.test',
            'nomor_induk' => '221000000002',
            'password' => 'password',
            'role' => UserRole::Mahasiswa,
            'program_studi_id' => $this->prodiAgt->id,
        ]);

        // Mhs1 mengajukan lebih awal
        $sk1 = PengajuanSkripsi::create([
            'mahasiswa_id' => $mhs1->id,
            'program_studi_id' => $this->prodiAgt->id,
            'judul' => 'Klasifikasi Mutu Benih Padi Citra Morfologi FPST',
            'abstrak' => 'Abstrak skripsi satu',
            'status' => StatusPengajuan::Diajukan,
            'created_at' => now()->subDays(5),
        ]);

        // Mhs2 mengajukan lebih baru
        $sk2 = PengajuanSkripsi::create([
            'mahasiswa_id' => $mhs2->id,
            'program_studi_id' => $this->prodiAgt->id,
            'judul' => 'Analisis Rantai Pasok Pupuk Organik',
            'abstrak' => 'Abstrak skripsi dua',
            'status' => StatusPengajuan::Diajukan,
            'created_at' => now()->subDay(),
        ]);

        // Default: FIFO -> Mhs 1 muncul sebelum Mhs 2
        $response = $this->actingAs($this->kaprodiAgt)->get(route('kaprodi.penetapan.index', ['sort_judul' => 'fifo']));
        $response->assertStatus(200);
        $response->assertSeeInOrder(['Andi Saputra', 'Budi Santoso']);

        // Search filter: cari "Citra"
        $responseSearch = $this->actingAs($this->kaprodiAgt)->get(route('kaprodi.penetapan.index', ['search_judul' => 'Citra']));
        $responseSearch->assertStatus(200);
        $responseSearch->assertSee('Klasifikasi Mutu Benih Padi Citra Morfologi FPST');
        $responseSearch->assertDontSee('Analisis Rantai Pasok Pupuk Organik');
    }

    public function test_tab_seminar_and_sidang_render_and_filter(): void
    {
        $mhs = User::create([
            'name' => 'Citra Dewi',
            'email' => 'citra@example.test',
            'nomor_induk' => '221000000003',
            'password' => 'password',
            'role' => UserRole::Mahasiswa,
            'program_studi_id' => $this->prodiAgt->id,
        ]);

        $sk = PengajuanSkripsi::create([
            'mahasiswa_id' => $mhs->id,
            'program_studi_id' => $this->prodiAgt->id,
            'judul' => 'Respon Pertumbuhan Tanaman Tomat Hidroponik FPST',
            'abstrak' => 'Abstrak tomat hidroponik',
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

        $response = $this->actingAs($this->kaprodiAgt)->get(route('kaprodi.penetapan.index', ['tab' => 'seminar']));
        $response->assertStatus(200);
        $response->assertSee('Respon Pertumbuhan Tanaman Tomat Hidroponik FPST');
        $response->assertSee('Citra Dewi');

        $responseSidang = $this->actingAs($this->kaprodiAgt)->get(route('kaprodi.penetapan.index', ['tab' => 'sidang']));
        $responseSidang->assertStatus(200);
        $responseSidang->assertSee('Respon Pertumbuhan Tanaman Tomat Hidroponik FPST');
    }
}

