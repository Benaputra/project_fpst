@extends('layouts.app')

@section('title', 'Pengajuan Judul Skripsi')

@section('content')
    <div class="eyebrow">Tahap pertama</div>
    <h1>Pengajuan Judul Skripsi</h1>
    <p class="lead">Ajukan satu judul skripsi dan pantau keputusan Ketua Program Studi dari halaman ini.</p>

    <div class="grid">
        @if (session('status'))
            <div class="notice notice--success" role="status">{{ session('status') }}</div>
        @endif

        @if (! $mahasiswa)
            <section class="card">
                <div class="notice notice--warning" role="alert">
                    Akun Anda belum terhubung dengan data mahasiswa. Hubungi Admin Prodi sebelum mengajukan judul.
                </div>
            </section>
        @else
            <section class="card" aria-labelledby="identitas-heading">
                <div class="card__header">
                    <h2 id="identitas-heading">Identitas mahasiswa</h2>
                </div>
                <dl class="meta">
                    <div>
                        <dt>Nama</dt>
                        <dd>{{ $mahasiswa->nama }}</dd>
                    </div>
                    <div>
                        <dt>NIM</dt>
                        <dd>{{ $mahasiswa->nim }}</dd>
                    </div>
                    <div>
                        <dt>Program studi</dt>
                        <dd>{{ $mahasiswa->programStudi->nama }}</dd>
                    </div>
                </dl>
            </section>

            @if (! $pengajuan)
                <section class="card" aria-labelledby="form-heading">
                    <div class="card__header">
                        <div>
                            <h2 id="form-heading">Ajukan judul pertama</h2>
                            <p class="field-help">NIM diambil otomatis dari akun Anda.</p>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('pengajuan-judul.store') }}">
                        @csrf
                        <div class="field">
                            <label for="judul">Judul skripsi</label>
                            <textarea id="judul" name="judul" maxlength="1000" required aria-describedby="judul-help judul-error">{{ old('judul') }}</textarea>
                            <p id="judul-help" class="field-help">Tuliskan judul lengkap dan spesifik. Maksimal 1.000 karakter.</p>
                            @error('judul')
                                <p id="judul-error" class="field-error" role="alert">{{ $message }}</p>
                            @enderror
                        </div>
                        <button class="button button--primary" type="submit">Ajukan judul</button>
                    </form>
                </section>
            @elseif ($pengajuan->status === \App\Enums\StatusPengajuanJudul::Ditolak)
                <section class="card" aria-labelledby="revision-heading">
                    <div class="card__header">
                        <h2 id="revision-heading">Perbaiki pengajuan</h2>
                        <span class="badge badge--danger">{{ $pengajuan->status->label() }}</span>
                    </div>
                    <div class="notice notice--danger" role="alert">
                        <strong>Alasan penolakan:</strong> {{ $pengajuan->catatan_reject }}
                    </div>
                    <form method="POST" action="{{ route('mahasiswa.pengajuan-judul.update') }}">
                        @csrf
                        @method('PUT')
                        <div class="field">
                            <label for="judul">Judul skripsi yang diperbaiki</label>
                            <textarea id="judul" name="judul" maxlength="1000" required aria-describedby="judul-help judul-error">{{ old('judul', $pengajuan->judul) }}</textarea>
                            <p id="judul-help" class="field-help">Perbaiki judul sesuai catatan Kaprodi.</p>
                            @error('judul')
                                <p id="judul-error" class="field-error" role="alert">{{ $message }}</p>
                            @enderror
                        </div>
                        <button class="button button--primary" type="submit">Ajukan ulang</button>
                    </form>
                </section>
            @else
                <section class="card" aria-labelledby="status-heading">
                    <div class="card__header">
                        <h2 id="status-heading">Status pengajuan</h2>
                        <span class="badge {{ $pengajuan->status === \App\Enums\StatusPengajuanJudul::Diajukan ? 'badge--waiting' : 'badge--success' }}">
                            {{ $pengajuan->status->label() }}
                        </span>
                    </div>
                    <p class="title-value">{{ $pengajuan->judul }}</p>
                    @if ($pengajuan->status === \App\Enums\StatusPengajuanJudul::Diajukan)
                        <p class="field-help">Judul sedang menunggu pemeriksaan Ketua Program Studi dan tidak dapat diubah.</p>
                    @else
                        <div class="notice notice--success" role="status" style="margin-top: 1.25rem; margin-bottom: 0;">
                            Judul telah diverifikasi pada {{ $pengajuan->diverifikasi_at?->translatedFormat('d F Y, H:i') }}.
                        </div>
                    @endif
                </section>
            @endif

            @if ($pengajuan?->skripsi)
                @foreach ($pengajuan->skripsi->kesediaanBimbingan as $kesediaan)
                    @php
                        $suratAktif = $kesediaan->surat
                            ->where('jenis_surat', \App\Enums\JenisSurat::KesediaanPembimbing)
                            ->where('status', \App\Enums\StatusSurat::Diterbitkan)
                            ->sortByDesc('versi')
                            ->first();
                        $dokumenTerakhir = $kesediaan->dokumenPengajuan
                            ->where('jenis', \App\Enums\JenisDokumenPengajuan::HasilKonsultasi)
                            ->sortByDesc('versi')
                            ->first();
                        $bolehUpload = in_array($kesediaan->status, [
                            \App\Enums\StatusKesediaanBimbingan::MenungguUpload,
                            \App\Enums\StatusKesediaanBimbingan::UploadTidakValid,
                        ], true);
                    @endphp
                    <section class="card" aria-labelledby="consultation-heading-{{ $kesediaan->id }}">
                        <div class="card__header">
                            <div>
                                <h2 id="consultation-heading-{{ $kesediaan->id }}">
                                    Konsultasi {{ $kesediaan->peran === \App\Enums\PeranKesediaanBimbingan::Pembimbing1 ? 'Pembimbing 1' : 'Pembimbing 2' }}
                                </h2>
                                <p class="field-help">Siklus {{ $kesediaan->siklus }} — {{ $kesediaan->dosen->nama }}</p>
                            </div>
                            <span class="badge {{ $kesediaan->status === \App\Enums\StatusKesediaanBimbingan::UploadTidakValid ? 'badge--danger' : 'badge--waiting' }}">
                                {{ str_replace('_', ' ', $kesediaan->status->value) }}
                            </span>
                        </div>

                        @if ($suratAktif)
                            <a class="button button--secondary" href="{{ route('surat.download', $suratAktif) }}">
                                Unduh surat kesediaan
                            </a>
                        @endif

                        @if ($kesediaan->status === \App\Enums\StatusKesediaanBimbingan::UploadTidakValid)
                            <div class="notice notice--danger" role="alert" style="margin-top: 1rem;">
                                <strong>Upload sebelumnya tidak valid.</strong>
                                {{ $kesediaan->catatan_verifikasi }}
                            </div>
                        @endif

                        @if ($bolehUpload)
                            <form method="POST" enctype="multipart/form-data"
                                action="{{ route('kesediaan-bimbingan.hasil-konsultasi.store', $kesediaan) }}">
                                @csrf
                                <div class="field">
                                    <label for="hasil-konsultasi-{{ $kesediaan->id }}">Scan hasil konsultasi</label>
                                    <input id="hasil-konsultasi-{{ $kesediaan->id }}" type="file"
                                        name="hasil_konsultasi" accept=".pdf,.jpg,.jpeg,.png" required>
                                    <p class="field-help">PDF, JPG, JPEG, atau PNG. Maksimal 5 MB. File disimpan privat.</p>
                                    @error('hasil_konsultasi')
                                        <p class="field-error" role="alert">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div class="field">
                                    <label for="catatan-mahasiswa-{{ $kesediaan->id }}">Catatan <span class="field-help">(opsional)</span></label>
                                    <textarea id="catatan-mahasiswa-{{ $kesediaan->id }}"
                                        name="catatan_mahasiswa" maxlength="2000">{{ old('catatan_mahasiswa') }}</textarea>
                                    @error('catatan_mahasiswa')
                                        <p class="field-error" role="alert">{{ $message }}</p>
                                    @enderror
                                </div>
                                <button class="button button--primary" type="submit">
                                    {{ $kesediaan->status === \App\Enums\StatusKesediaanBimbingan::UploadTidakValid ? 'Unggah perbaikan' : 'Unggah hasil konsultasi' }}
                                </button>
                            </form>
                        @elseif ($dokumenTerakhir)
                            <div class="notice notice--warning" style="margin-top: 1rem; margin-bottom: 0;">
                                Dokumen versi {{ $dokumenTerakhir->versi }} telah diunggah dan tidak dapat diganti pada status ini.
                                <a class="table-link" href="{{ route('dokumen-pengajuan.download', $dokumenTerakhir) }}">Unduh dokumen</a>
                            </div>
                        @endif
                    </section>
                @endforeach
            @endif
        @endif
    </div>
@endsection
