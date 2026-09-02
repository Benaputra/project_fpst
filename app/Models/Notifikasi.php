<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notifikasi extends Model
{
    use HasFactory;

    protected $table = 'notifikasi';

    protected $fillable = [
        'user_id',
        'judul',
        'pesan',
        'link',
        'dibaca',
        'dibaca_at',
    ];

    protected function casts(): array
    {
        return [
            'dibaca' => 'boolean',
            'dibaca_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tandaiDibaca(): bool
    {
        return $this->update([
            'dibaca' => true,
            'dibaca_at' => now(),
        ]);
    }

    public static function kirim(User|int|null $user, string $judul, string $pesan, ?string $link = null): ?self
    {
        $userId = $user instanceof User ? $user->id : $user;

        if (!$userId) {
            return null;
        }

        return self::create([
            'user_id' => $userId,
            'judul' => $judul,
            'pesan' => $pesan,
            'link' => $link,
            'dibaca' => false,
        ]);
    }
}
