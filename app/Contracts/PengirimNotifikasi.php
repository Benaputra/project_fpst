<?php

namespace App\Contracts;

use Illuminate\Database\Eloquent\Model;

interface PengirimNotifikasi
{
    public function kirim(Model $notifiable, string $jenis): void;
}
