@extends('layouts.app')

@section('title', 'Penetapan Pembimbing & Penguji')
@section('page_title', $user->isAdminUtama() ? 'Penetapan Pembimbing & Penguji (Admin Utama - Akses Penuh)' : ('Penetapan Pembimbing & Penguji (Kaprodi ' . ($user->programStudi ? $user->programStudi->nama : '') . ')'))

@section('content')

<div class="card" style="padding: 1.25rem;">
    @if ($user->isAdminUtama())
        <!-- Filter Prodi Khusus Admin Utama -->
        <form method="GET" action="{{ route('kaprodi.penetapan.index') }}" style="display: flex; flex-wrap: wrap; gap: 0.75rem; align-items: flex-end; margin-bottom: 1.5rem; padding-bottom: 1.25rem; border-bottom: 1px solid var(--border);">
            <div style="flex: 1; min-width: 250px;">
                <label class="form-label" style="font-size: 0.78rem; font-weight: 700; color: #1e293b;">Pilih Program Studi yang Dikelola (Akses Penuh Admin Utama):</label>
                <select name="prodi_id" class="form-control" style="font-size: 0.85rem;" onchange="this.form.submit()">
                    @foreach ($daftarProdi as $prodi)
                        <option value="{{ $prodi->id }}" {{ $prodiFilter == $prodi->id ? 'selected' : '' }}>
                            {{ $prodi->nama }} ({{ $prodi->kode }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div style="font-size: 0.8rem; color: var(--text-muted); align-self: center;">
                * Admin Utama berhak menentukan & mengubah dosen pembimbing serta penguji seminar/sidang untuk seluruh program studi.
            </div>
        </form>
    @endif

    <!-- Tab Navigasi -->
    <div class="tab-nav">
        <button type="button" class="tab-btn {{ !request('tab') || request('tab') === 'judul' ? 'active' : '' }}" onclick="switchTab(event, 'tab-judul')">
            1. Review Judul & Pembimbing
            <span class="badge {{ $pendingJudulCount > 0 ? 'badge-diajukan' : 'badge-diproses' }}" style="margin-left: 0.35rem; font-size: 0.7rem;">
                {{ $daftarJudul->total() }} ({{ $pendingJudulCount }} antrean)
            </span>
        </button>
        <button type="button" class="tab-btn {{ request('tab') === 'seminar' ? 'active' : '' }}" onclick="switchTab(event, 'tab-seminar')">
            2. Penguji Seminar
            <span class="badge {{ $pendingSeminarCount > 0 ? 'badge-diajukan' : 'badge-diproses' }}" style="margin-left: 0.35rem; font-size: 0.7rem;">
                {{ $daftarSeminar->total() }} ({{ $pendingSeminarCount }} antrean)
            </span>
        </button>
        <button type="button" class="tab-btn {{ request('tab') === 'sidang' ? 'active' : '' }}" onclick="switchTab(event, 'tab-sidang')">
            3. Penguji Sidang (2 Org)
            <span class="badge {{ $pendingSidangCount > 0 ? 'badge-diajukan' : 'badge-diproses' }}" style="margin-left: 0.35rem; font-size: 0.7rem;">
                {{ $daftarSidang->total() }} ({{ $pendingSidangCount }} antrean)
            </span>
        </button>
    </div>

    <!-- ========================================== -->
    <!-- TAB 1: REVIEW JUDUL & PEMBIMBING -->
    <!-- ========================================== -->
    <div id="tab-judul" class="tab-content {{ !request('tab') || request('tab') === 'judul' ? 'active' : '' }}">
        <div style="font-size: 0.88rem; color: var(--text-muted); margin-bottom: 1rem;">
            Tinjau judul dan berkas persyaratan awal mahasiswa, lalu tetapkan Dosen Pembimbing 1 dan Pembimbing 2 berdasarkan antrean masuk.
        </div>

        <!-- Filter & Search Toolbar Tab 1 -->
        <form method="GET" action="{{ route('kaprodi.penetapan.index') }}" style="background: #f8fafc; border: 1px solid var(--border); border-radius: 0.65rem; padding: 0.85rem; margin-bottom: 1.25rem; display: flex; flex-wrap: wrap; gap: 0.75rem; justify-content: space-between; align-items: center;">
            <input type="hidden" name="tab" value="judul">
            @if($user->isAdminUtama() && $prodiFilter)
                <input type="hidden" name="prodi_id" value="{{ $prodiFilter }}">
            @endif

            <!-- Status Buttons -->
            <div style="display: flex; gap: 0.35rem; flex-wrap: wrap;">
                <button type="submit" name="status_judul" value="" class="btn btn-sm {{ !request('status_judul') ? 'btn-primary' : 'btn-secondary' }}" style="padding: 0.3rem 0.75rem; font-size: 0.78rem;">
                    Semua
                </button>
                <button type="submit" name="status_judul" value="menunggu" class="btn btn-sm {{ request('status_judul') === 'menunggu' ? 'btn-primary' : 'btn-secondary' }}" style="padding: 0.3rem 0.75rem; font-size: 0.78rem;">
                    Menunggu Penetapan ({{ $pendingJudulCount }})
                </button>
                <button type="submit" name="status_judul" value="selesai" class="btn btn-sm {{ request('status_judul') === 'selesai' ? 'btn-primary' : 'btn-secondary' }}" style="padding: 0.3rem 0.75rem; font-size: 0.78rem;">
                    Selesai Ditetapkan
                </button>
            </div>

            <!-- Search & Sort FIFO -->
            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; align-items: center;">
                <div style="position: relative; width: 220px;">
                    <input type="text" name="search_judul" value="{{ request('search_judul') }}" placeholder="Cari Nama / NIM / Judul..." class="form-control" style="font-size: 0.8rem; padding: 0.35rem 0.65rem 0.35rem 1.85rem; height: 36px;">
                    <span style="position: absolute; left: 0.6rem; top: 50%; transform: translateY(-50%); font-size: 0.75rem; color: var(--text-muted);">🔍</span>
                </div>
                <select name="sort_judul" class="form-control" style="font-size: 0.8rem; width: 170px; height: 36px; padding: 0.35rem 0.5rem;" onchange="this.form.submit()">
                    <option value="fifo" {{ request('sort_judul', 'fifo') === 'fifo' ? 'selected' : '' }}>Urutan: Terlama (FIFO)</option>
                    <option value="lifo" {{ request('sort_judul') === 'lifo' ? 'selected' : '' }}>Urutan: Terbaru Masuk</option>
                </select>
                <button type="submit" class="btn btn-secondary btn-sm" style="height: 36px;">Filter</button>
                @if(request('search_judul') || request('status_judul') || request('sort_judul'))
                    <a href="{{ route('kaprodi.penetapan.index', array_merge(['tab' => 'judul'], $user->isAdminUtama() && $prodiFilter ? ['prodi_id' => $prodiFilter] : [])) }}" class="btn btn-secondary btn-sm" style="height: 36px;" title="Reset Filter">✕</a>
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
                        <th>Judul & Abstrak Skripsi</th>
                        <th style="width: 140px;">Status</th>
                        <th style="width: 130px; text-align: center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($daftarJudul as $skripsi)
                        @php
                            $isWaiting = $skripsi->status->value === 'diajukan';
                            $isUrgent = $isWaiting && $skripsi->created_at->diffInDays(now()) >= 3;
                            $queueNumber = ($daftarJudul->currentPage() - 1) * $daftarJudul->perPage() + $loop->iteration;
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
                                        <span class="badge badge-diajukan" style="font-size: 0.68rem; padding: 0.15rem 0.4rem;" title="Pengajuan telah menunggu cukup lama">
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
                                <div style="font-size: 0.75rem; color: var(--text-muted); font-family: monospace;">{{ $skripsi->mahasiswa->nomor_induk }}</div>
                            </td>
                            <td>
                                <div style="font-weight: 700; color: #0f172a; font-size: 0.88rem; line-height: 1.4;">
                                    "{{ $skripsi->judul }}"
                                </div>
                                @if($skripsi->pembimbing1)
                                    <div style="font-size: 0.75rem; color: #2e6840; margin-top: 0.3rem; font-weight: 600;">
                                        👨‍🏫 Pembimbing: 1. {{ $skripsi->pembimbing1->name }} {{ $skripsi->pembimbing2 ? '| 2. ' . $skripsi->pembimbing2->name : '' }}
                                    </div>
                                @endif
                                <!-- Berkas Links -->
                                <div style="display: flex; gap: 0.35rem; margin-top: 0.35rem; flex-wrap: wrap;">
                                    @if ($skripsi->file_proposal)
                                        <a href="{{ route('dokumen.download', base64_encode($skripsi->file_proposal)) }}" class="btn btn-secondary btn-sm" style="font-size: 0.68rem; padding: 0.15rem 0.45rem; min-height: 24px;">
                                            📄 Draf Proposal
                                        </a>
                                    @endif
                                    @if ($skripsi->file_transkrip)
                                        <a href="{{ route('dokumen.download', base64_encode($skripsi->file_transkrip)) }}" class="btn btn-secondary btn-sm" style="font-size: 0.68rem; padding: 0.15rem 0.45rem; min-height: 24px;">
                                            📊 Transkrip
                                        </a>
                                    @endif
                                    @if ($skripsi->file_bukti_bayar)
                                        <a href="{{ route('dokumen.download', base64_encode($skripsi->file_bukti_bayar)) }}" class="btn btn-secondary btn-sm" style="font-size: 0.68rem; padding: 0.15rem 0.45rem; min-height: 24px;">
                                            💳 Bukti Bayar
                                        </a>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <span class="badge badge-{{ $skripsi->status->value }}">{{ $skripsi->status->label() }}</span>
                            </td>
                            <td style="text-align: center;">
                                <button type="button" class="btn {{ $isWaiting ? 'btn-primary' : 'btn-secondary' }} btn-sm" style="font-size: 0.78rem; padding: 0.35rem 0.75rem;" onclick="openDrawerJudul({{ json_encode([
                                    'id' => $skripsi->id,
                                    'mhs_name' => $skripsi->mahasiswa->name,
                                    'mhs_nim' => $skripsi->mahasiswa->nomor_induk,
                                    'judul' => $skripsi->judul,
                                    'abstrak' => $skripsi->abstrak,
                                    'status_label' => $skripsi->status->label(),
                                    'status_val' => $skripsi->status->value,
                                    'tgl_pengajuan' => $skripsi->created_at->translatedFormat('d F Y, H:i') . ' (' . $skripsi->created_at->diffForHumans() . ')',
                                    'pembimbing_1_id' => $skripsi->pembimbing_1_id,
                                    'pembimbing_2_id' => $skripsi->pembimbing_2_id,
                                    'pembimbing1_name' => $skripsi->pembimbing1 ? $skripsi->pembimbing1->name : null,
                                    'pembimbing2_name' => $skripsi->pembimbing2 ? $skripsi->pembimbing2->name : null,
                                    'is_locked' => ($skripsi->pembimbing_1_id !== null && !$user->isAdminUtama()),
                                    'is_admin' => $user->isAdminUtama(),
                                    'file_proposal' => $skripsi->file_proposal ? route('dokumen.download', base64_encode($skripsi->file_proposal)) : null,
                                    'file_transkrip' => $skripsi->file_transkrip ? route('dokumen.download', base64_encode($skripsi->file_transkrip)) : null,
                                    'file_bukti_bayar' => $skripsi->file_bukti_bayar ? route('dokumen.download', base64_encode($skripsi->file_bukti_bayar)) : null,
                                    'form_action' => route('kaprodi.skripsi.review', $skripsi->id),
                                ]) }})">
                                    {{ $isWaiting ? '✓ Tetapkan' : ($user->isAdminUtama() ? '✏️ Ubah' : '👁️ Detail') }}
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 2.5rem;">
                                Tidak ada data pengajuan judul yang sesuai dengan filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div style="margin-top: 1rem;">
            {{ $daftarJudul->links() }}
        </div>
    </div>

    <!-- ========================================== -->
    <!-- TAB 2: PENETAPAN PENGUJI SEMINAR -->
    <!-- ========================================== -->
    <div id="tab-seminar" class="tab-content {{ request('tab') === 'seminar' ? 'active' : '' }}">
        <div style="font-size: 0.88rem; color: var(--text-muted); margin-bottom: 1rem;">
            Daftar pengajuan seminar proposal/hasil. Tetapkan Dosen Penguji Seminar untuk setiap mahasiswa berdasarkan antrean.
        </div>

        <!-- Filter & Search Toolbar Tab 2 -->
        <form method="GET" action="{{ route('kaprodi.penetapan.index') }}" style="background: #f8fafc; border: 1px solid var(--border); border-radius: 0.65rem; padding: 0.85rem; margin-bottom: 1.25rem; display: flex; flex-wrap: wrap; gap: 0.75rem; justify-content: space-between; align-items: center;">
            <input type="hidden" name="tab" value="seminar">
            @if($user->isAdminUtama() && $prodiFilter)
                <input type="hidden" name="prodi_id" value="{{ $prodiFilter }}">
            @endif

            <!-- Status Buttons -->
            <div style="display: flex; gap: 0.35rem; flex-wrap: wrap;">
                <button type="submit" name="status_seminar" value="" class="btn btn-sm {{ !request('status_seminar') ? 'btn-primary' : 'btn-secondary' }}" style="padding: 0.3rem 0.75rem; font-size: 0.78rem;">
                    Semua
                </button>
                <button type="submit" name="status_seminar" value="menunggu" class="btn btn-sm {{ request('status_seminar') === 'menunggu' ? 'btn-primary' : 'btn-secondary' }}" style="padding: 0.3rem 0.75rem; font-size: 0.78rem;">
                    Menunggu Penguji ({{ $pendingSeminarCount }})
                </button>
                <button type="submit" name="status_seminar" value="selesai" class="btn btn-sm {{ request('status_seminar') === 'selesai' ? 'btn-primary' : 'btn-secondary' }}" style="padding: 0.3rem 0.75rem; font-size: 0.78rem;">
                    Selesai Ditetapkan
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
                    <a href="{{ route('kaprodi.penetapan.index', array_merge(['tab' => 'seminar'], $user->isAdminUtama() && $prodiFilter ? ['prodi_id' => $prodiFilter] : [])) }}" class="btn btn-secondary btn-sm" style="height: 36px;" title="Reset Filter">✕</a>
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
                        <th>Judul & Berkas Seminar</th>
                        <th style="width: 140px;">Status</th>
                        <th style="width: 130px; text-align: center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($daftarSeminar as $seminar)
                        @php
                            $sk = $seminar->pengajuanSkripsi;
                            $isWaiting = empty($seminar->penguji_seminar_id);
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
                                <div style="font-size: 0.75rem; color: #475569; margin-top: 0.25rem;">
                                    Pembimbing: 1. {{ $sk->pembimbing1 ? $sk->pembimbing1->name : '-' }} | 2. {{ $sk->pembimbing2 ? $sk->pembimbing2->name : '-' }}
                                </div>
                                @if($seminar->penguji)
                                    <div style="font-size: 0.75rem; color: #2e6840; margin-top: 0.25rem; font-weight: 600;">
                                        🎓 Penguji: {{ $seminar->penguji->name }}
                                    </div>
                                @endif
                                <!-- Berkas Links -->
                                <div style="display: flex; gap: 0.35rem; margin-top: 0.35rem; flex-wrap: wrap;">
                                    @if ($seminar->file_naskah_seminar)
                                        <a href="{{ route('dokumen.download', base64_encode($seminar->file_naskah_seminar)) }}" class="btn btn-secondary btn-sm" style="font-size: 0.68rem; padding: 0.15rem 0.45rem; min-height: 24px;">
                                            📄 Naskah
                                        </a>
                                    @endif
                                    @if ($seminar->file_acc_pembimbing)
                                        <a href="{{ route('dokumen.download', base64_encode($seminar->file_acc_pembimbing)) }}" class="btn btn-secondary btn-sm" style="font-size: 0.68rem; padding: 0.15rem 0.45rem; min-height: 24px;">
                                            ✍️ ACC
                                        </a>
                                    @endif
                                    @if ($seminar->file_bukti_bayar_seminar)
                                        <a href="{{ route('dokumen.download', base64_encode($seminar->file_bukti_bayar_seminar)) }}" class="btn btn-secondary btn-sm" style="font-size: 0.68rem; padding: 0.15rem 0.45rem; min-height: 24px;">
                                            💳 Bayar
                                        </a>
                                    @endif
                                    @if ($seminar->file_toefl)
                                        <a href="{{ route('dokumen.download', base64_encode($seminar->file_toefl)) }}" class="btn btn-secondary btn-sm" style="font-size: 0.68rem; padding: 0.15rem 0.45rem; min-height: 24px;">
                                            📜 TOEFL
                                        </a>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <span class="badge badge-{{ $seminar->status->value }}">{{ $seminar->status->label() }}</span>
                            </td>
                            <td style="text-align: center;">
                                <button type="button" class="btn {{ $isWaiting ? 'btn-primary' : 'btn-secondary' }} btn-sm" style="font-size: 0.78rem; padding: 0.35rem 0.75rem;" onclick="openDrawerSeminar({{ json_encode([
                                    'id' => $seminar->id,
                                    'mhs_name' => $sk->mahasiswa->name,
                                    'mhs_nim' => $sk->mahasiswa->nomor_induk,
                                    'judul' => $sk->judul,
                                    'pembimbing_info' => 'Pembimbing 1: ' . ($sk->pembimbing1 ? $sk->pembimbing1->name : '-') . ' | Pembimbing 2: ' . ($sk->pembimbing2 ? $sk->pembimbing2->name : '-'),
                                    'status_label' => $seminar->status->label(),
                                    'status_val' => $seminar->status->value,
                                    'tgl_pengajuan' => $seminar->created_at->translatedFormat('d F Y, H:i') . ' (' . $seminar->created_at->diffForHumans() . ')',
                                    'penguji_seminar_id' => $seminar->penguji_seminar_id,
                                    'penguji_name' => $seminar->penguji ? $seminar->penguji->name : null,
                                    'is_locked' => ($seminar->penguji_seminar_id !== null && !$user->isAdminUtama()),
                                    'is_admin' => $user->isAdminUtama(),
                                    'file_naskah' => $seminar->file_naskah_seminar ? route('dokumen.download', base64_encode($seminar->file_naskah_seminar)) : null,
                                    'file_acc' => $seminar->file_acc_pembimbing ? route('dokumen.download', base64_encode($seminar->file_acc_pembimbing)) : null,
                                    'file_bayar' => $seminar->file_bukti_bayar_seminar ? route('dokumen.download', base64_encode($seminar->file_bukti_bayar_seminar)) : null,
                                    'file_toefl' => $seminar->file_toefl ? route('dokumen.download', base64_encode($seminar->file_toefl)) : null,
                                    'form_action' => route('kaprodi.seminar.penguji', $seminar->id),
                                ]) }})">
                                    {{ $isWaiting ? '✓ Tetapkan' : ($user->isAdminUtama() ? '✏️ Ubah' : '👁️ Detail') }}
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 2.5rem;">
                                Tidak ada data pengajuan seminar yang sesuai dengan filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div style="margin-top: 1rem;">
            {{ $daftarSeminar->links() }}
        </div>
    </div>

    <!-- ========================================== -->
    <!-- TAB 3: PENETAPAN 2 PENGUJI SIDANG -->
    <!-- ========================================== -->
    <div id="tab-sidang" class="tab-content {{ request('tab') === 'sidang' ? 'active' : '' }}">
        <div style="font-size: 0.88rem; color: var(--text-muted); margin-bottom: 1rem;">
            Daftar pengajuan sidang meja hijau. Tetapkan 2 Orang Dosen Penguji Sidang untuk setiap mahasiswa berdasarkan antrean.
        </div>

        <!-- Filter & Search Toolbar Tab 3 -->
        <form method="GET" action="{{ route('kaprodi.penetapan.index') }}" style="background: #f8fafc; border: 1px solid var(--border); border-radius: 0.65rem; padding: 0.85rem; margin-bottom: 1.25rem; display: flex; flex-wrap: wrap; gap: 0.75rem; justify-content: space-between; align-items: center;">
            <input type="hidden" name="tab" value="sidang">
            @if($user->isAdminUtama() && $prodiFilter)
                <input type="hidden" name="prodi_id" value="{{ $prodiFilter }}">
            @endif

            <!-- Status Buttons -->
            <div style="display: flex; gap: 0.35rem; flex-wrap: wrap;">
                <button type="submit" name="status_sidang" value="" class="btn btn-sm {{ !request('status_sidang') ? 'btn-primary' : 'btn-secondary' }}" style="padding: 0.3rem 0.75rem; font-size: 0.78rem;">
                    Semua
                </button>
                <button type="submit" name="status_sidang" value="menunggu" class="btn btn-sm {{ request('status_sidang') === 'menunggu' ? 'btn-primary' : 'btn-secondary' }}" style="padding: 0.3rem 0.75rem; font-size: 0.78rem;">
                    Menunggu Penguji ({{ $pendingSidangCount }})
                </button>
                <button type="submit" name="status_sidang" value="selesai" class="btn btn-sm {{ request('status_sidang') === 'selesai' ? 'btn-primary' : 'btn-secondary' }}" style="padding: 0.3rem 0.75rem; font-size: 0.78rem;">
                    Selesai Ditetapkan
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
                    <a href="{{ route('kaprodi.penetapan.index', array_merge(['tab' => 'sidang'], $user->isAdminUtama() && $prodiFilter ? ['prodi_id' => $prodiFilter] : [])) }}" class="btn btn-secondary btn-sm" style="height: 36px;" title="Reset Filter">✕</a>
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
                        <th style="width: 140px;">Status</th>
                        <th style="width: 130px; text-align: center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($daftarSidang as $sidang)
                        @php
                            $sk = $sidang->pengajuanSkripsi;
                            $isWaiting = empty($sidang->penguji_1_id) || empty($sidang->penguji_2_id);
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
                                <div style="font-size: 0.75rem; color: #475569; margin-top: 0.25rem;">
                                    Pembimbing: 1. {{ $sk->pembimbing1 ? $sk->pembimbing1->name : '-' }} | 2. {{ $sk->pembimbing2 ? $sk->pembimbing2->name : '-' }}
                                </div>
                                @if($sidang->penguji1 || $sidang->penguji2)
                                    <div style="font-size: 0.75rem; color: #2e6840; margin-top: 0.25rem; font-weight: 600;">
                                        ⚖️ Penguji Sidang: 1. {{ $sidang->penguji1 ? $sidang->penguji1->name : '-' }} | 2. {{ $sidang->penguji2 ? $sidang->penguji2->name : '-' }}
                                    </div>
                                @endif
                                <!-- Berkas Links -->
                                <div style="display: flex; gap: 0.35rem; margin-top: 0.35rem; flex-wrap: wrap;">
                                    @if ($sidang->file_naskah_sidang)
                                        <a href="{{ route('dokumen.download', base64_encode($sidang->file_naskah_sidang)) }}" class="btn btn-secondary btn-sm" style="font-size: 0.68rem; padding: 0.15rem 0.45rem; min-height: 24px;">
                                            📘 Naskah Final
                                        </a>
                                    @endif
                                    @if ($sidang->file_acc_sidang)
                                        <a href="{{ route('dokumen.download', base64_encode($sidang->file_acc_sidang)) }}" class="btn btn-secondary btn-sm" style="font-size: 0.68rem; padding: 0.15rem 0.45rem; min-height: 24px;">
                                            ✍️ ACC Sidang
                                        </a>
                                    @endif
                                    @if ($sidang->file_bebas_revisi_seminar)
                                        <a href="{{ route('dokumen.download', base64_encode($sidang->file_bebas_revisi_seminar)) }}" class="btn btn-secondary btn-sm" style="font-size: 0.68rem; padding: 0.15rem 0.45rem; min-height: 24px;">
                                            📄 Bebas Revisi
                                        </a>
                                    @endif
                                    @if ($sidang->file_bukti_bayar_sidang)
                                        <a href="{{ route('dokumen.download', base64_encode($sidang->file_bukti_bayar_sidang)) }}" class="btn btn-secondary btn-sm" style="font-size: 0.68rem; padding: 0.15rem 0.45rem; min-height: 24px;">
                                            💳 Bukti Bayar
                                        </a>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <span class="badge badge-{{ $sidang->status->value }}">{{ $sidang->status->label() }}</span>
                            </td>
                            <td style="text-align: center;">
                                <button type="button" class="btn {{ $isWaiting ? 'btn-primary' : 'btn-secondary' }} btn-sm" style="font-size: 0.78rem; padding: 0.35rem 0.75rem;" onclick="openDrawerSidang({{ json_encode([
                                    'id' => $sidang->id,
                                    'mhs_name' => $sk->mahasiswa->name,
                                    'mhs_nim' => $sk->mahasiswa->nomor_induk,
                                    'judul' => $sk->judul,
                                    'pembimbing_info' => 'Pembimbing 1: ' . ($sk->pembimbing1 ? $sk->pembimbing1->name : '-') . ' | Pembimbing 2: ' . ($sk->pembimbing2 ? $sk->pembimbing2->name : '-'),
                                    'status_label' => $sidang->status->label(),
                                    'status_val' => $sidang->status->value,
                                    'tgl_pengajuan' => $sidang->created_at->translatedFormat('d F Y, H:i') . ' (' . $sidang->created_at->diffForHumans() . ')',
                                    'penguji_1_id' => $sidang->penguji_1_id,
                                    'penguji_2_id' => $sidang->penguji_2_id,
                                    'penguji1_name' => $sidang->penguji1 ? $sidang->penguji1->name : null,
                                    'penguji2_name' => $sidang->penguji2 ? $sidang->penguji2->name : null,
                                    'is_locked' => (($sidang->penguji_1_id !== null || $sidang->penguji_2_id !== null) && !$user->isAdminUtama()),
                                    'is_admin' => $user->isAdminUtama(),
                                    'file_naskah' => $sidang->file_naskah_sidang ? route('dokumen.download', base64_encode($sidang->file_naskah_sidang)) : null,
                                    'file_acc' => $sidang->file_acc_sidang ? route('dokumen.download', base64_encode($sidang->file_acc_sidang)) : null,
                                    'file_bebas' => $sidang->file_bebas_revisi_seminar ? route('dokumen.download', base64_encode($sidang->file_bebas_revisi_seminar)) : null,
                                    'file_bayar' => $sidang->file_bukti_bayar_sidang ? route('dokumen.download', base64_encode($sidang->file_bukti_bayar_sidang)) : null,
                                    'form_action' => route('kaprodi.sidang.penguji', $sidang->id),
                                ]) }})">
                                    {{ $isWaiting ? '✓ Tetapkan' : ($user->isAdminUtama() ? '✏️ Ubah' : '👁️ Detail') }}
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 2.5rem;">
                                Tidak ada data pengajuan sidang yang sesuai dengan filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div style="margin-top: 1rem;">
            {{ $daftarSidang->links() }}
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- SLIDE-OVER DRAWER MODAL (UNIVERSAL) -->
<!-- ========================================== -->
<div id="drawer-backdrop" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.45); backdrop-filter: blur(2px); z-index: 100; transition: opacity 0.2s;" onclick="closeDrawer()"></div>

<div id="drawer-panel" style="position: fixed; top: 0; right: 0; bottom: 0; width: 100%; max-width: 580px; background: #ffffff; z-index: 101; box-shadow: -4px 0 25px rgba(0,0,0,0.15); transform: translateX(100%); transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1); display: flex; flex-direction: column;">
    <!-- Drawer Header -->
    <div style="padding: 1.15rem 1.25rem; border-bottom: 1px solid var(--border); background: #f8fafc; display: flex; align-items: center; justify-content: space-between;">
        <div>
            <div style="display: flex; align-items: center; gap: 0.5rem;">
                <span id="drawer-badge" class="badge"></span>
                <span id="drawer-tgl" style="font-size: 0.75rem; color: var(--text-muted);"></span>
            </div>
            <h2 id="drawer-title" style="font-size: 1.05rem; font-weight: 700; color: #0f172a; margin-top: 0.25rem;">Review & Penetapan</h2>
        </div>
        <button type="button" onclick="closeDrawer()" style="background: #e2e8f0; border: none; border-radius: 0.45rem; width: 32px; height: 32px; font-weight: 700; cursor: pointer; color: #475569;">✕</button>
    </div>

    <!-- Drawer Body (Scrollable) -->
    <div id="drawer-body" style="flex: 1; overflow-y: auto; padding: 1.25rem; display: flex; flex-direction: column; gap: 1.25rem;">
        <!-- Dynamic content injected by JS -->
    </div>
</div>

<!-- TEMPLATE DOSEN OPTIONS UNTUK JS -->
<template id="dosen-options-template">
    @foreach ($daftarDosen as $dosen)
        <option value="{{ $dosen->id }}">{{ $dosen->name }} ({{ $dosen->nomor_induk }})</option>
    @endforeach
</template>

<script>
    const csrfToken = '{{ csrf_token() }}';
    const dosenOptionsHtml = document.getElementById('dosen-options-template').innerHTML;

    function openDrawerJudul(data) {
        const panel = document.getElementById('drawer-panel');
        const backdrop = document.getElementById('drawer-backdrop');
        const body = document.getElementById('drawer-body');

        document.getElementById('drawer-title').innerText = 'Review Judul & Penetapan Pembimbing';
        const badge = document.getElementById('drawer-badge');
        badge.className = `badge badge-${data.status_val}`;
        badge.innerText = data.status_label;
        document.getElementById('drawer-tgl').innerText = data.tgl_pengajuan;

        let berkasHtml = '';
        if (data.file_proposal) berkasHtml += `<a href="${data.file_proposal}" class="btn btn-secondary btn-sm" style="font-size: 0.75rem;">📄 Draf Proposal</a> `;
        if (data.file_transkrip) berkasHtml += `<a href="${data.file_transkrip}" class="btn btn-secondary btn-sm" style="font-size: 0.75rem;">📊 Transkrip Nilai</a> `;
        if (data.file_bukti_bayar) berkasHtml += `<a href="${data.file_bukti_bayar}" class="btn btn-secondary btn-sm" style="font-size: 0.75rem;">💳 Bukti Bayar</a> `;

        let content = `
            <!-- Mahasiswa & Judul Info -->
            <div style="background: #f8fafc; border: 1px solid var(--border); border-radius: 0.5rem; padding: 1rem;">
                <div style="font-size: 0.8rem; font-weight: 700; color: var(--text-muted);">${data.mhs_nim} &bull; ${data.mhs_name}</div>
                <div style="font-size: 1rem; font-weight: 700; color: #0f172a; margin-top: 0.25rem; line-height: 1.4;">"${data.judul}"</div>
                ${data.abstrak ? `
                    <div style="margin-top: 0.75rem;">
                        <div style="font-size: 0.72rem; font-weight: 700; text-transform: uppercase; color: var(--text-muted); margin-bottom: 0.25rem;">Abstrak:</div>
                        <div style="font-size: 0.82rem; color: #334155; line-height: 1.5; background: #fff; border: 1px solid var(--border); border-radius: 0.375rem; padding: 0.65rem; max-height: 120px; overflow-y: auto;">${data.abstrak}</div>
                    </div>
                ` : ''}
                <div style="margin-top: 0.75rem; display: flex; gap: 0.4rem; flex-wrap: wrap;">
                    ${berkasHtml}
                </div>
            </div>
        `;

        if (data.is_locked) {
            content += `
                <div style="background: #f1f5f9; border: 1px solid #cbd5e1; border-radius: 0.5rem; padding: 1rem;">
                    <div style="font-weight: 700; font-size: 0.82rem; color: #334155; margin-bottom: 0.4rem;">
                        🔒 Dosen Pembimbing Telah Ditetapkan (Terkunci)
                    </div>
                    <div style="font-size: 0.85rem; color: #475569;">
                        Pembimbing 1 (Utama): <strong>${data.pembimbing1_name || '-'}</strong><br>
                        Pembimbing 2: <strong>${data.pembimbing2_name || 'Tanpa Pembimbing 2'}</strong>
                    </div>
                    <div style="font-size: 0.75rem; color: #64748b; margin-top: 0.4rem;">
                        * Sesuai aturan sistem, hanya Admin Utama yang berwenang mengubah Dosen Pembimbing setelah ditentukan.
                    </div>
                </div>
            `;
        } else {
            content += `
                <!-- Form Penetapan Pembimbing -->
                <div style="background: #ffffff; border: 1px solid var(--border); border-radius: 0.5rem; padding: 1rem;">
                    ${data.pembimbing_1_id && data.is_admin ? `
                        <div style="background: #fee2e2; color: #991b1b; padding: 0.25rem 0.5rem; border-radius: 0.35rem; font-size: 0.75rem; font-weight: 700; margin-bottom: 0.75rem;">
                            👑 Wewenang Admin Utama: Anda dapat mengubah Dosen Pembimbing
                        </div>
                    ` : ''}

                    <form method="POST" action="${data.form_action}">
                        <input type="hidden" name="_token" value="${csrfToken}">
                        
                        <div class="form-group">
                            <label class="form-label" style="font-size: 0.78rem;">Dosen Pembimbing 1 (Utama) *</label>
                            <select name="pembimbing_1_id" class="form-control" style="font-size: 0.85rem;" required id="drawer_p1">
                                <option value="">-- Pilih Pembimbing 1 --</option>
                                ${dosenOptionsHtml}
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label" style="font-size: 0.78rem;">Dosen Pembimbing 2 (Opsional)</label>
                            <select name="pembimbing_2_id" class="form-control" style="font-size: 0.85rem;" id="drawer_p2">
                                <option value="">-- Tanpa Pembimbing 2 --</option>
                                ${dosenOptionsHtml}
                            </select>
                        </div>

                        <div style="display: flex; justify-content: flex-end; gap: 0.5rem; margin-top: 1rem;">
                            <button type="button" onclick="closeDrawer()" class="btn btn-secondary btn-sm">Batal</button>
                            <button type="submit" name="action" value="terima" class="btn btn-primary btn-sm">
                                ✓ ${data.pembimbing_1_id ? 'Perbarui Penetapan Pembimbing' : 'Simpan & Tetapkan Pembimbing'}
                            </button>
                        </div>
                    </form>

                    <!-- Form Tolak -->
                    ${data.status_val === 'diajukan' ? `
                        <details style="margin-top: 1rem; border-top: 1px dashed var(--border); padding-top: 0.75rem;">
                            <summary style="font-size: 0.78rem; color: #dc2626; cursor: pointer; font-weight: 600;">Opsi: Tolak / Minta Perubahan Judul</summary>
                            <form method="POST" action="${data.form_action}" style="margin-top: 0.5rem;">
                                <input type="hidden" name="_token" value="${csrfToken}">
                                <input type="hidden" name="action" value="tolak">
                                <input type="text" name="catatan" class="form-control" style="font-size: 0.82rem; margin-bottom: 0.4rem;" placeholder="Tulis alasan penolakan atau arahan revisi judul..." required>
                                <button type="submit" class="btn btn-danger btn-sm">Tolak Pengajuan Judul</button>
                            </form>
                        </details>
                    ` : ''}
                </div>
            `;
        }

        body.innerHTML = content;

        // Set selected values
        if (!data.is_locked) {
            if (data.pembimbing_1_id) document.getElementById('drawer_p1').value = data.pembimbing_1_id;
            if (data.pembimbing_2_id) document.getElementById('drawer_p2').value = data.pembimbing_2_id;
        }

        showDrawer();
    }

    function openDrawerSeminar(data) {
        const body = document.getElementById('drawer-body');

        document.getElementById('drawer-title').innerText = 'Penetapan Dosen Penguji Seminar';
        const badge = document.getElementById('drawer-badge');
        badge.className = `badge badge-${data.status_val}`;
        badge.innerText = data.status_label;
        document.getElementById('drawer-tgl').innerText = data.tgl_pengajuan;

        let berkasHtml = '';
        if (data.file_naskah) berkasHtml += `<a href="${data.file_naskah}" class="btn btn-secondary btn-sm" style="font-size: 0.75rem;">📄 Naskah Proposal</a> `;
        if (data.file_acc) berkasHtml += `<a href="${data.file_acc}" class="btn btn-secondary btn-sm" style="font-size: 0.75rem;">✍️ ACC Pembimbing</a> `;
        if (data.file_bayar) berkasHtml += `<a href="${data.file_bayar}" class="btn btn-secondary btn-sm" style="font-size: 0.75rem;">💳 Bukti Bayar</a> `;
        if (data.file_toefl) berkasHtml += `<a href="${data.file_toefl}" class="btn btn-secondary btn-sm" style="font-size: 0.75rem;">📜 TOEFL</a> `;

        let content = `
            <div style="background: #f8fafc; border: 1px solid var(--border); border-radius: 0.5rem; padding: 1rem;">
                <div style="font-size: 0.8rem; font-weight: 700; color: var(--text-muted);">${data.mhs_nim} &bull; ${data.mhs_name}</div>
                <div style="font-size: 1rem; font-weight: 700; color: #0f172a; margin-top: 0.25rem; line-height: 1.4;">"${data.judul}"</div>
                <div style="font-size: 0.82rem; color: #475569; margin-top: 0.35rem;">${data.pembimbing_info}</div>
                <div style="margin-top: 0.75rem; display: flex; gap: 0.4rem; flex-wrap: wrap;">
                    ${berkasHtml}
                </div>
            </div>
        `;

        if (data.is_locked) {
            content += `
                <div style="background: #f1f5f9; border: 1px solid #cbd5e1; border-radius: 0.5rem; padding: 1rem;">
                    <div style="font-weight: 700; font-size: 0.82rem; color: #334155; margin-bottom: 0.4rem;">
                        🔒 Dosen Penguji Seminar Telah Ditetapkan (Terkunci)
                    </div>
                    <div style="font-size: 0.85rem; color: #475569;">
                        Dosen Penguji: <strong>${data.penguji_name || '-'}</strong>
                    </div>
                    <div style="font-size: 0.75rem; color: #64748b; margin-top: 0.4rem;">
                        * Sesuai aturan sistem, hanya Admin Utama yang berwenang mengubah Dosen Penguji Seminar setelah ditetapkan.
                    </div>
                </div>
            `;
        } else {
            content += `
                <div style="background: #ffffff; border: 1px solid var(--border); border-radius: 0.5rem; padding: 1rem;">
                    ${data.penguji_seminar_id && data.is_admin ? `
                        <div style="background: #fee2e2; color: #991b1b; padding: 0.25rem 0.5rem; border-radius: 0.35rem; font-size: 0.75rem; font-weight: 700; margin-bottom: 0.75rem;">
                            👑 Wewenang Admin Utama: Anda dapat mengubah Dosen Penguji Seminar
                        </div>
                    ` : ''}

                    <form method="POST" action="${data.form_action}">
                        <input type="hidden" name="_token" value="${csrfToken}">
                        
                        <div class="form-group">
                            <label class="form-label" style="font-size: 0.78rem;">Dosen Penguji Seminar *</label>
                            <select name="penguji_seminar_id" class="form-control" style="font-size: 0.85rem;" required id="drawer_penguji_seminar">
                                <option value="">-- Pilih Dosen Penguji Seminar --</option>
                                ${dosenOptionsHtml}
                            </select>
                        </div>

                        <div style="display: flex; justify-content: flex-end; gap: 0.5rem; margin-top: 1rem;">
                            <button type="button" onclick="closeDrawer()" class="btn btn-secondary btn-sm">Batal</button>
                            <button type="submit" class="btn btn-primary btn-sm">
                                ✓ ${data.penguji_seminar_id ? 'Perbarui Penguji Seminar' : 'Tetapkan Penguji Seminar'}
                            </button>
                        </div>
                    </form>
                </div>
            `;
        }

        body.innerHTML = content;
        if (!data.is_locked && data.penguji_seminar_id) {
            document.getElementById('drawer_penguji_seminar').value = data.penguji_seminar_id;
        }

        showDrawer();
    }

    function openDrawerSidang(data) {
        const body = document.getElementById('drawer-body');

        document.getElementById('drawer-title').innerText = 'Penetapan 2 Dosen Penguji Sidang Meja Hijau';
        const badge = document.getElementById('drawer-badge');
        badge.className = `badge badge-${data.status_val}`;
        badge.innerText = data.status_label;
        document.getElementById('drawer-tgl').innerText = data.tgl_pengajuan;

        let berkasHtml = '';
        if (data.file_naskah) berkasHtml += `<a href="${data.file_naskah}" class="btn btn-secondary btn-sm" style="font-size: 0.75rem;">📘 Naskah Final</a> `;
        if (data.file_acc) berkasHtml += `<a href="${data.file_acc}" class="btn btn-secondary btn-sm" style="font-size: 0.75rem;">✍️ ACC Sidang</a> `;
        if (data.file_bebas) berkasHtml += `<a href="${data.file_bebas}" class="btn btn-secondary btn-sm" style="font-size: 0.75rem;">📄 Bebas Revisi</a> `;
        if (data.file_bayar) berkasHtml += `<a href="${data.file_bayar}" class="btn btn-secondary btn-sm" style="font-size: 0.75rem;">💳 Bukti Bayar</a> `;

        let content = `
            <div style="background: #f8fafc; border: 1px solid var(--border); border-radius: 0.5rem; padding: 1rem;">
                <div style="font-size: 0.8rem; font-weight: 700; color: var(--text-muted);">${data.mhs_nim} &bull; ${data.mhs_name}</div>
                <div style="font-size: 1rem; font-weight: 700; color: #0f172a; margin-top: 0.25rem; line-height: 1.4;">"${data.judul}"</div>
                <div style="font-size: 0.82rem; color: #475569; margin-top: 0.35rem;">${data.pembimbing_info}</div>
                <div style="margin-top: 0.75rem; display: flex; gap: 0.4rem; flex-wrap: wrap;">
                    ${berkasHtml}
                </div>
            </div>
        `;

        if (data.is_locked) {
            content += `
                <div style="background: #f1f5f9; border: 1px solid #cbd5e1; border-radius: 0.5rem; padding: 1rem;">
                    <div style="font-weight: 700; font-size: 0.82rem; color: #334155; margin-bottom: 0.4rem;">
                        🔒 Dewan Penguji Sidang Telah Ditetapkan (Terkunci)
                    </div>
                    <div style="font-size: 0.85rem; color: #475569;">
                        Penguji 1: <strong>${data.penguji1_name || '-'}</strong><br>
                        Penguji 2: <strong>${data.penguji2_name || '-'}</strong>
                    </div>
                    <div style="font-size: 0.75rem; color: #64748b; margin-top: 0.4rem;">
                        * Sesuai aturan sistem, hanya Admin Utama yang berwenang mengubah Dewan Penguji Sidang setelah ditetapkan.
                    </div>
                </div>
            `;
        } else {
            content += `
                <div style="background: #ffffff; border: 1px solid var(--border); border-radius: 0.5rem; padding: 1rem;">
                    ${(data.penguji_1_id || data.penguji_2_id) && data.is_admin ? `
                        <div style="background: #fee2e2; color: #991b1b; padding: 0.25rem 0.5rem; border-radius: 0.35rem; font-size: 0.75rem; font-weight: 700; margin-bottom: 0.75rem;">
                            👑 Wewenang Admin Utama: Anda dapat mengubah 2 Dosen Penguji Sidang
                        </div>
                    ` : ''}

                    <form method="POST" action="${data.form_action}">
                        <input type="hidden" name="_token" value="${csrfToken}">
                        
                        <div class="form-group">
                            <label class="form-label" style="font-size: 0.78rem;">Dosen Penguji Sidang 1 *</label>
                            <select name="penguji_1_id" class="form-control" style="font-size: 0.85rem;" required id="drawer_penguji_1">
                                <option value="">-- Pilih Penguji Sidang 1 --</option>
                                ${dosenOptionsHtml}
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label" style="font-size: 0.78rem;">Dosen Penguji Sidang 2 *</label>
                            <select name="penguji_2_id" class="form-control" style="font-size: 0.85rem;" required id="drawer_penguji_2">
                                <option value="">-- Pilih Penguji Sidang 2 --</option>
                                ${dosenOptionsHtml}
                            </select>
                        </div>

                        <div style="display: flex; justify-content: flex-end; gap: 0.5rem; margin-top: 1rem;">
                            <button type="button" onclick="closeDrawer()" class="btn btn-secondary btn-sm">Batal</button>
                            <button type="submit" class="btn btn-primary btn-sm">
                                ✓ ${(data.penguji_1_id || data.penguji_2_id) ? 'Perbarui 2 Penguji Sidang' : 'Tetapkan 2 Penguji Sidang'}
                            </button>
                        </div>
                    </form>
                </div>
            `;
        }

        body.innerHTML = content;
        if (!data.is_locked) {
            if (data.penguji_1_id) document.getElementById('drawer_penguji_1').value = data.penguji_1_id;
            if (data.penguji_2_id) document.getElementById('drawer_penguji_2').value = data.penguji_2_id;
        }

        showDrawer();
    }

    function showDrawer() {
        const backdrop = document.getElementById('drawer-backdrop');
        const panel = document.getElementById('drawer-panel');
        backdrop.style.display = 'block';
        setTimeout(() => {
            panel.style.transform = 'translateX(0)';
        }, 10);
    }

    function closeDrawer() {
        const backdrop = document.getElementById('drawer-backdrop');
        const panel = document.getElementById('drawer-panel');
        panel.style.transform = 'translateX(100%)';
        setTimeout(() => {
            backdrop.style.display = 'none';
        }, 300);
    }
</script>

@endsection
