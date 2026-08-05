<?php

namespace App\Http\Requests\PengajuanJudul;

use App\Enums\StatusPengajuanJudul;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DaftarPengajuanJudulKaprodiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isKetuaProdi() === true;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'status' => ['nullable', Rule::enum(StatusPengajuanJudul::class)],
            'cari' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function status(): ?StatusPengajuanJudul
    {
        $status = $this->validated('status');

        return is_string($status) ? StatusPengajuanJudul::tryFrom($status) : null;
    }

    public function pencarian(): ?string
    {
        $pencarian = trim((string) $this->validated('cari', ''));

        return $pencarian === '' ? null : $pencarian;
    }
}
