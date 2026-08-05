@extends('layouts.app')

@section('title', 'Dashboard Mahasiswa')

@section('content')
    @php
        $pengajuan = $mahasiswa?->pengajuanJudul;
        $skripsi = $pengajuan?->skripsi;
    @endphp
    <span class="role-chip">Mahasiswa</span>
    <h1>Halo, {{ $mahasiswa?->nama ?? auth()->user()->name }}</h1>
    <p class="lead">Pantau perjalanan skripsi Anda dari pengajuan judul hingga sidang.</p>

    <div class="stats-grid">
        <div class="stat-card"><div class="stat-card__label">Pengajuan judul</div><div class="stat-card__value">{{ $pengajuan ? 1 : 0 }}</div><div class="stat-card__hint">{{ $pengajuan?->status->label() ?? 'Belum diajukan' }}</div></div>
        <div class="stat-card"><div class="stat-card__label">Status skripsi</div><div class="stat-card__value">{{ $skripsi ? 1 : 0 }}</div><div class="stat-card__hint status-text">{{ str_replace('_', ' ', $skripsi?->status->value ?? 'Belum dimulai') }}</div></div>
        <div class="stat-card"><div class="stat-card__label">Seminar</div><div class="stat-card__value">{{ $skripsi?->seminar ? 1 : 0 }}</div><div class="stat-card__hint status-text">{{ str_replace('_', ' ', $skripsi?->seminar?->status->value ?? 'Belum diajukan') }}</div></div>
        <div class="stat-card"><div class="stat-card__label">Sidang</div><div class="stat-card__value">{{ $skripsi?->sidangSkripsi ? 1 : 0 }}</div><div class="stat-card__hint status-text">{{ str_replace('_', ' ', $skripsi?->sidangSkripsi?->status->value ?? 'Belum diajukan') }}</div></div>
    </div>

    <div class="section-heading"><h2>Langkah berikutnya</h2></div>
    <section class="card">
        @if (! $mahasiswa)
            <div class="notice notice--warning">Akun belum terhubung dengan profil mahasiswa. Hubungi Admin Prodi.</div>
        @elseif (! $pengajuan)
            <h2>Ajukan judul skripsi</h2><p class="lead">Mulai proses akademik dengan mengirimkan judul untuk diperiksa Kaprodi.</p>
            <a class="button button--primary" href="{{ route('mahasiswa.pengajuan-judul.index') }}">Buka pengajuan judul</a>
        @else
            <h2>Status terbaru</h2>
            <p class="title-value" style="margin-top: .8rem;">{{ $pengajuan->judul }}</p>
            <div class="actions">
                <a class="button button--secondary button--compact" href="{{ route('mahasiswa.pengajuan-judul.index') }}">Lihat pengajuan</a>
                <a class="button button--secondary button--compact" href="{{ route('portal.skripsi.index') }}">Lihat skripsi</a>
            </div>
        @endif
    </section>
@endsection
