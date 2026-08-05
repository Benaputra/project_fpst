<?php

namespace App\Enums;

enum StatusPengajuanJudul: string
{
    case Diajukan = 'diajukan';
    case Diverifikasi = 'diverifikasi';
    case Ditolak = 'ditolak';

    public function label(): string
    {
        return match ($this) {
            self::Diajukan => 'Menunggu verifikasi',
            self::Diverifikasi => 'Terverifikasi',
            self::Ditolak => 'Perlu diperbaiki',
        };
    }
}
