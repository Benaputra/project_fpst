@extends('layouts.app')

@section('title', 'Praktik Lapang (PL) & Katalog Mitra - FPST UPB')
@section('page_title', 'Praktik Lapang (PL) & Katalog Tempat PL')

@section('content')
<div class="alert alert-info" style="margin-bottom: 1.25rem;">
    <span>ℹ️</span>
    <div>
        <strong>Ketentuan Praktik Lapang (PL) FPST:</strong>
        Syarat minimal untuk mendaftar Praktik Lapang adalah <strong>{{ $minSks }} SKS</strong>. Anda dapat memilih instansi mitra terverifikasi di bawah ini atau mengusulkan instansi baru (perkebunan/balai penelitian/perusahaan agribisnis/kelompok tani) untuk diverifikasi program studi.
    </div>
</div>

<!-- Form Pendaftaran PL & Katalog Tempat PL -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap: 1.25rem; margin-bottom: 1.5rem;">
    <!-- Form Daftar PL -->
    <div class="card" style="padding: 1.25rem;">
        <div class="card-header" style="margin-bottom: 1rem;">
            <h2 class="card-title">Daftar Praktik Lapang (PL)</h2>
        </div>

        <form method="POST" action="{{ route('mahasiswa.pl.daftar') }}" enctype="multipart/form-data">
            @csrf

            <div style="display: grid; grid-template-columns: 1fr 120px; gap: 0.75rem;">
                <div class="form-group">
                    <label class="form-label" for="periode_akademik">Periode Akademik *</label>
                    <input id="periode_akademik" type="text" name="periode_akademik" class="form-control" value="{{ old('periode_akademik', $periodeAktif) }}" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="sks_ditempuh">SKS Ditempuh *</label>
                    <input id="sks_ditempuh" type="number" min="0" max="200" name="sks_ditempuh" class="form-control" value="{{ old('sks_ditempuh', $minSks) }}" required>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="judul_kegiatan">Rencana Topik / Judul Kegiatan PL *</label>
                <input id="judul_kegiatan" type="text" name="judul_kegiatan" class="form-control" value="{{ old('judul_kegiatan') }}" placeholder="Contoh: Manajemen Pemeliharaan Kelapa Sawit / Analisis Rantai Pasok Agribisnis..." required>
            </div>

            <div class="form-group">
                <label class="form-label" for="lokasi_pl_id">Pilih Mitra Terdaftar (Katalog PL FPST)</label>
                <select id="lokasi_pl_id" name="lokasi_pl_id" class="form-control">
                    <option value="">-- Usulkan Tempat PL Baru di bawah --</option>
                    @foreach ($daftarLokasi as $lok)
                        @php
                            $sisa = max(0, (int) $lok->kuota_periode - (int) $lok->terisi_periode_count);
                        @endphp
                        <option value="{{ $lok->id }}" {{ (string) old('lokasi_pl_id') === (string) $lok->id ? 'selected' : '' }}>
                            {{ $lok->nama_instansi }} [{{ $lok->sektor }}] — Sisa {{ $sisa }}/{{ $lok->kuota_periode }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div id="box-lokasi-baru" style="border: 1px dashed var(--border); border-radius: 0.65rem; padding: 0.9rem; margin-bottom: 1rem; background: #f8faf8;">
                <div style="font-size: 0.8rem; font-weight: 700; color: var(--primary-dark); margin-bottom: 0.6rem;">Atau Usulkan Tempat PL Baru (Jika Tidak Memilih Mitra):</div>
                <div class="form-group">
                    <label class="form-label" style="font-size: 0.78rem;">Nama Instansi / Perusahaan / Kelompok Tani</label>
                    <input type="text" name="nama_instansi_baru" class="form-control" value="{{ old('nama_instansi_baru') }}">
                </div>
                <div style="display: grid; grid-template-columns: 120px 1fr; gap: 0.6rem;">
                    <div class="form-group">
                        <label class="form-label" style="font-size: 0.78rem;">Sektor Prodi</label>
                        <select name="sektor_baru" class="form-control">
                            <option value="AGT">AGT</option>
                            <option value="AGB">AGB</option>
                            <option value="AGT/AGB" selected>AGT/AGB</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" style="font-size: 0.78rem;">Kabupaten / Kota</label>
                        <input type="text" name="kabupaten_kota_baru" class="form-control" value="{{ old('kabupaten_kota_baru') }}">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label" style="font-size: 0.78rem;">Alamat Lengkap</label>
                    <input type="text" name="alamat_baru" class="form-control" value="{{ old('alamat_baru') }}">
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.6rem;">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label" style="font-size: 0.78rem;">Nama PIC Lapang</label>
                        <input type="text" name="kontak_pic_baru" class="form-control" value="{{ old('kontak_pic_baru') }}">
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label" style="font-size: 0.78rem;">Telepon PIC</label>
                        <input type="text" name="telepon_pic_baru" class="form-control" value="{{ old('telepon_pic_baru') }}">
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="file_transkrip_sks">Transkrip Nilai / Bukti SKS (PDF, Opsional)</label>
                <input id="file_transkrip_sks" type="file" name="file_transkrip_sks" class="form-control" accept=".pdf">
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%;">+ Daftar Praktik Lapang</button>
        </form>
    </div>

    <!-- Katalog Tempat PL Mitra FPST -->
    <div class="card" style="padding: 1.25rem;">
        <div class="card-header" style="margin-bottom: 1rem;">
            <h2 class="card-title">Katalog Tempat PL Terverifikasi ({{ $periodeAktif }})</h2>
        </div>

        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Instansi / Sektor</th>
                        <th>Lokasi &amp; PIC</th>
                        <th style="text-align: center;">Kuota</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($daftarLokasi as $lok)
                        @php
                            $sisa = max(0, (int) $lok->kuota_periode - (int) $lok->terisi_periode_count);
                        @endphp
                        <tr>
                            <td>
                                <strong>{{ $lok->nama_instansi }}</strong>
                                <div><span class="badge badge-diproses">{{ $lok->sektor }}</span></div>
                            </td>
                            <td style="font-size: 0.8rem;">
                                {{ $lok->alamat }}
                                @if ($lok->kabupaten_kota) ({{ $lok->kabupaten_kota }}) @endif
                                @if ($lok->kontak_pic)
                                    <div style="color: var(--text-muted);">PIC: {{ $lok->kontak_pic }}</div>
                                @endif
                            </td>
                            <td style="text-align: center;">
                                <strong>{{ $sisa }}/{{ $lok->kuota_periode }}</strong>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" style="text-align: center; color: var(--text-muted); padding: 1.5rem;">
                                Belum ada mitra PL terverifikasi. Anda dapat mengusulkan tempat PL baru pada formulir di samping.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($lokasiUsulanSaya->isNotEmpty())
            <div style="margin-top: 1rem; padding-top: 0.75rem; border-top: 1px solid var(--border);">
                <div style="font-size: 0.8rem; font-weight: 700; margin-bottom: 0.45rem;">Status Usulan Lokasi Saya:</div>
                @foreach ($lokasiUsulanSaya as $usulan)
                    <div style="font-size: 0.8rem; display: flex; justify-content: space-between; padding: 0.25rem 0;">
                        <span>{{ $usulan->nama_instansi }}</span>
                        <span class="badge {{ $usulan->status_verifikasi->badgeClass() }}">{{ $usulan->status_verifikasi->label() }}</span>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>

<!-- Daftar Riwayat & Progress PL Saya -->
@foreach ($riwayatPl as $pl)
    <div class="card" style="padding: 1.25rem; margin-bottom: 1.25rem;">
        <div class="card-header" style="margin-bottom: 1rem;">
            <div>
                <div style="font-size: 0.78rem; color: var(--text-muted); font-weight: 700;">
                    {{ $pl->periode_akademik }} • {{ $pl->sks_ditempuh }} SKS
                </div>
                <h2 class="card-title">{{ $pl->judul_kegiatan }}</h2>
                <div style="font-size: 0.84rem; color: #334155; margin-top: 0.2rem;">
                    📍 {{ $pl->lokasiPl?->nama_instansi }} • 👨‍🏫 DPL: <strong>{{ $pl->dpl?->name ?? 'Belum ditetapkan' }}</strong>
                </div>
            </div>
            <span class="badge {{ $pl->status->badgeClass() }}">{{ $pl->status->label() }}</span>
        </div>

        <!-- Tahapan Aksi Berkas & Surat -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem; margin-bottom: 1rem;">
            <!-- 1. Surat Pengantar (Integrasi Modul 1) -->
            <div style="border: 1px solid var(--border); border-radius: 0.5rem; padding: 0.9rem;">
                <div style="font-size: 0.76rem; font-weight: 700; text-transform: uppercase; color: var(--text-muted);">1. Surat Pengantar PL (Modul Persuratan)</div>
                @if ($pl->pengajuanSurat)
                    <div style="font-size: 0.82rem; margin: 0.4rem 0;">
                        Status: <strong>{{ $pl->pengajuanSurat->status->label() }}</strong>
                        @if ($pl->pengajuanSurat->nomor_surat_terbit)
                            <div>No: <code>{{ $pl->pengajuanSurat->nomor_surat_terbit }}</code></div>
                        @endif
                    </div>
                    @if ($pl->pengajuanSurat->file_surat_terbit)
                        <a href="{{ route('dokumen.download', base64_encode($pl->pengajuanSurat->file_surat_terbit)) }}" class="btn btn-secondary btn-sm">📥 Unduh Surat Pengantar</a>
                    @endif
                @else
                    <p style="font-size: 0.78rem; color: var(--text-muted); margin: 0.4rem 0;">Ajukan Surat Pengantar PL otomatis setelah lokasi terverifikasi.</p>
                    <form method="POST" action="{{ route('mahasiswa.pl.surat-pengantar', $pl) }}">
                        @csrf
                        <button type="submit" class="btn btn-primary btn-sm">✉️ Ajukan Surat Pengantar PL</button>
                    </form>
                @endif
            </div>

            <!-- 2. Surat Balasan Instansi -->
            <div style="border: 1px solid var(--border); border-radius: 0.5rem; padding: 0.9rem;">
                <div style="font-size: 0.76rem; font-weight: 700; text-transform: uppercase; color: var(--text-muted);">2. Surat Balasan Instansi</div>
                @if ($pl->file_surat_balasan)
                    <div style="margin: 0.45rem 0;">
                        <a href="{{ route('dokumen.download', base64_encode($pl->file_surat_balasan)) }}" class="btn btn-secondary btn-sm">📄 Lihat Surat Balasan</a>
                    </div>
                @endif
                @if ($pl->status !== \App\Enums\StatusPraktikLapang::Selesai && $pl->status !== \App\Enums\StatusPraktikLapang::Ditolak)
                    <form method="POST" action="{{ route('mahasiswa.pl.balasan', $pl) }}" enctype="multipart/form-data" style="margin-top: 0.4rem;">
                        @csrf
                        <input type="file" name="file_surat_balasan" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required style="margin-bottom: 0.4rem;">
                        <button type="submit" class="btn btn-secondary btn-sm">📤 Unggah Surat Balasan</button>
                    </form>
                @endif
            </div>

            <!-- 3. SK DPL & Laporan Akhir -->
            <div style="border: 1px solid var(--border); border-radius: 0.5rem; padding: 0.9rem;">
                <div style="font-size: 0.76rem; font-weight: 700; text-transform: uppercase; color: var(--text-muted);">3. SK DPL &amp; Laporan Akhir</div>
                <div style="font-size: 0.82rem; margin: 0.35rem 0;">
                    SK DPL: <strong>{{ $pl->nomor_sk_dpl ?? 'Belum diterbitkan' }}</strong>
                    @if ($pl->file_sk_dpl)
                        <div style="margin-top: 0.25rem;">
                            <a href="{{ route('dokumen.download', base64_encode($pl->file_sk_dpl)) }}" class="btn btn-secondary btn-sm">📥 Unduh SK DPL</a>
                        </div>
                    @endif
                </div>
                @if ($pl->file_laporan_akhir)
                    <div style="margin-top: 0.35rem;">
                        <a href="{{ route('dokumen.download', base64_encode($pl->file_laporan_akhir)) }}" class="btn btn-secondary btn-sm">📘 Laporan Akhir Terunggah</a>
                    </div>
                @endif
                @if (in_array($pl->status, [\App\Enums\StatusPraktikLapang::Berjalan, \App\Enums\StatusPraktikLapang::SeminarTerjadwal], true))
                    <form method="POST" action="{{ route('mahasiswa.pl.laporan', $pl) }}" enctype="multipart/form-data" style="margin-top: 0.45rem;">
                        @csrf
                        <input type="file" name="file_laporan_akhir" class="form-control" accept=".pdf" required style="margin-bottom: 0.35rem;">
                        <button type="submit" class="btn btn-primary btn-sm">📤 Unggah Laporan Akhir (PDF)</button>
                    </form>
                @endif
            </div>
        </div>

        <!-- Seminar PL & Nilai / Sertifikat -->
        @if ($pl->tgl_seminar_pl || $pl->nilai_akhir !== null)
            <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 0.5rem; padding: 0.9rem; margin-bottom: 1rem;">
                @if ($pl->tgl_seminar_pl)
                    <div style="font-size: 0.84rem; color: #166534;">
                        🗓️ <strong>Jadwal Seminar PL:</strong> {{ $pl->tgl_seminar_pl->translatedFormat('d F Y') }} • {{ $pl->jam_seminar_pl }} • {{ $pl->ruangan_seminar_pl }}
                    </div>
                @endif
                @if ($pl->nilai_akhir !== null)
                    <div style="font-size: 0.88rem; color: #065f46; margin-top: 0.4rem;">
                        🏆 <strong>Nilai Akhir PL: {{ number_format($pl->nilai_akhir, 2) }}</strong>
                        (Pembimbing Lapang: {{ $pl->nilai_pembimbing_lapang }}, DPL: {{ $pl->nilai_dpl }})
                        @if ($pl->file_sertifikat)
                            &mdash; <a href="{{ route('dokumen.download', base64_encode($pl->file_sertifikat)) }}" class="btn btn-success btn-sm">📥 Unduh Sertifikat PL</a>
                        @endif
                    </div>
                @endif
            </div>
        @endif

        <!-- Logbook Kegiatan PL -->
        @if (in_array($pl->status, [\App\Enums\StatusPraktikLapang::Berjalan, \App\Enums\StatusPraktikLapang::SeminarTerjadwal, \App\Enums\StatusPraktikLapang::Selesai], true))
            <div style="border-top: 1px solid var(--border); padding-top: 1rem;">
                <div style="font-size: 0.9rem; font-weight: 700; margin-bottom: 0.65rem;">Logbook Kegiatan Harian PL ({{ $pl->logbooks->count() }} entri)</div>

                @if ($pl->status !== \App\Enums\StatusPraktikLapang::Selesai)
                    <form method="POST" action="{{ route('mahasiswa.pl.logbook', $pl) }}" enctype="multipart/form-data" style="background: #f8fafc; border: 1px solid var(--border); border-radius: 0.5rem; padding: 0.85rem; margin-bottom: 0.85rem;">
                        @csrf
                        <div style="display: grid; grid-template-columns: 160px 1fr; gap: 0.65rem;">
                            <div class="form-group">
                                <label class="form-label" style="font-size: 0.76rem;">Tanggal *</label>
                                <input type="date" name="tanggal" class="form-control" value="{{ date('Y-m-d') }}" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label" style="font-size: 0.76rem;">Ringkasan Aktivitas *</label>
                                <input type="text" name="aktivitas" class="form-control" placeholder="Observasi pemeliharaan pembibitan / wawancara petani..." required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label" style="font-size: 0.76rem;">Uraian Kegiatan *</label>
                            <textarea name="uraian" class="form-control" rows="2" required></textarea>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center; gap: 0.75rem; flex-wrap: wrap;">
                            <input type="file" name="file_dokumentasi" class="form-control" accept=".pdf,.jpg,.jpeg,.png" style="max-width: 280px;">
                            <button type="submit" class="btn btn-primary btn-sm">+ Simpan Entri Logbook</button>
                        </div>
                    </form>
                @endif

                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th style="width: 115px;">Tanggal</th>
                                <th>Aktivitas &amp; Uraian</th>
                                <th style="width: 160px; text-align: center;">Verifikasi DPL</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($pl->logbooks as $log)
                                <tr>
                                    <td>{{ $log->tanggal->format('d/m/Y') }}</td>
                                    <td>
                                        <strong>{{ $log->aktivitas }}</strong>
                                        <div style="font-size: 0.8rem; color: var(--text-muted);">{{ $log->uraian }}</div>
                                    </td>
                                    <td style="text-align: center;">
                                        @if ($log->diverifikasi_dpl)
                                            <span class="badge badge-selesai">✓ Terverifikasi</span>
                                            @if ($log->catatan_dpl)
                                                <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 0.2rem;">{{ $log->catatan_dpl }}</div>
                                            @endif
                                        @else
                                            <span class="badge badge-diajukan">Menunggu DPL</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" style="text-align: center; color: var(--text-muted);">Belum ada catatan logbook.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
@endforeach
@endsection
