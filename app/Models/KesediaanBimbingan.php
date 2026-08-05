<?php

namespace App\Models;

use App\Enums\HasilKesediaanBimbingan;
use App\Enums\PeranKesediaanBimbingan;
use App\Enums\StatusKesediaanBimbingan;
use Database\Factories\KesediaanBimbinganFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class KesediaanBimbingan extends Model
{
    /** @use HasFactory<KesediaanBimbinganFactory> */
    use HasFactory;

    protected $table = 'kesediaan_bimbingan';

    protected $fillable = [
        'skripsi_id',
        'dosen_id',
        'peran',
        'siklus',
    ];

    protected function casts(): array
    {
        return [
            'peran' => PeranKesediaanBimbingan::class,
            'siklus' => 'integer',
            'status' => StatusKesediaanBimbingan::class,
            'hasil' => HasilKesediaanBimbingan::class,
            'uploaded_at' => 'datetime',
            'diverifikasi_at' => 'datetime',
        ];
    }

    public function skripsi(): BelongsTo
    {
        return $this->belongsTo(Skripsi::class);
    }

    public function dosen(): BelongsTo
    {
        return $this->belongsTo(Dosen::class, 'dosen_id', 'nidn');
    }

    public function verifikator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diverifikasi_oleh');
    }

    public function surat(): MorphMany
    {
        return $this->morphMany(Surat::class, 'suratable');
    }

    public function dokumenPengajuan(): MorphMany
    {
        return $this->morphMany(DokumenPengajuan::class, 'documentable');
    }
}
