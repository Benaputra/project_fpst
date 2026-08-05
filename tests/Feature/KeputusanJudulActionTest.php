<?php

namespace Tests\Feature;

use App\Actions\PengajuanJudul\TerimaJudul;
use App\Actions\PengajuanJudul\TolakJudul;
use App\Enums\StatusPengajuanJudul;
use App\Models\Dosen;
use App\Models\Mahasiswa;
use App\Models\PengajuanJudul;
use App\Models\ProgramStudi;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class KeputusanJudulActionTest extends TestCase
{
    use DatabaseTransactions;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_kaprodi_terkait_menerima_judul_dengan_identitas_dan_waktu_server(): void
    {
        Carbon::setTestNow('2026-08-04 21:30:00');
        $data = $this->buatPengajuan();

        $hasil = app(TerimaJudul::class)->execute($data['ketuaUser'], $data['pengajuan']);

        $this->assertSame(StatusPengajuanJudul::Diverifikasi, $hasil->status);
        $this->assertSame($data['ketua']->nidn, $hasil->diverifikasi_oleh);
        $this->assertTrue($hasil->diverifikasi_at->equalTo(now()));
        $this->assertNull($hasil->catatan_reject);
        $this->assertDatabaseCount('skripsi', 0);
    }

    public function test_kaprodi_terkait_menolak_judul_dengan_alasan_wajib(): void
    {
        Carbon::setTestNow('2026-08-04 21:45:00');
        $data = $this->buatPengajuan();

        $hasil = app(TolakJudul::class)->execute(
            $data['ketuaUser'],
            $data['pengajuan'],
            "  Topik\n terlalu   luas. "
        );

        $this->assertSame(StatusPengajuanJudul::Ditolak, $hasil->status);
        $this->assertSame('Topik terlalu luas.', $hasil->catatan_reject);
        $this->assertSame($data['ketua']->nidn, $hasil->diverifikasi_oleh);
        $this->assertTrue($hasil->diverifikasi_at->equalTo(now()));
    }

    public function test_alasan_penolakan_kosong_ditolak_tanpa_mengubah_pengajuan(): void
    {
        $data = $this->buatPengajuan();

        try {
            app(TolakJudul::class)->execute($data['ketuaUser'], $data['pengajuan'], " \n\t ");
            $this->fail('Alasan kosong seharusnya ditolak.');
        } catch (ValidationException) {
            $data['pengajuan']->refresh();
            $this->assertSame(StatusPengajuanJudul::Diajukan, $data['pengajuan']->status);
            $this->assertNull($data['pengajuan']->diverifikasi_oleh);
        }
    }

    public function test_kaprodi_prodi_lain_tidak_dapat_membuat_keputusan(): void
    {
        $data = $this->buatPengajuan();
        $prodiLain = ProgramStudi::factory()->create();
        $userKaprodiLain = User::factory()->dosen()->create();
        $kaprodiLain = Dosen::factory()->create([
            'program_studi_id' => $prodiLain->id,
            'user_id' => $userKaprodiLain->id,
        ]);
        $prodiLain->update(['ketua_prodi_id' => $kaprodiLain->nidn]);

        $this->expectException(AuthorizationException::class);

        app(TerimaJudul::class)->execute($userKaprodiLain, $data['pengajuan']);
    }

    public function test_dosen_biasa_tidak_dapat_membuat_keputusan(): void
    {
        $data = $this->buatPengajuan();
        $userDosen = User::factory()->dosen()->create();
        Dosen::factory()->create([
            'program_studi_id' => $data['programStudi']->id,
            'user_id' => $userDosen->id,
        ]);

        $this->expectException(AuthorizationException::class);

        app(TolakJudul::class)->execute($userDosen, $data['pengajuan'], 'Tidak sah');
    }

    public function test_status_selain_diajukan_tidak_dapat_diputuskan_lagi(): void
    {
        $data = $this->buatPengajuan(StatusPengajuanJudul::Ditolak);

        $this->expectException(ValidationException::class);

        app(TerimaJudul::class)->execute($data['ketuaUser'], $data['pengajuan']);
    }

    public function test_dua_keputusan_tidak_saling_menimpa(): void
    {
        $data = $this->buatPengajuan();
        $diterima = app(TerimaJudul::class)->execute($data['ketuaUser'], $data['pengajuan']);

        try {
            app(TolakJudul::class)->execute($data['ketuaUser'], $diterima, 'Keputusan kedua');
            $this->fail('Keputusan kedua seharusnya ditolak.');
        } catch (ValidationException) {
            $diterima->refresh();
            $this->assertSame(StatusPengajuanJudul::Diverifikasi, $diterima->status);
            $this->assertNull($diterima->catatan_reject);
            $this->assertSame($data['ketua']->nidn, $diterima->diverifikasi_oleh);
        }
    }

    /**
     * @return array{
     *     programStudi: ProgramStudi,
     *     ketuaUser: User,
     *     ketua: Dosen,
     *     mahasiswa: Mahasiswa,
     *     pengajuan: PengajuanJudul
     * }
     */
    private function buatPengajuan(
        StatusPengajuanJudul $status = StatusPengajuanJudul::Diajukan
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
            'catatan_reject' => $status === StatusPengajuanJudul::Ditolak
                ? 'Keputusan sebelumnya.'
                : null,
            'diverifikasi_oleh' => $status === StatusPengajuanJudul::Diajukan
                ? null
                : $ketua->nidn,
            'diverifikasi_at' => $status === StatusPengajuanJudul::Diajukan
                ? null
                : now(),
        ]);

        return compact('programStudi', 'ketuaUser', 'ketua', 'mahasiswa', 'pengajuan');
    }
}
