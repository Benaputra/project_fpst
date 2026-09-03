<?php

namespace App\Http\Controllers\Admin\Master;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\AktivitasLog;
use App\Models\ProgramStudi;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminProdiController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->input('search');
        $prodiFilter = $request->input('prodi_id');

        $query = User::where('role', UserRole::AdminProdi)->with('programStudi');

        if ($prodiFilter) {
            $query->where('program_studi_id', $prodiFilter);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('nomor_induk', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $daftarAdminProdi = $query->latest()->paginate(15)->withQueryString();
        $daftarProdi = ProgramStudi::orderBy('nama')->get();
        $totalAdminProdi = User::where('role', UserRole::AdminProdi)->count();

        return view('admin.master.admin_prodi.index', compact(
            'daftarAdminProdi',
            'daftarProdi',
            'search',
            'prodiFilter',
            'totalAdminProdi'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'nomor_induk' => 'nullable|string|max:50|unique:users,nomor_induk',
            'program_studi_id' => 'required|exists:program_studi,id',
            'no_hp' => 'nullable|string|max:20',
            'password' => 'nullable|string|min:6',
        ], [
            'email.unique' => 'Email sudah terdaftar.',
            'nomor_induk.unique' => 'Nomor induk/NIP sudah terdaftar.',
        ]);

        $actor = $request->user();
        $prodi = ProgramStudi::findOrFail($validated['program_studi_id']);

        $adminProdi = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'nomor_induk' => $validated['nomor_induk'] ?: null,
            'role' => UserRole::AdminProdi,
            'program_studi_id' => $prodi->id,
            'no_hp' => $validated['no_hp'] ?? null,
            'password' => Hash::make($validated['password'] ?? 'password'),
        ]);

        AktivitasLog::catat(
            $actor,
            'Tambah Admin Prodi',
            "Admin Utama {$actor->name} menambahkan Admin Prodi baru {$adminProdi->name} untuk Program Studi {$prodi->nama}"
        );

        return redirect()->route('admin.master.admin-prodi.index')
            ->with('success', "Admin Prodi {$adminProdi->name} untuk {$prodi->nama} berhasil ditambahkan.");
    }

    public function update(Request $request, User $adminProdi): RedirectResponse
    {
        if (! $adminProdi->isAdminProdi()) {
            abort(404, 'Data Admin Prodi tidak ditemukan.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($adminProdi->id)],
            'nomor_induk' => ['nullable', 'string', 'max:50', Rule::unique('users', 'nomor_induk')->ignore($adminProdi->id)],
            'program_studi_id' => 'required|exists:program_studi,id',
            'no_hp' => 'nullable|string|max:20',
            'password' => 'nullable|string|min:6',
        ], [
            'email.unique' => 'Email sudah terdaftar pada akun lain.',
            'nomor_induk.unique' => 'Nomor induk sudah terdaftar pada akun lain.',
        ]);

        $actor = $request->user();
        $prodi = ProgramStudi::findOrFail($validated['program_studi_id']);

        $updateData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'nomor_induk' => array_key_exists('nomor_induk', $validated) ? ($validated['nomor_induk'] ?: null) : $adminProdi->nomor_induk,
            'program_studi_id' => $prodi->id,
            'no_hp' => array_key_exists('no_hp', $validated) ? $validated['no_hp'] : $adminProdi->no_hp,
        ];

        if (! empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        $adminProdi->update($updateData);

        AktivitasLog::catat(
            $actor,
            'Ubah Admin Prodi',
            "Admin Utama {$actor->name} memperbarui data Admin Prodi {$adminProdi->name} (Prodi: {$prodi->nama})"
        );

        return redirect()->route('admin.master.admin-prodi.index')
            ->with('success', "Data Admin Prodi {$adminProdi->name} berhasil diperbarui.");
    }

    public function destroy(Request $request, User $adminProdi): RedirectResponse
    {
        if (! $adminProdi->isAdminProdi()) {
            abort(404, 'Data Admin Prodi tidak ditemukan.');
        }

        $actor = $request->user();
        $nama = $adminProdi->name;
        $prodiNama = $adminProdi->programStudi ? $adminProdi->programStudi->nama : 'Tidak terhubung';

        $adminProdi->delete();

        AktivitasLog::catat(
            $actor,
            'Hapus Admin Prodi',
            "Admin Utama {$actor->name} menghapus akun Admin Prodi {$nama} ({$prodiNama})"
        );

        return redirect()->route('admin.master.admin-prodi.index')
            ->with('success', "Akun Admin Prodi {$nama} berhasil dihapus.");
    }
}
