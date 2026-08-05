<?php

namespace App\Http\Requests\Seminar;

use App\Models\Seminar;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;

class JadwalkanSeminarRequest extends FormRequest
{
    public function authorize(): bool
    {
        $seminar = $this->route('seminar');

        return $seminar instanceof Seminar && Gate::allows('schedule', $seminar);
    }

    public function rules(): array
    {
        return [
            'penguji1_id' => ['required', 'string', 'max:20'],
            'penguji2_id' => ['required', 'string', 'max:20', 'different:penguji1_id'],
            'tanggal' => ['required', 'date'],
            'tempat' => ['required', 'string', 'max:255'],
        ];
    }

    public function tanggal(): Carbon
    {
        return Carbon::parse($this->validated('tanggal'));
    }
}
