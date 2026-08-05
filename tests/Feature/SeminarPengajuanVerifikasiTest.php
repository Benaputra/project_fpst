<?php

namespace Tests\Feature;

use App\Actions\Seminar\AjukanSeminar;
use App\Actions\Seminar\VerifikasiSeminar;
use App\Enums\KeputusanVerifikasiPengajuan;
use App\Enums\StatusDokumenPengajuan;
use App\Enums\StatusSeminar;
use App\Enums\StatusSkripsi;
use App\Models\Dosen;
use App\Models\Mahasiswa;
use App\Models\PengajuanJudul;
use App\Models\ProgramStudi;
use App\Models\Seminar;
use App\Models\Skripsi;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SeminarPengajuanVerifikasiTest extends TestCase
{
    use DatabaseTransactions;

    public function test_schema_kanonis_dan_satu_seminar_per_skripsi(): void
    {
        $columns = collect(DB::select("SELECT COLUMN_NAME, IS_NULLABLE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'seminar' ORDER BY ORDINAL_POSITION"));
        $this->assertSame([
            'id', 'skripsi_id', 'penguji1_id', 'penguji2_id', 'tanggal', 'tempat',
            'status', 'catatan_reject', 'verified_by', 'verified_at', 'created_at', 'updated_at',
        ], $columns->pluck('COLUMN_NAME')->all());
        foreach (['penguji1_id', 'penguji2_id', 'tanggal', 'tempat'] as $nullable) {
            $this->assertSame('YES', $columns->firstWhere('COLUMN_NAME', $nullable)->IS_NULLABLE);
        }

        $seminar = Seminar::factory()->create();
        $this->expectException(QueryException::class);
        Seminar::factory()->create(['skripsi_id' => $seminar->skripsi_id]);
    }

    public function test_mahasiswa_mengajukan_miliknya_dengan_file_privat_hash_dan_data_turunan(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $data = $this->dataSkripsi();
        $seminar = app(AjukanSeminar::class)->execute(
            $data['mahasiswaUser'],
            $data['skripsi'],
            $this->pdf()
        );

        $this->assertSame(StatusSeminar::Diajukan, $seminar->status);
        $this->assertNull($seminar->penguji1_id);
        $this->assertNull($seminar->tanggal);
        $this->assertSame($data['skripsi']->judul, $seminar->skripsi->judul);
        $this->assertSame($data['pembimbing1']->nidn, $seminar->skripsi->pembimbing1_id);
        $dokumen = $seminar->dokumenPengajuan->sole();
        $this->assertSame(StatusDokumenPengajuan::MenungguVerifikasi, $dokumen->status);
        Storage::disk('local')->assertExists($dokumen->file_path);
        Storage::disk('public')->assertMissing($dokumen->file_path);
        $this->assertSame(
            $dokumen->file_hash,
            hash('sha256', Storage::disk('local')->get($dokumen->file_path))
        );
        $this->assertDatabaseHas('aktivitas_log', [
            'subject_type' => Seminar::class,
            'subject_id' => $seminar->id,
            'aksi' => 'seminar_diajukan',
        ]);
    }

    public function test_kelayakan_pemilik_dan_file_palsu_ditegakkan(): void
    {
        Storage::fake('local');
        $data = $this->dataSkripsi();
        $lain = User::factory()->mahasiswa()->create();
        Mahasiswa::factory()->create(['user_id' => $lain->id]);
        try {
            app(AjukanSeminar::class)->execute($lain, $data['skripsi'], null);
            $this->fail('Mahasiswa lain dapat mengajukan seminar.');
        } catch (AuthorizationException) {
            $this->assertDatabaseCount('seminar', 0);
        }

        $data['skripsi']->forceFill(['status' => StatusSkripsi::BimbinganAktif])->save();
        try {
            app(AjukanSeminar::class)->execute($data['mahasiswaUser'], $data['skripsi'], null);
            $this->fail('Skripsi belum siap dapat diajukan.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('skripsi', $exception->errors());
        }

        $data['skripsi']->forceFill(['status' => StatusSkripsi::SiapSeminar])->save();
        try {
            app(AjukanSeminar::class)->execute(
                $data['mahasiswaUser'],
                $data['skripsi'],
                UploadedFile::fake()->createWithContent('palsu.pdf', '<?php echo 1;')
            );
            $this->fail('File palsu diterima.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('berkas_seminar', $exception->errors());
        }
    }

    public function test_verifikator_scope_menerima_dan_tidak_menjadwalkan(): void
    {
        Storage::fake('local');
        $data = $this->dataSkripsi();
        $seminar = app(AjukanSeminar::class)->execute(
            $data['mahasiswaUser'], $data['skripsi'], $this->pdf()
        );
        $admin = User::factory()->adminProdi()->create();
        $admin->programStudiAdministrasi()->attach($data['programStudi']);
        $hasil = app(VerifikasiSeminar::class)->execute(
            $admin, $seminar, KeputusanVerifikasiPengajuan::Terima
        );

        $this->assertSame(StatusSeminar::Diverifikasi, $hasil->status);
        $this->assertSame($admin->id, $hasil->verified_by);
        $this->assertNull($hasil->tanggal);
        $this->assertNull($hasil->tempat);
        $this->assertNull($hasil->penguji1_id);
        $this->assertSame(
            StatusDokumenPengajuan::Terverifikasi,
            $hasil->dokumenPengajuan->sole()->status
        );
        $this->assertDatabaseHas('aktivitas_log', [
            'subject_type' => Seminar::class,
            'subject_id' => $hasil->id,
            'aksi' => 'seminar_diverifikasi',
        ]);

        $adminLain = User::factory()->adminProdi()->create();
        $adminLain->programStudiAdministrasi()->attach(ProgramStudi::factory()->create());
        $data2 = $this->dataSkripsi();
        $seminar2 = app(AjukanSeminar::class)->execute(
            $data2['mahasiswaUser'],
            $data2['skripsi']
        );
        try {
            app(VerifikasiSeminar::class)->execute(
                $adminLain, $seminar2, KeputusanVerifikasiPengajuan::Terima
            );
            $this->fail('Admin lintas prodi dapat memverifikasi.');
        } catch (AuthorizationException) {
            $this->assertSame(StatusSeminar::Diajukan, $seminar2->fresh()->status);
        }
    }

    public function test_penolakan_wajib_alasan_dan_perbaikan_memakai_record_sama_versi_baru(): void
    {
        Storage::fake('local');
        $data = $this->dataSkripsi();
        $seminar = app(AjukanSeminar::class)->execute(
            $data['mahasiswaUser'], $data['skripsi'], $this->pdf('v1.pdf')
        );
        $admin = User::factory()->adminUtama()->create();
        try {
            app(VerifikasiSeminar::class)->execute(
                $admin, $seminar, KeputusanVerifikasiPengajuan::Tolak
            );
            $this->fail('Penolakan tanpa alasan diterima.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('catatan_reject', $exception->errors());
        }
        app(VerifikasiSeminar::class)->execute(
            $admin, $seminar, KeputusanVerifikasiPengajuan::Tolak, 'Berkas tidak terbaca.'
        );
        $perbaikan = app(AjukanSeminar::class)->execute(
            $data['mahasiswaUser'], $data['skripsi'], $this->pdf('v2.pdf')
        );

        $this->assertSame($seminar->id, $perbaikan->id);
        $this->assertSame(StatusSeminar::Diajukan, $perbaikan->status);
        $this->assertNull($perbaikan->catatan_reject);
        $this->assertSame([1, 2], $perbaikan->dokumenPengajuan->pluck('versi')->sort()->values()->all());
        $this->assertSame(StatusDokumenPengajuan::Ditolak, $perbaikan->dokumenPengajuan->firstWhere('versi', 1)->status);
        $this->assertSame(StatusDokumenPengajuan::MenungguVerifikasi, $perbaikan->dokumenPengajuan->firstWhere('versi', 2)->status);
    }

    public function test_endpoint_tidak_menerima_status_jadwal_penguji_atau_verifikator_bebas(): void
    {
        Storage::fake('local');
        $data = $this->dataSkripsi();
        $this->actingAs($data['mahasiswaUser'])
            ->post(route('skripsi.seminar.store', $data['skripsi']), [
                'berkas_seminar' => $this->pdf(),
                'status' => 'selesai',
                'penguji1_id' => Dosen::factory()->create()->nidn,
                'tanggal' => now()->toDateTimeString(),
                'tempat' => 'Ruang manipulasi',
            ])->assertRedirect()->assertSessionHas('status');
        $seminar = $data['skripsi']->seminar()->sole();
        $this->assertSame(StatusSeminar::Diajukan, $seminar->status);
        $this->assertNull($seminar->penguji1_id);
        $this->assertNull($seminar->tanggal);

        $admin = User::factory()->adminUtama()->create();
        $this->actingAs($admin)
            ->post(route('seminar.verifikasi.store', $seminar), [
                'keputusan' => 'status_bebas',
                'verified_by' => $data['mahasiswaUser']->id,
            ])->assertSessionHasErrors('keputusan');
        $this->assertNull($seminar->fresh()->verified_by);
    }

    public function test_file_tampered_membatalkan_verifikasi_dan_download_tetap_scoped(): void
    {
        Storage::fake('local');
        $data = $this->dataSkripsi();
        $seminar = app(AjukanSeminar::class)->execute(
            $data['mahasiswaUser'],
            $data['skripsi'],
            $this->pdf()
        );
        $dokumen = $seminar->dokumenPengajuan->sole();

        $this->actingAs($data['mahasiswaUser'])
            ->get(route('dokumen-pengajuan.download', $dokumen))
            ->assertOk()
            ->assertHeader('x-content-type-options', 'nosniff');
        $mahasiswaLain = User::factory()->mahasiswa()->create();
        Mahasiswa::factory()->create(['user_id' => $mahasiswaLain->id]);
        $this->actingAs($mahasiswaLain)
            ->get(route('dokumen-pengajuan.download', $dokumen))
            ->assertForbidden();

        Storage::disk('local')->put($dokumen->file_path, '%PDF-file-diubah');
        try {
            app(VerifikasiSeminar::class)->execute(
                User::factory()->adminUtama()->create(),
                $seminar,
                KeputusanVerifikasiPengajuan::Terima
            );
            $this->fail('Dokumen dengan hash berubah dapat diverifikasi.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('dokumen', $exception->errors());
        }

        $this->assertSame(StatusSeminar::Diajukan, $seminar->fresh()->status);
        $this->assertSame(StatusDokumenPengajuan::MenungguVerifikasi, $dokumen->fresh()->status);
    }

    /** @return array<string, mixed> */
    private function dataSkripsi(): array
    {
        $programStudi = ProgramStudi::factory()->create();
        $ketua = Dosen::factory()->create(['program_studi_id' => $programStudi->id]);
        $mahasiswaUser = User::factory()->mahasiswa()->create();
        $mahasiswa = Mahasiswa::factory()->create([
            'program_studi_id' => $programStudi->id,
            'pembimbing_akademik_id' => $ketua->nidn,
            'user_id' => $mahasiswaUser->id,
        ]);
        $pengajuan = PengajuanJudul::factory()->diverifikasi($ketua)->create(['nim' => $mahasiswa->nim]);
        $pembimbing1 = Dosen::factory()->create(['program_studi_id' => $programStudi->id]);
        $skripsi = Skripsi::factory()->for($pengajuan, 'pengajuanJudul')->create([
            'nim' => $mahasiswa->nim,
            'judul' => $pengajuan->judul,
            'pembimbing1_id' => $pembimbing1->nidn,
            'status' => StatusSkripsi::SiapSeminar,
        ]);

        return compact('programStudi', 'mahasiswaUser', 'pembimbing1', 'skripsi');
    }

    private function pdf(string $nama = 'seminar.pdf'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            $nama, "%PDF-1.4\nberkas seminar\n%%EOF"
        );
    }
}
