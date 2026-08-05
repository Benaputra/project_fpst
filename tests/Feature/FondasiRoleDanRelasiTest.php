<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Dosen;
use App\Models\Mahasiswa;
use App\Models\ProgramStudi;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class FondasiRoleDanRelasiTest extends TestCase
{
    use DatabaseTransactions;

    public function test_database_testing_mysql_terisolasi_dan_skema_fondasi_lengkap(): void
    {
        $this->assertSame('mysql', DB::connection()->getDriverName());
        $this->assertSame('project_fpst_testing', DB::connection()->getDatabaseName());
        $this->assertTrue(Schema::hasColumns('users', ['role']));
        $this->assertTrue(Schema::hasColumns('program_studi', [
            'nama',
            'ketua_prodi_id',
            'ttd_ketua_prodi',
        ]));
        $this->assertTrue(Schema::hasColumns('dosen', ['nidn', 'program_studi_id', 'user_id']));
        $this->assertTrue(Schema::hasColumns('mahasiswa', [
            'nim',
            'program_studi_id',
            'pembimbing_akademik_id',
            'user_id',
        ]));
        $this->assertTrue(Schema::hasColumns('user_program_studi', [
            'user_id',
            'program_studi_id',
        ]));
    }

    public function test_role_user_dicasting_ke_enum_kanonis(): void
    {
        $user = User::factory()->adminProdi()->create();

        $this->assertSame(UserRole::AdminProdi, $user->role);
        $this->assertTrue($user->isAdminProdi());
        $this->assertFalse($user->isAdminUtama());
    }

    public function test_akun_mahasiswa_dan_dosen_terhubung_ke_profilnya(): void
    {
        $programStudi = ProgramStudi::factory()->create();
        $dosenUser = User::factory()->dosen()->create();
        $dosen = Dosen::factory()->create([
            'program_studi_id' => $programStudi->id,
            'user_id' => $dosenUser->id,
        ]);
        $mahasiswaUser = User::factory()->mahasiswa()->create();
        $mahasiswa = Mahasiswa::factory()->create([
            'program_studi_id' => $programStudi->id,
            'pembimbing_akademik_id' => $dosen->nidn,
            'user_id' => $mahasiswaUser->id,
        ]);

        $this->assertTrue($dosenUser->dosen->is($dosen));
        $this->assertTrue($mahasiswaUser->mahasiswa->is($mahasiswa));
        $this->assertTrue($mahasiswa->programStudi->is($programStudi));
        $this->assertTrue($mahasiswa->pembimbingAkademik->is($dosen));
    }

    public function test_ketua_prodi_ditentukan_oleh_relasi_bukan_role_dosen_saja(): void
    {
        $programStudi = ProgramStudi::factory()->create();
        $programStudiLain = ProgramStudi::factory()->create();
        $ketuaUser = User::factory()->dosen()->create();
        $ketua = Dosen::factory()->create([
            'program_studi_id' => $programStudi->id,
            'user_id' => $ketuaUser->id,
        ]);
        $dosenBiasaUser = User::factory()->dosen()->create();
        Dosen::factory()->create([
            'program_studi_id' => $programStudi->id,
            'user_id' => $dosenBiasaUser->id,
        ]);
        $programStudi->update(['ketua_prodi_id' => $ketua->nidn]);

        $this->assertTrue($ketuaUser->isKetuaProdiUntuk($programStudi));
        $this->assertFalse($ketuaUser->isKetuaProdiUntuk($programStudiLain));
        $this->assertFalse($dosenBiasaUser->isKetuaProdiUntuk($programStudi));
    }

    public function test_admin_prodi_hanya_memiliki_akses_ke_prodi_yang_dipetakan(): void
    {
        $programStudi = ProgramStudi::factory()->create();
        $programStudiLain = ProgramStudi::factory()->create();
        $adminProdi = User::factory()->adminProdi()->create();
        $adminProdi->programStudiAdministrasi()->attach($programStudi);

        $this->assertTrue($adminProdi->memilikiAksesAdministratifKeProgramStudi($programStudi));
        $this->assertFalse($adminProdi->memilikiAksesAdministratifKeProgramStudi($programStudiLain));
    }

    public function test_admin_utama_memiliki_akses_global_dan_dosen_biasa_tidak(): void
    {
        $programStudi = ProgramStudi::factory()->create();
        $programStudiLain = ProgramStudi::factory()->create();
        $adminUtama = User::factory()->adminUtama()->create();
        $dosenBiasa = User::factory()->dosen()->create();

        $this->assertTrue($adminUtama->memilikiAksesAdministratifKeProgramStudi($programStudi));
        $this->assertTrue($adminUtama->memilikiAksesAdministratifKeProgramStudi($programStudiLain));
        $this->assertFalse($dosenBiasa->memilikiAksesAdministratifKeProgramStudi($programStudi));
    }

    public function test_satu_akun_hanya_dapat_terhubung_ke_satu_dosen(): void
    {
        $programStudi = ProgramStudi::factory()->create();
        $user = User::factory()->dosen()->create();
        Dosen::factory()->create([
            'program_studi_id' => $programStudi->id,
            'user_id' => $user->id,
        ]);

        $this->expectException(QueryException::class);

        Dosen::factory()->create([
            'program_studi_id' => $programStudi->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_pemetaan_admin_prodi_tidak_dapat_diduplikasi(): void
    {
        $programStudi = ProgramStudi::factory()->create();
        $adminProdi = User::factory()->adminProdi()->create();
        $adminProdi->programStudiAdministrasi()->attach($programStudi);

        $this->expectException(QueryException::class);

        $adminProdi->programStudiAdministrasi()->attach($programStudi);
    }
}
