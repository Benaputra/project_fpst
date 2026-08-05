<?php

namespace App\Models;

use Database\Factories\ProgramStudiFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProgramStudi extends Model
{
    /** @use HasFactory<ProgramStudiFactory> */
    use HasFactory;

    protected $table = 'program_studi';

    protected $fillable = [
        'nama',
        'ketua_prodi_id',
        'ttd_ketua_prodi',
    ];

    public function ketuaProdi(): BelongsTo
    {
        return $this->belongsTo(Dosen::class, 'ketua_prodi_id', 'nidn');
    }

    public function dosen(): HasMany
    {
        return $this->hasMany(Dosen::class, 'program_studi_id');
    }

    public function mahasiswa(): HasMany
    {
        return $this->hasMany(Mahasiswa::class, 'program_studi_id');
    }

    public function surat(): HasMany
    {
        return $this->hasMany(Surat::class);
    }

    public function adminProdi(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_program_studi')
            ->withTimestamps();
    }
}
