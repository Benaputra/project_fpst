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
    @if (session('status'))<div class="notice notice--success" role="status" style="margin-top: 1.5rem;">{{ session('status') }}</div>@endif
    @if ($errors->any())<div class="notice notice--danger" role="alert" style="margin-top: 1.5rem;">{{ $errors->first() }}</div>@endif
    <div class="section-heading"><h2>Tanda tangan Kaprodi</h2></div>
    <section class="card">
        <div class="card__header">
            <div>
                <h3>{{ $tandaTanganTersedia ? 'Tanda tangan tersedia' : 'Tanda tangan belum tersedia' }}</h3>
                <p class="field-help">Disimpan privat dan hanya digunakan saat Anda menerbitkan surat sebagai Kaprodi.</p>
            </div>
            <span class="badge {{ $tandaTanganTersedia ? 'badge--success' : 'badge--waiting' }}">{{ $tandaTanganTersedia ? 'Tersimpan' : 'Belum diunggah' }}</span>
        </div>
        <form method="POST" enctype="multipart/form-data" action="{{ route('kaprodi.tanda-tangan.store') }}">
            @csrf
            <div class="field">
                <label for="tanda-tangan">File tanda tangan</label>
                <input id="tanda-tangan" type="file" name="tanda_tangan" accept=".png,.jpg,.jpeg" required>
                <p class="field-help">PNG atau JPEG, maksimal 2 MB. Mengunggah ulang akan mengganti file sebelumnya.</p>
            </div>
            <button class="button button--primary" type="submit">{{ $tandaTanganTersedia ? 'Ganti tanda tangan' : 'Unggah tanda tangan' }}</button>
        </form>
    </section>
    <div class="section-heading"><h2>Pengajuan judul terbaru</h2><a class="table-link" href="{{ route('kaprodi.pengajuan-judul.index') }}">Lihat semua</a></div>
    <section class="card">
        @if ($pengajuanTerbaru->isEmpty())<div class="empty-state">Belum ada pengajuan judul.</div>@else
            <div class="table-wrap"><table><thead><tr><th>Mahasiswa</th><th>Judul</th><th>Status</th><th></th></tr></thead><tbody>
            @foreach ($pengajuanTerbaru as $item)<tr><td>{{ $item->mahasiswa->nama }}</td><td>{{ $item->judul }}</td><td>{{ $item->status->label() }}</td><td><a class="table-link" href="{{ route('kaprodi.pengajuan-judul.show', $item) }}">Detail</a></td></tr>@endforeach
            </tbody></table></div>
        @endif
    </section>
@endsection
