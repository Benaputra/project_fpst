<?php

namespace Tests\Feature;

use App\Actions\Skripsi\FinalisasiPembimbing;
use App\Enums\HasilKesediaanBimbingan;
use App\Enums\JenisSurat;
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
use App\Services\Skripsi\PenyimpanPembimbingFinal;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Tests\TestCase;

class FinalisasiPembimbingTest extends TestCase
{
    use DatabaseTransactions;

    public function test_kaprodi_memfinalkan_pembimbing_dan_mempertahankan_arsip_tanpa_sk(): void
    {
        $data = $this->dataSiapFinalisasi(denganPembimbing2: true, denganRiwayatDitolak: true);
        $jumlahRiwayat = $data['skripsi']->kesediaanBimbingan()->count();

        $hasil = app(FinalisasiPembimbing::class)->execute(
            $data['ketuaUser'],
            $data['skripsi']
        );

        $this->assertSame($data['pembimbing1']->dosen_id, $hasil->pembimbing1_id);
        $this->assertSame($data['pembimbing2']->dosen_id, $hasil->pembimbing2_id);
        $this->assertSame(StatusSkripsi::BimbinganAktif, $hasil->status);
        $this->assertSame($jumlahRiwayat, $hasil->kesediaanBimbingan()->count());
        $this->assertDatabaseHas('kesediaan_bimbingan', [
            'skripsi_id' => $hasil->id,
            'status' => StatusKesediaanBimbingan::Ditolak->value,
            'hasil' => HasilKesediaanBimbingan::TidakBersedia->value,
        ]);
        $this->assertDatabaseMissing('surat', [
            'suratable_type' => Skripsi::class,
            'suratable_id' => $hasil->id,
            'jenis_surat' => JenisSurat::SkBimbingan->value,
        ]);
        $this->assertDatabaseHas('aktivitas_log', [
            'subject_type' => Skripsi::class,
            'subject_id' => $hasil->id,
            'aksi' => 'pembimbing_difinalisasi',
        ]);
    }

    public function test_pembimbing2_opsional_tetapi_wajib_diterima_bila_pernah_ditunjuk(): void
    {
        $tanpaP2 = $this->dataSiapFinalisasi();
        $hasil = app(FinalisasiPembimbing::class)->execute(
            $tanpaP2['ketuaUser'],
            $tanpaP2['skripsi']
        );
        $this->assertNull($hasil->pembimbing2_id);

        $denganP2 = $this->dataSiapFinalisasi();
        KesediaanBimbingan::factory()->for($denganP2['skripsi'])->pembimbing2()->create([
            'dosen_id' => Dosen::factory()->create([
                'program_studi_id' => $denganP2['programStudi']->id,
            ])->nidn,
            'status' => StatusKesediaanBimbingan::Ditolak,
            'hasil' => HasilKesediaanBimbingan::TidakBersedia,
        ]);

        $this->expectException(ValidationException::class);
        app(FinalisasiPembimbing::class)->execute(
            $denganP2['ketuaUser'],
            $denganP2['skripsi']
        );
    }

    public function test_prasyarat_judul_dokumen_dan_proses_aktif_ditegakkan(): void
    {
        $kasus = ['judul', 'dokumen', 'proses_aktif'];

        foreach ($kasus as $kondisi) {
            $data = $this->dataSiapFinalisasi();
            match ($kondisi) {
                'judul' => $data['pengajuan']->forceFill([
                    'status' => StatusPengajuanJudul::Ditolak,
                ])->save(),
                'dokumen' => $data['dokumen1']->forceFill([
                    'status' => StatusDokumenPengajuan::Ditolak,
                ])->save(),
                'proses_aktif' => KesediaanBimbingan::factory()
                    ->for($data['skripsi'])
                    ->pembimbing2()
                    ->create([
                        'dosen_id' => Dosen::factory()->create([
                            'program_studi_id' => $data['programStudi']->id,
                        ])->nidn,
                        'status' => StatusKesediaanBimbingan::MenungguUpload,
                    ]),
            };

            try {
                app(FinalisasiPembimbing::class)->execute(
                    $data['ketuaUser'],
                    $data['skripsi']
                );
                $this->fail("Prasyarat {$kondisi} dapat dilewati.");
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('skripsi', $exception->errors());
            }

            $this->assertNull($data['skripsi']->fresh()->pembimbing1_id);
            $this->assertSame(
                StatusSkripsi::MenungguKesediaanPembimbing,
                $data['skripsi']->fresh()->status
            );
        }
    }

    public function test_hanya_kaprodi_terkait_dapat_memfinalkan(): void
    {
        $data = $this->dataSiapFinalisasi();
        $admin = User::factory()->adminUtama()->create();
        $adminProdi = User::factory()->adminProdi()->create();
        $adminProdi->programStudiAdministrasi()->attach($data['programStudi']);
        $kaprodiLain = $this->kaprodiUntuk(ProgramStudi::factory()->create());

        foreach ([$admin, $adminProdi, $kaprodiLain, $data['mahasiswaUser']] as $user) {
            try {
                app(FinalisasiPembimbing::class)->execute($user, $data['skripsi']);
                $this->fail('Pengguna selain Kaprodi terkait dapat memfinalkan.');
            } catch (AuthorizationException) {
                $this->assertNull($data['skripsi']->fresh()->pembimbing1_id);
            }
        }
    }

    public function test_eksekusi_ulang_idempoten_tanpa_mengubah_riwayat(): void
    {
        $data = $this->dataSiapFinalisasi(denganPembimbing2: true);
        $action = app(FinalisasiPembimbing::class);
        $pertama = $action->execute($data['ketuaUser'], $data['skripsi']);
        $jumlah = KesediaanBimbingan::query()->where('skripsi_id', $pertama->id)->count();
        $kedua = $action->execute($data['ketuaUser'], $pertama);

        $this->assertSame($pertama->pembimbing1_id, $kedua->pembimbing1_id);
        $this->assertSame($pertama->pembimbing2_id, $kedua->pembimbing2_id);
        $this->assertSame($jumlah, $kedua->kesediaanBimbingan()->count());
        $this->assertSame(StatusSkripsi::BimbinganAktif, $kedua->status);
    }

    public function test_kegagalan_penyimpanan_merollback_semua_field_final(): void
    {
        $data = $this->dataSiapFinalisasi();
        $this->mock(PenyimpanPembimbingFinal::class, function ($mock) {
            $mock->shouldReceive('execute')->once()->andReturnUsing(
                function (Skripsi $skripsi, string $pembimbing1Id): void {
                    $skripsi->forceFill(['pembimbing1_id' => $pembimbing1Id])->save();

                    throw new RuntimeException('Simulasi penyimpanan gagal.');
                }
            );
        });

        try {
            app(FinalisasiPembimbing::class)->execute(
                $data['ketuaUser'],
                $data['skripsi']
            );
            $this->fail('Kegagalan penyimpanan tidak diteruskan.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Simulasi penyimpanan gagal.', $exception->getMessage());
        }

        $skripsi = $data['skripsi']->fresh();
        $this->assertNull($skripsi->pembimbing1_id);
        $this->assertNull($skripsi->pembimbing2_id);
        $this->assertSame(StatusSkripsi::MenungguKesediaanPembimbing, $skripsi->status);
    }

    /** @return array<string, mixed> */
    private function dataSiapFinalisasi(
        bool $denganPembimbing2 = false,
        bool $denganRiwayatDitolak = false
    ): array {
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
        $verifikator = User::factory()->adminUtama()->create();
        if ($denganRiwayatDitolak) {
            KesediaanBimbingan::factory()->for($skripsi)->create([
                'dosen_id' => Dosen::factory()->create([
                    'program_studi_id' => $programStudi->id,
                ])->nidn,
                'status' => StatusKesediaanBimbingan::Ditolak,
                'hasil' => HasilKesediaanBimbingan::TidakBersedia,
            ]);
        }
        $pembimbing1 = $this->buatKesediaanDiterima(
            $skripsi,
            $programStudi,
            $verifikator,
            PeranKesediaanBimbingan::Pembimbing1,
            $denganRiwayatDitolak ? 2 : 1
        );
        $dokumen1 = $pembimbing1->dokumenPengajuan()->sole();
        $pembimbing2 = $denganPembimbing2
            ? $this->buatKesediaanDiterima(
                $skripsi,
                $programStudi,
                $verifikator,
                PeranKesediaanBimbingan::Pembimbing2
            )
            : null;

        return compact(
            'programStudi',
            'ketuaUser',
            'mahasiswaUser',
            'pengajuan',
            'skripsi',
            'pembimbing1',
            'pembimbing2',
            'dokumen1'
        );
    }

    private function buatKesediaanDiterima(
        Skripsi $skripsi,
        ProgramStudi $programStudi,
        User $verifikator,
        PeranKesediaanBimbingan $peran,
        int $siklus = 1
    ): KesediaanBimbingan {
        $kesediaan = KesediaanBimbingan::factory()->for($skripsi)->create([
            'dosen_id' => Dosen::factory()->create([
                'program_studi_id' => $programStudi->id,
            ])->nidn,
            'peran' => $peran,
            'siklus' => $siklus,
            'status' => StatusKesediaanBimbingan::Diterima,
            'hasil' => HasilKesediaanBimbingan::Bersedia,
            'diverifikasi_oleh' => $verifikator->id,
            'diverifikasi_at' => now(),
        ]);
        DokumenPengajuan::factory()->for($kesediaan, 'documentable')->create([
            'status' => StatusDokumenPengajuan::Terverifikasi,
            'verified_by' => $verifikator->id,
            'verified_at' => now(),
        ]);

        return $kesediaan;
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
