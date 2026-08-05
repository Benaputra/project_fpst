<?php

namespace App\Http\Requests\PengajuanJudul;

use App\Models\PengajuanJudul;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class AjukanUlangJudulRequest extends FormRequest
{
    public function authorize(): bool
    {
        $pengajuanJudul = $this->pengajuanJudul();

        return $pengajuanJudul
            && Gate::allows('update', $pengajuanJudul);
    }

    public function pengajuanJudul(): ?PengajuanJudul
    {
        $pengajuanJudul = $this->route('pengajuanJudul');

        if ($pengajuanJudul instanceof PengajuanJudul) {
            return $pengajuanJudul;
        }

        return $this->user()?->mahasiswa()
            ->first()?->pengajuanJudul()
            ->first();
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
