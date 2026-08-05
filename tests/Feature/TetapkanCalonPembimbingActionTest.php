<?php

namespace Tests\Feature;

use App\Actions\Skripsi\TetapkanCalonPembimbing;
use App\Enums\PeranKesediaanBimbingan;
use App\Enums\StatusKesediaanBimbingan;
use App\Enums\StatusPengajuanJudul;
use App\Enums\StatusSkripsi;
use App\Models\Dosen;
use App\Models\KesediaanBimbingan;
use App\Models\Mahasiswa;
use App\Models\PengajuanJudul;
use App\Models\ProgramStudi;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Tests\TestCase;

class TetapkanCalonPembimbingActionTest extends TestCase
{
    use DatabaseTransactions;

    public function test_kaprodi_terkait_menetapkan_p1_dan_p2_tanpa_memfinalkan_pembimbing(): void
    {
        $data = $this->dataAkademik();

        $skripsi = app(TetapkanCalonPembimbing::class)->execute(
            $data['ketuaUser'],
            $data['pengajuan'],
            $data['calon1']->nidn,
            $data['calon2']->nidn
        );

        $this->assertSame($data['pengajuan']->id, $skripsi->pengajuan_judul_id);
        $this->assertSame($data['mahasiswa']->nim, $skripsi->nim);
        $this->assertSame($data['pengajuan']->judul, $skripsi->judul);
        $this->assertSame(StatusSkripsi::MenungguKesediaanPembimbing, $skripsi->status);
        $this->assertNull($skripsi->pembimbing1_id);
        $this->assertNull($skripsi->pembimbing2_id);
        $this->assertCount(2, $skripsi->kesediaanBimbingan);
        $this->assertDatabaseHas('kesediaan_bimbingan', [
            'skripsi_id' => $skripsi->id,
            'dosen_id' => $data['calon1']->nidn,
            'peran' => PeranKesediaanBimbingan::Pembimbing1->value,
            'siklus' => 1,
            'status' => StatusKesediaanBimbingan::Ditunjuk->value,
        ]);
        $this->assertDatabaseHas('kesediaan_bimbingan', [
            'skripsi_id' => $skripsi->id,
            'dosen_id' => $data['calon2']->nidn,
            'peran' => PeranKesediaanBimbingan::Pembimbing2->value,
            'siklus' => 1,
        ]);
    }

    public function test_p2_opsional_sesuai_nullable_kontrak(): void
    {
        $data = $this->dataAkademik();

        $skripsi = app(TetapkanCalonPembimbing::class)->execute(
            $data['ketuaUser'],
            $data['pengajuan'],
            $data['calon1']->nidn
        );

        $this->assertCount(1, $skripsi->kesediaanBimbingan);
        $this->assertSame(
            PeranKesediaanBimbingan::Pembimbing1,
            $skripsi->kesediaanBimbingan->sole()->peran
        );
    }

    public function test_hanya_judul_terverifikasi_yang_dapat_diproses(): void
    {
        $data = $this->dataAkademik(StatusPengajuanJudul::Diajukan);

        try {
            app(TetapkanCalonPembimbing::class)->execute(
                $data['ketuaUser'],
                $data['pengajuan'],
                $data['calon1']->nidn
            );
            $this->fail('Pengajuan yang belum diverifikasi dapat diproses.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('pengajuan', $exception->errors());
        }

        $this->assertDatabaseCount('skripsi', 0);
        $this->assertDatabaseCount('kesediaan_bimbingan', 0);
    }

    public function test_calon_harus_berbeda_dan_berasal_dari_prodi_mahasiswa(): void
    {
        $data = $this->dataAkademik();

        try {
            app(TetapkanCalonPembimbing::class)->execute(
                $data['ketuaUser'],
                $data['pengajuan'],
                $data['calon1']->nidn,
                $data['calon1']->nidn
            );
            $this->fail('Calon yang sama dapat dipilih dua kali.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('pembimbing2_id', $exception->errors());
        }

        $dosenProdiLain = Dosen::factory()->create();
        try {
            app(TetapkanCalonPembimbing::class)->execute(
                $data['ketuaUser'],
                $data['pengajuan'],
                $dosenProdiLain->nidn
            );
            $this->fail('Dosen lintas prodi dapat dipilih.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('pembimbing1_id', $exception->errors());
        }

        $this->assertDatabaseCount('skripsi', 0);
    }

    public function test_kaprodi_lintas_prodi_dan_dosen_biasa_ditolak(): void
    {
        $data = $this->dataAkademik();
        $prodiLain = ProgramStudi::factory()->create();
        $kaprodiLainUser = User::factory()->dosen()->create();
        $kaprodiLain = Dosen::factory()->create([
            'program_studi_id' => $prodiLain->id,
            'user_id' => $kaprodiLainUser->id,
        ]);
        $prodiLain->update(['ketua_prodi_id' => $kaprodiLain->nidn]);
        $dosenBiasaUser = User::factory()->dosen()->create();
        Dosen::factory()->create([
            'program_studi_id' => $data['programStudi']->id,
            'user_id' => $dosenBiasaUser->id,
        ]);

        foreach ([$kaprodiLainUser, $dosenBiasaUser] as $user) {
            try {
                app(TetapkanCalonPembimbing::class)->execute(
                    $user,
                    $data['pengajuan'],
                    $data['calon1']->nidn
                );
                $this->fail('Pengguna tanpa kewenangan dapat menetapkan calon.');
            } catch (AuthorizationException) {
                $this->assertDatabaseCount('skripsi', 0);
            }
        }
    }

    public function test_double_submit_tidak_membuat_skripsi_atau_calon_ganda(): void
    {
        $data = $this->dataAkademik();
        $action = app(TetapkanCalonPembimbing::class);
        $action->execute(
            $data['ketuaUser'],
            $data['pengajuan'],
            $data['calon1']->nidn,
            $data['calon2']->nidn
        );

        try {
            $action->execute(
                $data['ketuaUser'],
                $data['pengajuan'],
                $data['calon1']->nidn,
                $data['calon2']->nidn
            );
            $this->fail('Pengajuan yang sama diproses dua kali.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('pengajuan', $exception->errors());
        }

        $this->assertDatabaseCount('skripsi', 1);
        $this->assertDatabaseCount('kesediaan_bimbingan', 2);
    }

    public function test_transaksi_rollback_bila_pembuatan_salah_satu_calon_gagal(): void
    {
        $data = $this->dataAkademik();
        $event = 'eloquent.creating: '.KesediaanBimbingan::class;
        Event::listen($event, function (KesediaanBimbingan $kesediaan): void {
            if ($kesediaan->peran === PeranKesediaanBimbingan::Pembimbing2) {
                throw new RuntimeException('Simulasi kegagalan calon kedua.');
            }
        });

        try {
            app(TetapkanCalonPembimbing::class)->execute(
                $data['ketuaUser'],
                $data['pengajuan'],
                $data['calon1']->nidn,
                $data['calon2']->nidn
            );
            $this->fail('Kegagalan calon kedua tidak dilemparkan.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Simulasi kegagalan calon kedua.', $exception->getMessage());
        } finally {
            Event::forget($event);
        }

        $this->assertDatabaseCount('skripsi', 0);
        $this->assertDatabaseCount('kesediaan_bimbingan', 0);
    }

    /**
     * @return array{
     *   programStudi: ProgramStudi,
     *   ketuaUser: User,
     *   mahasiswa: Mahasiswa,
     *   pengajuan: PengajuanJudul,
     *   calon1: Dosen,
     *   calon2: Dosen
     * }
     */
    private function dataAkademik(
        StatusPengajuanJudul $status = StatusPengajuanJudul::Diverifikasi
    ): array {
        $programStudi = ProgramStudi::factory()->create();
        $ketuaUser = User::factory()->dosen()->create();
        $ketua = Dosen::factory()->create([
            'program_studi_id' => $programStudi->id,
            'user_id' => $ketuaUser->id,
        ]);
        $programStudi->update(['ketua_prodi_id' => $ketua->nidn]);
        $mahasiswa = Mahasiswa::factory()->create([
            'program_studi_id' => $programStudi->id,
            'pembimbing_akademik_id' => $ketua->nidn,
        ]);
        $pengajuan = PengajuanJudul::factory()->create([
            'nim' => $mahasiswa->nim,
            'status' => $status,
            'diverifikasi_oleh' => $status === StatusPengajuanJudul::Diverifikasi
                ? $ketua->nidn
                : null,
            'diverifikasi_at' => $status === StatusPengajuanJudul::Diverifikasi
                ? now()
                : null,
        ]);
        $calon1 = Dosen::factory()->create(['program_studi_id' => $programStudi->id]);
        $calon2 = Dosen::factory()->create(['program_studi_id' => $programStudi->id]);

        return compact(
            'programStudi',
            'ketuaUser',
            'mahasiswa',
            'pengajuan',
            'calon1',
            'calon2'
        );
    }
}
