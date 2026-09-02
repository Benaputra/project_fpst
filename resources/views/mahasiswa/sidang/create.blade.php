@extends('layouts.app')

@section('title', 'Pendaftaran Sidang Skripsi')
@section('page_title', 'Formulir Pendaftaran Sidang Skripsi (Meja Hijau)')

@section('content')

<div style="max-width: 800px; margin: 0 auto;">
    <div class="card">
        <div class="card-header">
            <div>
                <h2 class="card-title">Pendaftaran Sidang Skripsi & Upload Berkas Syarat</h2>
                <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.2rem;">
                    Judul: <strong>"{{ $skripsi->judul }}"</strong>
                </div>
            </div>
            <a href="{{ route('mahasiswa.sidang.index') }}" class="btn btn-secondary btn-sm">Kembali</a>
        </div>

        <form method="POST" action="{{ route('mahasiswa.sidang.store') }}" enctype="multipart/form-data">
            @csrf

            <div style="background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 0.5rem; padding: 0.85rem 1rem; margin-bottom: 1.25rem; font-size: 0.85rem; color: #065f46;">
                <div><strong>Lulus Seminar:</strong> Nilai {{ number_format($skripsi->seminar->nilai_seminar, 2) }}</div>
                <div style="margin-top: 0.2rem;"><strong>Pembimbing 1:</strong> {{ $skripsi->pembimbing1 ? $skripsi->pembimbing1->name : '-' }} | <strong>Pembimbing 2:</strong> {{ $skripsi->pembimbing2 ? $skripsi->pembimbing2->name : '-' }}</div>
            </div>

            <h3 style="font-size: 0.95rem; font-weight: 700; color: #142017; margin-bottom: 0.35rem;">Unggah 4 Berkas Persyaratan Sidang Skripsi:</h3>
            <p style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 1.25rem;">Semua berkas di bawah ini wajib diunggah untuk kelengkapan berkas meja hijau.</p>

            <div class="form-group">
                <label for="file_naskah_sidang" class="form-label">1. File Naskah Skripsi Lengkap Final (PDF, Maks 15MB) <span style="color: #dc2626;">*</span></label>
                <input type="file" id="file_naskah_sidang" name="file_naskah_sidang" class="form-control" accept=".pdf" required>
                @error('file_naskah_sidang')
                    <div class="form-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="file_acc_sidang" class="form-label">2. File Lembar Persetujuan (ACC) Sidang dari Pembimbing <span style="color: #dc2626;">*</span></label>
                <input type="file" id="file_acc_sidang" name="file_acc_sidang" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required>
                <div class="form-help">Lembar persetujuan maju sidang yang telah ditandatangani pembimbing.</div>
                @error('file_acc_sidang')
                    <div class="form-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="file_bebas_revisi_seminar" class="form-label">3. File Bukti Bebas Revisi Seminar Skripsi <span style="color: #dc2626;">*</span></label>
                <input type="file" id="file_bebas_revisi_seminar" name="file_bebas_revisi_seminar" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required>
                <div class="form-help">Lembar pengesahan bahwa revisi seminar telah disetujui penguji/pembimbing.</div>
                @error('file_bebas_revisi_seminar')
                    <div class="form-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="file_bukti_bayar_sidang" class="form-label">4. File Bukti Pembayaran Sidang / SPP Lunas <span style="color: #dc2626;">*</span></label>
                <input type="file" id="file_bukti_bayar_sidang" name="file_bukti_bayar_sidang" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required>
                @error('file_bukti_bayar_sidang')
                    <div class="form-error">{{ $message }}</div>
                @enderror
            </div>

            <div style="display: flex; flex-wrap: wrap; justify-content: flex-end; gap: 0.75rem; margin-top: 1.5rem; border-top: 1px solid var(--border); padding-top: 1.25rem;">
                <a href="{{ route('mahasiswa.sidang.index') }}" class="btn btn-secondary" style="flex: 1; max-width: 150px;">Batal</a>
                <button type="submit" class="btn btn-primary" style="flex: 2; min-width: 180px;">Kirim Pendaftaran Sidang</button>
            </div>
        </form>
    </div>
</div>

@endsection
