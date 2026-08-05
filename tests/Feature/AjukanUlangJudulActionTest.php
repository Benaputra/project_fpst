<?php

namespace Tests\Feature;

use App\Actions\PengajuanJudul\AjukanUlangJudul;
use App\Enums\StatusPengajuanJudul;
use App\Models\Dosen;
use App\Models\Mahasiswa;
use App\Models\PengajuanJudul;
use App\Models\ProgramStudi;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AjukanUlangJudulActionTest extends TestCase
{
    use DatabaseTransactions;

    public function test_pemilik_memperbaiki_record_ditolak_tanpa_membuat_record_baru(): void
    {
        $data = $this->buatPengajuan(StatusPengajuanJudul::Ditolak);
        $idAwal = $data['pengajuan']->id;
        $nimAwal = $data['pengajuan']->nim;

        $hasil = app(AjukanUlangJudul::class)->execute(
            $data['user'],
            $data['pengajuan'],
            "  Judul\n   hasil   perbaikan  "
        );

        $this->assertSame($idAwal, $hasil->id);
        $this->assertSame($nimAwal, $hasil->nim);
        $this->assertSame('Judul hasil perbaikan', $hasil->judul);
        $this->assertSame(StatusPengajuanJudul::Diajukan, $hasil->status);
        $this->assertNull($hasil->catatan_reject);
        $this->assertNull($hasil->diverifikasi_oleh);
        $this->assertNull($hasil->diverifikasi_at);
        $this->assertDatabaseCount('pengajuan_judul', 1);
    }

    public function test_mahasiswa_lain_tidak_dapat_memperbaiki_pengajuan(): void
    {
        $data = $this->buatPengajuan(StatusPengajuanJudul::Ditolak);
        $userLain = User::factory()->mahasiswa()->create();
        Mahasiswa::factory()->create([
            'program_studi_id' => $data['programStudi']->id,
            'pembimbing_akademik_id' => $data['verifikator']->nidn,
            'user_id' => $userLain->id,
        ]);

        try {
            app(AjukanUlangJudul::class)->execute(
                $userLain,
                $data['pengajuan'],
                'Judul manipulasi'
            );
            $this->fail('Mahasiswa lain seharusnya ditolak.');
        } catch (AuthorizationException) {
            $data['pengajuan']->refresh();
            $this->assertSame(StatusPengajuanJudul::Ditolak, $data['pengajuan']->status);
            $this->assertSame('Judul awal', $data['pengajuan']->judul);
        }
    }

    public function test_pengajuan_diajukan_tidak_dapat_diedit(): void
    {
        $data = $this->buatPengajuan(StatusPengajuanJudul::Diajukan);

        $this->expectException(ValidationException::class);

        app(AjukanUlangJudul::class)->execute(
            $data['user'],
            $data['pengajuan'],
            'Judul yang tidak boleh disimpan'
        );
    }

    public function test_pengajuan_diverifikasi_tidak_dapat_diedit(): void
    {
        $data = $this->buatPengajuan(StatusPengajuanJudul::Diverifikasi);

        $this->expectException(ValidationException::class);

        app(AjukanUlangJudul::class)->execute(
            $data['user'],
            $data['pengajuan'],
            'Judul yang tidak boleh disimpan'
        );
    }

    public function test_eksekusi_ganda_hanya_mengubah_record_sekali(): void
    {
        $data = $this->buatPengajuan(StatusPengajuanJudul::Ditolak);
        $action = app(AjukanUlangJudul::class);
        $hasilPertama = $action->execute($data['user'], $data['pengajuan'], 'Judul baru');

        try {
            $action->execute($data['user'], $hasilPertama, 'Judul lain');
            $this->fail('Eksekusi kedua seharusnya ditolak.');
        } catch (ValidationException) {
            $hasilPertama->refresh();
            $this->assertSame('Judul baru', $hasilPertama->judul);
            $this->assertSame(StatusPengajuanJudul::Diajukan, $hasilPertama->status);
            $this->assertDatabaseCount('pengajuan_judul', 1);
        }
    }

    public function test_judul_kosong_ditolak_tanpa_mengubah_keputusan_lama(): void
    {
        $data = $this->buatPengajuan(StatusPengajuanJudul::Ditolak);

        try {
            app(AjukanUlangJudul::class)->execute($data['user'], $data['pengajuan'], " \t\n ");
            $this->fail('Judul kosong seharusnya ditolak.');
        } catch (ValidationException) {
            $data['pengajuan']->refresh();
            $this->assertSame(StatusPengajuanJudul::Ditolak, $data['pengajuan']->status);
            $this->assertSame('Perbaiki judul.', $data['pengajuan']->catatan_reject);
        }
    }

    /**
     * @return array{
     *     programStudi: ProgramStudi,
     *     verifikator: Dosen,
     *     user: User,
     *     mahasiswa: Mahasiswa,
     *     pengajuan: PengajuanJudul
     * }
     */
    private function buatPengajuan(StatusPengajuanJudul $status): array
    {
        $programStudi = ProgramStudi::factory()->create();
        $verifikator = Dosen::factory()->create([
            'program_studi_id' => $programStudi->id,
        ]);
        $user = User::factory()->mahasiswa()->create();
        $mahasiswa = Mahasiswa::factory()->create([
            'program_studi_id' => $programStudi->id,
            'pembimbing_akademik_id' => $verifikator->nidn,
            'user_id' => $user->id,
        ]);
        $pengajuan = PengajuanJudul::factory()->create([
            'nim' => $mahasiswa->nim,
            'judul' => 'Judul awal',
            'status' => $status,
            'catatan_reject' => $status === StatusPengajuanJudul::Ditolak
                ? 'Perbaiki judul.'
                : null,
            'diverifikasi_oleh' => $status === StatusPengajuanJudul::Diajukan
                ? null
                : $verifikator->nidn,
            'diverifikasi_at' => $status === StatusPengajuanJudul::Diajukan
                ? null
                : now(),
        ]);

        return compact('programStudi', 'verifikator', 'user', 'mahasiswa', 'pengajuan');
    }
}
