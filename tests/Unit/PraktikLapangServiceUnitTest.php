<?php

namespace Tests\Unit;

use App\Enums\JenisSuratInternal;
use App\Enums\StatusPraktikLapang;
use App\Enums\StatusVerifikasiLokasiPl;
use App\Enums\UserRole;
use App\Models\LokasiPl;
use App\Models\PengajuanSurat;
use App\Models\ProgramStudi;
use App\Models\Surat;
use App\Models\User;
use App\Services\PraktikLapangService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PraktikLapangServiceUnitTest extends TestCase
{
    use RefreshDatabase;

    private PraktikLapangService $service;
    private ProgramStudi $prodiAgt;
    private ProgramStudi $prodiAgb;
    private User $kaprodiAgt;
    private User $adminAgt;
    private User $dosenDpl;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(PraktikLapangService::class);
        $this->prodiAgt = ProgramStudi::create(['nama' => 'Agroteknologi', 'kode' => 'AGT']);
        $this->prodiAgb = ProgramStudi::create(['nama' => 'Agribisnis', 'kode' => 'AGB']);

        $this->kaprodiAgt = $this->makeUser('Kaprodi AGT', 'KAP-PL-01', UserRole::Kaprodi, $this->prodiAgt);
        $this->adminAgt = $this->makeUser('Admin AGT', 'ADM-PL-01', UserRole::AdminProdi, $this->prodiAgt);
        $this->dosenDpl = $this->makeUser('Dr. Ir. Fauzi, M.P.', 'DSN-PL-01', UserRole::Dosen, $this->prodiAgt);
    }

    public function test_syarat_minimal_sks_dan_cegah_daftar_ganda_periode_sama(): void
    {
        $mhs = $this->makeUser('Mhs AGT SKS Kurang', '221010101', UserRole::Mahasiswa, $this->prodiAgt);
        $lokasi = LokasiPl::create([
            'nama_instansi' => 'Balai Pengkajian Teknologi Pertanian (BPTP) Kalbar',
            'sektor' => 'AGT',
            'alamat' => 'Jl. Budi Utomo No. 45, Pontianak',
            'kuota_periode' => 5,
            'status_verifikasi' => StatusVerifikasiLokasiPl::Terverifikasi,
        ]);

        // SKS di bawah batas minimal (90) ditolak
        try {
            $this->service->daftarPl($mhs, [
                'periode_akademik' => 'Ganjil 2026/2027',
                'sks_ditempuh' => 84,
                'judul_kegiatan' => 'Budidaya Padi Sawah Sistem Jajar Legowo',
                'lokasi_pl_id' => $lokasi->id,
            ]);
            $this->fail('Pendaftaran PL di bawah syarat minimal 90 SKS seharusnya ditolak.');
        } catch (DomainException $e) {
            $this->assertStringContainsString('90 sks', mb_strtolower($e->getMessage()));
        }

        // SKS memenuhi (96) berhasil
        $pl = $this->service->daftarPl($mhs, [
            'periode_akademik' => 'Ganjil 2026/2027',
            'sks_ditempuh' => 96,
            'judul_kegiatan' => 'Budidaya Padi Sawah Sistem Jajar Legowo',
            'lokasi_pl_id' => $lokasi->id,
        ]);
        $this->assertSame(StatusPraktikLapang::Diajukan, $pl->status);

        // Daftar ganda di periode yang sama ditolak
        try {
            $this->service->daftarPl($mhs, [
                'periode_akademik' => 'Ganjil 2026/2027',
                'sks_ditempuh' => 96,
                'judul_kegiatan' => 'Judul Kegiatan Kedua',
                'lokasi_pl_id' => $lokasi->id,
            ]);
            $this->fail('Pendaftaran PL ganda pada periode yang sama seharusnya ditolak.');
        } catch (DomainException $e) {
            $this->assertStringContainsString('sudah memiliki pendaftaran', mb_strtolower($e->getMessage()));
        }
    }

    public function test_kuota_mitra_pl_anti_overbooking_dan_usulan_lokasi_baru(): void
    {
        $lokasi = LokasiPl::create([
            'nama_instansi' => 'PT Perkebunan Nusantara Kebun Parindu',
            'sektor' => 'AGT/AGB',
            'alamat' => 'Kabupaten Sanggau',
            'kuota_periode' => 2,
            'status_verifikasi' => StatusVerifikasiLokasiPl::Terverifikasi,
        ]);

        $m1 = $this->makeUser('Mhs PL 1', '221020001', UserRole::Mahasiswa, $this->prodiAgt);
        $m2 = $this->makeUser('Mhs PL 2', '221020002', UserRole::Mahasiswa, $this->prodiAgb);
        $m3 = $this->makeUser('Mhs PL 3', '221020003', UserRole::Mahasiswa, $this->prodiAgt);

        $this->service->daftarPl($m1, [
            'periode_akademik' => 'Ganjil 2026/2027',
            'sks_ditempuh' => 100,
            'judul_kegiatan' => 'Manajemen Pemupukan Kelapa Sawit',
            'lokasi_pl_id' => $lokasi->id,
        ]);
        $this->service->daftarPl($m2, [
            'periode_akademik' => 'Ganjil 2026/2027',
            'sks_ditempuh' => 102,
            'judul_kegiatan' => 'Analisis Tataniaga TBS Kelapa Sawit',
            'lokasi_pl_id' => $lokasi->id,
        ]);

        $this->assertSame(0, $lokasi->sisaKuotaPadaPeriode('Ganjil 2026/2027'));

        // Ke-3 ditolak karena kuota 2 sudah penuh
        try {
            $this->service->daftarPl($m3, [
                'periode_akademik' => 'Ganjil 2026/2027',
                'sks_ditempuh' => 105,
                'judul_kegiatan' => 'Pascapanen Sawit Rakyat',
                'lokasi_pl_id' => $lokasi->id,
            ]);
            $this->fail('Mahasiswa ke-3 seharusnya ditolak karena kuota lokasi PL penuh.');
        } catch (DomainException $e) {
            $this->assertStringContainsString('sudah penuh', mb_strtolower($e->getMessage()));
        }

        // Usulan lokasi baru oleh m3 -> status menunggu verifikasi
        $plUsulan = $this->service->daftarPl($m3, [
            'periode_akademik' => 'Ganjil 2026/2027',
            'sks_ditempuh' => 105,
            'judul_kegiatan' => 'Pengolahan Karet Remah di Koperasi Tani',
            'nama_instansi_baru' => 'Koperasi Tani Makmur Sintang',
            'sektor_baru' => 'AGB',
            'alamat_baru' => 'Jl. Lintas Sintang Km 12',
        ]);

        $this->assertSame(StatusVerifikasiLokasiPl::Menunggu, $plUsulan->lokasiPl->status_verifikasi);

        // Sebelum lokasi diverifikasi -> belum bisa ajukan surat pengantar PL
        try {
            $this->service->ajukanSuratPengantar($plUsulan, $m3);
            $this->fail('Surat pengantar seharusnya ditolak sebelum lokasi baru diverifikasi.');
        } catch (DomainException $e) {
            $this->assertStringContainsString('harus diverifikasi', mb_strtolower($e->getMessage()));
        }

        // Setelah diverifikasi -> pengajuan surat pengantar berhasil & terhubung ke Modul Persuratan
        $this->service->verifikasiLokasi($plUsulan->lokasiPl, $this->kaprodiAgt, StatusVerifikasiLokasiPl::Terverifikasi, 4);
        $surat = $this->service->ajukanSuratPengantar($plUsulan->fresh(), $m3);

        $this->assertInstanceOf(PengajuanSurat::class, $surat);
        $this->assertSame(JenisSuratInternal::PengantarPl, $surat->jenis_surat);
        $this->assertSame(StatusPraktikLapang::PengantarDiproses, $plUsulan->fresh()->status);
    }

    public function test_alur_dpl_logbook_seminar_pl_dan_penilaian_akhir(): void
    {
        $mhs = $this->makeUser('Mhs PL Lengkap', '221030001', UserRole::Mahasiswa, $this->prodiAgt);
        $lokasi = LokasiPl::create([
            'nama_instansi' => 'Balai Penelitian Tanaman Pangan',
            'sektor' => 'AGT',
            'alamat' => 'Kubu Raya',
            'kuota_periode' => 5,
            'status_verifikasi' => StatusVerifikasiLokasiPl::Terverifikasi,
        ]);

        $pl = $this->service->daftarPl($mhs, [
            'periode_akademik' => 'Genap 2026/2027',
            'sks_ditempuh' => 110,
            'judul_kegiatan' => 'Pengendalian Hama Terpadu Tanaman Cabai',
            'lokasi_pl_id' => $lokasi->id,
        ]);

        $this->service->unggahSuratBalasan($pl, $mhs, 'praktik_lapang/balasan/ok.pdf');
        $this->assertSame(StatusPraktikLapang::MenungguDpl, $pl->fresh()->status);

        $penugasan = $this->service->tetapkanDpl($pl->fresh(), $this->dosenDpl, $this->kaprodiAgt, false);

        // Admin Prodi terbitkan SK DPL sebelum DPL konfirmasi -> ditolak
        try {
            $this->service->terbitkanSkDpl($pl->fresh(), $this->adminAgt, 'SK-DPL/001/FPST/2026');
            $this->fail('Admin Prodi tidak boleh menerbitkan SK DPL sebelum DPL menyetujui kesediaan.');
        } catch (DomainException $e) {
            $this->assertStringContainsString('mengonfirmasi kesediaan', mb_strtolower($e->getMessage()));
        }

        // DPL konfirmasi via Admin Utama mandat atau update status penugasan
        $this->service->tetapkanDpl($pl->fresh(), $this->dosenDpl, $this->kaprodiAgt, true);
        $suratSk = $this->service->terbitkanSkDpl($pl->fresh(), $this->adminAgt, 'SK-DPL/001/FPST/2026');

        $this->assertInstanceOf(Surat::class, $suratSk);
        $this->assertSame('sk_dpl', $suratSk->jenis_surat);
        $this->assertSame(StatusPraktikLapang::Berjalan, $pl->fresh()->status);

        // Logbook & verifikasi
        $logbook = $this->service->tambahLogbook($pl->fresh(), $mhs, [
            'tanggal' => '2027-02-10',
            'aktivitas' => 'Pengamatan populasi kutu kebul',
            'uraian' => 'Melakukan pencatatan sampel 20 tanaman per petak percobaan.',
        ]);
        $verifiedLog = $this->service->verifikasiLogbook($logbook, $this->dosenDpl, 'Bagus, lanjutkan.');
        $this->assertTrue($verifiedLog->diverifikasi_dpl);

        // Jadwalkan Seminar PL & Penilaian Akhir
        $this->service->jadwalkanSeminarPl($pl->fresh(), $this->adminAgt, '2027-03-18', '09:00 - 10:30', 'Ruang Seminar FPST 1');
        $this->assertSame(StatusPraktikLapang::SeminarTerjadwal, $pl->fresh()->status);

        $selesai = $this->service->simpanPenilaianPl($pl->fresh(), $this->dosenDpl, 88.0, 92.0, 'praktik_lapang/sertifikat/sert.pdf', 'Lulus sangat baik');
        $this->assertSame(StatusPraktikLapang::Selesai, $selesai->status);
        $this->assertEquals(90.00, (float) $selesai->nilai_akhir);
    }

    private function makeUser(string $name, string $nim, UserRole $role, ProgramStudi $prodi): User
    {
        return User::create([
            'name' => $name,
            'email' => strtolower(str_replace([' ', '.'], '', $nim)) . '@pl-unit.fpst.test',
            'nomor_induk' => $nim,
            'password' => 'password',
            'role' => $role,
            'program_studi_id' => $prodi->id,
        ]);
    }
}
