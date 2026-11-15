<?php

namespace App\Http\Controllers\Kelola;

use App\Enums\StatusVerifikasiLokasiPl;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\LogbookPl;
use App\Models\LokasiPl;
use App\Models\PraktikLapang;
use App\Models\ProgramStudi;
use App\Models\User;
use App\Services\PraktikLapangService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Enum;
use Illuminate\View\View;

class PraktikLapangController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $isPengelolaProdi = $user->hasRole(UserRole::AdminUtama, UserRole::AdminProdi, UserRole::Kaprodi, UserRole::Tendik);

        $queryPl = PraktikLapang::query()
            ->with([
                'mahasiswa:id,name,nomor_induk,program_studi_id',
                'programStudi:id,nama,kode',
                'lokasiPl',
                'dpl:id,name',
                'pengajuanSurat:id,status,nomor_surat_terbit,file_surat_terbit',
                'logbooks',
            ]);

        if ($user->hasRole(UserRole::AdminProdi, UserRole::Kaprodi) && ! $user->isAdminUtama()) {
            $queryPl->where('program_studi_id', $user->program_studi_id);
        } elseif (! $isPengelolaProdi && $user->isDosen()) {
            $queryPl->where('dpl_id', $user->id);
        }

        if ($prodiFilter = $request->query('prodi_id')) {
            if ($user->hasRole(UserRole::AdminUtama, UserRole::Tendik)) {
                $queryPl->where('program_studi_id', (int) $prodiFilter);
            }
        }

        $daftarPl = $queryPl->latest('id')->paginate(12)->withQueryString();

        $daftarLokasi = LokasiPl::query()
            ->with('pengusul:id,name')
            ->latest('id')
            ->get();

        $daftarDosen = User::query()
            ->whereHasRoles([UserRole::Dosen->value, UserRole::Kaprodi->value])
            ->when(
                $user->hasRole(UserRole::Kaprodi, UserRole::AdminProdi) && ! $user->isAdminUtama(),
                fn ($q) => $q->where('program_studi_id', $user->program_studi_id)
            )
            ->orderBy('name')
            ->get(['id', 'name', 'program_studi_id']);

        $daftarProdi = ProgramStudi::orderBy('nama')->get(['id', 'nama', 'kode']);

        return view('kelola.pl.index', compact(
            'user',
            'daftarPl',
            'daftarLokasi',
            'daftarDosen',
            'daftarProdi',
            'isPengelolaProdi'
        ));
    }

    public function storeLokasi(Request $request): RedirectResponse
    {
        $user = $request->user();
        if (! $user->hasRole(UserRole::AdminUtama, UserRole::AdminProdi, UserRole::Kaprodi, UserRole::Tendik)) {
            abort(403, 'Hanya pengelola fakultas atau prodi yang dapat menambah mitra katalog PL.');
        }

        $validated = $request->validate([
            'nama_instansi' => ['required', 'string', 'max:180'],
            'sektor' => ['required', 'in:AGT,AGB,AGT/AGB'],
            'alamat' => ['required', 'string', 'max:255'],
            'kabupaten_kota' => ['nullable', 'string', 'max:120'],
            'kontak_pic' => ['nullable', 'string', 'max:120'],
            'telepon_pic' => ['nullable', 'string', 'max:40'],
            'kuota_periode' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        LokasiPl::create([
            ...$validated,
            'status_verifikasi' => StatusVerifikasiLokasiPl::Terverifikasi,
            'diverifikasi_oleh' => $user->id,
        ]);

        return back()->with('success', 'Instansi mitra Praktik Lapang berhasil ditambahkan ke katalog.');
    }

    public function verifikasiLokasi(Request $request, LokasiPl $lokasi, PraktikLapangService $plService): RedirectResponse
    {
        $user = $request->user();
        if (! $user->hasRole(UserRole::AdminUtama, UserRole::AdminProdi, UserRole::Kaprodi, UserRole::Tendik)) {
            abort(403, 'Anda tidak berwenang memverifikasi tempat Praktik Lapang.');
        }

        $validated = $request->validate([
            'status_verifikasi' => ['required', new Enum(StatusVerifikasiLokasiPl::class)],
            'kuota_periode' => ['nullable', 'integer', 'min:1', 'max:100'],
            'catatan_verifikasi' => ['nullable', 'string', 'max:500'],
        ]);

        $plService->verifikasiLokasi(
            $lokasi,
            $user,
            StatusVerifikasiLokasiPl::from($validated['status_verifikasi']),
            $validated['kuota_periode'] ?? null,
            $validated['catatan_verifikasi'] ?? null
        );

        return back()->with('success', 'Status verifikasi lokasi PL berhasil diperbarui.');
    }

    public function tetapkanDpl(Request $request, PraktikLapang $pl, PraktikLapangService $plService): RedirectResponse
    {
        $user = $request->user();
        if (! $user->hasRole(UserRole::Kaprodi, UserRole::AdminUtama)) {
            abort(403, 'Penetapan DPL dilakukan oleh Kaprodi atau Admin Utama.');
        }

        $this->authorizeProdiScope($user, (int) $pl->program_studi_id);

        $validated = $request->validate([
            'dpl_id' => ['required', 'exists:users,id'],
            'is_mandat' => ['nullable', 'boolean'],
        ]);

        $dosen = User::findOrFail((int) $validated['dpl_id']);
        $isMandat = $user->isAdminUtama() && (bool) ($validated['is_mandat'] ?? false);

        try {
            $plService->tetapkanDpl($pl, $dosen, $user, $isMandat);

            return back()->with('success', 'Dosen Pembimbing Lapang (DPL) berhasil ditetapkan.');
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function terbitkanSkDpl(Request $request, PraktikLapang $pl, PraktikLapangService $plService): RedirectResponse
    {
        $user = $request->user();
        if (! $user->hasRole(UserRole::AdminUtama, UserRole::AdminProdi)) {
            abort(403, 'Penerbitan SK DPL dilakukan oleh Admin Prodi atau Admin Utama.');
        }

        $this->authorizeProdiScope($user, (int) $pl->program_studi_id);

        $validated = $request->validate([
            'nomor_sk_dpl' => ['required', 'string', 'max:100'],
            'file_sk_dpl' => ['nullable', 'file', 'mimes:pdf', 'max:5120'],
            'tgl_mulai' => ['nullable', 'date'],
            'tgl_selesai' => ['nullable', 'date', 'after_or_equal:tgl_mulai'],
        ]);

        $path = $request->hasFile('file_sk_dpl')
            ? $request->file('file_sk_dpl')->store('praktik_lapang/sk_dpl', 'local')
            : null;

        try {
            $plService->terbitkanSkDpl(
                $pl,
                $user,
                $validated['nomor_sk_dpl'],
                $path,
                $validated['tgl_mulai'] ?? null,
                $validated['tgl_selesai'] ?? null
            );

            return back()->with('success', 'SK DPL Praktik Lapang berhasil diterbitkan & tercatat pada arsip surat.');
        } catch (DomainException $e) {
            if ($path) {
                Storage::disk('local')->delete($path);
            }

            return back()->with('error', $e->getMessage());
        }
    }

    public function verifikasiLogbook(Request $request, LogbookPl $logbook, PraktikLapangService $plService): RedirectResponse
    {
        $logbook->loadMissing('praktikLapang');
        $pl = $logbook->praktikLapang;
        $user = $request->user();

        if (! $user->isAdminUtama() && (int) $pl->dpl_id !== (int) $user->id) {
            abort(403, 'Hanya DPL terkait atau Admin Utama yang dapat memverifikasi logbook PL.');
        }

        $validated = $request->validate([
            'catatan_dpl' => ['nullable', 'string', 'max:255'],
        ]);

        $plService->verifikasiLogbook($logbook, $user, $validated['catatan_dpl'] ?? null);

        return back()->with('success', 'Entri logbook Praktik Lapang telah diverifikasi.');
    }

    public function jadwalkanSeminar(Request $request, PraktikLapang $pl, PraktikLapangService $plService): RedirectResponse
    {
        $user = $request->user();
        if (! $user->hasRole(UserRole::AdminUtama, UserRole::AdminProdi, UserRole::Kaprodi)) {
            abort(403, 'Penjadwalan Seminar PL hanya dapat dilakukan oleh Admin Prodi, Kaprodi, atau Admin Utama.');
        }

        $this->authorizeProdiScope($user, (int) $pl->program_studi_id);

        $validated = $request->validate([
            'tgl_seminar_pl' => ['required', 'date'],
            'jam_seminar_pl' => ['required', 'string', 'max:50'],
            'ruangan_seminar_pl' => ['required', 'string', 'max:100'],
        ]);

        try {
            $plService->jadwalkanSeminarPl(
                $pl,
                $user,
                $validated['tgl_seminar_pl'],
                $validated['jam_seminar_pl'],
                $validated['ruangan_seminar_pl']
            );

            return back()->with('success', 'Jadwal Seminar Praktik Lapang berhasil disimpan & dipublikasikan ke portal jadwal.');
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function simpanPenilaian(Request $request, PraktikLapang $pl, PraktikLapangService $plService): RedirectResponse
    {
        $user = $request->user();
        $isProdiAdmin = $user->hasRole(UserRole::AdminUtama, UserRole::AdminProdi, UserRole::Kaprodi);
        $isDpl = (int) $pl->dpl_id === (int) $user->id;

        if (! $isProdiAdmin && ! $isDpl) {
            abort(403, 'Anda tidak berwenang menginput nilai Praktik Lapang ini.');
        }

        if ($isProdiAdmin) {
            $this->authorizeProdiScope($user, (int) $pl->program_studi_id);
        }

        $validated = $request->validate([
            'nilai_pembimbing_lapang' => ['required', 'numeric', 'min:0', 'max:100'],
            'nilai_dpl' => ['required', 'numeric', 'min:0', 'max:100'],
            'file_sertifikat' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'catatan' => ['nullable', 'string', 'max:500'],
        ]);

        $path = $request->hasFile('file_sertifikat')
            ? $request->file('file_sertifikat')->store('praktik_lapang/sertifikat', 'local')
            : null;

        $plService->simpanPenilaianPl(
            $pl,
            $user,
            (float) $validated['nilai_pembimbing_lapang'],
            (float) $validated['nilai_dpl'],
            $path,
            $validated['catatan'] ?? null
        );

        return back()->with('success', 'Penilaian & arsip Praktik Lapang berhasil diselesaikan.');
    }

    private function authorizeProdiScope(User $user, int $prodiId): void
    {
        if ($user->isAdminUtama()) {
            return;
        }

        if ((int) $user->program_studi_id !== $prodiId) {
            abort(403, 'Anda hanya berwenang mengelola data Praktik Lapang program studi Anda.');
        }
    }
}
