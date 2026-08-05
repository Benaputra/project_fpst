<?php

namespace App\Services\Notification;

use App\Contracts\PengirimNotifikasi;
use App\Enums\StatusKirimNotifikasi;
use App\Models\NotifikasiLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Throwable;

class KirimNotifikasi
{
    public function __construct(private readonly PengirimNotifikasi $pengirim) {}

    public function execute(Model $notifiable, string $jenis): NotifikasiLog
    {
        $jenis = trim($jenis);
        $log = DB::transaction(function () use ($notifiable, $jenis) {
            $existing = NotifikasiLog::query()->whereMorphedTo('notifiable', $notifiable)->where('jenis', $jenis)->lockForUpdate()->first();
            if ($existing?->status_kirim === StatusKirimNotifikasi::Terkirim) {
                return $existing;
            }

            return $existing ?? NotifikasiLog::query()->forceCreate(['notifiable_type' => $notifiable::class, 'notifiable_id' => $notifiable->getKey(), 'jenis' => $jenis, 'status_kirim' => StatusKirimNotifikasi::Gagal, 'sent_at' => null]);
        });
        if ($log->status_kirim === StatusKirimNotifikasi::Terkirim) {
            return $log;
        }
        try {
            $this->pengirim->kirim($notifiable, $jenis);
            $log->forceFill(['status_kirim' => StatusKirimNotifikasi::Terkirim, 'sent_at' => now()])->save();
        } catch (Throwable) {
            $log->forceFill(['status_kirim' => StatusKirimNotifikasi::Gagal, 'sent_at' => null])->save();
        }

        return $log->refresh();
    }
}
