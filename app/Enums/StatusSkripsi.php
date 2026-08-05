<?php

namespace App\Enums;

enum StatusSkripsi: string
{
    case MenungguKesediaanPembimbing = 'menunggu_kesediaan_pembimbing';
    case BimbinganAktif = 'bimbingan_aktif';
    case SiapSeminar = 'siap_seminar';
    case SiapSidang = 'siap_sidang';
    case Selesai = 'selesai';
}
