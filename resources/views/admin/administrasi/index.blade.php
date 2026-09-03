@extends('layouts.app')

@section('title', 'Administrasi Skripsi, Jadwal & SK')
@section('page_title', 'Administrasi Surat & SK Skripsi')

@section('content')

<div class="card" style="padding: 1.25rem;">
    <!-- Filter Prodi & Global Status -->
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
            <label class="form-label" style="font-size: 0.75rem;">Status Alur Pengajuan</label>
            <select name="status" class="form-control" style="font-size: 0.85rem;" onchange="this.form.submit()">
                <option value="">-- Semua Status Alur --</option>
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
        $activeTab = request('tab', (request('page_surat') || request('jenis_surat') || request('q_surat') ? 'surat' : 'sk-bimbingan'));
    @endphp
    <div class="tab-nav">
        <button type="button" class="tab-btn {{ $activeTab === 'sk-bimbingan' ? 'active' : '' }}" onclick="switchTab(event, 'tab-sk-bimbingan')">
            1. SK Bimbingan
            <span class="badge {{ $pendingSkCount > 0 ? 'badge-diajukan' : 'badge-diproses' }}" style="margin-left: 0.35rem; font-size: 0.7rem;">
                {{ $daftarSkripsi->total() }} ({{ $pendingSkCount }} antrean)
            </span>
        </button>
        <button type="button" class="tab-btn {{ $activeTab === 'seminar' ? 'active' : '' }}" onclick="switchTab(event, 'tab-admin-seminar')">
            2. Jadwal & Dokumen Seminar
            <span class="badge {{ $pendingSeminarCount > 0 ? 'badge-diajukan' : 'badge-diproses' }}" style="margin-left: 0.35rem; font-size: 0.7rem;">
                {{ $daftarSeminar->total() }} ({{ $pendingSeminarCount }} antrean)
            </span>
        </button>
        <button type="button" class="tab-btn {{ $activeTab === 'sidang' ? 'active' : '' }}" onclick="switchTab(event, 'tab-admin-sidang')">
            3. Jadwal & Dokumen Sidang
            <span class="badge {{ $pendingSidangCount > 0 ? 'badge-diajukan' : 'badge-diproses' }}" style="margin-left: 0.35rem; font-size: 0.7rem;">
                {{ $daftarSidang->total() }} ({{ $pendingSidangCount }} antrean)
            </span>
        </button>
        <button type="button" class="tab-btn {{ $activeTab === 'surat' ? 'active' : '' }}" onclick="switchTab(event, 'tab-admin-surat')">
            4. Arsip Surat & SK ({{ $daftarSurat->total() }})
        </button>
    </div>

    <!-- ========================================== -->
    <!-- TAB 1: PENERBITAN SK BIMBINGAN -->
    <!-- ========================================== -->
    <div id="tab-sk-bimbingan" class="tab-content {{ $activeTab === 'sk-bimbingan' ? 'active' : '' }}">
        <div style="font-size: 0.88rem; color: var(--text-muted); margin-bottom: 1rem;">
            Terbitkan SK Dosen Pembimbing resmi setelah Kaprodi menentukan dosen pembimbing. Jika diperbarui, sistem otomatis mencatat versi baru dan riwayat revisi.
        </div>

        <!-- Filter Toolbar Tab 1 -->
        <form method="GET" action="{{ route('admin.administrasi.index') }}" style="background: #f8fafc; border: 1px solid var(--border); border-radius: 0.65rem; padding: 0.85rem; margin-bottom: 1.25rem; display: flex; flex-wrap: wrap; gap: 0.75rem; justify-content: space-between; align-items: center;">
            <input type="hidden" name="tab" value="sk-bimbingan">
            @if($prodiFilter)
                <input type="hidden" name="prodi_id" value="{{ $prodiFilter }}">
            @endif

            <!-- Status Buttons -->
            <div style="display: flex; gap: 0.35rem; flex-wrap: wrap;">
                <button type="submit" name="status_skripsi" value="" class="btn btn-sm {{ !request('status_skripsi') ? 'btn-primary' : 'btn-secondary' }}" style="padding: 0.3rem 0.75rem; font-size: 0.78rem;">
                    Semua
                </button>
                <button type="submit" name="status_skripsi" value="menunggu" class="btn btn-sm {{ request('status_skripsi') === 'menunggu' ? 'btn-primary' : 'btn-secondary' }}" style="padding: 0.3rem 0.75rem; font-size: 0.78rem;">
                    Menunggu SK ({{ $pendingSkCount }})
                </button>
                <button type="submit" name="status_skripsi" value="selesai" class="btn btn-sm {{ request('status_skripsi') === 'selesai' ? 'btn-primary' : 'btn-secondary' }}" style="padding: 0.3rem 0.75rem; font-size: 0.78rem;">
                    SK Terbit
                </button>
            </div>

            <!-- Search & Sort FIFO -->
            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; align-items: center;">
                <div style="position: relative; width: 220px;">
                    <input type="text" name="search_skripsi" value="{{ request('search_skripsi') }}" placeholder="Cari Nama / NIM / No SK..." class="form-control" style="font-size: 0.8rem; padding: 0.35rem 0.65rem 0.35rem 1.85rem; height: 36px;">
                    <span style="position: absolute; left: 0.6rem; top: 50%; transform: translateY(-50%); font-size: 0.75rem; color: var(--text-muted);">🔍</span>
                </div>
                <select name="sort_skripsi" class="form-control" style="font-size: 0.8rem; width: 170px; height: 36px; padding: 0.35rem 0.5rem;" onchange="this.form.submit()">
                    <option value="fifo" {{ request('sort_skripsi', 'fifo') === 'fifo' ? 'selected' : '' }}>Urutan: Terlama (FIFO)</option>
                    <option value="lifo" {{ request('sort_skripsi') === 'lifo' ? 'selected' : '' }}>Urutan: Terbaru Masuk</option>
                </select>
                <button type="submit" class="btn btn-secondary btn-sm" style="height: 36px;">Filter</button>
                @if(request('search_skripsi') || request('status_skripsi') || request('sort_skripsi'))
                    <a href="{{ route('admin.administrasi.index', array_merge(['tab' => 'sk-bimbingan'], $prodiFilter ? ['prodi_id' => $prodiFilter] : [])) }}" class="btn btn-secondary btn-sm" style="height: 36px;" title="Reset Filter">✕</a>
                @endif
            </div>
        </form>

        <!-- Data Table Tab 1 -->
        <div class="table-responsive" style="border: 1px solid var(--border); border-radius: 0.65rem;">
            <table>
                <thead>
                    <tr>
                        <th style="width: 50px; text-align: center;">No</th>
                        <th style="width: 170px;">Tgl Pengajuan (FIFO)</th>
                        <th style="width: 200px;">Mahasiswa</th>
                        <th>Judul & Pembimbing</th>
                        <th style="width: 190px;">No. SK Bimbingan</th>
                        <th style="width: 130px; text-align: center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($daftarSkripsi as $skripsi)
                        @php
                            $isWaiting = empty($skripsi->nomor_sk_bimbingan);
                            $isUrgent = $isWaiting && $skripsi->created_at->diffInDays(now()) >= 3;
                            $queueNumber = ($daftarSkripsi->currentPage() - 1) * $daftarSkripsi->perPage() + $loop->iteration;
                        @endphp
                        <tr style="background: {{ $isWaiting ? '#fffdf5' : '#ffffff' }};">
                            <td style="text-align: center; font-weight: 700;">
                                <span class="badge {{ $isWaiting ? 'badge-diajukan' : 'badge-diproses' }}" style="font-size: 0.72rem;">#{{ $queueNumber }}</span>
                            </td>
                            <td>
                                <div style="font-weight: 600; font-size: 0.8rem; color: #1e293b;">
                                    {{ $skripsi->created_at->translatedFormat('d M Y') }}
                                    <span style="font-weight: 400; color: var(--text-muted); font-size: 0.75rem;">{{ $skripsi->created_at->format('H:i') }}</span>
                                </div>
                                <div style="margin-top: 0.2rem;">
                                    @if ($isUrgent)
                                        <span class="badge badge-diajukan" style="font-size: 0.68rem; padding: 0.15rem 0.4rem;">
                                            ⏳ {{ $skripsi->created_at->diffForHumans() }}
                                        </span>
                                    @else
                                        <span style="font-size: 0.72rem; color: var(--text-muted);">
                                            {{ $skripsi->created_at->diffForHumans() }}
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <div style="font-weight: 700; color: #0f172a; font-size: 0.85rem;">{{ $skripsi->mahasiswa->name }}</div>
                                <div style="font-size: 0.75rem; color: var(--text-muted); font-family: monospace;">
                                    {{ $skripsi->mahasiswa->nomor_induk }} &bull; {{ $skripsi->programStudi ? $skripsi->programStudi->kode : '-' }}
                                </div>
                            </td>
                            <td>
                                <div style="font-weight: 700; color: #0f172a; font-size: 0.88rem; line-height: 1.4;">
                                    "{{ $skripsi->judul }}"
                                </div>
                                <div style="font-size: 0.75rem; color: #2e6840; margin-top: 0.25rem; font-weight: 600;">
                                    👨‍🏫 Pembimbing: 1. {{ $skripsi->pembimbing1 ? $skripsi->pembimbing1->name : 'Belum ditentukan' }}
                                    {{ $skripsi->pembimbing2 ? '| 2. ' . $skripsi->pembimbing2->name : '' }}
                                </div>
                            </td>
                            <td>
                                @if ($skripsi->nomor_sk_bimbingan)
                                    <div style="font-weight: 700; color: #1e40af; font-size: 0.82rem;">
                                        📜 {{ $skripsi->nomor_sk_bimbingan }}
                                    </div>
                                    <div style="font-size: 0.72rem; color: #475569; margin-top: 0.15rem;">
                                        Tgl Terbit: {{ $skripsi->tgl_sk_bimbingan ? $skripsi->tgl_sk_bimbingan->format('d/m/Y') : '-' }}
                                    </div>
                                    @if ($skripsi->file_sk_bimbingan)
                                        <a href="{{ route('dokumen.download', base64_encode($skripsi->file_sk_bimbingan)) }}" class="btn btn-secondary btn-sm" style="font-size: 0.68rem; padding: 0.1rem 0.4rem; min-height: 20px; margin-top: 0.25rem;">
                                            📥 Unduh PDF
                                        </a>
                                    @endif
                                @else
                                    <span class="badge badge-diajukan" style="font-size: 0.72rem;">Menunggu Penerbitan SK</span>
                                @endif
                            </td>
                            <td style="text-align: center;">
                                <button type="button" class="btn {{ $isWaiting ? 'btn-primary' : 'btn-secondary' }} btn-sm" style="font-size: 0.78rem; padding: 0.35rem 0.75rem;" onclick="openDrawerAdminSk({{ json_encode([
                                    'id' => $skripsi->id,
                                    'mhs_name' => $skripsi->mahasiswa->name,
                                    'mhs_nim' => $skripsi->mahasiswa->nomor_induk,
                                    'prodi' => $skripsi->programStudi ? $skripsi->programStudi->nama : '-',
                                    'judul' => $skripsi->judul,
                                    'pembimbing_info' => '1. ' . ($skripsi->pembimbing1 ? $skripsi->pembimbing1->name : 'Belum ditentukan') . ($skripsi->pembimbing2 ? ' | 2. ' . $skripsi->pembimbing2->name : ''),
                                    'nomor_sk' => $skripsi->nomor_sk_bimbingan,
                                    'tgl_sk' => $skripsi->tgl_sk_bimbingan ? $skripsi->tgl_sk_bimbingan->format('Y-m-d') : date('Y-m-d'),
                                    'file_sk_url' => $skripsi->file_sk_bimbingan ? route('dokumen.download', base64_encode($skripsi->file_sk_bimbingan)) : null,
                                    'form_action' => route('admin.skripsi.sk-bimbingan', $skripsi->id),
                                ]) }})">
                                    {{ $isWaiting ? '📜 Terbitkan SK' : '✏️ Perbarui SK' }}
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 2.5rem;">
                                Tidak ada data pengajuan skripsi untuk ditampilkan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div style="margin-top: 1rem;">
            {{ $daftarSkripsi->appends(['tab' => 'sk-bimbingan'])->links() }}
        </div>
    </div>

    <!-- ========================================== -->
    <!-- TAB 2: JADWAL & DOKUMEN SEMINAR -->
    <!-- ========================================== -->
    <div id="tab-admin-seminar" class="tab-content {{ $activeTab === 'seminar' ? 'active' : '' }}">
        <div style="font-size: 0.88rem; color: var(--text-muted); margin-bottom: 1rem;">
            Atur jadwal tanggal/jam/ruang seminar, nomor surat undangan, nomor SK penguji, serta input nilai kelulusan seminar berdasarkan antrean.
        </div>

        <!-- Filter Toolbar Tab 2 -->
        <form method="GET" action="{{ route('admin.administrasi.index') }}" style="background: #f8fafc; border: 1px solid var(--border); border-radius: 0.65rem; padding: 0.85rem; margin-bottom: 1.25rem; display: flex; flex-wrap: wrap; gap: 0.75rem; justify-content: space-between; align-items: center;">
            <input type="hidden" name="tab" value="seminar">
            @if($prodiFilter)
                <input type="hidden" name="prodi_id" value="{{ $prodiFilter }}">
            @endif

            <!-- Status Buttons -->
            <div style="display: flex; gap: 0.35rem; flex-wrap: wrap;">
                <button type="submit" name="status_seminar" value="" class="btn btn-sm {{ !request('status_seminar') ? 'btn-primary' : 'btn-secondary' }}" style="padding: 0.3rem 0.75rem; font-size: 0.78rem;">
                    Semua
                </button>
                <button type="submit" name="status_seminar" value="menunggu_jadwal" class="btn btn-sm {{ request('status_seminar') === 'menunggu_jadwal' ? 'btn-primary' : 'btn-secondary' }}" style="padding: 0.3rem 0.75rem; font-size: 0.78rem;">
                    Belum Ada Jadwal
                </button>
                <button type="submit" name="status_seminar" value="menunggu_nilai" class="btn btn-sm {{ request('status_seminar') === 'menunggu_nilai' ? 'btn-primary' : 'btn-secondary' }}" style="padding: 0.3rem 0.75rem; font-size: 0.78rem;">
                    Menunggu Nilai
                </button>
                <button type="submit" name="status_seminar" value="selesai" class="btn btn-sm {{ request('status_seminar') === 'selesai' ? 'btn-primary' : 'btn-secondary' }}" style="padding: 0.3rem 0.75rem; font-size: 0.78rem;">
                    Selesai Seminar
                </button>
            </div>

            <!-- Search & Sort FIFO -->
            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; align-items: center;">
                <div style="position: relative; width: 220px;">
                    <input type="text" name="search_seminar" value="{{ request('search_seminar') }}" placeholder="Cari Nama / NIM / Judul..." class="form-control" style="font-size: 0.8rem; padding: 0.35rem 0.65rem 0.35rem 1.85rem; height: 36px;">
                    <span style="position: absolute; left: 0.6rem; top: 50%; transform: translateY(-50%); font-size: 0.75rem; color: var(--text-muted);">🔍</span>
                </div>
                <select name="sort_seminar" class="form-control" style="font-size: 0.8rem; width: 170px; height: 36px; padding: 0.35rem 0.5rem;" onchange="this.form.submit()">
                    <option value="fifo" {{ request('sort_seminar', 'fifo') === 'fifo' ? 'selected' : '' }}>Urutan: Terlama (FIFO)</option>
                    <option value="lifo" {{ request('sort_seminar') === 'lifo' ? 'selected' : '' }}>Urutan: Terbaru Masuk</option>
                </select>
                <button type="submit" class="btn btn-secondary btn-sm" style="height: 36px;">Filter</button>
                @if(request('search_seminar') || request('status_seminar') || request('sort_seminar'))
                    <a href="{{ route('admin.administrasi.index', array_merge(['tab' => 'seminar'], $prodiFilter ? ['prodi_id' => $prodiFilter] : [])) }}" class="btn btn-secondary btn-sm" style="height: 36px;" title="Reset Filter">✕</a>
                @endif
            </div>
        </form>

        <!-- Data Table Tab 2 -->
        <div class="table-responsive" style="border: 1px solid var(--border); border-radius: 0.65rem;">
            <table>
                <thead>
                    <tr>
                        <th style="width: 50px; text-align: center;">No</th>
                        <th style="width: 170px;">Tgl Pengajuan (FIFO)</th>
                        <th style="width: 200px;">Mahasiswa</th>
                        <th>Judul & Penguji Seminar</th>
                        <th style="width: 190px;">Jadwal & Hasil</th>
                        <th style="width: 130px; text-align: center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($daftarSeminar as $seminar)
                        @php
                            $sk = $seminar->pengajuanSkripsi;
                            $isWaiting = empty($seminar->tgl_seminar) || empty($seminar->nilai_seminar);
                            $isUrgent = $isWaiting && $seminar->created_at->diffInDays(now()) >= 3;
                            $queueNumber = ($daftarSeminar->currentPage() - 1) * $daftarSeminar->perPage() + $loop->iteration;
                        @endphp
                        <tr style="background: {{ $isWaiting ? '#fffdf5' : '#ffffff' }};">
                            <td style="text-align: center; font-weight: 700;">
                                <span class="badge {{ $isWaiting ? 'badge-diajukan' : 'badge-diproses' }}" style="font-size: 0.72rem;">#{{ $queueNumber }}</span>
                            </td>
                            <td>
                                <div style="font-weight: 600; font-size: 0.8rem; color: #1e293b;">
                                    {{ $seminar->created_at->translatedFormat('d M Y') }}
                                    <span style="font-weight: 400; color: var(--text-muted); font-size: 0.75rem;">{{ $seminar->created_at->format('H:i') }}</span>
                                </div>
                                <div style="margin-top: 0.2rem;">
                                    @if ($isUrgent)
                                        <span class="badge badge-diajukan" style="font-size: 0.68rem; padding: 0.15rem 0.4rem;">
                                            ⏳ {{ $seminar->created_at->diffForHumans() }}
                                        </span>
                                    @else
                                        <span style="font-size: 0.72rem; color: var(--text-muted);">
                                            {{ $seminar->created_at->diffForHumans() }}
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <div style="font-weight: 700; color: #0f172a; font-size: 0.85rem;">{{ $sk->mahasiswa->name }}</div>
                                <div style="font-size: 0.75rem; color: var(--text-muted); font-family: monospace;">{{ $sk->mahasiswa->nomor_induk }}</div>
                            </td>
                            <td>
                                <div style="font-weight: 700; color: #0f172a; font-size: 0.88rem; line-height: 1.4;">
                                    "{{ $sk->judul }}"
                                </div>
                                <div style="font-size: 0.75rem; color: #2e6840; margin-top: 0.25rem; font-weight: 600;">
                                    🎓 Penguji: {{ $seminar->penguji ? $seminar->penguji->name : 'Belum ditetapkan Kaprodi' }}
                                </div>
                                <!-- Berkas Links -->
                                <div style="display: flex; gap: 0.35rem; margin-top: 0.35rem; flex-wrap: wrap;">
                                    @if ($seminar->file_naskah_seminar)
                                        <a href="{{ route('dokumen.download', base64_encode($seminar->file_naskah_seminar)) }}" class="btn btn-secondary btn-sm" style="font-size: 0.68rem; padding: 0.15rem 0.45rem; min-height: 24px;">📄 Naskah</a>
                                    @endif
                                    @if ($seminar->file_acc_pembimbing)
                                        <a href="{{ route('dokumen.download', base64_encode($seminar->file_acc_pembimbing)) }}" class="btn btn-secondary btn-sm" style="font-size: 0.68rem; padding: 0.15rem 0.45rem; min-height: 24px;">✍️ ACC</a>
                                    @endif
                                    @if ($seminar->file_bukti_bayar_seminar)
                                        <a href="{{ route('dokumen.download', base64_encode($seminar->file_bukti_bayar_seminar)) }}" class="btn btn-secondary btn-sm" style="font-size: 0.68rem; padding: 0.15rem 0.45rem; min-height: 24px;">💳 Bayar</a>
                                    @endif
                                </div>
                            </td>
                            <td>
                                @if ($seminar->tgl_seminar)
                                    <div style="font-weight: 700; color: #1e293b; font-size: 0.82rem;">
                                        🗓️ {{ $seminar->tgl_seminar->format('d/m/Y') }} &bull; {{ $seminar->jam_seminar }}
                                    </div>
                                    <div style="font-size: 0.72rem; color: var(--text-muted);">
                                        📍 {{ $seminar->ruangan }}
                                    </div>
                                @else
                                    <span class="badge badge-diajukan" style="font-size: 0.7rem;">Belum Terjadwal</span>
                                @endif

                                <div style="margin-top: 0.35rem;">
                                    @if ($seminar->nilai_seminar !== null)
                                        <span class="badge badge-selesai" style="font-size: 0.72rem;">Nilai: {{ $seminar->nilai_seminar }} (Lulus)</span>
                                    @else
                                        <span class="badge badge-diproses" style="font-size: 0.7rem;">Belum Dinilai</span>
                                    @endif
                                </div>
                            </td>
                            <td style="text-align: center;">
                                <button type="button" class="btn {{ $isWaiting ? 'btn-primary' : 'btn-secondary' }} btn-sm" style="font-size: 0.78rem; padding: 0.35rem 0.75rem;" onclick="openDrawerAdminSeminar({{ json_encode([
                                    'id' => $seminar->id,
                                    'mhs_name' => $sk->mahasiswa->name,
                                    'mhs_nim' => $sk->mahasiswa->nomor_induk,
                                    'judul' => $sk->judul,
                                    'penguji_name' => $seminar->penguji ? $seminar->penguji->name : 'Belum ditetapkan Kaprodi',
                                    'tgl_seminar' => $seminar->tgl_seminar ? $seminar->tgl_seminar->format('Y-m-d') : '',
                                    'jam_seminar' => $seminar->jam_seminar ?? '09:00 - 10:30',
                                    'ruangan' => $seminar->ruangan ?? 'Ruang Seminar',
                                    'nomor_undangan' => $seminar->nomor_undangan_seminar,
                                    'nomor_sk' => $seminar->nomor_sk_seminar,
                                    'file_undangan_url' => $seminar->file_undangan_seminar ? route('dokumen.download', base64_encode($seminar->file_undangan_seminar)) : null,
                                    'file_sk_url' => $seminar->file_sk_seminar ? route('dokumen.download', base64_encode($seminar->file_sk_seminar)) : null,
                                    'nilai_seminar' => $seminar->nilai_seminar,
                                    'catatan' => $seminar->catatan,
                                    'is_locked_nilai' => ($seminar->nilai_seminar !== null && !$user->isAdminUtama()),
                                    'is_admin_utama' => $user->isAdminUtama(),
                                    'action_jadwal' => route('admin.seminar.jadwal-sk', $seminar->id),
                                    'action_selesai' => route('admin.seminar.selesai', $seminar->id),
                                ]) }})">
                                    ⚙️ Kelola
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 2.5rem;">
                                Tidak ada data pengajuan seminar skripsi untuk ditampilkan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div style="margin-top: 1rem;">
            {{ $daftarSeminar->appends(['tab' => 'seminar'])->links() }}
        </div>
    </div>

    <!-- ========================================== -->
    <!-- TAB 3: JADWAL & DOKUMEN SIDANG -->
    <!-- ========================================== -->
    <div id="tab-admin-sidang" class="tab-content {{ $activeTab === 'sidang' ? 'active' : '' }}">
        <div style="font-size: 0.88rem; color: var(--text-muted); margin-bottom: 1rem;">
            Atur jadwal tanggal/jam/ruang sidang skripsi, nomor surat undangan ke semua pihak, nomor SK dewan penguji, dan input nilai kelulusan sidang skripsi.
        </div>

        <!-- Filter Toolbar Tab 3 -->
        <form method="GET" action="{{ route('admin.administrasi.index') }}" style="background: #f8fafc; border: 1px solid var(--border); border-radius: 0.65rem; padding: 0.85rem; margin-bottom: 1.25rem; display: flex; flex-wrap: wrap; gap: 0.75rem; justify-content: space-between; align-items: center;">
            <input type="hidden" name="tab" value="sidang">
            @if($prodiFilter)
                <input type="hidden" name="prodi_id" value="{{ $prodiFilter }}">
            @endif

            <!-- Status Buttons -->
            <div style="display: flex; gap: 0.35rem; flex-wrap: wrap;">
                <button type="submit" name="status_sidang" value="" class="btn btn-sm {{ !request('status_sidang') ? 'btn-primary' : 'btn-secondary' }}" style="padding: 0.3rem 0.75rem; font-size: 0.78rem;">
                    Semua
                </button>
                <button type="submit" name="status_sidang" value="menunggu_jadwal" class="btn btn-sm {{ request('status_sidang') === 'menunggu_jadwal' ? 'btn-primary' : 'btn-secondary' }}" style="padding: 0.3rem 0.75rem; font-size: 0.78rem;">
                    Belum Ada Jadwal
                </button>
                <button type="submit" name="status_sidang" value="menunggu_nilai" class="btn btn-sm {{ request('status_sidang') === 'menunggu_nilai' ? 'btn-primary' : 'btn-secondary' }}" style="padding: 0.3rem 0.75rem; font-size: 0.78rem;">
                    Menunggu Nilai
                </button>
                <button type="submit" name="status_sidang" value="selesai" class="btn btn-sm {{ request('status_sidang') === 'selesai' ? 'btn-primary' : 'btn-secondary' }}" style="padding: 0.3rem 0.75rem; font-size: 0.78rem;">
                    Selesai Sidang
                </button>
            </div>

            <!-- Search & Sort FIFO -->
            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; align-items: center;">
                <div style="position: relative; width: 220px;">
                    <input type="text" name="search_sidang" value="{{ request('search_sidang') }}" placeholder="Cari Nama / NIM / Judul..." class="form-control" style="font-size: 0.8rem; padding: 0.35rem 0.65rem 0.35rem 1.85rem; height: 36px;">
                    <span style="position: absolute; left: 0.6rem; top: 50%; transform: translateY(-50%); font-size: 0.75rem; color: var(--text-muted);">🔍</span>
                </div>
                <select name="sort_sidang" class="form-control" style="font-size: 0.8rem; width: 170px; height: 36px; padding: 0.35rem 0.5rem;" onchange="this.form.submit()">
                    <option value="fifo" {{ request('sort_sidang', 'fifo') === 'fifo' ? 'selected' : '' }}>Urutan: Terlama (FIFO)</option>
                    <option value="lifo" {{ request('sort_sidang') === 'lifo' ? 'selected' : '' }}>Urutan: Terbaru Masuk</option>
                </select>
                <button type="submit" class="btn btn-secondary btn-sm" style="height: 36px;">Filter</button>
                @if(request('search_sidang') || request('status_sidang') || request('sort_sidang'))
                    <a href="{{ route('admin.administrasi.index', array_merge(['tab' => 'sidang'], $prodiFilter ? ['prodi_id' => $prodiFilter] : [])) }}" class="btn btn-secondary btn-sm" style="height: 36px;" title="Reset Filter">✕</a>
                @endif
            </div>
        </form>

        <!-- Data Table Tab 3 -->
        <div class="table-responsive" style="border: 1px solid var(--border); border-radius: 0.65rem;">
            <table>
                <thead>
                    <tr>
                        <th style="width: 50px; text-align: center;">No</th>
                        <th style="width: 170px;">Tgl Pengajuan (FIFO)</th>
                        <th style="width: 200px;">Mahasiswa</th>
                        <th>Judul & Dewan Penguji Sidang</th>
                        <th style="width: 190px;">Jadwal & Yudisium</th>
                        <th style="width: 130px; text-align: center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($daftarSidang as $sidang)
                        @php
                            $sk = $sidang->pengajuanSkripsi;
                            $isWaiting = empty($sidang->tgl_sidang) || empty($sidang->nilai_sidang);
                            $isUrgent = $isWaiting && $sidang->created_at->diffInDays(now()) >= 3;
                            $queueNumber = ($daftarSidang->currentPage() - 1) * $daftarSidang->perPage() + $loop->iteration;
                        @endphp
                        <tr style="background: {{ $isWaiting ? '#fffdf5' : '#ffffff' }};">
                            <td style="text-align: center; font-weight: 700;">
                                <span class="badge {{ $isWaiting ? 'badge-diajukan' : 'badge-diproses' }}" style="font-size: 0.72rem;">#{{ $queueNumber }}</span>
                            </td>
                            <td>
                                <div style="font-weight: 600; font-size: 0.8rem; color: #1e293b;">
                                    {{ $sidang->created_at->translatedFormat('d M Y') }}
                                    <span style="font-weight: 400; color: var(--text-muted); font-size: 0.75rem;">{{ $sidang->created_at->format('H:i') }}</span>
                                </div>
                                <div style="margin-top: 0.2rem;">
                                    @if ($isUrgent)
                                        <span class="badge badge-diajukan" style="font-size: 0.68rem; padding: 0.15rem 0.4rem;">
                                            ⏳ {{ $sidang->created_at->diffForHumans() }}
                                        </span>
                                    @else
                                        <span style="font-size: 0.72rem; color: var(--text-muted);">
                                            {{ $sidang->created_at->diffForHumans() }}
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <div style="font-weight: 700; color: #0f172a; font-size: 0.85rem;">{{ $sk->mahasiswa->name }}</div>
                                <div style="font-size: 0.75rem; color: var(--text-muted); font-family: monospace;">{{ $sk->mahasiswa->nomor_induk }}</div>
                            </td>
                            <td>
                                <div style="font-weight: 700; color: #0f172a; font-size: 0.88rem; line-height: 1.4;">
                                    "{{ $sk->judul }}"
                                </div>
                                <div style="font-size: 0.75rem; color: #2e6840; margin-top: 0.25rem; font-weight: 600;">
                                    ⚖️ Penguji Sidang: 1. {{ $sidang->penguji1 ? $sidang->penguji1->name : '-' }} | 2. {{ $sidang->penguji2 ? $sidang->penguji2->name : '-' }}
                                </div>
                                <!-- Berkas Links -->
                                <div style="display: flex; gap: 0.35rem; margin-top: 0.35rem; flex-wrap: wrap;">
                                    @if ($sidang->file_naskah_sidang)
                                        <a href="{{ route('dokumen.download', base64_encode($sidang->file_naskah_sidang)) }}" class="btn btn-secondary btn-sm" style="font-size: 0.68rem; padding: 0.15rem 0.45rem; min-height: 24px;">📘 Naskah Final</a>
                                    @endif
                                    @if ($sidang->file_acc_sidang)
                                        <a href="{{ route('dokumen.download', base64_encode($sidang->file_acc_sidang)) }}" class="btn btn-secondary btn-sm" style="font-size: 0.68rem; padding: 0.15rem 0.45rem; min-height: 24px;">✍️ ACC</a>
                                    @endif
                                    @if ($sidang->file_bebas_revisi_seminar)
                                        <a href="{{ route('dokumen.download', base64_encode($sidang->file_bebas_revisi_seminar)) }}" class="btn btn-secondary btn-sm" style="font-size: 0.68rem; padding: 0.15rem 0.45rem; min-height: 24px;">📄 Bebas Revisi</a>
                                    @endif
                                </div>
                            </td>
                            <td>
                                @if ($sidang->tgl_sidang)
                                    <div style="font-weight: 700; color: #1e293b; font-size: 0.82rem;">
                                        🗓️ {{ $sidang->tgl_sidang->format('d/m/Y') }} &bull; {{ $sidang->jam_sidang }}
                                    </div>
                                    <div style="font-size: 0.72rem; color: var(--text-muted);">
                                        📍 {{ $sidang->ruangan }}
                                    </div>
                                @else
                                    <span class="badge badge-diajukan" style="font-size: 0.7rem;">Belum Terjadwal</span>
                                @endif

                                <div style="margin-top: 0.35rem;">
                                    @if ($sidang->nilai_sidang !== null)
                                        <span class="badge badge-selesai" style="font-size: 0.72rem;">Nilai: {{ $sidang->nilai_sidang }}</span>
                                    @else
                                        <span class="badge badge-diproses" style="font-size: 0.7rem;">Belum Dinilai</span>
                                    @endif
                                </div>
                            </td>
                            <td style="text-align: center;">
                                <button type="button" class="btn {{ $isWaiting ? 'btn-primary' : 'btn-secondary' }} btn-sm" style="font-size: 0.78rem; padding: 0.35rem 0.75rem;" onclick="openDrawerAdminSidang({{ json_encode([
                                    'id' => $sidang->id,
                                    'mhs_name' => $sk->mahasiswa->name,
                                    'mhs_nim' => $sk->mahasiswa->nomor_induk,
                                    'judul' => $sk->judul,
                                    'penguji_info' => '1. ' . ($sidang->penguji1 ? $sidang->penguji1->name : '-') . ' | 2. ' . ($sidang->penguji2 ? $sidang->penguji2->name : '-'),
                                    'tgl_sidang' => $sidang->tgl_sidang ? $sidang->tgl_sidang->format('Y-m-d') : '',
                                    'jam_sidang' => $sidang->jam_sidang ?? '13:30 - 15:30',
                                    'ruangan' => $sidang->ruangan ?? 'Ruang Sidang Utama',
                                    'nomor_undangan' => $sidang->nomor_undangan_sidang,
                                    'nomor_sk' => $sidang->nomor_sk_sidang,
                                    'file_undangan_url' => $sidang->file_undangan_sidang ? route('dokumen.download', base64_encode($sidang->file_undangan_sidang)) : null,
                                    'file_sk_url' => $sidang->file_sk_sidang ? route('dokumen.download', base64_encode($sidang->file_sk_sidang)) : null,
                                    'nilai_sidang' => $sidang->nilai_sidang,
                                    'catatan' => $sidang->catatan,
                                    'is_locked_nilai' => ($sidang->nilai_sidang !== null && !$user->isAdminUtama()),
                                    'is_admin_utama' => $user->isAdminUtama(),
                                    'action_jadwal' => route('admin.sidang.jadwal-sk', $sidang->id),
                                    'action_selesai' => route('admin.sidang.selesai', $sidang->id),
                                ]) }})">
                                    ⚙️ Kelola
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 2.5rem;">
                                Tidak ada data pengajuan sidang skripsi untuk ditampilkan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div style="margin-top: 1rem;">
            {{ $daftarSidang->appends(['tab' => 'sidang'])->links() }}
        </div>
    </div>

    <!-- ========================================== -->
    <!-- TAB 4: DATA ARSIP SURAT & SK -->
    <!-- ========================================== -->
    <div id="tab-admin-surat" class="tab-content {{ $activeTab === 'surat' ? 'active' : '' }}">
        <div style="font-size: 0.88rem; color: var(--text-muted); margin-bottom: 1rem;">
            Tabel rekapitulasi data nomor surat undangan dan SK (Seminar & Skripsi) yang dikeluarkan oleh sistem beserta status keaktifan dan dokumen arsip PDF.
        </div>

        <!-- Filter Surat & SK -->
        <form method="GET" action="{{ route('admin.administrasi.index') }}" style="display: flex; flex-wrap: wrap; gap: 0.75rem; align-items: flex-end; margin-bottom: 1.25rem; padding: 0.85rem; background: #f8fafc; border: 1px solid var(--border); border-radius: 0.65rem;">
            <input type="hidden" name="tab" value="surat">
            @if($prodiFilter)
                <input type="hidden" name="prodi_id" value="{{ $prodiFilter }}">
            @endif

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
            <a href="{{ route('admin.administrasi.index', array_merge(['tab' => 'surat'], $prodiFilter ? ['prodi_id' => $prodiFilter] : [])) }}" class="btn btn-secondary btn-sm" style="height: 38px;">Reset</a>
        </form>

        <div class="table-responsive" style="border: 1px solid var(--border); border-radius: 0.65rem;">
            <table>
                <thead>
                    <tr>
                        <th style="padding: 0.75rem 1rem;">No. Surat / SK</th>
                        <th style="padding: 0.75rem 1rem;">Jenis</th>
                        <th style="padding: 0.75rem 1rem;">Nama Surat & Versi</th>
                        <th style="padding: 0.75rem 1rem;">Mahasiswa & Prodi</th>
                        <th style="padding: 0.75rem 1rem;">Tgl Terbit</th>
                        <th style="padding: 0.75rem 1rem;">Status</th>
                        <th style="padding: 0.75rem 1rem;">Penerbit</th>
                        <th style="padding: 0.75rem 1rem; text-align: center;">Berkas</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($daftarSurat as $surat)
                        <tr>
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
            {{ $daftarSurat->appends(['tab' => 'surat'])->links() }}
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- SLIDE-OVER DRAWER MODAL (ADMIN) -->
<!-- ========================================== -->
<div id="drawer-backdrop-admin" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.45); backdrop-filter: blur(2px); z-index: 100; transition: opacity 0.2s;" onclick="closeDrawerAdmin()"></div>

<div id="drawer-panel-admin" style="position: fixed; top: 0; right: 0; bottom: 0; width: 100%; max-width: 600px; background: #ffffff; z-index: 101; box-shadow: -4px 0 25px rgba(0,0,0,0.15); transform: translateX(100%); transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1); display: flex; flex-direction: column;">
    <!-- Drawer Header -->
    <div style="padding: 1.15rem 1.25rem; border-bottom: 1px solid var(--border); background: #f8fafc; display: flex; align-items: center; justify-content: space-between;">
        <div>
            <h2 id="drawer-admin-title" style="font-size: 1.05rem; font-weight: 700; color: #0f172a;">Pengelolaan Administrasi</h2>
            <div id="drawer-admin-subtitle" style="font-size: 0.78rem; color: var(--text-muted); margin-top: 0.15rem;"></div>
        </div>
        <button type="button" onclick="closeDrawerAdmin()" style="background: #e2e8f0; border: none; border-radius: 0.45rem; width: 32px; height: 32px; font-weight: 700; cursor: pointer; color: #475569;">✕</button>
    </div>

    <!-- Drawer Body (Scrollable) -->
    <div id="drawer-admin-body" style="flex: 1; overflow-y: auto; padding: 1.25rem; display: flex; flex-direction: column; gap: 1.25rem;">
        <!-- Injected via JS -->
    </div>
</div>

<script>
    const csrfToken = '{{ csrf_token() }}';

    function openDrawerAdminSk(data) {
        document.getElementById('drawer-admin-title').innerText = 'Penerbitan SK Dosen Pembimbing';
        document.getElementById('drawer-admin-subtitle').innerText = `${data.mhs_nim} • ${data.mhs_name} (${data.prodi})`;

        const body = document.getElementById('drawer-admin-body');

        let content = `
            <div style="background: #f8fafc; border: 1px solid var(--border); border-radius: 0.5rem; padding: 1rem;">
                <div style="font-size: 1rem; font-weight: 700; color: #0f172a; line-height: 1.4;">"${data.judul}"</div>
                <div style="font-size: 0.82rem; color: #2e6840; margin-top: 0.35rem; font-weight: 600;">👨‍🏫 Pembimbing: ${data.pembimbing_info}</div>
            </div>

            ${data.nomor_sk ? `
                <div style="font-size: 0.82rem; color: #1e40af; background: #eff6ff; padding: 0.5rem 0.75rem; border-radius: 0.35rem; border: 1px solid #bfdbfe;">
                    📜 SK Aktif: <strong>${data.nomor_sk}</strong><br>
                    <span style="font-size: 0.72rem; color: #2563eb;">* Jika disimpan ulang, sistem otomatis memberikan nama baru revisi SK (Revisi ke-n)</span>
                    ${data.file_sk_url ? `<div style="margin-top: 0.35rem;"><a href="${data.file_sk_url}" class="btn btn-secondary btn-sm" style="font-size: 0.72rem; padding: 0.15rem 0.5rem;">📥 Unduh File SK Aktif (PDF)</a></div>` : ''}
                </div>
            ` : ''}

            <form method="POST" action="${data.form_action}" enctype="multipart/form-data" class="space-y-4">
                <input type="hidden" name="_token" value="${csrfToken}">
                
                <div class="form-group">
                    <label class="form-label" style="font-size: 0.78rem;">Nomor SK Bimbingan *</label>
                    <input type="text" name="nomor_sk_bimbingan" value="${data.nomor_sk || ''}" class="form-control" placeholder="Contoh: SK/001/FPST/TI/2026" required>
                </div>

                <div class="form-group">
                    <label class="form-label" style="font-size: 0.78rem;">Tanggal Terbit SK *</label>
                    <input type="date" name="tgl_sk_bimbingan" value="${data.tgl_sk}" class="form-control" required>
                </div>

                <div class="form-group">
                    <label class="form-label" style="font-size: 0.78rem;">Upload File PDF SK Bimbingan</label>
                    <input type="file" name="file_sk_bimbingan" class="form-control" accept=".pdf">
                    <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 0.25rem;">Format PDF, maksimal 5MB.</div>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 0.5rem; margin-top: 1rem;">
                    <button type="button" onclick="closeDrawerAdmin()" class="btn btn-secondary btn-sm">Batal</button>
                    <button type="submit" class="btn btn-primary btn-sm">
                        ✓ ${data.nomor_sk ? 'Perbarui SK (Versi Baru)' : 'Simpan & Terbitkan SK'}
                    </button>
                </div>
            </form>
        `;

        body.innerHTML = content;
        showDrawerAdmin();
    }

    function openDrawerAdminSeminar(data) {
        document.getElementById('drawer-admin-title').innerText = 'Jadwal & Dokumen Seminar Skripsi';
        document.getElementById('drawer-admin-subtitle').innerText = `${data.mhs_nim} • ${data.mhs_name}`;

        const body = document.getElementById('drawer-admin-body');

        let content = `
            <div style="background: #f8fafc; border: 1px solid var(--border); border-radius: 0.5rem; padding: 1rem;">
                <div style="font-size: 1rem; font-weight: 700; color: #0f172a; line-height: 1.4;">"${data.judul}"</div>
                <div style="font-size: 0.82rem; color: #2e6840; margin-top: 0.35rem; font-weight: 600;">🎓 Penguji: ${data.penguji_name}</div>
            </div>

            <!-- Form 1: Jadwal & Surat -->
            <div style="border: 1px solid var(--border); border-radius: 0.5rem; padding: 1rem; background: #ffffff;">
                <div style="font-weight: 700; font-size: 0.85rem; margin-bottom: 0.75rem; color: #0f172a;">1. Pengaturan Jadwal & Dokumen Surat/SK:</div>
                <form method="POST" action="${data.action_jadwal}" enctype="multipart/form-data">
                    <input type="hidden" name="_token" value="${csrfToken}">
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                        <div class="form-group">
                            <label class="form-label" style="font-size: 0.75rem;">Tanggal Seminar *</label>
                            <input type="date" name="tgl_seminar" value="${data.tgl_seminar}" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" style="font-size: 0.75rem;">Jam Pelaksanaan *</label>
                            <input type="text" name="jam_seminar" value="${data.jam_seminar}" class="form-control" placeholder="09:00 - 10:30" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" style="font-size: 0.75rem;">Ruangan / Tempat *</label>
                        <input type="text" name="ruangan" value="${data.ruangan}" class="form-control" required>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                        <div class="form-group">
                            <label class="form-label" style="font-size: 0.75rem;">No. Undangan Seminar</label>
                            <input type="text" name="nomor_undangan_seminar" value="${data.nomor_undangan || ''}" class="form-control" placeholder="UND/01/FPST/2026">
                            ${data.file_undangan_url ? `
                                <div style="margin-top: 0.25rem;">
                                    <a href="${data.file_undangan_url}" class="btn btn-secondary btn-sm" style="font-size: 0.7rem; padding: 0.15rem 0.45rem;">📥 Unduh Undangan (PDF)</a>
                                </div>
                            ` : ''}
                        </div>
                        <div class="form-group">
                            <label class="form-label" style="font-size: 0.75rem;">No. SK Penguji Seminar</label>
                            <input type="text" name="nomor_sk_seminar" value="${data.nomor_sk || ''}" class="form-control" placeholder="SK-SEM/01/2026">
                            ${data.file_sk_url ? `
                                <div style="margin-top: 0.25rem;">
                                    <a href="${data.file_sk_url}" class="btn btn-secondary btn-sm" style="font-size: 0.7rem; padding: 0.15rem 0.45rem;">📥 Unduh SK Penguji (PDF)</a>
                                </div>
                            ` : ''}
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                        <div class="form-group">
                            <label class="form-label" style="font-size: 0.75rem;">Upload Undangan (Opsional)</label>
                            <input type="file" name="file_undangan_seminar" class="form-control" accept=".pdf">
                            <div style="font-size: 0.7rem; color: #166534; margin-top: 0.2rem;">* Otomatis dibuat sistem jika nomor diisi & file dikosongkan.</div>
                        </div>
                        <div class="form-group">
                            <label class="form-label" style="font-size: 0.75rem;">Upload File SK Penguji (PDF)</label>
                            <input type="file" name="file_sk_seminar" class="form-control" accept=".pdf">
                        </div>
                    </div>

                    <div style="display: flex; justify-content: flex-end; margin-top: 0.5rem;">
                        <button type="submit" class="btn btn-primary btn-sm">✓ Simpan Jadwal & Surat</button>
                    </div>
                </form>
            </div>

            <!-- Form 2: Input Nilai Seminar -->
            <div style="border: 1px solid var(--border); border-radius: 0.5rem; padding: 1rem; background: #ffffff;">
                <div style="font-weight: 700; font-size: 0.85rem; margin-bottom: 0.75rem; color: #166534;">2. Input Nilai & Finalisasi Hasil Seminar:</div>
                
                ${data.is_locked_nilai ? `
                    <div style="background: #f1f5f9; border: 1px solid #cbd5e1; border-radius: 0.5rem; padding: 0.85rem;">
                        <div style="font-weight: 700; color: #166534;">🔒 Nilai Seminar: <strong style="font-size: 1.1rem; color: #0f172a;">${data.nilai_seminar}</strong></div>
                        ${data.catatan ? `<div style="font-size: 0.8rem; color: #475569; margin-top: 0.2rem;">Catatan: ${data.catatan}</div>` : ''}
                        <div style="font-size: 0.72rem; color: #64748b; margin-top: 0.35rem;">* Terkunci. Hanya Admin Utama yang berwenang mengubah nilai yang sudah difinalisasi.</div>
                    </div>
                ` : `
                    ${data.nilai_seminar !== null && data.is_admin_utama ? `
                        <div style="background: #fee2e2; color: #991b1b; padding: 0.25rem 0.5rem; border-radius: 0.35rem; font-size: 0.72rem; font-weight: 700; margin-bottom: 0.5rem;">
                            👑 Hak Akses Admin Utama: Anda dapat mengubah nilai seminar
                        </div>
                    ` : ''}
                    <form method="POST" action="${data.action_selesai}">
                        <input type="hidden" name="_token" value="${csrfToken}">
                        <div style="display: grid; grid-template-columns: 140px 1fr; gap: 0.75rem;">
                            <div class="form-group">
                                <label class="form-label" style="font-size: 0.75rem;">Nilai Akhir (0-100) *</label>
                                <input type="number" step="0.01" min="0" max="100" name="nilai_seminar" value="${data.nilai_seminar !== null ? data.nilai_seminar : ''}" class="form-control" placeholder="85.50" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label" style="font-size: 0.75rem;">Catatan Hasil Seminar</label>
                                <input type="text" name="catatan" value="${data.catatan || ''}" class="form-control" placeholder="Catatan hasil / berita acara...">
                            </div>
                        </div>
                        <div style="display: flex; justify-content: flex-end; margin-top: 0.5rem;">
                            <button type="submit" class="btn btn-success btn-sm">
                                ✓ ${data.nilai_seminar !== null ? 'Perbarui Nilai Seminar' : 'Selesaikan & Luluskan Seminar'}
                            </button>
                        </div>
                    </form>
                `}
            </div>
        `;

        body.innerHTML = content;
        showDrawerAdmin();
    }

    function openDrawerAdminSidang(data) {
        document.getElementById('drawer-admin-title').innerText = 'Jadwal & Dokumen Sidang Skripsi';
        document.getElementById('drawer-admin-subtitle').innerText = `${data.mhs_nim} • ${data.mhs_name}`;

        const body = document.getElementById('drawer-admin-body');

        let content = `
            <div style="background: #f8fafc; border: 1px solid var(--border); border-radius: 0.5rem; padding: 1rem;">
                <div style="font-size: 1rem; font-weight: 700; color: #0f172a; line-height: 1.4;">"${data.judul}"</div>
                <div style="font-size: 0.82rem; color: #2e6840; margin-top: 0.35rem; font-weight: 600;">⚖️ Penguji Sidang: ${data.penguji_info}</div>
            </div>

            <!-- Form 1: Jadwal & Surat Sidang -->
            <div style="border: 1px solid var(--border); border-radius: 0.5rem; padding: 1rem; background: #ffffff;">
                <div style="font-weight: 700; font-size: 0.85rem; margin-bottom: 0.75rem; color: #0f172a;">1. Pengaturan Jadwal & Dokumen Surat/SK Sidang:</div>
                <form method="POST" action="${data.action_jadwal}" enctype="multipart/form-data">
                    <input type="hidden" name="_token" value="${csrfToken}">
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                        <div class="form-group">
                            <label class="form-label" style="font-size: 0.75rem;">Tanggal Sidang *</label>
                            <input type="date" name="tgl_sidang" value="${data.tgl_sidang}" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" style="font-size: 0.75rem;">Jam Pelaksanaan *</label>
                            <input type="text" name="jam_sidang" value="${data.jam_sidang}" class="form-control" placeholder="13:30 - 15:30" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" style="font-size: 0.75rem;">Ruangan / Tempat *</label>
                        <input type="text" name="ruangan" value="${data.ruangan}" class="form-control" required>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                        <div class="form-group">
                            <label class="form-label" style="font-size: 0.75rem;">No. Undangan Sidang</label>
                            <input type="text" name="nomor_undangan_sidang" value="${data.nomor_undangan || ''}" class="form-control" placeholder="UND-SDG/01/2026">
                            ${data.file_undangan_url ? `
                                <div style="margin-top: 0.25rem;">
                                    <a href="${data.file_undangan_url}" class="btn btn-secondary btn-sm" style="font-size: 0.7rem; padding: 0.15rem 0.45rem;">📥 Unduh Undangan (PDF)</a>
                                </div>
                            ` : ''}
                        </div>
                        <div class="form-group">
                            <label class="form-label" style="font-size: 0.75rem;">No. SK Dewan Penguji</label>
                            <input type="text" name="nomor_sk_sidang" value="${data.nomor_sk || ''}" class="form-control" placeholder="SK-SDG/01/2026">
                            ${data.file_sk_url ? `
                                <div style="margin-top: 0.25rem;">
                                    <a href="${data.file_sk_url}" class="btn btn-secondary btn-sm" style="font-size: 0.7rem; padding: 0.15rem 0.45rem;">📥 Unduh SK Penguji (PDF)</a>
                                </div>
                            ` : ''}
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                        <div class="form-group">
                            <label class="form-label" style="font-size: 0.75rem;">Upload Undangan (Opsional)</label>
                            <input type="file" name="file_undangan_sidang" class="form-control" accept=".pdf">
                            <div style="font-size: 0.7rem; color: #166534; margin-top: 0.2rem;">* Otomatis dibuat sistem jika nomor diisi & file dikosongkan.</div>
                        </div>
                        <div class="form-group">
                            <label class="form-label" style="font-size: 0.75rem;">Upload File SK Penguji (PDF)</label>
                            <input type="file" name="file_sk_sidang" class="form-control" accept=".pdf">
                        </div>
                    </div>

                    <div style="display: flex; justify-content: flex-end; margin-top: 0.5rem;">
                        <button type="submit" class="btn btn-primary btn-sm">✓ Simpan Jadwal & Surat</button>
                    </div>
                </form>
            </div>

            <!-- Form 2: Input Nilai & Yudisium Sidang -->
            <div style="border: 1px solid var(--border); border-radius: 0.5rem; padding: 1rem; background: #ffffff;">
                <div style="font-weight: 700; font-size: 0.85rem; margin-bottom: 0.75rem; color: #166534;">2. Input Nilai Akhir & Yudisium Kelulusan:</div>
                
                ${data.is_locked_nilai ? `
                    <div style="background: #f1f5f9; border: 1px solid #cbd5e1; border-radius: 0.5rem; padding: 0.85rem;">
                        <div style="font-weight: 700; color: #166534;">🔒 Nilai Akhir Sidang: <strong style="font-size: 1.1rem; color: #0f172a;">${data.nilai_sidang}</strong></div>
                        ${data.catatan ? `<div style="font-size: 0.8rem; color: #475569; margin-top: 0.2rem;">Yudisium: ${data.catatan}</div>` : ''}
                        <div style="font-size: 0.72rem; color: #64748b; margin-top: 0.35rem;">* Terkunci. Hanya Admin Utama yang berwenang mengubah nilai yang sudah difinalisasi.</div>
                    </div>
                ` : `
                    ${data.nilai_sidang !== null && data.is_admin_utama ? `
                        <div style="background: #fee2e2; color: #991b1b; padding: 0.25rem 0.5rem; border-radius: 0.35rem; font-size: 0.72rem; font-weight: 700; margin-bottom: 0.5rem;">
                            👑 Hak Akses Admin Utama: Anda dapat mengubah nilai sidang
                        </div>
                    ` : ''}
                    <form method="POST" action="${data.action_selesai}">
                        <input type="hidden" name="_token" value="${csrfToken}">
                        <div style="display: grid; grid-template-columns: 140px 1fr; gap: 0.75rem;">
                            <div class="form-group">
                                <label class="form-label" style="font-size: 0.75rem;">Nilai Akhir (0-100) *</label>
                                <input type="number" step="0.01" min="0" max="100" name="nilai_sidang" value="${data.nilai_sidang !== null ? data.nilai_sidang : ''}" class="form-control" placeholder="88.00" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label" style="font-size: 0.75rem;">Catatan Kelulusan / Predikat Yudisium</label>
                                <input type="text" name="catatan" value="${data.catatan || ''}" class="form-control" placeholder="Dinyatakan Lulus dengan predikat Sangat Memuaskan...">
                            </div>
                        </div>
                        <div style="display: flex; justify-content: flex-end; margin-top: 0.5rem;">
                            <button type="submit" class="btn btn-success btn-sm">
                                ✓ ${data.nilai_sidang !== null ? 'Perbarui Nilai Sidang' : 'Finalisasi Kelulusan Sidang Skripsi'}
                            </button>
                        </div>
                    </form>
                `}
            </div>
        `;

        body.innerHTML = content;
        showDrawerAdmin();
    }

    function showDrawerAdmin() {
        const backdrop = document.getElementById('drawer-backdrop-admin');
        const panel = document.getElementById('drawer-panel-admin');
        backdrop.style.display = 'block';
        setTimeout(() => {
            panel.style.transform = 'translateX(0)';
        }, 10);
    }

    function closeDrawerAdmin() {
        const backdrop = document.getElementById('drawer-backdrop-admin');
        const panel = document.getElementById('drawer-panel-admin');
        panel.style.transform = 'translateX(100%)';
        setTimeout(() => {
            backdrop.style.display = 'none';
        }, 300);
    }
</script>

@endsection
