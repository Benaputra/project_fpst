<?php

namespace App\Services\Upload;

use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

class ValidasiHasilKonsultasi
{
    public function __construct(private readonly ValidasiUploadPrivat $validator) {}

    /**
     * @return array{content: string, extension: string, hash: string}
     *
     * @throws ValidationException
     */
    public function execute(UploadedFile $file): array
    {
        return $this->validator->execute($file, 'hasil_konsultasi');
    }
}
