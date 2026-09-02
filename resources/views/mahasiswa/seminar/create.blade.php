@extends('layouts.app')

@section('title', 'Pendaftaran Seminar Skripsi')
@section('page_title', 'Formulir Pendaftaran Seminar Skripsi')

@section('content')

<div style="max-width: 800px; margin: 0 auto;">
    <div class="card">
        <div class="card-header">
            <div>
                <h2 class="card-title">Pendaftaran Seminar & Upload Berkas Syarat</h2>
                <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.2rem;">
                    Judul: <strong>"{{ $skripsi->judul }}"</strong>
                </div>
            </div>
            <a href="{{ route('mahasiswa.seminar.index') }}" class="btn btn-secondary btn-sm">Kembali</a>
        </div>

        <form method="POST" action="{{ route('mahasiswa.seminar.store') }}" enctype="multipart/form-data">
            @csrf

            <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 0.5rem; padding: 0.85rem 1rem; margin-bottom: 1.25rem; font-size: 0.85rem; color: #166534;">
                <div><strong>SK Bimbingan Aktif:</strong> {{ $skripsi->nomor_sk_bimbingan }}</div>
                <div style="margin-top: 0.2rem;"><strong>Pembimbing 1:</strong> {{ $skripsi->pembimbing1 ? $skripsi->pembimbing1->name : '-' }} | <strong>Pembimbing 2:</strong> {{ $skripsi->pembimbing2 ? $skripsi->pembimbing2->name : '-' }}</div>
            </div>

            <h3 style="font-size: 0.95rem; font-weight: 700; color: #142017; margin-bottom: 0.35rem;">Unggah 4 Berkas Persyaratan Seminar:</h3>
            <p style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 1.25rem;">Semua berkas di bawah ini wajib diunggah untuk kelengkapan administrasi.</p>

            <div class="form-group">
                <label for="file_naskah_seminar" class="form-label">1. File Naskah Proposal Lengkap (PDF, Maks 10MB) <span style="color: #dc2626;">*</span></label>
                <input type="file" id="file_naskah_seminar" name="file_naskah_seminar" class="form-control" accept=".pdf" required>
                @error('file_naskah_seminar')
                    <div class="form-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="file_acc_pembimbing" class="form-label">2. File Lembar Persetujuan (ACC) Seminar dari Pembimbing <span style="color: #dc2626;">*</span></label>
                <input type="file" id="file_acc_pembimbing" name="file_acc_pembimbing" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required>
                <div class="form-help">Scan atau foto lembar persetujuan yang telah ditandatangani pembimbing.</div>
                @error('file_acc_pembimbing')
                    <div class="form-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="file_bukti_bayar_seminar" class="form-label">3. File Bukti Pembayaran Seminar Skripsi <span style="color: #dc2626;">*</span></label>
                <input type="file" id="file_bukti_bayar_seminar" name="file_bukti_bayar_seminar" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required>
                @error('file_bukti_bayar_seminar')
                    <div class="form-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="file_toefl" class="form-label">4. File Sertifikat TOEFL / Kemampuan Bahasa <span style="color: #dc2626;">*</span></label>
                <input type="file" id="file_toefl" name="file_toefl" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required>
                @error('file_toefl')
                    <div class="form-error">{{ $message }}</div>
                @enderror
            </div>

            <div style="display: flex; flex-wrap: wrap; justify-content: flex-end; gap: 0.75rem; margin-top: 1.5rem; border-top: 1px solid var(--border); padding-top: 1.25rem;">
                <a href="{{ route('mahasiswa.seminar.index') }}" class="btn btn-secondary" style="flex: 1; max-width: 150px;">Batal</a>
                <button type="submit" class="btn btn-primary" style="flex: 2; min-width: 180px;">Kirim Pendaftaran Seminar</button>
            </div>
        </form>
    </div>
</div>

@endsection
