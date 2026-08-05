<?php

namespace Tests\Feature;

use App\Actions\PengajuanJudul\AjukanJudul;
use App\Enums\StatusPengajuanJudul;
use App\Models\Dosen;
use App\Models\Mahasiswa;
use App\Models\ProgramStudi;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AjukanJudulActionTest extends TestCase
{
    use DatabaseTransactions;

    public function test_mahasiswa_berhasil_mengajukan_judul_dengan_nim_dari_akun(): void
    {
        ['user' => $user, 'mahasiswa' => $mahasiswa] = $this->buatMahasiswaTerautentikasi();

        $pengajuan = app(AjukanJudul::class)->execute(
            $user,
            "  Analisis\n  Sistem   Administrasi\tSkripsi  "
        );

        $this->assertSame($mahasiswa->nim, $pengajuan->nim);
        $this->assertSame('Analisis Sistem Administrasi Skripsi', $pengajuan->judul);
        $this->assertSame(StatusPengajuanJudul::Diajukan, $pengajuan->status);
        $this->assertNull($pengajuan->catatan_reject);
        $this->assertNull($pengajuan->diverifikasi_oleh);
        $this->assertNull($pengajuan->diverifikasi_at);
        $this->assertDatabaseHas('pengajuan_judul', [
            'id' => $pengajuan->id,
            'nim' => $mahasiswa->nim,
            'status' => StatusPengajuanJudul::Diajukan->value,
        ]);
    }

    public function test_pengajuan_kedua_ditangani_sebagai_error_validasi_yang_aman(): void
    {
        ['user' => $user, 'mahasiswa' => $mahasiswa] = $this->buatMahasiswaTerautentikasi();
        $action = app(AjukanJudul::class);
        $action->execute($user, 'Judul pertama');

        try {
            $action->execute($user, 'Judul kedua');
            $this->fail('Pengajuan kedua seharusnya ditolak.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                ['Anda sudah memiliki pengajuan judul.'],
                $exception->errors()['judul']
            );
        }

        $this->assertDatabaseCount('pengajuan_judul', 1);
        $this->assertDatabaseMissing('pengajuan_judul', [
            'nim' => $mahasiswa->nim,
            'judul' => 'Judul kedua',
        ]);
    }

    public function test_judul_kosong_setelah_normalisasi_ditolak(): void
    {
        ['user' => $user] = $this->buatMahasiswaTerautentikasi();

        $this->expectException(ValidationException::class);

        app(AjukanJudul::class)->execute($user, " \n\t ");
    }

    public function test_role_lain_tidak_dapat_mengajukan_judul(): void
    {
        $user = User::factory()->dosen()->create();

        $this->expectException(AuthorizationException::class);

        app(AjukanJudul::class)->execute($user, 'Judul tidak sah');
    }

    public function test_akun_mahasiswa_tanpa_profil_tidak_dapat_mengajukan_judul(): void
    {
        $user = User::factory()->mahasiswa()->create();

        $this->expectException(AuthorizationException::class);

        app(AjukanJudul::class)->execute($user, 'Judul tanpa profil');
    }

    /**
     * @return array{user: User, mahasiswa: Mahasiswa}
     */
    private function buatMahasiswaTerautentikasi(): array
    {
        $programStudi = ProgramStudi::factory()->create();
        $dosen = Dosen::factory()->create([
            'program_studi_id' => $programStudi->id,
        ]);
        $user = User::factory()->mahasiswa()->create();
        $mahasiswa = Mahasiswa::factory()->create([
            'program_studi_id' => $programStudi->id,
            'pembimbing_akademik_id' => $dosen->nidn,
            'user_id' => $user->id,
        ]);

        return compact('user', 'mahasiswa');
    }
}
