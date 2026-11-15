<?php

namespace App\Models;

use App\Enums\JenisSuratInternal;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Surat extends Model
{
    use HasFactory;

    protected $table = 'surat';

    protected $fillable = [
        'nomor_surat',
        'jenis_surat',
        'nama_surat',
        'pengajuan_skripsi_id',
        'seminar_skripsi_id',
        'sidang_skripsi_id',
        'pengajuan_surat_id',
        'praktik_lapang_id',
        'program_studi_id',
        'tgl_surat',
        'file_surat',
        'versi',
        'status',
        'verification_token',
        'verification_hash',
        'diterbitkan_oleh',
        'penanda_tangan_id',
        'jabatan_penanda_tangan',
        'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'tgl_surat' => 'date',
            'versi' => 'integer',
        ];
    }

    public function pengajuanSkripsi(): BelongsTo
    {
        return $this->belongsTo(PengajuanSkripsi::class, 'pengajuan_skripsi_id');
    }

    public function seminar(): BelongsTo
    {
        return $this->belongsTo(SeminarSkripsi::class, 'seminar_skripsi_id');
    }

    public function sidang(): BelongsTo
    {
        return $this->belongsTo(SidangSkripsi::class, 'sidang_skripsi_id');
    }

    public function pengajuanSurat(): BelongsTo
    {
        return $this->belongsTo(PengajuanSurat::class, 'pengajuan_surat_id');
    }

    public function praktikLapang(): BelongsTo
    {
        return $this->belongsTo(PraktikLapang::class, 'praktik_lapang_id');
    }

    public function programStudi(): BelongsTo
    {
        return $this->belongsTo(ProgramStudi::class, 'program_studi_id');
    }

    public function penerbit(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diterbitkan_oleh');
    }

    public function penandaTangan(): BelongsTo
    {
        return $this->belongsTo(User::class, 'penanda_tangan_id');
    }

    public function jenisLabel(): string
    {
        $internal = JenisSuratInternal::tryFrom($this->jenis_surat);
        if ($internal) {
            return $internal->label();
        }

        return match ($this->jenis_surat) {
            'sk_bimbingan' => 'SK Pembimbing Skripsi',
            'undangan_seminar' => 'Surat Undangan Seminar',
            'sk_seminar' => 'SK Penguji Seminar',
            'undangan_sidang' => 'Surat Undangan Sidang',
            'sk_sidang' => 'SK Dewan Penguji Sidang',
            'sk_dpl' => 'SK Dosen Pembimbing Lapang (DPL)',
            default => strtoupper($this->jenis_surat),
        };
    }

    public function jenisBadgeClass(): string
    {
        if (JenisSuratInternal::tryFrom($this->jenis_surat)) {
            return 'badge--primary';
        }

        return match ($this->jenis_surat) {
            'sk_bimbingan' => 'badge--primary',
            'undangan_seminar' => 'badge--warning',
            'sk_seminar' => 'badge--purple',
            'undangan_sidang' => 'badge--info',
            'sk_sidang' => 'badge--success',
            default => 'badge--secondary',
        };
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            'aktif' => 'badge--success',
            'diperbarui' => 'badge--secondary',
            default => 'badge--warning',
        };
    }
}
