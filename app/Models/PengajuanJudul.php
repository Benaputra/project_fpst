<?php

namespace App\Models;

use App\Enums\StatusPengajuanJudul;
use Database\Factories\PengajuanJudulFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PengajuanJudul extends Model
{
    /** @use HasFactory<PengajuanJudulFactory> */
    use HasFactory;

    protected $table = 'pengajuan_judul';

    protected $fillable = [
        'judul',
    ];

    protected function casts(): array
    {
        return [
            'status' => StatusPengajuanJudul::class,
            'diverifikasi_at' => 'datetime',
        ];
    }

    public function mahasiswa(): BelongsTo
    {
        return $this->belongsTo(Mahasiswa::class, 'nim', 'nim');
    }

    public function verifikator(): BelongsTo
    {
        return $this->belongsTo(Dosen::class, 'diverifikasi_oleh', 'nidn');
    }

    public function skripsi(): HasOne
    {
        return $this->hasOne(Skripsi::class);
    }
}
