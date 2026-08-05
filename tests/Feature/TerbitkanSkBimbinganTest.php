<?php

namespace Tests\Feature;

use App\Actions\Surat\TerbitkanSkBimbingan;
use App\Enums\JenisSurat;
use App\Enums\StatusSkripsi;
use App\Enums\StatusSurat;
use App\Models\Dosen;
use App\Models\Mahasiswa;
use App\Models\PengajuanJudul;
use App\Models\ProgramStudi;
use App\Models\Skripsi;
use App\Models\Surat;
use App\Models\User;
use App\Services\Pdf\SkBimbinganPdf;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TerbitkanSkBimbinganTest extends TestCase
{
    use DatabaseTransactions;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_admin_prodi_menerbitkan_sk_terverifikasi_tanpa_signature(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        Carbon::setTestNow('2026-08-05 14:00:00');
        $data = $this->dataSkripsiFinal();
        $admin = User::factory()->adminProdi()->create();
        $admin->programStudiAdministrasi()->attach($data['programStudi']);

        $surat = app(TerbitkanSkBimbingan::class)->execute($admin, $data['skripsi']);

        $this->assertSame(Skripsi::class, $surat->suratable_type);
        $this->assertSame($data['skripsi']->id, $surat->suratable_id);
        $this->assertSame(JenisSurat::SkBimbingan, $surat->jenis_surat);
        $this->assertSame(StatusSurat::Terverifikasi, $surat->status);
        $this->assertSame($admin->id, $surat->verified_by);
        $this->assertNotNull($surat->verified_at);
        $this->assertNull($surat->signed_by);
        $this->assertNull($surat->signed_at);
        $this->assertSame($data['mahasiswa']->nama, $surat->tujuan_surat);
        Storage::disk('local')->assertExists($surat->file_path);
        Storage::disk('public')->assertMissing($surat->file_path);
        $pdf = Storage::disk('local')->get($surat->file_path);
        $this->assertStringStartsWith('%PDF-', $pdf);
        $this->assertSame(hash('sha256', $pdf), $surat->file_hash);
        $this->assertDatabaseHas('aktivitas_log', [
            'subject_type' => Surat::class,
            'subject_id' => $surat->id,
            'aksi' => 'surat_diterbitkan',
        ]);
    }

    public function test_admin_utama_tidak_pernah_mendapat_signature_meski_ttd_tersedia(): void
    {
        Storage::fake('local');
        $data = $this->dataSkripsiFinal(denganTtd: true);
        $adminUtama = User::factory()->adminUtama()->create();

        $surat = app(TerbitkanSkBimbingan::class)->execute(
            $adminUtama,
            $data['skripsi']
        );

        $this->assertSame($adminUtama->id, $surat->verified_by);
        $this->assertNull($surat->signed_by);
        $this->assertNull($surat->signed_at);
    }

    public function test_kaprodi_terkait_menandatangani_dengan_ttd_privat_valid(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $data = $this->dataSkripsiFinal(denganTtd: true);

        $surat = app(TerbitkanSkBimbingan::class)->execute(
            $data['ketuaUser'],
            $data['skripsi']
        );

        $this->assertSame($data['ketua']->nidn, $surat->signed_by);
        $this->assertNotNull($surat->signed_at);
        $this->assertSame($data['ketuaUser']->id, $surat->verified_by);
        Storage::disk('local')->assertExists($data['programStudi']->ttd_ketua_prodi);
        Storage::disk('public')->assertMissing($data['programStudi']->ttd_ketua_prodi);
    }

    public function test_html_membedakan_dokumen_signed_dan_unsigned_dengan_data_kanonis(): void
    {
        Storage::fake('local');
        $data = $this->dataSkripsiFinal(denganPembimbing2: true, denganTtd: true);
        $service = app(SkBimbinganPdf::class);
        $dataUri = 'data:image/png;base64,'.base64_encode($this->pngValid());
        $signed = $service->renderHtml(
            $data['skripsi'],
            'SKB-TEST-001',
            Carbon::parse('2026-08-05'),
            $data['ketua'],
            $dataUri
        );
        $unsigned = $service->renderHtml(
            $data['skripsi'],
            'SKB-TEST-002',
            Carbon::parse('2026-08-05'),
            null,
            null
        );

        foreach ([
            'SKB-TEST-001',
            $data['mahasiswa']->nama,
            $data['mahasiswa']->nim,
            $data['skripsi']->judul,
            $data['pembimbing1']->nama,
            $data['pembimbing2']->nama,
            $data['ketua']->nama,
            $dataUri,
        ] as $teks) {
            $this->assertStringContainsString($teks, $signed);
        }
        $this->assertStringNotContainsString(
            $data['programStudi']->ttd_ketua_prodi,
            $signed
        );
        $this->assertStringContainsString(
            'TERVERIFIKASI TANPA TANDA TANGAN KAPRODI',
            $unsigned
        );
        $this->assertStringContainsString(
            'tidak dinyatakan ditandatangani Kaprodi',
            $unsigned
        );
        $this->assertStringNotContainsString('<img', $unsigned);
    }

    public function test_kaprodi_wajib_memiliki_ttd_privat_valid_tetapi_admin_tidak(): void
    {
        Storage::fake('local');

        foreach (['kosong', 'path_luar', 'gambar_palsu'] as $kondisi) {
            $data = $this->dataSkripsiFinal();
            if ($kondisi === 'path_luar') {
                Storage::disk('local')->put('dokumen/rahasia.png', $this->pngValid());
                $data['programStudi']->forceFill([
                    'ttd_ketua_prodi' => 'dokumen/rahasia.png',
                ])->save();
            } elseif ($kondisi === 'gambar_palsu') {
                $path = "tanda-tangan/kaprodi/{$data['programStudi']->id}/ttd.png";
                Storage::disk('local')->put($path, 'bukan gambar');
                $data['programStudi']->forceFill(['ttd_ketua_prodi' => $path])->save();
            }

            try {
                app(TerbitkanSkBimbingan::class)->execute(
                    $data['ketuaUser'],
                    $data['skripsi']
                );
                $this->fail("TTD {$kondisi} dapat dipakai.");
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('tanda_tangan', $exception->errors());
            }
            $this->assertDatabaseMissing('surat', [
                'suratable_type' => Skripsi::class,
                'suratable_id' => $data['skripsi']->id,
            ]);
        }

        $dataAdmin = $this->dataSkripsiFinal();
        $surat = app(TerbitkanSkBimbingan::class)->execute(
            User::factory()->adminUtama()->create(),
            $dataAdmin['skripsi']
        );
        $this->assertNull($surat->signed_by);
    }

    public function test_versi_baru_mengarsipkan_lama_tanpa_menimpa_file(): void
    {
        Storage::fake('local');
        $data = $this->dataSkripsiFinal();
        $admin = User::factory()->adminUtama()->create();
        $action = app(TerbitkanSkBimbingan::class);
        $versi1 = $action->execute($admin, $data['skripsi']);
        $isi1 = Storage::disk('local')->get($versi1->file_path);
        $versi2 = $action->execute($admin, $data['skripsi']);

        $this->assertSame(StatusSurat::Dibatalkan, $versi1->fresh()->status);
        $this->assertSame(StatusSurat::Terverifikasi, $versi2->status);
        $this->assertSame(2, $versi2->versi);
        $this->assertNotSame($versi1->file_path, $versi2->file_path);
        $this->assertSame($isi1, Storage::disk('local')->get($versi1->file_path));
        Storage::disk('local')->assertExists([$versi1->file_path, $versi2->file_path]);
    }

    public function test_role_dan_status_skripsi_ditegakkan(): void
    {
        Storage::fake('local');
        $data = $this->dataSkripsiFinal();
        $adminLain = User::factory()->adminProdi()->create();
        $adminLain->programStudiAdministrasi()->attach(ProgramStudi::factory()->create());
        $kaprodiLain = $this->kaprodiUntuk(ProgramStudi::factory()->create())['user'];

        foreach ([$adminLain, $kaprodiLain, $data['mahasiswaUser']] as $user) {
            try {
                app(TerbitkanSkBimbingan::class)->execute($user, $data['skripsi']);
                $this->fail('Role/cakupan yang salah dapat menerbitkan SK.');
            } catch (AuthorizationException) {
                $this->assertDatabaseCount('surat', 0);
            }
        }

        $data['skripsi']->forceFill([
            'status' => StatusSkripsi::MenungguKesediaanPembimbing,
        ])->save();
        try {
            app(TerbitkanSkBimbingan::class)->execute(
                User::factory()->adminUtama()->create(),
                $data['skripsi']
            );
            $this->fail('SK dapat diterbitkan sebelum finalisasi.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('skripsi', $exception->errors());
        }
    }

    /** @return array<string, mixed> */
    private function dataSkripsiFinal(
        bool $denganPembimbing2 = false,
        bool $denganTtd = false
    ): array {
        $programStudi = ProgramStudi::factory()->create(['nama' => 'Teknik Informatika']);
        ['user' => $ketuaUser, 'dosen' => $ketua] = $this->kaprodiUntuk($programStudi);
        if ($denganTtd) {
            $path = "tanda-tangan/kaprodi/{$programStudi->id}/ttd.png";
            Storage::disk('local')->put($path, $this->pngValid());
            $programStudi->forceFill(['ttd_ketua_prodi' => $path])->save();
        }
        $mahasiswaUser = User::factory()->mahasiswa()->create();
        $mahasiswa = Mahasiswa::factory()->create([
            'nama' => 'Mahasiswa SK',
            'program_studi_id' => $programStudi->id,
            'pembimbing_akademik_id' => $ketua->nidn,
            'user_id' => $mahasiswaUser->id,
        ]);
        $pengajuan = PengajuanJudul::factory()->diverifikasi($ketua)->create([
            'nim' => $mahasiswa->nim,
            'judul' => 'Sistem Administrasi Skripsi Aman',
        ]);
        $pembimbing1 = Dosen::factory()->create([
            'nama' => 'Dosen Pembimbing Satu',
            'program_studi_id' => $programStudi->id,
        ]);
        $pembimbing2 = $denganPembimbing2
            ? Dosen::factory()->create([
                'nama' => 'Dosen Pembimbing Dua',
                'program_studi_id' => $programStudi->id,
            ])
            : null;
        $skripsi = Skripsi::factory()->for($pengajuan, 'pengajuanJudul')->create([
            'nim' => $mahasiswa->nim,
            'judul' => $pengajuan->judul,
            'pembimbing1_id' => $pembimbing1->nidn,
            'pembimbing2_id' => $pembimbing2?->nidn,
            'status' => StatusSkripsi::BimbinganAktif,
        ]);

        return compact(
            'programStudi',
            'ketuaUser',
            'ketua',
            'mahasiswaUser',
            'mahasiswa',
            'pembimbing1',
            'pembimbing2',
            'skripsi'
        );
    }

    /** @return array{user: User, dosen: Dosen} */
    private function kaprodiUntuk(ProgramStudi $programStudi): array
    {
        $user = User::factory()->dosen()->create();
        $dosen = Dosen::factory()->create([
            'program_studi_id' => $programStudi->id,
            'user_id' => $user->id,
        ]);
        $programStudi->update(['ketua_prodi_id' => $dosen->nidn]);

        return ['user' => $user->fresh('dosen'), 'dosen' => $dosen];
    }

    private function pngValid(): string
    {
        return base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII='
        );
    }
}
