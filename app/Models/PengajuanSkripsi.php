<?php

namespace App\Models;

use App\Enums\StatusPengajuan;
use App\Enums\StatusPenugasanDosen;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class PengajuanSkripsi extends Model
{
    use HasFactory;

    protected $table = 'pengajuan_skripsi';

    protected $fillable = [
        'mahasiswa_id',
        'program_studi_id',
        'judul',
        'abstrak',
        'file_proposal',
        'file_transkrip',
        'file_bukti_bayar',
        'pembimbing_1_id',
        'pembimbing_2_id',
        'nomor_sk_bimbingan',
        'tgl_sk_bimbingan',
        'file_sk_bimbingan',
        'status',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'status' => StatusPengajuan::class,
            'tgl_sk_bimbingan' => 'date',
        ];
    }

    public function mahasiswa(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mahasiswa_id');
    }

    public function programStudi(): BelongsTo
    {
        return $this->belongsTo(ProgramStudi::class, 'program_studi_id');
    }

    public function pembimbing1(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pembimbing_1_id');
    }

    public function pembimbing2(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pembimbing_2_id');
    }

    public function seminar(): HasOne
    {
        return $this->hasOne(SeminarSkripsi::class, 'pengajuan_skripsi_id');
    }

    public function sidang(): HasOne
    {
        return $this->hasOne(SidangSkripsi::class, 'pengajuan_skripsi_id');
    }

    public function surat(): HasMany
    {
        return $this->hasMany(Surat::class, 'pengajuan_skripsi_id')->latest();
    }

    public function penugasanDosen(): MorphMany
    {
        return $this->morphMany(PenugasanDosen::class, 'assignable')->latest('id');
    }

    public function latestPenugasanPembimbing1(): ?PenugasanDosen
    {
        return $this->penugasanDosen()->where('peran', 'pembimbing_1')->latest('id')->first();
    }

    public function latestPenugasanPembimbing2(): ?PenugasanDosen
    {
        return $this->penugasanDosen()->where('peran', 'pembimbing_2')->latest('id')->first();
    }

    public function isPembimbingConfirmed(): bool
    {
        // Jika ada penugasan yang masih 'menunggu' atau 'ditolak', maka belum konfirm
        $hasPending = $this->penugasanDosen()
            ->whereIn('peran', ['pembimbing_1', 'pembimbing_2'])
            ->where('status', StatusPenugasanDosen::Menunggu)
            ->exists();

        if ($hasPending) {
            return false;
        }

        // Jika pembimbing 1 belum ditentukan
        if (!$this->pembimbing_1_id) {
            return false;
        }

        return true;
    }

    // Helper status
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

    public function canAjukanSeminar(): bool
    {
        return $this->status === StatusPengajuan::Selesai && !empty($this->nomor_sk_bimbingan);
    }

    public function isPembimbing(User|int|null $user): bool
    {
        if (!$user) {
            return false;
        }

        $userId = $user instanceof User ? $user->id : (int) $user;
        return (int) $this->pembimbing_1_id === $userId || (int) $this->pembimbing_2_id === $userId;
    }

    // Scopes
    public function scopeForProdi($query, int $prodiId)
    {
        return $query->where('program_studi_id', $prodiId);
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
