<?php

namespace App\Http\Requests\Skripsi;

use App\Models\PengajuanJudul;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class CariCalonPembimbingRequest extends FormRequest
{
    public function authorize(): bool
    {
        $pengajuanJudul = $this->route('pengajuanJudul');

        return $pengajuanJudul instanceof PengajuanJudul
            && Gate::allows('tetapkanCalonPembimbing', $pengajuanJudul);
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function pencarian(): ?string
    {
        $pencarian = trim((string) $this->validated('q', ''));

        return $pencarian === '' ? null : $pencarian;
    }
}
