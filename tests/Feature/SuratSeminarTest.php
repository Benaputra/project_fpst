<?php

namespace Tests\Feature;

use App\Actions\Surat\TerbitkanSuratSeminar;
use App\Enums\JenisSurat;
use App\Enums\StatusSeminar;
use App\Enums\StatusSurat;
use App\Models\Dosen;
use App\Models\Mahasiswa;
use App\Models\ProgramStudi;
use App\Models\Seminar;
use App\Models\Skripsi;
use App\Models\User;
use App\Services\Pdf\SuratSeminarPdf;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SuratSeminarTest extends TestCase
{
    use DatabaseTransactions;

    public function test_admin_menerbitkan_dua_jenis_disetujui_tanpa_signature(): void
    {
        Storage::fake('local');
        $data = $this->dataSeminar();
        $admin = User::factory()->adminProdi()->create();
        $admin->programStudiAdministrasi()->attach($data['programStudi']);
        $action = app(TerbitkanSuratSeminar::class);

        foreach ([JenisSurat::UndanganSeminar, JenisSurat::SuratTugasSeminar] as $jenis) {
            $surat = $action->execute($admin, $data['seminar'], $jenis);
            $this->assertSame($jenis, $surat->jenis_surat);
            $this->assertSame(StatusSurat::Terverifikasi, $surat->status);
            $this->assertNull($surat->signed_by);
            $this->assertSame($admin->id, $surat->verified_by);
            Storage::disk('local')->assertExists($surat->file_path);
        }
    }

    public function test_kaprodi_memakai_ttd_privat_dan_admin_utama_tetap_unsigned(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $signed = $this->dataSeminar(denganTtd: true);
        $suratSigned = app(TerbitkanSuratSeminar::class)->execute(
            $signed['ketuaUser'], $signed['seminar'], JenisSurat::UndanganSeminar
        );
        $this->assertSame($signed['ketua']->nidn, $suratSigned->signed_by);
        $this->assertNotNull($suratSigned->signed_at);
        Storage::disk('public')->assertMissing($signed['programStudi']->ttd_ketua_prodi);

        $unsigned = $this->dataSeminar(denganTtd: true);
        $suratUnsigned = app(TerbitkanSuratSeminar::class)->execute(
            User::factory()->adminUtama()->create(), $unsigned['seminar'], JenisSurat::UndanganSeminar
        );
        $this->assertNull($suratUnsigned->signed_by);
    }

    public function test_sumber_pdf_dari_jadwal_penguji_database_dan_versi_immutable(): void
    {
        Storage::fake('local');
        $data = $this->dataSeminar();
        $html = app(SuratSeminarPdf::class)->renderHtml(
            $data['seminar'], JenisSurat::SuratTugasSeminar, 'TSM-TEST', now(), null, null
        );
        foreach ([$data['seminar']->tempat, $data['penguji1']->nama, $data['penguji2']->nama, $data['skripsi']->judul, 'TERVERIFIKASI TANPA TANDA TANGAN KAPRODI'] as $teks) {
            $this->assertStringContainsString($teks, $html);
        }

        $admin = User::factory()->adminUtama()->create();
        $action = app(TerbitkanSuratSeminar::class);
        $v1 = $action->execute($admin, $data['seminar'], JenisSurat::UndanganSeminar);
        $isi1 = Storage::disk('local')->get($v1->file_path);
        $v2 = $action->execute($admin, $data['seminar'], JenisSurat::UndanganSeminar);
        $this->assertSame(StatusSurat::Dibatalkan, $v1->fresh()->status);
        $this->assertSame(2, $v2->versi);
        $this->assertSame($isi1, Storage::disk('local')->get($v1->file_path));
    }

    public function test_status_role_jenis_dan_download_scope_ditegakkan(): void
    {
        Storage::fake('local');
        $data = $this->dataSeminar();
        $adminLain = User::factory()->adminProdi()->create();
        $adminLain->programStudiAdministrasi()->attach(ProgramStudi::factory()->create());
        try {
            app(TerbitkanSuratSeminar::class)->execute($adminLain, $data['seminar'], JenisSurat::UndanganSeminar);
            $this->fail('Admin lintas prodi dapat menerbitkan.');
        } catch (AuthorizationException) {
            $this->assertDatabaseCount('surat', 0);
        }
        $data['seminar']->forceFill(['status' => StatusSeminar::Diverifikasi])->save();
        $this->expectException(ValidationException::class);
        app(TerbitkanSuratSeminar::class)->execute(
            User::factory()->adminUtama()->create(), $data['seminar'], JenisSurat::UndanganSeminar
        );
    }

    /** @return array<string, mixed> */
    private function dataSeminar(bool $denganTtd = false): array
    {
        $programStudi = ProgramStudi::factory()->create(['nama' => 'Teknik Informatika']);
        $ketuaUser = User::factory()->dosen()->create();
        $ketua = Dosen::factory()->create(['program_studi_id' => $programStudi->id, 'user_id' => $ketuaUser->id]);
        $programStudi->update(['ketua_prodi_id' => $ketua->nidn]);
        if ($denganTtd) {
            $path = "tanda-tangan/kaprodi/{$programStudi->id}/ttd.png";
            Storage::disk('local')->put($path, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII='));
            $programStudi->forceFill(['ttd_ketua_prodi' => $path])->save();
        }
        $mahasiswaUser = User::factory()->mahasiswa()->create();
        $mahasiswa = Mahasiswa::factory()->create(['program_studi_id' => $programStudi->id, 'pembimbing_akademik_id' => $ketua->nidn, 'user_id' => $mahasiswaUser->id]);
        $pembimbing1 = Dosen::factory()->create(['program_studi_id' => $programStudi->id]);
        $skripsi = Skripsi::factory()->create(['nim' => $mahasiswa->nim, 'pembimbing1_id' => $pembimbing1->nidn]);
        $penguji1 = Dosen::factory()->create(['program_studi_id' => $programStudi->id]);
        $penguji2 = Dosen::factory()->create(['program_studi_id' => $programStudi->id]);
        $seminar = Seminar::factory()->for($skripsi)->create([
            'penguji1_id' => $penguji1->nidn, 'penguji2_id' => $penguji2->nidn,
            'tanggal' => '2026-08-20 09:00:00', 'tempat' => 'Ruang Seminar A',
            'status' => StatusSeminar::Dijadwalkan,
        ]);

        return compact('programStudi', 'ketuaUser', 'ketua', 'mahasiswaUser', 'skripsi', 'penguji1', 'penguji2', 'seminar');
    }
}
