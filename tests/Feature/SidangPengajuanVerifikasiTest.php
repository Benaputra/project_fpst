<?php

namespace Tests\Feature;

use App\Actions\Sidang\AjukanSidang;
use App\Actions\Sidang\VerifikasiSidang;
use App\Enums\KeputusanVerifikasiPengajuan;
use App\Enums\StatusDokumenPengajuan;
use App\Enums\StatusSeminar;
use App\Enums\StatusSidangSkripsi;
use App\Enums\StatusSkripsi;
use App\Models\Dosen;
use App\Models\Mahasiswa;
use App\Models\ProgramStudi;
use App\Models\Seminar;
use App\Models\SidangSkripsi;
use App\Models\Skripsi;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SidangPengajuanVerifikasiTest extends TestCase
{
    use DatabaseTransactions;

    public function test_pengajuan_file_privat_verifikasi_dan_tanpa_jadwal(): void
    {
        Storage::fake('local');
        $d = $this->data();
        $sidang = app(AjukanSidang::class)->execute($d['user'], $d['skripsi'], $this->pdf());
        $this->assertSame(StatusSidangSkripsi::Diajukan, $sidang->status);
        $doc = $sidang->dokumenPengajuan->sole();
        Storage::disk('local')->assertExists($doc->file_path);
        $this->assertSame($doc->file_hash, hash('sha256', Storage::disk('local')->get($doc->file_path)));
        $admin = User::factory()->adminProdi()->create();
        $admin->programStudiAdministrasi()->attach($d['prodi']);
        $hasil = app(VerifikasiSidang::class)->execute($admin, $sidang, KeputusanVerifikasiPengajuan::Terima);
        $this->assertSame(StatusSidangSkripsi::Diverifikasi, $hasil->status);
        $this->assertNull($hasil->tanggal);
        $this->assertNull($hasil->penguji1_id);
        $this->assertSame(StatusDokumenPengajuan::Terverifikasi, $hasil->dokumenPengajuan->sole()->status);
        $this->assertDatabaseHas('aktivitas_log', [
            'subject_type' => SidangSkripsi::class,
            'subject_id' => $hasil->id,
            'aksi' => 'sidang_diverifikasi',
        ]);
    }

    public function test_prasyarat_penolakan_dan_perbaikan_record_sama(): void
    {
        Storage::fake('local');
        $d = $this->data();
        $d['skripsi']->forceFill(['status' => StatusSkripsi::SiapSeminar])->save();
        try {
            app(AjukanSidang::class)->execute($d['user'], $d['skripsi']);
            $this->fail();
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('skripsi', $e->errors());
        }
        $d['skripsi']->forceFill(['status' => StatusSkripsi::SiapSidang])->save();
        $sidang = app(AjukanSidang::class)->execute($d['user'], $d['skripsi'], $this->pdf('v1.pdf'));
        $admin = User::factory()->adminUtama()->create();
        try {
            app(VerifikasiSidang::class)->execute($admin, $sidang, KeputusanVerifikasiPengajuan::Tolak);
            $this->fail();
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('catatan_reject', $e->errors());
        }
        app(VerifikasiSidang::class)->execute($admin, $sidang, KeputusanVerifikasiPengajuan::Tolak, 'Perbaiki berkas.');
        $ulang = app(AjukanSidang::class)->execute($d['user'], $d['skripsi'], $this->pdf('v2.pdf'));
        $this->assertSame($sidang->id, $ulang->id);
        $this->assertSame([1, 2], $ulang->dokumenPengajuan->pluck('versi')->sort()->values()->all());
    }

    public function test_schema_unique_dan_status_request_tidak_bebas(): void
    {
        $d = $this->data();
        $sidang = SidangSkripsi::factory()->for($d['skripsi'])->create();
        $this->actingAs($d['user'])->post(route('skripsi.sidang.store', $d['skripsi']), ['status' => 'selesai', 'penguji1_id' => Dosen::factory()->create()->nidn])->assertSessionHasErrors('sidang');
        $this->assertSame(StatusSidangSkripsi::Diajukan, $sidang->fresh()->status);
    }

    public function test_file_hilang_membatalkan_verifikasi_dan_pemilik_dapat_download(): void
    {
        Storage::fake('local');
        $data = $this->data();
        $sidang = app(AjukanSidang::class)->execute($data['user'], $data['skripsi'], $this->pdf());
        $dokumen = $sidang->dokumenPengajuan->sole();
        $this->actingAs($data['user'])
            ->get(route('dokumen-pengajuan.download', $dokumen))
            ->assertOk();
        Storage::disk('local')->delete($dokumen->file_path);

        try {
            app(VerifikasiSidang::class)->execute(
                User::factory()->adminUtama()->create(),
                $sidang,
                KeputusanVerifikasiPengajuan::Terima
            );
            $this->fail('Dokumen hilang dapat diverifikasi.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('dokumen', $exception->errors());
        }

        $this->assertSame(StatusSidangSkripsi::Diajukan, $sidang->fresh()->status);
        $this->assertNull($sidang->fresh()->verified_by);
    }

    private function data(): array
    {
        $prodi = ProgramStudi::factory()->create();
        $pa = Dosen::factory()->create(['program_studi_id' => $prodi->id]);
        $user = User::factory()->mahasiswa()->create();
        $m = Mahasiswa::factory()->create(['program_studi_id' => $prodi->id, 'pembimbing_akademik_id' => $pa->nidn, 'user_id' => $user->id]);
        $skripsi = Skripsi::factory()->create(['nim' => $m->nim, 'status' => StatusSkripsi::SiapSidang]);
        Seminar::factory()->for($skripsi)->create(['status' => StatusSeminar::Selesai]);

        return compact('prodi', 'user', 'skripsi');
    }

    private function pdf(string $n = 'sidang.pdf'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($n, "%PDF-1.4\nsidang\n%%EOF");
    }
}
