<?php

namespace Tests\Feature;

use App\Actions\Seminar\JadwalkanSeminar;
use App\Enums\StatusSeminar;
use App\Models\Dosen;
use App\Models\Mahasiswa;
use App\Models\ProgramStudi;
use App\Models\Seminar;
use App\Models\Skripsi;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class JadwalkanSeminarTest extends TestCase
{
    use DatabaseTransactions;

    public function test_admin_scope_menjadwalkan_dengan_dua_penguji_prodi(): void
    {
        $data = $this->dataSeminar();
        $admin = User::factory()->adminProdi()->create();
        $admin->programStudiAdministrasi()->attach($data['programStudi']);
        $hasil = app(JadwalkanSeminar::class)->execute(
            $admin, $data['seminar'], $data['penguji1']->nidn, $data['penguji2']->nidn,
            Carbon::parse('2026-08-20 09:00'), 'Ruang Sidang A'
        );

        $this->assertSame(StatusSeminar::Dijadwalkan, $hasil->status);
        $this->assertSame('Ruang Sidang A', $hasil->tempat);
        $this->assertSame('2026-08-20 09:00', $hasil->tanggal->format('Y-m-d H:i'));
        $this->assertDatabaseCount('surat', 0);
        $this->assertDatabaseHas('aktivitas_log', [
            'subject_type' => Seminar::class,
            'subject_id' => $hasil->id,
            'aksi' => 'seminar_dijadwalkan',
        ]);
    }

    public function test_status_penguji_duplikat_dan_lintas_prodi_ditolak(): void
    {
        foreach (['status', 'duplikat', 'lintas_prodi'] as $kasus) {
            $data = $this->dataSeminar();
            $p1 = $data['penguji1']->nidn;
            $p2 = $kasus === 'duplikat' ? $p1 : $data['penguji2']->nidn;
            if ($kasus === 'lintas_prodi') {
                $p2 = Dosen::factory()->create()->nidn;
            }
            if ($kasus === 'status') {
                $data['seminar']->forceFill(['status' => StatusSeminar::Diajukan])->save();
            }
            try {
                app(JadwalkanSeminar::class)->execute(
                    User::factory()->adminUtama()->create(), $data['seminar'], $p1, $p2,
                    Carbon::parse('2026-08-20'), 'Ruang A'
                );
                $this->fail("Kasus {$kasus} diterima.");
            } catch (ValidationException) {
                $this->assertNull($data['seminar']->fresh()->tanggal);
            }
        }
    }

    public function test_scope_dan_status_selesai_tidak_dapat_diubah(): void
    {
        $data = $this->dataSeminar();
        $adminLain = User::factory()->adminProdi()->create();
        $adminLain->programStudiAdministrasi()->attach(ProgramStudi::factory()->create());
        try {
            app(JadwalkanSeminar::class)->execute(
                $adminLain, $data['seminar'], $data['penguji1']->nidn, $data['penguji2']->nidn,
                Carbon::parse('2026-08-20'), 'Ruang A'
            );
            $this->fail('Admin lintas prodi diterima.');
        } catch (AuthorizationException) {
            $this->assertNull($data['seminar']->fresh()->tanggal);
        }
        $data['seminar']->forceFill(['status' => StatusSeminar::Selesai])->save();
        $this->expectException(ValidationException::class);
        app(JadwalkanSeminar::class)->execute(
            User::factory()->adminUtama()->create(), $data['seminar'],
            $data['penguji1']->nidn, $data['penguji2']->nidn,
            Carbon::parse('2026-08-20'), 'Ruang A'
        );
    }

    public function test_double_submit_identik_idempoten_dan_request_tidak_bisa_set_status(): void
    {
        $data = $this->dataSeminar();
        $admin = User::factory()->adminUtama()->create();
        $tanggal = Carbon::parse('2026-08-20 09:00');
        $action = app(JadwalkanSeminar::class);
        $pertama = $action->execute($admin, $data['seminar'], $data['penguji1']->nidn, $data['penguji2']->nidn, $tanggal, 'Ruang A');
        $kedua = $action->execute($admin, $pertama, $data['penguji1']->nidn, $data['penguji2']->nidn, $tanggal, 'Ruang A');
        $this->assertSame($pertama->id, $kedua->id);

        $data2 = $this->dataSeminar();
        $this->actingAs($admin)->post(route('seminar.jadwal.store', $data2['seminar']), [
            'penguji1_id' => $data2['penguji1']->nidn,
            'penguji2_id' => $data2['penguji2']->nidn,
            'tanggal' => '2026-08-21 10:00',
            'tempat' => 'Ruang B',
            'status' => 'selesai',
        ])->assertRedirect();
        $this->assertSame(StatusSeminar::Dijadwalkan, $data2['seminar']->fresh()->status);
    }

    /** @return array<string, mixed> */
    private function dataSeminar(): array
    {
        $programStudi = ProgramStudi::factory()->create();
        $pa = Dosen::factory()->create(['program_studi_id' => $programStudi->id]);
        $mahasiswa = Mahasiswa::factory()->create([
            'program_studi_id' => $programStudi->id,
            'pembimbing_akademik_id' => $pa->nidn,
        ]);
        $skripsi = Skripsi::factory()->create(['nim' => $mahasiswa->nim]);
        $seminar = Seminar::factory()->for($skripsi)->create([
            'status' => StatusSeminar::Diverifikasi,
            'verified_by' => User::factory()->adminUtama(),
            'verified_at' => now(),
        ]);
        $penguji1 = Dosen::factory()->create(['program_studi_id' => $programStudi->id]);
        $penguji2 = Dosen::factory()->create(['program_studi_id' => $programStudi->id]);

        return compact('programStudi', 'seminar', 'penguji1', 'penguji2');
    }
}
