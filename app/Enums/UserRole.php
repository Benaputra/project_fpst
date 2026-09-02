<?php

namespace App\Enums;

enum UserRole: string
{
    case Mahasiswa = 'mahasiswa';
    case Dosen = 'dosen';
    case Kaprodi = 'kaprodi';
    case AdminProdi = 'admin_prodi';
    case AdminUtama = 'admin_utama';

    public function label(): string
    {
        return match ($this) {
            self::Mahasiswa => 'Mahasiswa',
            self::Dosen => 'Dosen',
            self::Kaprodi => 'Ketua Program Studi',
            self::AdminProdi => 'Admin Program Studi',
            self::AdminUtama => 'Admin Utama',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Mahasiswa => 'badge--success',
            self::Dosen => 'badge--primary',
            self::Kaprodi => 'badge--purple',
            self::AdminProdi => 'badge--warning',
            self::AdminUtama => 'badge--danger',
        };
    }
}
