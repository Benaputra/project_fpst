<?php

namespace App\Enums;

enum UserRole: string
{
    case Mahasiswa = 'mahasiswa';
    case Dosen = 'dosen';
    case AdminProdi = 'admin_prodi';
    case AdminUtama = 'admin_utama';

    public function label(): string
    {
        return match ($this) {
            self::Mahasiswa => 'Mahasiswa',
            self::Dosen => 'Dosen',
            self::AdminProdi => 'Admin Program Studi',
            self::AdminUtama => 'Admin Utama',
        };
    }
}
