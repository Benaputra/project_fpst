<?php

namespace App\Models;

use App\Enums\UserRole;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }

    public function mahasiswa(): HasOne
    {
        return $this->hasOne(Mahasiswa::class);
    }

    public function dosen(): HasOne
    {
        return $this->hasOne(Dosen::class);
    }

    public function programStudiAdministrasi(): BelongsToMany
    {
        return $this->belongsToMany(ProgramStudi::class, 'user_program_studi')
            ->withTimestamps();
    }

    public function kesediaanBimbinganDiverifikasi(): HasMany
    {
        return $this->hasMany(KesediaanBimbingan::class, 'diverifikasi_oleh');
    }

    public function suratDiverifikasi(): HasMany
    {
        return $this->hasMany(Surat::class, 'verified_by');
    }

    public function dokumenPengajuanDiunggah(): HasMany
    {
        return $this->hasMany(DokumenPengajuan::class, 'uploaded_by');
    }

    public function dokumenPengajuanDiverifikasi(): HasMany
    {
        return $this->hasMany(DokumenPengajuan::class, 'verified_by');
    }

    public function isMahasiswa(): bool
    {
        return $this->role === UserRole::Mahasiswa;
    }

    public function isDosen(): bool
    {
        return $this->role === UserRole::Dosen;
    }

    public function isAdminProdi(): bool
    {
        return $this->role === UserRole::AdminProdi;
    }

    public function isAdminUtama(): bool
    {
        return $this->role === UserRole::AdminUtama;
    }

    public function isKetuaProdiUntuk(ProgramStudi|int $programStudi): bool
    {
        if (! $this->isDosen()) {
            return false;
        }

        $programStudiId = $programStudi instanceof ProgramStudi
            ? $programStudi->getKey()
            : $programStudi;

        return $this->dosen()
            ->whereHas(
                'programStudiDipimpin',
                fn ($query) => $query->whereKey($programStudiId)
            )
            ->exists();
    }

    public function isKetuaProdi(): bool
    {
        if (! $this->isDosen()) {
            return false;
        }

        return $this->dosen()
            ->whereHas('programStudiDipimpin')
            ->exists();
    }

    public function memilikiAksesAdministratifKeProgramStudi(ProgramStudi|int $programStudi): bool
    {
        if ($this->isAdminUtama()) {
            return true;
        }

        if (! $this->isAdminProdi()) {
            return false;
        }

        $programStudiId = $programStudi instanceof ProgramStudi
            ? $programStudi->getKey()
            : $programStudi;

        return $this->programStudiAdministrasi()
            ->whereKey($programStudiId)
            ->exists();
    }
}
