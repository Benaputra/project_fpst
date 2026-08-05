<?php

namespace App\Http\Requests\Sidang;

use App\Models\SidangSkripsi;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;

class JadwalkanSidangRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->route('sidang') instanceof SidangSkripsi && Gate::allows('schedule', $this->route('sidang'));
    }

    public function rules(): array
    {
        return ['penguji1_id' => ['required', 'string', 'max:20'], 'penguji2_id' => ['required', 'string', 'max:20', 'different:penguji1_id'], 'tanggal' => ['required', 'date'], 'tempat' => ['required', 'string', 'max:255']];
    }

    public function tanggal(): Carbon
    {
        return Carbon::parse($this->validated('tanggal'));
    }
}
