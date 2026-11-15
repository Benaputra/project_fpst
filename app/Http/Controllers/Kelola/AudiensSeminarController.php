<?php

namespace App\Http\Controllers\Kelola;

use App\Enums\StatusAudiensSeminar;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\SeminarAudiens;
use App\Models\SeminarSkripsi;
use App\Models\User;
use App\Services\QrCodeService;
use App\Services\SeminarAudiensService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Enum;
use Illuminate\View\View;

class AudiensSeminarController extends Controller
{
    public function show(Request $request, SeminarSkripsi $seminar, QrCodeService $qrService): View
    {
        $seminar->loadMissing(['pengajuanSkripsi.mahasiswa', 'pengajuanSkripsi.programStudi', 'penguji']);
        $this->authorizeSeminarManagement($request->user(), $seminar);

        if (! $seminar->token_presensi) {
            $seminar->update(['token_presensi' => strtoupper(Str::random(10))]);
        }

        $daftarAudiens = SeminarAudiens::query()
            ->with(['mahasiswa.programStudi', 'validator'])
            ->where('seminar_skripsi_id', $seminar->id)
            ->orderByRaw("CASE WHEN peran = 'penyanggah' THEN 0 ELSE 1 END")
            ->orderBy('waktu_daftar')
            ->get();

        $qrTokenSvg = $qrService->makeSvgDataUri($seminar->token_presensi);

        return view('kelola.audiens.show', compact('seminar', 'daftarAudiens', 'qrTokenSvg'));
    }

    public function validasi(Request $request, SeminarAudiens $audiens, SeminarAudiensService $audiensService): RedirectResponse
    {
        $audiens->loadMissing('seminar.pengajuanSkripsi');
        $this->authorizeSeminarManagement($request->user(), $audiens->seminar);

        $validated = $request->validate([
            'status' => ['required', new Enum(StatusAudiensSeminar::class)],
            'catatan' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $status = StatusAudiensSeminar::from($validated['status']);
            $audiensService->validasiPresensi($audiens, $request->user(), $status, $validated['catatan'] ?? null);

            return back()->with('success', 'Status kehadiran penonton/penyanggah berhasil diperbarui.');
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    private function authorizeSeminarManagement(User $user, SeminarSkripsi $seminar): void
    {
        if ($user->hasRole(UserRole::AdminUtama, UserRole::Tendik)) {
            return;
        }

        $prodiId = (int) ($seminar->pengajuanSkripsi?->program_studi_id ?? 0);

        if ($user->hasRole(UserRole::AdminProdi, UserRole::Kaprodi)) {
            if ((int) $user->program_studi_id === $prodiId) {
                return;
            }
            abort(403, 'Anda hanya berwenang mengelola daftar hadir seminar program studi Anda.');
        }

        if ($user->isDosen()) {
            $skripsi = $seminar->pengajuanSkripsi;
            $involved = in_array((int) $user->id, array_filter([
                $skripsi?->pembimbing_1_id ? (int) $skripsi->pembimbing_1_id : null,
                $skripsi?->pembimbing_2_id ? (int) $skripsi->pembimbing_2_id : null,
                $seminar->penguji_seminar_id ? (int) $seminar->penguji_seminar_id : null,
            ]), true);

            if ($involved) {
                return;
            }
        }

        abort(403, 'Anda tidak berwenang memvalidasi presensi sesi seminar ini.');
    }
}
