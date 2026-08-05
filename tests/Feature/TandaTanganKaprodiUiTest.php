<?php

namespace Tests\Feature;

use App\Models\Dosen;
use App\Models\ProgramStudi;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TandaTanganKaprodiUiTest extends TestCase
{
    use DatabaseTransactions;

    public function test_kaprodi_mengunggah_tanda_tangan_ke_penyimpanan_privat(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        [$user, $programStudi] = $this->kaprodi();

        $this->actingAs($user)
            ->post(route('kaprodi.tanda-tangan.store'), [
                'tanda_tangan' => UploadedFile::fake()->image('ttd.png', 128, 194),
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'Tanda tangan Kaprodi berhasil disimpan secara privat.');

        $path = $programStudi->fresh()->ttd_ketua_prodi;
        $this->assertNotNull($path);
        $this->assertStringStartsWith("tanda-tangan/kaprodi/{$programStudi->id}/", $path);
        Storage::disk('local')->assertExists($path);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_role_lain_tidak_dapat_mengunggah_tanda_tangan_kaprodi(): void
    {
        Storage::fake('local');
        $dosenUser = User::factory()->dosen()->create();
        Dosen::factory()->create(['user_id' => $dosenUser->id]);

        $this->actingAs($dosenUser)
            ->post(route('kaprodi.tanda-tangan.store'), [
                'tanda_tangan' => UploadedFile::fake()->image('ttd.png', 128, 194),
            ])
            ->assertForbidden();

        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_dashboard_kaprodi_menampilkan_form_upload_tanda_tangan(): void
    {
        [$user] = $this->kaprodi();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee(route('kaprodi.tanda-tangan.store'))
            ->assertSee('name="tanda_tangan"', false)
            ->assertSee('Unggah tanda tangan');
    }

    /** @return array{User, ProgramStudi} */
    private function kaprodi(): array
    {
        $programStudi = ProgramStudi::factory()->create();
        $user = User::factory()->dosen()->create();
        $dosen = Dosen::factory()->create([
            'program_studi_id' => $programStudi->id,
            'user_id' => $user->id,
        ]);
        $programStudi->update(['ketua_prodi_id' => $dosen->nidn]);

        return [$user, $programStudi];
    }
}
