<?php

namespace Tests\Feature;

use App\Enums\HasilEvaluasiAkademik;
use App\Enums\PeranAudiensSeminar;
use App\Enums\StatusAudiensSeminar;
use App\Enums\StatusPengajuan;
use App\Enums\StatusPenugasanDosen;
use App\Enums\UserRole;
use App\Models\PengajuanSkripsi;
use App\Models\PenugasanDosen;
use App\Models\ProgramStudi;
use App\Models\SeminarAudiens;
use App\Models\SeminarSkripsi;
use App\Models\SidangSkripsi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class Fase2AudiensAndSkripsiEnhancementTest extends TestCase
{
    use RefreshDatabase;

    private ProgramStudi $prodiAgt;
    private ProgramStudi $prodiAgb;
    private User $adminAgt;
    private User $adminAgb;
    private User $kaprodiAgt;
    private User $dosenAgt1;
    private User $dosenAgt2;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        $this->prodiAgt = ProgramStudi::create(['nama' => 'Agroteknologi', 'kode' => 'AGT']);
        $this->prodiAgb = ProgramStudi::create(['nama' => 'Agribisnis', 'kode' => 'AGB']);

        $this->adminAgt = $this->createUser('Admin Prodi AGT', 'ADM-AGT-01', UserRole::AdminProdi, $this->prodiAgt);
        $this->adminAgb = $this->createUser('Admin Prodi AGB', 'ADM-AGB-01', UserRole::AdminProdi, $this->prodiAgb);
        $this->kaprodiAgt = $this->createUser('Kaprodi AGT', 'KAP-AGT-01', UserRole::Kaprodi, $this->prodiAgt);
        $this->dosenAgt1 = $this->createUser('Dr. Ir. Rahman, M.P.', 'DSN-AGT-01', UserRole::Dosen, $this->prodiAgt);
        $this->dosenAgt2 = $this->createUser('Ir. Sari Dewi, M.Si.', 'DSN-AGT-02', UserRole::Dosen, $this->prodiAgt);
    }

    public function test_http_pendaftaran_audiens_batas_12x_dan_2x_penyanggah_serta_presensi_qr(): void
    {
        $mhsPengamat = $this->createUser('Budi Mahasiswa AGT', '221010001', UserRole::Mahasiswa, $this->prodiAgt);
        $seminars = $this->createScheduledSeminars(13, '2026-12-02');

        // Halaman Kartu Kendali mahasiswa dapat diakses
        $this->actingAs($mhsPengamat)
            ->get(route('mahasiswa.audiens.index'))
            ->assertOk()
            ->assertSee('Kartu Kendali');

        // Daftar Penyanggah #1 dan #2 berhasil
        $this->actingAs($mhsPengamat)
            ->post(route('mahasiswa.audiens.daftar', $seminars[0]), ['peran' => 'penyanggah'])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->actingAs($mhsPengamat)
            ->post(route('mahasiswa.audiens.daftar', $seminars[1]), ['peran' => 'penyanggah'])
            ->assertRedirect()
            ->assertSessionHas('success');

        // Daftar Penyanggah #3 harus gagal via HTTP
        $this->actingAs($mhsPengamat)
            ->post(route('mahasiswa.audiens.daftar', $seminars[2]), ['peran' => 'penyanggah'])
            ->assertRedirect()
            ->assertSessionHas('error');

        // Sisa 10 pendaftaran (sesi 3..12) sebagai Peserta berhasil (total = 12)
        for ($i = 2; $i < 12; $i++) {
            $this->actingAs($mhsPengamat)
                ->post(route('mahasiswa.audiens.daftar', $seminars[$i]), ['peran' => 'peserta'])
                ->assertRedirect()
                ->assertSessionHas('success');
        }

        // Ke-13 ditolak oleh aturan maks 12 tonton
        $this->actingAs($mhsPengamat)
            ->post(route('mahasiswa.audiens.daftar', $seminars[12]), ['peran' => 'peserta'])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame(12, SeminarAudiens::where('mahasiswa_id', $mhsPengamat->id)->aktif()->count());

        // Presensi melalui Admin Prodi AGT + presensi mandiri via Token QR
        $audiensPertama = SeminarAudiens::where('mahasiswa_id', $mhsPengamat->id)
            ->where('seminar_skripsi_id', $seminars[0]->id)
            ->firstOrFail();

        $this->actingAs($this->adminAgt)
            ->post(route('kelola.audiens.validasi', $audiensPertama), [
                'status' => StatusAudiensSeminar::Hadir->value,
                'catatan' => 'Bertanya aktif sebagai penyanggah',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(StatusAudiensSeminar::Hadir, $audiensPertama->fresh()->status);

        $audiensKedua = SeminarAudiens::where('mahasiswa_id', $mhsPengamat->id)
            ->where('seminar_skripsi_id', $seminars[1]->id)
            ->firstOrFail();

        $token = (string) $seminars[1]->fresh()->token_presensi;
        $this->actingAs($mhsPengamat)
            ->post(route('mahasiswa.audiens.presensi-mandiri', $audiensKedua), ['token_presensi' => $token])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(StatusAudiensSeminar::Hadir, $audiensKedua->fresh()->status);

        // Uji batal pendaftaran di sesi ke-12 lalu daftar lagi di sesi ke-13
        $audiensDuaBelas = SeminarAudiens::where('mahasiswa_id', $mhsPengamat->id)
            ->where('seminar_skripsi_id', $seminars[11]->id)
            ->firstOrFail();

        $this->actingAs($mhsPengamat)
            ->delete(route('mahasiswa.audiens.batalkan', $audiensDuaBelas))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->actingAs($mhsPengamat)
            ->post(route('mahasiswa.audiens.daftar', $seminars[12]), ['peran' => 'peserta'])
            ->assertRedirect()
            ->assertSessionHas('success');
    }

    public function test_http_kuota_sesi_maksimal_35_orang_dan_tampil_sisa_kursi_di_jadwal_publik(): void
    {
        $seminar = $this->createScheduledSeminars(1, '2026-12-25')[0];

        $mahasiswaList = [];
        for ($i = 1; $i <= 36; $i++) {
            $prodi = $i % 2 === 0 ? $this->prodiAgb : $this->prodiAgt;
            $mahasiswaList[] = $this->createUser("Audiens HTTP {$i}", sprintf('2235%04d', $i), UserRole::Mahasiswa, $prodi);
        }

        // 34 pendaftar pertama (2 penyanggah + 32 peserta)
        for ($i = 0; $i < 34; $i++) {
            $peran = $i < 2 ? 'penyanggah' : 'peserta';
            $this->actingAs($mahasiswaList[$i])
                ->post(route('mahasiswa.audiens.daftar', $seminar), ['peran' => $peran])
                ->assertRedirect()
                ->assertSessionHas('success');
        }

        // Jadwal publik menampilkan sisa 1/35 kursi penonton
        $this->get(route('jadwal.publik'))
            ->assertOk()
            ->assertSee('Sisa Kursi Audiens:')
            ->assertSee('1/35');

        // Orang ke-35 berhasil masuk
        $this->actingAs($mahasiswaList[34])
            ->post(route('mahasiswa.audiens.daftar', $seminar), ['peran' => 'peserta'])
            ->assertRedirect()
            ->assertSessionHas('success');

        // Orang ke-36 DITOLAK karena kuota 35 orang penuh
        $this->actingAs($mahasiswaList[35])
            ->post(route('mahasiswa.audiens.daftar', $seminar), ['peran' => 'peserta'])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame(35, SeminarAudiens::where('seminar_skripsi_id', $seminar->id)->aktif()->count());

        // Jadwal publik menunjukkan 0/35 kursi tersisa
        $this->get(route('jadwal.publik'))
            ->assertOk()
            ->assertSee('Sisa Kursi Audiens:')
            ->assertSee('0/35');
    }

    public function test_http_syarat_12x_menonton_menghalangi_lalu_meloloskan_pengajuan_seminar(): void
    {
        config(['fpst.seminar_audiens.enforce_syarat_seminar' => true]);

        $mhsKandidat = $this->createUser('Siti Kandidat Seminar', '221010999', UserRole::Mahasiswa, $this->prodiAgt);
        PengajuanSkripsi::create([
            'mahasiswa_id' => $mhsKandidat->id,
            'program_studi_id' => $this->prodiAgt->id,
            'judul' => 'Optimasi Media Tanam Cocopeat Pada Pembibitan Kakao',
            'file_proposal' => 'dummy/prop.pdf',
            'file_transkrip' => 'dummy/tr.pdf',
            'file_bukti_bayar' => 'dummy/bb.pdf',
            'pembimbing_1_id' => $this->dosenAgt1->id,
            'nomor_sk_bimbingan' => 'SK-BIM/01/FPST/2026',
            'status' => StatusPengajuan::Selesai,
        ]);

        // Belum menonton 12x -> ditolak saat buka form maupun POST seminar
        $this->actingAs($mhsKandidat)
            ->get(route('mahasiswa.seminar.create'))
            ->assertRedirect(route('mahasiswa.seminar.index'))
            ->assertSessionHas('error');

        $payloadSeminar = [
            'file_naskah_seminar' => UploadedFile::fake()->create('naskah.pdf', 100, 'application/pdf'),
            'file_acc_pembimbing' => UploadedFile::fake()->create('acc.pdf', 100, 'application/pdf'),
            'file_bukti_bayar_seminar' => UploadedFile::fake()->create('bayar.pdf', 100, 'application/pdf'),
            'file_toefl' => UploadedFile::fake()->create('toefl.pdf', 100, 'application/pdf'),
        ];

        $this->actingAs($mhsKandidat)
            ->post(route('mahasiswa.seminar.store'), $payloadSeminar)
            ->assertRedirect(route('mahasiswa.seminar.index'))
            ->assertSessionHas('error');

        $seminars = $this->createScheduledSeminars(12, '2027-01-10');
        for ($i = 0; $i < 11; $i++) {
            SeminarAudiens::create([
                'seminar_skripsi_id' => $seminars[$i]->id,
                'mahasiswa_id' => $mhsKandidat->id,
                'peran' => $i < 2 ? PeranAudiensSeminar::Penyanggah : PeranAudiensSeminar::Peserta,
                'status' => StatusAudiensSeminar::Hadir,
                'waktu_daftar' => now(),
                'waktu_presensi' => now(),
                'divalidasi_oleh' => $this->dosenAgt1->id,
            ]);
        }

        // Baru 11 hadir -> masih ditolak
        $this->actingAs($mhsKandidat)
            ->post(route('mahasiswa.seminar.store'), [
                'file_naskah_seminar' => UploadedFile::fake()->create('naskah.pdf', 100, 'application/pdf'),
                'file_acc_pembimbing' => UploadedFile::fake()->create('acc.pdf', 100, 'application/pdf'),
                'file_bukti_bayar_seminar' => UploadedFile::fake()->create('bayar.pdf', 100, 'application/pdf'),
                'file_toefl' => UploadedFile::fake()->create('toefl.pdf', 100, 'application/pdf'),
            ])
            ->assertRedirect(route('mahasiswa.seminar.index'))
            ->assertSessionHas('error');

        // Hadir ke-12 dicatat -> form create & submit BERHASIL
        SeminarAudiens::create([
            'seminar_skripsi_id' => $seminars[11]->id,
            'mahasiswa_id' => $mhsKandidat->id,
            'peran' => PeranAudiensSeminar::Peserta,
            'status' => StatusAudiensSeminar::Hadir,
            'waktu_daftar' => now(),
            'waktu_presensi' => now(),
            'divalidasi_oleh' => $this->dosenAgt1->id,
        ]);

        $this->actingAs($mhsKandidat)
            ->get(route('mahasiswa.seminar.create'))
            ->assertOk();

        $this->actingAs($mhsKandidat)
            ->post(route('mahasiswa.seminar.store'), [
                'file_naskah_seminar' => UploadedFile::fake()->create('naskah.pdf', 100, 'application/pdf'),
                'file_acc_pembimbing' => UploadedFile::fake()->create('acc.pdf', 100, 'application/pdf'),
                'file_bukti_bayar_seminar' => UploadedFile::fake()->create('bayar.pdf', 100, 'application/pdf'),
                'file_toefl' => UploadedFile::fake()->create('toefl.pdf', 100, 'application/pdf'),
            ])
            ->assertRedirect(route('mahasiswa.seminar.index'))
            ->assertSessionHas('success');
    }

    public function test_http_otorisasi_kelola_audiens_lintas_prodi_dan_dosen_pembimbing(): void
    {
        $seminarAgt = $this->createScheduledSeminars(1, '2027-02-15', forceProdi: $this->prodiAgt)[0];
        $mhsBukanPengelola = $this->createUser('Mhs Biasa', '22900001', UserRole::Mahasiswa, $this->prodiAgt);
        $dosenLuar = $this->createUser('Dosen Tidak Terlibat', 'DSN-LUAR-01', UserRole::Dosen, $this->prodiAgt);

        $this->actingAs($mhsBukanPengelola)
            ->get(route('kelola.audiens.show', $seminarAgt))
            ->assertForbidden();

        // Admin Prodi AGB tidak berhak mengelola sesi prodi AGT
        $this->actingAs($this->adminAgb)
            ->get(route('kelola.audiens.show', $seminarAgt))
            ->assertForbidden();

        // Dosen yang bukan pembimbing / penguji pada seminar ini -> 403
        $this->actingAs($dosenLuar)
            ->get(route('kelola.audiens.show', $seminarAgt))
            ->assertForbidden();

        // Pembimbing 1 / Penguji seminar ini -> 200 OK beserta QR token SVG
        $this->actingAs($this->dosenAgt1)
            ->get(route('kelola.audiens.show', $seminarAgt))
            ->assertOk()
            ->assertSee('KODE PRESENSI SESI');
    }

    public function test_http_cek_kemiripan_bentrok_jadwal_validasi_kuota_dan_berita_acara_bebas_revisi(): void
    {
        $mhsExisting = $this->createUser('Mhs Eksisting AGT', '21105001', UserRole::Mahasiswa, $this->prodiAgt);
        PengajuanSkripsi::create([
            'mahasiswa_id' => $mhsExisting->id,
            'program_studi_id' => $this->prodiAgt->id,
            'judul' => 'Respon Pertumbuhan Tanaman Padi Terhadap Pemberian Pupuk Kompos Jerami',
            'file_proposal' => 'dummy/p.pdf',
            'file_transkrip' => 'dummy/t.pdf',
            'file_bukti_bayar' => 'dummy/b.pdf',
            'status' => StatusPengajuan::Selesai,
        ]);

        // 1. Cek endpoint JSON kemiripan judul
        $mhsBaru = $this->createUser('Mhs Baru AGT', '22105002', UserRole::Mahasiswa, $this->prodiAgt);
        $this->actingAs($mhsBaru)
            ->getJson(route('mahasiswa.skripsi.cek-kemiripan', [
                'judul' => 'Respon Pertumbuhan Tanaman Padi Terhadap Pemberian Pupuk Kompos Jerami Padi',
            ]))
            ->assertOk()
            ->assertJsonStructure(['matches' => [['skripsi_id', 'judul', 'prodi_kode', 'skor_kemiripan']]]);

        // 2. Halaman Kaprodi Penetapan berjalan lancar memuat peta kemiripan
        $this->actingAs($this->kaprodiAgt)
            ->get(route('kaprodi.penetapan.index'))
            ->assertOk();

        // 3. Jadwal eksisting untuk uji bentrok
        $seminarEksisting = $this->createScheduledSeminars(1, '2027-05-10', forceProdi: $this->prodiAgt)[0];
        $seminarEksisting->update([
            'tgl_seminar' => '2027-05-10',
            'jam_seminar' => '09:00 - 11:00',
            'ruangan' => 'Ruang Seminar FPST 1',
        ]);

        // Siapkan seminar kedua yang sudah disetujui pengujinya
        $skripsiTarget = PengajuanSkripsi::create([
            'mahasiswa_id' => $mhsBaru->id,
            'program_studi_id' => $this->prodiAgt->id,
            'judul' => 'Kajian Agribisnis Kopi Liberika di Kubu Raya',
            'file_proposal' => 'dummy/p2.pdf',
            'file_transkrip' => 'dummy/t2.pdf',
            'file_bukti_bayar' => 'dummy/b2.pdf',
            'pembimbing_1_id' => $this->dosenAgt2->id,
            'status' => StatusPengajuan::Selesai,
        ]);

        $seminarTarget = SeminarSkripsi::create([
            'pengajuan_skripsi_id' => $skripsiTarget->id,
            'file_naskah_seminar' => 'dummy/ns.pdf',
            'file_acc_pembimbing' => 'dummy/ac.pdf',
            'file_bukti_bayar_seminar' => 'dummy/bb.pdf',
            'file_toefl' => 'dummy/tf.pdf',
            'penguji_seminar_id' => $this->dosenAgt2->id,
            'status' => StatusPengajuan::Diproses,
        ]);

        PenugasanDosen::create([
            'assignable_type' => SeminarSkripsi::class,
            'assignable_id' => $seminarTarget->id,
            'dosen_id' => $this->dosenAgt2->id,
            'peran' => 'penguji_seminar',
            'status' => StatusPenugasanDosen::Disetujui,
        ]);

        // 3a. Validasi kuota > 35 ditolak form request
        $this->actingAs($this->adminAgt)
            ->post(route('admin.seminar.jadwal-sk', $seminarTarget), [
                'tgl_seminar' => '2027-05-11',
                'jam_seminar' => '09:00 - 11:00',
                'ruangan' => 'Ruang Seminar FPST 2',
                'kuota_penonton' => 40,
            ])
            ->assertSessionHasErrors('kuota_penonton');

        // 3b. Bentrok ruangan pada tanggal & jam overlap -> ditolak
        $this->actingAs($this->adminAgt)
            ->post(route('admin.seminar.jadwal-sk', $seminarTarget), [
                'tgl_seminar' => '2027-05-10',
                'jam_seminar' => '10:00 - 11:30',
                'ruangan' => 'Ruang Seminar FPST 1',
                'kuota_penonton' => 35,
            ])
            ->assertRedirect()
            ->assertSessionHas('error');

        // 3c. Jadwal aman -> sukses
        $this->actingAs($this->adminAgt)
            ->post(route('admin.seminar.jadwal-sk', $seminarTarget), [
                'tgl_seminar' => '2027-05-11',
                'jam_seminar' => '13:30 - 15:00',
                'ruangan' => 'Ruang Seminar FPST 2',
                'kuota_penonton' => 35,
                'kuota_penyanggah' => 2,
                'pendaftaran_audiens_dibuka' => 1,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        // 4. Selesaikan Seminar dengan status Lulus dengan Revisi + rincian nilai -> lalu setujui Bebas Revisi Digital
        $this->actingAs($this->adminAgt)
            ->post(route('admin.seminar.selesai', $seminarTarget), [
                'nilai_seminar' => 84.50,
                'hasil_evaluasi' => HasilEvaluasiAkademik::LulusRevisi->value,
                'nilai_pembimbing_1' => 86,
                'nilai_penguji' => 83,
                'catatan' => 'Layak dilanjutkan setelah perbaikan minor.',
                'catatan_revisi' => 'Perbaiki rancangan acak kelompok pada BAB III.',
                'tenggat_revisi' => '2027-05-25',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $seminarFresh = $seminarTarget->fresh();
        $this->assertSame(HasilEvaluasiAkademik::LulusRevisi, $seminarFresh->hasil_evaluasi);
        $this->assertNull($seminarFresh->bebas_revisi_at);
        $this->assertSame(86, $seminarFresh->rincian_nilai['pembimbing_1']);

        $this->actingAs($this->adminAgt)
            ->post(route('admin.seminar.bebas-revisi', $seminarTarget))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertNotNull($seminarTarget->fresh()->bebas_revisi_at);
        $this->assertSame(StatusPengajuan::Selesai, $seminarTarget->fresh()->status);

        // 5. Finalisasi Sidang Skripsi dengan Predikat Yudisium
        $sidang = SidangSkripsi::create([
            'pengajuan_skripsi_id' => $skripsiTarget->id,
            'file_naskah_sidang' => 'dummy/s.pdf',
            'file_acc_sidang' => 'dummy/as.pdf',
            'file_bebas_revisi_seminar' => 'dummy/br.pdf',
            'file_bukti_bayar_sidang' => 'dummy/bbs.pdf',
            'penguji_1_id' => $this->dosenAgt1->id,
            'penguji_2_id' => $this->dosenAgt2->id,
            'tgl_sidang' => '2027-06-15',
            'jam_sidang' => '09:00 - 11:00',
            'ruangan' => 'Ruang Sidang Utama FPST',
            'status' => StatusPengajuan::Diproses,
        ]);

        $this->actingAs($this->adminAgt)
            ->post(route('admin.sidang.selesai', $sidang), [
                'nilai_sidang' => 91.25,
                'hasil_evaluasi' => HasilEvaluasiAkademik::Lulus->value,
                'predikat_yudisium' => 'Dengan Pujian (Cum Laude)',
                'nilai_pembimbing_1' => 92,
                'nilai_penguji_1' => 90,
                'catatan' => 'Sangat baik mempertahankan argumen.',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $sidangFresh = $sidang->fresh();
        $this->assertSame(HasilEvaluasiAkademik::Lulus, $sidangFresh->hasil_evaluasi);
        $this->assertSame('Dengan Pujian (Cum Laude)', $sidangFresh->predikat_yudisium);
        $this->assertNotNull($sidangFresh->bebas_revisi_at);
        $this->assertSame(StatusPengajuan::Selesai, $sidangFresh->status);
    }

    private function createUser(string $name, string $nim, UserRole $role, ProgramStudi $prodi): User
    {
        return User::create([
            'name' => $name,
            'email' => strtolower(str_replace([' ', '.'], '', $nim)) . '@fpst-f2.test',
            'nomor_induk' => $nim,
            'password' => 'password',
            'role' => $role,
            'program_studi_id' => $prodi->id,
        ]);
    }

    /**
     * @return array<int, SeminarSkripsi>
     */
    private function createScheduledSeminars(int $count, string $startDate, ?ProgramStudi $forceProdi = null): array
    {
        $seminars = [];
        $base = Carbon::parse($startDate);

        for ($i = 0; $i < $count; $i++) {
            $prodi = $forceProdi ?? ($i % 2 === 0 ? $this->prodiAgt : $this->prodiAgb);
            $penyaji = $this->createUser("Penyaji F2-{$startDate}-{$i}", sprintf('20%04d%02d', $base->dayOfYear, $i), UserRole::Mahasiswa, $prodi);

            $skripsi = PengajuanSkripsi::create([
                'mahasiswa_id' => $penyaji->id,
                'program_studi_id' => $prodi->id,
                'judul' => $prodi->kode === 'AGT'
                    ? "Aplikasi Pupuk Hayati Pada Kedelai di Lahan Gambut #{$i}"
                    : "Strategi Pemasaran Produk Olahan Kelapa Sawit Rakyat #{$i}",
                'file_proposal' => 'dummy/prop.pdf',
                'file_transkrip' => 'dummy/tr.pdf',
                'file_bukti_bayar' => 'dummy/bb.pdf',
                'pembimbing_1_id' => $this->dosenAgt1->id,
                'status' => StatusPengajuan::Selesai,
            ]);

            $seminars[] = SeminarSkripsi::create([
                'pengajuan_skripsi_id' => $skripsi->id,
                'file_naskah_seminar' => 'dummy/ns.pdf',
                'file_acc_pembimbing' => 'dummy/acc.pdf',
                'file_bukti_bayar_seminar' => 'dummy/bbs.pdf',
                'file_toefl' => 'dummy/toefl.pdf',
                'penguji_seminar_id' => $this->dosenAgt1->id,
                'tgl_seminar' => $base->copy()->addDays($i)->toDateString(),
                'jam_seminar' => '09:00 - 10:30',
                'ruangan' => 'Ruang Seminar FPST ' . (($i % 3) + 1),
                'kuota_penonton' => 35,
                'kuota_penyanggah' => 2,
                'pendaftaran_audiens_dibuka' => true,
                'status' => StatusPengajuan::Diproses,
            ]);
        }

        return $seminars;
    }
}
