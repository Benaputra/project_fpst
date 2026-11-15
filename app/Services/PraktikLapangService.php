<?php

namespace App\Services;

use App\Enums\JenisSuratInternal;
use App\Enums\StatusPengajuanSurat;
use App\Enums\StatusPenugasanDosen;
use App\Enums\StatusPraktikLapang;
use App\Enums\StatusVerifikasiLokasiPl;
use App\Enums\UserRole;
use App\Models\AktivitasLog;
use App\Models\LogbookPl;
use App\Models\LokasiPl;
use App\Models\Notifikasi;
use App\Models\PengajuanSurat;
use App\Models\PenugasanDosen;
use App\Models\PraktikLapang;
use App\Models\Surat;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

class PraktikLapangService
{
    public function __construct(
        private readonly JadwalBentrokService $bentrokService,
    ) {}

    /**
     * Pendaftaran Praktik Lapang (PL) beserta pengecekan syarat SKS, anti-overbooking kuota lokasi,
     * dan dukungan pengajuan lokasi PL baru untuk diverifikasi.
     *
     * @param array<string, mixed> $data
     */
    public function daftarPl(User $mahasiswa, array $data, ?string $fileTranskripPath = null): PraktikLapang
    {
        if (! $mahasiswa->isMahasiswa()) {
            throw new DomainException('Hanya akun Mahasiswa yang dapat mendaftar Praktik Lapang.');
        }

        $minSks = (int) config('fpst.praktik_lapang.min_sks', 90);
        $sksDitempuh = (int) ($data['sks_ditempuh'] ?? 0);

        if ($sksDitempuh < $minSks) {
            throw new DomainException("Syarat minimal SKS untuk mengikuti Praktik Lapang adalah {$minSks} SKS (tercatat: {$sksDitempuh} SKS).");
        }

        $periode = trim((string) ($data['periode_akademik'] ?? ''));
        if ($periode === '') {
            throw new DomainException('Periode akademik Praktik Lapang wajib diisi.');
        }

        return DB::transaction(function () use ($mahasiswa, $data, $sksDitempuh, $periode, $fileTranskripPath): PraktikLapang {
            User::whereKey($mahasiswa->id)->lockForUpdate()->firstOrFail();

            $existingActive = PraktikLapang::query()
                ->where('mahasiswa_id', $mahasiswa->id)
                ->where('periode_akademik', $periode)
                ->where('status', '!=', StatusPraktikLapang::Ditolak->value)
                ->lockForUpdate()
                ->exists();

            if ($existingActive) {
                throw new DomainException("Anda sudah memiliki pendaftaran Praktik Lapang aktif pada periode {$periode}.");
            }

            if (! empty($data['lokasi_pl_id'])) {
                $lokasi = LokasiPl::whereKey((int) $data['lokasi_pl_id'])->lockForUpdate()->firstOrFail();

                if ($lokasi->status_verifikasi !== StatusVerifikasiLokasiPl::Terverifikasi) {
                    throw new DomainException('Lokasi Praktik Lapang yang dipilih belum terverifikasi sebagai mitra aktif.');
                }

                $terisi = PraktikLapang::query()
                    ->where('lokasi_pl_id', $lokasi->id)
                    ->where('periode_akademik', $periode)
                    ->where('status', '!=', StatusPraktikLapang::Ditolak->value)
                    ->lockForUpdate()
                    ->count();

                if ($terisi >= (int) $lokasi->kuota_periode) {
                    throw new DomainException("Kuota tempat Praktik Lapang {$lokasi->nama_instansi} pada periode {$periode} sudah penuh ({$lokasi->kuota_periode} mahasiswa).");
                }
            } else {
                $namaBaru = trim((string) ($data['nama_instansi_baru'] ?? ''));
                $alamatBaru = trim((string) ($data['alamat_baru'] ?? ''));

                if ($namaBaru === '' || $alamatBaru === '') {
                    throw new DomainException('Pilih lokasi mitra terdaftar atau lengkapi nama instansi & alamat usulan baru.');
                }

                $lokasi = LokasiPl::create([
                    'nama_instansi' => $namaBaru,
                    'sektor' => (string) ($data['sektor_baru'] ?? ($mahasiswa->programStudi?->kode ?: 'AGT/AGB')),
                    'alamat' => $alamatBaru,
                    'kabupaten_kota' => $data['kabupaten_kota_baru'] ?? null,
                    'kontak_pic' => $data['kontak_pic_baru'] ?? null,
                    'telepon_pic' => $data['telepon_pic_baru'] ?? null,
                    'kuota_periode' => (int) config('fpst.praktik_lapang.default_kuota_periode', 10),
                    'status_verifikasi' => StatusVerifikasiLokasiPl::Menunggu,
                    'diusulkan_oleh' => $mahasiswa->id,
                ]);
            }

            $pl = PraktikLapang::create([
                'mahasiswa_id' => $mahasiswa->id,
                'program_studi_id' => (int) $mahasiswa->program_studi_id,
                'lokasi_pl_id' => $lokasi->id,
                'periode_akademik' => $periode,
                'sks_ditempuh' => $sksDitempuh,
                'judul_kegiatan' => trim((string) $data['judul_kegiatan']),
                'file_transkrip_sks' => $fileTranskripPath,
                'status' => StatusPraktikLapang::Diajukan,
            ]);

            AktivitasLog::catat(
                $mahasiswa,
                'Pendaftaran Praktik Lapang',
                "Mahasiswa {$mahasiswa->name} ({$mahasiswa->nomor_induk}) mendaftar Praktik Lapang di {$lokasi->nama_instansi} periode {$periode}"
            );

            Notifikasi::kirimKePengelola(
                $mahasiswa->program_studi_id,
                'Pendaftaran Praktik Lapang (PL) Baru',
                "Mahasiswa {$mahasiswa->name} ({$mahasiswa->nomor_induk}) mendaftar PL di {$lokasi->nama_instansi} ({$periode}).",
                route('kelola.pl.index'),
                $mahasiswa->id
            );

            return $pl;
        });
    }

    /**
     * Integrasi Modul 2 -> Modul 1: Ajukan Surat Pengantar Praktik Lapang melalui sistem persuratan internal.
     */
    public function ajukanSuratPengantar(PraktikLapang $pl, User $mahasiswa): PengajuanSurat
    {
        return DB::transaction(function () use ($pl, $mahasiswa): PengajuanSurat {
            $locked = PraktikLapang::with(['lokasiPl', 'programStudi'])
                ->whereKey($pl->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ((int) $locked->mahasiswa_id !== (int) $mahasiswa->id) {
                throw new DomainException('Anda tidak berhak mengajukan surat pengantar untuk pendaftaran PL mahasiswa lain.');
            }

            if ($locked->status === StatusPraktikLapang::Ditolak) {
                throw new DomainException('Pendaftaran PL yang ditolak tidak dapat diajukan surat pengantar.');
            }

            if ($locked->lokasiPl && $locked->lokasiPl->status_verifikasi !== StatusVerifikasiLokasiPl::Terverifikasi) {
                throw new DomainException('Lokasi usulan baru harus diverifikasi prodi/admin terlebih dahulu sebelum mengajukan Surat Pengantar PL.');
            }

            if ($locked->pengajuan_surat_id) {
                throw new DomainException('Surat Pengantar Praktik Lapang untuk pendaftaran ini sudah diajukan melalui Modul Persuratan.');
            }

            $namaLokasi = $locked->lokasiPl?->nama_instansi ?? 'Instansi Mitra';
            $prodiNama = $locked->programStudi?->nama ?? 'FPST';

            $pengajuanSurat = PengajuanSurat::create([
                'mahasiswa_id' => $mahasiswa->id,
                'program_studi_id' => $locked->program_studi_id,
                'jenis_surat' => JenisSuratInternal::PengantarPl,
                'perihal' => "Surat Pengantar Praktik Lapang - {$namaLokasi}",
                'tujuan_instansi' => $namaLokasi,
                'alamat_instansi' => $locked->lokasiPl?->alamat,
                'keterangan_kebutuhan' => "Permohonan pengantar pelaksanaan Praktik Lapang (PL) Program Studi {$prodiNama} periode {$locked->periode_akademik} dengan topik: {$locked->judul_kegiatan}.",
                'file_pendukung' => $locked->file_transkrip_sks,
                'status' => StatusPengajuanSurat::Diajukan,
            ]);

            $locked->update([
                'pengajuan_surat_id' => $pengajuanSurat->id,
                'status' => StatusPraktikLapang::PengantarDiproses,
            ]);

            AktivitasLog::catat(
                $mahasiswa,
                'Ajukan Surat Pengantar PL',
                "Mahasiswa {$mahasiswa->name} mengajukan Surat Pengantar PL ke {$namaLokasi} (Modul Persuratan #{$pengajuanSurat->id})"
            );

            Notifikasi::kirimKePengelola(
                $locked->program_studi_id,
                'Permohonan Surat Pengantar Praktik Lapang',
                "Mahasiswa {$mahasiswa->name} mengajukan Surat Pengantar PL ke {$namaLokasi}.",
                route('admin.persuratan.index'),
                $mahasiswa->id,
                [UserRole::AdminUtama, UserRole::AdminProdi, UserRole::Tendik, UserRole::PimpinanFakultas]
            );

            return $pengajuanSurat;
        });
    }

    public function unggahSuratBalasan(PraktikLapang $pl, User $mahasiswa, string $fileBalasanPath): PraktikLapang
    {
        return DB::transaction(function () use ($pl, $mahasiswa, $fileBalasanPath): PraktikLapang {
            $locked = PraktikLapang::with('lokasiPl')->whereKey($pl->id)->lockForUpdate()->firstOrFail();

            if ((int) $locked->mahasiswa_id !== (int) $mahasiswa->id) {
                throw new DomainException('Akses ditolak untuk pendaftaran PL ini.');
            }

            if ($locked->status === StatusPraktikLapang::Ditolak) {
                throw new DomainException('Pendaftaran PL ini telah ditolak.');
            }

            $statusBaru = $locked->dpl_id && $locked->isDplConfirmed()
                ? StatusPraktikLapang::Berjalan
                : StatusPraktikLapang::MenungguDpl;

            $locked->update([
                'file_surat_balasan' => $fileBalasanPath,
                'status' => $statusBaru,
            ]);

            AktivitasLog::catat(
                $mahasiswa,
                'Unggah Surat Balasan PL',
                "Mahasiswa {$mahasiswa->name} mengunggah surat balasan instansi untuk PL di {$locked->lokasiPl?->nama_instansi}"
            );

            Notifikasi::kirimKePengelola(
                $locked->program_studi_id,
                'Surat Balasan PL Diunggah — Siap Penetapan DPL',
                "Mahasiswa {$mahasiswa->name} telah mengunggah surat balasan instansi PL. Silakan tetapkan Dosen Pembimbing Lapang (DPL).",
                route('kelola.pl.index'),
                $mahasiswa->id,
                [UserRole::AdminUtama, UserRole::Kaprodi, UserRole::AdminProdi]
            );

            return $locked;
        });
    }

    public function verifikasiLokasi(
        LokasiPl $lokasi,
        User $actor,
        StatusVerifikasiLokasiPl $status,
        ?int $kuota = null,
        ?string $catatan = null
    ): LokasiPl {
        return DB::transaction(function () use ($lokasi, $actor, $status, $kuota, $catatan): LokasiPl {
            $locked = LokasiPl::whereKey($lokasi->id)->lockForUpdate()->firstOrFail();

            $locked->update([
                'status_verifikasi' => $status,
                'kuota_periode' => $kuota ? max(1, $kuota) : $locked->kuota_periode,
                'diverifikasi_oleh' => $actor->id,
                'catatan_verifikasi' => $catatan,
            ]);

            if ($status === StatusVerifikasiLokasiPl::Ditolak) {
                PraktikLapang::query()
                    ->where('lokasi_pl_id', $locked->id)
                    ->where('status', StatusPraktikLapang::Diajukan->value)
                    ->update([
                        'status' => StatusPraktikLapang::Ditolak,
                        'catatan' => $catatan ?: 'Usulan tempat Praktik Lapang ditolak.',
                    ]);
            }

            AktivitasLog::catat(
                $actor,
                'Verifikasi Lokasi Praktik Lapang',
                "{$actor->name} mengubah status lokasi {$locked->nama_instansi} menjadi {$status->label()}"
            );

            if ($locked->diusulkan_oleh) {
                Notifikasi::kirim(
                    $locked->diusulkan_oleh,
                    'Status Verifikasi Tempat PL',
                    "Usulan tempat PL '{$locked->nama_instansi}' berstatus: {$status->label()}." . ($catatan ? " Catatan: {$catatan}" : ''),
                    route('mahasiswa.pl.index')
                );
            }

            return $locked;
        });
    }

    public function tetapkanDpl(PraktikLapang $pl, User $dosen, User $actor, bool $isMandat = false): PenugasanDosen
    {
        if (! $dosen->isDosen()) {
            throw new DomainException('Dosen Pembimbing Lapang (DPL) harus berasal dari akun Dosen atau Kaprodi.');
        }

        return DB::transaction(function () use ($pl, $dosen, $actor, $isMandat): PenugasanDosen {
            $locked = PraktikLapang::with(['mahasiswa', 'lokasiPl'])->whereKey($pl->id)->lockForUpdate()->firstOrFail();

            if (! $isMandat && $locked->lokasiPl && $locked->lokasiPl->status_verifikasi !== StatusVerifikasiLokasiPl::Terverifikasi) {
                throw new DomainException('Verifikasi lokasi PL terlebih dahulu sebelum menetapkan DPL.');
            }

            $locked->penugasanDosen()
                ->where('peran', 'dpl')
                ->where('status', StatusPenugasanDosen::Menunggu)
                ->update([
                    'status' => StatusPenugasanDosen::Ditolak,
                    'alasan_penolakan' => 'Digantikan penunjukan DPL baru oleh pengelola.',
                    'direspon_pada' => now(),
                ]);

            $penugasan = $locked->penugasanDosen()->create([
                'dosen_id' => $dosen->id,
                'peran' => 'dpl',
                'status' => $isMandat ? StatusPenugasanDosen::Disetujui : StatusPenugasanDosen::Menunggu,
                'ditugaskan_oleh' => $actor->id,
                'is_mandat_admin_utama' => $isMandat,
                'direspon_pada' => $isMandat ? now() : null,
            ]);

            $locked->update([
                'dpl_id' => $dosen->id,
                'status' => $isMandat ? StatusPraktikLapang::Berjalan : StatusPraktikLapang::MenungguDpl,
            ]);

            $mhsName = $locked->mahasiswa?->name ?? 'Mahasiswa';

            AktivitasLog::catat(
                $actor,
                'Penetapan DPL Praktik Lapang',
                "{$actor->name} menetapkan {$dosen->name} sebagai DPL untuk {$mhsName}" . ($isMandat ? ' (Mandat Admin Utama)' : '')
            );

            Notifikasi::kirim(
                $dosen->id,
                $isMandat ? 'Mandat Pembimbing Lapang (DPL)' : 'Permintaan Kesediaan DPL Praktik Lapang',
                $isMandat
                    ? "Anda ditetapkan (mandat) sebagai Dosen Pembimbing Lapang untuk mahasiswa {$mhsName} di {$locked->lokasiPl?->nama_instansi}."
                    : "Anda ditugaskan sebagai Dosen Pembimbing Lapang untuk mahasiswa {$mhsName}. Silakan konfirmasi kesediaan di portal Dosen.",
                route('dosen.bimbingan.index')
            );

            Notifikasi::kirim(
                $locked->mahasiswa_id,
                'Penetapan Dosen Pembimbing Lapang (DPL)',
                "Dosen {$dosen->name} ditetapkan sebagai DPL Praktik Lapang Anda" . ($isMandat ? ' (Aktif).' : ' (menunggu konfirmasi kesediaan dosen).'),
                route('mahasiswa.pl.index')
            );

            return $penugasan;
        });
    }

    public function terbitkanSkDpl(
        PraktikLapang $pl,
        User $actor,
        string $nomorSk,
        ?string $fileSkPath = null,
        ?string $tglMulai = null,
        ?string $tglSelesai = null
    ): Surat {
        return DB::transaction(function () use ($pl, $actor, $nomorSk, $fileSkPath, $tglMulai, $tglSelesai): Surat {
            $locked = PraktikLapang::with('mahasiswa')->whereKey($pl->id)->lockForUpdate()->firstOrFail();

            if (! $locked->dpl_id) {
                throw new DomainException('Dosen Pembimbing Lapang (DPL) belum ditetapkan oleh Kaprodi.');
            }

            if (! $actor->isAdminUtama() && ! $locked->isDplConfirmed()) {
                throw new DomainException('SK DPL belum dapat diterbitkan sebelum Dosen Pembimbing Lapang mengonfirmasi kesediaan.');
            }

            $existingCount = Surat::where('praktik_lapang_id', $locked->id)
                ->where('jenis_surat', 'sk_dpl')
                ->lockForUpdate()
                ->count();

            $versi = $existingCount + 1;
            $mhsName = $locked->mahasiswa?->name ?? 'Mahasiswa';

            if ($existingCount > 0) {
                Surat::where('praktik_lapang_id', $locked->id)
                    ->where('jenis_surat', 'sk_dpl')
                    ->where('status', 'aktif')
                    ->update(['status' => 'diperbarui']);
            }

            $surat = Surat::create([
                'nomor_surat' => $nomorSk,
                'jenis_surat' => 'sk_dpl',
                'nama_surat' => $versi > 1 ? "SK DPL Praktik Lapang (Pembaruan ke-{$versi}) - {$mhsName}" : "SK DPL Praktik Lapang - {$mhsName}",
                'praktik_lapang_id' => $locked->id,
                'program_studi_id' => $locked->program_studi_id,
                'tgl_surat' => now()->toDateString(),
                'file_surat' => $fileSkPath ?? $locked->file_sk_dpl,
                'versi' => $versi,
                'status' => 'aktif',
                'diterbitkan_oleh' => $actor->id,
                'keterangan' => 'SK Penetapan Dosen Pembimbing Lapang (DPL) Praktik Lapang',
            ]);

            $locked->update([
                'nomor_sk_dpl' => $nomorSk,
                'file_sk_dpl' => $fileSkPath ?? $locked->file_sk_dpl,
                'tgl_mulai' => $tglMulai ?? $locked->tgl_mulai,
                'tgl_selesai' => $tglSelesai ?? $locked->tgl_selesai,
                'status' => $locked->status === StatusPraktikLapang::MenungguDpl ? StatusPraktikLapang::Berjalan : $locked->status,
            ]);

            AktivitasLog::catat(
                $actor,
                'Penerbitan SK DPL',
                "{$actor->name} menerbitkan SK DPL ({$nomorSk}) untuk mahasiswa {$mhsName}"
            );

            Notifikasi::kirim(
                $locked->mahasiswa_id,
                'SK DPL Praktik Lapang Terbit',
                "SK DPL ({$nomorSk}) telah diterbitkan. Anda dapat mengisi logbook kegiatan harian PL.",
                route('mahasiswa.pl.index')
            );

            return $surat;
        });
    }

    /**
     * @param array<string, mixed> $data
     */
    public function tambahLogbook(PraktikLapang $pl, User $mahasiswa, array $data, ?string $filePath = null): LogbookPl
    {
        return DB::transaction(function () use ($pl, $mahasiswa, $data, $filePath): LogbookPl {
            $locked = PraktikLapang::whereKey($pl->id)->lockForUpdate()->firstOrFail();

            if ((int) $locked->mahasiswa_id !== (int) $mahasiswa->id) {
                throw new DomainException('Akses logbook ditolak.');
            }

            if (! in_array($locked->status, [StatusPraktikLapang::Berjalan, StatusPraktikLapang::SeminarTerjadwal], true)) {
                throw new DomainException('Logbook hanya dapat diisi ketika status Praktik Lapang sedang berjalan.');
            }

            $entry = LogbookPl::create([
                'praktik_lapang_id' => $locked->id,
                'tanggal' => $data['tanggal'],
                'aktivitas' => trim((string) $data['aktivitas']),
                'uraian' => trim((string) $data['uraian']),
                'file_dokumentasi' => $filePath,
                'diverifikasi_dpl' => false,
            ]);

            AktivitasLog::catat(
                $mahasiswa,
                'Isi Logbook PL',
                "Mahasiswa {$mahasiswa->name} menambahkan logbook PL tanggal {$entry->tanggal->format('Y-m-d')}"
            );

            return $entry;
        });
    }

    public function verifikasiLogbook(LogbookPl $logbook, User $dpl, ?string $catatan = null): LogbookPl
    {
        return DB::transaction(function () use ($logbook, $dpl, $catatan): LogbookPl {
            $locked = LogbookPl::with('praktikLapang')->whereKey($logbook->id)->lockForUpdate()->firstOrFail();

            $locked->update([
                'diverifikasi_dpl' => true,
                'diverifikasi_at' => now(),
                'catatan_dpl' => $catatan,
            ]);

            AktivitasLog::catat(
                $dpl,
                'Verifikasi Logbook PL',
                "{$dpl->name} memverifikasi logbook PL ID {$locked->id}"
            );

            return $locked;
        });
    }

    public function unggahLaporanAkhir(PraktikLapang $pl, User $mahasiswa, string $filePath): PraktikLapang
    {
        return DB::transaction(function () use ($pl, $mahasiswa, $filePath): PraktikLapang {
            $locked = PraktikLapang::whereKey($pl->id)->lockForUpdate()->firstOrFail();

            if ((int) $locked->mahasiswa_id !== (int) $mahasiswa->id) {
                throw new DomainException('Akses ditolak.');
            }

            if (! in_array($locked->status, [StatusPraktikLapang::Berjalan, StatusPraktikLapang::SeminarTerjadwal], true)) {
                throw new DomainException('Laporan akhir PL hanya dapat diunggah saat PL berjalan.');
            }

            $locked->update([
                'file_laporan_akhir' => $filePath,
            ]);

            AktivitasLog::catat(
                $mahasiswa,
                'Unggah Laporan Akhir PL',
                "Mahasiswa {$mahasiswa->name} mengunggah laporan akhir Praktik Lapang"
            );

            Notifikasi::kirimKePengelola(
                $locked->program_studi_id,
                'Laporan Akhir PL Diunggah',
                "Mahasiswa {$mahasiswa->name} mengunggah laporan akhir PL dan siap dijadwalkan Seminar PL.",
                route('kelola.pl.index'),
                $mahasiswa->id
            );

            return $locked;
        });
    }

    public function jadwalkanSeminarPl(
        PraktikLapang $pl,
        User $actor,
        string $tglSeminar,
        string $jamSeminar,
        string $ruangan
    ): PraktikLapang {
        $updated = DB::transaction(function () use ($pl, $actor, $tglSeminar, $jamSeminar, $ruangan): PraktikLapang {
            $locked = PraktikLapang::with('mahasiswa')->whereKey($pl->id)->lockForUpdate()->firstOrFail();

            if (! $locked->dpl_id) {
                throw new DomainException('Seminar PL belum dapat dijadwalkan sebelum DPL ditetapkan.');
            }

            $conflict = $this->bentrokService->detectConflict(
                $tglSeminar,
                $jamSeminar,
                $ruangan,
                [$locked->dpl_id],
                ignorePlId: $locked->id
            );

            if ($conflict !== null) {
                throw new DomainException($conflict);
            }

            $locked->update([
                'tgl_seminar_pl' => $tglSeminar,
                'jam_seminar_pl' => $jamSeminar,
                'ruangan_seminar_pl' => $ruangan,
                'status' => StatusPraktikLapang::SeminarTerjadwal,
            ]);

            $mhsName = $locked->mahasiswa?->name ?? 'Mahasiswa';

            AktivitasLog::catat(
                $actor,
                'Penjadwalan Seminar PL',
                "{$actor->name} menjadwalkan Seminar PL {$mhsName} pada {$tglSeminar} ({$jamSeminar}) di {$ruangan}"
            );

            Notifikasi::kirim(
                $locked->mahasiswa_id,
                'Jadwal Seminar Praktik Lapang Ditetapkan',
                "Seminar PL Anda dijadwalkan pada {$tglSeminar} ({$jamSeminar}) di {$ruangan}.",
                route('mahasiswa.pl.index')
            );

            return $locked;
        });

        JadwalPublikService::clearCache();

        return $updated;
    }

    public function simpanPenilaianPl(
        PraktikLapang $pl,
        User $actor,
        float $nilaiPembimbingLapang,
        float $nilaiDpl,
        ?string $fileSertifikatPath = null,
        ?string $catatan = null
    ): PraktikLapang {
        $updated = DB::transaction(function () use ($pl, $actor, $nilaiPembimbingLapang, $nilaiDpl, $fileSertifikatPath, $catatan): PraktikLapang {
            $locked = PraktikLapang::with('mahasiswa')->whereKey($pl->id)->lockForUpdate()->firstOrFail();

            $nilaiAkhir = round(($nilaiPembimbingLapang * 0.5) + ($nilaiDpl * 0.5), 2);

            $locked->update([
                'nilai_pembimbing_lapang' => $nilaiPembimbingLapang,
                'nilai_dpl' => $nilaiDpl,
                'nilai_akhir' => $nilaiAkhir,
                'file_sertifikat' => $fileSertifikatPath ?? $locked->file_sertifikat,
                'catatan' => $catatan ?? $locked->catatan,
                'status' => StatusPraktikLapang::Selesai,
            ]);

            $mhsName = $locked->mahasiswa?->name ?? 'Mahasiswa';

            AktivitasLog::catat(
                $actor,
                'Finalisasi Nilai Praktik Lapang',
                "{$actor->name} menyimpan nilai akhir PL ({$nilaiAkhir}) untuk {$mhsName}"
            );

            Notifikasi::kirim(
                $locked->mahasiswa_id,
                'Nilai & Sertifikat Praktik Lapang Terbit',
                "Nilai akhir Praktik Lapang Anda telah diterbitkan: {$nilaiAkhir} (Pembimbing Lapang: {$nilaiPembimbingLapang}, DPL: {$nilaiDpl}).",
                route('mahasiswa.pl.index')
            );

            return $locked;
        });

        JadwalPublikService::clearCache();

        return $updated;
    }
}
