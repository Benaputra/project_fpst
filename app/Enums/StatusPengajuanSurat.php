<?php

namespace App\Enums;

enum StatusPengajuanSurat: string
{
    case Diajukan = 'diajukan';
    case Diverifikasi = 'diverifikasi';
    case Disetujui = 'disetujui';
    case Diterbitkan = 'diterbitkan';
    case Ditolak = 'ditolak';

    public function label(): string
    {
        return match ($this) {
            self::Diajukan => 'Menunggu Verifikasi Tendik/Admin',
            self::Diverifikasi => 'Terverifikasi • Menunggu Persetujuan',
            self::Disetujui => 'Disetujui • Siap Diterbitkan',
            self::Diterbitkan => 'Surat Resmi Terbit',
            self::Ditolak => 'Ditolak',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Diajukan => 'badge-diajukan',
            self::Diverifikasi, self::Disetujui => 'badge-diproses',
            self::Diterbitkan => 'badge-selesai',
            self::Ditolak => 'badge-ditolak',
        };
    }
}
