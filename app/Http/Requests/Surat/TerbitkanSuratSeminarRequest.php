<?php

namespace App\Http\Requests\Surat;

use App\Models\Seminar;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class TerbitkanSuratSeminarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->route('seminar') instanceof Seminar && Gate::allows('terbitkanSurat', $this->route('seminar'));
    }

    public function rules(): array
    {
        return ['jenis_surat' => ['required', Rule::in(['undangan_seminar', 'surat_tugas_seminar'])]];
    }
}
