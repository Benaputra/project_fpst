<?php

namespace App\Models;

use App\Enums\HasilEvaluasiAkademik;
use App\Enums\PeranAudiensSeminar;
use App\Enums\StatusAudiensSeminar;
use App\Enums\StatusPengajuan;
use App\Enums\StatusPenugasanDosen;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class SeminarSkripsi extends Model
{
    use HasFactory;

    protected $table = 'seminar_skripsi';

    protected $fillable = [
        'pengajuan_skripsi_id',
        'file_naskah_seminar',
        'file_acc_pembimbing',
        'file_bukti_bayar_seminar',
        'file_toefl',
        'penguji_seminar_id',
        'tgl_seminar',
        'jam_seminar',
        'ruangan',
        'kuota_penonton',
        'kuota_penyanggah',
        'pendaftaran_audiens_dibuka',
        'token_presensi',
        'nomor_undangan_seminar',
        'file_undangan_seminar',
        'nomor_sk_seminar',
        'file_sk_seminar',
        'nilai_seminar',
        'hasil_evaluasi',
        'rincian_nilai',
        'status',
        'catatan',
        'catatan_revisi',
        'tenggat_revisi',
        'bebas_revisi_at',
        'bebas_revisi_oleh',
    ];

    protected function casts(): array
    {
        return [
            'status' => StatusPengajuan::class,
            'hasil_evaluasi' => HasilEvaluasiAkademik::class,
            'tgl_seminar' => 'date',
            'tenggat_revisi' => 'date',
            'bebas_revisi_at' => 'datetime',
            'nilai_seminar' => 'decimal:2',
            'rincian_nilai' => 'array',
            'kuota_penonton' => 'integer',
            'kuota_penyanggah' => 'integer',
            'pendaftaran_audiens_dibuka' => 'boolean',
        ];
    }

    public function pengajuanSkripsi(): BelongsTo
    {
        return $this->belongsTo(PengajuanSkripsi::class, 'pengajuan_skripsi_id');
    }

    public function penguji(): BelongsTo
    {
        return $this->belongsTo(User::class, 'penguji_seminar_id');
    }

    public function bebasRevisiOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'bebas_revisi_oleh');
    }

    public function audiens(): HasMany
    {
        return $this->hasMany(SeminarAudiens::class, 'seminar_skripsi_id')->orderBy('waktu_daftar');
    }

    public function kapasitasEfektif(): int
    {
        $maksSistem = (int) config('fpst.seminar_audiens.maks_kuota_sesi', 35);
        $kuota = (int) ($this->kuota_penonton ?: $maksSistem);

        return max(1, min($kuota, $maksSistem));
    }

    public function kapasitasPenyanggahEfektif(): int
    {
        $maksKapasitas = $this->kapasitasEfektif();
        $kuotaPenyanggah = $this->kuota_penyanggah !== null ? (int) $this->kuota_penyanggah : 2;

        return max(0, min($kuotaPenyanggah, $maksKapasitas));
    }

    public function jumlahAudiensAktif(): int
    {
        return $this->audiens()->aktif()->count();
    }

    public function jumlahPenyanggahAktif(): int
    {
        return $this->audiens()
            ->aktif()
            ->where('peran', PeranAudiensSeminar::Penyanggah->value)
            ->count();
    }

    public function sisaKuotaPenonton(): int
    {
        return max(0, $this->kapasitasEfektif() - $this->jumlahAudiensAktif());
    }

    public function sisaKuotaPenyanggah(): int
    {
        $sisaTotal = $this->sisaKuotaPenonton();
        $sisaSlotPenyanggah = max(0, $this->kapasitasPenyanggahEfektif() - $this->jumlahPenyanggahAktif());

        return min($sisaTotal, $sisaSlotPenyanggah);
    }

    public function surat(): HasMany
    {
        return $this->hasMany(Surat::class, 'seminar_skripsi_id')->latest();
    }

    public function penugasanDosen(): MorphMany
    {
        return $this->morphMany(PenugasanDosen::class, 'assignable')->latest('id');
    }

    public function latestPenugasanPenguji(): ?PenugasanDosen
    {
        return $this->penugasanDosen()->where('peran', 'penguji_seminar')->latest('id')->first();
    }

    public function isPengujiConfirmed(): bool
    {
        $hasPending = $this->penugasanDosen()
            ->where('peran', 'penguji_seminar')
            ->where('status', StatusPenugasanDosen::Menunggu)
            ->exists();

        if ($hasPending) {
            return false;
        }

        if (!$this->penguji_seminar_id) {
            return false;
        }

        return true;
    }

    public function isSelesai(): bool
    {
        return $this->status === StatusPengajuan::Selesai;
    }
}
