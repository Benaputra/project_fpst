@extends('layouts.app')

@section('title', 'Arsip Surat')

@section('content')
    <div class="eyebrow">Dokumen resmi</div>
    <h1>Arsip Surat</h1>
    <p class="lead">Surat yang diterbitkan dalam cakupan program studi Anda.</p>
    <div class="grid"><section class="card">
        @if ($surat->isEmpty())
            <div class="empty-state">Belum ada surat yang diterbitkan.</div>
        @else
            <div class="table-wrap"><table><thead><tr><th>Nomor</th><th>Jenis</th><th>Program studi</th><th>Versi</th><th>Status</th><th>Diterbitkan</th><th></th></tr></thead><tbody>
            @foreach ($surat as $item)<tr><td>{{ $item->no_surat }}</td><td>{{ str_replace('_', ' ', $item->jenis_surat->value) }}</td><td>{{ $item->programStudi->nama }}</td><td>{{ $item->versi }}</td><td class="status-text">{{ str_replace('_', ' ', $item->status->value) }}</td><td>{{ $item->generated_at?->format('d/m/Y H:i') }}</td><td><a class="table-link" href="{{ route('surat.download', $item) }}">Unduh</a></td></tr>@endforeach
            </tbody></table></div>
            <x-portal-pagination :paginator="$surat" />
        @endif
    </section></div>
@endsection
