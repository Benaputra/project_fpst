<?php

namespace Tests\Feature;

use App\Enums\PeranAudiensSeminar;
use App\Enums\StatusAudiensSeminar;
use App\Enums\StatusPengajuan;
use App\Enums\StatusPraktikLapang;
use App\Enums\StatusVerifikasiLokasiPl;
use App\Enums\UserRole;
use App\Jobs\KirimNotifikasiMassalJob;
use App\Models\LokasiPl;
use App\Models\Notifikasi;
use App\Models\PengajuanSkripsi;
use App\Models\PraktikLapang;
use App\Models\ProgramStudi;
use App\Models\SeminarAudiens;
use App\Models\SeminarSkripsi;
use App\Models\SidangSkripsi;
use App\Models\User;
use App\Services\JadwalPublikService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class Fase4PeakLoadAndSecurityHeadersTest extends TestCase
{
    use RefreshDatabase;

    private ProgramStudi $prodiAgt;
    private ProgramStudi $prodiAgb;
    private User $dosen;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prodiAgt = ProgramStudi::create(['nama' => 'Agroteknologi', 'kode' => 'AGT']);
        $this->prodiAgb = ProgramStudi::create(['nama' => 'Agribisnis', 'kode' => 'AGB']);
        $this->dosen = User::create([
            'name' => 'Prof. Dr. Ir. Slamet, M.P.',
            'email' => 'slamet.peak@fpst.test',
            'nomor_induk' => 'DSN-PEAK-01',
            'password' => 'password',
            'role' => UserRole::Dosen,
            'program_studi_id' => $this->prodiAgt->id,
        ]);
    }

    public function test_http_security_headers_terpasang_pada_respons_publik_dan_terautentikasi(): void
    {
        $publicRes = $this->get(route('jadwal.publik'));
        $publicRes->assertOk();
        $publicRes->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $publicRes->assertHeader('X-Content-Type-Options', 'nosniff');
        $publicRes->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $publicRes->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        $this->assertNotNull($publicRes->headers->get('Content-Security-Policy'));

        $authRes = $this->actingAs($this->dosen)->get(route('dashboard'));
        $authRes->assertOk();
        $authRes->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $authRes->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_jadwal_publik_bebas_n_plus_1_dan_cache_hit_nol_query(): void
    {
        $lokasi = LokasiPl::create([
            'nama_instansi' => 'Balai Penelitian Karet Sembawa',
            'sektor' => 'AGT/AGB',
            'alamat' => 'Kubu Raya',
            'kuota_periode' => 20,
            'status_verifikasi' => StatusVerifikasiLokasiPl::Terverifikasi,
        ]);

        // Buat 12 Seminar Skripsi (masing-masing dengan 3 audiens), 6 Sidang Skripsi, dan 6 Seminar PL = 24 agenda
        $base = Carbon::parse('2027-06-01');
        $pengamat = $this->makeMahasiswa('Pengamat Query Budget 1', '230001001', $this->prodiAgt);
        $pengamat2 = $this->makeMahasiswa('Pengamat Query Budget 2', '230001002', $this->prodiAgb);

        for ($i = 0; $i < 12; $i++) {
            $prodi = $i % 2 === 0 ? $this->prodiAgt : $this->prodiAgb;
            $mhs = $this->makeMahasiswa("Penyaji Peak {$i}", sprintf('2301%04d', $i), $prodi);
            $skripsi = PengajuanSkripsi::create([
                'mahasiswa_id' => $mhs->id,
                'program_studi_id' => $prodi->id,
                'judul' => "Uji Agronomi dan Agribisnis Seri Peak #{$i}",
                'file_proposal' => 'dummy/p.pdf',
                'file_transkrip' => 'dummy/t.pdf',
                'file_bukti_bayar' => 'dummy/b.pdf',
                'pembimbing_1_id' => $this->dosen->id,
                'status' => StatusPengajuan::Selesai,
            ]);

            $sem = SeminarSkripsi::create([
                'pengajuan_skripsi_id' => $skripsi->id,
                'file_naskah_seminar' => 'dummy/ns.pdf',
                'file_acc_pembimbing' => 'dummy/ac.pdf',
                'file_bukti_bayar_seminar' => 'dummy/bb.pdf',
                'file_toefl' => 'dummy/tf.pdf',
                'penguji_seminar_id' => $this->dosen->id,
                'tgl_seminar' => $base->copy()->addDays($i)->toDateString(),
                'jam_seminar' => '09:00 - 10:30',
                'ruangan' => 'Ruang AGT/AGB',
                'kuota_penonton' => 35,
                'kuota_penyanggah' => 2,
                'status' => StatusPengajuan::Diproses,
            ]);

            SeminarAudiens::create([
                'seminar_skripsi_id' => $sem->id,
                'mahasiswa_id' => $pengamat->id,
                'peran' => PeranAudiensSeminar::Penyanggah,
                'status' => StatusAudiensSeminar::Terdaftar,
                'waktu_daftar' => now(),
            ]);
            SeminarAudiens::create([
                'seminar_skripsi_id' => $sem->id,
                'mahasiswa_id' => $pengamat2->id,
                'peran' => PeranAudiensSeminar::Peserta,
                'status' => StatusAudiensSeminar::Hadir,
                'waktu_daftar' => now(),
            ]);

            if ($i < 6) {
                SidangSkripsi::create([
                    'pengajuan_skripsi_id' => $skripsi->id,
                    'file_naskah_sidang' => 'dummy/s.pdf',
                    'file_acc_sidang' => 'dummy/as.pdf',
                    'file_bebas_revisi_seminar' => 'dummy/br.pdf',
                    'file_bukti_bayar_sidang' => 'dummy/bbs.pdf',
                    'penguji_1_id' => $this->dosen->id,
                    'tgl_sidang' => $base->copy()->addDays($i + 15)->toDateString(),
                    'jam_sidang' => '13:30 - 15:00',
                    'ruangan' => 'Ruang Sidang FPST',
                    'status' => StatusPengajuan::Diproses,
                ]);

                PraktikLapang::create([
                    'mahasiswa_id' => $mhs->id,
                    'program_studi_id' => $prodi->id,
                    'lokasi_pl_id' => $lokasi->id,
                    'periode_akademik' => 'Ganjil 2026/2027',
                    'sks_ditempuh' => 100,
                    'judul_kegiatan' => "Seminar Praktik Lapang Peak #{$i}",
                    'dpl_id' => $this->dosen->id,
                    'tgl_seminar_pl' => $base->copy()->addDays($i + 25)->toDateString(),
                    'jam_seminar_pl' => '08:30 - 10:00',
                    'ruangan_seminar_pl' => 'Ruang Seminar B',
                    'status' => StatusPraktikLapang::SeminarTerjadwal,
                ]);
            }
        }

        JadwalPublikService::clearCache();
        $service = app(JadwalPublikService::class);

        // 1. Cache Miss (build 24 entries): harus bebas N+1 (jumlah query konstan << 24)
        DB::flushQueryLog();
        DB::enableQueryLog();
        $entries = $service->getAllScheduledEntries();
        $coldQueries = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertCount(24, $entries);
        $this->assertLessThanOrEqual(20, $coldQueries, "Query count terlalu tinggi ({$coldQueries}); indikasi kebocoran N+1.");

        // 2. Cache Hit: panggilan berikutnya menghasilkan 0 query SQL terhadap tabel jadwal
        DB::flushQueryLog();
        DB::enableQueryLog();
        $cachedEntries = $service->getAllScheduledEntries();
        $warmQueries = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertCount(24, $cachedEntries);
        $this->assertSame(0, $warmQueries);
    }

    public function test_simulasi_beban_puncak_perebutan_kuota_seminar_35_dan_lokasi_pl_terbatas(): void
    {
        $penyaji = $this->makeMahasiswa('Penyaji Simulasi Peak', '231000001', $this->prodiAgt);
        $skripsi = PengajuanSkripsi::create([
            'mahasiswa_id' => $penyaji->id,
            'program_studi_id' => $this->prodiAgt->id,
            'judul' => 'Optimasi Pemupukan Kelapa Sawit Pada Lahan Sulfat Masam',
            'file_proposal' => 'dummy/p.pdf',
            'file_transkrip' => 'dummy/t.pdf',
            'file_bukti_bayar' => 'dummy/b.pdf',
            'pembimbing_1_id' => $this->dosen->id,
            'status' => StatusPengajuan::Selesai,
        ]);

        $seminar = SeminarSkripsi::create([
            'pengajuan_skripsi_id' => $skripsi->id,
            'file_naskah_seminar' => 'dummy/ns.pdf',
            'file_acc_pembimbing' => 'dummy/ac.pdf',
            'file_bukti_bayar_seminar' => 'dummy/bb.pdf',
            'file_toefl' => 'dummy/tf.pdf',
            'penguji_seminar_id' => $this->dosen->id,
            'tgl_seminar' => '2027-07-10',
            'jam_seminar' => '09:00 - 11:00',
            'ruangan' => 'Aula FPST UPB',
            'kuota_penonton' => 35,
            'kuota_penyanggah' => 2,
            'pendaftaran_audiens_dibuka' => true,
            'status' => StatusPengajuan::Diproses,
        ]);

        // Simulasi 45 mahasiswa berebut kursi seminar berkapasitas 35
        $suksesAudiens = 0;
        $gagalAudiens = 0;
        for ($i = 1; $i <= 45; $i++) {
            $prodi = $i % 2 === 0 ? $this->prodiAgb : $this->prodiAgt;
            $mhs = $this->makeMahasiswa("Racer Audiens {$i}", sprintf('2320%04d', $i), $prodi);

            $res = $this->actingAs($mhs)
                ->post(route('mahasiswa.audiens.daftar', $seminar), ['peran' => 'peserta']);

            $res->assertRedirect();
            if ($res->getSession()->has('success')) {
                $suksesAudiens++;
            } else {
                $gagalAudiens++;
            }
        }

        $this->assertSame(35, $suksesAudiens);
        $this->assertSame(10, $gagalAudiens);
        $this->assertSame(35, SeminarAudiens::where('seminar_skripsi_id', $seminar->id)->aktif()->count());

        // Simulasi 12 mahasiswa berebut tempat PL berkuota 5
        $lokasi = LokasiPl::create([
            'nama_instansi' => 'Pusat Riset Agribisnis Khatulistiwa',
            'sektor' => 'AGB',
            'alamat' => 'Pontianak',
            'kuota_periode' => 5,
            'status_verifikasi' => StatusVerifikasiLokasiPl::Terverifikasi,
        ]);

        $suksesPl = 0;
        $gagalPl = 0;
        for ($i = 1; $i <= 12; $i++) {
            $mhs = $this->makeMahasiswa("Racer PL {$i}", sprintf('2330%04d', $i), $this->prodiAgb);
            $res = $this->actingAs($mhs)->post(route('mahasiswa.pl.daftar'), [
                'periode_akademik' => 'Ganjil 2026/2027',
                'sks_ditempuh' => 100,
                'judul_kegiatan' => "Riset Agribisnis Kemitraan Petani Ke-{$i}",
                'lokasi_pl_id' => $lokasi->id,
            ]);

            if ($res->getSession()->has('success')) {
                $suksesPl++;
            } else {
                $gagalPl++;
            }
        }

        $this->assertSame(5, $suksesPl);
        $this->assertSame(7, $gagalPl);
        $this->assertSame(5, PraktikLapang::where('lokasi_pl_id', $lokasi->id)->count());
    }

    public function test_kirim_notifikasi_massal_job_dispatch_dan_eksekusi_deduplikasi(): void
    {
        $u1 = $this->makeMahasiswa('Target Notif 1', '23400001', $this->prodiAgt);
        $u2 = $this->makeMahasiswa('Target Notif 2', '23400002', $this->prodiAgb);

        // 1. Uji dispatch ke Queue
        Queue::fake();
        Notifikasi::kirimMassalAsync([$u1->id, $u2->id, $u1->id], 'Pengumuman Peak', 'Uji antrean notifikasi.', '/jadwal');
        Queue::assertPushed(KirimNotifikasiMassalJob::class);

        // 2. Uji eksekusi handler job -> deduplikasi user_id dan bulk insert bersih
        $job = new KirimNotifikasiMassalJob([$u1->id, $u2->id, $u1->id], 'Pengumuman Peak', 'Uji antrean notifikasi.', '/jadwal');
        $job->handle();

        $this->assertSame(2, Notifikasi::where('judul', 'Pengumuman Peak')->count());
        $this->assertDatabaseHas('notifikasi', ['user_id' => $u1->id, 'judul' => 'Pengumuman Peak']);
        $this->assertDatabaseHas('notifikasi', ['user_id' => $u2->id, 'judul' => 'Pengumuman Peak']);
    }

    private function makeMahasiswa(string $name, string $nim, ProgramStudi $prodi): User
    {
        return User::create([
            'name' => $name,
            'email' => strtolower(str_replace([' ', '.'], '', $nim)) . '@fpst-peak.test',
            'nomor_induk' => $nim,
            'password' => 'password',
            'role' => UserRole::Mahasiswa,
            'program_studi_id' => $prodi->id,
        ]);
    }
}
