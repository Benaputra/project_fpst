@extends('layouts.app')

@section('title', 'Kartu Kendali & Pendaftaran Penonton Seminar - FPST UPB')
@section('page_title', 'Kartu Kendali & Penonton / Penyanggah Seminar')

@section('content')
<div class="grid-stats" style="margin-bottom: 1.25rem;">
    <div class="stat-box">
        <div class="stat-box-title">Total Terpakai (Terdaftar/Hadir)</div>
        <div class="stat-box-value">{{ $kartuKendali['total_terpakai'] }} / {{ $kartuKendali['maks_total'] }}</div>
    </div>
    <div class="stat-box">
        <div class="stat-box-title">Kuota Penyanggah</div>
        <div class="stat-box-value">{{ $kartuKendali['penyanggah_terpakai'] }} / {{ $kartuKendali['maks_penyanggah'] }}</div>
    </div>
    <div class="stat-box">
        <div class="stat-box-title">Kehadiran Tervalidasi (Hadir)</div>
        <div class="stat-box-value">{{ $kartuKendali['total_hadir'] }} / {{ $kartuKendali['maks_total'] }}</div>
    </div>
    <div class="stat-box">
        <div class="stat-box-title">Status Syarat Daftar Seminar</div>
        <div class="stat-box-value" style="font-size: 1rem;">
            @if ($kartuKendali['memenuhi_syarat_seminar'])
                <span class="badge badge-selesai">✓ Memenuhi Syarat</span>
            @else
                <span class="badge badge-diajukan">Kurang {{ max(0, $kartuKendali['maks_total'] - $kartuKendali['total_hadir']) }}x Hadir</span>
            @endif
        </div>
    </div>
</div>

<div class="alert alert-info" style="margin-bottom: 1.25rem;">
    <span>ℹ️</span>
    <div>
        <strong>Aturan Kartu Kendali Seminar FPST:</strong>
        Setiap mahasiswa dapat mendaftar menonton seminar maksimal <strong>12 kali</strong> (sudah termasuk maksimal <strong>2 kali</strong> sebagai <strong>Penyanggah</strong>, selebihnya sebagai <strong>Peserta</strong>). Setiap sesi seminar memiliki batas kapasitas maksimal <strong>35 orang</strong>.
    </div>
</div>

<!-- Daftar Sesi Seminar Terjadwal yang Terbuka -->
<div class="card" style="padding: 1.25rem; margin-bottom: 1.25rem;">
    <div class="card-header" style="margin-bottom: 1rem;">
        <h2 class="card-title">Jadwal Sesi Seminar Terbuka untuk Pendaftaran</h2>
        <span style="font-size: 0.8rem; color: var(--text-muted);">Sisa kuota penonton dihitung real-time (maks. 35/sesi)</span>
    </div>

    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Jadwal &amp; Ruang</th>
                    <th>Penyaji &amp; Judul Skripsi</th>
                    <th>Kuota Sesi</th>
                    <th style="text-align: center; width: 250px;">Pendaftaran</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($daftarSeminar as $sesi)
                    @php
                        $kapasitasTotal = $sesi->kapasitasEfektif();
                        $kapasitasPenyanggah = $sesi->kapasitasPenyanggahEfektif();
                        $terisiTotal = (int) ($sesi->terisi_total_count ?? 0);
                        $terisiPenyanggah = (int) ($sesi->terisi_penyanggah_count ?? 0);
                        $sisaTotal = max(0, $kapasitasTotal - $terisiTotal);
                        $sisaPenyanggah = min($sisaTotal, max(0, $kapasitasPenyanggah - $terisiPenyanggah));
                        $isOwnSeminar = (int) ($sesi->pengajuanSkripsi?->mahasiswa_id) === (int) $user->id;
                        $sudahDaftar = $pendaftaranAktifBySeminar->get($sesi->id);
                    @endphp
                    <tr>
                        <td style="font-size: 0.83rem; white-space: nowrap;">
                            <strong>{{ $sesi->tgl_seminar?->translatedFormat('d M Y') }}</strong>
                            <div>⏰ {{ $sesi->jam_seminar }}</div>
                            <div style="color: var(--text-muted);">📍 {{ $sesi->ruangan }}</div>
                        </td>
                        <td>
                            <div style="font-weight: 700; color: #1c2b20;">
                                {{ $sesi->pengajuanSkripsi?->mahasiswa?->name ?? '-' }}
                                <span class="badge badge--primary" style="font-size: 0.68rem;">{{ $sesi->pengajuanSkripsi?->programStudi?->kode ?? '-' }}</span>
                            </div>
                            <div style="font-size: 0.83rem; color: var(--text-muted); margin-top: 0.2rem;">
                                {{ $sesi->pengajuanSkripsi?->judul }}
                            </div>
                        </td>
                        <td style="font-size: 0.82rem; white-space: nowrap;">
                            <div>Kursi Total: <strong>{{ $terisiTotal }} / {{ $kapasitasTotal }}</strong></div>
                            <div>Penyanggah: <strong>{{ $terisiPenyanggah }} / {{ $kapasitasPenyanggah }}</strong></div>
                        </td>
                        <td style="text-align: center;">
                            @if ($isOwnSeminar)
                                <span class="badge badge--secondary">Seminar Anda Sendiri</span>
                            @elseif ($sudahDaftar)
                                <span class="badge {{ $sudahDaftar->status->badgeClass() }}">
                                    Terdaftar ({{ $sudahDaftar->peran->label() }})
                                </span>
                            @elseif (!$sesi->pendaftaran_audiens_dibuka)
                                <span class="badge badge-ditolak">Pendaftaran Ditutup</span>
                            @elseif ($sisaTotal <= 0)
                                <span class="badge badge-ditolak">Kuota Penuh (35/35)</span>
                            @elseif ($kartuKendali['sisa_kuota_tonton'] <= 0)
                                <span class="badge badge-ditolak">Batas 12x Terpakai</span>
                            @else
                                <div style="display: flex; gap: 0.35rem; justify-content: center; flex-wrap: wrap;">
                                    <form method="POST" action="{{ route('mahasiswa.audiens.daftar', $sesi->id) }}">
                                        @csrf
                                        <input type="hidden" name="peran" value="peserta">
                                        <button type="submit" class="btn btn-primary btn-sm">
                                            Daftar Peserta
                                        </button>
                                    </form>
                                    @if ($sisaPenyanggah > 0 && $kartuKendali['sisa_kuota_penyanggah'] > 0)
                                        <form method="POST" action="{{ route('mahasiswa.audiens.daftar', $sesi->id) }}">
                                            @csrf
                                            <input type="hidden" name="peran" value="penyanggah">
                                            <button type="submit" class="btn btn-secondary btn-sm">
                                                Daftar Penyanggah
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" style="text-align: center; padding: 2rem; color: var(--text-muted);">
                            Belum ada jadwal seminar baru yang terbuka untuk pendaftaran audiens.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div style="margin-top: 1rem;">
        {{ $daftarSeminar->links() }}
    </div>
</div>

<!-- Riwayat & Presensi Kartu Kendali Saya -->
<div class="card" style="padding: 1.25rem;">
    <div class="card-header" style="margin-bottom: 1rem;">
        <h2 class="card-title">Riwayat Kartu Kendali Seminar Saya</h2>
    </div>

    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Tanggal &amp; Penyaji</th>
                    <th>Peran</th>
                    <th>Status Kehadiran</th>
                    <th style="text-align: center;">Presensi / Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($riwayatSaya as $row)
                    <tr>
                        <td style="font-size: 0.84rem;">
                            <div style="font-weight: 700;">{{ $row->seminar?->pengajuanSkripsi?->mahasiswa?->name ?? '-' }}</div>
                            <div style="color: var(--text-muted); font-size: 0.78rem;">
                                {{ $row->seminar?->tgl_seminar?->translatedFormat('d M Y') }} • {{ $row->seminar?->ruangan }}
                            </div>
                        </td>
                        <td>
                            <span class="badge {{ $row->peran->badgeClass() }}">{{ $row->peran->label() }}</span>
                        </td>
                        <td>
                            <span class="badge {{ $row->status->badgeClass() }}">{{ $row->status->label() }}</span>
                            @if ($row->waktu_presensi)
                                <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 0.2rem;">
                                    Presensi: {{ $row->waktu_presensi->format('d M Y H:i') }}
                                </div>
                            @endif
                        </td>
                        <td style="text-align: center;">
                            @if ($row->status === \App\Enums\StatusAudiensSeminar::Terdaftar)
                                <div style="display: flex; gap: 0.4rem; justify-content: center; flex-wrap: wrap; align-items: center;">
                                    <form method="POST" action="{{ route('mahasiswa.audiens.presensi-mandiri', $row->id) }}" style="display: inline-flex; gap: 0.25rem;">
                                        @csrf
                                        <input type="text" name="token_presensi" placeholder="Kode Sesi QR" required class="form-control" style="width: 115px; height: 32px; font-size: 0.76rem;">
                                        <button type="submit" class="btn btn-primary btn-sm" style="height: 32px; font-size: 0.75rem;">Hadir</button>
                                    </form>
                                    <form method="POST" action="{{ route('mahasiswa.audiens.batalkan', $row->id) }}" onsubmit="return confirm('Batalkan pendaftaran seminar ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-secondary btn-sm" style="height: 32px; font-size: 0.75rem;">Batal</button>
                                    </form>
                                </div>
                            @else
                                <span style="font-size: 0.78rem; color: var(--text-muted);">-</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" style="text-align: center; padding: 2rem; color: var(--text-muted);">
                            Belum ada catatan keikutsertaan seminar pada Kartu Kendali Anda.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
