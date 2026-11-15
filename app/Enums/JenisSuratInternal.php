<?php

namespace App\Enums;

enum JenisSuratInternal: string
{
    case IzinPenelitian = 'izin_penelitian';
    case IzinLab = 'izin_lab';
    case PengantarPl = 'pengantar_pl';
    case KeteranganAkademik = 'keterangan_akademik';

    public function label(): string
    {
        return match ($this) {
            self::IzinPenelitian => 'Surat Izin Penelitian / Pengambilan Data',
            self::IzinLab => 'Surat Izin Penggunaan Laboratorium / Alat',
            self::PengantarPl => 'Surat Pengantar Praktik Lapang (PL)',
            self::KeteranganAkademik => 'Surat Keterangan Administrasi Akademik',
        };
    }

    public function kodeKlasifikasi(): string
    {
        return match ($this) {
            self::IzinPenelitian => 'PNL',
            self::IzinLab => 'LAB',
            self::PengantarPl => 'PL',
            self::KeteranganAkademik => 'AKD',
        };
    }

    public function penandaTanganDefault(): string
    {
        return match ($this) {
            self::IzinPenelitian => 'Ketua Program Studi',
            self::IzinLab => 'Pimpinan Fakultas (Dekan / Wakil Dekan FPST)',
            self::PengantarPl => 'Pimpinan Fakultas (Dekan / Wakil Dekan FPST)',
            self::KeteranganAkademik => 'Pimpinan Fakultas (Dekan / Wakil Dekan FPST)',
        };
    }
}
