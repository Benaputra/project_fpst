@extends('layouts.app')

@section('title', 'Dashboard Ketua Program Studi')

@section('content')
    <span class="role-chip">Ketua Program Studi</span>
    <h1>{{ $programStudi?->nama ?? 'Dashboard Kaprodi' }}</h1>
    <p class="lead">Ringkasan keputusan akademik dan proses skripsi pada program studi yang Anda pimpin.</p>
    <div class="stats-grid">
        <div class="stat-card"><div class="stat-card__label">Judul menunggu</div><div class="stat-card__value">{{ $menungguJudul }}</div><div class="stat-card__hint">Perlu keputusan Kaprodi</div></div>
        <div class="stat-card"><div class="stat-card__label">Skripsi aktif</div><div class="stat-card__value">{{ $skripsiAktif }}</div><div class="stat-card__hint">Belum selesai</div></div>
        <div class="stat-card"><div class="stat-card__label">Seminar menunggu</div><div class="stat-card__value">{{ $menungguSeminar }}</div><div class="stat-card__hint">Menunggu verifikasi</div></div>
        <div class="stat-card"><div class="stat-card__label">Arsip surat</div><div class="stat-card__value">{{ $suratTerbit }}</div><div class="stat-card__hint">Dalam cakupan prodi</div></div>
    </div>
    <div class="section-heading"><h2>Pengajuan judul terbaru</h2><a class="table-link" href="{{ route('kaprodi.pengajuan-judul.index') }}">Lihat semua</a></div>
    <section class="card">
        @if ($pengajuanTerbaru->isEmpty())<div class="empty-state">Belum ada pengajuan judul.</div>@else
            <div class="table-wrap"><table><thead><tr><th>Mahasiswa</th><th>Judul</th><th>Status</th><th></th></tr></thead><tbody>
            @foreach ($pengajuanTerbaru as $item)<tr><td>{{ $item->mahasiswa->nama }}</td><td>{{ $item->judul }}</td><td>{{ $item->status->label() }}</td><td><a class="table-link" href="{{ route('kaprodi.pengajuan-judul.show', $item) }}">Detail</a></td></tr>@endforeach
            </tbody></table></div>
        @endif
    </section>
@endsection
