<?php

namespace App\Http\Requests\PengajuanJudul;

use App\Models\PengajuanJudul;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class TolakJudulRequest extends FormRequest
{
    public function authorize(): bool
    {
        $pengajuanJudul = $this->route('pengajuanJudul');

        return $pengajuanJudul instanceof PengajuanJudul
            && Gate::allows('tolak', $pengajuanJudul);
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'alasan' => ['bail', 'required', 'string', 'max:2000'],
        ];
    }
}
