<?php

namespace Tests\Feature;

use App\Actions\Dokumen\VerifikasiHasilKonsultasi;
use App\Enums\HasilKesediaanBimbingan;
use App\Enums\KeputusanHasilKonsultasi;
use App\Enums\PeranKesediaanBimbingan;
use App\Enums\StatusDokumenPengajuan;
use App\Enums\StatusKesediaanBimbingan;
use App\Enums\StatusPengajuanJudul;
use App\Enums\StatusSkripsi;
use App\Models\DokumenPengajuan;
use App\Models\Dosen;
use App\Models\KesediaanBimbingan;
use App\Models\Mahasiswa;
use App\Models\PengajuanJudul;
use App\Models\ProgramStudi;
use App\Models\Skripsi;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class VerifikasiHasilKonsultasiTest extends TestCase
{
    use DatabaseTransactions;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_admin_prodi_mencatat_valid_bersedia_tanpa_memfinalkan_pembimbing(): void
    {
        Storage::fake('local');
        Carbon::setTestNow('2026-08-05 09:15:00');
        $data = $this->dataDokumen();
        $admin = User::factory()->adminProdi()->create();
        $admin->programStudiAdministrasi()->attach($data['programStudi']);

        $hasil = app(VerifikasiHasilKonsultasi::class)->execute(
            $admin,
            $data['dokumen'],
            KeputusanHasilKonsultasi::ValidBersedia
        );

        $this->assertSame(StatusKesediaanBimbingan::Diterima, $hasil->status);
        $this->assertSame(HasilKesediaanBimbingan::Bersedia, $hasil->hasil);
        $this->assertSame($admin->id, $hasil->diverifikasi_oleh);
        $this->assertSame('2026-08-05 09:15:00', $hasil->diverifikasi_at->format('Y-m-d H:i:s'));
        $dokumen = $data['dokumen']->fresh();
        $this->assertSame(StatusDokumenPengajuan::Terverifikasi, $dokumen->status);
        $this->assertSame($admin->id, $dokumen->verified_by);
        $this->assertNull($dokumen->catatan_verifikasi);

        $skripsi = $data['skripsi']->fresh();
        $this->assertSame(StatusSkripsi::MenungguKesediaanPembimbing, $skripsi->status);
        $this->assertNull($skripsi->pembimbing1_id);
        $this->assertNull($skripsi->pembimbing2_id);
        $this->assertSame(StatusPengajuanJudul::Diverifikasi, $data['pengajuan']->fresh()->status);
        $this->assertSame($data['pengajuan']->judul, $skripsi->judul);
    }

    public function test_penolakan_hanya_mengubah_posisi_terkait_dan_catatan_wajib(): void
    {
        Storage::fake('local');
        $data = $this->dataDokumen();
        $diterima = KesediaanBimbingan::factory()->for($data['skripsi'])->pembimbing2()->create([
            'dosen_id' => Dosen::factory()->create([
                'program_studi_id' => $data['programStudi']->id,
            ])->nidn,
            'status' => StatusKesediaanBimbingan::Diterima,
            'hasil' => HasilKesediaanBimbingan::Bersedia,
        ]);

        try {
            app(VerifikasiHasilKonsultasi::class)->execute(
                $data['ketuaUser'],
                $data['dokumen'],
                KeputusanHasilKonsultasi::ValidTidakBersedia
            );
            $this->fail('Penolakan tanpa catatan dapat disimpan.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('catatan_verifikasi', $exception->errors());
        }

        $hasil = app(VerifikasiHasilKonsultasi::class)->execute(
            $data['ketuaUser'],
            $data['dokumen'],
            KeputusanHasilKonsultasi::ValidTidakBersedia,
            '  Calon menyatakan tidak bersedia.  '
        );

        $this->assertSame(StatusKesediaanBimbingan::Ditolak, $hasil->status);
        $this->assertSame(HasilKesediaanBimbingan::TidakBersedia, $hasil->hasil);
        $this->assertSame('Calon menyatakan tidak bersedia.', $hasil->catatan_verifikasi);
        $this->assertSame(StatusKesediaanBimbingan::Diterima, $diterima->fresh()->status);
        $this->assertSame(HasilKesediaanBimbingan::Bersedia, $diterima->fresh()->hasil);
    }

    public function test_upload_tidak_valid_memungkinkan_perbaikan_tanpa_menghapus_file(): void
    {
        Storage::fake('local');
        $data = $this->dataDokumen();

        $hasil = app(VerifikasiHasilKonsultasi::class)->execute(
            User::factory()->adminUtama()->create(),
            $data['dokumen'],
            KeputusanHasilKonsultasi::UploadTidakValid,
            'Scan buram dan terpotong.'
        );

        $this->assertSame(StatusKesediaanBimbingan::UploadTidakValid, $hasil->status);
        $this->assertNull($hasil->hasil);
        $this->assertSame(StatusDokumenPengajuan::Ditolak, $data['dokumen']->fresh()->status);
        Storage::disk('local')->assertExists($data['dokumen']->file_path);
    }

    public function test_semua_role_dan_scope_verifikator_ditegakkan(): void
    {
        Storage::fake('local');
        $data = $this->dataDokumen();
        $adminLain = User::factory()->adminProdi()->create();
        $adminLain->programStudiAdministrasi()->attach(ProgramStudi::factory()->create());
        $kaprodiLain = $this->kaprodiUntuk(ProgramStudi::factory()->create());
        $dosenBiasa = User::factory()->dosen()->create();
        Dosen::factory()->create([
            'program_studi_id' => $data['programStudi']->id,
            'user_id' => $dosenBiasa->id,
        ]);

        foreach ([$adminLain, $kaprodiLain, $dosenBiasa, $data['mahasiswaUser']] as $user) {
            try {
                app(VerifikasiHasilKonsultasi::class)->execute(
                    $user,
                    $data['dokumen'],
                    KeputusanHasilKonsultasi::ValidBersedia
                );
                $this->fail('Role atau scope yang tidak sah dapat memverifikasi.');
            } catch (AuthorizationException) {
                $this->assertSame(
                    StatusDokumenPengajuan::MenungguVerifikasi,
                    $data['dokumen']->fresh()->status
                );
            }
        }

        app(VerifikasiHasilKonsultasi::class)->execute(
            $data['ketuaUser'],
            $data['dokumen'],
            KeputusanHasilKonsultasi::ValidBersedia
        );
        $this->assertSame(StatusDokumenPengajuan::Terverifikasi, $data['dokumen']->fresh()->status);
    }

    public function test_hanya_versi_terbaru_dan_transisi_tunggal_yang_dapat_diverifikasi(): void
    {
        Storage::fake('local');
        $data = $this->dataDokumen();
        $data['dokumen']->forceFill(['status' => StatusDokumenPengajuan::Ditolak])->save();
        $versi2 = $this->buatDokumen($data['kesediaan'], $data['mahasiswaUser'], 2);

        foreach ([$data['dokumen'], $versi2] as $index => $dokumen) {
            if ($index === 1) {
                app(VerifikasiHasilKonsultasi::class)->execute(
                    $data['ketuaUser'],
                    $dokumen,
                    KeputusanHasilKonsultasi::ValidBersedia
                );
            }

            try {
                app(VerifikasiHasilKonsultasi::class)->execute(
                    $data['ketuaUser'],
                    $dokumen,
                    KeputusanHasilKonsultasi::ValidBersedia
                );
                $this->fail('Dokumen lama atau keputusan ganda diterima.');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('dokumen', $exception->errors());
            }
        }
    }

    public function test_file_hilang_atau_hash_berubah_merollback_keputusan(): void
    {
        Storage::fake('local');

        foreach (['hilang', 'berubah'] as $kondisi) {
            $data = $this->dataDokumen();
            if ($kondisi === 'hilang') {
                Storage::disk('local')->delete($data['dokumen']->file_path);
            } else {
                Storage::disk('local')->put($data['dokumen']->file_path, 'isi diubah');
            }

            try {
                app(VerifikasiHasilKonsultasi::class)->execute(
                    $data['ketuaUser'],
                    $data['dokumen'],
                    KeputusanHasilKonsultasi::ValidBersedia
                );
                $this->fail('Integritas file yang rusak dapat diverifikasi.');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('dokumen', $exception->errors());
            }

            $this->assertSame(
                StatusKesediaanBimbingan::MenungguVerifikasi,
                $data['kesediaan']->fresh()->status
            );
            $this->assertNull($data['dokumen']->fresh()->verified_by);
        }
    }

    /** @return array<string, mixed> */
    private function dataDokumen(): array
    {
        $programStudi = ProgramStudi::factory()->create();
        $ketuaUser = $this->kaprodiUntuk($programStudi);
        $mahasiswaUser = User::factory()->mahasiswa()->create();
        $mahasiswa = Mahasiswa::factory()->create([
            'program_studi_id' => $programStudi->id,
            'pembimbing_akademik_id' => $ketuaUser->dosen->nidn,
            'user_id' => $mahasiswaUser->id,
        ]);
        $pengajuan = PengajuanJudul::factory()->diverifikasi($ketuaUser->dosen)->create([
            'nim' => $mahasiswa->nim,
        ]);
        $skripsi = Skripsi::factory()->for($pengajuan, 'pengajuanJudul')->create([
            'nim' => $mahasiswa->nim,
            'judul' => $pengajuan->judul,
        ]);
        $calon = Dosen::factory()->create(['program_studi_id' => $programStudi->id]);
        $kesediaan = KesediaanBimbingan::factory()->for($skripsi)->create([
            'dosen_id' => $calon->nidn,
            'peran' => PeranKesediaanBimbingan::Pembimbing1,
            'status' => StatusKesediaanBimbingan::MenungguVerifikasi,
            'uploaded_at' => now(),
        ]);
        $dokumen = $this->buatDokumen($kesediaan, $mahasiswaUser);

        return compact(
            'programStudi',
            'ketuaUser',
            'mahasiswaUser',
            'pengajuan',
            'skripsi',
            'kesediaan',
            'dokumen'
        );
    }

    private function buatDokumen(
        KesediaanBimbingan $kesediaan,
        User $mahasiswa,
        int $versi = 1
    ): DokumenPengajuan {
        $isi = "%PDF-1.4\nhasil konsultasi versi {$versi}\n%%EOF";
        $path = "dokumen/testing/{$kesediaan->id}-{$versi}.pdf";
        Storage::disk('local')->put($path, $isi);

        return DokumenPengajuan::factory()->for($kesediaan, 'documentable')->create([
            'versi' => $versi,
            'file_path' => $path,
            'file_hash' => hash('sha256', $isi),
            'uploaded_by' => $mahasiswa->id,
        ]);
    }

    private function kaprodiUntuk(ProgramStudi $programStudi): User
    {
        $user = User::factory()->dosen()->create();
        $dosen = Dosen::factory()->create([
            'program_studi_id' => $programStudi->id,
            'user_id' => $user->id,
        ]);
        $programStudi->update(['ketua_prodi_id' => $dosen->nidn]);

        return $user->fresh('dosen');
    }
}
