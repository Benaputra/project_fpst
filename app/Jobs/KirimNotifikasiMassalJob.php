<?php

namespace App\Jobs;

use App\Models\Notifikasi;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class KirimNotifikasiMassalJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param array<int, int> $userIds
     */
    public function __construct(
        public readonly array $userIds,
        public readonly string $judul,
        public readonly string $pesan,
        public readonly ?string $link = null,
    ) {}

    public function handle(): void
    {
        $now = now();
        $rows = [];

        foreach (array_unique(array_filter(array_map('intval', $this->userIds))) as $userId) {
            $rows[] = [
                'user_id' => $userId,
                'judul' => $this->judul,
                'pesan' => $this->pesan,
                'link' => $this->link,
                'dibaca' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (! empty($rows)) {
            Notifikasi::insert($rows);
        }
    }
}
