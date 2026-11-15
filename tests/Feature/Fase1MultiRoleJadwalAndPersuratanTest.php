<?php

namespace Tests\Feature;

use App\Enums\JenisSuratInternal;
use App\Enums\StatusPengajuan;
use App\Enums\StatusPengajuanSurat;
use App\Enums\UserRole;
use App\Models\PengajuanSkripsi;
use App\Models\PengajuanSurat;
use App\Models\ProgramStudi;
use App\Models\SeminarSkripsi;
use App\Models\SidangSkripsi;
use App\Models\Surat;
use App\Models\User;
use App\Services\JadwalPublikService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class Fase1MultiRoleJadwalAndPersuratanTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_utama_can_assign_tendik_pimpinan_and_multi_roles(): void
    {
        $agt = ProgramStudi::create(['nama' => 'Agroteknologi', 'kode' => 'AGT']);
        $adminUtama = User::factory()->create(['role' => UserRole::AdminUtama]);

        $response = $this->actingAs($adminUtama)->post(route('admin.master.user.store'), [
            'name' => 'Dr. Siti Maimunah, M.P.',
            'email' => 'siti.multi@example.test',
            'nomor_induk' => '1000000888',
            'role' => UserRole::Dosen->value,
            'extra_roles' => [UserRole::Kaprodi->value, UserRole::PimpinanFakultas->value],
            'program_studi_id' => $agt->id,
            'jabatan' => 'Ketua Program Studi Agroteknologi',
            'unit_kerja' => 'FPST UPB',
        ]);

        $response->assertRedirect(route('admin.master.user.index'));

        $created = User::where('email', 'siti.multi@example.test')->firstOrFail();
        $this->assertTrue($created->hasRole(UserRole::Dosen));
        $this->assertTrue($created->hasRole(UserRole::Kaprodi));
        $this->assertTrue($created->hasRole(UserRole::PimpinanFakultas));
        $this->assertFalse($created->hasRole(UserRole::AdminUtama));

        // EnsureUserHasRole middleware mengakui extra role Kaprodi & PimpinanFakultas
        $this->actingAs($created)->get(route('kaprodi.penetapan.index'))->assertOk();
        $this->actingAs($created)->get(route('admin.persuratan.index'))->assertOk();
    }

    public function test_public_schedule_displays_agb_and_agt_agendas_with_data_minimization_and_cache_invalidation(): void
    {
        JadwalPublikService::clearCache();

        $agt = ProgramStudi::create(['nama' => 'Agroteknologi', 'kode' => 'AGT']);
        $agb = ProgramStudi::create(['nama' => 'Agribisnis', 'kode' => 'AGB']);

        $adminAgt = User::factory()->create([
            'role' => UserRole::AdminProdi,
            'program_studi_id' => $agt->id,
        ]);
        $mhsAgt = User::factory()->create([
            'name' => 'Budi Santoso',
            'email' => 'budi.rahasia@example.test',
            'nomor_induk' => '221000009901',
            'no_hp' => '081999888777',
            'role' => UserRole::Mahasiswa,
            'program_studi_id' => $agt->id,
        ]);
        $mhsAgb = User::factory()->create([
            'name' => 'Rina Amelia',
            'email' => 'rina.rahasia@example.test',
            'nomor_induk' => '222000009902',
            'no_hp' => '081999888666',
            'role' => UserRole::Mahasiswa,
            'program_studi_id' => $agb->id,
        ]);
        $d1 = User::factory()->create(['name' => 'Dr. Ahmad Pembimbing, M.P.', 'role' => UserRole::Dosen, 'program_studi_id' => $agt->id]);
        $d2 = User::factory()->create(['name' => 'Sari Penguji, S.P., M.Si.', 'role' => UserRole::Dosen, 'program_studi_id' => $agt->id]);
        $d3 = User::factory()->create(['name' => 'Hendra Penguji, M.Sc.', 'role' => UserRole::Dosen, 'program_studi_id' => $agb->id]);

        $skripsiAgt = PengajuanSkripsi::create([
            'mahasiswa_id' => $mhsAgt->id,
            'program_studi_id' => $agt->id,
            'judul' => 'Optimasi Pupuk Organik Hayati pada Tanaman Jagung Hibrida',
            'pembimbing_1_id' => $d1->id,
            'status' => StatusPengajuan::Selesai,
            'nomor_sk_bimbingan' => 'SK/10/AGT/2026',
        ]);
        SeminarSkripsi::create([
            'pengajuan_skripsi_id' => $skripsiAgt->id,
            'penguji_seminar_id' => $d2->id,
            'tgl_seminar' => now()->addDays(3)->toDateString(),
            'jam_seminar' => '09:00 WIB',
            'ruangan' => 'Ruang Seminar FPST 1',
            'status' => StatusPengajuan::Diproses,
        ]);

        // Kunjungan awal mengisi cache publik
        $firstResponse = $this->get(route('jadwal.publik'));
        $firstResponse->assertOk();
        $firstResponse->assertSee('Budi Santoso');
        $firstResponse->assertSee('Optimasi Pupuk Organik Hayati pada Tanaman Jagung Hibrida');
        $firstResponse->assertSee('Ruang Seminar FPST 1');
        // Data minimization: NIM, email, no_hp TIDAK boleh tampil
        $firstResponse->assertDontSee('221000009901');
        $firstResponse->assertDontSee('budi.rahasia@example.test');
        $firstResponse->assertDontSee('081999888777');

        // Sidang baru dijadwalkan untuk Agribisnis -> clearCache dipicu saat admin menjadwalkan
        $skripsiAgb = PengajuanSkripsi::create([
            'mahasiswa_id' => $mhsAgb->id,
            'program_studi_id' => $agb->id,
            'judul' => 'Analisis Efisiensi Pemasaran Beras Lokal Kalimantan Barat',
            'pembimbing_1_id' => $d1->id,
            'status' => StatusPengajuan::Selesai,
            'nomor_sk_bimbingan' => 'SK/11/AGB/2026',
        ]);
        $sidangAgb = SidangSkripsi::create([
            'pengajuan_skripsi_id' => $skripsiAgb->id,
            'penguji_1_id' => $d2->id,
            'penguji_2_id' => $d3->id,
            'status' => StatusPengajuan::Diajukan,
        ]);

        $adminAgb = User::factory()->create(['role' => UserRole::AdminProdi, 'program_studi_id' => $agb->id]);

        Storage::fake('local');
        $this->actingAs($adminAgb)->post(route('admin.sidang.jadwal-sk', $sidangAgb->id), [
            'tgl_sidang' => now()->addDays(5)->toDateString(),
            'jam_sidang' => '13:30 WIB',
            'ruangan' => 'Aula Utama FPST',
            'nomor_undangan_sidang' => 'UND-SDG/011/AGB/2026',
            'nomor_sk_sidang' => 'SK-SDG/011/AGB/2026',
        ])->assertSessionHasNoErrors();

        $refreshed = $this->get(route('jadwal.publik', ['jenis' => 'sidang', 'prodi_id' => $agb->id]));
        $refreshed->assertOk();
        $refreshed->assertSee('Rina Amelia');
        $refreshed->assertSee('Sidang Skripsi');
        $refreshed->assertSee('Aula Utama FPST');
        $refreshed->assertDontSee('222000009902');
        $refreshed->assertDontSee('Budi Santoso');
    }

    public function test_internal_letter_end_to_end_disposition_atomic_numbering_and_qr_verification(): void
    {
        Storage::fake('local');

        $agt = ProgramStudi::create(['nama' => 'Agroteknologi', 'kode' => 'AGT']);
        $agb = ProgramStudi::create(['nama' => 'Agribisnis', 'kode' => 'AGB']);

        $mhsAgt = User::factory()->create([
            'name' => 'Andi Pratama',
            'nomor_induk' => '221000005501',
            'role' => UserRole::Mahasiswa,
            'program_studi_id' => $agt->id,
        ]);
        $mhsLain = User::factory()->create([
            'role' => UserRole::Mahasiswa,
            'program_studi_id' => $agb->id,
        ]);
        $tendik = User::factory()->create([
            'name' => 'Rina Kartika, S.E.',
            'role' => UserRole::Tendik,
            'jabatan' => 'Staf TU Persuratan FPST',
        ]);
        $dekan = User::factory()->create([
            'name' => 'Prof. Dr. Hendro Wijoyo, M.P.',
            'nomor_induk' => '1000000099',
            'role' => UserRole::PimpinanFakultas,
            'jabatan' => 'Dekan Fakultas Pertanian, Sains dan Teknologi',
        ]);

        // 1. Mahasiswa mengajukan Surat Izin Penelitian + lampiran pendukung
        $pdfPendukung = UploadedFile::fake()->create('lampiran_proposal.pdf', 150, 'application/pdf');
        $this->actingAs($mhsAgt)->post(route('mahasiswa.surat-internal.store'), [
            'jenis_surat' => JenisSuratInternal::IzinPenelitian->value,
            'perihal' => 'Izin Pengambilan Sampel Tanah Lahan Gambut',
            'tujuan_instansi' => 'BPTP Kalimantan Barat',
            'alamat_instansi' => 'Pontianak',
            'tanggal_mulai' => now()->addDays(7)->toDateString(),
            'tanggal_selesai' => now()->addDays(21)->toDateString(),
            'keterangan_kebutuhan' => 'Pengambilan data penelitian Agroteknologi tentang kesuburan tanah.',
            'file_pendukung' => $pdfPendukung,
        ])->assertRedirect(route('mahasiswa.surat-internal.index'));

        $pengajuan = PengajuanSurat::firstOrFail();
        $this->assertEquals(StatusPengajuanSurat::Diajukan, $pengajuan->status);
        Storage::disk('local')->assertExists($pengajuan->file_pendukung);

        // Mahasiswa lain ditolak akses berkas pendukung (IDOR protection)
        $this->actingAs($mhsLain)
            ->get(route('dokumen.download', base64_encode($pengajuan->file_pendukung)))
            ->assertForbidden();

        // 2. Tendik belum bisa menerbitkan sebelum disetujui Pimpinan/Kaprodi
        $this->actingAs($tendik)
            ->post(route('admin.persuratan.terbitkan', $pengajuan->id))
            ->assertSessionHas('error');

        // 3. Tendik memverifikasi
        $this->actingAs($tendik)
            ->post(route('admin.persuratan.verifikasi', $pengajuan->id))
            ->assertSessionHas('success');
        $this->assertEquals(StatusPengajuanSurat::Diverifikasi, $pengajuan->fresh()->status);

        // 4. Tendik TIDAK boleh melakukan approval pimpinan
        $this->actingAs($tendik)
            ->post(route('admin.persuratan.setujui', $pengajuan->id))
            ->assertForbidden();

        // 5. Pimpinan Fakultas menyetujui
        $this->actingAs($dekan)
            ->post(route('admin.persuratan.setujui', $pengajuan->id))
            ->assertSessionHas('success');
        $this->assertEquals(StatusPengajuanSurat::Disetujui, $pengajuan->fresh()->status);

        // 6. Tendik menerbitkan PDF resmi + nomor surat atomik + QR Code verifikasi
        $this->actingAs($tendik)
            ->post(route('admin.persuratan.terbitkan', $pengajuan->id))
            ->assertSessionHas('success');

        $pengajuan->refresh();
        $this->assertEquals(StatusPengajuanSurat::Diterbitkan, $pengajuan->status);
        $this->assertNotNull($pengajuan->nomor_surat_terbit);
        $this->assertStringStartsWith('001/PNL/AGT/FPST-UPB/', $pengajuan->nomor_surat_terbit);
        $this->assertNotNull($pengajuan->file_surat_terbit);
        Storage::disk('local')->assertExists($pengajuan->file_surat_terbit);

        $surat = Surat::where('pengajuan_surat_id', $pengajuan->id)->firstOrFail();
        $this->assertEquals('aktif', $surat->status);
        $this->assertNotNull($surat->verification_token);

        // Pemilik sah bisa mengunduh surat resmi, mahasiswa lain dilarang
        $this->actingAs($mhsAgt)
            ->get(route('dokumen.download', base64_encode($pengajuan->file_surat_terbit)))
            ->assertOk();
        $this->actingAs($mhsLain)
            ->get(route('dokumen.download', base64_encode($pengajuan->file_surat_terbit)))
            ->assertForbidden();

        // 7a. Saat dinonaktifkan (default), halaman verifikasi QR menampilkan status nonaktif
        $verifyDisabled = $this->get(route('surat.verifikasi', $surat->verification_token));
        $verifyDisabled->assertOk();
        $verifyDisabled->assertSee('Layanan Verifikasi QR Code Dokumen Sedang Dinonaktifkan Sementara');
        $verifyDisabled->assertDontSee('Dokumen Terdaftar dan Terverifikasi Asli');

        // 7b. Ketika diaktifkan via config, verifikasi QR publik berjalan normal & menolak token palsu
        config(['fpst.surat_internal.qr_verifikasi_enabled' => true]);

        $verifyOk = $this->get(route('surat.verifikasi', $surat->verification_token));
        $verifyOk->assertOk();
        $verifyOk->assertSee('Dokumen Terdaftar dan Terverifikasi Asli');
        $verifyOk->assertSee($surat->nomor_surat);
        $verifyOk->assertSee('Andi Pratama');
        $verifyOk->assertDontSee('221000005501');

        $verifyFake = $this->get(route('surat.verifikasi', 'token-palsu-tidak-ada'));
        $verifyFake->assertOk();
        $verifyFake->assertSee('Dokumen Tidak Dikenali');
    }
}
