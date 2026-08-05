<?php

namespace App\Http\Requests\Surat;

use App\Models\Skripsi;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class TerbitkanSkBimbinganRequest extends FormRequest
{
    public function authorize(): bool
    {
        $skripsi = $this->route('skripsi');

        return $skripsi instanceof Skripsi && Gate::allows('terbitkanSk', $skripsi);
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [];
    }
}
