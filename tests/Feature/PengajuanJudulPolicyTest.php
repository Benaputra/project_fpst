<?php

namespace Tests\Feature;

use App\Models\Dosen;
use App\Models\Mahasiswa;
use App\Models\PengajuanJudul;
use App\Models\ProgramStudi;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class PengajuanJudulPolicyTest extends TestCase
{
    use DatabaseTransactions;

    public function test_mahasiswa_hanya_dapat_melihat_dan_membuat_pengajuan_miliknya(): void
    {
        $data = $this->buatPengajuan();
        $mahasiswaLain = $this->buatMahasiswa($data['programStudi'], $data['ketua']);

        $this->assertTrue(Gate::forUser($data['mahasiswaUser'])->allows('view', $data['pengajuan']));
        $this->assertFalse(Gate::forUser($mahasiswaLain['user'])->allows('view', $data['pengajuan']));
        $this->assertFalse(Gate::forUser($data['mahasiswaUser'])->allows('create', PengajuanJudul::class));
        $this->assertTrue(Gate::forUser($mahasiswaLain['user'])->allows('create', PengajuanJudul::class));
    }

    public function test_mahasiswa_hanya_dapat_memperbaiki_pengajuan_miliknya_yang_ditolak(): void
    {
        $ditolak = $this->buatPengajuan(status: 'ditolak');
        $diajukan = $this->buatPengajuan(status: 'diajukan');
        $diverifikasi = $this->buatPengajuan(status: 'diverifikasi');
        $mahasiswaLain = $this->buatMahasiswa($ditolak['programStudi'], $ditolak['ketua']);

        $this->assertTrue(Gate::forUser($ditolak['mahasiswaUser'])->allows('update', $ditolak['pengajuan']));
        $this->assertFalse(Gate::forUser($mahasiswaLain['user'])->allows('update', $ditolak['pengajuan']));
        $this->assertFalse(Gate::forUser($diajukan['mahasiswaUser'])->allows('update', $diajukan['pengajuan']));
        $this->assertFalse(Gate::forUser($diverifikasi['mahasiswaUser'])->allows('update', $diverifikasi['pengajuan']));
    }

    public function test_hanya_kaprodi_terkait_dapat_memverifikasi_pengajuan(): void
    {
        $data = $this->buatPengajuan();
        $prodiLain = ProgramStudi::factory()->create();
        $kaprodiLainUser = User::factory()->dosen()->create();
        $kaprodiLain = Dosen::factory()->create([
            'program_studi_id' => $prodiLain->id,
            'user_id' => $kaprodiLainUser->id,
        ]);
        $prodiLain->update(['ketua_prodi_id' => $kaprodiLain->nidn]);
        $dosenBiasaUser = User::factory()->dosen()->create();
        Dosen::factory()->create([
            'program_studi_id' => $data['programStudi']->id,
            'user_id' => $dosenBiasaUser->id,
        ]);

        $this->assertTrue(Gate::forUser($data['ketuaUser'])->allows('view', $data['pengajuan']));
        $this->assertTrue(Gate::forUser($data['ketuaUser'])->allows('terima', $data['pengajuan']));
        $this->assertTrue(Gate::forUser($data['ketuaUser'])->allows('tolak', $data['pengajuan']));
        $this->assertFalse(Gate::forUser($kaprodiLainUser)->allows('view', $data['pengajuan']));
        $this->assertFalse(Gate::forUser($kaprodiLainUser)->allows('terima', $data['pengajuan']));
        $this->assertFalse(Gate::forUser($dosenBiasaUser)->allows('view', $data['pengajuan']));
        $this->assertFalse(Gate::forUser($dosenBiasaUser)->allows('tolak', $data['pengajuan']));
    }

    public function test_admin_prodi_hanya_memantau_prodinya_dan_tidak_memutuskan(): void
    {
        $data = $this->buatPengajuan();
        $dataLain = $this->buatPengajuan();
        $adminProdi = User::factory()->adminProdi()->create();
        $adminProdi->programStudiAdministrasi()->attach($data['programStudi']);

        $this->assertTrue(Gate::forUser($adminProdi)->allows('view', $data['pengajuan']));
        $this->assertFalse(Gate::forUser($adminProdi)->allows('view', $dataLain['pengajuan']));
        $this->assertFalse(Gate::forUser($adminProdi)->allows('terima', $data['pengajuan']));
        $this->assertFalse(Gate::forUser($adminProdi)->allows('tolak', $data['pengajuan']));
    }

    public function test_admin_utama_melihat_global_tetapi_tidak_memutuskan(): void
    {
        $data = $this->buatPengajuan();
        $dataLain = $this->buatPengajuan();
        $adminUtama = User::factory()->adminUtama()->create();

        $this->assertTrue(Gate::forUser($adminUtama)->allows('viewAny', PengajuanJudul::class));
        $this->assertTrue(Gate::forUser($adminUtama)->allows('view', $data['pengajuan']));
        $this->assertTrue(Gate::forUser($adminUtama)->allows('view', $dataLain['pengajuan']));
        $this->assertFalse(Gate::forUser($adminUtama)->allows('terima', $data['pengajuan']));
        $this->assertFalse(Gate::forUser($adminUtama)->allows('tolak', $data['pengajuan']));
    }

    public function test_tidak_ada_role_yang_dapat_menghapus_pengajuan(): void
    {
        $data = $this->buatPengajuan();
        $adminUtama = User::factory()->adminUtama()->create();

        $this->assertFalse(Gate::forUser($data['mahasiswaUser'])->allows('delete', $data['pengajuan']));
        $this->assertFalse(Gate::forUser($data['ketuaUser'])->allows('delete', $data['pengajuan']));
        $this->assertFalse(Gate::forUser($adminUtama)->allows('delete', $data['pengajuan']));
    }

    /**
     * @return array{
     *     programStudi: ProgramStudi,
     *     ketua: Dosen,
     *     ketuaUser: User,
     *     mahasiswa: Mahasiswa,
     *     mahasiswaUser: User,
     *     pengajuan: PengajuanJudul
     * }
     */
    private function buatPengajuan(string $status = 'diajukan'): array
    {
        $programStudi = ProgramStudi::factory()->create();
        $ketuaUser = User::factory()->dosen()->create();
        $ketua = Dosen::factory()->create([
            'program_studi_id' => $programStudi->id,
            'user_id' => $ketuaUser->id,
        ]);
        $programStudi->update(['ketua_prodi_id' => $ketua->nidn]);
        $mahasiswaData = $this->buatMahasiswa($programStudi, $ketua);
        $pengajuan = PengajuanJudul::factory()->create([
            'nim' => $mahasiswaData['mahasiswa']->nim,
            'status' => $status,
            'catatan_reject' => $status === 'ditolak' ? 'Perbaiki judul.' : null,
            'diverifikasi_oleh' => $status === 'diajukan' ? null : $ketua->nidn,
            'diverifikasi_at' => $status === 'diajukan' ? null : now(),
        ]);

        return [
            'programStudi' => $programStudi,
            'ketua' => $ketua,
            'ketuaUser' => $ketuaUser,
            'mahasiswa' => $mahasiswaData['mahasiswa'],
            'mahasiswaUser' => $mahasiswaData['user'],
            'pengajuan' => $pengajuan,
        ];
    }

    /**
     * @return array{mahasiswa: Mahasiswa, user: User}
     */
    private function buatMahasiswa(ProgramStudi $programStudi, Dosen $pembimbingAkademik): array
    {
        $user = User::factory()->mahasiswa()->create();
        $mahasiswa = Mahasiswa::factory()->create([
            'program_studi_id' => $programStudi->id,
            'pembimbing_akademik_id' => $pembimbingAkademik->nidn,
            'user_id' => $user->id,
        ]);

        return compact('mahasiswa', 'user');
    }
}
