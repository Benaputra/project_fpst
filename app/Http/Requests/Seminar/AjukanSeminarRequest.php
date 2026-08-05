<?php

namespace App\Http\Requests\Seminar;

use Illuminate\Foundation\Http\FormRequest;

class AjukanSeminarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isMahasiswa() === true;
    }

    public function rules(): array
    {
        return ['berkas_seminar' => ['nullable', 'file', 'max:5120']];
    }
}
