<?php

namespace Tests\Feature;

use App\Actions\Sidang\JadwalkanSidang;
use App\Enums\StatusSidangSkripsi;
use App\Models\Dosen;
use App\Models\Mahasiswa;
use App\Models\ProgramStudi;
use App\Models\SidangSkripsi;
use App\Models\Skripsi;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class JadwalkanSidangTest extends TestCase
{
    use DatabaseTransactions;

    public function test_jadwal_valid_idempoten_dan_tanpa_surat(): void
    {
        $d = $this->data();
        $admin = User::factory()->adminUtama()->create();
        $action = app(JadwalkanSidang::class);
        $tgl = Carbon::parse('2026-09-01 09:00');
        $a = $action->execute($admin, $d['sidang'], $d['p1']->nidn, $d['p2']->nidn, $tgl, 'Ruang Sidang');
        $b = $action->execute($admin, $a, $d['p1']->nidn, $d['p2']->nidn, $tgl, 'Ruang Sidang');
        $this->assertSame(StatusSidangSkripsi::Dijadwalkan, $b->status);
        $this->assertSame($a->id, $b->id);
        $this->assertDatabaseCount('surat', 0);
        $this->assertDatabaseHas('aktivitas_log', [
            'subject_type' => SidangSkripsi::class,
            'subject_id' => $b->id,
            'aksi' => 'sidang_dijadwalkan',
        ]);
    }

    public function test_status_duplikat_dan_lintas_prodi_ditolak(): void
    {
        foreach (['status', 'duplikat', 'prodi'] as $case) {
            $d = $this->data();
            if ($case === 'status') {
                $d['sidang']->forceFill(['status' => StatusSidangSkripsi::Diajukan])->save();
            }
            $p2 = $case === 'duplikat' ? $d['p1']->nidn : ($case === 'prodi' ? Dosen::factory()->create()->nidn : $d['p2']->nidn);
            try {
                app(JadwalkanSidang::class)->execute(User::factory()->adminUtama()->create(), $d['sidang'], $d['p1']->nidn, $p2, Carbon::now(), 'Ruang');
                $this->fail();
            } catch (ValidationException) {
                $this->assertNull($d['sidang']->fresh()->tanggal);
            }
        }
    }

    public function test_scope_salah_ditolak(): void
    {
        $d = $this->data();
        $admin = User::factory()->adminProdi()->create();
        $admin->programStudiAdministrasi()->attach(ProgramStudi::factory()->create());
        $this->expectException(AuthorizationException::class);
        app(JadwalkanSidang::class)->execute($admin, $d['sidang'], $d['p1']->nidn, $d['p2']->nidn, Carbon::now(), 'Ruang');
    }

    private function data(): array
    {
        $prodi = ProgramStudi::factory()->create();
        $pa = Dosen::factory()->create(['program_studi_id' => $prodi->id]);
        $m = Mahasiswa::factory()->create(['program_studi_id' => $prodi->id, 'pembimbing_akademik_id' => $pa->nidn]);
        $skripsi = Skripsi::factory()->create(['nim' => $m->nim]);
        $sidang = SidangSkripsi::factory()->for($skripsi)->create(['status' => StatusSidangSkripsi::Diverifikasi]);
        $p1 = Dosen::factory()->create(['program_studi_id' => $prodi->id]);
        $p2 = Dosen::factory()->create(['program_studi_id' => $prodi->id]);

        return compact('prodi', 'sidang', 'p1', 'p2');
    }
}
