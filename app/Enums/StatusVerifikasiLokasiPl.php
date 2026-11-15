<?php

namespace App\Enums;

enum StatusVerifikasiLokasiPl: string
{
    case Menunggu = 'menunggu_verifikasi';
    case Terverifikasi = 'terverifikasi';
    case Ditolak = 'ditolak';

    public function label(): string
    {
        return match ($this) {
            self::Menunggu => 'Menunggu Verifikasi',
            self::Terverifikasi => 'Terverifikasi Mitra FPST',
            self::Ditolak => 'Ditolak',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Menunggu => 'badge-diajukan',
            self::Terverifikasi => 'badge-selesai',
            self::Ditolak => 'badge-ditolak',
        };
    }
}
