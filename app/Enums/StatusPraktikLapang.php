<?php

namespace App\Enums;

enum StatusPraktikLapang: string
{
    case Diajukan = 'diajukan';
    case PengantarDiproses = 'pengantar_diproses';
    case MenungguDpl = 'menunggu_dpl';
    case Berjalan = 'berjalan';
    case SeminarTerjadwal = 'seminar_terjadwal';
    case Selesai = 'selesai';
    case Ditolak = 'ditolak';

    public function label(): string
    {
        return match ($this) {
            self::Diajukan => 'Diajukan Mahasiswa',
            self::PengantarDiproses => 'Pengantar & Surat Balasan',
            self::MenungguDpl => 'Penetapan DPL',
            self::Berjalan => 'Pelaksanaan & Logbook PL',
            self::SeminarTerjadwal => 'Seminar PL Terjadwal',
            self::Selesai => 'Selesai & Nilai Terbit',
            self::Ditolak => 'Ditolak',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Diajukan => 'badge-diajukan',
            self::PengantarDiproses, self::MenungguDpl, self::Berjalan, self::SeminarTerjadwal => 'badge-diproses',
            self::Selesai => 'badge-selesai',
            self::Ditolak => 'badge-ditolak',
        };
    }
}
