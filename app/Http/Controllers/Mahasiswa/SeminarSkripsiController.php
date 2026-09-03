<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Enums\StatusPengajuan;
use App\Http\Controllers\Controller;
use App\Models\AktivitasLog;
use App\Models\Notifikasi;
use App\Models\SeminarSkripsi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SeminarSkripsiController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $skripsi = $user->pengajuanSkripsi()->with(['seminar.penguji', 'pembimbing1', 'pembimbing2'])->first();

        return view('mahasiswa.seminar.index', compact('skripsi', 'user'));
    }

    public function create(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        $skripsi = $user->pengajuanSkripsi()->with('seminar')->first();

        if (!$skripsi || !$skripsi->canAjukanSeminar()) {
            return redirect()->route('mahasiswa.seminar.index')
                ->with('error', 'Anda belum dapat mengajukan seminar. Pastikan SK Bimbingan telah terbit.');
        }

        if ($skripsi->seminar && $skripsi->seminar->status !== StatusPengajuan::Ditolak) {
            return redirect()->route('mahasiswa.seminar.index')
                ->with('info', 'Anda sudah memiliki pendaftaran seminar yang sedang berjalan.');
        }

        return view('mahasiswa.seminar.create', compact('skripsi', 'user'));
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $skripsi = $user->pengajuanSkripsi()->firstOrFail();

        if (!$skripsi->canAjukanSeminar()) {
            abort(403, 'SK Bimbingan belum terbit.');
        }

        $validated = $request->validate([
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
            'file_naskah_seminar' => $request->file('file_naskah_seminar')->store('seminar/naskah'),
            'file_acc_pembimbing' => $request->file('file_acc_pembimbing')->store('seminar/acc'),
            'file_bukti_bayar_seminar' => $request->file('file_bukti_bayar_seminar')->store('seminar/bukti_bayar'),
            'file_toefl' => $request->file('file_toefl')->store('seminar/toefl'),
        ];

        SeminarSkripsi::updateOrCreate(
            ['pengajuan_skripsi_id' => $skripsi->id],
            array_merge($paths, ['status' => StatusPengajuan::Diajukan])
        );

        AktivitasLog::catat(
            $user,
            'Pengajuan Seminar Skripsi',
            "Mahasiswa {$user->name} ({$user->nomor_induk}) mengajukan pendaftaran seminar proposal/hasil untuk judul: '{$skripsi->judul}'"
        );

        // Notifikasi ke Pengelola (Kaprodi, Admin Prodi, Admin Utama)
        Notifikasi::kirimKePengelola(
            $user->program_studi_id,
            'Pendaftaran Seminar Skripsi Baru',
            "Mahasiswa {$user->name} ({$user->nomor_induk}) mendaftar seminar proposal/hasil untuk judul: '{$skripsi->judul}'.",
            null,
            $user->id
        );

        return redirect()->route('mahasiswa.seminar.index')
            ->with('success', 'Pendaftaran seminar berhasil diajukan dan sedang menunggu penetapan penguji serta jadwal.');
    }
}
