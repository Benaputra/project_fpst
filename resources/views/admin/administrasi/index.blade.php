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
            <label class="form-label" style="font-size: 0.75rem;">Status Alur</label>
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
    @php
        $isTabSuratActive = request('page_surat') || request('jenis_surat') || request('q_surat');
    @endphp
    <div class="tab-nav">
        <button type="button" class="tab-btn {{ !$isTabSuratActive ? 'active' : '' }}" onclick="switchTab(event, 'tab-sk-bimbingan')">
            1. SK Bimbingan ({{ $daftarSkripsi->total() }})
        </button>
        <button type="button" class="tab-btn" onclick="switchTab(event, 'tab-admin-seminar')">
            2. Jadwal & Dokumen Seminar ({{ $daftarSeminar->total() }})
        </button>
        <button type="button" class="tab-btn" onclick="switchTab(event, 'tab-admin-sidang')">
            3. Jadwal & Dokumen Sidang ({{ $daftarSidang->total() }})
        </button>
        <button type="button" class="tab-btn {{ $isTabSuratActive ? 'active' : '' }}" onclick="switchTab(event, 'tab-admin-surat')">
            4. Arsip Surat & SK ({{ $daftarSurat->total() }})
        </button>
    </div>

    <!-- TAB 1: PENERBITAN SK BIMBINGAN -->
    <div id="tab-sk-bimbingan" class="tab-content {{ !$isTabSuratActive ? 'active' : '' }}">
        <div style="font-size: 0.88rem; color: var(--text-muted); margin-bottom: 1.25rem;">
            Masukkan Nomor SK Bimbingan resmi, tanggal terbit, dan unggah file PDF SK. Jika SK yang sudah diterbitkan diperbarui, sistem otomatis memberikan nama baru dan mencatat riwayat revisi.
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
                        @if ($skripsi->nomor_sk_bimbingan)
                            <div style="margin-bottom: 0.75rem; font-size: 0.82rem; color: #1e40af; background: #eff6ff; padding: 0.4rem 0.75rem; border-radius: 0.35rem; display: flex; align-items: center; justify-content: space-between;">
                                <span>📜 SK Telah Diterbitkan: <strong>{{ $skripsi->nomor_sk_bimbingan }}</strong> ({{ $skripsi->tgl_sk_bimbingan ? $skripsi->tgl_sk_bimbingan->format('d/m/Y') : '-' }})</span>
                                <span style="font-size: 0.75rem; color: #2563eb;">* Jika disimpan ulang, sistem otomatis memberikan nama baru revisi SK</span>
                            </div>
                        @endif

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
                                    <label class="form-label" style="font-size: 0.75rem;">Upload File PDF SK</label>
                                    <input type="file" name="file_sk_bimbingan" class="form-control" accept=".pdf">
                                </div>

                                <div>
                                    <button type="submit" class="btn btn-primary btn-sm" style="width: 100%; height: 38px;">
                                        ✓ {{ $skripsi->nomor_sk_bimbingan ? 'Perbarui SK (Nama Baru)' : 'Simpan & Terbitkan SK' }}
                                    </button>
                                </div>
                            </div>
                        </form>

                        @if ($skripsi->file_sk_bimbingan)
                            <div style="margin-top: 0.5rem; font-size: 0.8rem;">
                                <a href="{{ route('dokumen.download', base64_encode($skripsi->file_sk_bimbingan)) }}" style="color: #2563eb; font-weight: 600;">
                                    📥 Unduh Berkas SK Bimbingan Aktif (PDF)
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
                            <div style="font-size: 0.82rem; color: #475569; margin-top: 0.25rem; display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                                <span>Penguji Seminar: <strong>{{ $seminar->penguji ? $seminar->penguji->name : 'Belum ditetapkan Kaprodi' }}</strong></span>
                                @if ($user->isAdminUtama())
                                    <a href="{{ route('kaprodi.penetapan.index', ['prodi_id' => $sk->program_studi_id]) }}" class="btn btn-secondary btn-sm" style="font-size: 0.72rem; padding: 0.15rem 0.5rem;">
                                        ⚙️ Kelola Penguji (Admin Utama)
                                    </a>
                                @endif
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

                            @if ($seminar->nilai_seminar !== null && !$user->isAdminUtama())
                                <!-- Nilai Terkunci untuk Non-Admin Utama -->
                                <div style="background: #f1f5f9; border: 1px solid #cbd5e1; border-radius: 0.5rem; padding: 0.85rem 1rem;">
                                    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.5rem;">
                                        <div>
                                            <span style="font-weight: 700; color: #166534;">🔒 Nilai Seminar Telah Ditentukan:</span>
                                            <strong style="font-size: 1.15rem; color: #0f172a; margin-left: 0.35rem;">{{ $seminar->nilai_seminar }}</strong>
                                            @if ($seminar->catatan)
                                                &bull; <span style="font-size: 0.82rem; color: #475569;">Catatan: {{ $seminar->catatan }}</span>
                                            @endif
                                        </div>
                                        <span class="badge badge--secondary" style="font-size: 0.75rem;">Terkunci (Hanya Admin Utama yang berwenang mengubah nilai)</span>
                                    </div>
                                </div>
                            @else
                                <!-- Form Input / Ubah Nilai (Bisa diisi jika belum ada, atau diubah oleh Admin Utama) -->
                                @if ($seminar->nilai_seminar !== null && $user->isAdminUtama())
                                    <div style="display: inline-block; background: #fee2e2; color: #991b1b; padding: 0.15rem 0.5rem; border-radius: 0.35rem; font-size: 0.72rem; font-weight: 700; margin-bottom: 0.5rem;">
                                        👑 Hak Akses Admin Utama: Anda dapat mengubah nilai seminar yang sudah difinalisasi
                                    </div>
                                @endif

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
                                            ✓ {{ $seminar->nilai_seminar !== null ? 'Perbarui Nilai Seminar' : 'Selesaikan & Luluskan Seminar' }}
                                        </button>
                                    </div>
                                </form>
                            @endif
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
            Atur jadwal tanggal/jam/ruang sidang meja hijau, nomor surat undangan (otomatis didistribusikan ke Mahasiswa, Penguji, serta Pembimbing 1 & 2), nomor SK dewan penguji, dan input nilai kelulusan.
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
                            <div style="font-size: 0.82rem; color: #475569; margin-top: 0.25rem; display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                                <span>2 Penguji Sidang: 1. <strong>{{ $sidang->penguji1 ? $sidang->penguji1->name : '-' }}</strong> | 2. <strong>{{ $sidang->penguji2 ? $sidang->penguji2->name : '-' }}</strong></span>
                                @if ($user->isAdminUtama())
                                    <a href="{{ route('kaprodi.penetapan.index', ['prodi_id' => $sk->program_studi_id]) }}" class="btn btn-secondary btn-sm" style="font-size: 0.72rem; padding: 0.15rem 0.5rem;">
                                        ⚙️ Kelola Dewan Penguji (Admin Utama)
                                    </a>
                                @endif
                            </div>
                            <div style="font-size: 0.78rem; color: #64748b; margin-top: 0.2rem;">
                                Pembimbing yang turut menerima undangan: 1. {{ $sk->pembimbing1 ? $sk->pembimbing1->name : '-' }} | 2. {{ $sk->pembimbing2 ? $sk->pembimbing2->name : '-' }}
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

                            @if ($sidang->nilai_sidang !== null && !$user->isAdminUtama())
                                <!-- Nilai Terkunci untuk Non-Admin Utama -->
                                <div style="background: #f1f5f9; border: 1px solid #cbd5e1; border-radius: 0.5rem; padding: 0.85rem 1rem;">
                                    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.5rem;">
                                        <div>
                                            <span style="font-weight: 700; color: #166534;">🔒 Nilai Akhir Sidang Telah Ditentukan:</span>
                                            <strong style="font-size: 1.15rem; color: #0f172a; margin-left: 0.35rem;">{{ $sidang->nilai_sidang }}</strong>
                                            @if ($sidang->catatan)
                                                &bull; <span style="font-size: 0.82rem; color: #475569;">Yudisium: {{ $sidang->catatan }}</span>
                                            @endif
                                        </div>
                                        <span class="badge badge--secondary" style="font-size: 0.75rem;">Terkunci (Hanya Admin Utama yang berwenang mengubah nilai)</span>
                                    </div>
                                </div>
                            @else
                                <!-- Form Input / Ubah Nilai Sidang -->
                                @if ($sidang->nilai_sidang !== null && $user->isAdminUtama())
                                    <div style="display: inline-block; background: #fee2e2; color: #991b1b; padding: 0.15rem 0.5rem; border-radius: 0.35rem; font-size: 0.72rem; font-weight: 700; margin-bottom: 0.5rem;">
                                        👑 Hak Akses Admin Utama: Anda dapat mengubah nilai sidang yang sudah difinalisasi
                                    </div>
                                @endif

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
                                            ✓ {{ $sidang->nilai_sidang !== null ? 'Perbarui Nilai Sidang' : 'Finalisasi Kelulusan Meja Hijau' }}
                                        </button>
                                    </div>
                                </form>
                            @endif
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

    <!-- TAB 4: DATA ARSIP SURAT & SK -->
    <div id="tab-admin-surat" class="tab-content {{ $isTabSuratActive ? 'active' : '' }}">
        <div style="font-size: 0.88rem; color: var(--text-muted); margin-bottom: 1.25rem;">
            Tabel rekapitulasi data nomor surat undangan dan SK (Seminar & Skripsi) yang dikeluarkan oleh sistem beserta status keaktifan dan dokumen arsip PDF.
        </div>

        <!-- Filter Surat & SK -->
        <form method="GET" action="{{ route('admin.administrasi.index') }}" style="display: flex; flex-wrap: wrap; gap: 0.75rem; align-items: flex-end; margin-bottom: 1.25rem; padding: 1rem; background: #f8fafc; border: 1px solid var(--border); border-radius: 0.5rem;">
            <input type="hidden" name="prodi_id" value="{{ $prodiFilter }}">

            <div style="flex: 1; min-width: 180px;">
                <label class="form-label" style="font-size: 0.75rem;">Jenis Dokumen Surat / SK</label>
                <select name="jenis_surat" class="form-control" style="font-size: 0.85rem;" onchange="this.form.submit()">
                    <option value="">-- Semua Jenis Surat/SK --</option>
                    <option value="sk_bimbingan" {{ $jenisSuratFilter === 'sk_bimbingan' ? 'selected' : '' }}>SK Pembimbing Skripsi</option>
                    <option value="undangan_seminar" {{ $jenisSuratFilter === 'undangan_seminar' ? 'selected' : '' }}>Surat Undangan Seminar</option>
                    <option value="sk_seminar" {{ $jenisSuratFilter === 'sk_seminar' ? 'selected' : '' }}>SK Penguji Seminar</option>
                    <option value="undangan_sidang" {{ $jenisSuratFilter === 'undangan_sidang' ? 'selected' : '' }}>Surat Undangan Sidang</option>
                    <option value="sk_sidang" {{ $jenisSuratFilter === 'sk_sidang' ? 'selected' : '' }}>SK Dewan Penguji Sidang</option>
                </select>
            </div>

            <div style="flex: 2; min-width: 220px;">
                <label class="form-label" style="font-size: 0.75rem;">Cari No. Surat / Nama Surat</label>
                <input type="text" name="q_surat" value="{{ $cariSurat }}" class="form-control" style="font-size: 0.85rem;" placeholder="Cari nomor surat atau kata kunci...">
            </div>

            <button type="submit" class="btn btn-primary btn-sm" style="height: 38px;">🔍 Filter Surat</button>
            <a href="{{ route('admin.administrasi.index') }}" class="btn btn-secondary btn-sm" style="height: 38px;">Reset</a>
        </form>

        <div style="overflow-x: auto; border: 1px solid var(--border); border-radius: 0.5rem; background: #fff;">
            <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.85rem;">
                <thead style="background: #f1f5f9; color: #475569; font-size: 0.78rem; text-transform: uppercase;">
                    <tr>
                        <th style="padding: 0.75rem 1rem; border-bottom: 1px solid var(--border);">No. Surat / SK</th>
                        <th style="padding: 0.75rem 1rem; border-bottom: 1px solid var(--border);">Jenis</th>
                        <th style="padding: 0.75rem 1rem; border-bottom: 1px solid var(--border);">Nama Surat & Versi</th>
                        <th style="padding: 0.75rem 1rem; border-bottom: 1px solid var(--border);">Mahasiswa & Prodi</th>
                        <th style="padding: 0.75rem 1rem; border-bottom: 1px solid var(--border);">Tgl Terbit</th>
                        <th style="padding: 0.75rem 1rem; border-bottom: 1px solid var(--border);">Status</th>
                        <th style="padding: 0.75rem 1rem; border-bottom: 1px solid var(--border);">Penerbit</th>
                        <th style="padding: 0.75rem 1rem; border-bottom: 1px solid var(--border); text-align: center;">Berkas</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($daftarSurat as $surat)
                        <tr style="border-bottom: 1px solid var(--border);">
                            <td style="padding: 0.75rem 1rem; font-weight: 700; color: #0f172a;">
                                {{ $surat->nomor_surat }}
                            </td>
                            <td style="padding: 0.75rem 1rem;">
                                <span class="badge {{ $surat->jenisBadgeClass() }}">{{ $surat->jenisLabel() }}</span>
                            </td>
                            <td style="padding: 0.75rem 1rem;">
                                <div style="font-weight: 600; color: #1e293b;">{{ $surat->nama_surat }}</div>
                                @if ($surat->versi > 1)
                                    <span style="font-size: 0.72rem; color: #b45309; font-weight: 700;">Revisi ke-{{ $surat->versi }}</span>
                                @endif
                            </td>
                            <td style="padding: 0.75rem 1rem;">
                                <div>{{ $surat->pengajuanSkripsi && $surat->pengajuanSkripsi->mahasiswa ? $surat->pengajuanSkripsi->mahasiswa->name : '-' }}</div>
                                <div style="font-size: 0.75rem; color: var(--text-muted);">
                                    {{ $surat->pengajuanSkripsi && $surat->pengajuanSkripsi->mahasiswa ? $surat->pengajuanSkripsi->mahasiswa->nomor_induk : '' }}
                                    &bull; {{ $surat->programStudi ? $surat->programStudi->kode : '-' }}
                                </div>
                            </td>
                            <td style="padding: 0.75rem 1rem; color: #475569;">
                                {{ $surat->tgl_surat ? $surat->tgl_surat->format('d/m/Y') : '-' }}
                            </td>
                            <td style="padding: 0.75rem 1rem;">
                                <span class="badge {{ $surat->statusBadgeClass() }}">{{ ucfirst($surat->status) }}</span>
                            </td>
                            <td style="padding: 0.75rem 1rem; font-size: 0.8rem; color: #64748b;">
                                {{ $surat->penerbit ? $surat->penerbit->name : 'Sistem' }}
                            </td>
                            <td style="padding: 0.75rem 1rem; text-align: center;">
                                @if ($surat->file_surat)
                                    <a href="{{ route('dokumen.download', base64_encode($surat->file_surat)) }}" class="btn btn-secondary btn-sm" style="font-size: 0.75rem; padding: 0.2rem 0.6rem;">
                                        📥 Unduh
                                    </a>
                                @else
                                    <span style="color: var(--text-muted); font-size: 0.78rem;">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="text-align: center; color: var(--text-muted); padding: 3rem;">
                                Belum ada data surat atau SK yang diterbitkan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div style="margin-top: 1rem;">
            {{ $daftarSurat->links() }}
        </div>
    </div>
</div>

@endsection
