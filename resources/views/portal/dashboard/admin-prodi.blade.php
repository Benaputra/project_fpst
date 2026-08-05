@extends('layouts.app')

@section('title', 'Dashboard Admin Program Studi')

@section('content')
    <span class="role-chip">Admin Program Studi</span>
    <h1>Dashboard Administrasi Prodi</h1>
    <p class="lead">Data dibatasi pada {{ $programStudi->pluck('nama')->join(', ') ?: 'program studi yang belum dipetakan' }}.</p>
    <div class="stats-grid">
        <div class="stat-card"><div class="stat-card__label">Pengajuan judul</div><div class="stat-card__value">{{ $totalPengajuan }}</div><div class="stat-card__hint">Dalam cakupan administrasi</div></div>
        <div class="stat-card"><div class="stat-card__label">Skripsi aktif</div><div class="stat-card__value">{{ $skripsiAktif }}</div><div class="stat-card__hint">Belum selesai</div></div>
        <div class="stat-card"><div class="stat-card__label">Seminar menunggu</div><div class="stat-card__value">{{ $menungguSeminar }}</div><div class="stat-card__hint">Perlu pemeriksaan</div></div>
        <div class="stat-card"><div class="stat-card__label">Arsip surat</div><div class="stat-card__value">{{ $suratTerbit }}</div><div class="stat-card__hint">Semua jenis surat</div></div>
    </div>
    <div class="section-heading"><h2>Akses cepat</h2></div>
    <section class="card"><div class="actions">
        <a class="button button--secondary button--compact" href="{{ route('portal.pengajuan-judul.index') }}">Pantau pengajuan</a>
        <a class="button button--secondary button--compact" href="{{ route('portal.seminar.index') }}">Kelola seminar</a>
        <a class="button button--secondary button--compact" href="{{ route('portal.surat.index') }}">Buka arsip surat</a>
    </div></section>
@endsection
