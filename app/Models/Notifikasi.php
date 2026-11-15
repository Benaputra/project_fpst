<?php

namespace App\Models;

use App\Jobs\KirimNotifikasiMassalJob;
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

    /**
     * Kirim notifikasi ke role pengelola terkait (Admin Utama, Kaprodi, dan/atau Admin Prodi).
     *
     * @param int|null $programStudiId ID program studi mahasiswa/pengajuan
     * @param string $judul
     * @param string $pesan
     * @param string|null $link
     * @param int|null $excludeUserId ID user yang tidak perlu dikirimi notifikasi
     * @param array<\App\Enums\UserRole> $roles Daftar role penerima
     */
    public static function kirimKePengelola(
        ?int $programStudiId,
        string $judul,
        string $pesan,
        ?string $link = null,
        ?int $excludeUserId = null,
        array $roles = [\App\Enums\UserRole::AdminUtama, \App\Enums\UserRole::Kaprodi, \App\Enums\UserRole::AdminProdi]
    ): void {
        $roleValues = array_map(fn($r) => $r instanceof \BackedEnum ? $r->value : (string) $r, $roles);
        $query = User::query()->with('extraRoles:id,user_id,role,program_studi_id')->whereHasRoles($roleValues);

        if ($excludeUserId) {
            $query->where('id', '!=', $excludeUserId);
        }

        $users = $query->get(['id', 'role', 'program_studi_id']);
        $now = now();
        $rows = [];

        foreach ($users as $user) {
            if (
                $user->isAdminUtama() ||
                !$programStudiId ||
                $user->program_studi_id === $programStudiId
            ) {
                $userLink = $link;
                if (!$userLink) {
                    if ($user->isTendik() || $user->isPimpinanFakultas()) {
                        $userLink = route('admin.persuratan.index');
                    } elseif ($user->isKaprodi()) {
                        $userLink = route('kaprodi.penetapan.index');
                    } elseif ($user->isAdmin()) {
                        $userLink = route('admin.administrasi.index');
                    }
                }

                $rows[] = [
                    'user_id' => $user->id,
                    'judul' => $judul,
                    'pesan' => $pesan,
                    'link' => $userLink,
                    'dibaca' => false,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        if (! empty($rows)) {
            self::insert($rows);
        }
    }

    /**
     * Dispatch pengiriman notifikasi massal ke queue worker agar tidak memblokir respons HTTP saat peak.
     *
     * @param array<int, int> $userIds
     */
    public static function kirimMassalAsync(array $userIds, string $judul, string $pesan, ?string $link = null): void
    {
        if (empty($userIds)) {
            return;
        }

        KirimNotifikasiMassalJob::dispatch($userIds, $judul, $pesan, $link);
    }
}

