<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LogbookPl extends Model
{
    use HasFactory;

    protected $table = 'logbook_pl';

    protected $fillable = [
        'praktik_lapang_id',
        'tanggal',
        'aktivitas',
        'uraian',
        'file_dokumentasi',
        'diverifikasi_dpl',
        'diverifikasi_at',
        'catatan_dpl',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'diverifikasi_dpl' => 'boolean',
            'diverifikasi_at' => 'datetime',
        ];
    }

    public function praktikLapang(): BelongsTo
    {
        return $this->belongsTo(PraktikLapang::class, 'praktik_lapang_id');
    }
}
