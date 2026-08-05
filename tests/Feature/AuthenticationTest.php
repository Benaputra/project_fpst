<?php

namespace Tests\Feature;

use App\Models\Dosen;
use App\Models\Mahasiswa;
use App\Models\ProgramStudi;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use DatabaseTransactions;

    protected function tearDown(): void
    {
        RateLimiter::clear('tidak-ada@example.com|127.0.0.1');

        parent::tearDown();
    }

    public function test_guest_dapat_membuka_halaman_login(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Masuk ke akun Anda')
            ->assertSee('name="email"', false)
            ->assertSee('name="password"', false);
    }

    public function test_mahasiswa_dapat_login_dan_diarahkan_ke_halaman_pengajuan(): void
    {
        $user = $this->buatMahasiswa();

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->get(route('dashboard'))
            ->assertRedirect(route('mahasiswa.pengajuan-judul.index'));
    }

    public function test_kaprodi_dapat_login_dan_diarahkan_ke_daftar_verifikasi(): void
    {
        $user = $this->buatKaprodi();

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->get(route('dashboard'))
            ->assertRedirect(route('kaprodi.pengajuan-judul.index'));
    }

    public function test_kredensial_salah_ditolak_dengan_pesan_generik(): void
    {
        $user = User::factory()->mahasiswa()->create();

        $this->from(route('login'))
            ->post(route('login.store'), [
                'email' => $user->email,
                'password' => 'kata-sandi-salah',
            ])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_login_dibatasi_setelah_lima_percobaan_gagal(): void
    {
        foreach (range(1, 5) as $percobaan) {
            $this->post(route('login.store'), [
                'email' => 'tidak-ada@example.com',
                'password' => 'salah-'.$percobaan,
            ])->assertSessionHasErrors('email');
        }

        $response = $this->post(route('login.store'), [
            'email' => 'tidak-ada@example.com',
            'password' => 'tetap-salah',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertStringContainsString(
            'Terlalu banyak percobaan login',
            session('errors')->first('email')
        );

        $this->assertGuest();
    }

    public function test_logout_mengakhiri_session_dan_meregenerasi_token(): void
    {
        $user = User::factory()->mahasiswa()->create();
        $this->actingAs($user);
        $tokenLama = session()->token();

        $this->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
        $this->assertNotSame($tokenLama, session()->token());
    }

    public function test_pengguna_yang_sudah_login_tidak_dapat_membuka_form_login(): void
    {
        $user = User::factory()->mahasiswa()->create();

        $this->actingAs($user)
            ->get(route('login'))
            ->assertRedirect(route('dashboard'));
    }

    private function buatMahasiswa(): User
    {
        $programStudi = ProgramStudi::factory()->create();
        $dosen = Dosen::factory()->create([
            'program_studi_id' => $programStudi->id,
        ]);
        $user = User::factory()->mahasiswa()->create();
        Mahasiswa::factory()->create([
            'program_studi_id' => $programStudi->id,
            'pembimbing_akademik_id' => $dosen->nidn,
            'user_id' => $user->id,
        ]);

        return $user;
    }

    private function buatKaprodi(): User
    {
        $programStudi = ProgramStudi::factory()->create();
        $user = User::factory()->dosen()->create();
        $dosen = Dosen::factory()->create([
            'program_studi_id' => $programStudi->id,
            'user_id' => $user->id,
        ]);
        $programStudi->update(['ketua_prodi_id' => $dosen->nidn]);

        return $user;
    }
}
