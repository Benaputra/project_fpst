<?php

namespace App\Http\Requests\Surat;

use App\Models\SidangSkripsi;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class TerbitkanSuratSidangRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->route('sidang') instanceof SidangSkripsi && Gate::allows('terbitkanSurat', $this->route('sidang'));
    }

    public function rules(): array
    {
        return ['jenis_surat' => ['required', Rule::in(['undangan_sidang', 'surat_tugas_sidang'])]];
    }
}
