<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Enums\StatusPengajuan;
use App\Http\Controllers\Controller;
use App\Models\AktivitasLog;
use App\Models\PengajuanSkripsi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PengajuanSkripsiController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $skripsi = $user->pengajuanSkripsi()
            ->with(['programStudi', 'pembimbing1', 'pembimbing2', 'seminar', 'sidang'])
            ->first();

        return view('mahasiswa.skripsi.index', compact('skripsi', 'user'));
    }

    public function create(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        $existing = $user->pengajuanSkripsi()->first();

        if ($existing && $existing->status !== StatusPengajuan::Ditolak) {
            return redirect()->route('mahasiswa.skripsi.index')
                ->with('info', 'Anda sudah memiliki pengajuan judul skripsi yang aktif.');
        }

        return view('mahasiswa.skripsi.create', compact('user'));
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'judul' => ['required', 'string', 'min:10', 'max:500'],
            'abstrak' => ['nullable', 'string', 'max:5000'],
            'file_proposal' => ['required', 'file', 'mimes:pdf', 'max:5120'], // max 5MB
            'file_transkrip' => ['required', 'file', 'mimes:pdf', 'max:5120'],
            'file_bukti_bayar' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ], [
            'judul.required' => 'Judul skripsi wajib diisi.',
            'judul.min' => 'Judul skripsi minimal 10 karakter.',
            'file_proposal.required' => 'File draf proposal wajib diunggah (PDF, maks 5MB).',
            'file_transkrip.required' => 'File transkrip nilai sementara wajib diunggah (PDF, maks 5MB).',
            'file_bukti_bayar.required' => 'File bukti pembayaran skripsi wajib diunggah.',
        ]);

        $paths = [
            'file_proposal' => $request->file('file_proposal')->store('skripsi/proposal'),
            'file_transkrip' => $request->file('file_transkrip')->store('skripsi/transkrip'),
            'file_bukti_bayar' => $request->file('file_bukti_bayar')->store('skripsi/bukti_bayar'),
        ];

        $skripsi = PengajuanSkripsi::create([
            'mahasiswa_id' => $user->id,
            'program_studi_id' => $user->program_studi_id,
            'judul' => $validated['judul'],
            'abstrak' => $validated['abstrak'] ?? null,
            'file_proposal' => $paths['file_proposal'],
            'file_transkrip' => $paths['file_transkrip'],
            'file_bukti_bayar' => $paths['file_bukti_bayar'],
            'status' => StatusPengajuan::Diajukan,
        ]);

        AktivitasLog::catat(
            $user,
            'Pengajuan Judul Skripsi',
            "Mahasiswa {$user->name} ({$user->nomor_induk}) mengajukan judul: '{$skripsi->judul}'"
        );

        return redirect()->route('mahasiswa.skripsi.index')
            ->with('success', 'Pengajuan judul skripsi berhasil dikirim dan sedang menunggu verifikasi/penetapan pembimbing.');
    }
}
