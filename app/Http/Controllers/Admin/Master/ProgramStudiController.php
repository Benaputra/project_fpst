<?php

namespace App\Http\Controllers\Admin\Master;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\AktivitasLog;
use App\Models\ProgramStudi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProgramStudiController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->input('search');

        $query = ProgramStudi::withCount([
            'users as mahasiswa_count' => fn ($q) => $q->where('role', UserRole::Mahasiswa),
            'users as dosen_count' => fn ($q) => $q->whereIn('role', [UserRole::Dosen, UserRole::Kaprodi]),
            'users as admin_count' => fn ($q) => $q->where('role', UserRole::AdminProdi),
            'pengajuanSkripsi',
        ]);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                    ->orWhere('kode', 'like', "%{$search}%");
            });
        }

        $daftarProdi = $query->orderBy('nama')->paginate(15)->withQueryString();
        $totalProdi = ProgramStudi::count();

        return view('admin.master.prodi.index', compact(
            'daftarProdi',
            'search',
            'totalProdi'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:255|unique:program_studi,nama',
            'kode' => 'nullable|string|max:10|unique:program_studi,kode',
            'file_ttd_kaprodi' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:2048',
        ], [
            'nama.unique' => 'Nama program studi sudah terdaftar.',
            'kode.unique' => 'Kode program studi sudah terdaftar.',
            'file_ttd_kaprodi.image' => 'Berkas tanda tangan harus berupa gambar (PNG, JPG, JPEG, WEBP).',
            'file_ttd_kaprodi.max' => 'Ukuran berkas tanda tangan maksimal 2MB.',
        ]);

        $actor = $request->user();

        $pathTtd = null;
        if ($request->hasFile('file_ttd_kaprodi')) {
            $pathTtd = $request->file('file_ttd_kaprodi')->store('ttd', 'public');
        }

        $prodi = ProgramStudi::create([
            'nama' => $validated['nama'],
            'kode' => ! empty($validated['kode']) ? strtoupper(trim($validated['kode'])) : null,
            'file_ttd_kaprodi' => $pathTtd,
        ]);

        AktivitasLog::catat(
            $actor,
            'Tambah Program Studi',
            "Admin Utama {$actor->name} menambahkan program studi baru: {$prodi->nama} (Kode: {$prodi->kode})"
        );

        return redirect()->route('admin.master.prodi.index')
            ->with('success', "Program studi {$prodi->nama} berhasil ditambahkan.");
    }

    public function update(Request $request, ProgramStudi $prodi): RedirectResponse
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:255', Rule::unique('program_studi', 'nama')->ignore($prodi->id)],
            'kode' => ['nullable', 'string', 'max:10', Rule::unique('program_studi', 'kode')->ignore($prodi->id)],
            'file_ttd_kaprodi' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:2048',
        ], [
            'nama.unique' => 'Nama program studi sudah terdaftar.',
            'kode.unique' => 'Kode program studi sudah terdaftar.',
            'file_ttd_kaprodi.image' => 'Berkas tanda tangan harus berupa gambar (PNG, JPG, JPEG, WEBP).',
            'file_ttd_kaprodi.max' => 'Ukuran berkas tanda tangan maksimal 2MB.',
        ]);

        $dataUpdate = [
            'nama' => $validated['nama'],
            'kode' => ! empty($validated['kode']) ? strtoupper(trim($validated['kode'])) : null,
        ];

        if ($request->hasFile('file_ttd_kaprodi')) {
            if ($prodi->file_ttd_kaprodi && \Illuminate\Support\Facades\Storage::disk('public')->exists($prodi->file_ttd_kaprodi)) {
                if (! str_contains($prodi->file_ttd_kaprodi, 'ttd_agribisnis.png') && ! str_contains($prodi->file_ttd_kaprodi, 'ttd_agroteknologi.png')) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($prodi->file_ttd_kaprodi);
                }
            }
            $dataUpdate['file_ttd_kaprodi'] = $request->file('file_ttd_kaprodi')->store('ttd', 'public');
        }

        $prodi->update($dataUpdate);

        $actor = $request->user();
        AktivitasLog::catat(
            $actor,
            'Ubah Program Studi',
            "Admin Utama {$actor->name} memperbarui program studi: {$prodi->nama} (Kode: {$prodi->kode})"
        );

        return redirect()->route('admin.master.prodi.index')
            ->with('success', "Program studi {$prodi->nama} berhasil diperbarui.");
    }

    public function destroy(Request $request, ProgramStudi $prodi): RedirectResponse
    {
        $actor = $request->user();

        if ($prodi->users()->exists()) {
            return back()->with('error', "Program studi {$prodi->nama} tidak dapat dihapus karena masih memiliki mahasiswa, dosen, atau admin terdaftar.");
        }

        if ($prodi->pengajuanSkripsi()->exists()) {
            return back()->with('error', "Program studi {$prodi->nama} tidak dapat dihapus karena memiliki riwayat data skripsi.");
        }

        $nama = $prodi->nama;
        $prodi->delete();

        AktivitasLog::catat(
            $actor,
            'Hapus Program Studi',
            "Admin Utama {$actor->name} menghapus program studi {$nama}"
        );

        return redirect()->route('admin.master.prodi.index')
            ->with('success', "Program studi {$nama} berhasil dihapus.");
    }
}
