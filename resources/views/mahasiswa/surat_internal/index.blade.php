@extends('layouts.app')

@section('title', 'Pengajuan Surat Internal - FPST UPB')
@section('page_title', 'Pengajuan Surat Internal Fakultas')

@section('content')
<div class="card" style="padding: 1.25rem; margin-bottom: 1.25rem;">
    <div style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 1rem;">
        <div>
            <h2 style="font-size: 1.05rem; font-weight: 700; color: var(--text-dark);">Layanan Surat Resmi FPST untuk Kebutuhan Akademik</h2>
            <p style="font-size: 0.85rem; color: var(--text-muted); margin-top: 0.2rem;">
                Ajukan Surat Izin Penelitian, Izin Penggunaan Laboratorium / Alat, Pengantar Praktik Lapang (PL), maupun Keterangan Akademik secara daring dengan QR verifikasi resmi.
            </p>
        </div>
        <a href="{{ route('mahasiswa.surat-internal.create') }}" class="btn btn-primary btn-sm">
            ➕ Ajukan Surat Baru
        </a>
    </div>
</div>

<div class="card" style="padding: 1.25rem;">
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Tanggal Ajuan</th>
                    <th>Jenis &amp; Perihal Surat</th>
                    <th>Instansi Tujuan</th>
                    <th>Status Disposisi</th>
                    <th style="text-align: center;">Dokumen &amp; Verifikasi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($daftarPengajuan as $item)
                    <tr>
                        <td style="font-size: 0.82rem; color: var(--text-muted); white-space: nowrap;">
                            {{ $item->created_at?->format('d M Y') }}
                            <div style="font-size: 0.74rem;">{{ $item->created_at?->format('H:i') }} WIB</div>
                        </td>
                        <td>
                            <div style="font-weight: 700; color: #1c2b20;">{{ $item->jenis_surat->label() }}</div>
                            <div style="font-size: 0.83rem; color: var(--text-muted); margin-top: 0.15rem;">{{ $item->perihal }}</div>
                            @if ($item->nomor_surat_terbit)
                                <div style="font-size: 0.78rem; margin-top: 0.25rem; font-family: monospace; color: var(--primary-dark);">
                                    No: {{ $item->nomor_surat_terbit }}
                                </div>
                            @endif
                        </td>
                        <td style="font-size: 0.84rem;">
                            <strong>{{ $item->tujuan_instansi }}</strong>
                            @if ($item->alamat_instansi)
                                <div style="font-size: 0.75rem; color: var(--text-muted);">{{ $item->alamat_instansi }}</div>
                            @endif
                            @if ($item->tanggal_mulai)
                                <div style="font-size: 0.74rem; color: var(--text-muted); margin-top: 0.2rem;">
                                    🗓️ {{ $item->tanggal_mulai->format('d/m/Y') }}
                                    @if ($item->tanggal_selesai)
                                        - {{ $item->tanggal_selesai->format('d/m/Y') }}
                                    @endif
                                </div>
                            @endif
                        </td>
                        <td>
                            <span class="badge {{ $item->status->badgeClass() }}">{{ $item->status->label() }}</span>
                            @if ($item->catatan_penolakan)
                                <div style="font-size: 0.76rem; color: #991b1b; margin-top: 0.3rem; max-width: 240px;">
                                    Catatan: {{ $item->catatan_penolakan }}
                                </div>
                            @endif
                        </td>
                        <td style="text-align: center;">
                            <div style="display: flex; flex-direction: column; gap: 0.35rem; align-items: center;">
                                @if ($item->file_surat_terbit)
                                    <a href="{{ route('dokumen.download', base64_encode($item->file_surat_terbit)) }}" class="btn btn-primary btn-sm">
                                        📄 Unduh Surat Resmi
                                    </a>
                                @endif
                                @if (config('fpst.surat_internal.qr_verifikasi_enabled') && $item->verification_token)
                                    <a href="{{ route('surat.verifikasi', $item->verification_token) }}" target="_blank" class="btn btn-secondary btn-sm">
                                        🔍 Cek Verifikasi QR
                                    </a>
                                @endif
                                @if ($item->file_pendukung)
                                    <a href="{{ route('dokumen.download', base64_encode($item->file_pendukung)) }}" class="btn btn-secondary btn-sm" style="font-size: 0.74rem;">
                                        📎 Berkas Pendukung
                                    </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 2.25rem; color: var(--text-muted);">
                            Belum ada pengajuan surat internal. Klik <strong>Ajukan Surat Baru</strong> untuk membuat permohonan.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top: 1rem;">
        {{ $daftarPengajuan->links() }}
    </div>
</div>
@endsection
