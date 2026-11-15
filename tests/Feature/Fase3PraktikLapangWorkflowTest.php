<?php

namespace Tests\Feature;

use App\Enums\JenisSuratInternal;
use App\Enums\StatusPenugasanDosen;
use App\Enums\StatusPraktikLapang;
use App\Enums\StatusVerifikasiLokasiPl;
use App\Enums\UserRole;
use App\Models\LogbookPl;
use App\Models\LokasiPl;
use App\Models\PenugasanDosen;
use App\Models\PraktikLapang;
use App\Models\ProgramStudi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class Fase3PraktikLapangWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private ProgramStudi $prodiAgt;
    private ProgramStudi $prodiAgb;
    private User $kaprodiAgt;
    private User $kaprodiAgb;
    private User $adminAgt;
    private User $dosenDpl;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        $this->prodiAgt = ProgramStudi::create(['nama' => 'Agroteknologi', 'kode' => 'AGT']);
        $this->prodiAgb = ProgramStudi::create(['nama' => 'Agribisnis', 'kode' => 'AGB']);

        $this->kaprodiAgt = $this->createUser('Kaprodi AGT PL', 'KAP-AGT-PL', UserRole::Kaprodi, $this->prodiAgt);
        $this->kaprodiAgb = $this->createUser('Kaprodi AGB PL', 'KAP-AGB-PL', UserRole::Kaprodi, $this->prodiAgb);
        $this->adminAgt = $this->createUser('Admin Prodi AGT PL', 'ADM-AGT-PL', UserRole::AdminProdi, $this->prodiAgt);
        $this->dosenDpl = $this->createUser('Dr. DPL AGT, M.P.', 'DSN-AGT-DPL', UserRole::Dosen, $this->prodiAgt);
    }

    public function test_mahasiswa_end_to_end_pl_terintegrasi_persuratan_dan_dokumen_rbac(): void
    {
        $mhsAgt = $this->createUser('Rina Mahasiswa AGT', '221010501', UserRole::Mahasiswa, $this->prodiAgt);
        $mhsLain = $this->createUser('Rudi Mahasiswa Lain', '221010502', UserRole::Mahasiswa, $this->prodiAgb);

        $lokasi = LokasiPl::create([
            'nama_instansi' => 'Balai Benih Induk Tanaman Pangan Pontianak',
            'sektor' => 'AGT',
            'alamat' => 'Jl. Trans Kalimantan',
            'kuota_periode' => 8,
            'status_verifikasi' => StatusVerifikasiLokasiPl::Terverifikasi,
        ]);

        // 1. Mahasiswa akses halaman PL & daftar PL
        $this->actingAs($mhsAgt)
            ->get(route('mahasiswa.pl.index'))
            ->assertOk()
            ->assertSee($lokasi->nama_instansi);

        $this->actingAs($mhsAgt)
            ->post(route('mahasiswa.pl.daftar'), [
                'periode_akademik' => 'Ganjil 2026/2027',
                'sks_ditempuh' => 98,
                'judul_kegiatan' => 'Sertifikasi Benih Padi Unggul Di Balai Benih Induk',
                'lokasi_pl_id' => $lokasi->id,
                'file_transkrip_sks' => UploadedFile::fake()->create('transkrip.pdf', 100, 'application/pdf'),
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $pl = PraktikLapang::where('mahasiswa_id', $mhsAgt->id)->firstOrFail();

        // 2. Ajukan Surat Pengantar PL ke Modul Persuratan (Modul 1)
        $this->actingAs($mhsAgt)
            ->post(route('mahasiswa.pl.surat-pengantar', $pl))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('pengajuan_surat', [
            'mahasiswa_id' => $mhsAgt->id,
            'jenis_surat' => JenisSuratInternal::PengantarPl->value,
        ]);

        // 3. Unggah surat balasan instansi -> status MenungguDpl
        $this->actingAs($mhsAgt)
            ->post(route('mahasiswa.pl.balasan', $pl), [
                'file_surat_balasan' => UploadedFile::fake()->create('balasan_mitra.pdf', 120, 'application/pdf'),
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        // 4. Kaprodi AGT tetapkan DPL -> DPL setujui via route dosen.penugasan.respon
        $this->actingAs($this->kaprodiAgt)
            ->post(route('kelola.pl.dpl', $pl), ['dpl_id' => $this->dosenDpl->id])
            ->assertRedirect()
            ->assertSessionHas('success');

        $penugasan = PenugasanDosen::where('assignable_type', PraktikLapang::class)
            ->where('assignable_id', $pl->id)
            ->where('dosen_id', $this->dosenDpl->id)
            ->firstOrFail();

        $this->actingAs($this->dosenDpl)
            ->post(route('dosen.penugasan.respon', $penugasan), ['aksi' => 'terima'])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(StatusPenugasanDosen::Disetujui, $penugasan->fresh()->status);
        $this->assertSame(StatusPraktikLapang::Berjalan, $pl->fresh()->status);

        // 5. Admin AGT terbitkan SK DPL
        $this->actingAs($this->adminAgt)
            ->post(route('kelola.pl.sk-dpl', $pl), [
                'nomor_sk_dpl' => 'SK-DPL/09/FPST/2026',
                'file_sk_dpl' => UploadedFile::fake()->create('sk_dpl.pdf', 100, 'application/pdf'),
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('surat', [
            'praktik_lapang_id' => $pl->id,
            'jenis_surat' => 'sk_dpl',
            'nomor_surat' => 'SK-DPL/09/FPST/2026',
        ]);

        // 6. Mahasiswa isi logbook & unggah laporan akhir
        $this->actingAs($mhsAgt)
            ->post(route('mahasiswa.pl.logbook', $pl), [
                'tanggal' => '2026-12-01',
                'aktivitas' => 'Pengujian daya kecambah benih padi',
                'uraian' => 'Menggunakan metode uji kertas digulung untuk 400 butir benih.',
                'file_dokumentasi' => UploadedFile::fake()->image('kegiatan.jpg'),
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->actingAs($mhsAgt)
            ->post(route('mahasiswa.pl.laporan', $pl), [
                'file_laporan_akhir' => UploadedFile::fake()->create('laporan_pl.pdf', 200, 'application/pdf'),
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        // 7. DPL verifikasi logbook & input penilaian akhir + sertifikat
        $logbook = LogbookPl::where('praktik_lapang_id', $pl->id)->firstOrFail();

        $this->actingAs($this->dosenDpl)
            ->post(route('kelola.pl.logbook.verifikasi', $logbook), ['catatan_dpl' => 'Sesuai SOP ISTA'])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->actingAs($this->dosenDpl)
            ->post(route('kelola.pl.penilaian', $pl), [
                'nilai_pembimbing_lapang' => 86,
                'nilai_dpl' => 90,
                'file_sertifikat' => UploadedFile::fake()->create('sertifikat_pl.pdf', 100, 'application/pdf'),
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $plFresh = $pl->fresh();
        $this->assertSame(StatusPraktikLapang::Selesai, $plFresh->status);
        $this->assertEquals(88.0, (float) $plFresh->nilai_akhir);

        // 8. RBAC DokumenController pada berkas PL: pemilik & DPL diizinkan, mahasiswa lain 403
        $encodedSertifikat = base64_encode($plFresh->file_sertifikat);
        $this->actingAs($mhsAgt)->get(route('dokumen.download', $encodedSertifikat))->assertOk();
        $this->actingAs($this->dosenDpl)->get(route('dokumen.download', $encodedSertifikat))->assertOk();
        $this->actingAs($mhsLain)->get(route('dokumen.download', $encodedSertifikat))->assertForbidden();
    }

    public function test_usulan_lokasi_pl_baru_dan_jadwal_seminar_pl_tampil_di_portal_publik(): void
    {
        $mhsAgb = $this->createUser('Doni Mahasiswa AGB', '222010901', UserRole::Mahasiswa, $this->prodiAgb);

        $this->actingAs($mhsAgb)
            ->post(route('mahasiswa.pl.daftar'), [
                'periode_akademik' => 'Ganjil 2026/2027',
                'sks_ditempuh' => 94,
                'judul_kegiatan' => 'Analisis Pemasaran Kopi Robusta Kelompok Tani Mandiri',
                'nama_instansi_baru' => 'Koperasi Tani Mandiri Landak',
                'sektor_baru' => 'AGB',
                'alamat_baru' => 'Ngabang, Kabupaten Landak',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $pl = PraktikLapang::where('mahasiswa_id', $mhsAgb->id)->firstOrFail();
        $lokasiBaru = $pl->lokasiPl;
        $this->assertSame(StatusVerifikasiLokasiPl::Menunggu, $lokasiBaru->status_verifikasi);

        // Kaprodi AGB memverifikasi lokasi baru
        $this->actingAs($this->kaprodiAgb)
            ->post(route('kelola.pl.lokasi.verifikasi', $lokasiBaru), [
                'status_verifikasi' => StatusVerifikasiLokasiPl::Terverifikasi->value,
                'kuota_periode' => 6,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->actingAs($this->kaprodiAgb)
            ->post(route('kelola.pl.dpl', $pl), ['dpl_id' => $this->kaprodiAgb->id])
            ->assertRedirect();

        $this->actingAs($this->kaprodiAgb)
            ->post(route('kelola.pl.seminar', $pl), [
                'tgl_seminar_pl' => '2027-02-25',
                'jam_seminar_pl' => '10:00 - 11:30',
                'ruangan_seminar_pl' => 'Ruang Seminar Agribisnis',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        // Portal Jadwal Publik menampilkan Seminar PL dan menerapkan Data Minimization (tanpa NIM)
        $this->get(route('jadwal.publik', ['jenis' => 'pl']))
            ->assertOk()
            ->assertSee('Seminar Praktik Lapang')
            ->assertSee('Doni Mahasiswa AGB')
            ->assertSee('Koperasi Tani Mandiri Landak')
            ->assertDontSee('222010901');
    }

    public function test_otorisasi_lintas_prodi_kelola_pl(): void
    {
        $mhsAgt = $this->createUser('Mhs Proteksi PL', '221099001', UserRole::Mahasiswa, $this->prodiAgt);
        $lokasi = LokasiPl::create([
            'nama_instansi' => 'Kebun Percobaan Fakultas',
            'sektor' => 'AGT',
            'alamat' => 'Pontianak',
            'kuota_periode' => 5,
            'status_verifikasi' => StatusVerifikasiLokasiPl::Terverifikasi,
        ]);

        $plAgt = PraktikLapang::create([
            'mahasiswa_id' => $mhsAgt->id,
            'program_studi_id' => $this->prodiAgt->id,
            'lokasi_pl_id' => $lokasi->id,
            'periode_akademik' => 'Ganjil 2026/2027',
            'sks_ditempuh' => 100,
            'judul_kegiatan' => 'Budidaya Hortikultura Organik',
            'status' => StatusPraktikLapang::MenungguDpl,
        ]);

        $this->actingAs($mhsAgt)->get(route('kelola.pl.index'))->assertForbidden();
        $this->actingAs($this->kaprodiAgb)->post(route('kelola.pl.dpl', $plAgt), ['dpl_id' => $this->dosenDpl->id])->assertForbidden();
    }

    private function createUser(string $name, string $nim, UserRole $role, ProgramStudi $prodi): User
    {
        return User::create([
            'name' => $name,
            'email' => strtolower(str_replace([' ', '.'], '', $nim)) . '@fpst-pl.test',
            'nomor_induk' => $nim,
            'password' => 'password',
            'role' => $role,
            'program_studi_id' => $prodi->id,
        ]);
    }
}
