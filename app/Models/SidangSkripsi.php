<?php

namespace App\Models;

use App\Enums\StatusSidangSkripsi;
use Database\Factories\SidangSkripsiFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class SidangSkripsi extends Model
{
    /** @use HasFactory<SidangSkripsiFactory> */
    use HasFactory;

    protected $table = 'sidang_skripsi';

    protected $fillable = [];

    protected function casts(): array
    {
        return ['tanggal' => 'datetime', 'status' => StatusSidangSkripsi::class, 'verified_at' => 'datetime'];
    }

    public function skripsi(): BelongsTo
    {
        return $this->belongsTo(Skripsi::class);
    }

    public function penguji1(): BelongsTo
    {
        return $this->belongsTo(Dosen::class, 'penguji1_id', 'nidn');
    }

    public function penguji2(): BelongsTo
    {
        return $this->belongsTo(Dosen::class, 'penguji2_id', 'nidn');
    }

    public function verifikator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function dokumenPengajuan(): MorphMany
    {
        return $this->morphMany(DokumenPengajuan::class, 'documentable');
    }

    public function surat(): MorphMany
    {
        return $this->morphMany(Surat::class, 'suratable');
    }
}
