<?php

namespace App\Actions\Seminar;

use App\Enums\StatusSeminar;
use App\Models\Dosen;
use App\Models\Seminar;
use App\Models\User;
use App\Services\Audit\CatatAktivitas;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class JadwalkanSeminar
{
    public function __construct(private readonly CatatAktivitas $audit) {}

    public function execute(
        User $user,
        Seminar $seminar,
        string $penguji1Id,
        string $penguji2Id,
        Carbon $tanggal,
        string $tempat
    ): Seminar {
        $tempat = trim($tempat);

        return DB::transaction(function () use (
            $user,
            $seminar,
            $penguji1Id,
            $penguji2Id,
            $tanggal,
            $tempat
        ) {
            $terkunci = Seminar::query()->with('skripsi.mahasiswa')
                ->lockForUpdate()->findOrFail($seminar->id);
            Gate::forUser($user)->authorize('schedule', $terkunci);
            if ($penguji1Id === $penguji2Id) {
                throw ValidationException::withMessages([
                    'penguji2_id' => 'Penguji 1 dan Penguji 2 harus berbeda.',
                ]);
            }
            if ($tempat === '') {
                throw ValidationException::withMessages(['tempat' => 'Tempat wajib diisi.']);
            }

            if ($terkunci->status === StatusSeminar::Dijadwalkan
                && $terkunci->penguji1_id === $penguji1Id
                && $terkunci->penguji2_id === $penguji2Id
                && $terkunci->tanggal?->equalTo($tanggal)
                && $terkunci->tempat === $tempat) {
                return $terkunci;
            }
            if ($terkunci->status !== StatusSeminar::Diverifikasi) {
                throw ValidationException::withMessages([
                    'seminar' => 'Hanya seminar terverifikasi yang belum dijadwalkan dapat diproses.',
                ]);
            }

            $programStudiId = (int) $terkunci->skripsi->mahasiswa->program_studi_id;
            $penguji = Dosen::query()->whereIn('nidn', [$penguji1Id, $penguji2Id])
                ->lockForUpdate()->get()->keyBy('nidn');
            foreach ([$penguji1Id, $penguji2Id] as $index => $id) {
                $dosen = $penguji->get($id);
                if (! $dosen instanceof Dosen
                    || (int) $dosen->program_studi_id !== $programStudiId) {
                    throw ValidationException::withMessages([
                        'penguji'.($index + 1).'_id' => 'Penguji harus dosen dari program studi mahasiswa.',
                    ]);
                }
            }

            $terkunci->forceFill([
                'penguji1_id' => $penguji1Id,
                'penguji2_id' => $penguji2Id,
                'tanggal' => $tanggal,
                'tempat' => $tempat,
                'status' => StatusSeminar::Dijadwalkan,
            ])->save();

            $this->audit->execute($user, $terkunci, 'seminar_dijadwalkan', [
                'status' => StatusSeminar::Diverifikasi->value,
            ], [
                'status' => StatusSeminar::Dijadwalkan->value,
                'tanggal' => $tanggal->toDateTimeString(),
                'tempat' => $tempat,
                'penguji1_id' => $penguji1Id,
                'penguji2_id' => $penguji2Id,
            ]);

            return $terkunci->refresh()->load(['penguji1', 'penguji2']);
        }, 3);
    }
}
