<?php

namespace App\Models;

use App\Enums\StatusPraktikLapang;
use App\Enums\StatusVerifikasiLokasiPl;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LokasiPl extends Model
{
    use HasFactory;

    protected $table = 'lokasi_pl';

    protected $fillable = [
        'nama_instansi',
        'sektor',
        'alamat',
        'kabupaten_kota',
        'kontak_pic',
        'telepon_pic',
        'kuota_periode',
        'status_verifikasi',
        'diusulkan_oleh',
        'diverifikasi_oleh',
        'catatan_verifikasi',
    ];

    protected function casts(): array
    {
        return [
            'status_verifikasi' => StatusVerifikasiLokasiPl::class,
            'kuota_periode' => 'integer',
        ];
    }

    public function pengusul(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diusulkan_oleh');
    }

    public function verifikator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diverifikasi_oleh');
    }

    public function praktikLapang(): HasMany
    {
        return $this->hasMany(PraktikLapang::class, 'lokasi_pl_id');
    }

    public function scopeTerverifikasi(Builder $query): Builder
    {
        return $query->where('status_verifikasi', StatusVerifikasiLokasiPl::Terverifikasi);
    }

    public function terisiPadaPeriode(string $periode): int
    {
        return $this->praktikLapang()
            ->where('periode_akademik', $periode)
            ->where('status', '!=', StatusPraktikLapang::Ditolak->value)
            ->count();
    }

    public function sisaKuotaPadaPeriode(string $periode): int
    {
        return max(0, (int) $this->kuota_periode - $this->terisiPadaPeriode($periode));
    }
}
