<?php

namespace Tests\Feature;

use App\Enums\StatusPengajuan;
use App\Enums\UserRole;
use App\Models\AktivitasLog;
use App\Models\PengajuanSkripsi;
use App\Models\ProgramStudi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MasterDataAdminUtamaTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUtama;
    private User $adminProdi;
    private User $dosen;
    private User $mahasiswa;
    private ProgramStudi $prodiTi;
    private ProgramStudi $prodiSi;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prodiTi = ProgramStudi::create(['nama' => 'Agroteknologi', 'kode' => 'AGT']);
        $this->prodiSi = ProgramStudi::create(['nama' => 'Agribisnis', 'kode' => 'AGB']);

        $this->adminUtama = User::create([
            'name' => 'Admin Utama FPST',
            'email' => 'admin.utama@test.com',
            'nomor_induk' => 'ADM-ROOT',
            'password' => Hash::make('password'),
            'role' => UserRole::AdminUtama,
        ]);

        $this->adminProdi = User::create([
            'name' => 'Admin Prodi TI',
            'email' => 'admin.ti@test.com',
            'nomor_induk' => 'ADM-TI',
            'password' => Hash::make('password'),
            'role' => UserRole::AdminProdi,
            'program_studi_id' => $this->prodiTi->id,
        ]);

        $this->dosen = User::create([
            'name' => 'Dr. Dosen Penguji',
            'email' => 'dosen@test.com',
            'nomor_induk' => '1000000010',
            'password' => Hash::make('password'),
            'role' => UserRole::Dosen,
            'program_studi_id' => $this->prodiTi->id,
        ]);

        $this->mahasiswa = User::create([
            'name' => 'Mahasiswa Test',
            'email' => 'mahasiswa@test.com',
            'nomor_induk' => '221000000099',
            'password' => Hash::make('password'),
            'role' => UserRole::Mahasiswa,
            'program_studi_id' => $this->prodiTi->id,
        ]);
    }

    public function test_non_admin_utama_cannot_access_master_data(): void
    {
        // 1. Guest diarahkan ke login
        $this->get(route('admin.master.mahasiswa.index'))->assertRedirect(route('login'));

        // 2. Mahasiswa mendapat 403 AKSES DIBATASI
        $response = $this->actingAs($this->mahasiswa)->get(route('admin.master.mahasiswa.index'));
        $response->assertStatus(403);
        $response->assertSee('AKSES DIBATASI');

        // 3. Dosen mendapat 403 AKSES DIBATASI
        $response = $this->actingAs($this->dosen)->get(route('admin.master.dosen.index'));
        $response->assertStatus(403);
        $response->assertSee('AKSES DIBATASI');

        // 4. Admin Prodi mendapat 403 AKSES DIBATASI
        $response = $this->actingAs($this->adminProdi)->get(route('admin.master.user.index'));
        $response->assertStatus(403);
        $response->assertSee('AKSES DIBATASI');
    }

    public function test_admin_utama_can_render_all_master_data_pages(): void
    {
        $this->actingAs($this->adminUtama)->get(route('admin.master.mahasiswa.index'))->assertOk();
        $this->actingAs($this->adminUtama)->get(route('admin.master.dosen.index'))->assertOk();
        $this->actingAs($this->adminUtama)->get(route('admin.master.user.index'))->assertOk();
        $this->actingAs($this->adminUtama)->get(route('admin.master.prodi.index'))->assertOk();
        $this->actingAs($this->adminUtama)->get(route('admin.master.admin-prodi.index'))->assertOk();
    }

    public function test_admin_utama_can_create_and_update_mahasiswa(): void
    {
        // Create mahasiswa
        $res = $this->actingAs($this->adminUtama)->post(route('admin.master.mahasiswa.store'), [
            'name' => 'Ahmad Fajar',
            'nomor_induk' => '221000000055',
            'email' => 'ahmad.fajar@test.com',
            'program_studi_id' => $this->prodiTi->id,
            'no_hp' => '081234567890',
            'password' => 'secret123',
        ]);

        $res->assertRedirect(route('admin.master.mahasiswa.index'));
        $this->assertDatabaseHas('users', [
            'name' => 'Ahmad Fajar',
            'nomor_induk' => '221000000055',
            'email' => 'ahmad.fajar@test.com',
            'role' => 'mahasiswa',
        ]);

        $created = User::where('nomor_induk', '221000000055')->first();
        $this->assertTrue(Hash::check('secret123', $created->password));

        // Update mahasiswa
        $this->actingAs($this->adminUtama)->put(route('admin.master.mahasiswa.update', $created->id), [
            'name' => 'Ahmad Fajar Pratama',
            'nomor_induk' => '221000000055',
            'email' => 'ahmad.pratama@test.com',
            'program_studi_id' => $this->prodiSi->id,
            'no_hp' => '089999999999',
        ])->assertRedirect(route('admin.master.mahasiswa.index'));

        $this->assertDatabaseHas('users', [
            'id' => $created->id,
            'name' => 'Ahmad Fajar Pratama',
            'email' => 'ahmad.pratama@test.com',
            'program_studi_id' => $this->prodiSi->id,
        ]);
    }

    public function test_mahasiswa_with_skripsi_cannot_be_deleted(): void
    {
        PengajuanSkripsi::create([
            'mahasiswa_id' => $this->mahasiswa->id,
            'program_studi_id' => $this->prodiTi->id,
            'judul' => 'Sistem Informasi Skripsi Berbasis Web',
            'status' => StatusPengajuan::Diajukan,
        ]);

        $res = $this->actingAs($this->adminUtama)->delete(route('admin.master.mahasiswa.destroy', $this->mahasiswa->id));
        $res->assertSessionHas('error');
        $this->assertDatabaseHas('users', ['id' => $this->mahasiswa->id]);

        // Mahasiswa tanpa skripsi dapat dihapus
        $mhsBaru = User::create([
            'name' => 'Mahasiswa Bersih',
            'email' => 'mhs.bersih@test.com',
            'nomor_induk' => '221000000077',
            'password' => Hash::make('password'),
            'role' => UserRole::Mahasiswa,
            'program_studi_id' => $this->prodiTi->id,
        ]);

        $this->actingAs($this->adminUtama)->delete(route('admin.master.mahasiswa.destroy', $mhsBaru->id))
            ->assertRedirect(route('admin.master.mahasiswa.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('users', ['id' => $mhsBaru->id]);
    }

    public function test_batch_import_mahasiswa_csv(): void
    {
        // 1. Unduh template
        $resTemplate = $this->actingAs($this->adminUtama)->get(route('admin.master.mahasiswa.template-csv'));
        $resTemplate->assertOk();
        $resTemplate->assertHeader('Content-Disposition', 'attachment; filename="template_import_mahasiswa.csv"');

        // 2. Buat file CSV valid
        $csvContent = "nama,nim,email,kode_prodi,no_hp,password\n"
            . "Mahasiswa CSV Satu,221000001001,csv1@test.com,AGT,081111111111,pass123\n"
            . "Mahasiswa CSV Dua,222000001002,csv2@test.com,AGB,082222222222,\n"
            . "Mahasiswa CSV Gagal Duplikat,221000001001,csv3@test.com,AGT,083333333333,\n" // NIM duplikat
            . "Mahasiswa CSV Gagal Prodi,221000001004,csv4@test.com,PRODI_ASING,084444444444,\n";

        $file = UploadedFile::fake()->createWithContent('import.csv', $csvContent);

        $res = $this->actingAs($this->adminUtama)->post(route('admin.master.mahasiswa.import-csv'), [
            'file_csv' => $file,
        ]);

        $res->assertRedirect(route('admin.master.mahasiswa.index'));
        $res->assertSessionHas('success');
        $res->assertSessionHas('csv_errors');

        $this->assertDatabaseHas('users', [
            'nomor_induk' => '221000001001',
            'name' => 'Mahasiswa CSV Satu',
            'role' => 'mahasiswa',
        ]);

        $this->assertDatabaseHas('users', [
            'nomor_induk' => '222000001002',
            'name' => 'Mahasiswa CSV Dua',
            'role' => 'mahasiswa',
        ]);

        // Cek bahwa baris gagal tidak masuk
        $this->assertDatabaseMissing('users', ['email' => 'csv4@test.com']);
    }

    public function test_admin_utama_can_create_and_update_dosen(): void
    {
        // Buat Dosen baru sebagai Kaprodi
        $res = $this->actingAs($this->adminUtama)->post(route('admin.master.dosen.store'), [
            'name' => 'Dr. Ratna Dewi, M.Kom.',
            'nomor_induk' => '1000000088',
            'email' => 'ratna.dewi@test.com',
            'program_studi_id' => $this->prodiTi->id,
            'role' => 'kaprodi',
            'no_hp' => '081234567888',
        ]);

        $res->assertRedirect(route('admin.master.dosen.index'));
        $this->assertDatabaseHas('users', [
            'nomor_induk' => '1000000088',
            'role' => 'kaprodi',
        ]);

        $dosenBaru = User::where('nomor_induk', '1000000088')->first();

        // Update jadi Dosen biasa
        $this->actingAs($this->adminUtama)->put(route('admin.master.dosen.update', $dosenBaru->id), [
            'name' => 'Dr. Ratna Dewi, M.Kom.',
            'nomor_induk' => '1000000088',
            'email' => 'ratna.dewi@test.com',
            'program_studi_id' => $this->prodiSi->id,
            'role' => 'dosen',
        ])->assertRedirect(route('admin.master.dosen.index'));

        $this->assertDatabaseHas('users', [
            'id' => $dosenBaru->id,
            'role' => 'dosen',
            'program_studi_id' => $this->prodiSi->id,
        ]);
    }

    public function test_admin_utama_can_change_user_role(): void
    {
        $targetUser = User::create([
            'name' => 'Staff Biasa',
            'email' => 'staff@test.com',
            'password' => Hash::make('password'),
            'role' => UserRole::Dosen,
            'program_studi_id' => $this->prodiTi->id,
        ]);

        // Promosikan ke Admin Utama
        $this->actingAs($this->adminUtama)->put(route('admin.master.user.update', $targetUser->id), [
            'name' => 'Staff Promosi',
            'email' => 'staff@test.com',
            'role' => 'admin_utama',
        ])->assertRedirect(route('admin.master.user.index'));

        $this->assertDatabaseHas('users', [
            'id' => $targetUser->id,
            'name' => 'Staff Promosi',
            'role' => 'admin_utama',
        ]);

        // Proteksi: Admin Utama tidak dapat menurunkan role dirinya sendiri
        $resSelf = $this->actingAs($this->adminUtama)->put(route('admin.master.user.update', $this->adminUtama->id), [
            'name' => $this->adminUtama->name,
            'email' => $this->adminUtama->email,
            'role' => 'dosen',
        ]);
        $resSelf->assertSessionHas('error');
        $this->assertDatabaseHas('users', ['id' => $this->adminUtama->id, 'role' => 'admin_utama']);
    }

    public function test_admin_utama_can_manage_program_studi(): void
    {
        // Tambah Prodi
        $this->actingAs($this->adminUtama)->post(route('admin.master.prodi.store'), [
            'nama' => 'Teknik Elektro',
            'kode' => 'TE',
        ])->assertRedirect(route('admin.master.prodi.index'));

        $this->assertDatabaseHas('program_studi', [
            'nama' => 'Teknik Elektro',
            'kode' => 'TE',
        ]);

        $prodiTe = ProgramStudi::where('kode', 'TE')->first();

        // Update Prodi
        $this->actingAs($this->adminUtama)->put(route('admin.master.prodi.update', $prodiTe->id), [
            'nama' => 'Teknik Elektro Industri',
            'kode' => 'TEI',
        ])->assertRedirect(route('admin.master.prodi.index'));

        $this->assertDatabaseHas('program_studi', [
            'id' => $prodiTe->id,
            'nama' => 'Teknik Elektro Industri',
            'kode' => 'TEI',
        ]);

        // Hapus Prodi kosong
        $this->actingAs($this->adminUtama)->delete(route('admin.master.prodi.destroy', $prodiTe->id))
            ->assertRedirect(route('admin.master.prodi.index'));

        $this->assertDatabaseMissing('program_studi', ['id' => $prodiTe->id]);

        // Hapus Prodi yang masih ada user harus gagal
        $res = $this->actingAs($this->adminUtama)->delete(route('admin.master.prodi.destroy', $this->prodiTi->id));
        $res->assertSessionHas('error');
        $this->assertDatabaseHas('program_studi', ['id' => $this->prodiTi->id]);
    }

    public function test_admin_utama_can_manage_admin_prodi(): void
    {
        // Tambah Admin Prodi
        $res = $this->actingAs($this->adminUtama)->post(route('admin.master.admin-prodi.store'), [
            'name' => 'Staf Tata Usaha SI',
            'email' => 'admin.si.baru@test.com',
            'nomor_induk' => 'ADM-SI-02',
            'program_studi_id' => $this->prodiSi->id,
            'no_hp' => '081234567800',
        ]);

        $res->assertRedirect(route('admin.master.admin-prodi.index'));
        $this->assertDatabaseHas('users', [
            'email' => 'admin.si.baru@test.com',
            'role' => 'admin_prodi',
            'program_studi_id' => $this->prodiSi->id,
        ]);

        $newAdmin = User::where('email', 'admin.si.baru@test.com')->first();

        // Update Admin Prodi
        $this->actingAs($this->adminUtama)->put(route('admin.master.admin-prodi.update', $newAdmin->id), [
            'name' => 'Staf Tata Usaha SI Update',
            'email' => 'admin.si.baru@test.com',
            'program_studi_id' => $this->prodiTi->id,
        ])->assertRedirect(route('admin.master.admin-prodi.index'));

        $this->assertDatabaseHas('users', [
            'id' => $newAdmin->id,
            'name' => 'Staf Tata Usaha SI Update',
            'program_studi_id' => $this->prodiTi->id,
        ]);

        // Hapus Admin Prodi
        $this->actingAs($this->adminUtama)->delete(route('admin.master.admin-prodi.destroy', $newAdmin->id))
            ->assertRedirect(route('admin.master.admin-prodi.index'));

        $this->assertDatabaseMissing('users', ['id' => $newAdmin->id]);
    }

    public function test_activity_log_recorded_for_master_data_mutations(): void
    {
        $this->actingAs($this->adminUtama)->post(route('admin.master.prodi.store'), [
            'nama' => 'Teknik Lingkungan',
            'kode' => 'TL',
        ]);

        $this->assertDatabaseHas('aktivitas_log', [
            'user_id' => $this->adminUtama->id,
            'aksi' => 'Tambah Program Studi',
        ]);
    }
}
