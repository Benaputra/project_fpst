<?php

namespace App\Http\Requests\Dokumen;

use App\Models\KesediaanBimbingan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class UploadHasilKonsultasiRequest extends FormRequest
{
    public function authorize(): bool
    {
        $kesediaan = $this->route('kesediaanBimbingan');

        return $kesediaan instanceof KesediaanBimbingan
            && Gate::allows('uploadHasilKonsultasi', $kesediaan);
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'hasil_konsultasi' => [
                'bail',
                'required',
                'file',
                'extensions:pdf,jpg,jpeg,png',
                'mimetypes:application/pdf,image/jpeg,image/png',
                'max:5120',
            ],
            'catatan_mahasiswa' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
