<?php

namespace Database\Seeders;

use App\Enums\StatusPengajuan;
use App\Enums\UserRole;
use App\Models\AktivitasLog;
use App\Models\Notifikasi;
use App\Models\PengajuanSkripsi;
use App\Models\ProgramStudi;
use App\Models\SeminarSkripsi;
use App\Models\SidangSkripsi;
use App\Models\Surat;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            // 1. Program Studi
            $ti = ProgramStudi::create([
                'nama' => 'Teknik Informatika',
                'kode' => 'TI',
            ]);

            $si = ProgramStudi::create([
                'nama' => 'Sistem Informasi',
                'kode' => 'SI',
            ]);

            // 2. Admin Utama
            $adminUtama = User::create([
                'name' => 'Admin Utama FPST',
                'email' => 'admin.utama@example.test',
                'nomor_induk' => 'ADM001',
                'password' => Hash::make('password'),
                'role' => UserRole::AdminUtama,
                'no_hp' => '081299990001',
            ]);

            // 3. Admin Prodi
            $adminTi = User::create([
                'name' => 'Admin Prodi TI',
                'email' => 'admin.ti@example.test',
                'nomor_induk' => 'ADM-TI-01',
                'password' => Hash::make('password'),
                'role' => UserRole::AdminProdi,
                'program_studi_id' => $ti->id,
                'no_hp' => '081299990002',
            ]);

            $adminSi = User::create([
                'name' => 'Admin Prodi SI',
                'email' => 'admin.si@example.test',
                'nomor_induk' => 'ADM-SI-01',
                'password' => Hash::make('password'),
                'role' => UserRole::AdminProdi,
                'program_studi_id' => $si->id,
                'no_hp' => '081299990003',
            ]);

            // 4. Kaprodi
            $kaprodiTi = User::create([
                'name' => 'Dr. Ratna Wijaya, M.Kom.',
                'email' => 'kaprodi.ti@example.test',
                'nomor_induk' => '1000000001',
                'password' => Hash::make('password'),
                'role' => UserRole::Kaprodi,
                'program_studi_id' => $ti->id,
                'no_hp' => '081200000001',
            ]);

            $kaprodiSi = User::create([
                'name' => 'Dr. Bima Pratama, M.Kom.',
                'email' => 'kaprodi.si@example.test',
                'nomor_induk' => '1000000002',
                'password' => Hash::make('password'),
                'role' => UserRole::Kaprodi,
                'program_studi_id' => $si->id,
                'no_hp' => '081200000002',
            ]);

            // 5. Dosen
            $dosen1 = User::create([
                'name' => 'Dewi Lestari, M.Kom.',
                'email' => 'dosen1@example.test',
                'nomor_induk' => '1000000003',
                'password' => Hash::make('password'),
                'role' => UserRole::Dosen,
                'program_studi_id' => $ti->id,
                'no_hp' => '081200000003',
            ]);

            $dosen2 = User::create([
                'name' => 'Ahmad Fauzi, M.Kom.',
                'email' => 'dosen2@example.test',
                'nomor_induk' => '1000000004',
                'password' => Hash::make('password'),
                'role' => UserRole::Dosen,
                'program_studi_id' => $ti->id,
                'no_hp' => '081200000004',
            ]);

            $dosen3 = User::create([
                'name' => 'Nur Aisyah, M.T.',
                'email' => 'dosen3@example.test',
                'nomor_induk' => '1000000005',
                'password' => Hash::make('password'),
                'role' => UserRole::Dosen,
                'program_studi_id' => $ti->id,
                'no_hp' => '081200000005',
            ]);

            $dosen4 = User::create([
                'name' => 'Fajar Nugroho, M.Cs.',
                'email' => 'dosen4@example.test',
                'nomor_induk' => '1000000006',
                'password' => Hash::make('password'),
                'role' => UserRole::Dosen,
                'program_studi_id' => $ti->id,
                'no_hp' => '081200000006',
            ]);

            $dosen5 = User::create([
                'name' => 'Maya Putri, M.Kom.',
                'email' => 'dosen5@example.test',
                'nomor_induk' => '1000000007',
                'password' => Hash::make('password'),
                'role' => UserRole::Dosen,
                'program_studi_id' => $si->id,
                'no_hp' => '081200000007',
            ]);

            // 6. Mahasiswa & Skripsi Uji Coba

            // Mahasiswa 1: Baru mengajukan judul (Status: diajukan)
            $mhs1 = User::create([
                'name' => 'Andi Saputra',
                'email' => 'mahasiswa1@example.test',
                'nomor_induk' => '221000000001',
                'password' => Hash::make('password'),
                'role' => UserRole::Mahasiswa,
                'program_studi_id' => $ti->id,
                'no_hp' => '081300000001',
            ]);
            PengajuanSkripsi::create([
                'mahasiswa_id' => $mhs1->id,
                'program_studi_id' => $ti->id,
                'judul' => 'Rancang Bangun Sistem Klasifikasi Citra Medis Menggunakan Convolutional Neural Network',
                'abstrak' => 'Penelitian ini bertujuan mengimplementasikan arsitektur CNN untuk mendeteksi anomali pada citra rontgen secara otomatis.',
                'status' => StatusPengajuan::Diajukan,
            ]);

            // Mahasiswa 2: SK Bimbingan sudah terbit (Status: selesai) -> Siap ajukan seminar
            $mhs2 = User::create([
                'name' => 'Siti Rahma',
                'email' => 'mahasiswa2@example.test',
                'nomor_induk' => '221000000002',
                'password' => Hash::make('password'),
                'role' => UserRole::Mahasiswa,
                'program_studi_id' => $ti->id,
                'no_hp' => '081300000002',
            ]);
            $skripsi2 = PengajuanSkripsi::create([
                'mahasiswa_id' => $mhs2->id,
                'program_studi_id' => $ti->id,
                'judul' => 'Pengembangan Aplikasi Pemantauan Emisi Karbon Berbasis Internet of Things',
                'abstrak' => 'Sistem IoT real-time untuk memantau jejak karbon pabrik dengan visualisasi dashboard berbasis web.',
                'pembimbing_1_id' => $dosen1->id,
                'pembimbing_2_id' => $dosen2->id,
                'nomor_sk_bimbingan' => 'SK/001/FPST/TI/2026',
                'tgl_sk_bimbingan' => now()->subMonths(2),
                'status' => StatusPengajuan::Selesai,
            ]);
            Surat::create([
                'nomor_surat' => 'SK/001/FPST/TI/2026',
                'jenis_surat' => 'sk_bimbingan',
                'nama_surat' => 'SK Pembimbing Skripsi - Siti Rahma',
                'pengajuan_skripsi_id' => $skripsi2->id,
                'program_studi_id' => $ti->id,
                'tgl_surat' => now()->subMonths(2),
                'versi' => 1,
                'status' => 'aktif',
                'diterbitkan_oleh' => $adminTi->id,
                'keterangan' => 'Penerbitan awal SK Pembimbing Skripsi',
            ]);

            // Mahasiswa 3: Seminar Selesai (Status: selesai) -> Siap ajukan sidang
            $mhs3 = User::create([
                'name' => 'Rizky Maulana',
                'email' => 'mahasiswa3@example.test',
                'nomor_induk' => '222000000001',
                'password' => Hash::make('password'),
                'role' => UserRole::Mahasiswa,
                'program_studi_id' => $si->id,
                'no_hp' => '081300000003',
            ]);
            $skripsi3 = PengajuanSkripsi::create([
                'mahasiswa_id' => $mhs3->id,
                'program_studi_id' => $si->id,
                'judul' => 'Audit Tata Kelola Teknologi Informasi Menggunakan Framework COBIT 2019 pada Diskominfo',
                'abstrak' => 'Analisis kapabilitas tata kelola sistem informasi pemerintahan daerah untuk menghasilkan rekomendasi perbaikan proses bisnis.',
                'pembimbing_1_id' => $kaprodiSi->id,
                'pembimbing_2_id' => $dosen5->id,
                'nomor_sk_bimbingan' => 'SK/002/FPST/SI/2026',
                'tgl_sk_bimbingan' => now()->subMonths(4),
                'status' => StatusPengajuan::Selesai,
            ]);
            Surat::create([
                'nomor_surat' => 'SK/002/FPST/SI/2026',
                'jenis_surat' => 'sk_bimbingan',
                'nama_surat' => 'SK Pembimbing Skripsi - Rizky Maulana',
                'pengajuan_skripsi_id' => $skripsi3->id,
                'program_studi_id' => $si->id,
                'tgl_surat' => now()->subMonths(4),
                'versi' => 1,
                'status' => 'aktif',
                'diterbitkan_oleh' => $adminSi->id,
                'keterangan' => 'Penerbitan awal SK Pembimbing Skripsi',
            ]);

            $seminar3 = SeminarSkripsi::create([
                'pengajuan_skripsi_id' => $skripsi3->id,
                'penguji_seminar_id' => $dosen1->id,
                'tgl_seminar' => now()->subMonth(),
                'jam_seminar' => '09:00',
                'ruangan' => 'Ruang Seminar 201',
                'nomor_undangan_seminar' => 'UND/012/FPST/SI/2026',
                'nomor_sk_seminar' => 'SK-SEM/005/FPST/2026',
                'nilai_seminar' => 86.50,
                'status' => StatusPengajuan::Selesai,
                'catatan' => 'Lanjut ke tahap penelitian lapangan dan perbaikan instrumen kuesioner.',
            ]);
            Surat::create([
                'nomor_surat' => 'UND/012/FPST/SI/2026',
                'jenis_surat' => 'undangan_seminar',
                'nama_surat' => 'Surat Undangan Seminar - Rizky Maulana',
                'pengajuan_skripsi_id' => $skripsi3->id,
                'seminar_skripsi_id' => $seminar3->id,
                'program_studi_id' => $si->id,
                'tgl_surat' => now()->subMonth(),
                'versi' => 1,
                'status' => 'aktif',
                'diterbitkan_oleh' => $adminSi->id,
                'keterangan' => 'Surat Undangan Seminar Skripsi',
            ]);
            Surat::create([
                'nomor_surat' => 'SK-SEM/005/FPST/2026',
                'jenis_surat' => 'sk_seminar',
                'nama_surat' => 'SK Penguji Seminar - Rizky Maulana',
                'pengajuan_skripsi_id' => $skripsi3->id,
                'seminar_skripsi_id' => $seminar3->id,
                'program_studi_id' => $si->id,
                'tgl_surat' => now()->subMonth(),
                'versi' => 1,
                'status' => 'aktif',
                'diterbitkan_oleh' => $adminSi->id,
                'keterangan' => 'Penerbitan SK Penguji Seminar',
            ]);

            // Mahasiswa 4: Belum mengajukan apa-apa
            User::create([
                'name' => 'Nadia Permata',
                'email' => 'mahasiswa4@example.test',
                'nomor_induk' => '221000000003',
                'password' => Hash::make('password'),
                'role' => UserRole::Mahasiswa,
                'program_studi_id' => $ti->id,
                'no_hp' => '081300000004',
            ]);

            // 7. Seed Log Aktivitas Awal
            AktivitasLog::create([
                'user_id' => $mhs1->id,
                'aksi' => 'Pengajuan Judul Skripsi',
                'deskripsi' => "Mahasiswa Andi Saputra (221000000001) mengajukan judul skripsi: 'Rancang Bangun Sistem Klasifikasi Citra Medis Menggunakan Convolutional Neural Network'",
                'ip_address' => '127.0.0.1',
                'created_at' => now()->subDays(3),
            ]);

            AktivitasLog::create([
                'user_id' => $kaprodiTi->id,
                'aksi' => 'Penetapan Dosen Pembimbing',
                'deskripsi' => "Kaprodi Dr. Ratna Wijaya menyetujui judul & menetapkan Pembimbing untuk Siti Rahma (221000000002)",
                'ip_address' => '127.0.0.1',
                'created_at' => now()->subDays(2),
            ]);

            AktivitasLog::create([
                'user_id' => $adminTi->id,
                'aksi' => 'Penerbitan SK Bimbingan',
                'deskripsi' => "Admin menerbitkan SK Bimbingan No. SK/001/FPST/TI/2026 untuk Siti Rahma (221000000002)",
                'ip_address' => '127.0.0.1',
                'created_at' => now()->subDays(1),
            ]);

            AktivitasLog::create([
                'user_id' => $adminSi->id,
                'aksi' => 'Finalisasi Nilai Seminar',
                'deskripsi' => "Admin menginput nilai seminar (86.50) untuk mahasiswa Rizky Maulana (222000000001)",
                'ip_address' => '127.0.0.1',
                'created_at' => now()->subHours(6),
            ]);

            // 8. Seed Notifikasi Awal
            Notifikasi::create([
                'user_id' => $mhs2->id,
                'judul' => 'SK Bimbingan Resmi Diterbitkan',
                'pesan' => 'Surat Keputusan (SK) Bimbingan Anda No: SK/001/FPST/TI/2026 telah resmi diterbitkan. Anda kini dapat memulai bimbingan dan mendaftar seminar.',
                'link' => route('mahasiswa.skripsi.index'),
                'dibaca' => false,
                'created_at' => now()->subDays(1),
            ]);

            Notifikasi::create([
                'user_id' => $dosen1->id,
                'judul' => 'Penugasan Pembimbing Utama (1)',
                'pesan' => 'Anda ditetapkan sebagai Pembimbing Utama untuk mahasiswa Siti Rahma (221000000002) dengan judul "Pengembangan Aplikasi Pemantauan Emisi Karbon Berbasis Internet of Things".',
                'link' => route('dosen.bimbingan.index'),
                'dibaca' => false,
                'created_at' => now()->subDays(2),
            ]);

            Notifikasi::create([
                'user_id' => $dosen1->id,
                'judul' => 'Penugasan Dosen Penguji Seminar',
                'pesan' => 'Anda ditugaskan sebagai Dosen Penguji Seminar untuk mahasiswa Rizky Maulana (222000000001).',
                'link' => route('dosen.bimbingan.index'),
                'dibaca' => true,
                'dibaca_at' => now()->subHours(10),
                'created_at' => now()->subDays(3),
            ]);

            Notifikasi::create([
                'user_id' => $mhs3->id,
                'judul' => 'Hasil & Nilai Seminar Telah Keluar',
                'pesan' => 'Selamat! Nilai seminar Anda telah diinput: 86.50. Anda telah dinyatakan LULUS seminar dan dapat melanjutkan pendaftaran Sidang Skripsi.',
                'link' => route('mahasiswa.sidang.index'),
                'dibaca' => false,
                'created_at' => now()->subHours(6),
            ]);
        });
    }
}
