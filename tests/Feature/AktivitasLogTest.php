<?php

namespace Tests\Feature;

use App\Models\Seminar;
use App\Models\User;
use App\Services\Audit\CatatAktivitas;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class AktivitasLogTest extends TestCase
{
    use DatabaseTransactions;

    public function test_schema_kanonis_dan_snapshot_tidak_memuat_data_file_sensitif(): void
    {
        $this->assertSame([
            'id',
            'user_id',
            'subject_id',
            'subject_type',
            'aksi',
            'before_data',
            'after_data',
            'ip_address',
            'created_at',
        ], Schema::getColumnListing('aktivitas_log'));

        $user = User::factory()->adminUtama()->create();
        $seminar = Seminar::factory()->create();
        $log = app(CatatAktivitas::class)->execute(
            $user,
            $seminar,
            'seminar_diuji',
            ['status' => 'diajukan'],
            ['status' => 'diverifikasi']
        );

        $this->assertSame($user->id, $log->user_id);
        $this->assertSame($seminar->id, $log->subject_id);
        $this->assertSame(['status' => 'diajukan'], $log->before_data);
        $this->assertArrayNotHasKey('file_path', $log->after_data);
        $this->assertArrayNotHasKey('file_hash', $log->after_data);
    }

    public function test_log_mengikuti_rollback_transaksi_akademik(): void
    {
        $seminar = Seminar::factory()->create();

        try {
            DB::transaction(function () use ($seminar) {
                app(CatatAktivitas::class)->execute(
                    null,
                    $seminar,
                    'simulasi_gagal',
                    [],
                    ['status' => 'gagal']
                );

                throw new RuntimeException('rollback');
            });
        } catch (RuntimeException) {
            $this->assertDatabaseMissing('aktivitas_log', ['aksi' => 'simulasi_gagal']);
        }
    }
}
