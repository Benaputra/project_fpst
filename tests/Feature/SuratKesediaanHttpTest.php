<?php

namespace Tests\Feature;

use App\Actions\Surat\TerbitkanSuratKesediaan;
use App\Enums\PeranKesediaanBimbingan;
use App\Models\Dosen;
use App\Models\KesediaanBimbingan;
use App\Models\Mahasiswa;
use App\Models\PengajuanJudul;
use App\Models\ProgramStudi;
use App\Models\Skripsi;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SuratKesediaanHttpTest extends TestCase
{
    use DatabaseTransactions;

    public function test_endpoint_penerbitan_dilindungi_dan_controller_tetap_tipis(): void
    {
        Storage::fake('local');
        $data = $this->dataKesediaan();

        $this->post(route('kesediaan-bimbingan.surat.store', $data['kesediaan']))
            ->assertRedirect(route('login'));

        $this->actingAs($data['ketuaUser'])
            ->from('/')
            ->post(route('kesediaan-bimbingan.surat.store', $data['kesediaan']), [
                'status' => 'terverifikasi',
                'signed_by' => $data['calon']->nidn,
                'file_path' => '../../public/palsu.pdf',
            ])
            ->assertRedirect('/')
            ->assertSessionHas('status', 'Surat kesediaan versi 1 berhasil diterbitkan.');

        $this->assertDatabaseHas('surat', [
            'suratable_id' => $data['kesediaan']->id,
            'status' => 'diterbitkan',
            'signed_by' => $data['ketua']->nidn,
            'verified_by' => null,
        ]);
        $this->assertDatabaseMissing('surat', ['file_path' => '../../public/palsu.pdf']);
    }

    public function test_mahasiswa_hanya_dapat_mengunduh_surat_miliknya(): void
    {
        Storage::fake('local');
        $data = $this->dataKesediaan();
        $surat = app(TerbitkanSuratKesediaan::class)->execute(
            $data['ketuaUser'],
            $data['kesediaan']
        );
        $mahasiswaLain = User::factory()->mahasiswa()->create();
        Mahasiswa::factory()->create(['user_id' => $mahasiswaLain->id]);

        $this->get(route('surat.download', $surat))->assertRedirect(route('login'));
        $this->actingAs($mahasiswaLain)
            ->get(route('surat.download', $surat))
            ->assertForbidden();

        $response = $this->actingAs($data['mahasiswaUser'])
            ->get(route('surat.download', $surat))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $response->assertDownload(sprintf(
            'surat-kesediaan-%s-v1.pdf',
            $data['mahasiswa']->nim
        ));
        $this->assertStringStartsWith('%PDF-', $response->streamedContent());
    }

    public function test_download_menolak_file_hilang_dan_hash_yang_berubah(): void
    {
        Storage::fake('local');
        $data = $this->dataKesediaan();
        $surat = app(TerbitkanSuratKesediaan::class)->execute(
            $data['ketuaUser'],
            $data['kesediaan']
        );

        Storage::disk('local')->delete($surat->file_path);
        $this->actingAs($data['mahasiswaUser'])
            ->get(route('surat.download', $surat))
            ->assertNotFound();

        Storage::disk('local')->put($surat->file_path, '%PDF-file-telah-diubah');
        $this->get(route('surat.download', $surat))
            ->assertStatus(409);
    }

    public function test_mahasiswa_tidak_dapat_menerbitkan_surat_sendiri(): void
    {
        Storage::fake('local');
        $data = $this->dataKesediaan();

        $this->actingAs($data['mahasiswaUser'])
            ->post(route('kesediaan-bimbingan.surat.store', $data['kesediaan']))
            ->assertForbidden();

        $this->assertDatabaseCount('surat', 0);
        $this->assertSame([], Storage::disk('local')->allFiles('surat'));
    }

    public function test_mahasiswa_mendapat_satu_tombol_dan_pdf_gabungan_p1_p2(): void
    {
        Storage::fake('local');
        $data = $this->dataKesediaan();
        $calon2User = User::factory()->dosen()->create();
        $calon2 = Dosen::factory()->create([
            'program_studi_id' => $data['programStudi']->id,
            'user_id' => $calon2User->id,
        ]);
        $kesediaan2 = KesediaanBimbingan::factory()->for($data['skripsi'])->create([
            'dosen_id' => $calon2->nidn,
            'peran' => PeranKesediaanBimbingan::Pembimbing2,
        ]);
        $action = app(TerbitkanSuratKesediaan::class);
        $surat1 = $action->execute($data['ketuaUser'], $data['kesediaan']);
        $surat2 = $action->execute($data['ketuaUser'], $kesediaan2);

        $halaman = $this->actingAs($data['mahasiswaUser'])
            ->get(route('mahasiswa.pengajuan-judul.index'))
            ->assertOk();
        $this->assertSame(1, substr_count($halaman->getContent(), '>Unduh surat kesediaan</a>'));
        $halaman->assertSee(route('skripsi.surat-kesediaan.download', $data['skripsi']));

        $halamanSkripsi = $this->get(route('portal.skripsi.index'))->assertOk();
        $this->assertSame(1, substr_count($halamanSkripsi->getContent(), '>Unduh surat kesediaan</a>'));
        $halamanSkripsi
            ->assertSee(route('skripsi.surat-kesediaan.download', $data['skripsi']))
            ->assertDontSee(route('surat.download', $surat1))
            ->assertDontSee(route('surat.download', $surat2));

        $response = $this->get(route('skripsi.surat-kesediaan.download', $data['skripsi']))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
        $response->assertDownload("surat-kesediaan-pembimbing-{$data['mahasiswa']->nim}.pdf");
        $this->assertStringStartsWith('%PDF-', $response->streamedContent());
    }

    public function test_dosen_hanya_melihat_dan_mengunduh_surat_kesediaan_miliknya(): void
    {
        Storage::fake('local');
        $data = $this->dataKesediaan();
        $calon2User = User::factory()->dosen()->create();
        $calon2 = Dosen::factory()->create([
            'program_studi_id' => $data['programStudi']->id,
            'user_id' => $calon2User->id,
        ]);
        $kesediaan2 = KesediaanBimbingan::factory()->for($data['skripsi'])->create([
            'dosen_id' => $calon2->nidn,
            'peran' => PeranKesediaanBimbingan::Pembimbing2,
        ]);
        $action = app(TerbitkanSuratKesediaan::class);
        $suratMiliknya = $action->execute($data['ketuaUser'], $data['kesediaan']);
        $suratOrangLain = $action->execute($data['ketuaUser'], $kesediaan2);

        $this->actingAs($data['calonUser'])
            ->get(route('surat.download', $suratMiliknya))
            ->assertOk();
        $this->get(route('surat.download', $suratOrangLain))->assertForbidden();
        $this->get(route('skripsi.surat-kesediaan.download', $data['skripsi']))->assertForbidden();

        $this->get(route('portal.skripsi.index'))
            ->assertOk()
            ->assertSee(route('surat.download', $suratMiliknya))
            ->assertDontSee(route('surat.download', $suratOrangLain))
            ->assertSee('Unduh surat kesediaan saya');
    }

    /**
     * @return array{
     *   ketuaUser: User,
     *   calon: Dosen,
     *   mahasiswaUser: User,
     *   mahasiswa: Mahasiswa,
     *   kesediaan: KesediaanBimbingan
     * }
     */
    private function dataKesediaan(): array
    {
        $programStudi = ProgramStudi::factory()->create();
        $ketuaUser = User::factory()->dosen()->create();
        $ketua = Dosen::factory()->create([
            'program_studi_id' => $programStudi->id,
            'user_id' => $ketuaUser->id,
        ]);
        $programStudi->update(['ketua_prodi_id' => $ketua->nidn]);
        $pathTandaTangan = "tanda-tangan/kaprodi/{$programStudi->id}/ttd.png";
        Storage::disk('local')->put(
            $pathTandaTangan,
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=')
        );
        $programStudi->update(['ttd_ketua_prodi' => $pathTandaTangan]);
        $mahasiswaUser = User::factory()->mahasiswa()->create();
        $mahasiswa = Mahasiswa::factory()->create([
            'program_studi_id' => $programStudi->id,
            'pembimbing_akademik_id' => $ketua->nidn,
            'user_id' => $mahasiswaUser->id,
        ]);
        $pengajuan = PengajuanJudul::factory()->diverifikasi($ketua)->create([
            'nim' => $mahasiswa->nim,
        ]);
        $skripsi = Skripsi::factory()->for($pengajuan, 'pengajuanJudul')->create([
            'nim' => $mahasiswa->nim,
            'judul' => $pengajuan->judul,
        ]);
        $calonUser = User::factory()->dosen()->create();
        $calon = Dosen::factory()->create([
            'program_studi_id' => $programStudi->id,
            'user_id' => $calonUser->id,
        ]);
        $kesediaan = KesediaanBimbingan::factory()->for($skripsi)->create([
            'dosen_id' => $calon->nidn,
        ]);

        return compact(
            'ketuaUser',
            'ketua',
            'calon',
            'calonUser',
            'mahasiswaUser',
            'mahasiswa',
            'programStudi',
            'skripsi',
            'kesediaan'
        );
    }
}
