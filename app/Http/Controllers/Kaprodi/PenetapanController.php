<?php

namespace App\Http\Controllers\Kaprodi;

use App\Enums\StatusPengajuan;
use App\Enums\StatusPenugasanDosen;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\AktivitasLog;
use App\Models\Notifikasi;
use App\Models\PengajuanSkripsi;
use App\Models\PenugasanDosen;
use App\Models\ProgramStudi;
use App\Models\SeminarSkripsi;
use App\Models\SidangSkripsi;
use App\Models\User;
use App\Services\JudulSimilarityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PenetapanController extends Controller
{
    public function index(Request $request, JudulSimilarityService $similarityService): View
    {
        $user = $request->user();
        if (!$user->isKaprodi() && !$user->isAdminUtama()) {
            abort(403, 'Akses khusus Kaprodi dan Admin Utama.');
        }

        $daftarProdi = ProgramStudi::all();
        $prodiFilter = $user->isAdminUtama()
            ? $request->input('prodi_id', $daftarProdi->first()?->id)
            : $user->program_studi_id;
        $prodiId = $prodiFilter;

        // Filter & Search untuk Tab 1: Review Judul & Pembimbing
        $qJudul = PengajuanSkripsi::where('program_studi_id', $prodiId)
            ->with(['mahasiswa', 'pembimbing1', 'pembimbing2', 'penugasanDosen.dosen', 'penugasanDosen.rekomendasiDosen']);
        if ($searchJudul = $request->input('search_judul')) {
            $qJudul->where(function ($q) use ($searchJudul) {
                $q->where('judul', 'like', "%{$searchJudul}%")
                    ->orWhereHas('mahasiswa', fn($m) => $m->where('name', 'like', "%{$searchJudul}%")->orWhere('nomor_induk', 'like', "%{$searchJudul}%"));
            });
        }
        if ($statusJudul = $request->input('status_judul')) {
            if ($statusJudul === 'menunggu') {
                $qJudul->where('status', StatusPengajuan::Diajukan);
            } elseif ($statusJudul === 'selesai') {
                $qJudul->where('status', '!=', StatusPengajuan::Diajukan);
            }
        }
        if ($request->input('sort_judul', 'fifo') === 'fifo') {
            $qJudul->oldest();
        } else {
            $qJudul->latest();
        }
        $daftarJudul = $qJudul->paginate(10, ['*'], 'page_judul')->withQueryString();

        // Filter & Search untuk Tab 2: Penguji Seminar
        $qSeminar = SeminarSkripsi::whereHas('pengajuanSkripsi', fn($q) => $q->where('program_studi_id', $prodiId))
            ->with(['pengajuanSkripsi.mahasiswa', 'penguji', 'penugasanDosen.dosen', 'penugasanDosen.rekomendasiDosen']);
        if ($searchSeminar = $request->input('search_seminar')) {
            $qSeminar->where(function ($q) use ($searchSeminar) {
                $q->whereHas('pengajuanSkripsi', fn($ps) => 
                    $ps->where('judul', 'like', "%{$searchSeminar}%")
                        ->orWhereHas('mahasiswa', fn($m) => $m->where('name', 'like', "%{$searchSeminar}%")->orWhere('nomor_induk', 'like', "%{$searchSeminar}%"))
                );
            });
        }
        if ($statusSeminar = $request->input('status_seminar')) {
            if ($statusSeminar === 'menunggu') {
                $qSeminar->whereNull('penguji_seminar_id');
            } elseif ($statusSeminar === 'selesai') {
                $qSeminar->whereNotNull('penguji_seminar_id');
            }
        }
        if ($request->input('sort_seminar', 'fifo') === 'fifo') {
            $qSeminar->oldest();
        } else {
            $qSeminar->latest();
        }
        $daftarSeminar = $qSeminar->paginate(10, ['*'], 'page_seminar')->withQueryString();

        // Filter & Search untuk Tab 3: Penguji Sidang
        $qSidang = SidangSkripsi::whereHas('pengajuanSkripsi', fn($q) => $q->where('program_studi_id', $prodiId))
            ->with(['pengajuanSkripsi.mahasiswa', 'penguji1', 'penguji2', 'penugasanDosen.dosen', 'penugasanDosen.rekomendasiDosen']);
        if ($searchSidang = $request->input('search_sidang')) {
            $qSidang->where(function ($q) use ($searchSidang) {
                $q->whereHas('pengajuanSkripsi', fn($ps) => 
                    $ps->where('judul', 'like', "%{$searchSidang}%")
                        ->orWhereHas('mahasiswa', fn($m) => $m->where('name', 'like', "%{$searchSidang}%")->orWhere('nomor_induk', 'like', "%{$searchSidang}%"))
                );
            });
        }
        if ($statusSidang = $request->input('status_sidang')) {
            if ($statusSidang === 'menunggu') {
                $qSidang->where(fn($q) => $q->whereNull('penguji_1_id')->orWhereNull('penguji_2_id'));
            } elseif ($statusSidang === 'selesai') {
                $qSidang->whereNotNull('penguji_1_id')->whereNotNull('penguji_2_id');
            }
        }
        if ($request->input('sort_sidang', 'fifo') === 'fifo') {
            $qSidang->oldest();
        } else {
            $qSidang->latest();
        }
        $daftarSidang = $qSidang->paginate(10, ['*'], 'page_sidang')->withQueryString();

        $daftarDosen = User::whereIn('role', [UserRole::Dosen, UserRole::Kaprodi])
            ->where('program_studi_id', $prodiId)
            ->orderBy('name')
            ->get();

        $pendingJudulCount = PengajuanSkripsi::where('program_studi_id', $prodiId)->where('status', StatusPengajuan::Diajukan)->count();
        $pendingSeminarCount = SeminarSkripsi::whereHas('pengajuanSkripsi', fn($q) => $q->where('program_studi_id', $prodiId))->whereNull('penguji_seminar_id')->count();
        $pendingSidangCount = SidangSkripsi::whereHas('pengajuanSkripsi', fn($q) => $q->where('program_studi_id', $prodiId))->where(fn($q) => $q->whereNull('penguji_1_id')->orWhereNull('penguji_2_id'))->count();

        $kemiripanMap = [];
        foreach ($daftarJudul as $itemJudul) {
            $kemiripanMap[$itemJudul->id] = $similarityService->findSimilarTitles(
                $itemJudul->judul,
                excludeSkripsiId: $itemJudul->id,
                minThreshold: 40.0,
                limit: 3
            )->all();
        }

        return view('kaprodi.penetapan.index', compact(
            'daftarJudul', 'daftarSeminar', 'daftarSidang',
            'daftarDosen', 'daftarProdi', 'prodiFilter', 'user',
            'pendingJudulCount', 'pendingSeminarCount', 'pendingSidangCount',
            'kemiripanMap'
        ));
    }

    public function updateJudul(Request $request, PengajuanSkripsi $skripsi): RedirectResponse
    {
        $user = $request->user();
        if (!$user->isKaprodi() && !$user->isAdminUtama()) {
            abort(403, 'Akses khusus Kaprodi dan Admin Utama.');
        }
        $this->authorizeProdiScope($user, (int) $skripsi->program_studi_id);

        $action = $request->input('action'); // 'terima' or 'tolak'

        if ($action === 'tolak') {
            $request->validate([
                'catatan' => ['required', 'string', 'max:1000'],
            ], [
                'catatan.required' => 'Catatan penolakan / alasan wajib diisi.',
            ]);

            $errorMessage = DB::transaction(function () use ($request, $user, $skripsi): ?string {
                $lockedSkripsi = PengajuanSkripsi::with('mahasiswa')->whereKey($skripsi->id)->lockForUpdate()->firstOrFail();

                $latestP1 = $lockedSkripsi->latestPenugasanPembimbing1();
                $latestP2 = $lockedSkripsi->latestPenugasanPembimbing2();

                if (!$user->isAdminUtama()) {
                    $adaPenolakan = ($latestP1 && $latestP1->isDitolak()) || ($latestP2 && $latestP2->isDitolak());
                    if ($lockedSkripsi->pembimbing_1_id !== null && !$adaPenolakan) {
                        return 'Dosen pembimbing telah ditentukan oleh Kaprodi dan terkunci. Hanya Admin Utama yang berwenang mengubahnya.';
                    }
                }

                $lockedSkripsi->update([
                    'status' => StatusPengajuan::Ditolak,
                    'catatan' => $request->input('catatan'),
                ]);

                $actorRole = $user->isAdminUtama() ? 'Admin Utama' : 'Kaprodi';
                AktivitasLog::catat(
                    $user,
                    'Penolakan Judul Skripsi',
                    "{$actorRole} {$user->name} menolak judul mahasiswa {$lockedSkripsi->mahasiswa->name} ({$lockedSkripsi->mahasiswa->nomor_induk}): '{$lockedSkripsi->judul}'. Catatan: {$request->input('catatan')}"
                );

                Notifikasi::kirim(
                    $lockedSkripsi->mahasiswa_id,
                    'Pengajuan Judul Ditolak / Perlu Revisi',
                    "Pengajuan judul Anda '{$lockedSkripsi->judul}' ditolak oleh {$actorRole} dengan catatan: {$request->input('catatan')}",
                    route('mahasiswa.skripsi.index')
                );

                return null;
            });

            if ($errorMessage !== null) {
                return back()->with('error', $errorMessage);
            }

            return back()->with('success', 'Pengajuan judul berhasil ditolak dengan catatan.');
        }

        $dosenExistsRule = Rule::exists('users', 'id')->whereIn('role', [UserRole::Dosen->value, UserRole::Kaprodi->value]);
        $validated = $request->validate([
            'pembimbing_1_id' => ['required', $dosenExistsRule],
            'pembimbing_2_id' => ['nullable', $dosenExistsRule, 'different:pembimbing_1_id'],
            'catatan' => ['nullable', 'string', 'max:1000'],
        ], [
            'pembimbing_1_id.required' => 'Pembimbing Utama (1) wajib dipilih.',
            'pembimbing_2_id.different' => 'Pembimbing 2 tidak boleh sama dengan Pembimbing 1.',
        ]);

        $result = DB::transaction(function () use ($request, $user, $skripsi, $validated): array {
            $lockedSkripsi = PengajuanSkripsi::with('mahasiswa')->whereKey($skripsi->id)->lockForUpdate()->firstOrFail();

            $latestP1 = $lockedSkripsi->latestPenugasanPembimbing1();
            $latestP2 = $lockedSkripsi->latestPenugasanPembimbing2();

            // Aturan 5: Selain Admin Utama, tidak boleh merubah dosen pembimbing yang SUDAH MENYETUJUI atau yang sudah terkunci
            if (!$user->isAdminUtama()) {
                if ($latestP1 && $latestP1->isDisetujui() && $request->filled('pembimbing_1_id') && (int)$request->input('pembimbing_1_id') !== (int)$latestP1->dosen_id) {
                    return ['error' => 'Pembimbing Utama (' . ($latestP1->dosen ? $latestP1->dosen->name : '') . ') telah menyetujui penugasan dan terkunci. Hanya Admin Utama yang berwenang mengubahnya.'];
                }
                if ($latestP2 && $latestP2->isDisetujui() && $request->filled('pembimbing_2_id') && (int)$request->input('pembimbing_2_id') !== (int)$latestP2->dosen_id) {
                    return ['error' => 'Pembimbing Pendamping (' . ($latestP2->dosen ? $latestP2->dosen->name : '') . ') telah menyetujui penugasan dan terkunci. Hanya Admin Utama yang berwenang mengubahnya.'];
                }
                $adaPenolakan = ($latestP1 && $latestP1->isDitolak()) || ($latestP2 && $latestP2->isDitolak());
                if ($lockedSkripsi->pembimbing_1_id !== null && !$adaPenolakan) {
                    return ['error' => 'Dosen pembimbing telah ditentukan oleh Kaprodi dan terkunci. Hanya Admin Utama yang berwenang mengubahnya.'];
                }
            }

            $isUpdatePembimbing = $lockedSkripsi->pembimbing_1_id !== null;

            $lockedSkripsi->update([
                'pembimbing_1_id' => $validated['pembimbing_1_id'],
                'pembimbing_2_id' => $validated['pembimbing_2_id'] ?? null,
                'status' => StatusPengajuan::Diproses, // Menunggu SK Bimbingan dari Admin
                'catatan' => $validated['catatan'] ?? null,
            ]);

            $mhs = $lockedSkripsi->mahasiswa;
            $mhsName = $mhs ? $mhs->name : 'Mahasiswa';
            $mhsNim = $mhs ? $mhs->nomor_induk : '-';
            $judul = $lockedSkripsi->judul;

            // Proses Pembimbing 1 (pertahankan jika sudah disetujui)
            $this->handlePenugasanRole(
                $lockedSkripsi,
                'pembimbing_1',
                (int)$validated['pembimbing_1_id'],
                $user,
                'Pembimbing Utama (1)',
                $mhsName,
                $mhsNim,
                $judul
            );

            // Proses Pembimbing 2 (pertahankan jika sudah disetujui)
            if (!empty($validated['pembimbing_2_id'])) {
                $this->handlePenugasanRole(
                    $lockedSkripsi,
                    'pembimbing_2',
                    (int)$validated['pembimbing_2_id'],
                    $user,
                    'Pembimbing Pendamping (2)',
                    $mhsName,
                    $mhsNim,
                    $judul
                );
            } else {
                // Batalkan tiket pembimbing 2 jika sebelumnya ada yang menunggu
                PenugasanDosen::where('assignable_type', PengajuanSkripsi::class)
                    ->where('assignable_id', $lockedSkripsi->id)
                    ->where('peran', 'pembimbing_2')
                    ->where('status', StatusPenugasanDosen::Menunggu)
                    ->update(['status' => StatusPenugasanDosen::Dibatalkan]);
            }

            $actorRole = $user->isAdminUtama() ? 'Admin Utama' : 'Kaprodi';
            $logAction = $isUpdatePembimbing ? 'Pembaruan Dosen Pembimbing' : 'Penetapan Dosen Pembimbing';
            AktivitasLog::catat(
                $user,
                $logAction,
                "{$actorRole} {$user->name} " . ($isUpdatePembimbing ? "memperbarui penetapan" : "menyetujui judul & menetapkan") . " Dosen Pembimbing untuk mahasiswa {$mhsName} ({$mhsNim})" . ($user->isAdminUtama() ? ' (Mandat Langsung Admin Utama)' : '')
            );

            // Notifikasi ke Mahasiswa
            Notifikasi::kirim(
                $lockedSkripsi->mahasiswa_id,
                'Judul Disetujui & Pembimbing Ditetapkan',
                "Pengajuan judul Anda '{$lockedSkripsi->judul}' telah disetujui. Dosen Pembimbing telah ditetapkan dan menunggu penerbitan SK Bimbingan.",
                route('mahasiswa.skripsi.index')
            );

            $msg = $user->isAdminUtama()
                ? ($isUpdatePembimbing ? 'Dosen Pembimbing berhasil diperbarui secara langsung oleh Admin Utama (Mandat).' : 'Dosen Pembimbing berhasil ditetapkan langsung oleh Admin Utama (Mandat Resmi).')
                : ($isUpdatePembimbing ? 'Dosen Pembimbing berhasil diperbarui oleh Kaprodi.' : 'Judul disetujui & Dosen Pembimbing berhasil ditetapkan. Menunggu konfirmasi kesediaan dosen di Portal Dosen.');

            return ['success' => $msg];
        });

        if (isset($result['error'])) {
            return back()->with('error', $result['error']);
        }

        return back()->with('success', $result['success']);
    }

    public function assignPengujiSeminar(Request $request, SeminarSkripsi $seminar): RedirectResponse
    {
        $user = $request->user();
        if (!$user->isKaprodi() && !$user->isAdminUtama()) {
            abort(403, 'Akses khusus Kaprodi dan Admin Utama.');
        }

        $skripsi = $seminar->pengajuanSkripsi;
        $this->authorizeProdiScope($user, (int) $skripsi->program_studi_id);

        $excludedDosenIds = array_values(array_filter([$skripsi->pembimbing_1_id, $skripsi->pembimbing_2_id]));
        $dosenExistsRule = Rule::exists('users', 'id')->whereIn('role', [UserRole::Dosen->value, UserRole::Kaprodi->value]);

        $rules = [
            'penguji_seminar_id' => ['required', $dosenExistsRule],
        ];
        if (!empty($excludedDosenIds)) {
            $rules['penguji_seminar_id'][] = Rule::notIn($excludedDosenIds);
        }

        $validated = $request->validate($rules, [
            'penguji_seminar_id.required' => 'Dosen Penguji Seminar wajib dipilih.',
            'penguji_seminar_id.not_in' => 'Dosen pembimbing skripsi tidak dapat dipilih sebagai dosen penguji seminar untuk mahasiswa yang sama.',
        ]);

        $result = DB::transaction(function () use ($request, $user, $seminar, $validated): array {
            $lockedSeminar = SeminarSkripsi::with('pengajuanSkripsi.mahasiswa')->whereKey($seminar->id)->lockForUpdate()->firstOrFail();
            $latestPenguji = $lockedSeminar->latestPenugasanPenguji();

            // Aturan 5: Selain Admin Utama, tidak boleh merubah dosen penguji yang SUDAH MENYETUJUI atau yang sudah terkunci
            if (!$user->isAdminUtama()) {
                if ($latestPenguji && $latestPenguji->isDisetujui() && $request->filled('penguji_seminar_id') && (int)$request->input('penguji_seminar_id') !== (int)$latestPenguji->dosen_id) {
                    return ['error' => 'Dosen penguji seminar (' . ($latestPenguji->dosen ? $latestPenguji->dosen->name : '') . ') telah menyetujui penugasan dan terkunci. Hanya Admin Utama yang berwenang mengubahnya.'];
                }
                if ($lockedSeminar->penguji_seminar_id !== null && (!$latestPenguji || !$latestPenguji->isDitolak())) {
                    return ['error' => 'Dosen penguji seminar telah ditentukan oleh Kaprodi dan terkunci. Hanya Admin Utama yang berwenang mengubahnya.'];
                }
            }

            $isUpdatePenguji = $lockedSeminar->penguji_seminar_id !== null;

            $lockedSeminar->update([
                'penguji_seminar_id' => $validated['penguji_seminar_id'],
                'status' => StatusPengajuan::Diproses,
            ]);

            $mhs = $lockedSeminar->pengajuanSkripsi->mahasiswa;
            $mhsName = $mhs ? $mhs->name : 'Mahasiswa';
            $mhsNim = $mhs ? $mhs->nomor_induk : '-';
            $judul = $lockedSeminar->pengajuanSkripsi->judul;

            // Tangani penugasan penguji seminar (pertahankan jika sudah disetujui)
            $this->handlePenugasanRole(
                $lockedSeminar,
                'penguji_seminar',
                (int)$validated['penguji_seminar_id'],
                $user,
                'Dosen Penguji Seminar',
                $mhsName,
                $mhsNim,
                $judul
            );

            $actorRole = $user->isAdminUtama() ? 'Admin Utama' : 'Kaprodi';
            $logAction = $isUpdatePenguji ? 'Pembaruan Penguji Seminar' : 'Penetapan Penguji Seminar';

            AktivitasLog::catat(
                $user,
                $logAction,
                "{$actorRole} {$user->name} " . ($isUpdatePenguji ? "memperbarui" : "menetapkan") . " Dosen Penguji Seminar untuk mahasiswa {$mhsName} ({$mhsNim})" . ($user->isAdminUtama() ? ' (Mandat Langsung Admin Utama)' : '')
            );

            // Notifikasi ke Mahasiswa
            Notifikasi::kirim(
                $mhs->id,
                'Dosen Penguji Seminar Ditetapkan',
                "Dosen Penguji Seminar Anda telah ditetapkan. Menunggu pengaturan jadwal pelaksanaan dari Admin.",
                route('mahasiswa.seminar.index')
            );

            // Notifikasi ke Admin (Prodi & Utama) untuk persiapan jadwal
            Notifikasi::kirimKePengelola(
                $lockedSeminar->pengajuanSkripsi->program_studi_id,
                'Dosen Penguji Seminar Telah Ditetapkan',
                "Dosen Penguji Seminar untuk mahasiswa {$mhsName} ({$mhsNim}) telah ditetapkan. Silakan atur jadwal dan terbitkan dokumen seminar.",
                null,
                $user->id,
                [UserRole::AdminUtama, UserRole::AdminProdi]
            );

            $msg = $user->isAdminUtama()
                ? ($isUpdatePenguji ? 'Dosen Penguji Seminar berhasil diperbarui secara langsung oleh Admin Utama (Mandat).' : 'Dosen Penguji Seminar berhasil ditetapkan langsung oleh Admin Utama (Mandat Resmi).')
                : ($isUpdatePenguji ? 'Dosen Penguji Seminar berhasil diperbarui oleh Kaprodi.' : 'Dosen Penguji Seminar berhasil ditetapkan. Menunggu konfirmasi kesediaan dosen di Portal Dosen.');

            return ['success' => $msg];
        });

        if (isset($result['error'])) {
            return back()->with('error', $result['error']);
        }

        return back()->with('success', $result['success']);
    }

    public function assignPengujiSidang(Request $request, SidangSkripsi $sidang): RedirectResponse
    {
        $user = $request->user();
        if (!$user->isKaprodi() && !$user->isAdminUtama()) {
            abort(403, 'Akses khusus Kaprodi dan Admin Utama.');
        }

        $skripsi = $sidang->pengajuanSkripsi;
        $this->authorizeProdiScope($user, (int) $skripsi->program_studi_id);

        $excludedDosenIds = array_values(array_filter([$skripsi->pembimbing_1_id, $skripsi->pembimbing_2_id]));
        $dosenExistsRule = Rule::exists('users', 'id')->whereIn('role', [UserRole::Dosen->value, UserRole::Kaprodi->value]);

        $rules = [
            'penguji_1_id' => ['required', $dosenExistsRule],
            'penguji_2_id' => ['required', $dosenExistsRule, 'different:penguji_1_id'],
        ];
        if (!empty($excludedDosenIds)) {
            $rules['penguji_1_id'][] = Rule::notIn($excludedDosenIds);
            $rules['penguji_2_id'][] = Rule::notIn($excludedDosenIds);
        }

        $validated = $request->validate($rules, [
            'penguji_1_id.required' => 'Dosen Penguji 1 wajib dipilih.',
            'penguji_1_id.not_in' => 'Dosen pembimbing skripsi tidak dapat dipilih sebagai dewan penguji sidang untuk mahasiswa yang sama.',
            'penguji_2_id.required' => 'Dosen Penguji 2 wajib dipilih.',
            'penguji_2_id.different' => 'Penguji 2 tidak boleh sama dengan Penguji 1.',
            'penguji_2_id.not_in' => 'Dosen pembimbing skripsi tidak dapat dipilih sebagai dewan penguji sidang untuk mahasiswa yang sama.',
        ]);

        $result = DB::transaction(function () use ($request, $user, $sidang, $validated): array {
            $lockedSidang = SidangSkripsi::with('pengajuanSkripsi.mahasiswa')->whereKey($sidang->id)->lockForUpdate()->firstOrFail();

            $latestPenguji1 = $lockedSidang->latestPenugasanPenguji1();
            $latestPenguji2 = $lockedSidang->latestPenugasanPenguji2();

            // Aturan 5: Selain Admin Utama, tidak boleh merubah dosen penguji yang SUDAH MENYETUJUI atau yang sudah terkunci
            if (!$user->isAdminUtama()) {
                if ($latestPenguji1 && $latestPenguji1->isDisetujui() && $request->filled('penguji_1_id') && (int)$request->input('penguji_1_id') !== (int)$latestPenguji1->dosen_id) {
                    return ['error' => 'Penguji 1 Sidang (' . ($latestPenguji1->dosen ? $latestPenguji1->dosen->name : '') . ') telah menyetujui penugasan dan terkunci. Hanya Admin Utama yang berwenang mengubahnya.'];
                }
                if ($latestPenguji2 && $latestPenguji2->isDisetujui() && $request->filled('penguji_2_id') && (int)$request->input('penguji_2_id') !== (int)$latestPenguji2->dosen_id) {
                    return ['error' => 'Penguji 2 Sidang (' . ($latestPenguji2->dosen ? $latestPenguji2->dosen->name : '') . ') telah menyetujui penugasan dan terkunci. Hanya Admin Utama yang berwenang mengubahnya.'];
                }
                $adaPenolakan = ($latestPenguji1 && $latestPenguji1->isDitolak()) || ($latestPenguji2 && $latestPenguji2->isDitolak());
                if ($lockedSidang->penguji_1_id !== null && $lockedSidang->penguji_2_id !== null && !$adaPenolakan) {
                    return ['error' => 'Dewan penguji sidang telah ditentukan oleh Kaprodi dan terkunci. Hanya Admin Utama yang berwenang mengubahnya.'];
                }
            }

            $isUpdatePenguji = ($lockedSidang->penguji_1_id !== null || $lockedSidang->penguji_2_id !== null);

            $lockedSidang->update([
                'penguji_1_id' => $validated['penguji_1_id'],
                'penguji_2_id' => $validated['penguji_2_id'],
                'status' => StatusPengajuan::Diproses,
            ]);

            $mhs = $lockedSidang->pengajuanSkripsi->mahasiswa;
            $mhsName = $mhs ? $mhs->name : 'Mahasiswa';
            $mhsNim = $mhs ? $mhs->nomor_induk : '-';
            $judul = $lockedSidang->pengajuanSkripsi->judul;

            // Proses Penguji 1 (pertahankan jika sudah disetujui)
            $this->handlePenugasanRole(
                $lockedSidang,
                'penguji_1',
                (int)$validated['penguji_1_id'],
                $user,
                'Penguji 1 Sidang Skripsi',
                $mhsName,
                $mhsNim,
                $judul
            );

            // Proses Penguji 2 (pertahankan jika sudah disetujui)
            $this->handlePenugasanRole(
                $lockedSidang,
                'penguji_2',
                (int)$validated['penguji_2_id'],
                $user,
                'Penguji 2 Sidang Skripsi',
                $mhsName,
                $mhsNim,
                $judul
            );

            $actorRole = $user->isAdminUtama() ? 'Admin Utama' : 'Kaprodi';
            $logAction = $isUpdatePenguji ? 'Pembaruan 2 Penguji Sidang' : 'Penetapan 2 Penguji Sidang';

            AktivitasLog::catat(
                $user,
                $logAction,
                "{$actorRole} {$user->name} " . ($isUpdatePenguji ? "memperbarui" : "menetapkan") . " 2 Penguji Sidang Skripsi untuk mahasiswa {$mhsName} ({$mhsNim})" . ($user->isAdminUtama() ? ' (Mandat Langsung Admin Utama)' : '')
            );

            // Notifikasi ke Mahasiswa
            Notifikasi::kirim(
                $mhs->id,
                '2 Dosen Penguji Sidang Ditetapkan',
                "2 Orang Dosen Penguji Sidang Skripsi telah ditetapkan. Menunggu pengaturan jadwal pelaksanaan dari Admin.",
                route('mahasiswa.sidang.index')
            );

            // Notifikasi ke Admin (Prodi & Utama) untuk persiapan jadwal
            Notifikasi::kirimKePengelola(
                $lockedSidang->pengajuanSkripsi->program_studi_id,
                'Dewan Penguji Sidang Telah Ditetapkan',
                "Dewan Penguji Sidang Skripsi untuk mahasiswa {$mhsName} ({$mhsNim}) telah ditetapkan. Silakan atur jadwal dan terbitkan dokumen sidang.",
                null,
                $user->id,
                [UserRole::AdminUtama, UserRole::AdminProdi]
            );

            $msg = $user->isAdminUtama()
                ? ($isUpdatePenguji ? '2 Orang Dosen Penguji Sidang Skripsi berhasil diperbarui secara langsung oleh Admin Utama (Mandat).' : '2 Orang Dosen Penguji Sidang Skripsi berhasil ditetapkan langsung oleh Admin Utama (Mandat Resmi).')
                : ($isUpdatePenguji ? '2 Orang Dosen Penguji Sidang Skripsi berhasil diperbarui oleh Kaprodi.' : '2 Orang Dosen Penguji Sidang Skripsi berhasil ditetapkan. Menunggu konfirmasi kesediaan dosen di Portal Dosen.');

            return ['success' => $msg];
        });

        if (isset($result['error'])) {
            return back()->with('error', $result['error']);
        }

        return back()->with('success', $result['success']);
    }

    private function authorizeProdiScope(User $user, int $programStudiId): void
    {
        if ($user->isKaprodi() && (int) $user->program_studi_id !== $programStudiId) {
            abort(403, 'Anda tidak berwenang mengelola data program studi lain.');
        }
    }

    /**
     * Mesin Penanganan Penugasan Role:
     * - Mempertahankan status dosen yang SUDAH MENYETUJUI (tidak mereset ke Menunggu)
     * - Mencegah duplikasi tiket jika dosen sama masih berstatus Menunggu
     * - Membuat tiket baru hanya jika ada pergantian dosen atau penugasan baru
     */
    private function handlePenugasanRole(
        $assignable,
        string $peran,
        int $newDosenId,
        User $actor,
        string $labelPeran,
        string $mhsName,
        string $mhsNim,
        string $judul
    ): void {
        $isAdminUtama = $actor->isAdminUtama();

        // Ambil penugasan terakhir untuk peran ini pada assignable ini
        $latest = PenugasanDosen::where('assignable_type', get_class($assignable))
            ->where('assignable_id', $assignable->id)
            ->where('peran', $peran)
            ->latest('id')
            ->first();

        // Skenario 1: Dosen yang sama dan statusnya SUDAH DISETUJUI
        if ($latest && (int)$latest->dosen_id === $newDosenId && $latest->isDisetujui()) {
            // Status dosen dipertahankan (TIDAK diubah kembali ke Menunggu)
            if ($isAdminUtama && !$latest->is_mandat_admin_utama) {
                $latest->update(['is_mandat_admin_utama' => true]);
            }
            return;
        }

        // Skenario 2: Dosen yang sama dan statusnya MASIH MENUNGGU
        if ($latest && (int)$latest->dosen_id === $newDosenId && $latest->isMenunggu()) {
            if ($isAdminUtama) {
                // Admin Utama mengesahkan langsung (mandat fast-track)
                $latest->update([
                    'status' => StatusPenugasanDosen::Disetujui,
                    'is_mandat_admin_utama' => true,
                    'direspon_pada' => now(),
                    'ditugaskan_oleh' => $actor->id,
                ]);

                Notifikasi::kirim(
                    $newDosenId,
                    "Penugasan {$labelPeran}",
                    "Anda ditetapkan secara langsung (Mandat Admin Utama) sebagai {$labelPeran} untuk mahasiswa {$mhsName} ({$mhsNim}) dengan judul '{$judul}'.",
                    route('dosen.bimbingan.index')
                );
            }
            return;
        }

        // Skenario 3: Dosen berbeda ATAU penugasan sebelumnya ditolak/dibatalkan
        // Batalkan tiket penugasan yang masih berstatus menunggu untuk peran ini
        PenugasanDosen::where('assignable_type', get_class($assignable))
            ->where('assignable_id', $assignable->id)
            ->where('peran', $peran)
            ->where('status', StatusPenugasanDosen::Menunggu)
            ->update(['status' => StatusPenugasanDosen::Dibatalkan]);

        $statusPenugasan = $isAdminUtama ? StatusPenugasanDosen::Disetujui : StatusPenugasanDosen::Menunggu;

        PenugasanDosen::create([
            'assignable_type' => get_class($assignable),
            'assignable_id' => $assignable->id,
            'dosen_id' => $newDosenId,
            'peran' => $peran,
            'status' => $statusPenugasan,
            'ditugaskan_oleh' => $actor->id,
            'is_mandat_admin_utama' => $isAdminUtama,
            'direspon_pada' => $isAdminUtama ? now() : null,
        ]);

        $pesan = $isAdminUtama
            ? "Anda ditetapkan secara langsung (Mandat Admin Utama) sebagai {$labelPeran} untuk mahasiswa {$mhsName} ({$mhsNim}) dengan judul '{$judul}'."
            : "Anda ditetapkan sebagai {$labelPeran} untuk mahasiswa {$mhsName} ({$mhsNim}) dengan judul '{$judul}'. Silakan konfirmasi kesediaan Anda di Portal Dosen.";

        Notifikasi::kirim(
            $newDosenId,
            "Penugasan {$labelPeran}",
            $pesan,
            route('dosen.bimbingan.index')
        );
    }
}
