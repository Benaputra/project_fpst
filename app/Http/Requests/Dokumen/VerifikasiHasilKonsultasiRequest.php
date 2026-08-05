<?php

namespace App\Http\Requests\Dokumen;

use App\Enums\KeputusanHasilKonsultasi;
use App\Models\DokumenPengajuan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class VerifikasiHasilKonsultasiRequest extends FormRequest
{
    public function authorize(): bool
    {
        $dokumen = $this->route('dokumen');

        return $dokumen instanceof DokumenPengajuan
            && Gate::allows('verify', $dokumen);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'keputusan' => ['required', Rule::enum(KeputusanHasilKonsultasi::class)],
            'catatan_verifikasi' => [
                'nullable',
                'string',
                'max:2000',
                'required_if:keputusan,valid_tidak_bersedia,upload_tidak_valid',
            ],
        ];
    }

    public function keputusan(): KeputusanHasilKonsultasi
    {
        return KeputusanHasilKonsultasi::from((string) $this->validated('keputusan'));
    }
}
