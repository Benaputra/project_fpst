<?php

namespace App\Enums;

enum KeputusanHasilKonsultasi: string
{
    case ValidBersedia = 'valid_bersedia';
    case ValidTidakBersedia = 'valid_tidak_bersedia';
    case UploadTidakValid = 'upload_tidak_valid';

    public function membutuhkanCatatan(): bool
    {
        return $this !== self::ValidBersedia;
    }
}
