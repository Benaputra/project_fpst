<?php

namespace App\Http\Requests\Seminar;

use App\Enums\KeputusanVerifikasiPengajuan;
use App\Models\Seminar;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class VerifikasiSeminarRequest extends FormRequest
{
    public function authorize(): bool
    {
        $seminar = $this->route('seminar');

        return $seminar instanceof Seminar && Gate::allows('verify', $seminar);
    }

    public function rules(): array
    {
        return [
            'keputusan' => ['required', Rule::enum(KeputusanVerifikasiPengajuan::class)],
            'catatan_reject' => ['nullable', 'string', 'max:2000', 'required_if:keputusan,tolak'],
        ];
    }
}
