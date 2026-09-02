<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'nomor_induk',
        'password',
        'role',
        'program_studi_id',
        'no_hp',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }

    public function programStudi(): BelongsTo
    {
        return $this->belongsTo(ProgramStudi::class);
    }

    // Role helpers
    public function isMahasiswa(): bool
    {
        return $this->role === UserRole::Mahasiswa;
    }

    public function isDosen(): bool
    {
        return $this->role === UserRole::Dosen || $this->role === UserRole::Kaprodi;
    }

    public function isKaprodi(): bool
    {
        return $this->role === UserRole::Kaprodi;
    }

    public function isAdminProdi(): bool
    {
        return $this->role === UserRole::AdminProdi;
    }

    public function isAdminUtama(): bool
    {
        return $this->role === UserRole::AdminUtama;
    }

    public function isAdmin(): bool
    {
        return $this->isAdminProdi() || $this->isAdminUtama();
    }

    // Relasi Mahasiswa ke Pengajuan Skripsi
    public function pengajuanSkripsi(): HasOne
    {
        return $this->hasOne(PengajuanSkripsi::class, 'mahasiswa_id')->latestOfMany();
    }

    // Relasi Dosen
    public function bimbinganPertama(): HasMany
    {
        return $this->hasMany(PengajuanSkripsi::class, 'pembimbing_1_id');
    }

    public function bimbinganKedua(): HasMany
    {
        return $this->hasMany(PengajuanSkripsi::class, 'pembimbing_2_id');
    }

    public function mengujiSeminar(): HasMany
    {
        return $this->hasMany(SeminarSkripsi::class, 'penguji_seminar_id');
    }

    public function mengujiSidangPertama(): HasMany
    {
        return $this->hasMany(SidangSkripsi::class, 'penguji_1_id');
    }

    public function mengujiSidangKedua(): HasMany
    {
        return $this->hasMany(SidangSkripsi::class, 'penguji_2_id');
    }

    // Relasi Notifikasi & Log Aktivitas
    public function notifikasi(): HasMany
    {
        return $this->hasMany(Notifikasi::class)->latest();
    }

    public function unreadNotifikasiCount(): int
    {
        return $this->hasMany(Notifikasi::class)->where('dibaca', false)->count();
    }

    public function aktivitasLog(): HasMany
    {
        return $this->hasMany(AktivitasLog::class)->latest();
    }
}
