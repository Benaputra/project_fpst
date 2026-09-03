<?php

namespace App\Models;

use App\Enums\StatusPenugasanDosen;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PenugasanDosen extends Model
{
    use HasFactory;

    protected $table = 'penugasan_dosen';

    protected $fillable = [
        'assignable_type',
        'assignable_id',
        'dosen_id',
        'peran',
        'status',
        'alasan_penolakan',
        'rekomendasi_dosen_id',
        'ditugaskan_oleh',
        'is_mandat_admin_utama',
        'direspon_pada',
    ];

    protected function casts(): array
    {
        return [
            'status' => StatusPenugasanDosen::class,
            'is_mandat_admin_utama' => 'boolean',
            'direspon_pada' => 'datetime',
        ];
    }

    public function assignable(): MorphTo
    {
        return $this->morphTo();
    }

    public function dosen(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dosen_id');
    }

    public function rekomendasiDosen(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rekomendasi_dosen_id');
    }

    public function ditugaskanOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ditugaskan_oleh');
    }

    public function scopeMenunggu($query)
    {
        return $query->where('status', StatusPenugasanDosen::Menunggu);
    }

    public function scopeDisetujui($query)
    {
        return $query->where('status', StatusPenugasanDosen::Disetujui);
    }

    public function scopeDitolak($query)
    {
        return $query->where('status', StatusPenugasanDosen::Ditolak);
    }

    public function isMenunggu(): bool
    {
        return $this->status === StatusPenugasanDosen::Menunggu;
    }

    public function isDisetujui(): bool
    {
        return $this->status === StatusPenugasanDosen::Disetujui;
    }

    public function isDitolak(): bool
    {
        return $this->status === StatusPenugasanDosen::Ditolak;
    }

    public function labelPeran(): string
    {
        return match ($this->peran) {
            'pembimbing_1' => 'Pembimbing Utama (1)',
            'pembimbing_2' => 'Pembimbing Pendamping (2)',
            'penguji_seminar' => 'Penguji Seminar Skripsi',
            'penguji_1' => 'Penguji 1 Sidang Skripsi',
            'penguji_2' => 'Penguji 2 Sidang Skripsi',
            default => ucfirst(str_replace('_', ' ', $this->peran)),
        };
    }
}
