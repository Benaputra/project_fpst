@extends('layouts.app')

@section('title', 'Notifikasi & Pemberitahuan')
@section('page_title', 'Kotak Masuk Notifikasi')

@section('content')

<div class="card">
    <div class="card-header">
        <div>
            <h2 class="card-title">Pemberitahuan & Perubahan Data Terkini</h2>
            <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.2rem;">
                Notifikasi otomatis saat status judul, pembimbing, seminar, sidang, dan surat/SK diperbarui.
            </div>
        </div>

        @if ($user->unreadNotifikasiCount() > 0)
            <form method="POST" action="{{ route('notifikasi.baca-semua') }}">
                @csrf
                <button type="submit" class="btn btn-secondary btn-sm">
                    ✓ Tandai Semua Telah Dibaca
                </button>
            </form>
        @endif
    </div>

    <div style="display: flex; flex-direction: column; gap: 0.85rem;">
        @forelse ($notifikasiList as $notif)
            <div style="border: 1px solid var(--border); border-radius: 0.65rem; padding: 1.15rem; background: {{ $notif->dibaca ? '#ffffff' : '#f0f5f1' }}; border-left: 4px solid {{ $notif->dibaca ? '#cbd8ce' : '#446850' }}; display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; transition: background 0.15s;">
                <div style="flex: 1;">
                    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.35rem;">
                        <span style="font-size: 0.95rem; font-weight: 700; color: #142017;">{{ $notif->judul }}</span>
                        @if (!$notif->dibaca)
                            <span class="badge" style="background: #fee2e2; color: #991b1b; font-size: 0.68rem; padding: 0.15rem 0.45rem;">Baru</span>
                        @endif
                    </div>

                    <p style="font-size: 0.88rem; color: #3b5040; line-height: 1.5; margin-bottom: 0.5rem;">
                        {{ $notif->pesan }}
                    </p>

                    <div style="font-size: 0.75rem; color: var(--text-muted);">
                        🕒 {{ $notif->created_at->diffForHumans() }} ({{ $notif->created_at->translatedFormat('d M Y, H:i') }} WIB)
                    </div>
                </div>

                <div style="display: flex; align-items: center; gap: 0.5rem; flex-shrink: 0;">
                    @if ($notif->link)
                        <form method="POST" action="{{ route('notifikasi.baca', $notif->id) }}">
                            @csrf
                            <button type="submit" class="btn btn-primary btn-sm">
                                Buka Halaman ➔
                            </button>
                        </form>
                    @elseif (!$notif->dibaca)
                        <form method="POST" action="{{ route('notifikasi.baca', $notif->id) }}">
                            @csrf
                            <button type="submit" class="btn btn-secondary btn-sm" title="Tandai Dibaca">
                                ✓ Dibaca
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        @empty
            <div style="text-align: center; color: var(--text-muted); padding: 3.5rem 1.5rem;">
                <div style="font-size: 2.5rem; margin-bottom: 0.75rem;">🔔</div>
                <h3 style="font-size: 1.1rem; color: #2c3f31; margin-bottom: 0.35rem;">Belum Ada Notifikasi</h3>
                <p style="font-size: 0.85rem; color: var(--text-muted);">Semua pemberitahuan dan perubahan data terbaru akan muncul di sini.</p>
            </div>
        @endforelse
    </div>

    <div style="margin-top: 1.25rem;">
        {{ $notifikasiList->links() }}
    </div>
</div>

@endsection
