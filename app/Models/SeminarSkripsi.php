<?php

namespace App\Models;

use App\Enums\StatusPengajuan;
use App\Enums\StatusPenugasanDosen;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class SeminarSkripsi extends Model
{
    use HasFactory;

    protected $table = 'seminar_skripsi';

    protected $fillable = [
        'pengajuan_skripsi_id',
        'file_naskah_seminar',
        'file_acc_pembimbing',
        'file_bukti_bayar_seminar',
        'file_toefl',
        'penguji_seminar_id',
        'tgl_seminar',
        'jam_seminar',
        'ruangan',
        'nomor_undangan_seminar',
        'file_undangan_seminar',
        'nomor_sk_seminar',
        'file_sk_seminar',
        'nilai_seminar',
        'status',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'status' => StatusPengajuan::class,
            'tgl_seminar' => 'date',
            'nilai_seminar' => 'decimal:2',
        ];
    }

    public function pengajuanSkripsi(): BelongsTo
    {
        return $this->belongsTo(PengajuanSkripsi::class, 'pengajuan_skripsi_id');
    }

    public function penguji(): BelongsTo
    {
        return $this->belongsTo(User::class, 'penguji_seminar_id');
    }

    public function surat(): HasMany
    {
        return $this->hasMany(Surat::class, 'seminar_skripsi_id')->latest();
    }

    public function penugasanDosen(): MorphMany
    {
        return $this->morphMany(PenugasanDosen::class, 'assignable')->latest('id');
    }

    public function latestPenugasanPenguji(): ?PenugasanDosen
    {
        return $this->penugasanDosen()->where('peran', 'penguji_seminar')->latest('id')->first();
    }

    public function isPengujiConfirmed(): bool
    {
        $hasPending = $this->penugasanDosen()
            ->where('peran', 'penguji_seminar')
            ->where('status', StatusPenugasanDosen::Menunggu)
            ->exists();

        if ($hasPending) {
            return false;
        }

        if (!$this->penguji_seminar_id) {
            return false;
        }

        return true;
    }

    public function isDiajukan(): bool
    {
        return $this->status === StatusPengajuan::Diajukan;
    }

    public function isDiproses(): bool
    {
        return $this->status === StatusPengajuan::Diproses;
    }

    public function isDitolak(): bool
    {
        return $this->status === StatusPengajuan::Ditolak;
    }

    public function isSelesai(): bool
    {
        return $this->status === StatusPengajuan::Selesai;
    }

    public function canAjukanSidang(): bool
    {
        return $this->isSelesai() && $this->nilai_seminar !== null;
    }

    public function isPenguji(User|int|null $user): bool
    {
        if (!$user) {
            return false;
        }

        $userId = $user instanceof User ? $user->id : (int) $user;
        return (int) $this->penguji_seminar_id === $userId;
    }

    public function getMahasiswaAttribute(): ?User
    {
        return $this->pengajuanSkripsi?->mahasiswa;
    }

    public function getProgramStudiIdAttribute(): ?int
    {
        return $this->pengajuanSkripsi?->program_studi_id;
    }

    // Scopes
    public function scopeForProdi($query, int $prodiId)
    {
        return $query->whereHas('pengajuanSkripsi', fn ($q) => $q->where('program_studi_id', $prodiId));
    }

    public function scopeDiajukan($query)
    {
        return $query->where('status', StatusPengajuan::Diajukan);
    }

    public function scopeDiproses($query)
    {
        return $query->where('status', StatusPengajuan::Diproses);
    }

    public function scopeSelesai($query)
    {
        return $query->where('status', StatusPengajuan::Selesai);
    }
}
