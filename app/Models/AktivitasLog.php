<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AktivitasLog extends Model
{
    use HasFactory;

    protected $table = 'aktivitas_log';

    protected $fillable = [
        'user_id',
        'aksi',
        'deskripsi',
        'ip_address',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function catat(?User $user, string $aksi, string $deskripsi): self
    {
        return self::create([
            'user_id' => $user?->id ?? auth()->id(),
            'aksi' => $aksi,
            'deskripsi' => $deskripsi,
            'ip_address' => request()->ip(),
        ]);
    }
}
