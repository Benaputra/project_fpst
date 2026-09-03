@extends('layouts.app')

@section('title', 'Daftar Bimbingan & Jadwal Uji')
@section('page_title', 'Portal Dosen: Bimbingan & Jadwal Penguji')

@section('content')

<div class="card">
    <!-- Tab Navigasi -->
    <div class="tab-nav">
        <button type="button" class="tab-btn active" onclick="switchTab(event, 'tab-dosen-bimbingan')">
            1. Mahasiswa Bimbingan ({{ $daftarBimbingan->total() }})
        </button>
        <button type="button" class="tab-btn" onclick="switchTab(event, 'tab-dosen-seminar')">
            2. Tugas Penguji Seminar ({{ $jadwalUjiSeminar->total() }})
        </button>
        <button type="button" class="tab-btn" onclick="switchTab(event, 'tab-dosen-sidang')">
            3. Tugas Penguji Sidang ({{ $jadwalUjiSidang->total() }})
        </button>
    </div>

    <!-- TAB 1: MAHASISWA BIMBINGAN -->
    <div id="tab-dosen-bimbingan" class="tab-content active">
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

                        @if ($skripsi->seminar && $skripsi->seminar->file_undangan_seminar)
                            <a href="{{ route('dokumen.download', base64_encode($skripsi->seminar->file_undangan_seminar)) }}" class="btn btn-success btn-sm">
                                ✉️ Undangan Seminar (PDF)
                            </a>
                        @endif

                        @if ($skripsi->sidang && $skripsi->sidang->file_naskah_sidang)
                            <a href="{{ route('dokumen.download', base64_encode($skripsi->sidang->file_naskah_sidang)) }}" class="btn btn-secondary btn-sm">
                                📘 Naskah Sidang Final
                            </a>
                        @endif

                        @if ($skripsi->sidang && $skripsi->sidang->file_undangan_sidang)
                            <a href="{{ route('dokumen.download', base64_encode($skripsi->sidang->file_undangan_sidang)) }}" class="btn btn-primary btn-sm">
                                ✉️ Undangan Sidang (PDF)
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
                                <a href="{{ route('dokumen.download', base64_encode($skripsi->seminar->file_undangan_seminar)) }}" class="btn btn-success btn-sm" style="font-weight: 600;">
                                    📥 Unduh Undangan Seminar (PDF)
                                </a>
                            @endif
                        </div>
                    @endif

                    <!-- Undangan & Jadwal Sidang untuk Pembimbing -->
                    @if ($skripsi->sidang && ($skripsi->sidang->tgl_sidang || $skripsi->sidang->file_undangan_sidang))
                        <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 0.5rem; padding: 0.85rem 1rem; margin-top: 0.85rem; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.75rem;">
                            <div>
                                <div style="font-weight: 700; color: #1e40af; font-size: 0.85rem; display: flex; align-items: center; gap: 0.35rem;">
                                    <span>✉️</span> Undangan Sidang Meja Hijau Mahasiswa Bimbingan
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
                                <a href="{{ route('dokumen.download', base64_encode($skripsi->sidang->file_undangan_sidang)) }}" class="btn btn-primary btn-sm" style="font-weight: 600;">
                                    📥 Unduh Berkas Undangan (PDF)
                                </a>
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
                                <a href="{{ route('dokumen.download', base64_encode($seminar->file_undangan_seminar)) }}" class="btn btn-secondary btn-sm">
                                    ✉️ Unduh Undangan Seminar (PDF)
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
            Daftar tugas penguji sidang skripsi (meja hijau) yang ditugaskan kepada Anda.
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
                                <a href="{{ route('dokumen.download', base64_encode($sidang->file_undangan_sidang)) }}" class="btn btn-secondary btn-sm">
                                    ✉️ Undangan
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

@endsection
