@extends('layouts.app')

@section('title', 'Administrasi Skripsi, Jadwal & SK')
@section('page_title', 'Administrasi Surat & SK Skripsi')

@section('content')

<div class="card">
    <!-- Filter Prodi & Status -->
    <form method="GET" action="{{ route('admin.administrasi.index') }}" style="display: flex; flex-wrap: wrap; gap: 0.75rem; align-items: flex-end; margin-bottom: 1.5rem; padding-bottom: 1.25rem; border-bottom: 1px solid var(--border);">
        <div style="flex: 1; min-width: 200px;">
            <label class="form-label" style="font-size: 0.75rem;">Program Studi</label>
            <select name="prodi_id" class="form-control" style="font-size: 0.85rem;" onchange="this.form.submit()">
                <option value="">-- Semua Program Studi --</option>
                @foreach ($daftarProdi as $prodi)
                    <option value="{{ $prodi->id }}" {{ $prodiFilter == $prodi->id ? 'selected' : '' }}>
                        {{ $prodi->nama }} ({{ $prodi->kode }})
                    </option>
                @endforeach
            </select>
        </div>

        <div style="flex: 1; min-width: 180px;">
            <label class="form-label" style="font-size: 0.75rem;">Status</label>
            <select name="status" class="form-control" style="font-size: 0.85rem;" onchange="this.form.submit()">
                <option value="">-- Semua Status --</option>
                <option value="diajukan" {{ $statusFilter === 'diajukan' ? 'selected' : '' }}>Diajukan</option>
                <option value="diproses" {{ $statusFilter === 'diproses' ? 'selected' : '' }}>Diproses</option>
                <option value="selesai" {{ $statusFilter === 'selesai' ? 'selected' : '' }}>Selesai</option>
                <option value="ditolak" {{ $statusFilter === 'ditolak' ? 'selected' : '' }}>Ditolak</option>
            </select>
        </div>

        <a href="{{ route('admin.administrasi.index') }}" class="btn btn-secondary btn-sm" style="height: 38px;">Reset Filter</a>
    </form>

    <!-- Tab Navigasi -->
    <div class="tab-nav">
        <button type="button" class="tab-btn active" onclick="switchTab(event, 'tab-sk-bimbingan')">
            1. Penerbitan SK Bimbingan ({{ $daftarSkripsi->total() }})
        </button>
        <button type="button" class="tab-btn" onclick="switchTab(event, 'tab-admin-seminar')">
            2. Jadwal & Dokumen Seminar ({{ $daftarSeminar->total() }})
        </button>
        <button type="button" class="tab-btn" onclick="switchTab(event, 'tab-admin-sidang')">
            3. Jadwal & Dokumen Sidang ({{ $daftarSidang->total() }})
        </button>
    </div>

    <!-- TAB 1: PENERBITAN SK BIMBINGAN -->
    <div id="tab-sk-bimbingan" class="tab-content active">
        <div style="font-size: 0.88rem; color: var(--text-muted); margin-bottom: 1.25rem;">
            Masukkan Nomor SK Bimbingan resmi, tanggal terbit, dan unggah file PDF SK untuk menyelesaikan penetapan bimbingan.
        </div>

        <div style="display: flex; flex-direction: column; gap: 1.25rem;">
            @forelse ($daftarSkripsi as $skripsi)
                <div style="border: 1px solid var(--border); border-radius: 0.65rem; padding: 1.25rem; background: #fff;">
                    <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; margin-bottom: 0.5rem;">
                        <div>
                            <div style="font-size: 0.78rem; font-weight: 700; color: var(--text-muted);">
                                {{ $skripsi->programStudi ? $skripsi->programStudi->nama : '-' }} &bull; {{ $skripsi->mahasiswa->nomor_induk }} &bull; {{ $skripsi->mahasiswa->name }}
                            </div>
                            <h3 style="font-size: 1.05rem; font-weight: 700; color: #0f172a; margin-top: 0.2rem;">
                                "{{ $skripsi->judul }}"
                            </h3>
                            <div style="font-size: 0.82rem; color: #475569; margin-top: 0.25rem;">
                                Pembimbing: 1. <strong>{{ $skripsi->pembimbing1 ? $skripsi->pembimbing1->name : 'Belum ditentukan Kaprodi' }}</strong>
                                | 2. <strong>{{ $skripsi->pembimbing2 ? $skripsi->pembimbing2->name : '-' }}</strong>
                            </div>
                        </div>
                        <span class="badge badge-{{ $skripsi->status->value }}">{{ $skripsi->status->label() }}</span>
                    </div>

                    <!-- Form Input No SK & Upload SK Bimbingan -->
                    <div style="background: #f8fafc; border: 1px solid var(--border); border-radius: 0.5rem; padding: 1rem; margin-top: 0.85rem;">
                        <form method="POST" action="{{ route('admin.skripsi.sk-bimbingan', $skripsi->id) }}" enctype="multipart/form-data">
                            @csrf
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 0.75rem; align-items: flex-end;">
                                <div class="form-group" style="margin-bottom: 0;">
                                    <label class="form-label" style="font-size: 0.75rem;">Nomor SK Bimbingan *</label>
                                    <input type="text" name="nomor_sk_bimbingan" value="{{ old('nomor_sk_bimbingan', $skripsi->nomor_sk_bimbingan) }}" class="form-control" placeholder="Contoh: SK/001/FPST/TI/2026" required>
                                </div>

                                <div class="form-group" style="margin-bottom: 0;">
                                    <label class="form-label" style="font-size: 0.75rem;">Tanggal Terbit SK *</label>
                                    <input type="date" name="tgl_sk_bimbingan" value="{{ old('tgl_sk_bimbingan', $skripsi->tgl_sk_bimbingan ? $skripsi->tgl_sk_bimbingan->format('Y-m-d') : date('Y-m-d')) }}" class="form-control" required>
                                </div>

                                <div class="form-group" style="margin-bottom: 0;">
                                    <label class="form-label" style="font-size: 0.75rem;">Upload File PDF SK (Opsional)</label>
                                    <input type="file" name="file_sk_bimbingan" class="form-control" accept=".pdf">
                                </div>

                                <div>
                                    <button type="submit" class="btn btn-primary btn-sm" style="width: 100%; height: 38px;">
                                        ✓ Simpan & Terbitkan SK
                                    </button>
                                </div>
                            </div>
                        </form>

                        @if ($skripsi->file_sk_bimbingan)
                            <div style="margin-top: 0.5rem; font-size: 0.8rem;">
                                <a href="{{ route('dokumen.download', base64_encode($skripsi->file_sk_bimbingan)) }}" style="color: #2563eb; font-weight: 600;">
                                    📥 Unduh Berkas SK Bimbingan Terunggah
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            @empty
                <div style="text-align: center; color: var(--text-muted); padding: 3rem;">
                    Belum ada data pengajuan skripsi untuk ditampilkan.
                </div>
            @endforelse
        </div>

        <div style="margin-top: 1rem;">
            {{ $daftarSkripsi->links() }}
        </div>
    </div>

    <!-- TAB 2: JADWAL & DOKUMEN SEMINAR -->
    <div id="tab-admin-seminar" class="tab-content">
        <div style="font-size: 0.88rem; color: var(--text-muted); margin-bottom: 1.25rem;">
            Atur jadwal tanggal/jam/ruang seminar, nomor surat undangan, nomor SK seminar, serta input nilai kelulusan seminar.
        </div>

        <div style="display: flex; flex-direction: column; gap: 1.25rem;">
            @forelse ($daftarSeminar as $seminar)
                @php $sk = $seminar->pengajuanSkripsi; @endphp
                <div style="border: 1px solid var(--border); border-radius: 0.65rem; padding: 1.25rem; background: #fff;">
                    <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; margin-bottom: 0.5rem;">
                        <div>
                            <div style="font-size: 0.78rem; font-weight: 700; color: var(--text-muted);">
                                {{ $sk->mahasiswa->nomor_induk }} &bull; {{ $sk->mahasiswa->name }}
                            </div>
                            <h3 style="font-size: 1.05rem; font-weight: 700; color: #0f172a; margin-top: 0.2rem;">
                                "{{ $sk->judul }}"
                            </h3>
                            <div style="font-size: 0.82rem; color: #475569; margin-top: 0.25rem;">
                                Penguji Seminar: <strong>{{ $seminar->penguji ? $seminar->penguji->name : 'Belum ditetapkan Kaprodi' }}</strong>
                            </div>
                        </div>
                        <span class="badge badge-{{ $seminar->status->value }}">{{ $seminar->status->label() }}</span>
                    </div>

                    <!-- Form Input Jadwal & Surat/SK Seminar -->
                    <div style="background: #f8fafc; border: 1px solid var(--border); border-radius: 0.5rem; padding: 1rem; margin-top: 0.85rem;">
                        <div style="font-weight: 700; font-size: 0.82rem; margin-bottom: 0.5rem;">1. Pengaturan Jadwal & Dokumen Surat/SK:</div>
                        <form method="POST" action="{{ route('admin.seminar.jadwal-sk', $seminar->id) }}" enctype="multipart/form-data">
                            @csrf
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 0.75rem;">
                                <div class="form-group" style="margin-bottom: 0;">
                                    <label class="form-label" style="font-size: 0.75rem;">Tanggal Seminar *</label>
                                    <input type="date" name="tgl_seminar" value="{{ old('tgl_seminar', $seminar->tgl_seminar ? $seminar->tgl_seminar->format('Y-m-d') : '') }}" class="form-control" required>
                                </div>

                                <div class="form-group" style="margin-bottom: 0;">
                                    <label class="form-label" style="font-size: 0.75rem;">Jam Pelaksanaan *</label>
                                    <input type="text" name="jam_seminar" value="{{ old('jam_seminar', $seminar->jam_seminar ?? '09:00 - 10:30') }}" class="form-control" placeholder="09:00 - 10:30" required>
                                </div>

                                <div class="form-group" style="margin-bottom: 0;">
                                    <label class="form-label" style="font-size: 0.75rem;">Ruangan / Tempat *</label>
                                    <input type="text" name="ruangan" value="{{ old('ruangan', $seminar->ruangan ?? 'Ruang Seminar') }}" class="form-control" required>
                                </div>

                                <div class="form-group" style="margin-bottom: 0;">
                                    <label class="form-label" style="font-size: 0.75rem;">No. Undangan Seminar</label>
                                    <input type="text" name="nomor_undangan_seminar" value="{{ old('nomor_undangan_seminar', $seminar->nomor_undangan_seminar) }}" class="form-control" placeholder="UND/01/FPST/2026">
                                </div>

                                <div class="form-group" style="margin-bottom: 0;">
                                    <label class="form-label" style="font-size: 0.75rem;">No. SK Penguji Seminar</label>
                                    <input type="text" name="nomor_sk_seminar" value="{{ old('nomor_sk_seminar', $seminar->nomor_sk_seminar) }}" class="form-control" placeholder="SK-SEM/01/2026">
                                </div>
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr 1fr auto; gap: 0.75rem; margin-top: 0.75rem; align-items: flex-end;">
                                <div class="form-group" style="margin-bottom: 0;">
                                    <label class="form-label" style="font-size: 0.75rem;">Upload File Undangan (PDF)</label>
                                    <input type="file" name="file_undangan_seminar" class="form-control" accept=".pdf">
                                </div>

                                <div class="form-group" style="margin-bottom: 0;">
                                    <label class="form-label" style="font-size: 0.75rem;">Upload File SK Penguji (PDF)</label>
                                    <input type="file" name="file_sk_seminar" class="form-control" accept=".pdf">
                                </div>

                                <button type="submit" class="btn btn-primary btn-sm" style="height: 38px;">
                                    ✓ Simpan Jadwal & Surat
                                </button>
                            </div>
                        </form>

                        <!-- Form Input Nilai & Selesaikan Seminar -->
                        <div style="border-top: 1px dashed var(--border); margin-top: 1rem; padding-top: 0.75rem;">
                            <div style="font-weight: 700; font-size: 0.82rem; margin-bottom: 0.5rem; color: #166534;">2. Input Nilai & Finalisasi Hasil Seminar:</div>
                            <form method="POST" action="{{ route('admin.seminar.selesai', $seminar->id) }}">
                                @csrf
                                <div style="display: grid; grid-template-columns: 140px 1fr auto; gap: 0.75rem; align-items: flex-end;">
                                    <div class="form-group" style="margin-bottom: 0;">
                                        <label class="form-label" style="font-size: 0.75rem;">Nilai Akhir (0-100) *</label>
                                        <input type="number" step="0.01" min="0" max="100" name="nilai_seminar" value="{{ old('nilai_seminar', $seminar->nilai_seminar) }}" class="form-control" placeholder="85.50" required>
                                    </div>

                                    <div class="form-group" style="margin-bottom: 0;">
                                        <label class="form-label" style="font-size: 0.75rem;">Catatan Hasil Seminar / Berita Acara</label>
                                        <input type="text" name="catatan" value="{{ old('catatan', $seminar->catatan) }}" class="form-control" placeholder="Lanjut ke tahap penelitian / revisi minor...">
                                    </div>

                                    <button type="submit" class="btn btn-success btn-sm" style="height: 38px;">
                                        ✓ Selesaikan & Luluskan Seminar
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                <div style="text-align: center; color: var(--text-muted); padding: 3rem;">
                    Belum ada pengajuan seminar skripsi.
                </div>
            @endforelse
        </div>

        <div style="margin-top: 1rem;">
            {{ $daftarSeminar->links() }}
        </div>
    </div>

    <!-- TAB 3: JADWAL & DOKUMEN SIDANG -->
    <div id="tab-admin-sidang" class="tab-content">
        <div style="font-size: 0.88rem; color: var(--text-muted); margin-bottom: 1.25rem;">
            Atur jadwal tanggal/jam/ruang sidang meja hijau, nomor surat undangan, nomor SK dewan penguji, serta input nilai kelulusan akhir.
        </div>

        <div style="display: flex; flex-direction: column; gap: 1.25rem;">
            @forelse ($daftarSidang as $sidang)
                @php $sk = $sidang->pengajuanSkripsi; @endphp
                <div style="border: 1px solid var(--border); border-radius: 0.65rem; padding: 1.25rem; background: #fff;">
                    <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; margin-bottom: 0.5rem;">
                        <div>
                            <div style="font-size: 0.78rem; font-weight: 700; color: var(--text-muted);">
                                {{ $sk->mahasiswa->nomor_induk }} &bull; {{ $sk->mahasiswa->name }}
                            </div>
                            <h3 style="font-size: 1.05rem; font-weight: 700; color: #0f172a; margin-top: 0.2rem;">
                                "{{ $sk->judul }}"
                            </h3>
                            <div style="font-size: 0.82rem; color: #475569; margin-top: 0.25rem;">
                                2 Penguji Sidang: 1. <strong>{{ $sidang->penguji1 ? $sidang->penguji1->name : '-' }}</strong> | 2. <strong>{{ $sidang->penguji2 ? $sidang->penguji2->name : '-' }}</strong>
                            </div>
                        </div>
                        <span class="badge badge-{{ $sidang->status->value }}">{{ $sidang->status->label() }}</span>
                    </div>

                    <!-- Form Input Jadwal & Surat/SK Sidang -->
                    <div style="background: #f8fafc; border: 1px solid var(--border); border-radius: 0.5rem; padding: 1rem; margin-top: 0.85rem;">
                        <div style="font-weight: 700; font-size: 0.82rem; margin-bottom: 0.5rem;">1. Pengaturan Jadwal & Dokumen Surat/SK Sidang:</div>
                        <form method="POST" action="{{ route('admin.sidang.jadwal-sk', $sidang->id) }}" enctype="multipart/form-data">
                            @csrf
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 0.75rem;">
                                <div class="form-group" style="margin-bottom: 0;">
                                    <label class="form-label" style="font-size: 0.75rem;">Tanggal Sidang *</label>
                                    <input type="date" name="tgl_sidang" value="{{ old('tgl_sidang', $sidang->tgl_sidang ? $sidang->tgl_sidang->format('Y-m-d') : '') }}" class="form-control" required>
                                </div>

                                <div class="form-group" style="margin-bottom: 0;">
                                    <label class="form-label" style="font-size: 0.75rem;">Jam Pelaksanaan *</label>
                                    <input type="text" name="jam_sidang" value="{{ old('jam_sidang', $sidang->jam_sidang ?? '13:30 - 15:30') }}" class="form-control" placeholder="13:30 - 15:30" required>
                                </div>

                                <div class="form-group" style="margin-bottom: 0;">
                                    <label class="form-label" style="font-size: 0.75rem;">Ruangan / Tempat *</label>
                                    <input type="text" name="ruangan" value="{{ old('ruangan', $sidang->ruangan ?? 'Ruang Sidang Utama') }}" class="form-control" required>
                                </div>

                                <div class="form-group" style="margin-bottom: 0;">
                                    <label class="form-label" style="font-size: 0.75rem;">No. Undangan Sidang</label>
                                    <input type="text" name="nomor_undangan_sidang" value="{{ old('nomor_undangan_sidang', $sidang->nomor_undangan_sidang) }}" class="form-control" placeholder="UND-SDG/01/2026">
                                </div>

                                <div class="form-group" style="margin-bottom: 0;">
                                    <label class="form-label" style="font-size: 0.75rem;">No. SK Dewan Penguji</label>
                                    <input type="text" name="nomor_sk_sidang" value="{{ old('nomor_sk_sidang', $sidang->nomor_sk_sidang) }}" class="form-control" placeholder="SK-SDG/01/2026">
                                </div>
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr 1fr auto; gap: 0.75rem; margin-top: 0.75rem; align-items: flex-end;">
                                <div class="form-group" style="margin-bottom: 0;">
                                    <label class="form-label" style="font-size: 0.75rem;">Upload File Undangan Sidang (PDF)</label>
                                    <input type="file" name="file_undangan_sidang" class="form-control" accept=".pdf">
                                </div>

                                <div class="form-group" style="margin-bottom: 0;">
                                    <label class="form-label" style="font-size: 0.75rem;">Upload File SK Dewan Penguji (PDF)</label>
                                    <input type="file" name="file_sk_sidang" class="form-control" accept=".pdf">
                                </div>

                                <button type="submit" class="btn btn-primary btn-sm" style="height: 38px;">
                                    ✓ Simpan Jadwal & Surat
                                </button>
                            </div>
                        </form>

                        <!-- Form Input Nilai & Selesaikan Sidang -->
                        <div style="border-top: 1px dashed var(--border); margin-top: 1rem; padding-top: 0.75rem;">
                            <div style="font-weight: 700; font-size: 0.82rem; margin-bottom: 0.5rem; color: #166534;">2. Input Nilai Akhir & Yudisium Kelulusan:</div>
                            <form method="POST" action="{{ route('admin.sidang.selesai', $sidang->id) }}">
                                @csrf
                                <div style="display: grid; grid-template-columns: 140px 1fr auto; gap: 0.75rem; align-items: flex-end;">
                                    <div class="form-group" style="margin-bottom: 0;">
                                        <label class="form-label" style="font-size: 0.75rem;">Nilai Akhir (0-100) *</label>
                                        <input type="number" step="0.01" min="0" max="100" name="nilai_sidang" value="{{ old('nilai_sidang', $sidang->nilai_sidang) }}" class="form-control" placeholder="88.00" required>
                                    </div>

                                    <div class="form-group" style="margin-bottom: 0;">
                                        <label class="form-label" style="font-size: 0.75rem;">Catatan Kelulusan / Predikat Yudisium</label>
                                        <input type="text" name="catatan" value="{{ old('catatan', $sidang->catatan) }}" class="form-control" placeholder="Dinyatakan Lulus dengan predikat Sangat Memuaskan...">
                                    </div>

                                    <button type="submit" class="btn btn-success btn-sm" style="height: 38px;">
                                        ✓ Finalisasi Kelulusan Meja Hijau
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                <div style="text-align: center; color: var(--text-muted); padding: 3rem;">
                    Belum ada pengajuan sidang skripsi.
                </div>
            @endforelse
        </div>

        <div style="margin-top: 1rem;">
            {{ $daftarSidang->links() }}
        </div>
    </div>
</div>

@endsection
