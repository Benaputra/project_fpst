<?php

namespace Tests\Feature;

use App\Contracts\PengirimNotifikasi;
use App\Enums\StatusKirimNotifikasi;
use App\Models\Seminar;
use App\Services\Notification\KirimNotifikasi;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use RuntimeException;
use Tests\TestCase;

class NotifikasiTest extends TestCase
{
    use DatabaseTransactions;

    public function test_default_tidak_mengirim_nyata_dan_mencatat_gagal_tanpa_melempar(): void
    {
        $seminar = Seminar::factory()->create();
        $log = app(KirimNotifikasi::class)->execute($seminar, 'seminar_diverifikasi');
        $this->assertSame(StatusKirimNotifikasi::Gagal, $log->status_kirim);
        $this->assertNull($log->sent_at);
        $this->assertSame($seminar->id, $log->notifiable_id);
        $this->assertDatabaseHas('seminar', ['id' => $seminar->id]);
    }

    public function test_fake_terkirim_retry_dan_duplikasi_dicegah(): void
    {
        $fake = new class implements PengirimNotifikasi
        {
            public int $calls = 0;

            public function kirim(Model $notifiable, string $jenis): void
            {
                $this->calls++;
            }
        };
        $this->app->instance(PengirimNotifikasi::class, $fake);
        $seminar = Seminar::factory()->create();
        $service = app(KirimNotifikasi::class);
        $a = $service->execute($seminar, 'jadwal_seminar');
        $b = $service->execute($seminar, 'jadwal_seminar');
        $this->assertSame(StatusKirimNotifikasi::Terkirim, $a->status_kirim);
        $this->assertSame($a->id, $b->id);
        $this->assertSame(1, $fake->calls);
        $this->assertDatabaseCount('notifikasi_log', 1);
    }

    public function test_kegagalan_provider_dapat_retry_pada_log_sama(): void
    {
        $fake = new class implements PengirimNotifikasi
        {
            public int $calls = 0;

            public function kirim(Model $notifiable, string $jenis): void
            {
                $this->calls++;
                if ($this->calls === 1) {
                    throw new RuntimeException('gagal');
                }
            }
        };
        $this->app->instance(PengirimNotifikasi::class, $fake);
        $seminar = Seminar::factory()->create();
        $service = app(KirimNotifikasi::class);
        $gagal = $service->execute($seminar, 'hasil_seminar');
        $sukses = $service->execute($seminar, 'hasil_seminar');
        $this->assertSame($gagal->id, $sukses->id);
        $this->assertSame(StatusKirimNotifikasi::Terkirim, $sukses->status_kirim);
        $this->assertSame(2, $fake->calls);
    }
}
