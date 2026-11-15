<?php

namespace App\Models;

use App\Enums\StatusPenugasanDosen;
use App\Enums\StatusPraktikLapang;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class PraktikLapang extends Model
{
    use HasFactory;

    protected $table = 'praktik_lapang';

    protected $fillable = [
        'mahasiswa_id',
        'program_studi_id',
        'lokasi_pl_id',
        'periode_akademik',
        'sks_ditempuh',
        'judul_kegiatan',
        'file_transkrip_sks',
        'pengajuan_surat_id',
        'file_surat_balasan',
        'dpl_id',
        'nomor_sk_dpl',
        'file_sk_dpl',
        'tgl_mulai',
        'tgl_selesai',
        'file_laporan_akhir',
        'tgl_seminar_pl',
        'jam_seminar_pl',
        'ruangan_seminar_pl',
        'nilai_pembimbing_lapang',
        'nilai_dpl',
        'nilai_akhir',
        'file_sertifikat',
        'status',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'status' => StatusPraktikLapang::class,
            'sks_ditempuh' => 'integer',
            'tgl_mulai' => 'date',
            'tgl_selesai' => 'date',
            'tgl_seminar_pl' => 'date',
            'nilai_pembimbing_lapang' => 'decimal:2',
            'nilai_dpl' => 'decimal:2',
            'nilai_akhir' => 'decimal:2',
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

    public function lokasiPl(): BelongsTo
    {
        return $this->belongsTo(LokasiPl::class, 'lokasi_pl_id');
    }

    public function pengajuanSurat(): BelongsTo
    {
        return $this->belongsTo(PengajuanSurat::class, 'pengajuan_surat_id');
    }

    public function dpl(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dpl_id');
    }

    public function logbooks(): HasMany
    {
        return $this->hasMany(LogbookPl::class, 'praktik_lapang_id')->orderByDesc('tanggal')->orderByDesc('id');
    }

    public function surat(): HasMany
    {
        return $this->hasMany(Surat::class, 'praktik_lapang_id');
    }

    public function penugasanDosen(): MorphMany
    {
        return $this->morphMany(PenugasanDosen::class, 'assignable')->latest('id');
    }

    public function isDplConfirmed(): bool
    {
        if (! $this->dpl_id) {
            return false;
        }

        return $this->penugasanDosen()
            ->where('peran', 'dpl')
            ->where('dosen_id', $this->dpl_id)
            ->where('status', StatusPenugasanDosen::Disetujui)
            ->exists();
    }
}
