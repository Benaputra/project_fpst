@extends('layouts.app')

@section('title', 'Fase 1: Judul & SK Bimbingan')
@section('page_title', 'Tahap 1: Pengajuan Judul & SK Bimbingan')

@section('content')

@if (!$skripsi)
    <div class="card" style="text-align: center; padding: 3rem 1.5rem;">
        <div style="font-size: 3rem; margin-bottom: 1rem;">📝</div>
        <h2 style="font-size: 1.35rem; font-weight: 800; color: #0f172a; margin-bottom: 0.5rem;">Belum Ada Pengajuan Judul</h2>
        <p style="color: var(--text-muted); max-width: 500px; margin: 0 auto 1.5rem; font-size: 0.9rem;">
            Anda belum mengajukan judul skripsi. Silakan persiapkan draf proposal, transkrip nilai sementara, dan bukti pembayaran skripsi untuk memulai.
        </p>
        <a href="{{ route('mahasiswa.skripsi.create') }}" class="btn btn-primary">
            + Ajukan Judul Skripsi Baru
        </a>
    </div>
@else
    <!-- Status Banner -->
    <div class="card">
        <div class="card-header">
            <div>
                <span style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Status Pengajuan</span>
                <h2 class="card-title" style="margin-top: 0.2rem;">
                    Tahap 1: {{ $skripsi->status->label() }}
                </h2>
            </div>
            <span class="badge badge-{{ $skripsi->status->value }}">{{ $skripsi->status->label() }}</span>
        </div>

        @if ($skripsi->status->value === 'ditolak')
            <div class="alert alert-error" style="margin-bottom: 1rem;">
                <div>
                    <strong>Pengajuan Judul Ditolak / Perlu Revisi</strong>
                    <p style="margin-top: 0.25rem;">Catatan Kaprodi: {{ $skripsi->catatan ?? 'Silakan ajukan judul kembali sesuai arahan.' }}</p>
                    <div style="margin-top: 0.75rem;">
                        <a href="{{ route('mahasiswa.skripsi.create') }}" class="btn btn-danger btn-sm">Ajukan Judul Baru</a>
                    </div>
                </div>
            </div>
        @elseif ($skripsi->status->value === 'diajukan')
            <div class="alert alert-warning" style="margin-bottom: 1rem;">
                <span>⏳</span>
                <div>
                    <strong>Pengajuan Anda Sedang Menunggu Review Kaprodi</strong>
                    <p style="margin-top: 0.2rem;">Ketua Program Studi akan memeriksa judul Anda dan menetapkan Dosen Pembimbing 1 & 2.</p>
                </div>
            </div>
        @elseif ($skripsi->status->value === 'diproses')
            <div class="alert alert-info" style="margin-bottom: 1rem;">
                <span>ℹ️</span>
                <div>
                    <strong>Pembimbing Telah Ditetapkan! Menunggu Penerbitan SK</strong>
                    <p style="margin-top: 0.2rem;">Kaprodi telah menetapkan pembimbing. Admin program studi sedang memproses penerbitan Surat Keputusan (SK) Bimbingan.</p>
                </div>
            </div>
        @elseif ($skripsi->status->value === 'selesai')
            <div class="alert alert-success" style="margin-bottom: 1rem;">
                <span>🎉</span>
                <div>
                    <strong>SK Bimbingan Telah Terbit!</strong>
                    <p style="margin-top: 0.2rem;">Anda telah resmi memulai bimbingan skripsi. Silakan unduh dokumen SK di bawah ini dan lakukan proses bimbingan. Setelah siap, Anda dapat melanjutkan ke <strong>Tahap 2: Seminar</strong>.</p>
                </div>
            </div>
        @endif

        <div style="display: grid; grid-template-columns: 1fr; gap: 1.5rem; margin-top: 1rem;">
            <!-- Informasi Judul -->
            <div style="background: #f8fafc; border-radius: 0.5rem; padding: 1.25rem; border: 1px solid var(--border);">
                <div style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Judul Skripsi</div>
                <h3 style="font-size: 1.15rem; font-weight: 700; color: #0f172a; margin: 0.35rem 0 0.75rem;">
                    {{ $skripsi->judul }}
                </h3>
                @if ($skripsi->abstrak)
                    <div style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-top: 0.75rem;">Ringkasan / Abstrak</div>
                    <p style="font-size: 0.88rem; color: #334155; line-height: 1.6; margin-top: 0.25rem; white-space: pre-line;">
                        {{ $skripsi->abstrak }}
                    </p>
                @endif
            </div>

            <!-- Berkas Syarat Terunggah -->
            <div>
                <div style="font-size: 0.85rem; font-weight: 700; margin-bottom: 0.75rem; color: #334155;">Berkas Persyaratan yang Anda Unggah:</div>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 0.75rem;">
                    @if ($skripsi->file_proposal)
                        <a href="{{ route('dokumen.download', base64_encode($skripsi->file_proposal)) }}" class="btn btn-secondary btn-sm" style="justify-content: flex-start;">
                            <span>📄</span> Draf Proposal (PDF)
                        </a>
                    @endif
                    @if ($skripsi->file_transkrip)
                        <a href="{{ route('dokumen.download', base64_encode($skripsi->file_transkrip)) }}" class="btn btn-secondary btn-sm" style="justify-content: flex-start;">
                            <span>📊</span> Transkrip Nilai (PDF)
                        </a>
                    @endif
                    @if ($skripsi->file_bukti_bayar)
                        <a href="{{ route('dokumen.download', base64_encode($skripsi->file_bukti_bayar)) }}" class="btn btn-secondary btn-sm" style="justify-content: flex-start;">
                            <span>💳</span> Bukti Bayar Skripsi
                        </a>
                    @endif
                </div>
            </div>

            <!-- Penetapan Pembimbing & Dokumen SK -->
            <div style="border-top: 1px solid var(--border); padding-top: 1.25rem;">
                <div style="font-size: 0.95rem; font-weight: 700; margin-bottom: 0.75rem;">Dosen Pembimbing & Dokumen SK Bimbingan</div>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1rem; margin-bottom: 1.25rem;">
                    <div style="background: #fff; border: 1px solid var(--border); border-radius: 0.5rem; padding: 1rem;">
                        <span style="font-size: 0.75rem; color: var(--text-muted); font-weight: 600;">PEMBIMBING UTAMA (1)</span>
                        <div style="font-weight: 700; font-size: 0.95rem; margin-top: 0.25rem;">
                            {{ $skripsi->pembimbing1 ? $skripsi->pembimbing1->name : 'Belum ditetapkan' }}
                        </div>
                    </div>

                    <div style="background: #fff; border: 1px solid var(--border); border-radius: 0.5rem; padding: 1rem;">
                        <span style="font-size: 0.75rem; color: var(--text-muted); font-weight: 600;">PEMBIMBING PENDAMPING (2)</span>
                        <div style="font-weight: 700; font-size: 0.95rem; margin-top: 0.25rem;">
                            {{ $skripsi->pembimbing2 ? $skripsi->pembimbing2->name : '-' }}
                        </div>
                    </div>
                </div>

                <!-- Box Dokumen SK -->
                <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 0.5rem; padding: 1.25rem; display: flex; align-items: center; justify-content: space-between; flex-wrap: gap: 1rem;">
                    <div>
                        <div style="font-size: 0.75rem; font-weight: 700; color: #166534; text-transform: uppercase;">Surat Keputusan (SK) Bimbingan</div>
                        <div style="font-size: 1.05rem; font-weight: 800; color: #14532d; margin-top: 0.2rem;">
                            {{ $skripsi->nomor_sk_bimbingan ?? 'Nomor SK belum diterbitkan' }}
                        </div>
                        @if ($skripsi->tgl_sk_bimbingan)
                            <div style="font-size: 0.78rem; color: #166534; margin-top: 0.15rem;">
                                Tanggal Terbit: {{ $skripsi->tgl_sk_bimbingan->translatedFormat('d F Y') }}
                            </div>
                        @endif
                    </div>

                    @if ($skripsi->file_sk_bimbingan)
                        <a href="{{ route('dokumen.download', base64_encode($skripsi->file_sk_bimbingan)) }}" class="btn btn-success">
                            <span>📥</span> Unduh Berkas SK Bimbingan (PDF)
                        </a>
                    @elseif ($skripsi->isSelesai())
                        <span class="badge badge-selesai">SK Bimbingan Resmi Berlaku</span>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endif

@endsection
