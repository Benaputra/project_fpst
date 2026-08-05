<?php

namespace App\Models;

use Database\Factories\MahasiswaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Mahasiswa extends Model
{
    /** @use HasFactory<MahasiswaFactory> */
    use HasFactory;

    protected $table = 'mahasiswa';

    protected $primaryKey = 'nim';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'nim',
        'nama',
        'program_studi_id',
        'no_hp',
        'tempat_lahir',
        'tanggal_lahir',
        'angkatan',
        'pembimbing_akademik_id',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_lahir' => 'date',
            'angkatan' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function programStudi(): BelongsTo
    {
        return $this->belongsTo(ProgramStudi::class, 'program_studi_id');
    }

    public function pembimbingAkademik(): BelongsTo
    {
        return $this->belongsTo(Dosen::class, 'pembimbing_akademik_id', 'nidn');
    }

    public function pengajuanJudul(): HasOne
    {
        return $this->hasOne(PengajuanJudul::class, 'nim', 'nim');
    }

    public function skripsi(): HasOne
    {
        return $this->hasOne(Skripsi::class, 'nim', 'nim');
    }
}
