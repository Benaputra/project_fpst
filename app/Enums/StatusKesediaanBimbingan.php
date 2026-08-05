<?php

namespace App\Enums;

enum StatusKesediaanBimbingan: string
{
    case Ditunjuk = 'ditunjuk';
    case SuratTerbit = 'surat_terbit';
    case MenungguUpload = 'menunggu_upload';
    case MenungguVerifikasi = 'menunggu_verifikasi';
    case Diterima = 'diterima';
    case Ditolak = 'ditolak';
    case UploadTidakValid = 'upload_tidak_valid';
    case Dibatalkan = 'dibatalkan';
}
