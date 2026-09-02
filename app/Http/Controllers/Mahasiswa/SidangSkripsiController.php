<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Enums\StatusPengajuan;
use App\Http\Controllers\Controller;
use App\Models\AktivitasLog;
use App\Models\SidangSkripsi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SidangSkripsiController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $skripsi = $user->pengajuanSkripsi()->with(['seminar', 'sidang.penguji1', 'sidang.penguji2', 'pembimbing1', 'pembimbing2'])->first();

        return view('mahasiswa.sidang.index', compact('skripsi', 'user'));
    }

    public function create(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        $skripsi = $user->pengajuanSkripsi()->with(['seminar', 'sidang'])->first();

        if (!$skripsi || !$skripsi->seminar || !$skripsi->seminar->isSelesai()) {
            return redirect()->route('mahasiswa.sidang.index')
                ->with('error', 'Anda belum dapat mengajukan sidang skripsi. Pastikan Anda telah lulus seminar.');
        }

        if ($skripsi->sidang && $skripsi->sidang->status !== StatusPengajuan::Ditolak) {
            return redirect()->route('mahasiswa.sidang.index')
                ->with('info', 'Anda sudah memiliki pendaftaran sidang yang sedang berjalan.');
        }

        return view('mahasiswa.sidang.create', compact('skripsi', 'user'));
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $skripsi = $user->pengajuanSkripsi()->with('seminar')->firstOrFail();

        if (!$skripsi->seminar || !$skripsi->seminar->isSelesai()) {
            abort(403, 'Anda belum menyelesaikan seminar.');
        }

        $validated = $request->validate([
            'file_naskah_sidang' => ['required', 'file', 'mimes:pdf', 'max:15360'], // max 15MB
            'file_acc_sidang' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'file_bebas_revisi_seminar' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'file_bukti_bayar_sidang' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ], [
            'file_naskah_sidang.required' => 'File naskah skripsi lengkap (final) wajib diunggah (PDF, maks 15MB).',
            'file_acc_sidang.required' => 'File lembar persetujuan sidang dari pembimbing wajib diunggah.',
            'file_bebas_revisi_seminar.required' => 'File bukti bebas revisi seminar wajib diunggah.',
            'file_bukti_bayar_sidang.required' => 'File bukti pembayaran sidang skripsi / SPP wajib diunggah.',
        ]);

        $paths = [
            'file_naskah_sidang' => $request->file('file_naskah_sidang')->store('sidang/naskah'),
            'file_acc_sidang' => $request->file('file_acc_sidang')->store('sidang/acc'),
            'file_bebas_revisi_seminar' => $request->file('file_bebas_revisi_seminar')->store('sidang/bebas_revisi'),
            'file_bukti_bayar_sidang' => $request->file('file_bukti_bayar_sidang')->store('sidang/bukti_bayar'),
        ];

        SidangSkripsi::updateOrCreate(
            ['pengajuan_skripsi_id' => $skripsi->id],
            array_merge($paths, ['status' => StatusPengajuan::Diajukan])
        );

        AktivitasLog::catat(
            $user,
            'Pengajuan Sidang Skripsi',
            "Mahasiswa {$user->name} ({$user->nomor_induk}) mengajukan sidang meja hijau untuk judul: '{$skripsi->judul}'"
        );

        return redirect()->route('mahasiswa.sidang.index')
            ->with('success', 'Pendaftaran sidang skripsi berhasil diajukan dan sedang menunggu penetapan penguji serta jadwal.');
    }
}
