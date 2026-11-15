@extends('layouts.app')

@section('title', 'Buat Pengajuan Surat Internal - FPST UPB')
@section('page_title', 'Formulir Pengajuan Surat Internal')

@section('content')
<div class="card" style="max-width: 780px; padding: 1.5rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem; padding-bottom: 0.85rem; border-bottom: 1px solid var(--border);">
        <div>
            <h2 style="font-size: 1.05rem; font-weight: 700;">Permohonan Surat Resmi Fakultas (FPST)</h2>
            <p style="font-size: 0.82rem; color: var(--text-muted);">
                Program Studi: <strong>{{ $user->programStudi?->nama ?? 'FPST' }} ({{ $user->programStudi?->kode ?? '-' }})</strong> • NIM: <strong>{{ $user->nomor_induk ?: '-' }}</strong>
            </p>
        </div>
        <a href="{{ route('mahasiswa.surat-internal.index') }}" class="btn btn-secondary btn-sm">← Kembali</a>
    </div>

    <form method="POST" action="{{ route('mahasiswa.surat-internal.store') }}" enctype="multipart/form-data">
        @csrf

        <div class="form-group">
            <label for="jenis_surat" class="form-label">Jenis Surat yang Diajukan <span style="color: #dc2626;">*</span></label>
            <select id="jenis_surat" name="jenis_surat" class="form-control" required>
                <option value="">-- Pilih Jenis Surat --</option>
                @foreach ($jenisList as $jenis)
                    <option value="{{ $jenis->value }}" {{ old('jenis_surat') === $jenis->value ? 'selected' : '' }}>
                        {{ $jenis->label() }}
                    </option>
                @endforeach
            </select>
            @error('jenis_surat')
                <div class="form-error">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label for="perihal" class="form-label">Perihal / Judul Keperluan Surat <span style="color: #dc2626;">*</span></label>
            <input id="perihal" type="text" name="perihal" value="{{ old('perihal') }}" class="form-control" placeholder="Misal: Permohonan Izin Pengambilan Data Penelitian Skripsi" required>
            @error('perihal')
                <div class="form-error">{{ $message }}</div>
            @enderror
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.9rem;">
            <div class="form-group">
                <label for="tujuan_instansi" class="form-label">Tujuan Instansi / Lembaga / Lab <span style="color: #dc2626;">*</span></label>
                <input id="tujuan_instansi" type="text" name="tujuan_instansi" value="{{ old('tujuan_instansi') }}" class="form-control" placeholder="Misal: Kepala Balai Pengkajian Teknologi Pertanian Kalbar" required>
                @error('tujuan_instansi')
                    <div class="form-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="alamat_instansi" class="form-label">Alamat / Lokasi Instansi</label>
                <input id="alamat_instansi" type="text" name="alamat_instansi" value="{{ old('alamat_instansi') }}" class="form-control" placeholder="Kota / Kabupaten / Alamat singkat">
                @error('alamat_instansi')
                    <div class="form-error">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.9rem;">
            <div class="form-group">
                <label for="tanggal_mulai" class="form-label">Tanggal Mulai Kegiatan</label>
                <input id="tanggal_mulai" type="date" name="tanggal_mulai" value="{{ old('tanggal_mulai') }}" class="form-control">
                @error('tanggal_mulai')
                    <div class="form-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="tanggal_selesai" class="form-label">Tanggal Selesai Kegiatan</label>
                <input id="tanggal_selesai" type="date" name="tanggal_selesai" value="{{ old('tanggal_selesai') }}" class="form-control">
                @error('tanggal_selesai')
                    <div class="form-error">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="form-group">
            <label for="keterangan_kebutuhan" class="form-label">Uraian Kebutuhan / Topik Penelitian / Praktik Lapang <span style="color: #dc2626;">*</span></label>
            <textarea id="keterangan_kebutuhan" name="keterangan_kebutuhan" rows="4" class="form-control" style="height: auto;" placeholder="Jelaskan rincian kegiatan, objek pengamatan, atau kebutuhan penggunaan fasilitas laboratorium..." required>{{ old('keterangan_kebutuhan') }}</textarea>
            @error('keterangan_kebutuhan')
                <div class="form-error">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label for="file_pendukung" class="form-label">Lampiran Berkas Pendukung (Opsional, PDF/JPG/PNG maks. 5 MB)</label>
            <input id="file_pendukung" type="file" name="file_pendukung" accept=".pdf,.jpg,.jpeg,.png" class="form-control" style="height: auto; padding: 0.5rem;">
            @error('file_pendukung')
                <div class="form-error">{{ $message }}</div>
            @enderror
        </div>

        <div style="display: flex; justify-content: flex-end; gap: 0.65rem; margin-top: 1.25rem;">
            <a href="{{ route('mahasiswa.surat-internal.index') }}" class="btn btn-secondary">Batal</a>
            <button type="submit" class="btn btn-primary">Kirim Pengajuan Surat</button>
        </div>
    </form>
</div>
@endsection
