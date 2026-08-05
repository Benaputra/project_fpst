<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleUserSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PortalRoleUiTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleUserSeeder::class);
    }

    public function test_guest_dialihkan_dari_semua_halaman_portal(): void
    {
        foreach (['dashboard', 'portal.pengajuan-judul.index', 'portal.seminar.index', 'portal.skripsi.index', 'portal.sidang.index'] as $route) {
            $this->get(route($route))->assertRedirect(route('login'));
        }
    }

    public function test_dashboard_mahasiswa_memiliki_menu_pribadi_tanpa_menu_admin(): void
    {
        $response = $this->actingAs($this->user('mahasiswa.ti1@example.test'))->get(route('dashboard'));

        $response->assertOk()
            ->assertSee('Halo, Andi Saputra')
            ->assertSee('Pengajuan Judul')
            ->assertSee('Seminar')
            ->assertSee('Skripsi')
            ->assertSee('Sidang')
            ->assertSee('Profil')
            ->assertDontSee('Log Aktivitas')
            ->assertDontSee('Arsip Surat');
    }

    public function test_dashboard_dosen_biasa_hanya_memiliki_menu_akademik(): void
    {
        $response = $this->actingAs($this->user('dosen@example.test'))->get(route('dashboard'));

        $response->assertOk()
            ->assertSee('Dashboard Dosen')
            ->assertSee('Pengajuan Judul')
            ->assertSee('Seminar')
            ->assertSee('Skripsi')
            ->assertSee('Sidang')
            ->assertDontSee('Log Aktivitas')
            ->assertDontSee('Arsip Surat')
            ->assertDontSee('Profil');
    }

    public function test_dashboard_kaprodi_memiliki_menu_surat_tanpa_log_global(): void
    {
        $response = $this->actingAs($this->user('kaprodi.ti@example.test'))->get(route('dashboard'));

        $response->assertOk()
            ->assertSee('Teknik Informatika')
            ->assertSee('Surat')
            ->assertDontSee('Log Aktivitas');
    }

    public function test_dashboard_admin_prodi_dibatasi_prodi_dan_memiliki_menu_surat(): void
    {
        $response = $this->actingAs($this->user('admin.prodi.ti@example.test'))->get(route('dashboard'));

        $response->assertOk()
            ->assertSee('Dashboard Administrasi Prodi')
            ->assertSee('Teknik Informatika')
            ->assertDontSee('Sistem Informasi')
            ->assertSee('Surat')
            ->assertDontSee('Log Aktivitas');
    }

    public function test_dashboard_admin_utama_memiliki_menu_global_surat_dan_log(): void
    {
        $response = $this->actingAs($this->user('admin.utama@example.test'))->get(route('dashboard'));

        $response->assertOk()
            ->assertSee('Dashboard Sistem')
            ->assertSee('Surat')
            ->assertSee('Log Aktivitas');
    }

    public function test_hak_akses_halaman_khusus_role_ditegakkan(): void
    {
        $mahasiswa = $this->user('mahasiswa.ti1@example.test');
        $adminProdi = $this->user('admin.prodi.ti@example.test');
        $adminUtama = $this->user('admin.utama@example.test');

        $this->actingAs($mahasiswa)->get(route('portal.profile.show'))->assertOk();
        $this->actingAs($adminProdi)->get(route('portal.profile.show'))->assertForbidden();
        $this->actingAs($mahasiswa)->get(route('portal.surat.index'))->assertForbidden();
        $this->actingAs($adminProdi)->get(route('portal.surat.index'))->assertOk();
        $this->actingAs($adminProdi)->get(route('portal.aktivitas-log.index'))->assertForbidden();
        $this->actingAs($adminUtama)->get(route('portal.aktivitas-log.index'))->assertOk();
    }

    public function test_semua_role_dapat_membuka_menu_umum_tanpa_error(): void
    {
        foreach ([
            'mahasiswa.ti1@example.test',
            'dosen@example.test',
            'kaprodi.ti@example.test',
            'admin.prodi.ti@example.test',
            'admin.utama@example.test',
        ] as $email) {
            $user = $this->user($email);
            foreach (['portal.pengajuan-judul.index', 'portal.seminar.index', 'portal.skripsi.index', 'portal.sidang.index'] as $route) {
                $response = $this->actingAs($user)->get(route($route));

                if ($route === 'portal.pengajuan-judul.index' && $user->isMahasiswa()) {
                    $response->assertRedirect(route('mahasiswa.pengajuan-judul.index'));
                } elseif ($route === 'portal.pengajuan-judul.index' && $user->isKetuaProdi()) {
                    $response->assertRedirect(route('kaprodi.pengajuan-judul.index'));
                } else {
                    $response->assertOk();
                }
            }
        }
    }

    private function user(string $email): User
    {
        return User::query()->where('email', $email)->firstOrFail();
    }
}
