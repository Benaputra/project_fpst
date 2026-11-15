<?php

namespace App\Models;

use App\Enums\JenisSuratInternal;
use App\Enums\StatusPengajuanSurat;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PengajuanSurat extends Model
{
    use HasFactory;

    protected $table = 'pengajuan_surat';

    protected $fillable = [
        'mahasiswa_id',
        'program_studi_id',
        'jenis_surat',
        'perihal',
        'tujuan_instansi',
        'alamat_instansi',
        'tanggal_mulai',
        'tanggal_selesai',
        'keterangan_kebutuhan',
        'file_pendukung',
        'status',
        'catatan_penolakan',
        'diverifikasi_oleh',
        'diverifikasi_pada',
        'disetujui_oleh',
        'disetujui_pada',
        'diterbitkan_oleh',
        'diterbitkan_pada',
        'nomor_surat_terbit',
        'file_surat_terbit',
        'verification_token',
    ];

    protected function casts(): array
    {
        return [
            'jenis_surat' => JenisSuratInternal::class,
            'status' => StatusPengajuanSurat::class,
            'tanggal_mulai' => 'date',
            'tanggal_selesai' => 'date',
            'diverifikasi_pada' => 'datetime',
            'disetujui_pada' => 'datetime',
            'diterbitkan_pada' => 'datetime',
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

    public function verifikator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diverifikasi_oleh');
    }

    public function penyetuju(): BelongsTo
    {
        return $this->belongsTo(User::class, 'disetujui_oleh');
    }

    public function penerbit(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diterbitkan_oleh');
    }

    public function daftarSurat(): HasMany
    {
        return $this->hasMany(Surat::class, 'pengajuan_surat_id')->latest();
    }

    public function suratAktif(): HasOne
    {
        return $this->hasOne(Surat::class, 'pengajuan_surat_id')
            ->where('status', 'aktif')
            ->latestOfMany();
    }
}
