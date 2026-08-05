<?php

namespace App\Actions\Sidang;

use App\Enums\StatusSidangSkripsi;
use App\Models\Dosen;
use App\Models\SidangSkripsi;
use App\Models\User;
use App\Services\Audit\CatatAktivitas;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class JadwalkanSidang
{
    public function __construct(private readonly CatatAktivitas $audit) {}

    public function execute(User $user, SidangSkripsi $sidang, string $p1, string $p2, Carbon $tanggal, string $tempat): SidangSkripsi
    {
        $tempat = trim($tempat);

        return DB::transaction(function () use ($user, $sidang, $p1, $p2, $tanggal, $tempat) {
            $s = SidangSkripsi::query()->with('skripsi.mahasiswa')->lockForUpdate()->findOrFail($sidang->id);
            Gate::forUser($user)->authorize('schedule', $s);
            if ($p1 === $p2) {
                throw ValidationException::withMessages(['penguji2_id' => 'Penguji harus berbeda.']);
            }
            if ($tempat === '') {
                throw ValidationException::withMessages(['tempat' => 'Tempat wajib diisi.']);
            }
            if ($s->status === StatusSidangSkripsi::Dijadwalkan && $s->penguji1_id === $p1 && $s->penguji2_id === $p2 && $s->tanggal?->equalTo($tanggal) && $s->tempat === $tempat) {
                return $s;
            }
            if ($s->status !== StatusSidangSkripsi::Diverifikasi) {
                throw ValidationException::withMessages(['sidang' => 'Sidang harus terverifikasi dan belum dijadwalkan.']);
            }
            $prodi = (int) $s->skripsi->mahasiswa->program_studi_id;
            $penguji = Dosen::query()->whereIn('nidn', [$p1, $p2])->lockForUpdate()->get()->keyBy('nidn');
            foreach ([$p1, $p2] as $i => $id) {
                if (! $penguji->get($id) instanceof Dosen || (int) $penguji->get($id)->program_studi_id !== $prodi) {
                    throw ValidationException::withMessages(['penguji'.($i + 1).'_id' => 'Penguji harus dari program studi mahasiswa.']);
                }
            }
            $s->forceFill(['penguji1_id' => $p1, 'penguji2_id' => $p2, 'tanggal' => $tanggal, 'tempat' => $tempat, 'status' => StatusSidangSkripsi::Dijadwalkan])->save();

            $this->audit->execute($user, $s, 'sidang_dijadwalkan', [
                'status' => StatusSidangSkripsi::Diverifikasi->value,
            ], [
                'status' => StatusSidangSkripsi::Dijadwalkan->value,
                'tanggal' => $tanggal->toDateTimeString(),
                'tempat' => $tempat,
                'penguji1_id' => $p1,
                'penguji2_id' => $p2,
            ]);

            return $s->refresh()->load(['penguji1', 'penguji2']);
        }, 3);
    }
}
