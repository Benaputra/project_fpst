<?php

namespace App\Models;

use App\Enums\PeranAudiensSeminar;
use App\Enums\StatusAudiensSeminar;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SeminarAudiens extends Model
{
    use HasFactory;

    protected $table = 'seminar_audiens';

    protected $fillable = [
        'seminar_skripsi_id',
        'mahasiswa_id',
        'peran',
        'status',
        'waktu_daftar',
        'waktu_presensi',
        'divalidasi_oleh',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'peran' => PeranAudiensSeminar::class,
            'status' => StatusAudiensSeminar::class,
            'waktu_daftar' => 'datetime',
            'waktu_presensi' => 'datetime',
        ];
    }

    public function seminar(): BelongsTo
    {
        return $this->belongsTo(SeminarSkripsi::class, 'seminar_skripsi_id');
    }

    public function mahasiswa(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mahasiswa_id');
    }

    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'divalidasi_oleh');
    }

    public function scopeAktif(Builder $query): Builder
    {
        return $query->whereIn('status', [
            StatusAudiensSeminar::Terdaftar->value,
            StatusAudiensSeminar::Hadir->value,
        ]);
    }

    public function scopeHadir(Builder $query): Builder
    {
        return $query->where('status', StatusAudiensSeminar::Hadir->value);
    }
}
