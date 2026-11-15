<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Builder;
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
        'jabatan',
        'unit_kerja',
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

    public function extraRoles(): HasMany
    {
        return $this->hasMany(UserRoleAssignment::class);
    }

    /**
     * Kembalikan seluruh role aktif (role utama + role tambahan).
     *
     * @return array<int, UserRole>
     */
    public function allRoles(): array
    {
        $roles = [];
        if ($this->role instanceof UserRole) {
            $roles[$this->role->value] = $this->role;
        }

        $extra = $this->relationLoaded('extraRoles')
            ? $this->extraRoles
            : $this->extraRoles()->get();

        foreach ($extra as $assignment) {
            if ($assignment->role instanceof UserRole) {
                $roles[$assignment->role->value] = $assignment->role;
            }
        }

        return array_values($roles);
    }

    public function hasRole(UserRole|string ...$roles): bool
    {
        $targetValues = array_map(
            fn (UserRole|string $role): string => $role instanceof UserRole ? $role->value : $role,
            $roles
        );

        foreach ($this->allRoles() as $ownedRole) {
            if (in_array($ownedRole->value, $targetValues, true)) {
                return true;
            }
        }

        return false;
    }

    public function scopeWhereHasRoles(Builder $query, array $roles): Builder
    {
        $values = array_map(
            fn (UserRole|string $role): string => $role instanceof UserRole ? $role->value : $role,
            $roles
        );

        return $query->where(function (Builder $q) use ($values) {
            $q->whereIn('role', $values)
                ->orWhereHas('extraRoles', fn (Builder $sub) => $sub->whereIn('role', $values));
        });
    }

    /**
     * Sinkronkan role tambahan di luar role utama.
     *
     * @param array<int, UserRole|string> $roles
     */
    public function syncExtraRoles(array $roles): void
    {
        $primaryValue = $this->role?->value;
        $normalized = collect($roles)
            ->map(fn ($r) => $r instanceof UserRole ? $r->value : (string) $r)
            ->filter(fn ($v) => $v !== '' && $v !== $primaryValue && UserRole::tryFrom($v) !== null)
            ->unique()
            ->values();

        $this->extraRoles()->whereNotIn('role', $normalized->all())->delete();

        foreach ($normalized as $roleValue) {
            $this->extraRoles()->firstOrCreate(
                ['role' => $roleValue],
                ['program_studi_id' => $this->program_studi_id]
            );
        }

        $this->unsetRelation('extraRoles');
    }

    // Role helpers
    public function isMahasiswa(): bool
    {
        return $this->hasRole(UserRole::Mahasiswa);
    }

    public function isDosen(): bool
    {
        return $this->hasRole(UserRole::Dosen, UserRole::Kaprodi);
    }

    public function isKaprodi(): bool
    {
        return $this->hasRole(UserRole::Kaprodi);
    }

    public function isAdminProdi(): bool
    {
        return $this->hasRole(UserRole::AdminProdi);
    }

    public function isTendik(): bool
    {
        return $this->hasRole(UserRole::Tendik);
    }

    public function isPimpinanFakultas(): bool
    {
        return $this->hasRole(UserRole::PimpinanFakultas);
    }

    public function isAdminUtama(): bool
    {
        return $this->hasRole(UserRole::AdminUtama);
    }

    public function isAdmin(): bool
    {
        return $this->isAdminProdi() || $this->isAdminUtama();
    }

    public function isStaffPersuratan(): bool
    {
        return $this->hasRole(UserRole::AdminUtama, UserRole::AdminProdi, UserRole::Tendik);
    }

    public function canApproveSurat(): bool
    {
        return $this->hasRole(UserRole::AdminUtama, UserRole::PimpinanFakultas, UserRole::Kaprodi);
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

    public function suratDiterbitkan(): HasMany
    {
        return $this->hasMany(Surat::class, 'diterbitkan_oleh')->latest();
    }

    public function penugasanDosen(): HasMany
    {
        return $this->hasMany(PenugasanDosen::class, 'dosen_id')->latest();
    }

    public function pendingPenugasanDosenCount(): int
    {
        return $this->penugasanDosen()->where('status', \App\Enums\StatusPenugasanDosen::Menunggu)->count();
    }

    public function riwayatAudiensSeminar(): HasMany
    {
        return $this->hasMany(SeminarAudiens::class, 'mahasiswa_id')->latest('waktu_daftar');
    }

    public function praktikLapang(): HasMany
    {
        return $this->hasMany(PraktikLapang::class, 'mahasiswa_id')->latest();
    }

    public function bimbinganPl(): HasMany
    {
        return $this->hasMany(PraktikLapang::class, 'dpl_id')->latest();
    }
}
