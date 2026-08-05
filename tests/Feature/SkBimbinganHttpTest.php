<?php

namespace Tests\Feature;

use App\Enums\StatusSkripsi;
use App\Models\Dosen;
use App\Models\Mahasiswa;
use App\Models\PengajuanJudul;
use App\Models\ProgramStudi;
use App\Models\Skripsi;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SkBimbinganHttpTest extends TestCase
{
    use DatabaseTransactions;

    public function test_endpoint_mengabaikan_signed_by_dan_download_tetap_privat(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $data = $this->dataSkripsiFinal();
        $admin = User::factory()->adminUtama()->create();
        $url = route('skripsi.sk-bimbingan.store', $data['skripsi']);

        $this->post($url)->assertRedirect(route('login'));
        $this->actingAs($admin)
            ->from('/dashboard')
            ->post($url, [
                'signed_by' => $data['ketua']->nidn,
                'signed_at' => now()->toDateTimeString(),
                'status' => 'ditandatangani',
            ])
            ->assertRedirect('/dashboard')
            ->assertSessionHas('status');

        $surat = $data['skripsi']->surat()->sole();
        $this->assertNull($surat->signed_by);
        $this->assertNull($surat->signed_at);
        $this->actingAs($data['mahasiswaUser'])
            ->get(route('surat.download', $surat))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $mahasiswaLain = User::factory()->mahasiswa()->create();
        Mahasiswa::factory()->create(['user_id' => $mahasiswaLain->id]);
        $this->actingAs($mahasiswaLain)
            ->get(route('surat.download', $surat))
            ->assertForbidden();
        Storage::disk('public')->assertMissing($surat->file_path);
    }

    /** @return array<string, mixed> */
    private function dataSkripsiFinal(): array
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
        $pembimbing1 = Dosen::factory()->create([
            'program_studi_id' => $programStudi->id,
        ]);
        $skripsi = Skripsi::factory()->for($pengajuan, 'pengajuanJudul')->create([
            'nim' => $mahasiswa->nim,
            'judul' => $pengajuan->judul,
            'pembimbing1_id' => $pembimbing1->nidn,
            'status' => StatusSkripsi::BimbinganAktif,
        ]);

        return compact('ketua', 'mahasiswaUser', 'skripsi');
    }
}
