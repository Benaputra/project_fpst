@extends('layouts.app')

@section('title', 'Dashboard Admin Utama')

@section('content')
    <span class="role-chip">Admin Utama</span>
    <h1>Dashboard Sistem</h1>
    <p class="lead">Ringkasan global administrasi skripsi lintas program studi.</p>
    <div class="stats-grid">
        <div class="stat-card"><div class="stat-card__label">Pengguna</div><div class="stat-card__value">{{ $totalPengguna }}</div><div class="stat-card__hint">Seluruh role</div></div>
        <div class="stat-card"><div class="stat-card__label">Program studi</div><div class="stat-card__value">{{ $totalProgramStudi }}</div><div class="stat-card__hint">Cakupan global</div></div>
        <div class="stat-card"><div class="stat-card__label">Skripsi</div><div class="stat-card__value">{{ $totalSkripsi }}</div><div class="stat-card__hint">Seluruh status</div></div>
        <div class="stat-card"><div class="stat-card__label">Arsip surat</div><div class="stat-card__value">{{ $totalSurat }}</div><div class="stat-card__hint">Seluruh prodi</div></div>
    </div>
    <div class="section-heading"><h2>Aktivitas terbaru</h2><a class="table-link" href="{{ route('portal.aktivitas-log.index') }}">Lihat log</a></div>
    <section class="card">
        @if ($aktivitasTerbaru->isEmpty())<div class="empty-state">Belum ada aktivitas yang tercatat.</div>@else
            <div class="table-wrap"><table><thead><tr><th>Waktu</th><th>Pengguna</th><th>Aksi</th><th>Subjek</th></tr></thead><tbody>
                @foreach ($aktivitasTerbaru as $item)<tr><td>{{ $item->created_at?->format('d/m/Y H:i') }}</td><td>{{ $item->user?->name ?? 'Sistem' }}</td><td>{{ str_replace('_', ' ', $item->aksi) }}</td><td>{{ class_basename($item->subject_type) }} #{{ $item->subject_id }}</td></tr>@endforeach
            </tbody></table></div>
        @endif
    </section>
@endsection
