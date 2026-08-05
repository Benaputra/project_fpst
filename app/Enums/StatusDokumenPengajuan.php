<?php

namespace App\Enums;

enum StatusDokumenPengajuan: string
{
    case Diunggah = 'diunggah';
    case MenungguVerifikasi = 'menunggu_verifikasi';
    case Terverifikasi = 'terverifikasi';
    case Ditolak = 'ditolak';
    case Dibatalkan = 'dibatalkan';
}
