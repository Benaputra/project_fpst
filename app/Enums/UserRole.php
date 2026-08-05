<?php

namespace App\Enums;

enum UserRole: string
{
    case Mahasiswa = 'mahasiswa';
    case Dosen = 'dosen';
    case AdminProdi = 'admin_prodi';
    case AdminUtama = 'admin_utama';
}
