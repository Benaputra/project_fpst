@extends('layouts.app')

@section('title', 'Pengajuan Judul')

@section('content')
    <div class="eyebrow">Pemantauan akademik</div>
    <h1>Pengajuan Judul</h1>
    <p class="lead">Daftar pengajuan yang termasuk dalam cakupan akses akun Anda.</p>
    <div class="grid"><section class="card">
        @if ($pengajuanJudul->isEmpty())
            <div class="empty-state">Tidak ada data pengajuan judul yang dapat diakses.</div>
        @else
            <div class="table-wrap"><table><thead><tr><th>NIM</th><th>Mahasiswa</th><th>Program studi</th><th>Judul</th><th>Status</th><th>Tanggal</th></tr></thead><tbody>
                @foreach ($pengajuanJudul as $item)<tr><td>{{ $item->nim }}</td><td>{{ $item->mahasiswa->nama }}</td><td>{{ $item->mahasiswa->programStudi->nama }}</td><td>{{ $item->judul }}</td><td>{{ $item->status->label() }}</td><td>{{ $item->created_at->format('d/m/Y') }}</td></tr>@endforeach
            </tbody></table></div>
            <x-portal-pagination :paginator="$pengajuanJudul" />
        @endif
    </section></div>
@endsection
