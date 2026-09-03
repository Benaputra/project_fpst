@extends('layouts.app')

@section('title', 'Penetapan Pembimbing & Penguji')
@section('page_title', $user->isAdminUtama() ? 'Penetapan Pembimbing & Penguji (Admin Utama - Akses Penuh)' : ('Penetapan Pembimbing & Penguji (Kaprodi ' . ($user->programStudi ? $user->programStudi->nama : '') . ')'))

@section('content')

<div class="card">
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
        <button type="button" class="tab-btn active" onclick="switchTab(event, 'tab-judul')">
            1. Review Judul & Pembimbing ({{ $daftarJudul->total() }})
        </button>
        <button type="button" class="tab-btn" onclick="switchTab(event, 'tab-seminar')">
            2. Penguji Seminar ({{ $daftarSeminar->total() }})
        </button>
        <button type="button" class="tab-btn" onclick="switchTab(event, 'tab-sidang')">
            3. Penguji Sidang (2 Org) ({{ $daftarSidang->total() }})
        </button>
    </div>

    <!-- TAB 1: PENGANJUAN JUDUL & PEMBIMBING -->
    <div id="tab-judul" class="tab-content active">
        <div style="font-size: 0.9rem; color: var(--text-muted); margin-bottom: 1.25rem;">
            Tinjau judul dan berkas persyaratan awal mahasiswa, lalu tetapkan Dosen Pembimbing 1 dan Pembimbing 2.
        </div>

        <div style="display: flex; flex-direction: column; gap: 1.5rem;">
            @forelse ($daftarJudul as $skripsi)
                <div style="border: 1px solid var(--border); border-radius: 0.65rem; padding: 1.25rem; background: {{ $skripsi->status->value === 'diajukan' ? '#fffbeb' : '#ffffff' }};">
                    <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; margin-bottom: 0.75rem;">
                        <div>
                            <div style="font-size: 0.8rem; font-weight: 700; color: var(--text-muted);">
                                {{ $skripsi->mahasiswa->nomor_induk }} &bull; {{ $skripsi->mahasiswa->name }}
                            </div>
                            <h3 style="font-size: 1.05rem; font-weight: 700; color: #0f172a; margin-top: 0.2rem;">
                                "{{ $skripsi->judul }}"
                            </h3>
                        </div>
                        <span class="badge badge-{{ $skripsi->status->value }}">{{ $skripsi->status->label() }}</span>
                    </div>

                    @if ($skripsi->abstrak)
                        <p style="font-size: 0.85rem; color: #475569; line-height: 1.5; margin-bottom: 0.75rem;">
                            {{ Str::limit($skripsi->abstrak, 250) }}
                        </p>
                    @endif

                    <!-- Berkas Lampiran -->
                    <div style="display: flex; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 1rem;">
                        @if ($skripsi->file_proposal)
                            <a href="{{ route('dokumen.download', base64_encode($skripsi->file_proposal)) }}" class="btn btn-secondary btn-sm">
                                📄 Draf Proposal
                            </a>
                        @endif
                        @if ($skripsi->file_transkrip)
                            <a href="{{ route('dokumen.download', base64_encode($skripsi->file_transkrip)) }}" class="btn btn-secondary btn-sm">
                                📊 Transkrip Nilai
                            </a>
                        @endif
                        @if ($skripsi->file_bukti_bayar)
                            <a href="{{ route('dokumen.download', base64_encode($skripsi->file_bukti_bayar)) }}" class="btn btn-secondary btn-sm">
                                💳 Bukti Bayar Skripsi
                            </a>
                        @endif
                    </div>

                    @if ($skripsi->pembimbing_1_id !== null && !$user->isAdminUtama())
                        <!-- Tampilan Terkunci untuk Non-Admin Utama -->
                        <div style="background: #f1f5f9; border: 1px solid #cbd5e1; border-radius: 0.5rem; padding: 1rem;">
                            <div style="font-weight: 700; font-size: 0.82rem; color: #334155; margin-bottom: 0.4rem; display: flex; align-items: center; gap: 0.35rem;">
                                <span>🔒</span> Dosen Pembimbing Telah Ditetapkan (Terkunci)
                            </div>
                            <div style="font-size: 0.85rem; color: #475569;">
                                Pembimbing 1 (Utama): <strong>{{ $skripsi->pembimbing1 ? $skripsi->pembimbing1->name : '-' }}</strong> &bull;
                                Pembimbing 2: <strong>{{ $skripsi->pembimbing2 ? $skripsi->pembimbing2->name : 'Tanpa Pembimbing 2' }}</strong>
                            </div>
                            <div style="font-size: 0.75rem; color: #64748b; margin-top: 0.4rem;">
                                * Sesuai aturan sistem, hanya Admin Utama yang berwenang mengubah Dosen Pembimbing setelah ditentukan.
                            </div>
                        </div>
                    @else
                        <!-- Form Penetapan / Pengubahan Pembimbing (Bisa oleh Kaprodi jika belum ditetapkan, atau Admin Utama kapan saja) -->
                        <div style="background: #f8fafc; border: 1px solid var(--border); border-radius: 0.5rem; padding: 1rem;">
                            @if ($skripsi->pembimbing_1_id !== null && $user->isAdminUtama())
                                <div style="display: inline-block; background: #fee2e2; color: #991b1b; padding: 0.2rem 0.5rem; border-radius: 0.35rem; font-size: 0.75rem; font-weight: 700; margin-bottom: 0.6rem;">
                                    👑 Wewenang Admin Utama: Anda dapat mengubah Dosen Pembimbing yang sudah ditetapkan
                                </div>
                            @endif

                            <form method="POST" action="{{ route('kaprodi.skripsi.review', $skripsi->id) }}">
                                @csrf
                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 0.75rem;">
                                    <div class="form-group" style="margin-bottom: 0;">
                                        <label class="form-label" style="font-size: 0.78rem;">Dosen Pembimbing 1 (Utama) *</label>
                                        <select name="pembimbing_1_id" class="form-control" style="font-size: 0.85rem;" required>
                                            <option value="">-- Pilih Pembimbing 1 --</option>
                                            @foreach ($daftarDosen as $dosen)
                                                <option value="{{ $dosen->id }}" {{ $skripsi->pembimbing_1_id === $dosen->id ? 'selected' : '' }}>
                                                    {{ $dosen->name }} ({{ $dosen->nomor_induk }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="form-group" style="margin-bottom: 0;">
                                        <label class="form-label" style="font-size: 0.78rem;">Dosen Pembimbing 2 (Opsional)</label>
                                        <select name="pembimbing_2_id" class="form-control" style="font-size: 0.85rem;">
                                            <option value="">-- Tanpa Pembimbing 2 --</option>
                                            @foreach ($daftarDosen as $dosen)
                                                <option value="{{ $dosen->id }}" {{ $skripsi->pembimbing_2_id === $dosen->id ? 'selected' : '' }}>
                                                    {{ $dosen->name }} ({{ $dosen->nomor_induk }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div style="display: flex; justify-content: flex-end; gap: 0.5rem; margin-top: 0.75rem;">
                                    <button type="submit" name="action" value="terima" class="btn btn-primary btn-sm">
                                        ✓ {{ $skripsi->pembimbing_1_id ? 'Perbarui Penetapan Pembimbing' : 'Simpan & Tetapkan Pembimbing' }}
                                    </button>
                                </div>
                            </form>

                            <!-- Form Tolak -->
                            @if ($skripsi->status->value === 'diajukan')
                                <details style="margin-top: 0.75rem; border-top: 1px dashed var(--border); padding-top: 0.5rem;">
                                    <summary style="font-size: 0.78rem; color: #dc2626; cursor: pointer; font-weight: 600;">Opsi: Tolak / Minta Perubahan Judul</summary>
                                    <form method="POST" action="{{ route('kaprodi.skripsi.review', $skripsi->id) }}" style="margin-top: 0.5rem;">
                                        @csrf
                                        <input type="hidden" name="action" value="tolak">
                                        <input type="text" name="catatan" class="form-control" style="font-size: 0.82rem; margin-bottom: 0.4rem;" placeholder="Tulis alasan penolakan atau arahan perubahan judul..." required>
                                        <button type="submit" class="btn btn-danger btn-sm">Tolak Pengajuan Judul</button>
                                    </form>
                                </details>
                            @endif
                        </div>
                    @endif
                </div>
            @empty
                <div style="text-align: center; color: var(--text-muted); padding: 3rem;">
                    Belum ada data pengajuan judul skripsi.
                </div>
            @endforelse
        </div>

        <div style="margin-top: 1rem;">
            {{ $daftarJudul->links() }}
        </div>
    </div>

    <!-- TAB 2: PENETAPAN PENGUJI SEMINAR -->
    <div id="tab-seminar" class="tab-content">
        <div style="font-size: 0.9rem; color: var(--text-muted); margin-bottom: 1.25rem;">
            Daftar pengajuan seminar proposal/hasil. Tetapkan Dosen Penguji Seminar untuk setiap mahasiswa.
        </div>

        <div style="display: flex; flex-direction: column; gap: 1.5rem;">
            @forelse ($daftarSeminar as $seminar)
                @php $sk = $seminar->pengajuanSkripsi; @endphp
                <div style="border: 1px solid var(--border); border-radius: 0.65rem; padding: 1.25rem; background: #fff;">
                    <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; margin-bottom: 0.75rem;">
                        <div>
                            <div style="font-size: 0.8rem; font-weight: 700; color: var(--text-muted);">
                                {{ $sk->mahasiswa->nomor_induk }} &bull; {{ $sk->mahasiswa->name }}
                            </div>
                            <h3 style="font-size: 1.05rem; font-weight: 700; color: #0f172a; margin-top: 0.2rem;">
                                "{{ $sk->judul }}"
                            </h3>
                            <div style="font-size: 0.8rem; color: #475569; margin-top: 0.25rem;">
                                Pembimbing: 1. {{ $sk->pembimbing1 ? $sk->pembimbing1->name : '-' }} | 2. {{ $sk->pembimbing2 ? $sk->pembimbing2->name : '-' }}
                            </div>
                        </div>
                        <span class="badge badge-{{ $seminar->status->value }}">{{ $seminar->status->label() }}</span>
                    </div>

                    <!-- Berkas Persyaratan Seminar -->
                    <div style="display: flex; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 1rem;">
                        @if ($seminar->file_naskah_seminar)
                            <a href="{{ route('dokumen.download', base64_encode($seminar->file_naskah_seminar)) }}" class="btn btn-secondary btn-sm">
                                📄 Naskah Proposal
                            </a>
                        @endif
                        @if ($seminar->file_acc_pembimbing)
                            <a href="{{ route('dokumen.download', base64_encode($seminar->file_acc_pembimbing)) }}" class="btn btn-secondary btn-sm">
                                ✍️ ACC Pembimbing
                            </a>
                        @endif
                        @if ($seminar->file_bukti_bayar_seminar)
                            <a href="{{ route('dokumen.download', base64_encode($seminar->file_bukti_bayar_seminar)) }}" class="btn btn-secondary btn-sm">
                                💳 Bukti Bayar
                            </a>
                        @endif
                        @if ($seminar->file_toefl)
                            <a href="{{ route('dokumen.download', base64_encode($seminar->file_toefl)) }}" class="btn btn-secondary btn-sm">
                                📜 TOEFL
                            </a>
                        @endif
                    </div>

                    @if ($seminar->penguji_seminar_id !== null && !$user->isAdminUtama())
                        <!-- Tampilan Terkunci Penguji Seminar untuk Non-Admin Utama -->
                        <div style="background: #f1f5f9; border: 1px solid #cbd5e1; border-radius: 0.5rem; padding: 1rem;">
                            <div style="font-weight: 700; font-size: 0.82rem; color: #334155; margin-bottom: 0.4rem; display: flex; align-items: center; gap: 0.35rem;">
                                <span>🔒</span> Dosen Penguji Seminar Telah Ditetapkan (Terkunci)
                            </div>
                            <div style="font-size: 0.85rem; color: #475569;">
                                Dosen Penguji: <strong>{{ $seminar->penguji ? $seminar->penguji->name : '-' }}</strong> ({{ $seminar->penguji ? $seminar->penguji->nomor_induk : '-' }})
                            </div>
                            <div style="font-size: 0.75rem; color: #64748b; margin-top: 0.4rem;">
                                * Sesuai aturan sistem, hanya Admin Utama yang berwenang mengubah Dosen Penguji Seminar setelah ditetapkan.
                            </div>
                        </div>
                    @else
                        <!-- Form Plot Penguji Seminar -->
                        <div style="background: #f8fafc; border: 1px solid var(--border); border-radius: 0.5rem; padding: 1rem;">
                            @if ($seminar->penguji_seminar_id !== null && $user->isAdminUtama())
                                <div style="display: inline-block; background: #fee2e2; color: #991b1b; padding: 0.2rem 0.5rem; border-radius: 0.35rem; font-size: 0.75rem; font-weight: 700; margin-bottom: 0.6rem;">
                                    👑 Wewenang Admin Utama: Anda dapat mengubah Dosen Penguji Seminar
                                </div>
                            @endif

                            <form method="POST" action="{{ route('kaprodi.seminar.penguji', $seminar->id) }}">
                                @csrf
                                <div style="display: grid; grid-template-columns: minmax(220px, 1fr) auto; gap: 0.75rem; align-items: flex-end;">
                                    <div class="form-group" style="margin-bottom: 0;">
                                        <label class="form-label" style="font-size: 0.78rem;">Dosen Penguji Seminar *</label>
                                        <select name="penguji_seminar_id" class="form-control" style="font-size: 0.85rem;" required>
                                            <option value="">-- Pilih Dosen Penguji Seminar --</option>
                                            @foreach ($daftarDosen as $dosen)
                                                <option value="{{ $dosen->id }}" {{ $seminar->penguji_seminar_id === $dosen->id ? 'selected' : '' }}>
                                                    {{ $dosen->name }} ({{ $dosen->nomor_induk }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <button type="submit" class="btn btn-primary btn-sm" style="height: 38px;">
                                        ✓ {{ $seminar->penguji_seminar_id ? 'Perbarui Penguji Seminar' : 'Tetapkan Penguji Seminar' }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    @endif
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

    <!-- TAB 3: PENETAPAN 2 PENGUJI SIDANG -->
    <div id="tab-sidang" class="tab-content">
        <div style="font-size: 0.9rem; color: var(--text-muted); margin-bottom: 1.25rem;">
            Daftar pengajuan sidang skripsi (meja hijau). Tetapkan 2 Orang Dosen Penguji Sidang untuk setiap mahasiswa.
        </div>

        <div style="display: flex; flex-direction: column; gap: 1.5rem;">
            @forelse ($daftarSidang as $sidang)
                @php $sk = $sidang->pengajuanSkripsi; @endphp
                <div style="border: 1px solid var(--border); border-radius: 0.65rem; padding: 1.25rem; background: #fff;">
                    <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; margin-bottom: 0.75rem;">
                        <div>
                            <div style="font-size: 0.8rem; font-weight: 700; color: var(--text-muted);">
                                {{ $sk->mahasiswa->nomor_induk }} &bull; {{ $sk->mahasiswa->name }}
                            </div>
                            <h3 style="font-size: 1.05rem; font-weight: 700; color: #0f172a; margin-top: 0.2rem;">
                                "{{ $sk->judul }}"
                            </h3>
                            <div style="font-size: 0.8rem; color: #475569; margin-top: 0.25rem;">
                                Pembimbing: 1. {{ $sk->pembimbing1 ? $sk->pembimbing1->name : '-' }} | 2. {{ $sk->pembimbing2 ? $sk->pembimbing2->name : '-' }}
                            </div>
                        </div>
                        <span class="badge badge-{{ $sidang->status->value }}">{{ $sidang->status->label() }}</span>
                    </div>

                    <!-- Berkas Persyaratan Sidang -->
                    <div style="display: flex; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 1rem;">
                        @if ($sidang->file_naskah_sidang)
                            <a href="{{ route('dokumen.download', base64_encode($sidang->file_naskah_sidang)) }}" class="btn btn-secondary btn-sm">
                                📘 Naskah Final
                            </a>
                        @endif
                        @if ($sidang->file_acc_sidang)
                            <a href="{{ route('dokumen.download', base64_encode($sidang->file_acc_sidang)) }}" class="btn btn-secondary btn-sm">
                                ✍️ ACC Sidang
                            </a>
                        @endif
                        @if ($sidang->file_bebas_revisi_seminar)
                            <a href="{{ route('dokumen.download', base64_encode($sidang->file_bebas_revisi_seminar)) }}" class="btn btn-secondary btn-sm">
                                📄 Bebas Revisi Seminar
                            </a>
                        @endif
                        @if ($sidang->file_bukti_bayar_sidang)
                            <a href="{{ route('dokumen.download', base64_encode($sidang->file_bukti_bayar_sidang)) }}" class="btn btn-secondary btn-sm">
                                💳 Bukti Bayar Sidang
                            </a>
                        @endif
                    </div>

                    @if (($sidang->penguji_1_id !== null || $sidang->penguji_2_id !== null) && !$user->isAdminUtama())
                        <!-- Tampilan Terkunci Dewan Penguji Sidang untuk Non-Admin Utama -->
                        <div style="background: #f1f5f9; border: 1px solid #cbd5e1; border-radius: 0.5rem; padding: 1rem;">
                            <div style="font-weight: 700; font-size: 0.82rem; color: #334155; margin-bottom: 0.4rem; display: flex; align-items: center; gap: 0.35rem;">
                                <span>🔒</span> 2 Orang Dewan Penguji Sidang Telah Ditetapkan (Terkunci)
                            </div>
                            <div style="font-size: 0.85rem; color: #475569;">
                                Penguji 1: <strong>{{ $sidang->penguji1 ? $sidang->penguji1->name : '-' }}</strong> ({{ $sidang->penguji1 ? $sidang->penguji1->nomor_induk : '-' }}) &bull;
                                Penguji 2: <strong>{{ $sidang->penguji2 ? $sidang->penguji2->name : '-' }}</strong> ({{ $sidang->penguji2 ? $sidang->penguji2->nomor_induk : '-' }})
                            </div>
                            <div style="font-size: 0.75rem; color: #64748b; margin-top: 0.4rem;">
                                * Sesuai aturan sistem, hanya Admin Utama yang berwenang mengubah Dewan Penguji Sidang setelah ditetapkan.
                            </div>
                        </div>
                    @else
                        <!-- Form Plot 2 Penguji Sidang -->
                        <div style="background: #f8fafc; border: 1px solid var(--border); border-radius: 0.5rem; padding: 1rem;">
                            @if (($sidang->penguji_1_id !== null || $sidang->penguji_2_id !== null) && $user->isAdminUtama())
                                <div style="display: inline-block; background: #fee2e2; color: #991b1b; padding: 0.2rem 0.5rem; border-radius: 0.35rem; font-size: 0.75rem; font-weight: 700; margin-bottom: 0.6rem;">
                                    👑 Wewenang Admin Utama: Anda dapat mengubah 2 Dosen Penguji Sidang
                                </div>
                            @endif

                            <form method="POST" action="{{ route('kaprodi.sidang.penguji', $sidang->id) }}">
                                @csrf
                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 0.75rem;">
                                    <div class="form-group" style="margin-bottom: 0;">
                                        <label class="form-label" style="font-size: 0.78rem;">Dosen Penguji Sidang 1 *</label>
                                        <select name="penguji_1_id" class="form-control" style="font-size: 0.85rem;" required>
                                            <option value="">-- Pilih Penguji Sidang 1 --</option>
                                            @foreach ($daftarDosen as $dosen)
                                                <option value="{{ $dosen->id }}" {{ $sidang->penguji_1_id === $dosen->id ? 'selected' : '' }}>
                                                    {{ $dosen->name }} ({{ $dosen->nomor_induk }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="form-group" style="margin-bottom: 0;">
                                        <label class="form-label" style="font-size: 0.78rem;">Dosen Penguji Sidang 2 *</label>
                                        <select name="penguji_2_id" class="form-control" style="font-size: 0.85rem;" required>
                                            <option value="">-- Pilih Penguji Sidang 2 --</option>
                                            @foreach ($daftarDosen as $dosen)
                                                <option value="{{ $dosen->id }}" {{ $sidang->penguji_2_id === $dosen->id ? 'selected' : '' }}>
                                                    {{ $dosen->name }} ({{ $dosen->nomor_induk }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div style="display: flex; justify-content: flex-end; margin-top: 0.75rem;">
                                    <button type="submit" class="btn btn-primary btn-sm">
                                        ✓ {{ ($sidang->penguji_1_id || $sidang->penguji_2_id) ? 'Perbarui 2 Dosen Penguji Sidang' : 'Tetapkan 2 Dosen Penguji Sidang' }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    @endif
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
