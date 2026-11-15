<?php

namespace Tests\Unit;

use App\Enums\PeranAudiensSeminar;
use App\Enums\StatusAudiensSeminar;
use App\Enums\StatusPengajuan;
use App\Enums\UserRole;
use App\Models\PengajuanSkripsi;
use App\Models\ProgramStudi;
use App\Models\SeminarAudiens;
use App\Models\SeminarSkripsi;
use App\Models\User;
use App\Services\SeminarAudiensService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SeminarAudiensRulesUnitTest extends TestCase
{
    use RefreshDatabase;

    private SeminarAudiensService $service;
    private ProgramStudi $prodiAgt;
    private ProgramStudi $prodiAgb;
    private User $dosenModerator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(SeminarAudiensService::class);
        $this->prodiAgt = ProgramStudi::create(['nama' => 'Agroteknologi', 'kode' => 'AGT']);
        $this->prodiAgb = ProgramStudi::create(['nama' => 'Agribisnis', 'kode' => 'AGB']);

        $this->dosenModerator = User::create([
            'name' => 'Dr. Ir. Hendra Wijaya, M.Si.',
            'email' => 'hendra.unit@fpst-upb.test',
            'nomor_induk' => '00112201',
            'password' => 'password',
            'role' => UserRole::Dosen,
            'program_studi_id' => $this->prodiAgt->id,
        ]);
    }

    public function test_mahasiswa_maksimal_menonton_12_kali_termasuk_maksimal_2_penyanggah_selebihnya_peserta(): void
    {
        $mahasiswaPengamat = $this->makeMahasiswa('Mhs Pengamat AGT', '22101001', $this->prodiAgt);
        $seminars = $this->makeScheduledSeminars(13, '2026-12-01');

        // 1-2: Daftar sebagai Penyanggah (maksimal 2x)
        $r1 = $this->service->daftar($seminars[0], $mahasiswaPengamat, PeranAudiensSeminar::Penyanggah);
        $r2 = $this->service->daftar($seminars[1], $mahasiswaPengamat, PeranAudiensSeminar::Penyanggah);

        $this->assertSame(PeranAudiensSeminar::Penyanggah, $r1->peran);
        $this->assertSame(PeranAudiensSeminar::Penyanggah, $r2->peran);

        // Ke-3 sebagai Penyanggah harus DITOLAK
        try {
            $this->service->daftar($seminars[2], $mahasiswaPengamat, PeranAudiensSeminar::Penyanggah);
            $this->fail('Pendaftaran ke-3 sebagai Penyanggah seharusnya ditolak oleh aturan batas 2x Penyanggah.');
        } catch (DomainException $e) {
            $this->assertStringContainsString('penyanggah adalah 2 kali', mb_strtolower($e->getMessage()));
        }

        // Ke-3 sampai ke-12 (10 sesi) sebagai Peserta harus BERHASIL -> Total = 12
        for ($i = 2; $i < 12; $i++) {
            $rec = $this->service->daftar($seminars[$i], $mahasiswaPengamat, PeranAudiensSeminar::Peserta);
            $this->assertSame(PeranAudiensSeminar::Peserta, $rec->peran);
        }

        $ringkasan = $this->service->ringkasanKartuKendali($mahasiswaPengamat);
        $this->assertSame(12, $ringkasan['maks_total']);
        $this->assertSame(2, $ringkasan['maks_penyanggah']);
        $this->assertSame(12, $ringkasan['total_terpakai']);
        $this->assertSame(0, $ringkasan['sisa_kuota_tonton']);
        $this->assertSame(2, $ringkasan['penyanggah_terpakai']);
        $this->assertSame(0, $ringkasan['sisa_kuota_penyanggah']);
        $this->assertSame(10, $ringkasan['peserta_terpakai']);

        // Ke-13 sebagai Peserta harus DITOLAK (batas total 12x)
        try {
            $this->service->daftar($seminars[12], $mahasiswaPengamat, PeranAudiensSeminar::Peserta);
            $this->fail('Pendaftaran ke-13 seharusnya ditolak oleh aturan batas maksimal 12x menonton.');
        } catch (DomainException $e) {
            $this->assertStringContainsString('maksimal menonton seminar sebanyak 12 kali', mb_strtolower($e->getMessage()));
        }

        $this->assertSame(12, SeminarAudiens::where('mahasiswa_id', $mahasiswaPengamat->id)->count());
    }

    public function test_kuota_penonton_per_sesi_seminar_maksimal_35_orang_termasuk_penyanggah(): void
    {
        $seminar = $this->makeScheduledSeminars(1, '2026-12-20')[0];
        // Simulasikan nilai DB mencoba melebihi 35 agar terbukti tetap di-clamp sistem ke 35
        $seminar->update(['kuota_penonton' => 50, 'kuota_penyanggah' => 2]);

        $this->assertSame(35, $seminar->fresh()->kapasitasEfektif());

        $mahasiswas = [];
        for ($i = 1; $i <= 36; $i++) {
            $prodi = $i % 2 === 0 ? $this->prodiAgb : $this->prodiAgt;
            $mahasiswas[] = $this->makeMahasiswa("Penonton Ke-{$i}", sprintf('2220%04d', $i), $prodi);
        }

        // 2 penyanggah + 33 peserta = 35 kursi penuh
        $this->service->daftar($seminar, $mahasiswas[0], PeranAudiensSeminar::Penyanggah);
        $this->service->daftar($seminar, $mahasiswas[1], PeranAudiensSeminar::Penyanggah);

        for ($i = 2; $i < 35; $i++) {
            $this->service->daftar($seminar, $mahasiswas[$i], PeranAudiensSeminar::Peserta);
        }

        $this->assertSame(35, $seminar->fresh()->jumlahAudiensAktif());
        $this->assertSame(0, $seminar->fresh()->sisaKuotaPenonton());

        // Orang ke-36 harus DITOLAK
        try {
            $this->service->daftar($seminar, $mahasiswas[35], PeranAudiensSeminar::Peserta);
            $this->fail('Pendaftar ke-36 seharusnya ditolak karena kapasitas sesi maksimal 35 orang.');
        } catch (DomainException $e) {
            $this->assertStringContainsString('maksimal 35 orang', mb_strtolower($e->getMessage()));
        }

        $this->assertSame(35, SeminarAudiens::where('seminar_skripsi_id', $seminar->id)->aktif()->count());
    }

    public function test_slot_penyanggah_pada_sesi_seminar_dibatasi_oleh_kuota_penyanggah_sesi(): void
    {
        $seminar = $this->makeScheduledSeminars(1, '2026-12-22')[0];
        $seminar->update(['kuota_penonton' => 35, 'kuota_penyanggah' => 2]);

        $m1 = $this->makeMahasiswa('Calon Penyanggah 1', '22300001', $this->prodiAgt);
        $m2 = $this->makeMahasiswa('Calon Penyanggah 2', '22300002', $this->prodiAgb);
        $m3 = $this->makeMahasiswa('Calon Penyanggah 3', '22300003', $this->prodiAgt);

        $this->service->daftar($seminar, $m1, PeranAudiensSeminar::Penyanggah);
        $this->service->daftar($seminar, $m2, PeranAudiensSeminar::Penyanggah);

        try {
            $this->service->daftar($seminar, $m3, PeranAudiensSeminar::Penyanggah);
            $this->fail('Penyanggah ke-3 pada sesi dengan kuota_penyanggah=2 seharusnya ditolak.');
        } catch (DomainException $e) {
            $this->assertStringContainsString('slot penyanggah pada sesi seminar ini sudah penuh', mb_strtolower($e->getMessage()));
        }

        // Masih bisa mendaftar sebagai Peserta karena kursi 35 orang masih tersisa 33
        $recPeserta = $this->service->daftar($seminar, $m3, PeranAudiensSeminar::Peserta);
        $this->assertSame(PeranAudiensSeminar::Peserta, $recPeserta->peran);
    }

    public function test_pembatalan_mengembalikan_kuota_pribadi_dan_bisa_daftar_ulang(): void
    {
        $mhs = $this->makeMahasiswa('Mhs Batal AGT', '22400001', $this->prodiAgt);
        $seminars = $this->makeScheduledSeminars(12, '2027-01-05');

        $records = [];
        for ($i = 0; $i < 12; $i++) {
            $peran = $i === 0 ? PeranAudiensSeminar::Penyanggah : PeranAudiensSeminar::Peserta;
            $records[] = $this->service->daftar($seminars[$i], $mhs, $peran);
        }

        $this->assertSame(12, $this->service->ringkasanKartuKendali($mhs)['total_terpakai']);

        // Batalkan sesi pertama (yang semula penyanggah)
        $cancelled = $this->service->batalkan($records[0], $mhs);
        $this->assertSame(StatusAudiensSeminar::Dibatalkan, $cancelled->status);

        $summaryAfterCancel = $this->service->ringkasanKartuKendali($mhs);
        $this->assertSame(11, $summaryAfterCancel['total_terpakai']);
        $this->assertSame(1, $summaryAfterCancel['sisa_kuota_tonton']);
        $this->assertSame(0, $summaryAfterCancel['penyanggah_terpakai']);
        $this->assertSame(2, $summaryAfterCancel['sisa_kuota_penyanggah']);

        // Bisa mendaftar kembali di sesi yang sama (meng-update baris dibatalkan tanpa melanggar unique index)
        $reRegistered = $this->service->daftar($seminars[0], $mhs, PeranAudiensSeminar::Peserta);
        $this->assertSame(StatusAudiensSeminar::Terdaftar, $reRegistered->status);
        $this->assertSame(PeranAudiensSeminar::Peserta, $reRegistered->peran);
        $this->assertSame(12, $this->service->ringkasanKartuKendali($mhs)['total_terpakai']);
    }

    public function test_larangan_daftar_seminar_sendiri_ganda_bentrok_jam_dan_non_mahasiswa(): void
    {
        $seminars = $this->makeScheduledSeminars(2, '2027-02-01', sameDateTime: true);
        $pemilikSeminar = $seminars[0]->pengajuanSkripsi->mahasiswa;
        $mhsPengamat = $this->makeMahasiswa('Mhs Pengamat AGB', '22500001', $this->prodiAgb);

        // 1. Daftar pada seminar milik sendiri
        try {
            $this->service->daftar($seminars[0], $pemilikSeminar, PeranAudiensSeminar::Peserta);
            $this->fail('Mahasiswa pemilik seminar tidak boleh mendaftar pada seminarnya sendiri.');
        } catch (DomainException $e) {
            $this->assertStringContainsString('seminar anda sendiri', mb_strtolower($e->getMessage()));
        }

        // 2. Non-mahasiswa ditolak
        try {
            $this->service->daftar($seminars[0], $this->dosenModerator, PeranAudiensSeminar::Peserta);
            $this->fail('Akun non-mahasiswa tidak boleh mendaftar sebagai audiens.');
        } catch (DomainException $e) {
            $this->assertStringContainsString('hanya akun mahasiswa', mb_strtolower($e->getMessage()));
        }

        // 3. Daftar ganda pada seminar yang sama ditolak
        $this->service->daftar($seminars[0], $mhsPengamat, PeranAudiensSeminar::Peserta);
        try {
            $this->service->daftar($seminars[0], $mhsPengamat, PeranAudiensSeminar::Peserta);
            $this->fail('Pendaftaran ganda pada sesi seminar yang sama seharusnya ditolak.');
        } catch (DomainException $e) {
            $this->assertStringContainsString('sudah terdaftar pada sesi seminar ini', mb_strtolower($e->getMessage()));
        }

        // 4. Bentrok jam dengan seminar lain di tanggal dan jam bertumpukan
        try {
            $this->service->daftar($seminars[1], $mhsPengamat, PeranAudiensSeminar::Peserta);
            $this->fail('Pendaftaran seminar pada waktu yang bertabrakan seharusnya ditolak.');
        } catch (DomainException $e) {
            $this->assertStringContainsString('bertabrakan dengan sesi seminar lain', mb_strtolower($e->getMessage()));
        }
    }

    public function test_validasi_presensi_moderator_dan_qr_token_serta_pemenuhan_syarat_12x_seminar(): void
    {
        config(['fpst.seminar_audiens.enforce_syarat_seminar' => true]);

        $mhs = $this->makeMahasiswa('Mhs Target Syarat', '22600001', $this->prodiAgt);
        $seminars = $this->makeScheduledSeminars(12, '2027-03-01');

        $daftarAudiens = [];
        for ($i = 0; $i < 12; $i++) {
            $peran = $i < 2 ? PeranAudiensSeminar::Penyanggah : PeranAudiensSeminar::Peserta;
            $daftarAudiens[] = $this->service->daftar($seminars[$i], $mhs, $peran);
        }

        // Masih berstatus Terdaftar (belum divalidasi Hadir) -> belum memenuhi syarat
        $this->assertFalse($this->service->mahasiswaMemenuhiSyaratSeminar($mhs));

        // 11 divalidasi oleh dosen moderator -> masih 11 hadir (kurang 1)
        for ($i = 0; $i < 11; $i++) {
            $this->service->validasiPresensi($daftarAudiens[$i], $this->dosenModerator, StatusAudiensSeminar::Hadir, 'Hadir penuh');
        }
        $this->assertFalse($this->service->mahasiswaMemenuhiSyaratSeminar($mhs));

        // Token QR salah pada ke-12 harus ditolak
        try {
            $this->service->validasiPresensiDenganToken($daftarAudiens[11], $mhs, 'TOKEN-SALAH');
            $this->fail('Token QR presensi yang salah seharusnya ditolak.');
        } catch (DomainException $e) {
            $this->assertStringContainsString('tidak valid', mb_strtolower($e->getMessage()));
        }

        // Token QR benar pada ke-12 -> total_hadir = 12 (2 penyanggah + 10 peserta) -> MEMENUHI SYARAT
        $tokenAsli = (string) $seminars[11]->fresh()->token_presensi;
        $this->service->validasiPresensiDenganToken($daftarAudiens[11], $mhs, strtolower($tokenAsli));

        $summary = $this->service->ringkasanKartuKendali($mhs);
        $this->assertSame(12, $summary['total_hadir']);
        $this->assertSame(2, $summary['penyanggah_hadir']);
        $this->assertSame(10, $summary['peserta_hadir']);
        $this->assertTrue($summary['memenuhi_syarat_seminar']);
        $this->assertTrue($this->service->mahasiswaMemenuhiSyaratSeminar($mhs));

        // Jika 1 sesi diubah ke Tidak Hadir -> turun jadi 11 -> kembali tidak memenuhi syarat
        $this->service->validasiPresensi($daftarAudiens[5], $this->dosenModerator, StatusAudiensSeminar::TidakHadir, 'Meninggalkan ruangan');
        $this->assertFalse($this->service->mahasiswaMemenuhiSyaratSeminar($mhs));
    }

    private function makeMahasiswa(string $name, string $nim, ProgramStudi $prodi): User
    {
        return User::create([
            'name' => $name,
            'email' => strtolower(str_replace([' ', '.'], '', $nim)) . '@mhs-unit.fpst.test',
            'nomor_induk' => $nim,
            'password' => 'password',
            'role' => UserRole::Mahasiswa,
            'program_studi_id' => $prodi->id,
        ]);
    }

    /**
     * @return array<int, SeminarSkripsi>
     */
    private function makeScheduledSeminars(int $count, string $startDate, bool $sameDateTime = false): array
    {
        $result = [];
        $base = Carbon::parse($startDate);

        for ($i = 0; $i < $count; $i++) {
            $prodi = $i % 2 === 0 ? $this->prodiAgt : $this->prodiAgb;
            $penyaji = $this->makeMahasiswa("Penyaji {$startDate}-{$i}", sprintf('21%04d%02d', $base->dayOfYear, $i), $prodi);

            $judul = $prodi->kode === 'AGT'
                ? "Respon Pertumbuhan Tanaman Padi Varietas Lokal Terhadap Pupuk Hayati Uji #{$i}"
                : "Analisis Pendapatan Usahatani Kelapa Sawit Rakyat Pola Kemitraan #{$i}";

            $skripsi = PengajuanSkripsi::create([
                'mahasiswa_id' => $penyaji->id,
                'program_studi_id' => $prodi->id,
                'judul' => $judul,
                'abstrak' => 'Abstrak penelitian FPST Universitas Panca Bhakti.',
                'file_proposal' => 'dummy/prop.pdf',
                'file_transkrip' => 'dummy/tr.pdf',
                'file_bukti_bayar' => 'dummy/bb.pdf',
                'pembimbing_1_id' => $this->dosenModerator->id,
                'status' => StatusPengajuan::Selesai,
            ]);

            $tgl = $sameDateTime ? $base->toDateString() : $base->copy()->addDays($i)->toDateString();

            $result[] = SeminarSkripsi::create([
                'pengajuan_skripsi_id' => $skripsi->id,
                'file_naskah_seminar' => 'dummy/sem.pdf',
                'file_acc_pembimbing' => 'dummy/acc.pdf',
                'file_bukti_bayar_seminar' => 'dummy/bbsem.pdf',
                'file_toefl' => 'dummy/toefl.pdf',
                'penguji_seminar_id' => $this->dosenModerator->id,
                'tgl_seminar' => $tgl,
                'jam_seminar' => '09:00 - 10:30',
                'ruangan' => 'Ruang Seminar FPST ' . (($i % 3) + 1),
                'kuota_penonton' => 35,
                'kuota_penyanggah' => 2,
                'pendaftaran_audiens_dibuka' => true,
                'status' => StatusPengajuan::Diproses,
            ]);
        }

        return $result;
    }
}
