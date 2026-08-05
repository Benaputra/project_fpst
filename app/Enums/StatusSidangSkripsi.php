<?php

namespace App\Enums;

enum StatusSidangSkripsi: string
{
    case Diajukan = 'diajukan';
    case Diverifikasi = 'diverifikasi';
    case Dijadwalkan = 'dijadwalkan';
    case Selesai = 'selesai';
    case Ditolak = 'ditolak';
}
