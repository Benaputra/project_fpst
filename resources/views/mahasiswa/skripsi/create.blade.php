@extends('layouts.app')

@section('title', 'Form Pengajuan Judul Skripsi')
@section('page_title', 'Formulir Pengajuan Judul Skripsi')

@section('content')

<div style="max-width: 800px; margin: 0 auto;">
    <div class="card">
        <div class="card-header">
            <div>
                <h2 class="card-title">Pengajuan Judul & Berkas Syarat Awal</h2>
                <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.2rem;">
                    Program Studi: <strong>{{ $user->programStudi ? $user->programStudi->nama : '-' }}</strong> | Mahasiswa: <strong>{{ $user->name }} ({{ $user->nomor_induk }})</strong>
                </div>
            </div>
            <a href="{{ route('mahasiswa.skripsi.index') }}" class="btn btn-secondary btn-sm">Kembali</a>
        </div>

        <form method="POST" action="{{ route('mahasiswa.skripsi.store') }}" enctype="multipart/form-data">
            @csrf

            <div class="form-group">
                <label for="judul" class="form-label">Rencana Judul Skripsi <span style="color: #dc2626;">*</span></label>
                <textarea id="judul" name="judul" class="form-control" rows="3" placeholder="Contoh: Rancang Bangun Sistem Informasi Pengelolaan Persediaan Menggunakan Algoritma XYZ..." required>{{ old('judul') }}</textarea>
                <div class="form-help">Gunakan Bahasa Indonesia baku dengan huruf kapital di setiap awal kata utama.</div>
                @error('judul')
                    <div class="form-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="abstrak" class="form-label">Sinopsis / Ringkasan Latar Belakang & Masalah</label>
                <textarea id="abstrak" name="abstrak" class="form-control" rows="5" placeholder="Tuliskan gambaran singkat mengenai latar belakang, rumusan masalah, dan tujuan penelitian yang akan dilakukan...">{{ old('abstrak') }}</textarea>
                <div class="form-help">Maksimal 5000 karakter.</div>
                @error('abstrak')
                    <div class="form-error">{{ $message }}</div>
                @enderror
            </div>

            <div style="border-top: 1px solid var(--border); margin: 1.5rem 0; padding-top: 1.25rem;">
                <h3 style="font-size: 0.95rem; font-weight: 700; color: #142017; margin-bottom: 0.35rem;">Unggah Berkas Persyaratan (PDF/Gambar, Maks 5MB)</h3>
                <p style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 1.25rem;">Pastikan dokumen yang diunggah jelas dan dapat dibaca dengan baik dari smartphone Anda.</p>

                <div class="form-group">
                    <label for="file_proposal" class="form-label">1. File Draf Proposal / Outline Skripsi (PDF) <span style="color: #dc2626;">*</span></label>
                    <input type="file" id="file_proposal" name="file_proposal" class="form-control" accept=".pdf" required>
                    @error('file_proposal')
                        <div class="form-error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="file_transkrip" class="form-label">2. File Transkrip Nilai Sementara (PDF) <span style="color: #dc2626;">*</span></label>
                    <input type="file" id="file_transkrip" name="file_transkrip" class="form-control" accept=".pdf" required>
                    @error('file_transkrip')
                        <div class="form-error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="file_bukti_bayar" class="form-label">3. File Bukti Pembayaran Skripsi (PDF / JPG / PNG) <span style="color: #dc2626;">*</span></label>
                    <input type="file" id="file_bukti_bayar" name="file_bukti_bayar" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required>
                    @error('file_bukti_bayar')
                        <div class="form-error">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div style="display: flex; flex-wrap: wrap; justify-content: flex-end; gap: 0.75rem; margin-top: 1.5rem; border-top: 1px solid var(--border); padding-top: 1.25rem;">
                <a href="{{ route('mahasiswa.skripsi.index') }}" class="btn btn-secondary" style="flex: 1; max-width: 150px;">Batal</a>
                <button type="submit" class="btn btn-primary" style="flex: 2; min-width: 180px;">Kirim Pengajuan Judul</button>
            </div>
        </form>
    </div>
</div>

@endsection
