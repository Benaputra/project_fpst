<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Enums\PeranAudiensSeminar;
use App\Enums\StatusAudiensSeminar;
use App\Enums\StatusPengajuan;
use App\Http\Controllers\Controller;
use App\Models\SeminarAudiens;
use App\Models\SeminarSkripsi;
use App\Services\SeminarAudiensService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;
use Illuminate\View\View;

class AudiensSeminarController extends Controller
{
    public function index(Request $request, SeminarAudiensService $audiensService): View
    {
        $user = $request->user();
        $kartuKendali = $audiensService->ringkasanKartuKendali($user);

        $daftarSeminar = SeminarSkripsi::query()
            ->with([
                'pengajuanSkripsi.mahasiswa:id,name',
                'pengajuanSkripsi.programStudi:id,nama,kode',
                'penguji:id,name',
            ])
            ->withCount([
                'audiens as terisi_total_count' => fn ($q) => $q->whereIn('status', [
                    StatusAudiensSeminar::Terdaftar->value,
                    StatusAudiensSeminar::Hadir->value,
                ]),
                'audiens as terisi_penyanggah_count' => fn ($q) => $q->whereIn('status', [
                    StatusAudiensSeminar::Terdaftar->value,
                    StatusAudiensSeminar::Hadir->value,
                ])->where('peran', PeranAudiensSeminar::Penyanggah->value),
            ])
            ->whereNotNull('tgl_seminar')
            ->where('status', '!=', StatusPengajuan::Ditolak)
            ->where('status', '!=', StatusPengajuan::Selesai)
            ->orderBy('tgl_seminar')
            ->paginate(10, ['*'], 'page_sesi')
            ->withQueryString();

        $riwayatSaya = SeminarAudiens::query()
            ->with([
                'seminar.pengajuanSkripsi.mahasiswa:id,name',
                'seminar.pengajuanSkripsi.programStudi:id,nama,kode',
            ])
            ->where('mahasiswa_id', $user->id)
            ->latest('waktu_daftar')
            ->get();

        $pendaftaranAktifBySeminar = $riwayatSaya
            ->whereIn('status', [StatusAudiensSeminar::Terdaftar, StatusAudiensSeminar::Hadir])
            ->keyBy('seminar_skripsi_id');

        return view('mahasiswa.audiens.index', compact(
            'user',
            'kartuKendali',
            'daftarSeminar',
            'riwayatSaya',
            'pendaftaranAktifBySeminar'
        ));
    }

    public function daftar(Request $request, SeminarSkripsi $seminar, SeminarAudiensService $audiensService): RedirectResponse
    {
        $validated = $request->validate([
            'peran' => ['required', new Enum(PeranAudiensSeminar::class)],
        ]);

        try {
            $peran = PeranAudiensSeminar::from($validated['peran']);
            $audiens = $audiensService->daftar($seminar, $request->user(), $peran);

            return back()->with('success', "Berhasil mendaftar sebagai {$audiens->peran->label()} pada seminar ini.");
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function batalkan(Request $request, SeminarAudiens $audiens, SeminarAudiensService $audiensService): RedirectResponse
    {
        try {
            $audiensService->batalkan($audiens, $request->user());

            return back()->with('success', 'Pendaftaran penonton seminar berhasil dibatalkan.');
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function presensiMandiri(Request $request, SeminarAudiens $audiens, SeminarAudiensService $audiensService): RedirectResponse
    {
        $validated = $request->validate([
            'token_presensi' => ['required', 'string', 'max:48'],
        ]);

        try {
            $audiensService->validasiPresensiDenganToken($audiens, $request->user(), $validated['token_presensi']);

            return back()->with('success', 'Presensi kehadiran seminar berhasil dicatat ke Kartu Kendali Anda.');
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
