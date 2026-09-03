<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProgramStudi extends Model
{
    use HasFactory;

    protected $table = 'program_studi';

    protected $fillable = [
        'nama',
        'kode',
        'file_ttd_kaprodi',
    ];

    public function getTtdKaprodiUrlAttribute(): ?string
    {
        if (! $this->file_ttd_kaprodi) {
            return null;
        }

        return asset('storage/' . $this->file_ttd_kaprodi);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function pengajuanSkripsi(): HasMany
    {
        return $this->hasMany(PengajuanSkripsi::class);
    }
}
