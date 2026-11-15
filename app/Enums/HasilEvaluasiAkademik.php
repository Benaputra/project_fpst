<?php

namespace App\Enums;

enum HasilEvaluasiAkademik: string
{
    case Lulus = 'lulus';
    case LulusRevisi = 'lulus_revisi';
    case Mengulang = 'mengulang';

    public function label(): string
    {
        return match ($this) {
            self::Lulus => 'Lulus Tanpa Revisi',
            self::LulusRevisi => 'Lulus dengan Revisi',
            self::Mengulang => 'Mengulang / Perlu Penjadwalan Ulang',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Lulus => 'badge-selesai',
            self::LulusRevisi => 'badge-diproses',
            self::Mengulang => 'badge-ditolak',
        };
    }
}
