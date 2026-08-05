@extends('layouts.app')

@section('title', 'Dashboard Dosen')

@section('content')
    <span class="role-chip">Dosen</span>
    <h1>Dashboard Dosen</h1>
    <p class="lead">Ringkasan bimbingan dan agenda akademik yang melibatkan Anda.</p>
    <div class="stats-grid" style="grid-template-columns: repeat(3, minmax(0, 1fr));">
        <div class="stat-card"><div class="stat-card__label">Skripsi bimbingan</div><div class="stat-card__value">{{ $skripsiBimbingan }}</div><div class="stat-card__hint">Sebagai Pembimbing 1 atau 2</div></div>
        <div class="stat-card"><div class="stat-card__label">Seminar terjadwal</div><div class="stat-card__value">{{ $seminarTerjadwal }}</div><div class="stat-card__hint">Sebagai pembimbing atau penguji</div></div>
        <div class="stat-card"><div class="stat-card__label">Program studi</div><div class="stat-card__value" style="font-size: 1.05rem;">{{ $dosen?->programStudi?->nama ?? '—' }}</div><div class="stat-card__hint">{{ $dosen?->nidn ?? 'Profil belum terhubung' }}</div></div>
    </div>
    <div class="section-heading"><h2>Agenda seminar</h2><a class="table-link" href="{{ route('portal.seminar.index') }}">Lihat semua</a></div>
    <section class="card">
        @if ($seminarTerbaru->isEmpty())
            <div class="empty-state">Belum ada seminar yang melibatkan Anda.</div>
        @else
            <div class="table-wrap"><table><thead><tr><th>Mahasiswa</th><th>Judul</th><th>Jadwal</th><th>Status</th></tr></thead><tbody>
                @foreach ($seminarTerbaru as $item)<tr><td>{{ $item->skripsi->mahasiswa->nama }}</td><td>{{ $item->skripsi->judul }}</td><td>{{ $item->tanggal?->format('d/m/Y H:i') ?? 'Belum dijadwalkan' }}</td><td class="status-text">{{ str_replace('_', ' ', $item->status->value) }}</td></tr>@endforeach
            </tbody></table></div>
        @endif
    </section>
@endsection
