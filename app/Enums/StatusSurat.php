<?php

namespace App\Enums;

enum StatusSurat: string
{
    case Draft = 'draft';
    case Diterbitkan = 'diterbitkan';
    case Terverifikasi = 'terverifikasi';
    case Dibatalkan = 'dibatalkan';
}
