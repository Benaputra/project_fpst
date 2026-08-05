<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Dosen;
use App\Models\Mahasiswa;
use App\Models\ProgramStudi;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleUserSeeder extends Seeder
{
    /**
     * Seed akun lokal yang stabil untuk pemeriksaan UI dan alur berbasis role.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $informatika = ProgramStudi::query()->updateOrCreate(
                ['nama' => 'Teknik Informatika'],
                ['ttd_ketua_prodi' => null],
            );
            $sistemInformasi = ProgramStudi::query()->updateOrCreate(
                ['nama' => 'Sistem Informasi'],
                ['ttd_ketua_prodi' => null],
            );

            $dosen = [
                $this->buatDosen(
                    'kaprodi.ti@example.test',
                    'Dr. Ratna Wijaya (Kaprodi TI)',
                    '1000000001',
                    '9000000000000001',
                    $informatika,
                ),
                $this->buatDosen(
                    'kaprodi.si@example.test',
                    'Dr. Bima Pratama (Kaprodi SI)',
                    '1000000002',
                    '9000000000000002',
                    $sistemInformasi,
                ),
                $this->buatDosen(
                    'dosen@example.test',
                    'Dewi Lestari, M.Kom.',
                    '1000000003',
                    '9000000000000003',
                    $informatika,
                ),
                $this->buatDosen(
                    'dosen.ti2@example.test',
                    'Ahmad Fauzi, M.Kom.',
                    '1000000004',
                    '9000000000000004',
                    $informatika,
                ),
                $this->buatDosen(
                    'dosen.ti3@example.test',
                    'Nur Aisyah, M.T.',
                    '1000000005',
                    '9000000000000005',
                    $informatika,
                ),
                $this->buatDosen(
                    'dosen.ti4@example.test',
                    'Fajar Nugroho, M.Cs.',
                    '1000000006',
                    '9000000000000006',
                    $informatika,
                ),
                $this->buatDosen(
                    'dosen.si2@example.test',
                    'Maya Putri, M.Kom.',
                    '1000000007',
                    '9000000000000007',
                    $sistemInformasi,
                ),
                $this->buatDosen(
                    'dosen.si3@example.test',
                    'Arif Hidayat, M.S.I.',
                    '1000000008',
                    '9000000000000008',
                    $sistemInformasi,
                ),
            ];

            $informatika->update(['ketua_prodi_id' => $dosen[0]->nidn]);
            $sistemInformasi->update(['ketua_prodi_id' => $dosen[1]->nidn]);

            $this->buatMahasiswa(
                'mahasiswa.ti1@example.test',
                'Andi Saputra',
                '221000000001',
                $informatika,
                $dosen[2],
            );
            $this->buatMahasiswa(
                'mahasiswa.ti2@example.test',
                'Siti Rahma',
                '221000000002',
                $informatika,
                $dosen[0],
            );
            $this->buatMahasiswa(
                'mahasiswa.si@example.test',
                'Rizky Maulana',
                '222000000001',
                $sistemInformasi,
                $dosen[1],
            );
            $this->buatMahasiswa(
                'mahasiswa.ti3@example.test',
                'Nadia Permata',
                '221000000003',
                $informatika,
                $dosen[3],
            );
            $this->buatMahasiswa(
                'mahasiswa.ti4@example.test',
                'Bagas Ramadhan',
                '221000000004',
                $informatika,
                $dosen[4],
            );
            $this->buatMahasiswa(
                'mahasiswa.ti5@example.test',
                'Citra Anggraini',
                '221000000005',
                $informatika,
                $dosen[5],
            );
            $this->buatMahasiswa(
                'mahasiswa.si2@example.test',
                'Dimas Setiawan',
                '222000000002',
                $sistemInformasi,
                $dosen[6],
            );
            $this->buatMahasiswa(
                'mahasiswa.si3@example.test',
                'Farah Nabila',
                '222000000003',
                $sistemInformasi,
                $dosen[7],
            );

            $this->buatAdminProdi(
                'admin.prodi.ti@example.test',
                'Admin Prodi TI',
                [$informatika->getKey()],
            );
            $this->buatAdminProdi(
                'admin.prodi.si@example.test',
                'Admin Prodi SI',
                [$sistemInformasi->getKey()],
            );
            $this->buatAdminProdi(
                'admin.prodi.gabungan@example.test',
                'Admin Prodi Gabungan',
                [$informatika->getKey(), $sistemInformasi->getKey()],
            );

            foreach ([
                ['admin.utama@example.test', 'Admin Utama'],
                ['admin.utama2@example.test', 'Admin Utama 2'],
                ['admin.utama3@example.test', 'Admin Utama 3'],
            ] as [$email, $nama]) {
                $this->buatUser($email, $nama, UserRole::AdminUtama);
            }
        });

        $this->command?->info('22 akun uji dibuat/diperbarui. Kata sandi seluruh akun: password');
    }

    private function buatUser(string $email, string $nama, UserRole $role): User
    {
        $user = User::query()->firstOrNew(['email' => $email]);

        $user->forceFill([
            'name' => $nama,
            'email' => $email,
            'email_verified_at' => now(),
            'password' => 'password',
            'role' => $role,
        ])->save();

        return $user;
    }

    private function buatDosen(
        string $email,
        string $nama,
        string $nidn,
        string $nuptk,
        ProgramStudi $programStudi,
    ): Dosen {
        $user = $this->buatUser($email, $nama, UserRole::Dosen);

        return Dosen::query()->updateOrCreate(
            ['nidn' => $nidn],
            [
                'nama' => $nama,
                'nuptk' => $nuptk,
                'program_studi_id' => $programStudi->getKey(),
                'no_hp' => '081200000'.$nidn[-1],
                'tempat_lahir' => 'Jakarta',
                'tanggal_lahir' => '1985-01-01',
                'jabatan_fungsional' => 'Lektor',
                'user_id' => $user->getKey(),
            ],
        );
    }

    private function buatMahasiswa(
        string $email,
        string $nama,
        string $nim,
        ProgramStudi $programStudi,
        Dosen $pembimbingAkademik,
    ): Mahasiswa {
        $user = $this->buatUser($email, $nama, UserRole::Mahasiswa);

        return Mahasiswa::query()->updateOrCreate(
            ['nim' => $nim],
            [
                'nama' => $nama,
                'program_studi_id' => $programStudi->getKey(),
                'no_hp' => '081300000'.$nim[-1],
                'tempat_lahir' => 'Bandung',
                'tanggal_lahir' => '2003-01-01',
                'angkatan' => 2022,
                'pembimbing_akademik_id' => $pembimbingAkademik->nidn,
                'user_id' => $user->getKey(),
            ],
        );
    }

    /** @param list<int> $programStudiIds */
    private function buatAdminProdi(string $email, string $nama, array $programStudiIds): User
    {
        $user = $this->buatUser($email, $nama, UserRole::AdminProdi);
        $user->programStudiAdministrasi()->sync($programStudiIds);

        return $user;
    }
}
