<?php

namespace App\Models;

use App\Enums\StatusPengajuan;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function isSelesai(): bool
    {
        return $this->status === StatusPengajuan::Selesai;
    }
}
