@extends('layouts.app')

@section('title', 'Log Aktivitas')

@section('content')
    <div class="eyebrow">Audit sistem</div>
    <h1>Log Aktivitas</h1>
    <p class="lead">Jejak perubahan akademik lintas program studi. Data detail sensitif tidak ditampilkan.</p>
    <div class="grid"><section class="card">
        @if ($aktivitas->isEmpty())<div class="empty-state">Belum ada aktivitas yang tercatat.</div>@else
            <div class="table-wrap"><table><thead><tr><th>Waktu</th><th>Pengguna</th><th>Role</th><th>Aksi</th><th>Subjek</th><th>IP</th></tr></thead><tbody>
            @foreach ($aktivitas as $item)<tr><td>{{ $item->created_at?->format('d/m/Y H:i:s') }}</td><td>{{ $item->user?->name ?? 'Sistem' }}</td><td>{{ $item->user?->role?->label() ?? '—' }}</td><td>{{ str_replace('_', ' ', $item->aksi) }}</td><td>{{ class_basename($item->subject_type) }} #{{ $item->subject_id }}</td><td>{{ $item->ip_address ?? '—' }}</td></tr>@endforeach
            </tbody></table></div>
            <x-portal-pagination :paginator="$aktivitas" />
        @endif
    </section></div>
@endsection
