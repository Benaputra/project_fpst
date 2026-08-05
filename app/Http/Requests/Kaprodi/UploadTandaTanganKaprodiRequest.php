<?php

namespace App\Http\Requests\Kaprodi;

use Illuminate\Foundation\Http\FormRequest;

class UploadTandaTanganKaprodiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isKetuaProdi() === true;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'tanda_tangan' => [
                'required',
                'file',
                'mimes:png,jpg,jpeg',
                'max:2048',
                'dimensions:min_width=40,min_height=20,max_width=2000,max_height=2000',
            ],
        ];
    }
}
