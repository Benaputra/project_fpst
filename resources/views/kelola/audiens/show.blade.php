@extends('layouts.app')

@section('title', 'Daftar Hadir Penonton & Penyanggah Seminar - FPST UPB')
@section('page_title', 'Validasi Kehadiran Penonton & Penyanggah Seminar')

@section('content')
<div class="card" style="padding: 1.25rem; margin-bottom: 1.25rem;">
    <div style="display: flex; flex-wrap: wrap; justify-content: space-between; gap: 1rem; align-items: center;">
        <div>
            <div style="font-size: 0.78rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">
                {{ $seminar->pengajuanSkripsi?->programStudi?->nama }} ({{ $seminar->pengajuanSkripsi?->programStudi?->kode }})
            </div>
            <h2 style="font-size: 1.1rem; font-weight: 700; margin-top: 0.15rem;">
                Penyaji: {{ $seminar->pengajuanSkripsi?->mahasiswa?->name }} ({{ $seminar->pengajuanSkripsi?->mahasiswa?->nomor_induk }})
            </h2>
            <p style="font-size: 0.85rem; color: var(--text-muted); margin-top: 0.25rem;">
                "{{ $seminar->pengajuanSkripsi?->judul }}"
            </p>
            <div style="font-size: 0.82rem; margin-top: 0.35rem;">
                📅 {{ $seminar->tgl_seminar?->translatedFormat('d F Y') }} • ⏰ {{ $seminar->jam_seminar }} • 📍 {{ $seminar->ruangan }}
            </div>
        </div>

        <div style="display: flex; align-items: center; gap: 0.9rem; background: #f8faf8; border: 1px solid var(--border); border-radius: 0.75rem; padding: 0.75rem 1rem;">
            <img src="{{ $qrTokenSvg }}" alt="QR Presensi Sesi" style="width: 72px; height: 72px;">
            <div>
                <div style="font-size: 0.72rem; color: var(--text-muted); font-weight: 600;">KODE PRESENSI SESI</div>
                <div style="font-size: 1.15rem; font-weight: 800; font-family: monospace; color: var(--primary-dark); letter-spacing: 1px;">
                    {{ $seminar->token_presensi }}
                </div>
                <div style="font-size: 0.72rem; color: var(--text-muted);">
                    Kursi Terisi: <strong>{{ $seminar->jumlahAudiensAktif() }} / {{ $seminar->kapasitasEfektif() }}</strong>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card" style="padding: 1.25rem;">
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th style="width: 50px; text-align: center;">No</th>
                    <th>Mahasiswa</th>
                    <th>Peran</th>
                    <th>Status Kehadiran</th>
                    <th style="text-align: center; width: 220px;">Validasi Presensi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($daftarAudiens as $idx => $item)
                    <tr>
                        <td style="text-align: center;">{{ $idx + 1 }}</td>
                        <td>
                            <strong>{{ $item->mahasiswa?->name }}</strong>
                            <div style="font-size: 0.76rem; color: var(--text-muted); font-family: monospace;">
                                {{ $item->mahasiswa?->nomor_induk }} • {{ $item->mahasiswa?->programStudi?->kode ?? '-' }}
                            </div>
                        </td>
                        <td>
                            <span class="badge {{ $item->peran->badgeClass() }}">{{ $item->peran->label() }}</span>
                        </td>
                        <td>
                            <span class="badge {{ $item->status->badgeClass() }}">{{ $item->status->label() }}</span>
                            @if ($item->validator)
                                <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 0.2rem;">
                                    Oleh: {{ $item->validator->name }}
                                </div>
                            @endif
                        </td>
                        <td style="text-align: center;">
                            @if ($item->status !== \App\Enums\StatusAudiensSeminar::Dibatalkan)
                                <div style="display: flex; gap: 0.35rem; justify-content: center;">
                                    <form method="POST" action="{{ route('kelola.audiens.validasi', $item->id) }}">
                                        @csrf
                                        <input type="hidden" name="status" value="hadir">
                                        <button type="submit" class="btn btn-primary btn-sm">✓ Hadir</button>
                                    </form>
                                    <form method="POST" action="{{ route('kelola.audiens.validasi', $item->id) }}">
                                        @csrf
                                        <input type="hidden" name="status" value="tidak_hadir">
                                        <button type="submit" class="btn btn-secondary btn-sm">✕ Alfa</button>
                                    </form>
                                </div>
                            @else
                                <span style="font-size: 0.78rem; color: var(--text-muted);">Dibatalkan Mahasiswa</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 2rem; color: var(--text-muted);">
                            Belum ada mahasiswa yang mendaftar sebagai penonton atau penyanggah pada seminar ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
