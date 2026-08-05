<?php

namespace Tests\Feature;

use App\Actions\Surat\TerbitkanSuratSidang;
use App\Enums\JenisSurat;
use App\Enums\StatusSidangSkripsi;
use App\Enums\StatusSurat;
use App\Models\Dosen;
use App\Models\Mahasiswa;
use App\Models\ProgramStudi;
use App\Models\SidangSkripsi;
use App\Models\Skripsi;
use App\Models\User;
use App\Services\Pdf\SuratSeminarPdf;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SuratSidangTest extends TestCase
{
    use DatabaseTransactions;

    public function test_dua_jenis_sumber_data_versi_dan_admin_unsigned(): void
    {
        Storage::fake('local');
        $d = $this->data();
        $admin = User::factory()->adminUtama()->create();
        $action = app(TerbitkanSuratSidang::class);
        foreach ([JenisSurat::UndanganSidang, JenisSurat::SuratTugasSidang] as $jenis) {
            $surat = $action->execute($admin, $d['sidang'], $jenis);
            $this->assertSame($jenis, $surat->jenis_surat);
            $this->assertNull($surat->signed_by);
            Storage::disk('local')->assertExists($surat->file_path);
        }
        $v1 = $action->execute($admin, $d['sidang'], JenisSurat::UndanganSidang);
        $v2 = $action->execute($admin, $d['sidang'], JenisSurat::UndanganSidang);
        $this->assertSame(StatusSurat::Dibatalkan, $v1->fresh()->status);
        $this->assertSame(3, $v2->versi);
        $html = app(SuratSeminarPdf::class)->renderHtml($d['sidang'], JenisSurat::UndanganSidang, 'USD-TEST', now(), null, null);
        foreach ([$d['sidang']->tempat, $d['p1']->nama, $d['p2']->nama, $d['skripsi']->judul] as $text) {
            $this->assertStringContainsString($text, $html);
        }
    }

    public function test_kaprodi_signed_dan_admin_tidak_dapat_memalsukan_signature(): void
    {
        Storage::fake('local');
        $d = $this->data(true);
        $surat = app(TerbitkanSuratSidang::class)->execute($d['ketuaUser'], $d['sidang'], JenisSurat::UndanganSidang);
        $this->assertSame($d['ketua']->nidn, $surat->signed_by);
        $d2 = $this->data(true);
        $admin = User::factory()->adminUtama()->create();
        $this->actingAs($admin)->post(route('sidang.surat.store', $d2['sidang']), ['jenis_surat' => 'surat_tugas_sidang', 'signed_by' => $d2['ketua']->nidn])->assertRedirect();
        $this->assertNull($d2['sidang']->surat()->sole()->signed_by);
    }

    private function data(bool $ttd = false): array
    {
        $prodi = ProgramStudi::factory()->create();
        $ketuaUser = User::factory()->dosen()->create();
        $ketua = Dosen::factory()->create(['program_studi_id' => $prodi->id, 'user_id' => $ketuaUser->id]);
        $prodi->update(['ketua_prodi_id' => $ketua->nidn]);
        if ($ttd) {
            $path = "tanda-tangan/kaprodi/{$prodi->id}/ttd.png";
            Storage::disk('local')->put($path, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII='));
            $prodi->forceFill(['ttd_ketua_prodi' => $path])->save();
        }
        $m = Mahasiswa::factory()->create(['program_studi_id' => $prodi->id, 'pembimbing_akademik_id' => $ketua->nidn]);
        $pb = Dosen::factory()->create(['program_studi_id' => $prodi->id]);
        $skripsi = Skripsi::factory()->create(['nim' => $m->nim, 'pembimbing1_id' => $pb->nidn]);
        $p1 = Dosen::factory()->create(['program_studi_id' => $prodi->id]);
        $p2 = Dosen::factory()->create(['program_studi_id' => $prodi->id]);
        $sidang = SidangSkripsi::factory()->for($skripsi)->create(['penguji1_id' => $p1->nidn, 'penguji2_id' => $p2->nidn, 'tanggal' => '2026-09-01 09:00', 'tempat' => 'Ruang Sidang', 'status' => StatusSidangSkripsi::Dijadwalkan]);

        return compact('ketuaUser', 'ketua', 'skripsi', 'p1', 'p2', 'sidang');
    }
}
