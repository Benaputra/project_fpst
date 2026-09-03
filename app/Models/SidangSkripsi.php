<?php

namespace App\Models;

use App\Enums\StatusPengajuan;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SidangSkripsi extends Model
{
    use HasFactory;

    protected $table = 'sidang_skripsi';

    protected $fillable = [
        'pengajuan_skripsi_id',
        'file_naskah_sidang',
        'file_acc_sidang',
        'file_bebas_revisi_seminar',
        'file_bukti_bayar_sidang',
        'penguji_1_id',
        'penguji_2_id',
        'tgl_sidang',
        'jam_sidang',
        'ruangan',
        'nomor_undangan_sidang',
        'file_undangan_sidang',
        'nomor_sk_sidang',
        'file_sk_sidang',
        'nilai_sidang',
        'status',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'status' => StatusPengajuan::class,
            'tgl_sidang' => 'date',
            'nilai_sidang' => 'decimal:2',
        ];
    }

    public function pengajuanSkripsi(): BelongsTo
    {
        return $this->belongsTo(PengajuanSkripsi::class, 'pengajuan_skripsi_id');
    }

    public function penguji1(): BelongsTo
    {
        return $this->belongsTo(User::class, 'penguji_1_id');
    }

    public function penguji2(): BelongsTo
    {
        return $this->belongsTo(User::class, 'penguji_2_id');
    }

    public function surat(): HasMany
    {
        return $this->hasMany(Surat::class, 'sidang_skripsi_id')->latest();
    }

    public function isSelesai(): bool
    {
        return $this->status === StatusPengajuan::Selesai;
    }
}
