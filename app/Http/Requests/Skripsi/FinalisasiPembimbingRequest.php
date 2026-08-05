<?php

namespace App\Http\Requests\Skripsi;

use App\Models\Skripsi;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class FinalisasiPembimbingRequest extends FormRequest
{
    public function authorize(): bool
    {
        $skripsi = $this->route('skripsi');

        return $skripsi instanceof Skripsi
            && Gate::allows('finalisasiPembimbing', $skripsi);
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [];
    }
}
