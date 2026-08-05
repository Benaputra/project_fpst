<?php

namespace App\Models;

use App\Enums\JenisDokumenPengajuan;
use App\Enums\StatusDokumenPengajuan;
use Database\Factories\DokumenPengajuanFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class DokumenPengajuan extends Model
{
    /** @use HasFactory<DokumenPengajuanFactory> */
    use HasFactory;

    protected $table = 'dokumen_pengajuan';

    protected $fillable = [];

    protected function casts(): array
    {
        return [
            'jenis' => JenisDokumenPengajuan::class,
            'versi' => 'integer',
            'status' => StatusDokumenPengajuan::class,
            'uploaded_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }

    public function documentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function pengunggah(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function verifikator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
