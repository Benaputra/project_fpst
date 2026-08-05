<?php

namespace Tests\Feature;

use App\Actions\Dokumen\UploadHasilKonsultasi;
use App\Enums\StatusKesediaanBimbingan;
use App\Enums\StatusPengajuanJudul;
use App\Enums\StatusSurat;
use App\Models\Dosen;
use App\Models\KesediaanBimbingan;
use App\Models\Mahasiswa;
use App\Models\PengajuanJudul;
use App\Models\ProgramStudi;
use App\Models\Skripsi;
use App\Models\Surat;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HasilKonsultasiHttpUiTest extends TestCase
{
    use DatabaseTransactions;

    public function test_ui_menampilkan_surat_form_upload_dan_tanpa_field_keputusan(): void
    {
        $data = $this->dataKesediaan();
        $surat = Surat::factory()->for($data['kesediaan'], 'suratable')->create([
            'program_studi_id' => $data['programStudi']->id,
            'status' => StatusSurat::Diterbitkan,
        ]);

        $this->actingAs($data['mahasiswaUser'])
            ->get(route('mahasiswa.pengajuan-judul.index'))
            ->assertOk()
            ->assertSee($data['calon']->nama)
            ->assertSee('Unggah hasil konsultasi')
            ->assertSee(route('surat.download', $surat))
            ->assertSee('enctype="multipart/form-data"', false)
            ->assertSee('name="hasil_konsultasi"', false)
            ->assertSee('name="catatan_mahasiswa"', false)
            ->assertDontSee('name="hasil"', false)
            ->assertDontSee('name="status"', false)
            ->assertDontSee('name="verified_by"', false);
    }

    public function test_endpoint_upload_mengabaikan_field_keputusan_dari_request(): void
    {
        Storage::fake('local');
        $data = $this->dataKesediaan();

        $this->actingAs($data['mahasiswaUser'])
            ->from(route('mahasiswa.pengajuan-judul.index'))
            ->post(route('kesediaan-bimbingan.hasil-konsultasi.store', $data['kesediaan']), [
                'hasil_konsultasi' => $this->pdfValid(),
                'catatan_mahasiswa' => 'Mohon diperiksa.',
                'hasil' => 'bersedia',
                'status' => 'diterima',
                'verified_by' => $data['mahasiswaUser']->id,
            ])
            ->assertRedirect(route('mahasiswa.pengajuan-judul.index'))
            ->assertSessionHas('status', 'Hasil konsultasi berhasil diunggah dan menunggu verifikasi.');

        $this->assertDatabaseHas('dokumen_pengajuan', [
            'documentable_id' => $data['kesediaan']->id,
            'status' => 'menunggu_verifikasi',
            'uploaded_by' => $data['mahasiswaUser']->id,
            'verified_by' => null,
        ]);
        $this->assertDatabaseHas('kesediaan_bimbingan', [
            'id' => $data['kesediaan']->id,
            'status' => 'menunggu_verifikasi',
            'hasil' => null,
            'diverifikasi_oleh' => null,
        ]);
    }

    public function test_request_menolak_executable_mime_palsu_dan_file_terlalu_besar(): void
    {
        Storage::fake('local');
        $data = $this->dataKesediaan();
        $files = [
            UploadedFile::fake()->createWithContent('malware.php', '<?php echo 1;'),
            UploadedFile::fake()->createWithContent('palsu.pdf', '<?php echo 1;'),
            UploadedFile::fake()->create('besar.pdf', 5121, 'application/pdf'),
        ];

        foreach ($files as $file) {
            $this->actingAs($data['mahasiswaUser'])
                ->from('/')
                ->post(route('kesediaan-bimbingan.hasil-konsultasi.store', $data['kesediaan']), [
                    'hasil_konsultasi' => $file,
                ])
                ->assertRedirect('/')
                ->assertSessionHasErrors('hasil_konsultasi');
        }

        $this->assertDatabaseCount('dokumen_pengajuan', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_ui_setelah_upload_read_only_dan_tidak_menawarkan_penggantian(): void
    {
        Storage::fake('local');
        $data = $this->dataKesediaan();
        $dokumen = app(UploadHasilKonsultasi::class)->execute(
            $data['mahasiswaUser'],
            $data['kesediaan'],
            $this->pdfValid()
        );

        $this->actingAs($data['mahasiswaUser'])
            ->get(route('mahasiswa.pengajuan-judul.index'))
            ->assertOk()
            ->assertSee('Dokumen versi 1 telah diunggah')
            ->assertSee(route('dokumen-pengajuan.download', $dokumen))
            ->assertDontSee('name="hasil_konsultasi"', false)
            ->assertDontSee('Unggah perbaikan');
    }

    public function test_download_privat_hanya_untuk_pemilik_dan_menolak_hash_berubah(): void
    {
        Storage::fake('local');
        $data = $this->dataKesediaan();
        $dokumen = app(UploadHasilKonsultasi::class)->execute(
            $data['mahasiswaUser'],
            $data['kesediaan'],
            $this->pdfValid()
        );
        $mahasiswaLain = User::factory()->mahasiswa()->create();
        Mahasiswa::factory()->create(['user_id' => $mahasiswaLain->id]);

        $this->get(route('dokumen-pengajuan.download', $dokumen))
            ->assertRedirect(route('login'));
        $this->actingAs($mahasiswaLain)
            ->get(route('dokumen-pengajuan.download', $dokumen))
            ->assertForbidden();
        $this->actingAs($data['mahasiswaUser'])
            ->get(route('dokumen-pengajuan.download', $dokumen))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertHeader('x-content-type-options', 'nosniff');

        Storage::disk('local')->put($dokumen->file_path, '%PDF-diubah');
        $this->get(route('dokumen-pengajuan.download', $dokumen))->assertStatus(409);
    }

    public function test_mahasiswa_lain_dan_status_yang_salah_ditolak(): void
    {
        Storage::fake('local');
        $data = $this->dataKesediaan();
        $mahasiswaLain = User::factory()->mahasiswa()->create();
        Mahasiswa::factory()->create(['user_id' => $mahasiswaLain->id]);

        $this->actingAs($mahasiswaLain)
            ->post(route('kesediaan-bimbingan.hasil-konsultasi.store', $data['kesediaan']), [
                'hasil_konsultasi' => $this->pdfValid(),
            ])
            ->assertForbidden();

        $data['kesediaan']->forceFill([
            'status' => StatusKesediaanBimbingan::Diterima,
        ])->save();
        $this->actingAs($data['mahasiswaUser'])
            ->post(route('kesediaan-bimbingan.hasil-konsultasi.store', $data['kesediaan']), [
                'hasil_konsultasi' => $this->pdfValid(),
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('dokumen_pengajuan', 0);
    }

    /**
     * @return array{
     *   programStudi: ProgramStudi,
     *   mahasiswaUser: User,
     *   calon: Dosen,
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
        $pengajuan = PengajuanJudul::factory()->create([
            'nim' => $mahasiswa->nim,
            'status' => StatusPengajuanJudul::Diverifikasi,
            'diverifikasi_oleh' => $ketua->nidn,
            'diverifikasi_at' => now(),
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

        return compact('programStudi', 'mahasiswaUser', 'calon', 'kesediaan');
    }

    private function pdfValid(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            'hasil.pdf',
            "%PDF-1.4\n1 0 obj<</Type/Catalog>>endobj\n%%EOF"
        );
    }
}
