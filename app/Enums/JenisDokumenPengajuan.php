<?php

namespace App\Enums;

enum JenisDokumenPengajuan: string
{
    case HasilKonsultasi = 'hasil_konsultasi';
    case BerkasSeminar = 'berkas_seminar';
    case BerkasSidang = 'berkas_sidang';
}
