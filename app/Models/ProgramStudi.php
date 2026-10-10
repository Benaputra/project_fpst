<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

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

    public function kaprodi(): HasOne
    {
        return $this->hasOne(User::class)->where('role', UserRole::Kaprodi);
    }

    public function dosen(): HasMany
    {
        return $this->hasMany(User::class)->whereIn('role', [UserRole::Dosen, UserRole::Kaprodi]);
    }

    public function mahasiswa(): HasMany
    {
        return $this->hasMany(User::class)->where('role', UserRole::Mahasiswa);
    }

    public function pengajuanSkripsi(): HasMany
    {
        return $this->hasMany(PengajuanSkripsi::class);
    }
}
