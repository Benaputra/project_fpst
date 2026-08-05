<?php

namespace App\Http\Requests\Skripsi;

use App\Models\PengajuanJudul;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class TetapkanCalonPembimbingRequest extends FormRequest
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
            'pembimbing1_id' => ['bail', 'required', 'string', 'max:20', 'exists:dosen,nidn'],
            'pembimbing2_id' => [
                'bail',
                'nullable',
                'string',
                'max:20',
                'different:pembimbing1_id',
                'exists:dosen,nidn',
            ],
        ];
    }
}
