<?php

namespace Tests\Feature;

use App\Actions\Dokumen\UploadHasilKonsultasi;
use App\Enums\StatusDokumenPengajuan;
use App\Enums\StatusKesediaanBimbingan;
use App\Models\Dosen;
use App\Models\KesediaanBimbingan;
use App\Models\Mahasiswa;
use App\Models\PengajuanJudul;
use App\Models\ProgramStudi;
use App\Models\Skripsi;
use App\Models\User;
use App\Services\Upload\ValidasiHasilKonsultasi;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class UploadHasilKonsultasiTest extends TestCase
{
    use DatabaseTransactions;

    public function test_mahasiswa_pemilik_mengunggah_file_privat_dengan_metadata_server(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $data = $this->dataKesediaan();
        $file = $this->pdfValid('nama ../../ berbahaya.php.pdf');

        $dokumen = app(UploadHasilKonsultasi::class)->execute(
            $data['mahasiswaUser'],
            $data['kesediaan'],
            $file,
            '  Catatan dari mahasiswa.  '
        );

        $this->assertSame($data['kesediaan']->id, $dokumen->documentable_id);
        $this->assertSame(1, $dokumen->versi);
        $this->assertSame(StatusDokumenPengajuan::MenungguVerifikasi, $dokumen->status);
        $this->assertSame($data['mahasiswaUser']->id, $dokumen->uploaded_by);
        $this->assertNotNull($dokumen->uploaded_at);
        $this->assertNull($dokumen->verified_by);
        $this->assertNull($dokumen->verified_at);
        $this->assertNull($dokumen->catatan_verifikasi);
        $this->assertStringNotContainsString('nama', $dokumen->file_path);
        $this->assertStringNotContainsString('..', $dokumen->file_path);
        $this->assertStringEndsWith('.pdf', $dokumen->file_path);
        Storage::disk('local')->assertExists($dokumen->file_path);
        Storage::disk('public')->assertMissing($dokumen->file_path);
        $isi = Storage::disk('local')->get($dokumen->file_path);
        $this->assertSame(hash('sha256', $isi), $dokumen->file_hash);

        $kesediaan = $data['kesediaan']->fresh();
        $this->assertSame(StatusKesediaanBimbingan::MenungguVerifikasi, $kesediaan->status);
        $this->assertSame('Catatan dari mahasiswa.', $kesediaan->catatan_mahasiswa);
        $this->assertNull($kesediaan->hasil);
        $this->assertNull($kesediaan->diverifikasi_oleh);
        $this->assertNotNull($kesediaan->uploaded_at);
    }

    public function test_validator_menerima_pdf_jpeg_dan_png_dengan_magic_bytes_benar(): void
    {
        $validator = app(ValidasiHasilKonsultasi::class);
        $jpeg = UploadedFile::fake()->createWithContent(
            'scan.jpeg',
            "\xFF\xD8\xFF\xE0".str_repeat('x', 20)."\xFF\xD9"
        );
        $png = UploadedFile::fake()->createWithContent(
            'scan.png',
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=')
        );

        $this->assertSame('pdf', $validator->execute($this->pdfValid())['extension']);
        $this->assertSame('jpeg', $validator->execute($jpeg)['extension']);
        $this->assertSame('png', $validator->execute($png)['extension']);
    }

    public function test_executable_dan_file_dengan_ekstensi_palsu_ditolak(): void
    {
        $validator = app(ValidasiHasilKonsultasi::class);
        $files = [
            UploadedFile::fake()->createWithContent('malware.php', '<?php echo "bahaya";'),
            UploadedFile::fake()->createWithContent('palsu.pdf', '<?php echo "bukan pdf";'),
            UploadedFile::fake()->createWithContent('palsu.jpg', 'bukan jpeg'),
        ];

        foreach ($files as $file) {
            try {
                $validator->execute($file);
                $this->fail('File tidak aman diterima.');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('hasil_konsultasi', $exception->errors());
            }
        }
    }

    public function test_upload_ulang_hanya_setelah_invalid_dan_membuat_versi_baru(): void
    {
        Storage::fake('local');
        $data = $this->dataKesediaan();
        $action = app(UploadHasilKonsultasi::class);
        $versi1 = $action->execute(
            $data['mahasiswaUser'],
            $data['kesediaan'],
            $this->pdfValid('versi1.pdf')
        );
        $isiVersi1 = Storage::disk('local')->get($versi1->file_path);

        try {
            $action->execute(
                $data['mahasiswaUser'],
                $data['kesediaan']->fresh(),
                $this->pdfValid('terlalu-cepat.pdf')
            );
            $this->fail('Upload ulang sebelum invalid diterima.');
        } catch (AuthorizationException) {
            $this->assertDatabaseCount('dokumen_pengajuan', 1);
        }

        $versi1->forceFill([
            'status' => StatusDokumenPengajuan::Ditolak,
            'catatan_verifikasi' => 'Scan tidak terbaca.',
        ])->save();
        $data['kesediaan']->forceFill([
            'status' => StatusKesediaanBimbingan::UploadTidakValid,
            'catatan_verifikasi' => 'Scan tidak terbaca.',
        ])->save();
        $versi2 = $action->execute(
            $data['mahasiswaUser'],
            $data['kesediaan']->fresh(),
            $this->pdfValid('versi2.pdf')
        );

        $this->assertSame(2, $versi2->versi);
        $this->assertSame(StatusDokumenPengajuan::Ditolak, $versi1->fresh()->status);
        $this->assertNotSame($versi1->file_path, $versi2->file_path);
        $this->assertSame($isiVersi1, Storage::disk('local')->get($versi1->file_path));
        $this->assertNull($data['kesediaan']->fresh()->catatan_verifikasi);
        $this->assertDatabaseCount('dokumen_pengajuan', 2);
    }

    public function test_file_terverifikasi_tidak_dapat_diganti_meski_status_tidak_konsisten(): void
    {
        Storage::fake('local');
        $data = $this->dataKesediaan();
        $dokumen = app(UploadHasilKonsultasi::class)->execute(
            $data['mahasiswaUser'],
            $data['kesediaan'],
            $this->pdfValid()
        );
        $dokumen->forceFill(['status' => StatusDokumenPengajuan::Terverifikasi])->save();
        $data['kesediaan']->forceFill([
            'status' => StatusKesediaanBimbingan::UploadTidakValid,
        ])->save();

        try {
            app(UploadHasilKonsultasi::class)->execute(
                $data['mahasiswaUser'],
                $data['kesediaan']->fresh(),
                $this->pdfValid('pengganti.pdf')
            );
            $this->fail('Dokumen terverifikasi dapat diganti.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('hasil_konsultasi', $exception->errors());
        }

        $this->assertDatabaseCount('dokumen_pengajuan', 1);
    }

    public function test_mahasiswa_lain_tidak_dapat_mengunggah_ke_kesediaan_bukan_miliknya(): void
    {
        Storage::fake('local');
        $data = $this->dataKesediaan();
        $userLain = User::factory()->mahasiswa()->create();
        Mahasiswa::factory()->create(['user_id' => $userLain->id]);

        $this->expectException(AuthorizationException::class);

        app(UploadHasilKonsultasi::class)->execute(
            $userLain,
            $data['kesediaan'],
            $this->pdfValid()
        );
    }

    /**
     * @return array{
     *   mahasiswaUser: User,
     *   kesediaan: KesediaanBimbingan
     * }
     */
    private function dataKesediaan(): array
    {
        $programStudi = ProgramStudi::factory()->create();
        $ketua = Dosen::factory()->create(['program_studi_id' => $programStudi->id]);
        $mahasiswaUser = User::factory()->mahasiswa()->create();
        $mahasiswa = Mahasiswa::factory()->create([
            'program_studi_id' => $programStudi->id,
            'pembimbing_akademik_id' => $ketua->nidn,
            'user_id' => $mahasiswaUser->id,
        ]);
        $pengajuan = PengajuanJudul::factory()->diverifikasi($ketua)->create([
            'nim' => $mahasiswa->nim,
        ]);
        $skripsi = Skripsi::factory()->for($pengajuan, 'pengajuanJudul')->create([
            'nim' => $mahasiswa->nim,
            'judul' => $pengajuan->judul,
        ]);
        $calon = Dosen::factory()->create(['program_studi_id' => $programStudi->id]);
        $kesediaan = KesediaanBimbingan::factory()->for($skripsi)->create([
            'dosen_id' => $calon->nidn,
            'status' => StatusKesediaanBimbingan::MenungguUpload,
        ]);

        return compact('mahasiswaUser', 'kesediaan');
    }

    private function pdfValid(string $nama = 'hasil.pdf'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            $nama,
            "%PDF-1.4\n1 0 obj<</Type/Catalog>>endobj\n%%EOF"
        );
    }
}
