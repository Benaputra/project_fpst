<?php

namespace App\Services\Notification;

use App\Contracts\PengirimNotifikasi;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

class PengirimTidakTersedia implements PengirimNotifikasi
{
    public function kirim(Model $notifiable, string $jenis): void
    {
        throw new RuntimeException('Provider notifikasi belum dikonfigurasi.');
    }
}
