<?php

namespace Tests\Feature;

use App\Actions\Surat\TerbitkanSuratKesediaan;
use App\Enums\PeranKesediaanBimbingan;
use App\Enums\StatusKesediaanBimbingan;
use App\Enums\StatusSurat;
use App\Models\Dosen;
use App\Models\KesediaanBimbingan;
use App\Models\Mahasiswa;
use App\Models\PengajuanJudul;
use App\Models\ProgramStudi;
use App\Models\Skripsi;
use App\Models\User;
use App\Services\Pdf\SuratKesediaanPdf;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Tests\TestCase;

class TerbitkanSuratKesediaanTest extends TestCase
{
    use DatabaseTransactions;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_kaprodi_menerbitkan_pdf_privat_immutable_dengan_metadata_kanonis(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        Carbon::setTestNow('2026-08-04 22:15:00');
        $data = $this->dataKesediaan();

        $surat = app(TerbitkanSuratKesediaan::class)->execute(
            $data['ketuaUser'],
            $data['kesediaan']
        );

        $this->assertSame(KesediaanBimbingan::class, $surat->suratable_type);
        $this->assertSame($data['kesediaan']->id, $surat->suratable_id);
        $this->assertSame($data['programStudi']->id, $surat->program_studi_id);
        $this->assertSame(1, $surat->versi);
        $this->assertSame(StatusSurat::Diterbitkan, $surat->status);
        $this->assertStringContainsString('KSB-2026-', $surat->no_surat);
        $this->assertSame($data['calon']->nama, $surat->tujuan_surat);
        $this->assertNull($surat->verified_by);
        $this->assertNull($surat->signed_by);
        $this->assertNotNull($surat->generated_at);
        $this->assertSame(
            StatusKesediaanBimbingan::MenungguUpload,
            $data['kesediaan']->fresh()->status
        );

        Storage::disk('local')->assertExists($surat->file_path);
        Storage::disk('public')->assertMissing($surat->file_path);
        $pdf = Storage::disk('local')->get($surat->file_path);
        $this->assertStringStartsWith('%PDF-', $pdf);
        $this->assertSame(hash('sha256', $pdf), $surat->file_hash);
    }

    public function test_template_mengambil_dosen_mahasiswa_peran_siklus_dan_judul_dari_relasi(): void
    {
        $data = $this->dataKesediaan(
            PeranKesediaanBimbingan::Pembimbing2,
            siklus: 3,
            namaMahasiswa: 'Ayu Pertiwi',
            namaDosen: 'Dr. Budi Santoso',
            judul: 'Analisis Administrasi Skripsi Terpadu'
        );

        $html = app(SuratKesediaanPdf::class)->renderHtml(
            $data['kesediaan'],
            'KSB-TEST-001',
            Carbon::parse('2026-08-04')
        );

        foreach ([
            'KSB-TEST-001',
            '04/08/2026',
            'Pembimbing 2',
            '>3<',
            'Dr. Budi Santoso',
            $data['calon']->nidn,
            'Ayu Pertiwi',
            $data['mahasiswa']->nim,
            'Analisis Administrasi Skripsi Terpadu',
            'Bersedia menjadi pembimbing',
            'Tidak bersedia menjadi pembimbing',
            'Catatan:',
            'Tanda tangan asli calon pembimbing',
        ] as $teks) {
            $this->assertStringContainsString($teks, $html);
        }

        $this->assertStringNotContainsString('<img', $html);
        $this->assertStringNotContainsString('ttd_ketua_prodi', $html);
    }

    public function test_versi_baru_membatalkan_versi_lama_tanpa_menimpa_file(): void
    {
        Storage::fake('local');
        $data = $this->dataKesediaan();
        $action = app(TerbitkanSuratKesediaan::class);

        $versi1 = $action->execute($data['ketuaUser'], $data['kesediaan']);
        $isiVersi1 = Storage::disk('local')->get($versi1->file_path);
        $versi2 = $action->execute($data['ketuaUser'], $data['kesediaan']);

        $this->assertSame(StatusSurat::Dibatalkan, $versi1->fresh()->status);
        $this->assertSame(StatusSurat::Diterbitkan, $versi2->status);
        $this->assertSame(2, $versi2->versi);
        $this->assertNotSame($versi1->no_surat, $versi2->no_surat);
        $this->assertNotSame($versi1->file_path, $versi2->file_path);
        Storage::disk('local')->assertExists([$versi1->file_path, $versi2->file_path]);
        $this->assertSame($isiVersi1, Storage::disk('local')->get($versi1->file_path));
        $this->assertSame($versi1->file_hash, hash('sha256', $isiVersi1));
    }

    public function test_surat_hanya_diterbitkan_untuk_siklus_terbaru_yang_aktif(): void
    {
        Storage::fake('local');
        $data = $this->dataKesediaan();
        KesediaanBimbingan::factory()->for($data['skripsi'])->create([
            'dosen_id' => Dosen::factory()->create([
                'program_studi_id' => $data['programStudi']->id,
            ])->nidn,
            'peran' => $data['kesediaan']->peran,
            'siklus' => 2,
        ]);

        try {
            app(TerbitkanSuratKesediaan::class)->execute(
                $data['ketuaUser'],
                $data['kesediaan']
            );
            $this->fail('Surat dapat diterbitkan untuk siklus lama.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('kesediaan', $exception->errors());
        }

        $dataTidakAktif = $this->dataKesediaan();
        $dataTidakAktif['kesediaan']->forceFill([
            'status' => StatusKesediaanBimbingan::Diterima,
        ])->save();
        try {
            app(TerbitkanSuratKesediaan::class)->execute(
                $dataTidakAktif['ketuaUser'],
                $dataTidakAktif['kesediaan']
            );
            $this->fail('Surat dapat diterbitkan untuk calon yang selesai.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('kesediaan', $exception->errors());
        }

        $this->assertDatabaseCount('surat', 0);
    }

    public function test_admin_prodi_dan_admin_utama_dapat_menerbitkan_tanpa_signature(): void
    {
        Storage::fake('local');
        $dataAdminProdi = $this->dataKesediaan();
        $adminProdi = User::factory()->adminProdi()->create();
        $adminProdi->programStudiAdministrasi()->attach($dataAdminProdi['programStudi']);
        $suratAdminProdi = app(TerbitkanSuratKesediaan::class)->execute(
            $adminProdi,
            $dataAdminProdi['kesediaan']
        );

        $dataAdminUtama = $this->dataKesediaan();
        $adminUtama = User::factory()->adminUtama()->create();
        $suratAdminUtama = app(TerbitkanSuratKesediaan::class)->execute(
            $adminUtama,
            $dataAdminUtama['kesediaan']
        );

        $this->assertNull($suratAdminProdi->signed_by);
        $this->assertNull($suratAdminProdi->signed_at);
        $this->assertNull($suratAdminUtama->signed_by);
        $this->assertNull($suratAdminUtama->signed_at);
    }

    public function test_mahasiswa_dosen_biasa_dan_kaprodi_lain_tidak_dapat_menerbitkan(): void
    {
        Storage::fake('local');
        $data = $this->dataKesediaan();
        $dosenBiasaUser = User::factory()->dosen()->create();
        Dosen::factory()->create([
            'program_studi_id' => $data['programStudi']->id,
            'user_id' => $dosenBiasaUser->id,
        ]);
        $prodiLain = ProgramStudi::factory()->create();
        $kaprodiLainUser = User::factory()->dosen()->create();
        $kaprodiLain = Dosen::factory()->create([
            'program_studi_id' => $prodiLain->id,
            'user_id' => $kaprodiLainUser->id,
        ]);
        $prodiLain->update(['ketua_prodi_id' => $kaprodiLain->nidn]);

        foreach ([$data['mahasiswaUser'], $dosenBiasaUser, $kaprodiLainUser] as $user) {
            try {
                app(TerbitkanSuratKesediaan::class)->execute($user, $data['kesediaan']);
                $this->fail('Pengguna tanpa kewenangan dapat menerbitkan surat.');
            } catch (AuthorizationException) {
                $this->assertDatabaseCount('surat', 0);
            }
        }
    }

    public function test_kegagalan_renderer_merollback_database_dan_status(): void
    {
        Storage::fake('local');
        $data = $this->dataKesediaan();
        $this->mock(SuratKesediaanPdf::class, function ($mock) {
            $mock->shouldReceive('render')->once()->andThrow(
                new RuntimeException('Simulasi renderer gagal.')
            );
        });

        try {
            app(TerbitkanSuratKesediaan::class)->execute(
                $data['ketuaUser'],
                $data['kesediaan']
            );
            $this->fail('Kegagalan renderer tidak diteruskan.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Simulasi renderer gagal.', $exception->getMessage());
        }

        $this->assertDatabaseCount('surat', 0);
        $this->assertSame(
            StatusKesediaanBimbingan::Ditunjuk,
            $data['kesediaan']->fresh()->status
        );
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    /**
     * @return array{
     *   programStudi: ProgramStudi,
     *   ketuaUser: User,
     *   calon: Dosen,
     *   mahasiswaUser: User,
     *   mahasiswa: Mahasiswa,
     *   skripsi: Skripsi,
     *   kesediaan: KesediaanBimbingan
     * }
     */
    private function dataKesediaan(
        PeranKesediaanBimbingan $peran = PeranKesediaanBimbingan::Pembimbing1,
        int $siklus = 1,
        string $namaMahasiswa = 'Mahasiswa Surat',
        string $namaDosen = 'Dosen Calon',
        string $judul = 'Judul Surat Kesediaan'
    ): array {
        $programStudi = ProgramStudi::factory()->create(['nama' => 'Teknik Informatika']);
        $ketuaUser = User::factory()->dosen()->create();
        $ketua = Dosen::factory()->create([
            'program_studi_id' => $programStudi->id,
            'user_id' => $ketuaUser->id,
        ]);
        $programStudi->update(['ketua_prodi_id' => $ketua->nidn]);
        $mahasiswaUser = User::factory()->mahasiswa()->create();
        $mahasiswa = Mahasiswa::factory()->create([
            'nama' => $namaMahasiswa,
            'program_studi_id' => $programStudi->id,
            'pembimbing_akademik_id' => $ketua->nidn,
            'user_id' => $mahasiswaUser->id,
        ]);
        $pengajuan = PengajuanJudul::factory()->diverifikasi($ketua)->create([
            'nim' => $mahasiswa->nim,
            'judul' => $judul,
        ]);
        $skripsi = Skripsi::factory()->for($pengajuan, 'pengajuanJudul')->create([
            'nim' => $mahasiswa->nim,
            'judul' => $judul,
        ]);
        $calon = Dosen::factory()->create([
            'nama' => $namaDosen,
            'program_studi_id' => $programStudi->id,
        ]);
        $kesediaan = KesediaanBimbingan::factory()->for($skripsi)->create([
            'dosen_id' => $calon->nidn,
            'peran' => $peran,
            'siklus' => $siklus,
        ]);

        return compact(
            'programStudi',
            'ketuaUser',
            'calon',
            'mahasiswaUser',
            'mahasiswa',
            'skripsi',
            'kesediaan'
        );
    }
}
