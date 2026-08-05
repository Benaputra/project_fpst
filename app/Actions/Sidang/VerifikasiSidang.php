<?php

namespace App\Actions\Sidang;

use App\Enums\KeputusanVerifikasiPengajuan;
use App\Enums\StatusDokumenPengajuan;
use App\Enums\StatusSidangSkripsi;
use App\Models\DokumenPengajuan;
use App\Models\SidangSkripsi;
use App\Models\User;
use App\Services\Audit\CatatAktivitas;
use App\Services\Document\PastikanIntegritasDokumen;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class VerifikasiSidang
{
    public function __construct(
        private readonly PastikanIntegritasDokumen $integritas,
        private readonly CatatAktivitas $audit
    ) {}

    public function execute(User $user, SidangSkripsi $sidang, KeputusanVerifikasiPengajuan $keputusan, ?string $catatan = null): SidangSkripsi
    {
        $catatan = trim((string) $catatan) ?: null;
        if ($keputusan === KeputusanVerifikasiPengajuan::Tolak && $catatan === null) {
            throw ValidationException::withMessages(['catatan_reject' => 'Alasan wajib diisi.']);
        }

        return DB::transaction(function () use ($user, $sidang, $keputusan, $catatan) {
            $s = SidangSkripsi::query()->with('skripsi.mahasiswa')->lockForUpdate()->findOrFail($sidang->id);
            Gate::forUser($user)->authorize('verify', $s);
            if ($s->status !== StatusSidangSkripsi::Diajukan) {
                throw ValidationException::withMessages(['sidang' => 'Hanya pengajuan sidang yang dapat diverifikasi.']);
            }
            $ok = $keputusan === KeputusanVerifikasiPengajuan::Terima;
            $s->forceFill(['status' => $ok ? StatusSidangSkripsi::Diverifikasi : StatusSidangSkripsi::Ditolak,
                'catatan_reject' => $ok ? null : $catatan, 'verified_by' => $user->id, 'verified_at' => now()])->save();
            $dokumen = DokumenPengajuan::query()
                ->whereMorphedTo('documentable', $s)
                ->where('status', StatusDokumenPengajuan::MenungguVerifikasi)
                ->lockForUpdate()
                ->get();
            foreach ($dokumen as $doc) {
                $this->integritas->execute($doc);
            }

            foreach ($dokumen as $doc) {
                $doc->forceFill(['status' => $ok ? StatusDokumenPengajuan::Terverifikasi : StatusDokumenPengajuan::Ditolak,
                    'verified_by' => $user->id, 'verified_at' => now(), 'catatan_verifikasi' => $ok ? null : $catatan])->save();
            }

            $this->audit->execute($user, $s, 'sidang_diverifikasi', [
                'status' => StatusSidangSkripsi::Diajukan->value,
            ], [
                'status' => $s->status->value,
                'keputusan' => $keputusan->value,
            ]);

            return $s->refresh()->load('dokumenPengajuan');
        }, 3);
    }
}
