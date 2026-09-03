<?php

namespace App\Http\Controllers\Admin\Master;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\AktivitasLog;
use App\Models\ProgramStudi;
use App\Models\User;
use App\Services\MahasiswaCsvImporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MahasiswaController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->input('search');
        $prodiFilter = $request->input('prodi_id');

        $query = User::where('role', UserRole::Mahasiswa)
            ->with(['programStudi', 'pengajuanSkripsi']);

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

        $daftarMahasiswa = $query->latest()->paginate(15)->withQueryString();
        $daftarProdi = ProgramStudi::orderBy('nama')->get();
        $totalMahasiswa = User::where('role', UserRole::Mahasiswa)->count();

        return view('admin.master.mahasiswa.index', compact(
            'daftarMahasiswa',
            'daftarProdi',
            'search',
            'prodiFilter',
            'totalMahasiswa'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'nomor_induk' => 'required|string|max:50|unique:users,nomor_induk',
            'email' => 'required|email|max:255|unique:users,email',
            'program_studi_id' => 'required|exists:program_studi,id',
            'no_hp' => 'nullable|string|max:20',
            'password' => 'nullable|string|min:6',
        ], [
            'nomor_induk.unique' => 'NIM sudah terdaftar.',
            'email.unique' => 'Email sudah terdaftar.',
        ]);

        $user = $request->user();

        $mahasiswa = User::create([
            'name' => $validated['name'],
            'nomor_induk' => $validated['nomor_induk'],
            'email' => $validated['email'],
            'program_studi_id' => $validated['program_studi_id'],
            'no_hp' => $validated['no_hp'] ?? null,
            'password' => Hash::make($validated['password'] ?? 'password'),
            'role' => UserRole::Mahasiswa,
        ]);

        AktivitasLog::catat(
            $user,
            'Tambah Data Mahasiswa',
            "Admin Utama {$user->name} menambahkan data mahasiswa {$mahasiswa->name} (NIM: {$mahasiswa->nomor_induk})"
        );

        return redirect()->route('admin.master.mahasiswa.index')
            ->with('success', "Mahasiswa {$mahasiswa->name} ({$mahasiswa->nomor_induk}) berhasil ditambahkan.");
    }

    public function update(Request $request, User $mahasiswa): RedirectResponse
    {
        if (! $mahasiswa->isMahasiswa()) {
            abort(404, 'Data mahasiswa tidak ditemukan.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'nomor_induk' => ['required', 'string', 'max:50', Rule::unique('users', 'nomor_induk')->ignore($mahasiswa->id)],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($mahasiswa->id)],
            'program_studi_id' => 'required|exists:program_studi,id',
            'no_hp' => 'nullable|string|max:20',
            'password' => 'nullable|string|min:6',
        ], [
            'nomor_induk.unique' => 'NIM sudah terdaftar pada akun lain.',
            'email.unique' => 'Email sudah terdaftar pada akun lain.',
        ]);

        $updateData = [
            'name' => $validated['name'],
            'nomor_induk' => $validated['nomor_induk'],
            'email' => $validated['email'],
            'program_studi_id' => $validated['program_studi_id'],
            'no_hp' => $validated['no_hp'] ?? null,
        ];

        if (! empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        $mahasiswa->update($updateData);

        $user = $request->user();
        AktivitasLog::catat(
            $user,
            'Ubah Data Mahasiswa',
            "Admin Utama {$user->name} memperbarui data mahasiswa {$mahasiswa->name} (NIM: {$mahasiswa->nomor_induk})"
        );

        return redirect()->route('admin.master.mahasiswa.index')
            ->with('success', "Data mahasiswa {$mahasiswa->name} berhasil diperbarui.");
    }

    public function destroy(Request $request, User $mahasiswa): RedirectResponse
    {
        if (! $mahasiswa->isMahasiswa()) {
            abort(404, 'Data mahasiswa tidak ditemukan.');
        }

        if ($mahasiswa->pengajuanSkripsi()->exists()) {
            return back()->with('error', "Mahasiswa {$mahasiswa->name} ({$mahasiswa->nomor_induk}) tidak dapat dihapus karena memiliki riwayat pengajuan skripsi/akademik aktif.");
        }

        $nama = $mahasiswa->name;
        $nim = $mahasiswa->nomor_induk;
        $mahasiswa->delete();

        $user = $request->user();
        AktivitasLog::catat(
            $user,
            'Hapus Data Mahasiswa',
            "Admin Utama {$user->name} menghapus akun mahasiswa {$nama} (NIM: {$nim})"
        );

        return redirect()->route('admin.master.mahasiswa.index')
            ->with('success', "Mahasiswa {$nama} ({$nim}) berhasil dihapus.");
    }

    public function importCsv(Request $request, MahasiswaCsvImporter $importer): RedirectResponse
    {
        $request->validate([
            'file_csv' => 'required|file|max:5120',
        ], [
            'file_csv.required' => 'Silakan pilih file CSV terlebih dahulu.',
            'file_csv.max' => 'Ukuran file CSV maksimal 5 MB.',
        ]);

        $file = $request->file('file_csv');
        $ext = strtolower($file->getClientOriginalExtension());
        if (! in_array($ext, ['csv', 'txt'])) {
            return back()->with('error', 'Format file harus berupa .csv atau text berbasis CSV.');
        }

        $result = $importer->import($file);
        $user = $request->user();

        AktivitasLog::catat(
            $user,
            'Batch Import Mahasiswa CSV',
            "Admin Utama {$user->name} mengimpor {$result['imported']} mahasiswa via CSV (dilewati: {$result['skipped']})"
        );

        if ($result['imported'] > 0) {
            $msg = "Batch import selesai: {$result['imported']} mahasiswa berhasil ditambahkan.";
            if ($result['skipped'] > 0) {
                $msg .= " ({$result['skipped']} baris dilewati karena duplikat atau data tidak lengkap).";
            }

            return redirect()->route('admin.master.mahasiswa.index')
                ->with('success', $msg)
                ->with('csv_errors', $result['errors']);
        }

        return redirect()->route('admin.master.mahasiswa.index')
            ->with('error', 'Tidak ada data mahasiswa yang berhasil diimpor.')
            ->with('csv_errors', $result['errors']);
    }

    public function downloadTemplate(): StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="template_import_mahasiswa.csv"',
        ];

        return response()->stream(function () {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['nama', 'nim', 'email', 'kode_prodi', 'no_hp', 'password']);
            fputcsv($handle, ['Budi Santoso', '221000000088', 'budi.santoso@example.test', 'TI', '081234567890', 'password']);
            fputcsv($handle, ['Siti Aminah', '222000000088', 'siti.aminah@example.test', 'SI', '081234567891', 'password']);
            fclose($handle);
        }, 200, $headers);
    }
}
