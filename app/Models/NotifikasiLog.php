<?php

namespace App\Models;

use App\Enums\StatusKirimNotifikasi;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class NotifikasiLog extends Model
{
    protected $table = 'notifikasi_log';

    protected $fillable = [];

    protected function casts(): array
    {
        return ['status_kirim' => StatusKirimNotifikasi::class, 'sent_at' => 'datetime'];
    }

    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }
}
