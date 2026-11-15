<?php

namespace App\Enums;

enum PeranAudiensSeminar: string
{
    case Peserta = 'peserta';
    case Penyanggah = 'penyanggah';

    public function label(): string
    {
        return match ($this) {
            self::Peserta => 'Peserta / Penonton',
            self::Penyanggah => 'Penyanggah Utama',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Peserta => 'badge-diproses',
            self::Penyanggah => 'badge-diajukan',
        };
    }
}
