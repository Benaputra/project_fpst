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
use Illuminate\Validation\Rules\Enum;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->input('search');
        $roleFilter = $request->input('role');
        $prodiFilter = $request->input('prodi_id');

        $query = User::with('programStudi');

        if ($roleFilter) {
            $query->where('role', $roleFilter);
        }

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

        $daftarUser = $query->latest()->paginate(20)->withQueryString();
        $daftarProdi = ProgramStudi::orderBy('nama')->get();
        $totalUser = User::count();
        $roleCounts = [
            'mahasiswa' => User::where('role', UserRole::Mahasiswa)->count(),
            'dosen' => User::where('role', UserRole::Dosen)->count(),
            'kaprodi' => User::where('role', UserRole::Kaprodi)->count(),
            'admin_prodi' => User::where('role', UserRole::AdminProdi)->count(),
            'admin_utama' => User::where('role', UserRole::AdminUtama)->count(),
        ];

        return view('admin.master.user.index', compact(
            'daftarUser',
            'daftarProdi',
            'search',
            'roleFilter',
            'prodiFilter',
            'totalUser',
            'roleCounts'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'nomor_induk' => 'nullable|string|max:50|unique:users,nomor_induk',
            'role' => ['required', new Enum(UserRole::class)],
            'program_studi_id' => 'nullable|exists:program_studi,id',
            'no_hp' => 'nullable|string|max:20',
            'password' => 'nullable|string|min:6',
        ], [
            'email.unique' => 'Email sudah terdaftar.',
            'nomor_induk.unique' => 'Nomor induk (NIM/NIDN/NIP) sudah terdaftar.',
        ]);

        $actor = $request->user();
        $roleEnum = UserRole::from($validated['role']);

        $newUser = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'nomor_induk' => $validated['nomor_induk'] ?: null,
            'role' => $roleEnum,
            'program_studi_id' => $validated['program_studi_id'] ?? null,
            'no_hp' => $validated['no_hp'] ?? null,
            'password' => Hash::make($validated['password'] ?? 'password'),
        ]);

        AktivitasLog::catat(
            $actor,
            'Tambah User Baru',
            "Admin Utama {$actor->name} membuat pengguna baru {$newUser->name} ({$newUser->email}) dengan role {$roleEnum->label()}"
        );

        return redirect()->route('admin.master.user.index')
            ->with('success', "Pengguna {$newUser->name} ({$roleEnum->label()}) berhasil ditambahkan.");
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $actor = $request->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'nomor_induk' => ['nullable', 'string', 'max:50', Rule::unique('users', 'nomor_induk')->ignore($user->id)],
            'role' => ['required', new Enum(UserRole::class)],
            'program_studi_id' => 'nullable|exists:program_studi,id',
            'no_hp' => 'nullable|string|max:20',
            'password' => 'nullable|string|min:6',
        ], [
            'email.unique' => 'Email sudah terdaftar pada akun lain.',
            'nomor_induk.unique' => 'Nomor induk sudah terdaftar pada akun lain.',
        ]);

        $newRole = UserRole::from($validated['role']);
        $oldRole = $user->role;

        // Proteksi: jangan izinkan user menurunkan role akun sendiri yang sedang login
        if ($actor->id === $user->id && $newRole !== UserRole::AdminUtama) {
            return back()->with('error', 'Anda tidak dapat mengubah atau menurunkan role akun Admin Utama Anda sendiri yang sedang aktif.');
        }

        $updateData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'nomor_induk' => array_key_exists('nomor_induk', $validated) ? ($validated['nomor_induk'] ?: null) : $user->nomor_induk,
            'role' => $newRole,
            'program_studi_id' => array_key_exists('program_studi_id', $validated) ? $validated['program_studi_id'] : $user->program_studi_id,
            'no_hp' => array_key_exists('no_hp', $validated) ? $validated['no_hp'] : $user->no_hp,
        ];

        if (! empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        $user->update($updateData);

        if ($oldRole !== $newRole) {
            AktivitasLog::catat(
                $actor,
                'Pergantian Role User',
                "Admin Utama {$actor->name} mengubah role {$user->name} ({$user->email}) dari {$oldRole->label()} menjadi {$newRole->label()}"
            );
        } else {
            AktivitasLog::catat(
                $actor,
                'Ubah Data User',
                "Admin Utama {$actor->name} memperbarui profil pengguna {$user->name} ({$user->email})"
            );
        }

        return redirect()->route('admin.master.user.index')
            ->with('success', "Data dan role pengguna {$user->name} berhasil diperbarui.");
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $actor = $request->user();

        if ($actor->id === $user->id) {
            return back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        // Cek ketergantungan relasi
        if ($user->pengajuanSkripsi()->exists()) {
            return back()->with('error', "User {$user->name} tidak dapat dihapus karena memiliki riwayat pengajuan skripsi aktif.");
        }

        $isBimbingan = PengajuanSkripsi::where('pembimbing_1_id', $user->id)
            ->orWhere('pembimbing_2_id', $user->id)
            ->exists();
        $isPengujiSeminar = SeminarSkripsi::where('penguji_seminar_id', $user->id)->exists();
        $isPengujiSidang = SidangSkripsi::where('penguji_1_id', $user->id)
            ->orWhere('penguji_2_id', $user->id)
            ->exists();

        if ($isBimbingan || $isPengujiSeminar || $isPengujiSidang) {
            return back()->with('error', "User {$user->name} tidak dapat dihapus karena tercatat sebagai Pembimbing atau Penguji skripsi.");
        }

        $name = $user->name;
        $roleLabel = $user->role->label();
        $user->delete();

        AktivitasLog::catat(
            $actor,
            'Hapus User',
            "Admin Utama {$actor->name} menghapus akun {$name} ({$roleLabel})"
        );

        return redirect()->route('admin.master.user.index')
            ->with('success', "Pengguna {$name} ({$roleLabel}) berhasil dihapus.");
    }
}
