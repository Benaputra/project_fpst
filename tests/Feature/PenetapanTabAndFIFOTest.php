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

    private ProgramStudi $prodiTI;
    private User $kaprodiTI;
    private User $dosen1;
    private User $dosen2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prodiTI = ProgramStudi::create(['nama' => 'Agroteknologi', 'kode' => 'AGT']);

        $this->kaprodiTI = User::create([
            'name' => 'Dr. Ratna Wijaya',
            'email' => 'kaprodi.ti@example.test',
            'nomor_induk' => '1000000001',
            'password' => 'password',
            'role' => UserRole::Kaprodi,
            'program_studi_id' => $this->prodiTI->id,
        ]);

        $this->dosen1 = User::create([
            'name' => 'Dr. Dosen Satu',
            'email' => 'dosen1@example.test',
            'nomor_induk' => '1000000002',
            'password' => 'password',
            'role' => UserRole::Dosen,
            'program_studi_id' => $this->prodiTI->id,
        ]);

        $this->dosen2 = User::create([
            'name' => 'Dr. Dosen Dua',
            'email' => 'dosen2@example.test',
            'nomor_induk' => '1000000003',
            'password' => 'password',
            'role' => UserRole::Dosen,
            'program_studi_id' => $this->prodiTI->id,
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
            'program_studi_id' => $this->prodiTI->id,
        ]);

        $mhs2 = User::create([
            'name' => 'Budi Santoso',
            'email' => 'budi@example.test',
            'nomor_induk' => '221000000002',
            'password' => 'password',
            'role' => UserRole::Mahasiswa,
            'program_studi_id' => $this->prodiTI->id,
        ]);

        // Mhs1 mengajukan lebih awal
        $sk1 = PengajuanSkripsi::create([
            'mahasiswa_id' => $mhs1->id,
            'program_studi_id' => $this->prodiTI->id,
            'judul' => 'Klasifikasi Citra Medis CNN',
            'abstrak' => 'Abstrak skripsi satu',
            'status' => StatusPengajuan::Diajukan,
            'created_at' => now()->subDays(5),
        ]);

        // Mhs2 mengajukan lebih baru
        $sk2 = PengajuanSkripsi::create([
            'mahasiswa_id' => $mhs2->id,
            'program_studi_id' => $this->prodiTI->id,
            'judul' => 'Sistem Smart Contract Blockchain',
            'abstrak' => 'Abstrak skripsi dua',
            'status' => StatusPengajuan::Diajukan,
            'created_at' => now()->subDay(),
        ]);

        // Default: FIFO -> Mhs 1 muncul sebelum Mhs 2
        $response = $this->actingAs($this->kaprodiTI)->get(route('kaprodi.penetapan.index', ['sort_judul' => 'fifo']));
        $response->assertStatus(200);
        $response->assertSeeInOrder(['Andi Saputra', 'Budi Santoso']);

        // Search filter: cari "Citra"
        $responseSearch = $this->actingAs($this->kaprodiTI)->get(route('kaprodi.penetapan.index', ['search_judul' => 'Citra']));
        $responseSearch->assertStatus(200);
        $responseSearch->assertSee('Klasifikasi Citra Medis CNN');
        $responseSearch->assertDontSee('Sistem Smart Contract Blockchain');
    }

    public function test_tab_seminar_and_sidang_render_and_filter(): void
    {
        $mhs = User::create([
            'name' => 'Citra Dewi',
            'email' => 'citra@example.test',
            'nomor_induk' => '221000000003',
            'password' => 'password',
            'role' => UserRole::Mahasiswa,
            'program_studi_id' => $this->prodiTI->id,
        ]);

        $sk = PengajuanSkripsi::create([
            'mahasiswa_id' => $mhs->id,
            'program_studi_id' => $this->prodiTI->id,
            'judul' => 'Sentimen Analisis IndoBERT',
            'abstrak' => 'Abstrak citra',
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

        $response = $this->actingAs($this->kaprodiTI)->get(route('kaprodi.penetapan.index', ['tab' => 'seminar']));
        $response->assertStatus(200);
        $response->assertSee('Sentimen Analisis IndoBERT');
        $response->assertSee('Citra Dewi');

        $responseSidang = $this->actingAs($this->kaprodiTI)->get(route('kaprodi.penetapan.index', ['tab' => 'sidang']));
        $responseSidang->assertStatus(200);
        $responseSidang->assertSee('Sentimen Analisis IndoBERT');
    }
}

