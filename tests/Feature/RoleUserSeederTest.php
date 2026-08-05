<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\RoleUserSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RoleUserSeederTest extends TestCase
{
    use DatabaseTransactions;

    public function test_seeder_membuat_beberapa_akun_lengkap_untuk_setiap_role(): void
    {
        $this->seed(RoleUserSeeder::class);

        foreach (UserRole::cases() as $role) {
            $this->assertGreaterThanOrEqual(
                3,
                User::query()->where('role', $role->value)->count(),
                "Role {$role->value} tidak memiliki cukup akun uji.",
            );
        }

        $mahasiswa = User::query()->where('email', 'mahasiswa.ti1@example.test')->firstOrFail();
        $kaprodi = User::query()->where('email', 'kaprodi.ti@example.test')->firstOrFail();
        $adminProdi = User::query()->where('email', 'admin.prodi.gabungan@example.test')->firstOrFail();

        $this->assertNotNull($mahasiswa->mahasiswa);
        $this->assertTrue($kaprodi->isKetuaProdi());
        $this->assertCount(2, $adminProdi->programStudiAdministrasi);
        $this->assertTrue(Hash::check('password', $mahasiswa->password));
    }

    public function test_seeder_dapat_dijalankan_ulang_tanpa_menduplikasi_akun(): void
    {
        $this->seed(RoleUserSeeder::class);
        $this->seed(RoleUserSeeder::class);

        $this->assertSame(
            1,
            User::query()->where('email', 'admin.utama@example.test')->count(),
        );
        $this->assertSame(
            1,
            User::query()->where('email', 'mahasiswa.ti1@example.test')->count(),
        );
    }
}
