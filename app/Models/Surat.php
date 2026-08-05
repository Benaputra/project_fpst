<?php

namespace App\Models;

use App\Enums\JenisSurat;
use App\Enums\StatusSurat;
use Database\Factories\SuratFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Surat extends Model
{
    /** @use HasFactory<SuratFactory> */
    use HasFactory;

    protected $table = 'surat';

    protected $fillable = [];

    protected function casts(): array
    {
        return [
            'jenis_surat' => JenisSurat::class,
            'versi' => 'integer',
            'status' => StatusSurat::class,
            'generated_at' => 'datetime',
            'verified_at' => 'datetime',
            'signed_at' => 'datetime',
        ];
    }

    public function suratable(): MorphTo
    {
        return $this->morphTo();
    }

    public function programStudi(): BelongsTo
    {
        return $this->belongsTo(ProgramStudi::class);
    }

    public function verifikator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function penandaTangan(): BelongsTo
    {
        return $this->belongsTo(Dosen::class, 'signed_by', 'nidn');
    }
}
