<?php

namespace App\Http\Requests\Skripsi;

use App\Models\KesediaanBimbingan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class GantiCalonPembimbingRequest extends FormRequest
{
    public function authorize(): bool
    {
        $kesediaan = $this->route('kesediaanBimbingan');

        return $kesediaan instanceof KesediaanBimbingan
            && Gate::allows('gantiCalon', $kesediaan);
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'calon_pengganti_id' => ['required', 'string', 'max:20'],
        ];
    }
}
