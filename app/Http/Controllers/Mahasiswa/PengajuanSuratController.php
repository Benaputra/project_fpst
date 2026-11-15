<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Enums\JenisSuratInternal;
use App\Enums\StatusPengajuanSurat;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\AktivitasLog;
use App\Models\Notifikasi;
use App\Models\PengajuanSurat;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Enum;
use Illuminate\View\View;

class PengajuanSuratController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $daftarPengajuan = PengajuanSurat::query()
            ->with(['programStudi', 'verifikator', 'penyetuju', 'suratAktif'])
            ->where('mahasiswa_id', $user->id)
            ->latest()
            ->paginate(12);

        return view('mahasiswa.surat_internal.index', compact('daftarPengajuan', 'user'));
    }

    public function create(Request $request): View
    {
        $user = $request->user();
        $jenisList = JenisSuratInternal::cases();

        return view('mahasiswa.surat_internal.create', compact('user', 'jenisList'));
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user->program_studi_id) {
            return back()->with('error', 'Akun Anda belum terhubung ke Program Studi resmi FPST. Hubungi Admin Prodi.');
        }

        $validated = $request->validate([
            'jenis_surat' => ['required', new Enum(JenisSuratInternal::class)],
            'perihal' => ['required', 'string', 'max:200'],
            'tujuan_instansi' => ['required', 'string', 'max:200'],
            'alamat_instansi' => ['nullable', 'string', 'max:255'],
            'tanggal_mulai' => ['nullable', 'date'],
            'tanggal_selesai' => ['nullable', 'date', 'after_or_equal:tanggal_mulai'],
            'keterangan_kebutuhan' => ['required', 'string', 'max:1500'],
            'file_pendukung' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ], [
            'tanggal_selesai.after_or_equal' => 'Tanggal selesai tidak boleh lebih awal dari tanggal mulai.',
            'file_pendukung.max' => 'Ukuran berkas pendukung maksimal 5 MB.',
        ]);

        $uploadedPath = null;
        if ($request->hasFile('file_pendukung')) {
            $file = $request->file('file_pendukung');
            $nimSafe = preg_replace('/[^A-Za-z0-9_-]/', '_', (string) ($user->nomor_induk ?: $user->id));
            $ext = strtolower($file->getClientOriginalExtension() ?: 'pdf');
            $filename = "Pendukung_{$nimSafe}_" . now()->format('YmdHis') . '_' . Str::random(6) . ".{$ext}";
            $uploadedPath = $file->storeAs('surat_internal/pendukung', $filename, 'local');
        }

        try {
            $pengajuan = DB::transaction(function () use ($user, $validated, $uploadedPath): PengajuanSurat {
                $jenis = JenisSuratInternal::from($validated['jenis_surat']);

                $record = PengajuanSurat::create([
                    'mahasiswa_id' => $user->id,
                    'program_studi_id' => $user->program_studi_id,
                    'jenis_surat' => $jenis,
                    'perihal' => trim($validated['perihal']),
                    'tujuan_instansi' => trim($validated['tujuan_instansi']),
                    'alamat_instansi' => isset($validated['alamat_instansi']) ? trim($validated['alamat_instansi']) : null,
                    'tanggal_mulai' => $validated['tanggal_mulai'] ?? null,
                    'tanggal_selesai' => $validated['tanggal_selesai'] ?? null,
                    'keterangan_kebutuhan' => trim($validated['keterangan_kebutuhan']),
                    'file_pendukung' => $uploadedPath,
                    'status' => StatusPengajuanSurat::Diajukan,
                ]);

                AktivitasLog::catat(
                    $user,
                    'Pengajuan Surat Internal',
                    "Mahasiswa {$user->name} ({$user->nomor_induk}) mengajukan {$jenis->label()} ke {$record->tujuan_instansi}"
                );

                Notifikasi::kirimKePengelola(
                    $user->program_studi_id,
                    'Permohonan Surat Internal Baru',
                    "Mahasiswa {$user->name} mengajukan {$jenis->label()} ({$record->perihal}) yang perlu diverifikasi.",
                    route('admin.persuratan.index'),
                    $user->id,
                    [UserRole::Tendik, UserRole::AdminProdi, UserRole::AdminUtama]
                );

                return $record;
            });
        } catch (\Throwable $e) {
            if ($uploadedPath) {
                Storage::disk('local')->delete($uploadedPath);
            }
            throw $e;
        }

        return redirect()->route('mahasiswa.surat-internal.index')
            ->with('success', "Pengajuan {$pengajuan->jenis_surat->label()} berhasil dikirim dan menunggu verifikasi staf/Tendik.");
    }
}
