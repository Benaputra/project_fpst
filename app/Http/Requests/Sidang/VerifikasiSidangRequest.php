<?php

namespace App\Http\Requests\Sidang;

use App\Enums\KeputusanVerifikasiPengajuan;
use App\Models\SidangSkripsi;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class VerifikasiSidangRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->route('sidang') instanceof SidangSkripsi && Gate::allows('verify', $this->route('sidang'));
    }

    public function rules(): array
    {
        return ['keputusan' => ['required', Rule::enum(KeputusanVerifikasiPengajuan::class)], 'catatan_reject' => ['nullable', 'string', 'max:2000', 'required_if:keputusan,tolak']];
    }
}
