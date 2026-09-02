<?php

namespace App\Enums;

enum StatusPengajuan: string
{
    case Diajukan = 'diajukan';
    case Diproses = 'diproses';
    case Selesai = 'selesai';
    case Ditolak = 'ditolak';

    public function label(): string
    {
        return match ($this) {
            self::Diajukan => 'Diajukan',
            self::Diproses => 'Sedang Diproses',
            self::Selesai => 'Selesai / Terbit',
            self::Ditolak => 'Ditolak',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Diajukan => 'bg-amber-50 text-amber-700 border border-amber-200',
            self::Diproses => 'bg-blue-50 text-blue-700 border border-blue-200',
            self::Selesai => 'bg-emerald-50 text-emerald-700 border border-emerald-200',
            self::Ditolak => 'bg-rose-50 text-rose-700 border border-rose-200',
        };
    }
}
