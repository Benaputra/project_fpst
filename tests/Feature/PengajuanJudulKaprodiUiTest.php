<?php

namespace Tests\Feature;

use App\Enums\StatusPengajuanJudul;
use App\Models\Dosen;
use App\Models\Mahasiswa;
use App\Models\PengajuanJudul;
use App\Models\ProgramStudi;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PengajuanJudulKaprodiUiTest extends TestCase
{
    use DatabaseTransactions;

    public function test_daftar_hanya_memuat_pengajuan_prodi_yang_dipimpin(): void
    {
        $kaprodi = $this->buatKaprodi();
        $pengajuan = $this->buatPengajuan($kaprodi['programStudi'], $kaprodi['dosen'], judul: 'Judul Prodi Sendiri');
        $kaprodiLain = $this->buatKaprodi();
        $pengajuanLain = $this->buatPengajuan($kaprodiLain['programStudi'], $kaprodiLain['dosen'], judul: 'Judul Prodi Lain');

        $this->actingAs($kaprodi['user'])
            ->get(route('kaprodi.pengajuan-judul.index'))
            ->assertOk()
            ->assertSee($pengajuan->mahasiswa->nim)
            ->assertSee('Judul Prodi Sendiri')
            ->assertDontSee($pengajuanLain->mahasiswa->nim)
            ->assertDontSee('Judul Prodi Lain');
    }

    public function test_daftar_mendukung_filter_status_dan_pencarian(): void
    {
        $kaprodi = $this->buatKaprodi();
        $diajukan = $this->buatPengajuan(
            $kaprodi['programStudi'],
            $kaprodi['dosen'],
            StatusPengajuanJudul::Diajukan,
            'Sistem Informasi Akademik',
            'Ayu Pertiwi'
        );
        $ditolak = $this->buatPengajuan(
            $kaprodi['programStudi'],
            $kaprodi['dosen'],
            StatusPengajuanJudul::Ditolak,
            'Topik Jaringan Komputer',
            'Budi Santoso'
        );

        $this->actingAs($kaprodi['user'])
            ->get(route('kaprodi.pengajuan-judul.index', ['status' => 'ditolak']))
            ->assertOk()
            ->assertSee($ditolak->mahasiswa->nim)
            ->assertDontSee($diajukan->mahasiswa->nim);

        $this->get(route('kaprodi.pengajuan-judul.index', ['cari' => 'Ayu Pertiwi']))
            ->assertOk()
            ->assertSee($diajukan->mahasiswa->nim)
            ->assertDontSee($ditolak->mahasiswa->nim);

        $this->get(route('kaprodi.pengajuan-judul.index', ['cari' => $ditolak->mahasiswa->nim]))
            ->assertOk()
            ->assertSee('Topik Jaringan Komputer')
            ->assertDontSee('Sistem Informasi Akademik');
    }

    public function test_status_filter_tidak_menerima_nilai_bebas(): void
    {
        $kaprodi = $this->buatKaprodi();

        $this->actingAs($kaprodi['user'])
            ->from(route('kaprodi.pengajuan-judul.index'))
            ->get(route('kaprodi.pengajuan-judul.index', ['status' => 'status-palsu']))
            ->assertRedirect(route('kaprodi.pengajuan-judul.index'))
            ->assertSessionHasErrors('status');
    }

    public function test_daftar_dipaginasi_lima_belas_record_per_halaman(): void
    {
        $kaprodi = $this->buatKaprodi();

        foreach (range(1, 16) as $nomor) {
            $this->buatPengajuan(
                $kaprodi['programStudi'],
                $kaprodi['dosen'],
                judul: sprintf('Judul Pagination %02d', $nomor)
            );
        }

        $this->actingAs($kaprodi['user'])
            ->get(route('kaprodi.pengajuan-judul.index'))
            ->assertOk()
            ->assertSee('Halaman 1 dari 2')
            ->assertSee('Judul Pagination 16')
            ->assertDontSee('Judul Pagination 01');

        $this->get(route('kaprodi.pengajuan-judul.index', ['page' => 2]))
            ->assertOk()
            ->assertSee('Halaman 2 dari 2')
            ->assertSee('Judul Pagination 01')
            ->assertDontSee('Judul Pagination 16');
    }

    public function test_detail_memuat_identitas_lengkap_dan_dialog_keputusan(): void
    {
        $kaprodi = $this->buatKaprodi();
        $pengajuan = $this->buatPengajuan($kaprodi['programStudi'], $kaprodi['dosen']);

        $this->actingAs($kaprodi['user'])
            ->get(route('kaprodi.pengajuan-judul.show', $pengajuan))
            ->assertOk()
            ->assertSee($pengajuan->mahasiswa->nama)
            ->assertSee($pengajuan->mahasiswa->nim)
            ->assertSee($kaprodi['programStudi']->nama)
            ->assertSee($kaprodi['dosen']->nama)
            ->assertSee($pengajuan->judul)
            ->assertSee('Terima judul')
            ->assertSee('Tolak judul')
            ->assertSee('name="alasan"', false)
            ->assertDontSee('name="status"', false)
            ->assertDontSee('name="diverifikasi_oleh"', false);
    }

    public function test_dosen_biasa_dan_kaprodi_lintas_prodi_ditolak(): void
    {
        $kaprodi = $this->buatKaprodi();
        $pengajuan = $this->buatPengajuan($kaprodi['programStudi'], $kaprodi['dosen']);
        $dosenBiasaUser = User::factory()->dosen()->create();
        Dosen::factory()->create([
            'program_studi_id' => $kaprodi['programStudi']->id,
            'user_id' => $dosenBiasaUser->id,
        ]);
        $kaprodiLain = $this->buatKaprodi();

        $this->actingAs($dosenBiasaUser)
            ->get(route('kaprodi.pengajuan-judul.index'))
            ->assertForbidden();
        $this->actingAs($kaprodiLain['user'])
            ->get(route('kaprodi.pengajuan-judul.show', $pengajuan))
            ->assertForbidden();
    }

    public function test_jumlah_query_daftar_tidak_bertambah_mengikuti_jumlah_record(): void
    {
        $kaprodi = $this->buatKaprodi();
        $this->buatPengajuan($kaprodi['programStudi'], $kaprodi['dosen']);
        $this->actingAs($kaprodi['user']);

        $jumlahAwal = $this->jumlahQueryDaftar();

        foreach (range(1, 5) as $nomor) {
            $this->buatPengajuan(
                $kaprodi['programStudi'],
                $kaprodi['dosen'],
                judul: 'Judul Tambahan '.$nomor
            );
        }

        $jumlahSetelahBertambah = $this->jumlahQueryDaftar();

        $this->assertLessThanOrEqual($jumlahAwal + 1, $jumlahSetelahBertambah);
    }

    /**
     * @return array{user: User, dosen: Dosen, programStudi: ProgramStudi}
     */
    private function buatKaprodi(): array
    {
        $programStudi = ProgramStudi::factory()->create();
        $user = User::factory()->dosen()->create();
        $dosen = Dosen::factory()->create([
            'program_studi_id' => $programStudi->id,
            'user_id' => $user->id,
        ]);
        $programStudi->update(['ketua_prodi_id' => $dosen->nidn]);

        return compact('user', 'dosen', 'programStudi');
    }

    private function buatPengajuan(
        ProgramStudi $programStudi,
        Dosen $pembimbingAkademik,
        StatusPengajuanJudul $status = StatusPengajuanJudul::Diajukan,
        string $judul = 'Analisis Administrasi Skripsi',
        ?string $namaMahasiswa = null
    ): PengajuanJudul {
        $mahasiswa = Mahasiswa::factory()->create([
            'nama' => $namaMahasiswa ?? fake()->name(),
            'program_studi_id' => $programStudi->id,
            'pembimbing_akademik_id' => $pembimbingAkademik->nidn,
        ]);

        return PengajuanJudul::factory()->create([
            'nim' => $mahasiswa->nim,
            'judul' => $judul,
            'status' => $status,
            'catatan_reject' => $status === StatusPengajuanJudul::Ditolak
                ? 'Perbaiki fokus penelitian.'
                : null,
            'diverifikasi_oleh' => $status === StatusPengajuanJudul::Diajukan
                ? null
                : $pembimbingAkademik->nidn,
            'diverifikasi_at' => $status === StatusPengajuanJudul::Diajukan
                ? null
                : now(),
        ]);
    }

    private function jumlahQueryDaftar(): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->get(route('kaprodi.pengajuan-judul.index'))->assertOk();

        $jumlah = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $jumlah;
    }
}
