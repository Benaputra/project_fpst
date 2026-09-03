@extends('layouts.app')

@section('title', 'Daftar Bimbingan & Jadwal Uji')
@section('page_title', 'Portal Dosen: Bimbingan & Jadwal Penguji')

@section('content')

<div class="card">
    @if ($permintaanPenugasan->count() > 0)
        <!-- Banner Alert Permintaan Baru -->
        <div style="background: #fffbeb; border: 1px solid #fef3c7; border-left: 4px solid #f59e0b; border-radius: 0.5rem; padding: 1rem 1.25rem; margin-bottom: 1.5rem; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.75rem;">
            <div style="display: flex; align-items: center; gap: 0.75rem;">
                <span style="font-size: 1.5rem;">🔔</span>
                <div>
                    <div style="font-weight: 700; color: #92400e; font-size: 0.95rem;">
                        Ada {{ $permintaanPenugasan->count() }} Permintaan Penugasan Memerlukan Konfirmasi Anda
                    </div>
                    <div style="font-size: 0.82rem; color: #b45309; margin-top: 0.15rem;">
                        Anda dapat menyetujui atau menolak (dengan alasan) penunjukan sebagai pembimbing maupun penguji sebelum proses administrasi berlanjut.
                    </div>
                </div>
            </div>
            <button type="button" class="btn btn-warning btn-sm" onclick="switchTab(event, 'tab-dosen-konfirmasi')" style="font-weight: 600;">
                Tinjau Sekarang ({{ $permintaanPenugasan->count() }})
            </button>
        </div>
    @endif

    <!-- Tab Navigasi -->
    <div class="tab-nav">
        <button type="button" class="tab-btn {{ $permintaanPenugasan->count() > 0 ? 'active' : '' }}" onclick="switchTab(event, 'tab-dosen-konfirmasi')">
            🔔 Permintaan Konfirmasi
            @if ($permintaanPenugasan->count() > 0)
                <span class="badge badge-diajukan" style="margin-left: 0.35rem; font-size: 0.72rem; font-weight: 700;">{{ $permintaanPenugasan->count() }} Baru</span>
            @endif
        </button>
        <button type="button" class="tab-btn {{ $permintaanPenugasan->count() == 0 ? 'active' : '' }}" onclick="switchTab(event, 'tab-dosen-bimbingan')">
            1. Mahasiswa Bimbingan ({{ $daftarBimbingan->total() }})
        </button>
        <button type="button" class="tab-btn" onclick="switchTab(event, 'tab-dosen-seminar')">
            2. Tugas Penguji Seminar ({{ $jadwalUjiSeminar->total() }})
        </button>
        <button type="button" class="tab-btn" onclick="switchTab(event, 'tab-dosen-sidang')">
            3. Tugas Penguji Sidang ({{ $jadwalUjiSidang->total() }})
        </button>
    </div>

    <!-- TAB 0: PERMINTAAN KONFIRMASI -->
    <div id="tab-dosen-konfirmasi" class="tab-content {{ $permintaanPenugasan->count() > 0 ? 'active' : '' }}">
        <div style="font-size: 0.88rem; color: var(--text-muted); margin-bottom: 1.25rem;">
            Daftar penugasan dari Ketua Program Studi yang memerlukan konfirmasi kesediaan Anda. Anda berhak menerima atau menolak penugasan ini disertai alasan tertulis.
        </div>

        <div style="display: flex; flex-direction: column; gap: 1.25rem;">
            @forelse ($permintaanPenugasan as $item)
                @php
                    $assign = $item->assignable;
                    $mhs = null;
                    $prodi = null;
                    $judul = '-';
                    $abstrak = '-';
                    $fileProposal = null;

                    if ($assign instanceof \App\Models\PengajuanSkripsi) {
                        $mhs = $assign->mahasiswa;
                        $prodi = $assign->programStudi;
                        $judul = $assign->judul;
                        $abstrak = $assign->abstrak;
                        $fileProposal = $assign->file_proposal;
                    } elseif ($assign instanceof \App\Models\SeminarSkripsi) {
                        $mhs = $assign->pengajuanSkripsi->mahasiswa;
                        $prodi = $assign->pengajuanSkripsi->programStudi;
                        $judul = $assign->pengajuanSkripsi->judul;
                        $abstrak = $assign->pengajuanSkripsi->abstrak;
                        $fileProposal = $assign->file_naskah_seminar;
                    } elseif ($assign instanceof \App\Models\SidangSkripsi) {
                        $mhs = $assign->pengajuanSkripsi->mahasiswa;
                        $prodi = $assign->pengajuanSkripsi->programStudi;
                        $judul = $assign->pengajuanSkripsi->judul;
                        $abstrak = $assign->pengajuanSkripsi->abstrak;
                        $fileProposal = $assign->file_naskah_sidang;
                    }
                @endphp

                <div style="border: 1px solid #fde68a; border-radius: 0.65rem; padding: 1.25rem; background: #fffdf5; box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
                    <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; margin-bottom: 0.5rem; flex-wrap: wrap;">
                        <div>
                            <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.25rem;">
                                <span class="badge badge-primary" style="font-size: 0.78rem; font-weight: 700;">
                                    📌 {{ $item->labelPeran() }}
                                </span>
                                <span style="font-size: 0.75rem; color: var(--text-muted);">
                                    Ditugaskan {{ $item->created_at->diffForHumans() }} oleh {{ $item->ditugaskanOleh ? $item->ditugaskanOleh->name : 'Kaprodi' }}
                                </span>
                            </div>
                            <div style="font-size: 0.82rem; font-weight: 700; color: #475569;">
                                {{ $mhs ? $mhs->nomor_induk : '-' }} &bull; {{ $mhs ? $mhs->name : '-' }} &bull; {{ $prodi ? $prodi->nama : '-' }}
                            </div>
                            <h3 style="font-size: 1.1rem; font-weight: 700; color: #0f172a; margin-top: 0.35rem; line-height: 1.4;">
                                "{{ $judul }}"
                            </h3>
                        </div>
                        <span class="badge badge-diajukan">⏳ Menunggu Respon</span>
                    </div>

                    @if ($abstrak && $abstrak !== '-')
                        <div style="background: #ffffff; border: 1px solid var(--border); border-radius: 0.45rem; padding: 0.75rem 1rem; margin-top: 0.75rem; font-size: 0.82rem; color: #334155; line-height: 1.5;">
                            <strong>Abstrak:</strong> {{ Str::limit($abstrak, 300) }}
                        </div>
                    @endif

                    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.75rem; margin-top: 1rem; padding-top: 0.85rem; border-top: 1px solid #fef3c7;">
                        <div>
                            @if ($fileProposal)
                                <a href="{{ route('dokumen.download', base64_encode($fileProposal)) }}" class="btn btn-secondary btn-sm" style="font-size: 0.78rem;">
                                    📄 Unduh Berkas Naskah / Proposal
                                </a>
                            @endif
                        </div>

                        <!-- Action Form Buttons -->
                        <div style="display: flex; gap: 0.5rem; align-items: center;">
                            <!-- Form Terima -->
                            <form method="POST" action="{{ route('dosen.penugasan.respon', $item->id) }}" onsubmit="return confirm('Apakah Anda bersedia menerima penugasan sebagai {{ $item->labelPeran() }} untuk mahasiswa {{ $mhs ? $mhs->name : '' }}?');">
                                @csrf
                                <input type="hidden" name="aksi" value="terima">
                                <button type="submit" class="btn btn-success btn-sm" style="font-weight: 600; padding: 0.45rem 1rem;">
                                    ✓ Bersedia / Setuju
                                </button>
                            </form>

                            <!-- Button Tolak (Open Modal) -->
                            <button type="button" class="btn btn-danger btn-sm" style="font-weight: 600; padding: 0.45rem 1rem;" onclick="openTolakModal('{{ $item->id }}', '{{ $item->labelPeran() }}', '{{ addslashes($mhs ? $mhs->name : '') }}', '{{ addslashes($judul) }}')">
                                ✕ Tidak Bersedia / Tolak
                            </button>
                        </div>
                    </div>
                </div>
            @empty
                <div style="text-align: center; color: var(--text-muted); padding: 3rem; background: #f8fafc; border-radius: 0.5rem; border: 1px dashed var(--border);">
                    <div style="font-size: 2rem; margin-bottom: 0.5rem;">🎉</div>
                    <div style="font-weight: 600; color: #1e293b;">Tidak ada permintaan penugasan baru.</div>
                    <div style="font-size: 0.82rem; margin-top: 0.25rem;">Semua penugasan telah Anda respon atau langsung diproses.</div>
                </div>
            @endforelse
        </div>
    </div>

    <!-- TAB 1: MAHASISWA BIMBINGAN -->
    <div id="tab-dosen-bimbingan" class="tab-content {{ $permintaanPenugasan->count() == 0 ? 'active' : '' }}">
        <div style="font-size: 0.88rem; color: var(--text-muted); margin-bottom: 1.25rem;">
            Daftar seluruh mahasiswa yang Anda bimbing baik sebagai Pembimbing Utama (1) maupun Pembimbing Pendamping (2).
        </div>

        <div style="display: flex; flex-direction: column; gap: 1.25rem;">
            @forelse ($daftarBimbingan as $skripsi)
                <div style="border: 1px solid var(--border); border-radius: 0.65rem; padding: 1.25rem; background: #fff;">
                    <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; margin-bottom: 0.5rem;">
                        <div>
                            <div style="font-size: 0.78rem; font-weight: 700; color: var(--text-muted);">
                                {{ $skripsi->mahasiswa->nomor_induk }} &bull; {{ $skripsi->mahasiswa->name }} &bull; {{ $skripsi->programStudi ? $skripsi->programStudi->nama : '-' }}
                            </div>
                            <h3 style="font-size: 1.05rem; font-weight: 700; color: #0f172a; margin-top: 0.2rem;">
                                "{{ $skripsi->judul }}"
                            </h3>
                            <div style="font-size: 0.82rem; color: #475569; margin-top: 0.25rem;">
                                Peran Anda: 
                                @if ($skripsi->pembimbing_1_id === $user->id)
                                    <strong style="color: #1d4ed8;">Pembimbing Utama (1)</strong>
                                @else
                                    <strong style="color: #6d28d9;">Pembimbing Pendamping (2)</strong>
                                @endif
                                &bull; No. SK Bimbingan: <strong>{{ $skripsi->nomor_sk_bimbingan ?? 'Belum terbit' }}</strong>
                            </div>
                        </div>
                        <span class="badge badge-{{ $skripsi->status->value }}">{{ $skripsi->status->label() }}</span>
                    </div>

                    <!-- Progress Indicator -->
                    <div style="display: flex; flex-wrap: wrap; gap: 0.5rem; margin-top: 0.75rem;">
                        @if ($skripsi->file_proposal)
                            <a href="{{ route('dokumen.download', base64_encode($skripsi->file_proposal)) }}" class="btn btn-secondary btn-sm">
                                📄 Draf Proposal Mahasiswa
                            </a>
                        @endif

                        @if ($skripsi->seminar && $skripsi->seminar->file_naskah_seminar)
                            <a href="{{ route('dokumen.download', base64_encode($skripsi->seminar->file_naskah_seminar)) }}" class="btn btn-secondary btn-sm">
                                📑 Naskah Seminar Mahasiswa
                            </a>
                        @endif

                        @if ($skripsi->sidang && $skripsi->sidang->file_naskah_sidang)
                            <a href="{{ route('dokumen.download', base64_encode($skripsi->sidang->file_naskah_sidang)) }}" class="btn btn-secondary btn-sm">
                                📘 Naskah Sidang Final
                            </a>
                        @endif

                        @if ($skripsi->file_sk_bimbingan)
                            <a href="{{ route('dokumen.download', base64_encode($skripsi->file_sk_bimbingan)) }}" class="btn btn-secondary btn-sm">
                                📜 SK Bimbingan (PDF)
                            </a>
                        @endif
                    </div>

                    <!-- Undangan & Jadwal Seminar untuk Pembimbing -->
                    @if ($skripsi->seminar && ($skripsi->seminar->tgl_seminar || $skripsi->seminar->file_undangan_seminar))
                        <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 0.5rem; padding: 0.85rem 1rem; margin-top: 0.85rem; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.75rem;">
                            <div>
                                <div style="font-weight: 700; color: #166534; font-size: 0.85rem; display: flex; align-items: center; gap: 0.35rem;">
                                    <span>✉️</span> Undangan Seminar Proposal/Hasil Mahasiswa Bimbingan
                                </div>
                                <div style="font-size: 0.82rem; color: #334155; margin-top: 0.2rem;">
                                    <strong>Jadwal:</strong> {{ $skripsi->seminar->tgl_seminar ? $skripsi->seminar->tgl_seminar->translatedFormat('l, d F Y') . ' (' . $skripsi->seminar->jam_seminar . ')' : 'Jadwal belum ditentukan' }}
                                    &bull; Ruangan: <strong>{{ $skripsi->seminar->ruangan ?? '-' }}</strong>
                                    @if ($skripsi->seminar->nomor_undangan_seminar)
                                        &bull; No. Undangan: <strong>{{ $skripsi->seminar->nomor_undangan_seminar }}</strong>
                                    @endif
                                    @if ($skripsi->seminar->penguji)
                                        &bull; Penguji: <strong>{{ $skripsi->seminar->penguji->name }}</strong>
                                    @endif
                                </div>
                            </div>
                            @if ($skripsi->seminar->file_undangan_seminar)
                                <div style="display: flex; gap: 0.4rem; flex-wrap: wrap;">
                                    <a href="{{ route('dokumen.view', base64_encode($skripsi->seminar->file_undangan_seminar)) }}" target="_blank" class="btn btn-secondary btn-sm" style="font-weight: 600;">
                                        👁️ Lihat Surat Undangan
                                    </a>
                                    <a href="{{ route('dokumen.download', base64_encode($skripsi->seminar->file_undangan_seminar)) }}" class="btn btn-success btn-sm" style="font-weight: 600;">
                                        📥 Unduh Undangan Seminar (PDF)
                                    </a>
                                </div>
                            @endif
                        </div>
                    @endif

                    <!-- Undangan & Jadwal Sidang untuk Pembimbing -->
                    @if ($skripsi->sidang && ($skripsi->sidang->tgl_sidang || $skripsi->sidang->file_undangan_sidang))
                        <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 0.5rem; padding: 0.85rem 1rem; margin-top: 0.85rem; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.75rem;">
                            <div>
                                <div style="font-weight: 700; color: #1e40af; font-size: 0.85rem; display: flex; align-items: center; gap: 0.35rem;">
                                    <span>✉️</span> Undangan Sidang Skripsi Mahasiswa Bimbingan
                                </div>
                                <div style="font-size: 0.82rem; color: #334155; margin-top: 0.2rem;">
                                    <strong>Jadwal:</strong> {{ $skripsi->sidang->tgl_sidang ? $skripsi->sidang->tgl_sidang->translatedFormat('l, d F Y') . ' (' . $skripsi->sidang->jam_sidang . ')' : 'Jadwal belum ditentukan' }}
                                    &bull; Ruangan: <strong>{{ $skripsi->sidang->ruangan ?? '-' }}</strong>
                                    @if ($skripsi->sidang->nomor_undangan_sidang)
                                        &bull; No. Undangan: <strong>{{ $skripsi->sidang->nomor_undangan_sidang }}</strong>
                                    @endif
                                </div>
                            </div>
                            @if ($skripsi->sidang->file_undangan_sidang)
                                <div style="display: flex; gap: 0.4rem; flex-wrap: wrap;">
                                    <a href="{{ route('dokumen.view', base64_encode($skripsi->sidang->file_undangan_sidang)) }}" target="_blank" class="btn btn-secondary btn-sm" style="font-weight: 600;">
                                        👁️ Lihat Surat Undangan
                                    </a>
                                    <a href="{{ route('dokumen.download', base64_encode($skripsi->sidang->file_undangan_sidang)) }}" class="btn btn-primary btn-sm" style="font-weight: 600;">
                                        📥 Unduh Berkas Undangan (PDF)
                                    </a>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            @empty
                <div style="text-align: center; color: var(--text-muted); padding: 3rem;">
                    Belum ada mahasiswa yang dibimbing.
                </div>
            @endforelse
        </div>

        <div style="margin-top: 1rem;">
            {{ $daftarBimbingan->links() }}
        </div>
    </div>

    <!-- TAB 2: PENGUJI SEMINAR -->
    <div id="tab-dosen-seminar" class="tab-content">
        <div style="font-size: 0.88rem; color: var(--text-muted); margin-bottom: 1.25rem;">
            Daftar tugas penguji seminar proposal/hasil skripsi yang ditugaskan kepada Anda.
        </div>

        <div style="display: flex; flex-direction: column; gap: 1.25rem;">
            @forelse ($jadwalUjiSeminar as $seminar)
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
                                Pembimbing: 1. {{ $sk->pembimbing1 ? $sk->pembimbing1->name : '-' }} | 2. {{ $sk->pembimbing2 ? $sk->pembimbing2->name : '-' }}
                            </div>
                        </div>
                        <span class="badge badge-{{ $seminar->status->value }}">{{ $seminar->status->label() }}</span>
                    </div>

                    <div style="background: #f8fafc; border-radius: 0.5rem; padding: 0.85rem 1rem; margin-top: 0.75rem; font-size: 0.85rem; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.75rem;">
                        <div>
                            <strong>Jadwal:</strong>
                            {{ $seminar->tgl_seminar ? $seminar->tgl_seminar->translatedFormat('l, d F Y') . ' (' . $seminar->jam_seminar . ')' : 'Jadwal belum ditentukan' }}
                            &bull; Ruangan: <strong>{{ $seminar->ruangan ?? '-' }}</strong>
                        </div>

                        <div style="display: flex; gap: 0.5rem;">
                            @if ($seminar->file_naskah_seminar)
                                <a href="{{ route('dokumen.download', base64_encode($seminar->file_naskah_seminar)) }}" class="btn btn-primary btn-sm">
                                    📄 Unduh Naskah Ujian (PDF)
                                </a>
                            @endif
                            @if ($seminar->file_undangan_seminar)
                                <a href="{{ route('dokumen.view', base64_encode($seminar->file_undangan_seminar)) }}" target="_blank" class="btn btn-secondary btn-sm">
                                    👁️ Lihat Undangan
                                </a>
                                <a href="{{ route('dokumen.download', base64_encode($seminar->file_undangan_seminar)) }}" class="btn btn-success btn-sm">
                                    📥 Unduh Undangan Seminar (PDF)
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div style="text-align: center; color: var(--text-muted); padding: 3rem;">
                    Belum ada penugasan penguji seminar.
                </div>
            @endforelse
        </div>

        <div style="margin-top: 1rem;">
            {{ $jadwalUjiSeminar->links() }}
        </div>
    </div>

    <!-- TAB 3: PENGUJI SIDANG -->
    <div id="tab-dosen-sidang" class="tab-content">
        <div style="font-size: 0.88rem; color: var(--text-muted); margin-bottom: 1.25rem;">
            Daftar tugas penguji sidang skripsi yang ditugaskan kepada Anda.
        </div>

        <div style="display: flex; flex-direction: column; gap: 1.25rem;">
            @forelse ($jadwalUjiSidang as $sidang)
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
                                Dewan Penguji: 1. {{ $sidang->penguji1 ? $sidang->penguji1->name : '-' }} | 2. {{ $sidang->penguji2 ? $sidang->penguji2->name : '-' }}
                            </div>
                        </div>
                        <span class="badge badge-{{ $sidang->status->value }}">{{ $sidang->status->label() }}</span>
                    </div>

                    <div style="background: #f8fafc; border-radius: 0.5rem; padding: 0.85rem 1rem; margin-top: 0.75rem; font-size: 0.85rem; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.75rem;">
                        <div>
                            <strong>Jadwal:</strong>
                            {{ $sidang->tgl_sidang ? $sidang->tgl_sidang->translatedFormat('l, d F Y') . ' (' . $sidang->jam_sidang . ')' : 'Jadwal belum ditentukan' }}
                            &bull; Ruangan: <strong>{{ $sidang->ruangan ?? '-' }}</strong>
                        </div>

                        <div style="display: flex; gap: 0.5rem;">
                            @if ($sidang->file_naskah_sidang)
                                <a href="{{ route('dokumen.download', base64_encode($sidang->file_naskah_sidang)) }}" class="btn btn-primary btn-sm">
                                    📘 Unduh Naskah Final (PDF)
                                </a>
                            @endif
                            @if ($sidang->file_undangan_sidang)
                                <a href="{{ route('dokumen.view', base64_encode($sidang->file_undangan_sidang)) }}" target="_blank" class="btn btn-secondary btn-sm">
                                    👁️ Lihat Undangan
                                </a>
                                <a href="{{ route('dokumen.download', base64_encode($sidang->file_undangan_sidang)) }}" class="btn btn-primary btn-sm">
                                    📥 Unduh Undangan (PDF)
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div style="text-align: center; color: var(--text-muted); padding: 3rem;">
                    Belum ada penugasan penguji sidang.
                </div>
            @endforelse
        </div>

        <div style="margin-top: 1rem;">
            {{ $jadwalUjiSidang->links() }}
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL TOLAK PENUGASAN DOSEN -->
<!-- ========================================== -->
<div id="modal-tolak-backdrop" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.5); backdrop-filter: blur(2px); z-index: 100;" onclick="closeTolakModal()"></div>

<div id="modal-tolak-dialog" style="display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 95%; max-width: 520px; background: #ffffff; border-radius: 0.75rem; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04); z-index: 101; overflow: hidden;">
    <!-- Modal Header -->
    <div style="padding: 1.25rem 1.5rem; background: #fff1f2; border-bottom: 1px solid #ffe4e6; display: flex; align-items: center; justify-content: space-between;">
        <div style="display: flex; align-items: center; gap: 0.5rem;">
            <span style="font-size: 1.25rem;">⚠️</span>
            <h3 style="font-size: 1.05rem; font-weight: 700; color: #9f1239; margin: 0;">
                Konfirmasi Penolakan Penugasan
            </h3>
        </div>
        <button type="button" onclick="closeTolakModal()" style="background: none; border: none; font-size: 1.2rem; cursor: pointer; color: #9f1239; line-height: 1;">✕</button>
    </div>

    <!-- Modal Form -->
    <form id="form-tolak-penugasan" method="POST" action="">
        @csrf
        <input type="hidden" name="aksi" value="tolak">
        
        <div style="padding: 1.5rem; display: flex; flex-direction: column; gap: 1rem;">
            <div style="background: #f8fafc; border: 1px solid var(--border); border-radius: 0.5rem; padding: 0.85rem;">
                <div id="modal-tolak-peran" style="font-size: 0.78rem; font-weight: 700; color: #1e40af;"></div>
                <div id="modal-tolak-mhs" style="font-size: 0.85rem; font-weight: 700; color: #0f172a; margin-top: 0.2rem;"></div>
                <div id="modal-tolak-judul" style="font-size: 0.82rem; color: #475569; margin-top: 0.2rem; font-style: italic;"></div>
            </div>

            <!-- Alasan Penolakan (Wajib) -->
            <div>
                <label class="form-label" style="font-weight: 700; font-size: 0.82rem; color: #0f172a; margin-bottom: 0.35rem; display: block;">
                    Alasan Penolakan <span style="color: #e11d48;">* (Wajib Diisi)</span>:
                </label>
                <textarea name="alasan_penolakan" required minlength="5" maxlength="1000" rows="3" class="form-control" style="font-size: 0.85rem; width: 100%; resize: vertical;" placeholder="Jelaskan alasan Anda, misal: Kuota bimbingan semester ini sudah penuh, topik di luar bidang kepakaran, atau bentrok dengan dinas/riset..."></textarea>
                <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.25rem;">
                    Alasan ini akan diteruskan secara resmi kepada Ketua Program Studi dan Admin Utama.
                </div>
            </div>

            <!-- Usulan Dosen Pengganti (Opsional) -->
            <div>
                <label class="form-label" style="font-weight: 700; font-size: 0.82rem; color: #0f172a; margin-bottom: 0.35rem; display: block;">
                    Rekomendasi Dosen Pengganti <span style="color: var(--text-muted); font-weight: 400;">(Opsional)</span>:
                </label>
                <select name="rekomendasi_dosen_id" class="form-control" style="font-size: 0.85rem; width: 100%;">
                    <option value="">-- Pilih rekan dosen yang relevan (opsional) --</option>
                    @foreach ($rekomendasiDosenList as $rd)
                        <option value="{{ $rd->id }}">{{ $rd->name }} ({{ $rd->nomor_induk }})</option>
                    @endforeach
                </select>
                <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.25rem;">
                    Bantu Kaprodi dengan menyarankan rekan sejawat yang lebih sesuai dengan topik mahasiswa ini.
                </div>
            </div>
        </div>

        <!-- Modal Footer -->
        <div style="padding: 1rem 1.5rem; background: #f8fafc; border-top: 1px solid var(--border); display: flex; justify-content: flex-end; gap: 0.75rem;">
            <button type="button" class="btn btn-secondary btn-sm" onclick="closeTolakModal()">
                Batal
            </button>
            <button type="submit" class="btn btn-danger btn-sm" style="font-weight: 600;">
                Kirim Penolakan Resmi
            </button>
        </div>
    </form>
</div>

<script>
    function openTolakModal(penugasanId, peran, mhsName, judul) {
        const backdrop = document.getElementById('modal-tolak-backdrop');
        const dialog = document.getElementById('modal-tolak-dialog');
        const form = document.getElementById('form-tolak-penugasan');

        form.action = `/dosen/penugasan/${penugasanId}/respon`;
        document.getElementById('modal-tolak-peran').innerText = peran;
        document.getElementById('modal-tolak-mhs').innerText = 'Mahasiswa: ' + mhsName;
        document.getElementById('modal-tolak-judul').innerText = '"' + judul + '"';

        backdrop.style.display = 'block';
        dialog.style.display = 'block';
    }

    function closeTolakModal() {
        document.getElementById('modal-tolak-backdrop').style.display = 'none';
        document.getElementById('modal-tolak-dialog').style.display = 'none';
    }
</script>

@endsection
