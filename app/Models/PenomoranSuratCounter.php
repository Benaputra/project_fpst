<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PenomoranSuratCounter extends Model
{
    protected $table = 'penomoran_surat_counters';

    protected $fillable = [
        'kode_unit',
        'tahun',
        'last_number',
    ];
}
