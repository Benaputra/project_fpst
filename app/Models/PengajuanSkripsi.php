<?php

namespace App\Models;

use App\Enums\StatusPengajuan;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

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

    // Helper status
    public function isSelesai(): bool
    {
        return $this->status === StatusPengajuan::Selesai;
    }

    public function canAjukanSeminar(): bool
    {
        return $this->status === StatusPengajuan::Selesai && !empty($this->nomor_sk_bimbingan);
    }
}
