<?php

namespace Tests\Feature;

use App\Enums\StatusKesediaanBimbingan;
use App\Models\DokumenPengajuan;
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

class VerifikasiHasilKonsultasiHttpTest extends TestCase
{
    use DatabaseTransactions;

    public function test_endpoint_memvalidasi_keputusan_catatan_dan_mengabaikan_metadata_luar(): void
    {
        Storage::fake('local');
        $data = $this->dataDokumen();

        $this->actingAs($data['admin'])
            ->from('/dashboard')
            ->post(route(
                'dokumen-pengajuan.verifikasi-hasil-konsultasi.store',
                $data['dokumen']
            ), [
                'keputusan' => 'valid_tidak_bersedia',
                'verified_by' => $data['mahasiswaUser']->id,
                'status' => 'terverifikasi',
            ])
            ->assertRedirect('/dashboard')
            ->assertSessionHasErrors('catatan_verifikasi');

        $this->post(route(
            'dokumen-pengajuan.verifikasi-hasil-konsultasi.store',
            $data['dokumen']
        ), [
            'keputusan' => 'nilai_bebas',
            'catatan_verifikasi' => 'Tidak valid.',
        ])->assertSessionHasErrors('keputusan');

        $this->post(route(
            'dokumen-pengajuan.verifikasi-hasil-konsultasi.store',
            $data['dokumen']
        ), [
            'keputusan' => 'valid_bersedia',
            'verified_by' => $data['mahasiswaUser']->id,
            'status' => 'ditolak',
        ])->assertRedirect('/dashboard');

        $this->assertDatabaseHas('dokumen_pengajuan', [
            'id' => $data['dokumen']->id,
            'status' => 'terverifikasi',
            'verified_by' => $data['admin']->id,
        ]);
        $this->assertDatabaseHas('kesediaan_bimbingan', [
            'id' => $data['kesediaan']->id,
            'status' => 'diterima',
            'diverifikasi_oleh' => $data['admin']->id,
        ]);
    }

    public function test_guest_dan_admin_di_luar_scope_ditolak(): void
    {
        Storage::fake('local');
        $data = $this->dataDokumen();

        $this->post(route(
            'dokumen-pengajuan.verifikasi-hasil-konsultasi.store',
            $data['dokumen']
        ), ['keputusan' => 'valid_bersedia'])->assertRedirect(route('login'));

        $adminLain = User::factory()->adminProdi()->create();
        $adminLain->programStudiAdministrasi()->attach(ProgramStudi::factory()->create());
        $this->actingAs($adminLain)
            ->post(route(
                'dokumen-pengajuan.verifikasi-hasil-konsultasi.store',
                $data['dokumen']
            ), ['keputusan' => 'valid_bersedia'])
            ->assertForbidden();

        $this->assertNull($data['dokumen']->fresh()->verified_by);
    }

    /** @return array<string, mixed> */
    private function dataDokumen(): array
    {
        $programStudi = ProgramStudi::factory()->create();
        $ketuaUser = User::factory()->dosen()->create();
        $ketua = Dosen::factory()->create([
            'program_studi_id' => $programStudi->id,
            'user_id' => $ketuaUser->id,
        ]);
        $programStudi->update(['ketua_prodi_id' => $ketua->nidn]);
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
        $kesediaan = KesediaanBimbingan::factory()->for($skripsi)->create([
            'dosen_id' => Dosen::factory()->create([
                'program_studi_id' => $programStudi->id,
            ])->nidn,
            'status' => StatusKesediaanBimbingan::MenungguVerifikasi,
        ]);
        $isi = "%PDF-1.4\nhasil konsultasi\n%%EOF";
        Storage::disk('local')->put('dokumen/testing/http.pdf', $isi);
        $dokumen = DokumenPengajuan::factory()->for($kesediaan, 'documentable')->create([
            'file_path' => 'dokumen/testing/http.pdf',
            'file_hash' => hash('sha256', $isi),
            'uploaded_by' => $mahasiswaUser->id,
        ]);
        $admin = User::factory()->adminProdi()->create();
        $admin->programStudiAdministrasi()->attach($programStudi);

        return compact(
            'programStudi',
            'mahasiswaUser',
            'kesediaan',
            'dokumen',
            'admin'
        );
    }
}
