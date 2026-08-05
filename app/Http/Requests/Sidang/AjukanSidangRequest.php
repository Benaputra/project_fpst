<?php

namespace App\Http\Requests\Sidang;

use Illuminate\Foundation\Http\FormRequest;

class AjukanSidangRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isMahasiswa() === true;
    }

    public function rules(): array
    {
        return ['berkas_sidang' => ['nullable', 'file', 'max:5120']];
    }
}
