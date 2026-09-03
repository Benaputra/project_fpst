<?php

namespace App\Enums;

enum StatusPenugasanDosen: string
{
    case Menunggu = 'menunggu';
    case Disetujui = 'disetujui';
    case Ditolak = 'ditolak';
    case Dibatalkan = 'dibatalkan';

    public function label(): string
    {
        return match ($this) {
            self::Menunggu => 'Menunggu Konfirmasi',
            self::Disetujui => 'Disetujui / Bersedia',
            self::Ditolak => 'Ditolak Dosen',
            self::Dibatalkan => 'Dibatalkan / Diganti',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Menunggu => 'bg-amber-50 text-amber-700 border border-amber-200',
            self::Disetujui => 'bg-emerald-50 text-emerald-700 border border-emerald-200',
            self::Ditolak => 'bg-rose-50 text-rose-700 border border-rose-200',
            self::Dibatalkan => 'bg-slate-50 text-slate-700 border border-slate-200',
        };
    }
}
