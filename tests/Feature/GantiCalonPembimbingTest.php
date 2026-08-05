<?php

namespace Tests\Feature;

use App\Actions\Skripsi\GantiCalonPembimbing;
use App\Enums\HasilKesediaanBimbingan;
use App\Enums\JenisSurat;
use App\Enums\PeranKesediaanBimbingan;
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
use App\Services\Pdf\SuratKesediaanPdf;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Tests\TestCase;

class GantiCalonPembimbingTest extends TestCase
{
    use DatabaseTransactions;

    public function test_kaprodi_mengganti_hanya_posisi_ditolak_dan_menerbitkan_surat_baru(): void
    {
        Storage::fake('local');
        $data = $this->dataSiklus();
        $pengganti = Dosen::factory()->create([
            'program_studi_id' => $data['programStudi']->id,
        ]);
        $judulAwal = $data['pengajuan']->judul;

        $siklusBaru = app(GantiCalonPembimbing::class)->execute(
            $data['ketuaUser'],
            $data['ditolak'],
            $pengganti->nidn
        );

        $this->assertSame(PeranKesediaanBimbingan::Pembimbing1, $siklusBaru->peran);
        $this->assertSame(2, $siklusBaru->siklus);
        $this->assertSame($pengganti->nidn, $siklusBaru->dosen_id);
        $this->assertSame(StatusKesediaanBimbingan::MenungguUpload, $siklusBaru->status);
        $this->assertNull($siklusBaru->hasil);
        $this->assertSame(StatusKesediaanBimbingan::Ditolak, $data['ditolak']->fresh()->status);
        $this->assertSame(HasilKesediaanBimbingan::TidakBersedia, $data['ditolak']->fresh()->hasil);
        $this->assertSame(StatusKesediaanBimbingan::Diterima, $data['diterima']->fresh()->status);
        $this->assertSame(HasilKesediaanBimbingan::Bersedia, $data['diterima']->fresh()->hasil);
        $this->assertSame($judulAwal, $data['pengajuan']->fresh()->judul);
        $this->assertSame(StatusPengajuanJudul::Diverifikasi, $data['pengajuan']->fresh()->status);
        $this->assertNull($data['skripsi']->fresh()->pembimbing1_id);

        $surat = Surat::query()->whereMorphedTo('suratable', $siklusBaru)->sole();
        $this->assertSame(JenisSurat::KesediaanPembimbing, $surat->jenis_surat);
        $this->assertSame(StatusSurat::Diterbitkan, $surat->status);
        $this->assertStringContainsString('-P1-S02-', $surat->no_surat);
        Storage::disk('local')->assertExists($surat->file_path);
        $this->assertDatabaseMissing('surat', [
            'suratable_type' => Skripsi::class,
            'jenis_surat' => JenisSurat::SkBimbingan->value,
        ]);
    }

    public function test_hanya_kaprodi_terkait_yang_dapat_mengganti(): void
    {
        Storage::fake('local');
        $data = $this->dataSiklus();
        $pengganti = Dosen::factory()->create([
            'program_studi_id' => $data['programStudi']->id,
        ]);
        $adminProdi = User::factory()->adminProdi()->create();
        $adminProdi->programStudiAdministrasi()->attach($data['programStudi']);
        $prodiLain = ProgramStudi::factory()->create();
        $kaprodiLain = $this->kaprodiUntuk($prodiLain);

        foreach ([
            User::factory()->adminUtama()->create(),
            $adminProdi,
            $kaprodiLain,
            $data['mahasiswaUser'],
        ] as $user) {
            try {
                app(GantiCalonPembimbing::class)->execute(
                    $user,
                    $data['ditolak'],
                    $pengganti->nidn
                );
                $this->fail('Pengguna selain Kaprodi terkait dapat mengganti calon.');
            } catch (AuthorizationException) {
                $this->assertDatabaseCount('kesediaan_bimbingan', 2);
            }
        }
    }

    public function test_pengganti_harus_dosen_baru_dari_prodi_yang_sama(): void
    {
        Storage::fake('local');

        foreach (['lama', 'diterima', 'prodi_lain', 'tidak_ada'] as $kondisi) {
            $data = $this->dataSiklus();
            $calonId = match ($kondisi) {
                'lama' => $data['ditolak']->dosen_id,
                'diterima' => $data['diterima']->dosen_id,
                'prodi_lain' => Dosen::factory()->create()->nidn,
                default => '99999999999999999999',
            };

            try {
                app(GantiCalonPembimbing::class)->execute(
                    $data['ketuaUser'],
                    $data['ditolak'],
                    $calonId
                );
                $this->fail("Calon duplikat/tidak valid {$kondisi} diterima.");
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('calon_pengganti_id', $exception->errors());
            }

            $this->assertSame(1, KesediaanBimbingan::query()
                ->where('skripsi_id', $data['skripsi']->id)
                ->where('peran', PeranKesediaanBimbingan::Pembimbing1)
                ->count());
        }
    }

    public function test_hanya_penolakan_terbaru_yang_dapat_memulai_siklus(): void
    {
        Storage::fake('local');

        foreach ([
            [StatusKesediaanBimbingan::UploadTidakValid, null],
            [StatusKesediaanBimbingan::Diterima, HasilKesediaanBimbingan::Bersedia],
            [StatusKesediaanBimbingan::Ditolak, null],
        ] as [$status, $hasil]) {
            $data = $this->dataSiklus();
            $data['ditolak']->forceFill(['status' => $status, 'hasil' => $hasil])->save();
            $pengganti = Dosen::factory()->create([
                'program_studi_id' => $data['programStudi']->id,
            ]);

            try {
                app(GantiCalonPembimbing::class)->execute(
                    $data['ketuaUser'],
                    $data['ditolak'],
                    $pengganti->nidn
                );
                $this->fail('Status yang bukan penolakan valid dapat diganti.');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('kesediaan', $exception->errors());
            }
        }
    }

    public function test_siklus_aktif_mencegah_penggantian_ganda(): void
    {
        Storage::fake('local');
        $data = $this->dataSiklus();
        $pengganti1 = Dosen::factory()->create([
            'program_studi_id' => $data['programStudi']->id,
        ]);
        $pengganti2 = Dosen::factory()->create([
            'program_studi_id' => $data['programStudi']->id,
        ]);
        app(GantiCalonPembimbing::class)->execute(
            $data['ketuaUser'],
            $data['ditolak'],
            $pengganti1->nidn
        );

        try {
            app(GantiCalonPembimbing::class)->execute(
                $data['ketuaUser'],
                $data['ditolak'],
                $pengganti2->nidn
            );
            $this->fail('Dua siklus aktif dapat dibuat.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('kesediaan', $exception->errors());
        }

        $this->assertSame(2, KesediaanBimbingan::query()
            ->where('skripsi_id', $data['skripsi']->id)
            ->where('peran', PeranKesediaanBimbingan::Pembimbing1)
            ->count());
    }

    public function test_kegagalan_surat_merollback_siklus_dan_file(): void
    {
        Storage::fake('local');
        $data = $this->dataSiklus();
        $pengganti = Dosen::factory()->create([
            'program_studi_id' => $data['programStudi']->id,
        ]);
        $this->mock(SuratKesediaanPdf::class, function ($mock) {
            $mock->shouldReceive('render')->once()->andThrow(
                new RuntimeException('Simulasi PDF gagal.')
            );
        });

        try {
            app(GantiCalonPembimbing::class)->execute(
                $data['ketuaUser'],
                $data['ditolak'],
                $pengganti->nidn
            );
            $this->fail('Kegagalan penerbitan tidak dilempar.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Simulasi PDF gagal.', $exception->getMessage());
        }

        $this->assertDatabaseCount('kesediaan_bimbingan', 2);
        $this->assertDatabaseCount('surat', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
        $this->assertSame(StatusKesediaanBimbingan::Ditolak, $data['ditolak']->fresh()->status);
    }

    /** @return array<string, mixed> */
    private function dataSiklus(): array
    {
        $programStudi = ProgramStudi::factory()->create(['nama' => 'Prodi Siklus']);
        $ketuaUser = $this->kaprodiUntuk($programStudi);
        $mahasiswaUser = User::factory()->mahasiswa()->create();
        $mahasiswa = Mahasiswa::factory()->create([
            'program_studi_id' => $programStudi->id,
            'pembimbing_akademik_id' => $ketuaUser->dosen->nidn,
            'user_id' => $mahasiswaUser->id,
        ]);
        $pengajuan = PengajuanJudul::factory()->diverifikasi($ketuaUser->dosen)->create([
            'nim' => $mahasiswa->nim,
            'judul' => 'Judul Tetap Terverifikasi',
        ]);
        $skripsi = Skripsi::factory()->for($pengajuan, 'pengajuanJudul')->create([
            'nim' => $mahasiswa->nim,
            'judul' => $pengajuan->judul,
        ]);
        $ditolak = KesediaanBimbingan::factory()->for($skripsi)->create([
            'dosen_id' => Dosen::factory()->create([
                'program_studi_id' => $programStudi->id,
            ])->nidn,
            'peran' => PeranKesediaanBimbingan::Pembimbing1,
            'status' => StatusKesediaanBimbingan::Ditolak,
            'hasil' => HasilKesediaanBimbingan::TidakBersedia,
            'catatan_verifikasi' => 'Tidak bersedia.',
        ]);
        $diterima = KesediaanBimbingan::factory()->for($skripsi)->pembimbing2()->create([
            'dosen_id' => Dosen::factory()->create([
                'program_studi_id' => $programStudi->id,
            ])->nidn,
            'status' => StatusKesediaanBimbingan::Diterima,
            'hasil' => HasilKesediaanBimbingan::Bersedia,
        ]);

        return compact(
            'programStudi',
            'ketuaUser',
            'mahasiswaUser',
            'pengajuan',
            'skripsi',
            'ditolak',
            'diterima'
        );
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
