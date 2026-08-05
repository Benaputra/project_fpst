<?php

namespace App\Models;

use Database\Factories\DosenFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Dosen extends Model
{
    /** @use HasFactory<DosenFactory> */
    use HasFactory;

    protected $table = 'dosen';

    protected $primaryKey = 'nidn';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'nidn',
        'nama',
        'nuptk',
        'program_studi_id',
        'no_hp',
        'tempat_lahir',
        'tanggal_lahir',
        'jabatan_fungsional',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_lahir' => 'date',
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

    public function programStudiDipimpin(): HasOne
    {
        return $this->hasOne(ProgramStudi::class, 'ketua_prodi_id', 'nidn');
    }

    public function mahasiswaBimbinganAkademik(): HasMany
    {
        return $this->hasMany(Mahasiswa::class, 'pembimbing_akademik_id', 'nidn');
    }

    public function pengajuanJudulDiverifikasi(): HasMany
    {
        return $this->hasMany(PengajuanJudul::class, 'diverifikasi_oleh', 'nidn');
    }

    public function kesediaanBimbingan(): HasMany
    {
        return $this->hasMany(KesediaanBimbingan::class, 'dosen_id', 'nidn');
    }

    public function skripsiSebagaiPembimbing1(): HasMany
    {
        return $this->hasMany(Skripsi::class, 'pembimbing1_id', 'nidn');
    }

    public function skripsiSebagaiPembimbing2(): HasMany
    {
        return $this->hasMany(Skripsi::class, 'pembimbing2_id', 'nidn');
    }

    public function suratDitandatangani(): HasMany
    {
        return $this->hasMany(Surat::class, 'signed_by', 'nidn');
    }
}
