<?php

namespace App\Http\Controllers;

use App\Enums\StatusPenugasanDosen;
use App\Enums\UserRole;
use App\Models\PengajuanSkripsi;
use App\Models\PengajuanSurat;
use App\Models\PenugasanDosen;
use App\Models\PraktikLapang;
use App\Models\SeminarSkripsi;
use App\Models\SidangSkripsi;
use App\Models\Surat;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DokumenController extends Controller
{
    public function download(Request $request, string $path): StreamedResponse
    {
        $decodedPath = $this->resolveAuthorizedPath($request->user(), $path);

        return Storage::disk('local')->download($decodedPath, basename($decodedPath), [
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function view(Request $request, string $path): BinaryFileResponse
    {
        $decodedPath = $this->resolveAuthorizedPath($request->user(), $path);

        $fullPath = Storage::disk('local')->path($decodedPath);
        $mimeType = Storage::disk('local')->mimeType($decodedPath) ?: 'application/pdf';
        $safeFilename = str_replace(['"', "\r", "\n"], '', basename($decodedPath));

        return response()->file($fullPath, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . $safeFilename . '"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * Dekode path dengan aman, cegah path traversal, dan verifikasi hak akses pemilik/terkait.
     */
    private function resolveAuthorizedPath(?User $user, string $encodedPath): string
    {
        if (! $user) {
            abort(403, 'Akses dokumen memerlukan autentikasi.');
        }

        $decodedPath = base64_decode($encodedPath, true);

        if (
            $decodedPath === false
            || $decodedPath === ''
            || str_contains($decodedPath, "\0")
            || str_contains($decodedPath, '\\')
            || str_starts_with($decodedPath, '/')
        ) {
            abort(403, 'Path dokumen tidak valid.');
        }

        $segments = explode('/', $decodedPath);
        foreach ($segments as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..') {
                abort(403, 'Path dokumen tidak valid.');
            }
        }

        if (! preg_match('#^(skripsi|seminar|sidang|surat_internal|praktik_lapang)/#', $decodedPath)) {
            abort(403, 'Akses direktori dokumen ditolak.');
        }

        $context = $this->findOwningContext($decodedPath);
        if (! $context) {
            abort(403, 'Dokumen tidak terdaftar atau akses ditolak.');
        }

        if (! $this->userCanAccessContext($user, $context)) {
            abort(403, 'Anda tidak memiliki izin untuk mengakses dokumen ini.');
        }

        if (! Storage::disk('local')->exists($decodedPath)) {
            abort(404, 'File dokumen tidak ditemukan.');
        }

        return $decodedPath;
    }

    /**
     * @return array{skripsi: PengajuanSkripsi, seminar: ?SeminarSkripsi, sidang: ?SidangSkripsi}|null
     */
    private function findOwningContext(string $path): ?array
    {
        $skripsi = PengajuanSkripsi::query()
            ->where('file_proposal', $path)
            ->orWhere('file_transkrip', $path)
            ->orWhere('file_bukti_bayar', $path)
            ->orWhere('file_sk_bimbingan', $path)
            ->first();

        if ($skripsi) {
            $skripsi->loadMissing(['seminar', 'sidang']);

            return [
                'skripsi' => $skripsi,
                'seminar' => $skripsi->seminar,
                'sidang' => $skripsi->sidang,
            ];
        }

        $seminar = SeminarSkripsi::query()
            ->with(['pengajuanSkripsi.sidang'])
            ->where('file_naskah_seminar', $path)
            ->orWhere('file_acc_pembimbing', $path)
            ->orWhere('file_bukti_bayar_seminar', $path)
            ->orWhere('file_toefl', $path)
            ->orWhere('file_undangan_seminar', $path)
            ->orWhere('file_sk_seminar', $path)
            ->first();

        if ($seminar && $seminar->pengajuanSkripsi) {
            return [
                'skripsi' => $seminar->pengajuanSkripsi,
                'seminar' => $seminar,
                'sidang' => $seminar->pengajuanSkripsi->sidang,
            ];
        }

        $sidang = SidangSkripsi::query()
            ->with(['pengajuanSkripsi.seminar'])
            ->where('file_naskah_sidang', $path)
            ->orWhere('file_acc_sidang', $path)
            ->orWhere('file_bebas_revisi_seminar', $path)
            ->orWhere('file_bukti_bayar_sidang', $path)
            ->orWhere('file_undangan_sidang', $path)
            ->orWhere('file_sk_sidang', $path)
            ->first();

        if ($sidang && $sidang->pengajuanSkripsi) {
            return [
                'skripsi' => $sidang->pengajuanSkripsi,
                'seminar' => $sidang->pengajuanSkripsi->seminar,
                'sidang' => $sidang,
            ];
        }

        $surat = Surat::query()
            ->with(['pengajuanSkripsi.seminar', 'pengajuanSkripsi.sidang', 'pengajuanSurat', 'praktikLapang', 'seminar', 'sidang'])
            ->where('file_surat', $path)
            ->first();

        if ($surat && $surat->pengajuanSkripsi) {
            return [
                'skripsi' => $surat->pengajuanSkripsi,
                'seminar' => $surat->seminar ?? $surat->pengajuanSkripsi->seminar,
                'sidang' => $surat->sidang ?? $surat->pengajuanSkripsi->sidang,
                'pengajuan_surat' => null,
                'praktik_lapang' => null,
            ];
        }

        if ($surat && $surat->pengajuanSurat) {
            return [
                'skripsi' => null,
                'seminar' => null,
                'sidang' => null,
                'pengajuan_surat' => $surat->pengajuanSurat,
                'praktik_lapang' => null,
            ];
        }

        if ($surat && $surat->praktikLapang) {
            return [
                'skripsi' => null,
                'seminar' => null,
                'sidang' => null,
                'pengajuan_surat' => null,
                'praktik_lapang' => $surat->praktikLapang,
            ];
        }

        $praktikLapang = PraktikLapang::query()
            ->where('file_transkrip_sks', $path)
            ->orWhere('file_surat_balasan', $path)
            ->orWhere('file_sk_dpl', $path)
            ->orWhere('file_laporan_akhir', $path)
            ->orWhere('file_sertifikat', $path)
            ->orWhereHas('logbooks', fn ($q) => $q->where('file_dokumentasi', $path))
            ->first();

        if ($praktikLapang) {
            return [
                'skripsi' => null,
                'seminar' => null,
                'sidang' => null,
                'pengajuan_surat' => null,
                'praktik_lapang' => $praktikLapang,
            ];
        }

        $pengajuanSurat = PengajuanSurat::query()
            ->where('file_pendukung', $path)
            ->orWhere('file_surat_terbit', $path)
            ->first();

        if ($pengajuanSurat) {
            return [
                'skripsi' => null,
                'seminar' => null,
                'sidang' => null,
                'pengajuan_surat' => $pengajuanSurat,
            ];
        }

        return null;
    }

    /**
     * @param array{skripsi: ?PengajuanSkripsi, seminar: ?SeminarSkripsi, sidang: ?SidangSkripsi, pengajuan_surat?: ?PengajuanSurat, praktik_lapang?: ?PraktikLapang} $context
     */
    private function userCanAccessContext(User $user, array $context): bool
    {
        if (! empty($context['praktik_lapang'])) {
            /** @var PraktikLapang $pl */
            $pl = $context['praktik_lapang'];

            if ($user->hasRole(UserRole::AdminUtama, UserRole::Tendik, UserRole::PimpinanFakultas)) {
                return true;
            }

            if ($user->hasRole(UserRole::AdminProdi, UserRole::Kaprodi)) {
                return $user->program_studi_id !== null
                    && (int) $pl->program_studi_id === (int) $user->program_studi_id;
            }

            if ($user->isMahasiswa() && (int) $pl->mahasiswa_id === (int) $user->id) {
                return true;
            }

            if ($user->isDosen()) {
                if ($pl->dpl_id && (int) $pl->dpl_id === (int) $user->id) {
                    return true;
                }

                return PenugasanDosen::query()
                    ->where('dosen_id', $user->id)
                    ->whereIn('status', [StatusPenugasanDosen::Menunggu, StatusPenugasanDosen::Disetujui])
                    ->where('assignable_type', PraktikLapang::class)
                    ->where('assignable_id', $pl->id)
                    ->exists();
            }

            return false;
        }

        if (! empty($context['pengajuan_surat'])) {
            /** @var PengajuanSurat $pengajuanSurat */
            $pengajuanSurat = $context['pengajuan_surat'];

            if ($user->hasRole(UserRole::AdminUtama, UserRole::Tendik, UserRole::PimpinanFakultas)) {
                return true;
            }

            if ($user->hasRole(UserRole::AdminProdi, UserRole::Kaprodi)) {
                return $user->program_studi_id !== null
                    && (int) $pengajuanSurat->program_studi_id === (int) $user->program_studi_id;
            }

            if ($user->isMahasiswa()) {
                return (int) $pengajuanSurat->mahasiswa_id === (int) $user->id;
            }

            return false;
        }

        $skripsi = $context['skripsi'];
        $seminar = $context['seminar'];
        $sidang = $context['sidang'];

        if (! $skripsi) {
            return false;
        }
        if ($user->isAdminUtama()) {
            return true;
        }

        if ($user->isAdminProdi() || $user->isKaprodi()) {
            return $user->program_studi_id !== null
                && (int) $skripsi->program_studi_id === (int) $user->program_studi_id;
        }

        if ($user->isMahasiswa()) {
            return (int) $skripsi->mahasiswa_id === (int) $user->id;
        }

        if ($user->isDosen()) {
            $involvedDirectly = in_array((int) $user->id, array_filter([
                $skripsi->pembimbing_1_id ? (int) $skripsi->pembimbing_1_id : null,
                $skripsi->pembimbing_2_id ? (int) $skripsi->pembimbing_2_id : null,
                $seminar?->penguji_seminar_id ? (int) $seminar->penguji_seminar_id : null,
                $sidang?->penguji_1_id ? (int) $sidang->penguji_1_id : null,
                $sidang?->penguji_2_id ? (int) $sidang->penguji_2_id : null,
            ]), true);

            if ($involvedDirectly) {
                return true;
            }

            return PenugasanDosen::query()
                ->where('dosen_id', $user->id)
                ->whereIn('status', [StatusPenugasanDosen::Menunggu, StatusPenugasanDosen::Disetujui])
                ->where(function ($q) use ($skripsi, $seminar, $sidang) {
                    $q->where(fn ($sub) => $sub->where('assignable_type', PengajuanSkripsi::class)->where('assignable_id', $skripsi->id));
                    if ($seminar) {
                        $q->orWhere(fn ($sub) => $sub->where('assignable_type', SeminarSkripsi::class)->where('assignable_id', $seminar->id));
                    }
                    if ($sidang) {
                        $q->orWhere(fn ($sub) => $sub->where('assignable_type', SidangSkripsi::class)->where('assignable_id', $sidang->id));
                    }
                })
                ->exists();
        }

        return false;
    }
}
