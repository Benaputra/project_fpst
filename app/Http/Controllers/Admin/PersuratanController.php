<?php

namespace App\Http\Controllers\Admin;

use App\Enums\JenisSuratInternal;
use App\Enums\StatusPengajuanSurat;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\AktivitasLog;
use App\Models\Notifikasi;
use App\Models\PengajuanSurat;
use App\Models\ProgramStudi;
use App\Models\User;
use App\Services\SuratInternalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class PersuratanController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $this->authorizePanelAccess($user);

        $statusFilter = $request->query('status');
        $jenisFilter = $request->query('jenis');
        $prodiFilter = $this->isProdiScopedOnly($user)
            ? $user->program_studi_id
            : ($request->filled('prodi_id') ? (int) $request->query('prodi_id') : null);
        $search = $request->query('search');

        $baseQuery = PengajuanSurat::query()
            ->with(['mahasiswa', 'programStudi', 'verifikator', 'penyetuju', 'penerbit', 'suratAktif']);

        if ($prodiFilter) {
            $baseQuery->where('program_studi_id', $prodiFilter);
        }

        $stats = [
            'diajukan' => (clone $baseQuery)->where('status', StatusPengajuanSurat::Diajukan)->count(),
            'diverifikasi' => (clone $baseQuery)->where('status', StatusPengajuanSurat::Diverifikasi)->count(),
            'disetujui' => (clone $baseQuery)->where('status', StatusPengajuanSurat::Disetujui)->count(),
            'diterbitkan' => (clone $baseQuery)->where('status', StatusPengajuanSurat::Diterbitkan)->count(),
        ];

        $listQuery = clone $baseQuery;

        if ($statusFilter && StatusPengajuanSurat::tryFrom($statusFilter)) {
            $listQuery->where('status', $statusFilter);
        }

        if ($jenisFilter && JenisSuratInternal::tryFrom($jenisFilter)) {
            $listQuery->where('jenis_surat', $jenisFilter);
        }

        if ($search) {
            $listQuery->where(function ($q) use ($search) {
                $q->where('perihal', 'like', "%{$search}%")
                    ->orWhere('tujuan_instansi', 'like', "%{$search}%")
                    ->orWhere('nomor_surat_terbit', 'like', "%{$search}%")
                    ->orWhereHas('mahasiswa', function ($m) use ($search) {
                        $m->where('name', 'like', "%{$search}%")
                            ->orWhere('nomor_induk', 'like', "%{$search}%");
                    });
            });
        }

        $daftarPengajuan = $listQuery->latest()->paginate(15)->withQueryString();
        $daftarProdi = ProgramStudi::orderBy('nama')->get();
        $daftarPejabat = User::query()
            ->whereHasRoles([UserRole::Kaprodi, UserRole::PimpinanFakultas])
            ->with('programStudi')
            ->orderBy('name')
            ->get();

        return view('admin.persuratan.index', compact(
            'user',
            'stats',
            'daftarPengajuan',
            'daftarProdi',
            'daftarPejabat',
            'statusFilter',
            'jenisFilter',
            'prodiFilter',
            'search'
        ));
    }

    public function verifikasi(Request $request, PengajuanSurat $pengajuanSurat): RedirectResponse
    {
        $user = $request->user();
        $this->authorizeStaffAction($user, (int) $pengajuanSurat->program_studi_id);

        $result = DB::transaction(function () use ($user, $pengajuanSurat): array {
            $locked = PengajuanSurat::with(['mahasiswa', 'programStudi'])
                ->whereKey($pengajuanSurat->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->status !== StatusPengajuanSurat::Diajukan) {
                return ['error' => 'Hanya pengajuan berstatus Menunggu Verifikasi yang dapat diverifikasi.'];
            }

            $locked->update([
                'status' => StatusPengajuanSurat::Diverifikasi,
                'diverifikasi_oleh' => $user->id,
                'diverifikasi_pada' => now(),
            ]);

            AktivitasLog::catat(
                $user,
                'Verifikasi Surat Internal',
                "{$user->name} memverifikasi permohonan {$locked->jenis_surat->label()} milik {$locked->mahasiswa->name}"
            );

            Notifikasi::kirim(
                $locked->mahasiswa_id,
                'Berkas Pengajuan Surat Terverifikasi',
                "Pengajuan surat \"{$locked->perihal}\" telah diverifikasi staf/Tendik dan diteruskan untuk persetujuan pejabat berwenang.",
                route('mahasiswa.surat-internal.index')
            );

            $approverRoles = $locked->jenis_surat === JenisSuratInternal::IzinPenelitian
                ? [UserRole::Kaprodi, UserRole::PimpinanFakultas, UserRole::AdminUtama]
                : [UserRole::PimpinanFakultas, UserRole::AdminUtama];

            Notifikasi::kirimKePengelola(
                $locked->program_studi_id,
                'Pengajuan Surat Menunggu Persetujuan',
                "Permohonan {$locked->jenis_surat->label()} milik {$locked->mahasiswa->name} ({$locked->perihal}) telah diverifikasi dan siap disetujui.",
                route('admin.persuratan.index'),
                $user->id,
                $approverRoles
            );

            return ['success' => 'Permohonan surat berhasil diverifikasi dan diteruskan ke Pimpinan/Kaprodi untuk persetujuan.'];
        });

        return isset($result['error'])
            ? back()->with('error', $result['error'])
            : back()->with('success', $result['success']);
    }

    public function setujui(Request $request, PengajuanSurat $pengajuanSurat): RedirectResponse
    {
        $user = $request->user();
        $this->authorizeApproverAction($user, (int) $pengajuanSurat->program_studi_id);

        $result = DB::transaction(function () use ($user, $pengajuanSurat): array {
            $locked = PengajuanSurat::with(['mahasiswa'])
                ->whereKey($pengajuanSurat->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! in_array($locked->status, [StatusPengajuanSurat::Diajukan, StatusPengajuanSurat::Diverifikasi], true)) {
                return ['error' => 'Status pengajuan ini sudah final atau sudah disetujui sebelumnya.'];
            }

            $locked->update([
                'status' => StatusPengajuanSurat::Disetujui,
                'disetujui_oleh' => $user->id,
                'disetujui_pada' => now(),
                'diverifikasi_oleh' => $locked->diverifikasi_oleh ?? $user->id,
                'diverifikasi_pada' => $locked->diverifikasi_pada ?? now(),
            ]);

            AktivitasLog::catat(
                $user,
                'Persetujuan Surat Internal',
                "{$user->name} menyetujui penerbitan {$locked->jenis_surat->label()} milik {$locked->mahasiswa->name}"
            );

            Notifikasi::kirim(
                $locked->mahasiswa_id,
                'Pengajuan Surat Disetujui Pejabat',
                "Pengajuan \"{$locked->perihal}\" telah disetujui dan sedang diproses penomoran surat resmi.",
                route('mahasiswa.surat-internal.index')
            );

            Notifikasi::kirimKePengelola(
                $locked->program_studi_id,
                'Surat Disetujui • Siap Penomoran & Terbit',
                "Pengajuan {$locked->jenis_surat->label()} milik {$locked->mahasiswa->name} telah disetujui dan siap diterbitkan PDF ber-QR.",
                route('admin.persuratan.index'),
                $user->id,
                [UserRole::Tendik, UserRole::AdminProdi, UserRole::AdminUtama]
            );

            return ['success' => 'Permohonan surat disetujui. Staf/Tendik atau Admin kini dapat menerbitkan nomor & PDF resmi.'];
        });

        return isset($result['error'])
            ? back()->with('error', $result['error'])
            : back()->with('success', $result['success']);
    }

    public function terbitkan(Request $request, PengajuanSurat $pengajuanSurat, SuratInternalService $suratService): RedirectResponse
    {
        $user = $request->user();
        $this->authorizeStaffAction($user, (int) $pengajuanSurat->program_studi_id);

        $validated = $request->validate([
            'nomor_surat_manual' => ['nullable', 'string', 'max:120'],
            'penanda_tangan_id' => ['nullable', 'exists:users,id'],
        ]);

        $createdPath = null;

        try {
            $result = DB::transaction(function () use ($user, $pengajuanSurat, $validated, $suratService, &$createdPath): array {
                $locked = PengajuanSurat::with(['mahasiswa', 'programStudi', 'penyetuju'])
                    ->whereKey($pengajuanSurat->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($locked->status !== StatusPengajuanSurat::Disetujui) {
                    return ['error' => 'Surat hanya dapat diterbitkan setelah berstatus Disetujui oleh Kaprodi/Pimpinan Fakultas.'];
                }

                $signer = ! empty($validated['penanda_tangan_id'])
                    ? User::find($validated['penanda_tangan_id'])
                    : null;

                $terbit = $suratService->terbitkanSurat(
                    $locked,
                    $user,
                    $signer,
                    $validated['nomor_surat_manual'] ?? null
                );

                $createdPath = $terbit['path'];

                AktivitasLog::catat(
                    $user,
                    'Penerbitan Surat Internal',
                    "{$user->name} menerbitkan {$locked->jenis_surat->label()} nomor {$terbit['surat']->nomor_surat} untuk {$locked->mahasiswa->name}"
                );

                Notifikasi::kirim(
                    $locked->mahasiswa_id,
                    'Surat Resmi Anda Telah Terbit',
                    "Surat {$locked->jenis_surat->label()} dengan nomor {$terbit['surat']->nomor_surat} telah terbit dan siap diunduh.",
                    route('mahasiswa.surat-internal.index')
                );

                $qrSuffix = config('fpst.surat_internal.qr_verifikasi_enabled') ? ' dan QR Code verifikasi.' : '.';

                return ['success' => "Surat resmi berhasil diterbitkan dengan nomor {$terbit['surat']->nomor_surat}{$qrSuffix}"];
            });
        } catch (\Throwable $e) {
            if ($createdPath) {
                Storage::disk('local')->delete($createdPath);
            }
            throw $e;
        }

        if (isset($result['error'])) {
            if ($createdPath) {
                Storage::disk('local')->delete($createdPath);
            }

            return back()->with('error', $result['error']);
        }

        return back()->with('success', $result['success']);
    }

    public function tolak(Request $request, PengajuanSurat $pengajuanSurat): RedirectResponse
    {
        $user = $request->user();
        $this->authorizePanelAccess($user, (int) $pengajuanSurat->program_studi_id);

        $validated = $request->validate([
            'catatan_penolakan' => ['required', 'string', 'max:1000'],
        ]);

        $result = DB::transaction(function () use ($user, $pengajuanSurat, $validated): array {
            $locked = PengajuanSurat::with('mahasiswa')
                ->whereKey($pengajuanSurat->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (in_array($locked->status, [StatusPengajuanSurat::Diterbitkan, StatusPengajuanSurat::Ditolak], true)) {
                return ['error' => 'Pengajuan yang sudah diterbitkan atau sudah ditolak tidak dapat ditolak kembali.'];
            }

            $locked->update([
                'status' => StatusPengajuanSurat::Ditolak,
                'catatan_penolakan' => trim($validated['catatan_penolakan']),
            ]);

            AktivitasLog::catat(
                $user,
                'Penolakan Surat Internal',
                "{$user->name} menolak permohonan {$locked->jenis_surat->label()} milik {$locked->mahasiswa->name}: {$locked->catatan_penolakan}"
            );

            Notifikasi::kirim(
                $locked->mahasiswa_id,
                'Pengajuan Surat Ditolak / Perlu Perbaikan',
                "Pengajuan \"{$locked->perihal}\" ditolak dengan catatan: {$locked->catatan_penolakan}",
                route('mahasiswa.surat-internal.index')
            );

            return ['success' => 'Pengajuan surat ditolak dan mahasiswa telah menerima catatan penolakan.'];
        });

        return isset($result['error'])
            ? back()->with('error', $result['error'])
            : back()->with('success', $result['success']);
    }

    private function authorizePanelAccess(User $user, ?int $prodiId = null): void
    {
        if (! $user->hasRole(
            UserRole::AdminUtama,
            UserRole::AdminProdi,
            UserRole::Tendik,
            UserRole::Kaprodi,
            UserRole::PimpinanFakultas
        )) {
            abort(403, 'AKSES DIBATASI');
        }

        if ($prodiId !== null && $this->isProdiScopedOnly($user) && (int) $user->program_studi_id !== $prodiId) {
            abort(403, 'Anda hanya berwenang memproses persuratan Program Studi Anda.');
        }
    }

    private function authorizeStaffAction(User $user, int $prodiId): void
    {
        if (! $user->isStaffPersuratan()) {
            abort(403, 'Hanya Staf Tendik, Admin Prodi, atau Admin Utama yang berwenang memverifikasi dan menerbitkan surat.');
        }

        if ($this->isProdiScopedOnly($user) && (int) $user->program_studi_id !== $prodiId) {
            abort(403, 'Anda tidak berwenang memproses pengajuan Program Studi lain.');
        }
    }

    private function authorizeApproverAction(User $user, int $prodiId): void
    {
        if (! $user->canApproveSurat()) {
            abort(403, 'Hanya Kaprodi, Pimpinan Fakultas, atau Admin Utama yang berwenang menyetujui penerbitan surat.');
        }

        if ($this->isProdiScopedOnly($user) && (int) $user->program_studi_id !== $prodiId) {
            abort(403, 'Anda hanya berwenang menyetujui permohonan mahasiswa Program Studi Anda.');
        }
    }

    private function isProdiScopedOnly(User $user): bool
    {
        if ($user->hasRole(UserRole::AdminUtama, UserRole::PimpinanFakultas, UserRole::Tendik)) {
            return false;
        }

        return $user->hasRole(UserRole::AdminProdi, UserRole::Kaprodi);
    }
}
