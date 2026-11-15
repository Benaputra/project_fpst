<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use App\Models\LokasiPl;
use App\Models\PraktikLapang;
use App\Services\PraktikLapangService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class PraktikLapangController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $minSks = (int) config('fpst.praktik_lapang.min_sks', 90);
        $periodeAktif = (string) $request->query('periode', 'Ganjil 2026/2027');

        $daftarLokasi = LokasiPl::query()
            ->terverifikasi()
            ->withCount([
                'praktikLapang as terisi_periode_count' => fn ($q) => $q
                    ->where('periode_akademik', $periodeAktif)
                    ->where('status', '!=', \App\Enums\StatusPraktikLapang::Ditolak->value),
            ])
            ->orderBy('nama_instansi')
            ->get();

        $lokasiUsulanSaya = LokasiPl::query()
            ->where('diusulkan_oleh', $user->id)
            ->latest('id')
            ->get();

        $riwayatPl = PraktikLapang::query()
            ->with([
                'lokasiPl',
                'dpl:id,name',
                'pengajuanSurat:id,jenis_surat,status,nomor_surat_terbit,file_surat_terbit',
                'logbooks',
                'surat',
            ])
            ->where('mahasiswa_id', $user->id)
            ->latest('id')
            ->get();

        return view('mahasiswa.pl.index', compact(
            'user',
            'minSks',
            'periodeAktif',
            'daftarLokasi',
            'lokasiUsulanSaya',
            'riwayatPl'
        ));
    }

    public function daftar(Request $request, PraktikLapangService $plService): RedirectResponse
    {
        $validated = $request->validate([
            'periode_akademik' => ['required', 'string', 'max:40'],
            'sks_ditempuh' => ['required', 'integer', 'min:0', 'max:200'],
            'judul_kegiatan' => ['required', 'string', 'min:10', 'max:255'],
            'lokasi_pl_id' => ['nullable', 'exists:lokasi_pl,id'],
            'nama_instansi_baru' => ['required_without:lokasi_pl_id', 'nullable', 'string', 'max:180'],
            'sektor_baru' => ['nullable', 'in:AGT,AGB,AGT/AGB'],
            'alamat_baru' => ['required_without:lokasi_pl_id', 'nullable', 'string', 'max:255'],
            'kabupaten_kota_baru' => ['nullable', 'string', 'max:120'],
            'kontak_pic_baru' => ['nullable', 'string', 'max:120'],
            'telepon_pic_baru' => ['nullable', 'string', 'max:40'],
            'file_transkrip_sks' => ['nullable', 'file', 'mimes:pdf', 'max:5120'],
        ], [
            'nama_instansi_baru.required_without' => 'Pilih instansi mitra terdaftar atau masukkan nama instansi usulan baru.',
            'alamat_baru.required_without' => 'Alamat instansi baru wajib diisi bila tidak memilih mitra terdaftar.',
        ]);

        $path = $request->hasFile('file_transkrip_sks')
            ? $request->file('file_transkrip_sks')->store('praktik_lapang/transkrip', 'local')
            : null;

        try {
            $plService->daftarPl($request->user(), $validated, $path);

            return back()->with('success', 'Pendaftaran Praktik Lapang (PL) berhasil disimpan.');
        } catch (DomainException $e) {
            if ($path) {
                Storage::disk('local')->delete($path);
            }

            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function ajukanSuratPengantar(Request $request, PraktikLapang $pl, PraktikLapangService $plService): RedirectResponse
    {
        try {
            $plService->ajukanSuratPengantar($pl, $request->user());

            return back()->with('success', 'Permohonan Surat Pengantar PL berhasil dikirim ke Modul Persuratan Fakultas.');
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function unggahBalasan(Request $request, PraktikLapang $pl, PraktikLapangService $plService): RedirectResponse
    {
        $request->validate([
            'file_surat_balasan' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        $path = $request->file('file_surat_balasan')->store('praktik_lapang/balasan', 'local');

        try {
            $plService->unggahSuratBalasan($pl, $request->user(), $path);

            return back()->with('success', 'Surat balasan instansi PL berhasil diunggah. Menunggu penetapan DPL oleh Program Studi.');
        } catch (DomainException $e) {
            Storage::disk('local')->delete($path);

            return back()->with('error', $e->getMessage());
        }
    }

    public function tambahLogbook(Request $request, PraktikLapang $pl, PraktikLapangService $plService): RedirectResponse
    {
        $validated = $request->validate([
            'tanggal' => ['required', 'date'],
            'aktivitas' => ['required', 'string', 'min:5', 'max:255'],
            'uraian' => ['required', 'string', 'min:10', 'max:2000'],
            'file_dokumentasi' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        $path = $request->hasFile('file_dokumentasi')
            ? $request->file('file_dokumentasi')->store('praktik_lapang/logbook', 'local')
            : null;

        try {
            $plService->tambahLogbook($pl, $request->user(), $validated, $path);

            return back()->with('success', 'Catatan kegiatan harian (logbook) PL berhasil ditambahkan.');
        } catch (DomainException $e) {
            if ($path) {
                Storage::disk('local')->delete($path);
            }

            return back()->with('error', $e->getMessage());
        }
    }

    public function unggahLaporan(Request $request, PraktikLapang $pl, PraktikLapangService $plService): RedirectResponse
    {
        $request->validate([
            'file_laporan_akhir' => ['required', 'file', 'mimes:pdf', 'max:10240'],
        ]);

        $path = $request->file('file_laporan_akhir')->store('praktik_lapang/laporan', 'local');

        try {
            $plService->unggahLaporanAkhir($pl, $request->user(), $path);

            return back()->with('success', 'Laporan akhir Praktik Lapang berhasil diunggah.');
        } catch (DomainException $e) {
            Storage::disk('local')->delete($path);

            return back()->with('error', $e->getMessage());
        }
    }
}
