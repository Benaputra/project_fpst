<?php

namespace App\Http\Controllers\Admin\Master;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\AktivitasLog;
use App\Models\PengajuanSkripsi;
use App\Models\ProgramStudi;
use App\Models\SeminarSkripsi;
use App\Models\SidangSkripsi;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DosenController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->input('search');
        $prodiFilter = $request->input('prodi_id');
        $roleFilter = $request->input('role');

        $query = User::whereIn('role', [UserRole::Dosen, UserRole::Kaprodi])
            ->with(['programStudi'])
            ->withCount(['bimbinganPertama', 'bimbinganKedua', 'mengujiSeminar', 'mengujiSidangPertama', 'mengujiSidangKedua']);

        if ($prodiFilter) {
            $query->where('program_studi_id', $prodiFilter);
        }

        if ($roleFilter && in_array($roleFilter, ['dosen', 'kaprodi'])) {
            $query->where('role', $roleFilter);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('nomor_induk', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $daftarDosen = $query->latest()->paginate(15)->withQueryString();
        $daftarProdi = ProgramStudi::orderBy('nama')->get();
        $totalDosen = User::whereIn('role', [UserRole::Dosen, UserRole::Kaprodi])->count();
        $totalKaprodi = User::where('role', UserRole::Kaprodi)->count();

        return view('admin.master.dosen.index', compact(
            'daftarDosen',
            'daftarProdi',
            'search',
            'prodiFilter',
            'roleFilter',
            'totalDosen',
            'totalKaprodi'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'nomor_induk' => 'required|string|max:50|unique:users,nomor_induk',
            'email' => 'required|email|max:255|unique:users,email',
            'program_studi_id' => 'required|exists:program_studi,id',
            'role' => ['required', Rule::in(['dosen', 'kaprodi'])],
            'no_hp' => 'nullable|string|max:20',
            'password' => 'nullable|string|min:6',
        ], [
            'nomor_induk.unique' => 'NIDN/NIP sudah terdaftar.',
            'email.unique' => 'Email sudah terdaftar.',
        ]);

        $user = $request->user();

        $dosen = User::create([
            'name' => $validated['name'],
            'nomor_induk' => $validated['nomor_induk'],
            'email' => $validated['email'],
            'program_studi_id' => $validated['program_studi_id'],
            'role' => UserRole::from($validated['role']),
            'no_hp' => $validated['no_hp'] ?? null,
            'password' => Hash::make($validated['password'] ?? 'password'),
        ]);

        AktivitasLog::catat(
            $user,
            'Tambah Data Dosen',
            "Admin Utama {$user->name} menambahkan data dosen {$dosen->name} (NIDN: {$dosen->nomor_induk}, Role: {$dosen->role->label()})"
        );

        return redirect()->route('admin.master.dosen.index')
            ->with('success', "Dosen {$dosen->name} berhasil ditambahkan.");
    }

    public function update(Request $request, User $dosen): RedirectResponse
    {
        if (! $dosen->isDosen()) {
            abort(404, 'Data dosen tidak ditemukan.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'nomor_induk' => ['required', 'string', 'max:50', Rule::unique('users', 'nomor_induk')->ignore($dosen->id)],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($dosen->id)],
            'program_studi_id' => 'required|exists:program_studi,id',
            'role' => ['required', Rule::in(['dosen', 'kaprodi'])],
            'no_hp' => 'nullable|string|max:20',
            'password' => 'nullable|string|min:6',
        ], [
            'nomor_induk.unique' => 'NIDN/NIP sudah terdaftar pada akun lain.',
            'email.unique' => 'Email sudah terdaftar pada akun lain.',
        ]);

        $updateData = [
            'name' => $validated['name'],
            'nomor_induk' => $validated['nomor_induk'],
            'email' => $validated['email'],
            'program_studi_id' => $validated['program_studi_id'],
            'role' => UserRole::from($validated['role']),
            'no_hp' => $validated['no_hp'] ?? null,
        ];

        if (! empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        $dosen->update($updateData);

        $user = $request->user();
        AktivitasLog::catat(
            $user,
            'Ubah Data Dosen',
            "Admin Utama {$user->name} memperbarui data dosen {$dosen->name} (NIDN: {$dosen->nomor_induk})"
        );

        return redirect()->route('admin.master.dosen.index')
            ->with('success', "Data dosen {$dosen->name} berhasil diperbarui.");
    }

    public function destroy(Request $request, User $dosen): RedirectResponse
    {
        if (! $dosen->isDosen()) {
            abort(404, 'Data dosen tidak ditemukan.');
        }

        $isPembimbing = PengajuanSkripsi::where('pembimbing_1_id', $dosen->id)
            ->orWhere('pembimbing_2_id', $dosen->id)
            ->exists();

        $isPengujiSeminar = SeminarSkripsi::where('penguji_seminar_id', $dosen->id)->exists();

        $isPengujiSidang = SidangSkripsi::where('penguji_1_id', $dosen->id)
            ->orWhere('penguji_2_id', $dosen->id)
            ->exists();

        if ($isPembimbing || $isPengujiSeminar || $isPengujiSidang) {
            return back()->with('error', "Dosen {$dosen->name} tidak dapat dihapus karena masih tercatat sebagai Pembimbing atau Penguji skripsi.");
        }

        $nama = $dosen->name;
        $nidn = $dosen->nomor_induk;
        $dosen->delete();

        $user = $request->user();
        AktivitasLog::catat(
            $user,
            'Hapus Data Dosen',
            "Admin Utama {$user->name} menghapus data dosen {$nama} (NIDN: {$nidn})"
        );

        return redirect()->route('admin.master.dosen.index')
            ->with('success', "Dosen {$nama} ({$nidn}) berhasil dihapus.");
    }
}
