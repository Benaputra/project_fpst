@extends('layouts.app')

@section('title', 'Pemeriksaan Judul Skripsi')

@section('content')
    <div class="eyebrow">Ketua Program Studi</div>
    <h1>Pemeriksaan Judul Skripsi</h1>
    <p class="lead">Tinjau pengajuan mahasiswa dari program studi yang Anda pimpin.</p>

    <div class="grid">
        @if (session('status'))
            <div class="notice notice--success" role="status">{{ session('status') }}</div>
        @endif

        <section class="card" aria-labelledby="filter-heading">
            <h2 id="filter-heading" style="margin-bottom: 1rem;">Filter pengajuan</h2>
            <form class="toolbar" method="GET" action="{{ route('kaprodi.pengajuan-judul.index') }}">
                <div class="field">
                    <label for="cari">Cari mahasiswa atau judul</label>
                    <input id="cari" name="cari" value="{{ request('cari') }}" maxlength="100" placeholder="NIM, nama, atau judul">
                </div>
                <div class="field">
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <option value="">Semua status</option>
                        @foreach (\App\Enums\StatusPengajuanJudul::cases() as $status)
                            <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <button class="button button--primary button--compact" type="submit">Terapkan</button>
            </form>
            @error('status')
                <p class="field-error" role="alert">{{ $message }}</p>
            @enderror
            @error('cari')
                <p class="field-error" role="alert">{{ $message }}</p>
            @enderror
        </section>

        <section class="card" aria-labelledby="list-heading">
            <div class="card__header">
                <h2 id="list-heading">Daftar pengajuan</h2>
                <span class="field-help">{{ $pengajuanJudul->total() }} pengajuan</span>
            </div>
            @if ($pengajuanJudul->isEmpty())
                <div class="empty-state">Tidak ada pengajuan yang sesuai.</div>
            @else
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>NIM</th>
                                <th>Mahasiswa</th>
                                <th>Angkatan</th>
                                <th>Judul</th>
                                <th>Tanggal</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($pengajuanJudul as $pengajuan)
                                <tr>
                                    <td>{{ $pengajuan->mahasiswa->nim }}</td>
                                    <td>{{ $pengajuan->mahasiswa->nama }}</td>
                                    <td>{{ $pengajuan->mahasiswa->angkatan }}</td>
                                    <td>{{ \Illuminate\Support\Str::limit($pengajuan->judul, 70) }}</td>
                                    <td>{{ $pengajuan->created_at->format('d/m/Y') }}</td>
                                    <td>
                                        <span class="badge {{ match ($pengajuan->status) {
                                            \App\Enums\StatusPengajuanJudul::Diajukan => 'badge--waiting',
                                            \App\Enums\StatusPengajuanJudul::Diverifikasi => 'badge--success',
                                            \App\Enums\StatusPengajuanJudul::Ditolak => 'badge--danger',
                                        } }}">{{ $pengajuan->status->label() }}</span>
                                    </td>
                                    <td><a class="table-link" href="{{ route('kaprodi.pengajuan-judul.show', $pengajuan) }}">Detail</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($pengajuanJudul->hasPages())
                    <nav class="pagination" aria-label="Pagination">
                        <span>Halaman {{ $pengajuanJudul->currentPage() }} dari {{ $pengajuanJudul->lastPage() }}</span>
                        <div class="pagination__links">
                            @if ($pengajuanJudul->onFirstPage())
                                <span aria-disabled="true">Sebelumnya</span>
                            @else
                                <a href="{{ $pengajuanJudul->previousPageUrl() }}">Sebelumnya</a>
                            @endif
                            @if ($pengajuanJudul->hasMorePages())
                                <a href="{{ $pengajuanJudul->nextPageUrl() }}">Berikutnya</a>
                            @else
                                <span aria-disabled="true">Berikutnya</span>
                            @endif
                        </div>
                    </nav>
                @endif
            @endif
        </section>
    </div>
@endsection
