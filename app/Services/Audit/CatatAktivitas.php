<?php

namespace App\Services\Audit;

use App\Models\AktivitasLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class CatatAktivitas
{
    /**
     * @param  array<string, scalar|null>  $sebelum
     * @param  array<string, scalar|null>  $sesudah
     */
    public function execute(
        ?User $user,
        Model $subject,
        string $aksi,
        array $sebelum,
        array $sesudah
    ): AktivitasLog {
        return AktivitasLog::query()->forceCreate([
            'user_id' => $user?->id,
            'subject_type' => $subject::class,
            'subject_id' => $subject->getKey(),
            'aksi' => $aksi,
            'before_data' => $sebelum === [] ? null : $sebelum,
            'after_data' => $sesudah === [] ? null : $sesudah,
            'ip_address' => app()->runningInConsole() ? null : request()->ip(),
            'created_at' => now(),
        ]);
    }
}
