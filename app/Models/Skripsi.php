<?php

namespace App\Models;

use App\Enums\StatusSkripsi;
use Database\Factories\SkripsiFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Skripsi extends Model
{
    /** @use HasFactory<SkripsiFactory> */
    use HasFactory;

    protected $table = 'skripsi';

    protected $fillable = [
        'pengajuan_judul_id',
        'nim',
        'judul',
    ];

    protected function casts(): array
    {
        return [
            'status' => StatusSkripsi::class,
        ];
    }

    public function pengajuanJudul(): BelongsTo
    {
        return $this->belongsTo(PengajuanJudul::class);
    }

    public function mahasiswa(): BelongsTo
    {
        return $this->belongsTo(Mahasiswa::class, 'nim', 'nim');
    }

    public function pembimbing1(): BelongsTo
    {
        return $this->belongsTo(Dosen::class, 'pembimbing1_id', 'nidn');
    }

    public function pembimbing2(): BelongsTo
    {
        return $this->belongsTo(Dosen::class, 'pembimbing2_id', 'nidn');
    }

    public function kesediaanBimbingan(): HasMany
    {
        return $this->hasMany(KesediaanBimbingan::class);
    }

    public function surat(): MorphMany
    {
        return $this->morphMany(Surat::class, 'suratable');
    }

    public function seminar(): HasOne
    {
        return $this->hasOne(Seminar::class);
    }

    public function sidangSkripsi(): HasOne
    {
        return $this->hasOne(SidangSkripsi::class);
    }
}
