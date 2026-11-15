<?php

namespace App\Enums;

enum StatusAudiensSeminar: string
{
    case Terdaftar = 'terdaftar';
    case Hadir = 'hadir';
    case TidakHadir = 'tidak_hadir';
    case Dibatalkan = 'dibatalkan';

    public function label(): string
    {
        return match ($this) {
            self::Terdaftar => 'Terdaftar (Menunggu Hadir)',
            self::Hadir => 'Hadir (Tervalidasi)',
            self::TidakHadir => 'Tidak Hadir',
            self::Dibatalkan => 'Dibatalkan',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Terdaftar => 'badge-diajukan',
            self::Hadir => 'badge-selesai',
            self::TidakHadir => 'badge-ditolak',
            self::Dibatalkan => 'badge--secondary',
        };
    }
}
