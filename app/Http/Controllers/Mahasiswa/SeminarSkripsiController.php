<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Enums\StatusPengajuan;
use App\Http\Controllers\Controller;
use App\Models\AktivitasLog;
use App\Models\Notifikasi;
use App\Models\PengajuanSkripsi;
use App\Models\SeminarSkripsi;
use App\Services\SeminarAudiensService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SeminarSkripsiController extends Controller
{
    public function index(Request $request, SeminarAudiensService $audiensService): View
    {
        $user = $request->user();
        $skripsi = $user->pengajuanSkripsi()->with(['seminar.penguji', 'seminar.bebasRevisiOleh', 'pembimbing1', 'pembimbing2'])->first();
        $kartuKendali = $audiensService->ringkasanKartuKendali($user);
        $enforceSyaratAudiens = (bool) config('fpst.seminar_audiens.enforce_syarat_seminar', true);

        return view('mahasiswa.seminar.index', compact('skripsi', 'user', 'kartuKendali', 'enforceSyaratAudiens'));
    }

    public function create(Request $request, SeminarAudiensService $audiensService): View|RedirectResponse
    {
        $user = $request->user();
        $skripsi = $user->pengajuanSkripsi()->with('seminar')->first();

        if (! $skripsi || ! $skripsi->canAjukanSeminar()) {
            return redirect()->route('mahasiswa.seminar.index')
                ->with('error', 'Anda belum dapat mengajukan seminar. Pastikan SK Bimbingan telah terbit.');
        }

        if (! $audiensService->mahasiswaMemenuhiSyaratSeminar($user)) {
            $ringkasan = $audiensService->ringkasanKartuKendali($user);

            return redirect()->route('mahasiswa.seminar.index')
                ->with('error', "Syarat kehadiran menonton seminar belum terpenuhi ({$ringkasan['total_hadir']}/{$ringkasan['maks_total']} kehadiran tervalidasi).");
        }

        if ($skripsi->seminar && $skripsi->seminar->status !== StatusPengajuan::Ditolak) {
            return redirect()->route('mahasiswa.seminar.index')
                ->with('info', 'Anda sudah memiliki pendaftaran seminar yang sedang berjalan.');
        }

        return view('mahasiswa.seminar.create', compact('skripsi', 'user'));
    }

    public function store(Request $request, SeminarAudiensService $audiensService): RedirectResponse
    {
        $user = $request->user();
        $skripsi = $user->pengajuanSkripsi()->firstOrFail();

        if (! $skripsi->canAjukanSeminar()) {
            abort(403, 'SK Bimbingan belum terbit.');
        }

        if (! $audiensService->mahasiswaMemenuhiSyaratSeminar($user)) {
            $ringkasan = $audiensService->ringkasanKartuKendali($user);

            return redirect()->route('mahasiswa.seminar.index')
                ->with('error', "Anda belum memenuhi syarat menonton seminar sebanyak {$ringkasan['maks_total']} kali (saat ini hadir: {$ringkasan['total_hadir']} kali).");
        }

        $request->validate([
            'file_naskah_seminar' => ['required', 'file', 'mimes:pdf', 'max:10240'], // max 10MB
            'file_acc_pembimbing' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'file_bukti_bayar_seminar' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'file_toefl' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ], [
            'file_naskah_seminar.required' => 'File naskah proposal lengkap wajib diunggah (PDF, maks 10MB).',
            'file_acc_pembimbing.required' => 'File lembar persetujuan (ACC) pembimbing wajib diunggah.',
            'file_bukti_bayar_seminar.required' => 'File bukti pembayaran seminar wajib diunggah.',
            'file_toefl.required' => 'File sertifikat TOEFL/bahasa wajib diunggah.',
        ]);

        $paths = [
            'file_naskah_seminar' => $request->file('file_naskah_seminar')->store('seminar/naskah', 'local'),
            'file_acc_pembimbing' => $request->file('file_acc_pembimbing')->store('seminar/acc', 'local'),
            'file_bukti_bayar_seminar' => $request->file('file_bukti_bayar_seminar')->store('seminar/bukti_bayar', 'local'),
            'file_toefl' => $request->file('file_toefl')->store('seminar/toefl', 'local'),
        ];

        try {
            $blocked = DB::transaction(function () use ($user, $skripsi, $paths): bool {
                $lockedSkripsi = PengajuanSkripsi::whereKey($skripsi->id)->lockForUpdate()->firstOrFail();

                if (! $lockedSkripsi->canAjukanSeminar()) {
                    abort(403, 'SK Bimbingan belum terbit.');
                }

                $existingSeminar = SeminarSkripsi::where('pengajuan_skripsi_id', $lockedSkripsi->id)
                    ->lockForUpdate()
                    ->first();

                if ($existingSeminar && $existingSeminar->status !== StatusPengajuan::Ditolak) {
                    return true;
                }

                SeminarSkripsi::updateOrCreate(
                    ['pengajuan_skripsi_id' => $lockedSkripsi->id],
                    array_merge($paths, ['status' => StatusPengajuan::Diajukan])
                );

                AktivitasLog::catat(
                    $user,
                    'Pengajuan Seminar Skripsi',
                    "Mahasiswa {$user->name} ({$user->nomor_induk}) mengajukan pendaftaran seminar proposal/hasil untuk judul: '{$lockedSkripsi->judul}'"
                );

                // Notifikasi ke Pengelola (Kaprodi, Admin Prodi, Admin Utama)
                Notifikasi::kirimKePengelola(
                    $user->program_studi_id,
                    'Pendaftaran Seminar Skripsi Baru',
                    "Mahasiswa {$user->name} ({$user->nomor_induk}) mendaftar seminar proposal/hasil untuk judul: '{$lockedSkripsi->judul}'.",
                    null,
                    $user->id
                );

                return false;
            });
        } catch (\Throwable $e) {
            Storage::disk('local')->delete(array_values($paths));
            throw $e;
        }

        if ($blocked) {
            Storage::disk('local')->delete(array_values($paths));

            return redirect()->route('mahasiswa.seminar.index')
                ->with('error', 'Anda sudah memiliki pendaftaran seminar yang aktif atau telah selesai.');
        }

        return redirect()->route('mahasiswa.seminar.index')
            ->with('success', 'Pendaftaran seminar berhasil diajukan dan sedang menunggu penetapan penguji serta jadwal.');
    }
}
