<?php

namespace App\Http\Requests\PengajuanJudul;

use App\Models\PengajuanJudul;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class AjukanJudulRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('create', PengajuanJudul::class);
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'judul' => ['bail', 'required', 'string', 'max:1000'],
        ];
    }
}
