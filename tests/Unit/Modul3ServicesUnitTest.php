<?php

namespace Tests\Unit;

use App\Enums\StatusPengajuan;
use App\Enums\UserRole;
use App\Models\PengajuanSkripsi;
use App\Models\ProgramStudi;
use App\Models\SeminarSkripsi;
use App\Models\SidangSkripsi;
use App\Models\User;
use App\Services\JadwalBentrokService;
use App\Services\JudulSimilarityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Modul3ServicesUnitTest extends TestCase
{
    use RefreshDatabase;

    private ProgramStudi $prodiAgt;
    private ProgramStudi $prodiAgb;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prodiAgt = ProgramStudi::create(['nama' => 'Agroteknologi', 'kode' => 'AGT']);
        $this->prodiAgb = ProgramStudi::create(['nama' => 'Agribisnis', 'kode' => 'AGB']);
    }

    public function test_judul_similarity_service_menemukan_kemiripan_lintas_angkatan_agt_dan_agb(): void
    {
        $service = app(JudulSimilarityService::class);

        $scoreIdentik = $service->calculateSimilarity(
            'Pengaruh Pemberian Pupuk Organik Cair Terhadap Pertumbuhan Bibit Kelapa Sawit!',
            'pengaruh pemberian pupuk organik cair terhadap pertumbuhan bibit kelapa sawit'
        );
        $this->assertSame(100.0, $scoreIdentik);

        $scoreBerbeda = $service->calculateSimilarity(
            'Pengaruh Dosis NPK dan Kompos Biochar Terhadap Produksi Cabai Merah',
            'Analisis Kelayakan Finansial Usaha Pengolahan Gula Aren di Bengkayang'
        );
        $this->assertLessThan(25.0, $scoreBerbeda);

        $mhsAgt = $this->makeUser('Mahasiswa AGT Repositori', '21100101', UserRole::Mahasiswa, $this->prodiAgt);
        $mhsAgb = $this->makeUser('Mahasiswa AGB Repositori', '21200102', UserRole::Mahasiswa, $this->prodiAgb);
        $mhsDitolak = $this->makeUser('Mahasiswa Judul Ditolak', '21100103', UserRole::Mahasiswa, $this->prodiAgt);

        $skripsiAgt = PengajuanSkripsi::create([
            'mahasiswa_id' => $mhsAgt->id,
            'program_studi_id' => $this->prodiAgt->id,
            'judul' => 'Pengaruh Pupuk Organik Cair Limbah Sawit Terhadap Pertumbuhan Bibit Kelapa Sawit',
            'file_proposal' => 'dummy/p1.pdf',
            'file_transkrip' => 'dummy/t1.pdf',
            'file_bukti_bayar' => 'dummy/b1.pdf',
            'status' => StatusPengajuan::Selesai,
        ]);

        PengajuanSkripsi::create([
            'mahasiswa_id' => $mhsAgb->id,
            'program_studi_id' => $this->prodiAgb->id,
            'judul' => 'Analisis Rantai Pasok Beras Merah Organik di Kabupaten Kapuas Hulu',
            'file_proposal' => 'dummy/p2.pdf',
            'file_transkrip' => 'dummy/t2.pdf',
            'file_bukti_bayar' => 'dummy/b2.pdf',
            'status' => StatusPengajuan::Selesai,
        ]);

        // Judul berstatus Ditolak tidak boleh muncul dalam hasil deteksi
        PengajuanSkripsi::create([
            'mahasiswa_id' => $mhsDitolak->id,
            'program_studi_id' => $this->prodiAgt->id,
            'judul' => 'Pengaruh Pupuk Organik Cair Limbah Sawit Pada Bibit Kelapa Sawit',
            'file_proposal' => 'dummy/p3.pdf',
            'file_transkrip' => 'dummy/t3.pdf',
            'file_bukti_bayar' => 'dummy/b3.pdf',
            'status' => StatusPengajuan::Ditolak,
        ]);

        $matches = $service->findSimilarTitles(
            'Pengaruh Pupuk Organik Cair Limbah Sawit Terhadap Pertumbuhan Tanaman Kelapa Sawit',
            excludeSkripsiId: null,
            minThreshold: 35.0,
            limit: 5
        );

        $this->assertCount(1, $matches);
        $this->assertSame($skripsiAgt->id, $matches[0]['skripsi_id']);
        $this->assertSame('AGT', $matches[0]['prodi_kode']);
        $this->assertGreaterThan(60.0, $matches[0]['skor_kemiripan']);

        // Pastikan excludeSkripsiId berfungsi untuk mengecualikan judul sendiri saat direview Kaprodi
        $excluded = $service->findSimilarTitles($skripsiAgt->judul, excludeSkripsiId: $skripsiAgt->id);
        $this->assertCount(0, $excluded);
    }

    public function test_jadwal_bentrok_service_parsing_waktu_overlap_dan_non_overlap(): void
    {
        $service = app(JadwalBentrokService::class);

        // Tumpang tindih
        $this->assertTrue($service->isTimeOverlapping('09:00 - 11:00', '10:00 - 12:00'));
        $this->assertTrue($service->isTimeOverlapping('09.30 WIB - Selesai', '10:00 - 11:30'));

        // Berimpit tepat di titik pergantian jam -> tidak dianggap bentrok
        $this->assertFalse($service->isTimeOverlapping('09:00 - 10:30', '10:30 - 12:00'));

        // Terpisah jauh
        $this->assertFalse($service->isTimeOverlapping('08:00 - 09:30', '13:30 - 15:30'));
    }

    public function test_jadwal_bentrok_service_deteksi_bentrok_ruangan_dan_dosen_lintas_seminar_sidang(): void
    {
        $service = app(JadwalBentrokService::class);

        $dosenA = $this->makeUser('Prof. Dr. Ir. Andi, M.P.', '00220001', UserRole::Dosen, $this->prodiAgt);
        $dosenB = $this->makeUser('Dr. Siti Rahma, S.P., M.Sc.', '00220002', UserRole::Dosen, $this->prodiAgb);
        $dosenC = $this->makeUser('Ir. Budi Santoso, M.P.', '00220003', UserRole::Dosen, $this->prodiAgt);

        $mhs1 = $this->makeUser('Mhs Seminar Bentrok', '22101011', UserRole::Mahasiswa, $this->prodiAgt);
        $mhs2 = $this->makeUser('Mhs Sidang Bentrok', '22201022', UserRole::Mahasiswa, $this->prodiAgb);

        $skripsi1 = PengajuanSkripsi::create([
            'mahasiswa_id' => $mhs1->id,
            'program_studi_id' => $this->prodiAgt->id,
            'judul' => 'Uji Aplikasi Mikoriza Pada Tanah Gambut',
            'file_proposal' => 'dummy/p.pdf',
            'file_transkrip' => 'dummy/t.pdf',
            'file_bukti_bayar' => 'dummy/b.pdf',
            'pembimbing_1_id' => $dosenA->id,
            'status' => StatusPengajuan::Selesai,
        ]);

        $seminar = SeminarSkripsi::create([
            'pengajuan_skripsi_id' => $skripsi1->id,
            'file_naskah_seminar' => 'dummy/sem.pdf',
            'file_acc_pembimbing' => 'dummy/acc.pdf',
            'file_bukti_bayar_seminar' => 'dummy/bbs.pdf',
            'file_toefl' => 'dummy/toefl.pdf',
            'penguji_seminar_id' => $dosenB->id,
            'tgl_seminar' => '2027-04-10',
            'jam_seminar' => '09:00 - 11:00',
            'ruangan' => 'Ruang Seminar Utama FPST',
            'status' => StatusPengajuan::Diproses,
        ]);

        $skripsi2 = PengajuanSkripsi::create([
            'mahasiswa_id' => $mhs2->id,
            'program_studi_id' => $this->prodiAgb->id,
            'judul' => 'Efisiensi Pemasaran Lada Putih Di Sanggau',
            'file_proposal' => 'dummy/p2.pdf',
            'file_transkrip' => 'dummy/t2.pdf',
            'file_bukti_bayar' => 'dummy/b2.pdf',
            'pembimbing_1_id' => $dosenB->id,
            'status' => StatusPengajuan::Selesai,
        ]);

        $sidang = SidangSkripsi::create([
            'pengajuan_skripsi_id' => $skripsi2->id,
            'file_naskah_sidang' => 'dummy/sid.pdf',
            'file_acc_sidang' => 'dummy/accs.pdf',
            'file_bebas_revisi_seminar' => 'dummy/brs.pdf',
            'file_bukti_bayar_sidang' => 'dummy/bbsid.pdf',
            'penguji_1_id' => $dosenC->id,
            'tgl_sidang' => '2027-04-10',
            'jam_sidang' => '13:30 - 15:30',
            'ruangan' => 'Ruang Sidang FPST A',
            'status' => StatusPengajuan::Diproses,
        ]);

        // 1. Bentrok ruangan (case-insensitive)
        $conflictRoom = $service->detectConflict('2027-04-10', '09:30 - 11:00', 'ruang seminar utama fpst', [$dosenC->id]);
        $this->assertNotNull($conflictRoom);
        $this->assertStringContainsString('Bentrok jadwal ruangan', $conflictRoom);

        // 2. Bentrok dosen dengan Sidang Skripsi di jam 14:00 (walaupun ruang berbeda)
        $conflictDosenSidang = $service->detectConflict('2027-04-10', '14:00 - 15:00', 'Ruang Seminar B', [$dosenC->id]);
        $this->assertNotNull($conflictDosenSidang);
        $this->assertStringContainsString('Bentrok jadwal dosen', $conflictDosenSidang);
        $this->assertStringContainsString('Sidang Skripsi', $conflictDosenSidang);

        // 3. Abaikan ID diri sendiri saat edit jadwal -> bebas bentrok
        $noConflictSelf = $service->detectConflict(
            '2027-04-10',
            '09:00 - 11:00',
            'Ruang Seminar Utama FPST',
            [$dosenA->id, $dosenB->id],
            ignoreSeminarId: $seminar->id
        );
        $this->assertNull($noConflictSelf);

        // 4. Jam berbeda setelah 11:00 - 12:30 -> bebas bentrok
        $noConflictDifferentTime = $service->detectConflict(
            '2027-04-10',
            '11:00 - 12:30',
            'Ruang Seminar Utama FPST',
            [$dosenA->id, $dosenB->id],
            ignoreSidangId: $sidang->id
        );
        $this->assertNull($noConflictDifferentTime);
    }

    private function makeUser(string $name, string $nim, UserRole $role, ProgramStudi $prodi): User
    {
        return User::create([
            'name' => $name,
            'email' => strtolower($nim) . '@fpst-unit3.test',
            'nomor_induk' => $nim,
            'password' => 'password',
            'role' => $role,
            'program_studi_id' => $prodi->id,
        ]);
    }
}
