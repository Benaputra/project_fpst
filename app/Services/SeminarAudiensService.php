<?php

namespace App\Services;

use App\Enums\PeranAudiensSeminar;
use App\Enums\StatusAudiensSeminar;
use App\Enums\StatusPengajuan;
use App\Models\AktivitasLog;
use App\Models\Notifikasi;
use App\Models\SeminarAudiens;
use App\Models\SeminarSkripsi;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SeminarAudiensService
{
    public function __construct(
        private readonly JadwalBentrokService $bentrokService,
    ) {}

    /**
     * Daftarkan mahasiswa ke sesi Seminar Skripsi sebagai Peserta atau Penyanggah.
     * Menjaga aturan:
     * - Maksimal 12x menonton per mahasiswa (termasuk maksimal 2x sebagai Penyanggah, selebihnya Peserta)
     * - Kapasitas maksimal 35 orang penonton per sesi seminar
     * - Anti-overbooking via DB::transaction + lockForUpdate()
     */
    public function daftar(SeminarSkripsi $seminar, User $mahasiswa, PeranAudiensSeminar $peran): SeminarAudiens
    {
        if (! $mahasiswa->isMahasiswa()) {
            throw new DomainException('Hanya akun Mahasiswa yang dapat mendaftar sebagai penonton atau penyanggah seminar.');
        }

        $maksTotal = (int) config('fpst.seminar_audiens.maks_total_tonton', 12);
        $maksPenyanggah = (int) config('fpst.seminar_audiens.maks_penyanggah_per_mahasiswa', 2);
        $maksSesiSistem = (int) config('fpst.seminar_audiens.maks_kuota_sesi', 35);

        $audiens = DB::transaction(function () use ($seminar, $mahasiswa, $peran, $maksTotal, $maksPenyanggah, $maksSesiSistem): SeminarAudiens {
            // Kunci akun mahasiswa dulu, lalu sesi seminar (urutan konsisten) agar bebas deadlock & race condition.
            User::whereKey($mahasiswa->id)->lockForUpdate()->firstOrFail();

            $lockedSeminar = SeminarSkripsi::with('pengajuanSkripsi')
                ->whereKey($seminar->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $lockedSeminar->tgl_seminar) {
                throw new DomainException('Seminar ini belum memiliki jadwal resmi dari program studi.');
            }

            if ($lockedSeminar->status === StatusPengajuan::Ditolak || $lockedSeminar->status === StatusPengajuan::Selesai) {
                throw new DomainException('Pendaftaran penonton seminar tidak tersedia karena seminar sudah selesai atau dibatalkan.');
            }

            if (! $lockedSeminar->pendaftaran_audiens_dibuka) {
                throw new DomainException('Pendaftaran penonton untuk sesi seminar ini sedang ditutup oleh admin.');
            }

            if ((int) $lockedSeminar->pengajuanSkripsi?->mahasiswa_id === (int) $mahasiswa->id) {
                throw new DomainException('Anda tidak dapat mendaftar sebagai penonton atau penyanggah pada seminar Anda sendiri.');
            }

            $existing = SeminarAudiens::query()
                ->where('seminar_skripsi_id', $lockedSeminar->id)
                ->where('mahasiswa_id', $mahasiswa->id)
                ->lockForUpdate()
                ->first();

            if ($existing && $existing->status !== StatusAudiensSeminar::Dibatalkan) {
                throw new DomainException('Anda sudah terdaftar pada sesi seminar ini.');
            }

            // Cek batas kuota pribadi mahasiswa (maks 12x tonton total, termasuk maks 2x penyanggah)
            $riwayatAktif = SeminarAudiens::query()
                ->where('mahasiswa_id', $mahasiswa->id)
                ->aktif()
                ->lockForUpdate()
                ->get(['id', 'peran', 'seminar_skripsi_id']);

            if ($riwayatAktif->count() >= $maksTotal) {
                throw new DomainException("Batas maksimal menonton seminar sebanyak {$maksTotal} kali telah tercapai.");
            }

            if ($peran === PeranAudiensSeminar::Penyanggah) {
                $jumlahPenyanggahMhs = $riwayatAktif
                    ->where('peran', PeranAudiensSeminar::Penyanggah)
                    ->count();

                if ($jumlahPenyanggahMhs >= $maksPenyanggah) {
                    throw new DomainException("Batas maksimal menjadi penyanggah adalah {$maksPenyanggah} kali. Sisa kesempatan Anda hanya dapat digunakan sebagai peserta.");
                }
            }

            // Cek bentrok jadwal pribadi mahasiswa pada tanggal & jam seminar lain yang aktif diikuti
            $pendaftaranLain = SeminarAudiens::query()
                ->with('seminar:id,tgl_seminar,jam_seminar')
                ->where('mahasiswa_id', $mahasiswa->id)
                ->where('seminar_skripsi_id', '!=', $lockedSeminar->id)
                ->aktif()
                ->get();

            foreach ($pendaftaranLain as $other) {
                if (! $other->seminar || ! $other->seminar->tgl_seminar) {
                    continue;
                }

                if ($other->seminar->tgl_seminar->toDateString() === $lockedSeminar->tgl_seminar->toDateString()
                    && $this->bentrokService->isTimeOverlapping((string) $other->seminar->jam_seminar, (string) $lockedSeminar->jam_seminar)) {
                    throw new DomainException('Jadwal seminar ini bertabrakan dengan sesi seminar lain yang sudah Anda ikuti.');
                }
            }

            // Cek kapasitas sesi seminar (maksimal 35 orang total penonton termasuk penyanggah)
            $kapasitasSesi = min($lockedSeminar->kapasitasEfektif(), $maksSesiSistem);
            $sesiAktif = SeminarAudiens::query()
                ->where('seminar_skripsi_id', $lockedSeminar->id)
                ->aktif()
                ->lockForUpdate()
                ->get(['id', 'peran']);

            if ($sesiAktif->count() >= $kapasitasSesi) {
                throw new DomainException("Kuota penonton untuk sesi seminar ini sudah penuh (maksimal {$kapasitasSesi} orang).");
            }

            if ($peran === PeranAudiensSeminar::Penyanggah) {
                $kapasitasPenyanggahSesi = min($lockedSeminar->kapasitasPenyanggahEfektif(), $kapasitasSesi);
                $terisiPenyanggah = $sesiAktif
                    ->where('peran', PeranAudiensSeminar::Penyanggah)
                    ->count();

                if ($terisiPenyanggah >= $kapasitasPenyanggahSesi) {
                    throw new DomainException('Slot penyanggah pada sesi seminar ini sudah penuh. Anda masih dapat mendaftar sebagai peserta selama kursi tersedia.');
                }
            }

            if (! $lockedSeminar->token_presensi) {
                $lockedSeminar->update(['token_presensi' => strtoupper(Str::random(10))]);
            }

            if ($existing) {
                $existing->update([
                    'peran' => $peran,
                    'status' => StatusAudiensSeminar::Terdaftar,
                    'waktu_daftar' => now(),
                    'waktu_presensi' => null,
                    'divalidasi_oleh' => null,
                    'catatan' => null,
                ]);
                $record = $existing;
            } else {
                $record = SeminarAudiens::create([
                    'seminar_skripsi_id' => $lockedSeminar->id,
                    'mahasiswa_id' => $mahasiswa->id,
                    'peran' => $peran,
                    'status' => StatusAudiensSeminar::Terdaftar,
                    'waktu_daftar' => now(),
                ]);
            }

            AktivitasLog::catat(
                $mahasiswa,
                'Daftar Audiens Seminar',
                "Mahasiswa {$mahasiswa->name} ({$mahasiswa->nomor_induk}) mendaftar sebagai {$peran->label()} pada seminar {$lockedSeminar->pengajuanSkripsi->judul}"
            );

            Notifikasi::kirim(
                $mahasiswa->id,
                'Pendaftaran Penonton Seminar Berhasil',
                "Anda terdaftar sebagai {$peran->label()} pada {$lockedSeminar->tgl_seminar->format('d/m/Y')} ({$lockedSeminar->jam_seminar}) di {$lockedSeminar->ruangan}.",
                route('mahasiswa.audiens.index')
            );

            return $record;
        });

        JadwalPublikService::clearCache();

        return $audiens;
    }

    public function batalkan(SeminarAudiens $audiens, User $mahasiswa): SeminarAudiens
    {
        $updated = DB::transaction(function () use ($audiens, $mahasiswa): SeminarAudiens {
            $locked = SeminarAudiens::with('seminar')
                ->whereKey($audiens->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ((int) $locked->mahasiswa_id !== (int) $mahasiswa->id) {
                throw new DomainException('Anda tidak berhak membatalkan pendaftaran mahasiswa lain.');
            }

            if ($locked->status !== StatusAudiensSeminar::Terdaftar) {
                throw new DomainException('Hanya pendaftaran berstatus Terdaftar yang dapat dibatalkan.');
            }

            if ($locked->seminar && $locked->seminar->status === StatusPengajuan::Selesai) {
                throw new DomainException('Pendaftaran tidak dapat dibatalkan karena seminar telah selesai.');
            }

            $locked->update([
                'status' => StatusAudiensSeminar::Dibatalkan,
            ]);

            AktivitasLog::catat(
                $mahasiswa,
                'Batal Audiens Seminar',
                "Mahasiswa {$mahasiswa->name} membatalkan pendaftaran audiens pada seminar ID {$locked->seminar_skripsi_id}"
            );

            return $locked;
        });

        JadwalPublikService::clearCache();

        return $updated;
    }

    public function validasiPresensi(
        SeminarAudiens $audiens,
        User $validator,
        StatusAudiensSeminar $statusBaru,
        ?string $catatan = null
    ): SeminarAudiens {
        if (! in_array($statusBaru, [StatusAudiensSeminar::Hadir, StatusAudiensSeminar::TidakHadir], true)) {
            throw new DomainException('Status validasi presensi harus Hadir atau Tidak Hadir.');
        }

        return DB::transaction(function () use ($audiens, $validator, $statusBaru, $catatan): SeminarAudiens {
            $locked = SeminarAudiens::with(['mahasiswa', 'seminar.pengajuanSkripsi'])
                ->whereKey($audiens->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->status === StatusAudiensSeminar::Dibatalkan) {
                throw new DomainException('Pendaftaran yang sudah dibatalkan tidak dapat divalidasi.');
            }

            $locked->update([
                'status' => $statusBaru,
                'waktu_presensi' => $statusBaru === StatusAudiensSeminar::Hadir ? now() : null,
                'divalidasi_oleh' => $validator->id,
                'catatan' => $catatan,
            ]);

            AktivitasLog::catat(
                $validator,
                'Validasi Presensi Seminar',
                "{$validator->name} mencatat {$locked->mahasiswa->name} ({$locked->peran->label()}) sebagai {$statusBaru->label()}"
            );

            Notifikasi::kirim(
                $locked->mahasiswa_id,
                'Presensi Seminar Divalidasi',
                "Kehadiran Anda sebagai {$locked->peran->label()} dicatat: {$statusBaru->label()}.",
                route('mahasiswa.audiens.index')
            );

            return $locked;
        });
    }

    public function validasiPresensiDenganToken(SeminarAudiens $audiens, User $mahasiswa, string $token): SeminarAudiens
    {
        return DB::transaction(function () use ($audiens, $mahasiswa, $token): SeminarAudiens {
            $locked = SeminarAudiens::with('seminar')
                ->whereKey($audiens->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ((int) $locked->mahasiswa_id !== (int) $mahasiswa->id) {
                throw new DomainException('Pendaftaran audiens bukan milik Anda.');
            }

            if ($locked->status === StatusAudiensSeminar::Dibatalkan) {
                throw new DomainException('Pendaftaran sudah dibatalkan.');
            }

            if ($locked->status === StatusAudiensSeminar::Hadir) {
                return $locked;
            }

            $expected = (string) ($locked->seminar?->token_presensi ?? '');
            if ($expected === '' || ! hash_equals(strtoupper($expected), strtoupper(trim($token)))) {
                throw new DomainException('Token / Kode QR presensi seminar tidak valid.');
            }

            $locked->update([
                'status' => StatusAudiensSeminar::Hadir,
                'waktu_presensi' => now(),
                'divalidasi_oleh' => $mahasiswa->id,
                'catatan' => 'Presensi mandiri terverifikasi kode sesi QR',
            ]);

            return $locked;
        });
    }

    /**
     * Ringkasan Kartu Kendali Seminar Mahasiswa & cek prasyarat mendaftar seminar sendiri.
     *
     * @return array<string, mixed>
     */
    public function ringkasanKartuKendali(User $mahasiswa): array
    {
        $maksTotal = (int) config('fpst.seminar_audiens.maks_total_tonton', 12);
        $maksPenyanggah = (int) config('fpst.seminar_audiens.maks_penyanggah_per_mahasiswa', 2);

        $rows = SeminarAudiens::query()
            ->where('mahasiswa_id', $mahasiswa->id)
            ->get(['peran', 'status']);

        $aktif = $rows->whereIn('status', [StatusAudiensSeminar::Terdaftar, StatusAudiensSeminar::Hadir]);
        $hadir = $rows->where('status', StatusAudiensSeminar::Hadir);

        $totalTerpakai = $aktif->count();
        $penyanggahTerpakai = $aktif->where('peran', PeranAudiensSeminar::Penyanggah)->count();
        $pesertaTerpakai = $aktif->where('peran', PeranAudiensSeminar::Peserta)->count();

        $totalHadir = $hadir->count();
        $penyanggahHadir = $hadir->where('peran', PeranAudiensSeminar::Penyanggah)->count();
        $pesertaHadir = $hadir->where('peran', PeranAudiensSeminar::Peserta)->count();

        return [
            'maks_total' => $maksTotal,
            'maks_penyanggah' => $maksPenyanggah,
            'total_terpakai' => $totalTerpakai,
            'sisa_kuota_tonton' => max(0, $maksTotal - $totalTerpakai),
            'penyanggah_terpakai' => $penyanggahTerpakai,
            'sisa_kuota_penyanggah' => max(0, $maksPenyanggah - $penyanggahTerpakai),
            'peserta_terpakai' => $pesertaTerpakai,
            'total_hadir' => $totalHadir,
            'penyanggah_hadir' => $penyanggahHadir,
            'peserta_hadir' => $pesertaHadir,
            'memenuhi_syarat_seminar' => $totalHadir >= $maksTotal && $penyanggahHadir <= $maksPenyanggah,
        ];
    }

    public function mahasiswaMemenuhiSyaratSeminar(User $mahasiswa): bool
    {
        if (! config('fpst.seminar_audiens.enforce_syarat_seminar', true)) {
            return true;
        }

        return $this->ringkasanKartuKendali($mahasiswa)['memenuhi_syarat_seminar'];
    }
}
