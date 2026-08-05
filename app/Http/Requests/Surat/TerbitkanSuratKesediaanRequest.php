<?php

namespace App\Http\Requests\Surat;

use App\Models\KesediaanBimbingan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class TerbitkanSuratKesediaanRequest extends FormRequest
{
    public function authorize(): bool
    {
        $kesediaan = $this->route('kesediaanBimbingan');

        return $kesediaan instanceof KesediaanBimbingan
            && Gate::allows('terbitkanSurat', $kesediaan);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
