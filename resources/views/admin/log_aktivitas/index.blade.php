@extends('layouts.app')

@section('title', 'Log Aktivitas Sistem - Audit Trail')
@section('page_title', 'Log Aktivitas Sistem (Audit Trail)')

@section('content')

<div class="card">
    <div class="card-header">
        <div>
            <h2 class="card-title">Rekam Jejak Aktivitas Pengguna</h2>
            <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.2rem;">
                Memantau seluruh aksi dan perubahan data yang dilakukan oleh Mahasiswa, Dosen, Kaprodi, dan Admin.
            </div>
        </div>
    </div>

    <!-- Filter & Pencarian -->
    <form method="GET" action="{{ route('admin.log-aktivitas.index') }}" style="display: flex; flex-wrap: wrap; gap: 0.75rem; align-items: flex-end; margin-bottom: 1.5rem; padding-bottom: 1.25rem; border-bottom: 1px solid var(--border);">
        <div style="flex: 2; min-width: 240px;">
            <label class="form-label" style="font-size: 0.75rem;">Cari Kata Kunci (Nama, NIM, Aksi, Deskripsi)</label>
            <input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="Ketik kata kunci pencarian...">
        </div>

        <div style="flex: 1; min-width: 200px;">
            <label class="form-label" style="font-size: 0.75rem;">Filter Jenis Aksi</label>
            <select name="aksi" class="form-control" onchange="this.form.submit()">
                <option value="">-- Semua Jenis Aksi --</option>
                @foreach ($daftarAksi as $a)
                    <option value="{{ $a }}" {{ $aksiFilter === $a ? 'selected' : '' }}>{{ $a }}</option>
                @endforeach
            </select>
        </div>

        <button type="submit" class="btn btn-primary btn-sm" style="height: 38px;">Cari Log</button>
        @if ($search || $aksiFilter)
            <a href="{{ route('admin.log-aktivitas.index') }}" class="btn btn-secondary btn-sm" style="height: 38px;">Reset</a>
        @endif
    </form>

    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th style="width: 170px;">Waktu & IP</th>
                    <th style="width: 200px;">Pengguna</th>
                    <th style="width: 180px;">Aksi</th>
                    <th>Detail Perubahan</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($logList as $log)
                    <tr>
                        <td style="font-size: 0.78rem;">
                            <div style="font-weight: 700; color: #1c2b20;">{{ $log->created_at->translatedFormat('d M Y, H:i:s') }}</div>
                            <div style="color: var(--text-muted); font-size: 0.72rem; margin-top: 0.15rem;">IP: {{ $log->ip_address ?? '127.0.0.1' }}</div>
                        </td>
                        <td>
                            @if ($log->user)
                                <div style="font-weight: 700; color: #142017;">{{ $log->user->name }}</div>
                                <div style="font-size: 0.72rem; color: var(--text-muted);">
                                    {{ $log->user->nomor_induk ? $log->user->nomor_induk . ' • ' : '' }}{{ $log->user->role->label() }}
                                </div>
                            @else
                                <span style="color: var(--text-muted); font-style: italic;">Sistem / Pengguna Terhapus</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge badge-diproses" style="font-size: 0.72rem;">{{ $log->aksi }}</span>
                        </td>
                        <td style="font-size: 0.85rem; color: #2c3f31; line-height: 1.45;">
                            {{ $log->deskripsi }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" style="text-align: center; color: var(--text-muted); padding: 3rem;">
                            Belum ada riwayat log aktivitas yang sesuai.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top: 1.25rem;">
        {{ $logList->links() }}
    </div>
</div>

@endsection
