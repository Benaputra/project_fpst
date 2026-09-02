@extends('layouts.app')

@section('title', 'Dashboard - Portal Skripsi')
@section('page_title', 'Dashboard')

@section('content')

@if ($user->isMahasiswa())
    <!-- Dashboard Mahasiswa -->
    <div class="card">
        <div class="card-header">
            <div>
                <h2 class="card-title">Progres Tahapan Skripsi Anda</h2>
                <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.2rem;">
                    NIM: {{ $user->nomor_induk }} | Program Studi: {{ $user->programStudi ? $user->programStudi->nama : '-' }}
                </div>
            </div>
            @if ($skripsi)
                <span class="badge badge-{{ $skripsi->status->value }}">{{ $skripsi->status->label() }}</span>
            @else
                <span class="badge badge-diajukan">Belum Mengajukan</span>
            @endif
        </div>

        @php
            $isFase1Done = $skripsi && $skripsi->isSelesai();
            $isFase2Done = $skripsi && $skripsi->seminar && $skripsi->seminar->isSelesai();
            $isFase3Done = $skripsi && $skripsi->sidang && $skripsi->sidang->isSelesai();
        @endphp

        <!-- Stepper Component -->
        <div class="stepper">
            <div class="step-item {{ $isFase1Done ? 'completed' : ($skripsi ? 'active' : '') }}">
                <div class="step-circle">{{ $isFase1Done ? '✓' : '1' }}</div>
                <div class="step-label">Pengajuan Judul & SK</div>
            </div>
            <div class="step-item {{ $isFase2Done ? 'completed' : ($isFase1Done ? 'active' : '') }}">
                <div class="step-circle">{{ $isFase2Done ? '✓' : '2' }}</div>
                <div class="step-label">Seminar Skripsi</div>
            </div>
            <div class="step-item {{ $isFase3Done ? 'completed' : ($isFase2Done ? 'active' : '') }}">
                <div class="step-circle">{{ $isFase3Done ? '✓' : '3' }}</div>
                <div class="step-label">Sidang Skripsi</div>
            </div>
        </div>

        @if (!$skripsi)
            <div style="text-align: center; padding: 2rem; background: #f8fafc; border-radius: 0.5rem; border: 1px dashed #cbd5e1;">
                <h3 style="font-size: 1.1rem; color: #334155; margin-bottom: 0.5rem;">Anda belum mengajukan judul skripsi</h3>
                <p style="font-size: 0.85rem; color: #64748b; max-width: 480px; margin: 0 auto 1.25rem;">
                    Silakan mulai dengan mengajukan judul skripsi beserta berkas draf proposal, transkrip sementara, dan bukti pembayaran skripsi.
                </p>
                <a href="{{ route('mahasiswa.skripsi.create') }}" class="btn btn-primary">
                    Ajukan Judul Skripsi Sekarang
                </a>
            </div>
        @else
            <div style="background: #f8fafc; border-radius: 0.5rem; padding: 1.25rem; border: 1px solid var(--border);">
                <div style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Judul Skripsi Terdaftar</div>
                <div style="font-size: 1.05rem; font-weight: 700; color: #0f172a; margin: 0.35rem 0 1rem;">
                    "{{ $skripsi->judul }}"
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; border-top: 1px solid var(--border); padding-top: 0.85rem; font-size: 0.85rem;">
                    <div>
                        <span style="color: var(--text-muted);">Pembimbing 1:</span>
                        <div style="font-weight: 600;">{{ $skripsi->pembimbing1 ? $skripsi->pembimbing1->name : 'Menunggu Penetapan' }}</div>
                    </div>
                    <div>
                        <span style="color: var(--text-muted);">Pembimbing 2:</span>
                        <div style="font-weight: 600;">{{ $skripsi->pembimbing2 ? $skripsi->pembimbing2->name : '-' }}</div>
                    </div>
                    <div>
                        <span style="color: var(--text-muted);">Nomor SK Bimbingan:</span>
                        <div style="font-weight: 600;">
                            @if ($skripsi->nomor_sk_bimbingan)
                                <span style="color: #166534;">{{ $skripsi->nomor_sk_bimbingan }}</span>
                            @else
                                <span style="color: #92400e;">Dalam Proses</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Action Cards for Mahasiswa -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem; margin-top: 1.5rem;">
                <!-- Card Fase 1 -->
                <div style="border: 1px solid var(--border); border-radius: 0.65rem; padding: 1rem; background: #fff;">
                    <div style="font-weight: 700; font-size: 0.95rem; margin-bottom: 0.35rem;">1. Judul & SK Bimbingan</div>
                    <p style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.75rem;">Status: <strong class="badge badge-{{ $skripsi->status->value }}">{{ $skripsi->status->label() }}</strong></p>
                    <a href="{{ route('mahasiswa.skripsi.index') }}" class="btn btn-secondary btn-sm" style="width: 100%;">Detail Judul & SK</a>
                </div>

                <!-- Card Fase 2 -->
                <div style="border: 1px solid var(--border); border-radius: 0.65rem; padding: 1rem; background: #fff;">
                    <div style="font-weight: 700; font-size: 0.95rem; margin-bottom: 0.35rem;">2. Seminar Skripsi</div>
                    @if (!$skripsi->canAjukanSeminar())
                        <p style="font-size: 0.8rem; color: #dc2626; margin-bottom: 0.75rem;">🔒 Terkunci (Tunggu SK Bimbingan)</p>
                        <button class="btn btn-secondary btn-sm" style="width: 100%;" disabled>Belum Terbuka</button>
                    @elseif (!$skripsi->seminar)
                        <p style="font-size: 0.8rem; color: #166534; margin-bottom: 0.75rem;">✅ Syarat Terpenuhi</p>
                        <a href="{{ route('mahasiswa.seminar.create') }}" class="btn btn-primary btn-sm" style="width: 100%;">Daftar Seminar</a>
                    @else
                        <p style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.75rem;">Status: <strong class="badge badge-{{ $skripsi->seminar->status->value }}">{{ $skripsi->seminar->status->label() }}</strong></p>
                        <a href="{{ route('mahasiswa.seminar.index') }}" class="btn btn-secondary btn-sm" style="width: 100%;">Detail Seminar</a>
                    @endif
                </div>

                <!-- Card Fase 3 -->
                <div style="border: 1px solid var(--border); border-radius: 0.65rem; padding: 1rem; background: #fff;">
                    <div style="font-weight: 700; font-size: 0.95rem; margin-bottom: 0.35rem;">3. Sidang Skripsi</div>
                    @if (!$isFase2Done)
                        <p style="font-size: 0.8rem; color: #dc2626; margin-bottom: 0.75rem;">🔒 Terkunci (Selesaikan Seminar)</p>
                        <button class="btn btn-secondary btn-sm" style="width: 100%;" disabled>Belum Terbuka</button>
                    @elseif (!$skripsi->sidang)
                        <p style="font-size: 0.8rem; color: #166534; margin-bottom: 0.75rem;">✅ Siap Daftar Sidang</p>
                        <a href="{{ route('mahasiswa.sidang.create') }}" class="btn btn-primary btn-sm" style="width: 100%;">Daftar Sidang Skripsi</a>
                    @else
                        <p style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.75rem;">Status: <strong class="badge badge-{{ $skripsi->sidang->status->value }}">{{ $skripsi->sidang->status->label() }}</strong></p>
                        <a href="{{ route('mahasiswa.sidang.index') }}" class="btn btn-secondary btn-sm" style="width: 100%;">Detail Sidang</a>
                    @endif
                </div>
            </div>
        @endif
    </div>

@elseif ($user->isKaprodi())
    <!-- Dashboard Kaprodi -->
    <div class="grid-stats">
        <div class="stat-box">
            <div class="stat-box-title">Judul Menunggu Review</div>
            <div class="stat-box-value">{{ $pendingJudulCount }}</div>
        </div>
        <div class="stat-box">
            <div class="stat-box-title">Seminar Butuh Penguji</div>
            <div class="stat-box-value">{{ $pendingSeminarPengujiCount }}</div>
        </div>
        <div class="stat-box">
            <div class="stat-box-title">Sidang Butuh 2 Penguji</div>
            <div class="stat-box-value">{{ $pendingSidangPengujiCount }}</div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Pengajuan Judul Terbaru (Program Studi {{ $user->programStudi ? $user->programStudi->nama : '' }})</h2>
            <a href="{{ route('kaprodi.penetapan.index') }}" class="btn btn-primary btn-sm">Buka Halaman Penetapan</a>
        </div>

        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Mahasiswa / NIM</th>
                        <th>Judul Skripsi</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($recentJudul as $j)
                        <tr>
                            <td>
                                <strong>{{ $j->mahasiswa->name }}</strong>
                                <div style="font-size: 0.75rem; color: var(--text-muted);">{{ $j->mahasiswa->nomor_induk }}</div>
                            </td>
                            <td>{{ Str::limit($j->judul, 70) }}</td>
                            <td><span class="badge badge-{{ $j->status->value }}">{{ $j->status->label() }}</span></td>
                            <td>
                                <a href="{{ route('kaprodi.penetapan.index') }}" class="btn btn-secondary btn-sm">Tinjau & Plot</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" style="text-align: center; color: var(--text-muted); padding: 2rem;">Belum ada pengajuan judul.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

@elseif ($user->isAdmin())
    <!-- Dashboard Admin -->
    <div class="grid-stats">
        <div class="stat-box">
            <div class="stat-box-title">Total Skripsi</div>
            <div class="stat-box-value">{{ $skripsiCount }}</div>
        </div>
        <div class="stat-box">
            <div class="stat-box-title">SK Bimbingan Butuh Terbit</div>
            <div class="stat-box-value">{{ $pendingSkBimbinganCount }}</div>
        </div>
        <div class="stat-box">
            <div class="stat-box-title">Jadwal & SK Seminar Pending</div>
            <div class="stat-box-value">{{ $pendingJadwalSeminarCount }}</div>
        </div>
        <div class="stat-box">
            <div class="stat-box-title">Jadwal & SK Sidang Pending</div>
            <div class="stat-box-value">{{ $pendingJadwalSidangCount }}</div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Aktivitas Administrasi Skripsi</h2>
            <a href="{{ route('admin.administrasi.index') }}" class="btn btn-primary btn-sm">Kelola Administrasi & SK</a>
        </div>

        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Mahasiswa / NIM</th>
                        <th>Judul Skripsi</th>
                        <th>Pembimbing</th>
                        <th>No. SK Bimbingan</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($recentSkripsi as $s)
                        <tr>
                            <td>
                                <strong>{{ $s->mahasiswa->name }}</strong>
                                <div style="font-size: 0.75rem; color: var(--text-muted);">{{ $s->mahasiswa->nomor_induk }}</div>
                            </td>
                            <td>{{ Str::limit($s->judul, 60) }}</td>
                            <td>
                                <div style="font-size: 0.8rem;">1. {{ $s->pembimbing1 ? $s->pembimbing1->name : '-' }}</div>
                                @if ($s->pembimbing2)
                                    <div style="font-size: 0.8rem; color: var(--text-muted);">2. {{ $s->pembimbing2->name }}</div>
                                @endif
                            </td>
                            <td>{{ $s->nomor_sk_bimbingan ?? 'Belum terbit' }}</td>
                            <td><span class="badge badge-{{ $s->status->value }}">{{ $s->status->label() }}</span></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" style="text-align: center; color: var(--text-muted); padding: 2rem;">Belum ada data skripsi.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

@elseif ($user->isDosen())
    <!-- Dashboard Dosen -->
    <div class="grid-stats">
        <div class="stat-box">
            <div class="stat-box-title">Bimbingan Utama (1)</div>
            <div class="stat-box-value">{{ $bimbingan1Count }}</div>
        </div>
        <div class="stat-box">
            <div class="stat-box-title">Bimbingan Pendamping (2)</div>
            <div class="stat-box-value">{{ $bimbingan2Count }}</div>
        </div>
        <div class="stat-box">
            <div class="stat-box-title">Menguji Seminar</div>
            <div class="stat-box-value">{{ $ujiSeminarCount }}</div>
        </div>
        <div class="stat-box">
            <div class="stat-box-title">Menguji Sidang</div>
            <div class="stat-box-value">{{ $ujiSidangCount }}</div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Daftar Mahasiswa Bimbingan Aktif</h2>
            <a href="{{ route('dosen.bimbingan.index') }}" class="btn btn-primary btn-sm">Lihat Semua Bimbingan & Ujian</a>
        </div>

        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Mahasiswa / NIM</th>
                        <th>Judul Skripsi</th>
                        <th>Peran</th>
                        <th>No. SK</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($bimbinganAktif as $b)
                        <tr>
                            <td>
                                <strong>{{ $b->mahasiswa->name }}</strong>
                                <div style="font-size: 0.75rem; color: var(--text-muted);">{{ $b->mahasiswa->nomor_induk }}</div>
                            </td>
                            <td>{{ Str::limit($b->judul, 70) }}</td>
                            <td>
                                @if ($b->pembimbing_1_id === $user->id)
                                    <span class="badge badge-diproses">Pembimbing 1</span>
                                @else
                                    <span class="badge badge-diajukan">Pembimbing 2</span>
                                @endif
                            </td>
                            <td>{{ $b->nomor_sk_bimbingan ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" style="text-align: center; color: var(--text-muted); padding: 2rem;">Belum ada mahasiswa yang dibimbing.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endif

@endsection
