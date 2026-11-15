@extends('layouts.app')

@section('title', 'Fase 2: Seminar Skripsi')
@section('page_title', 'Tahap 2: Pengajuan & Pelaksanaan Seminar Skripsi')

@section('content')

@php
    $canApply = $skripsi && $skripsi->canAjukanSeminar();
    $seminar = $skripsi ? $skripsi->seminar : null;
@endphp

@if (!$canApply)
    <div class="card" style="text-align: center; padding: 3rem 1.5rem;">
        <div style="font-size: 3rem; margin-bottom: 1rem;">🔒</div>
        <h2 style="font-size: 1.35rem; font-weight: 800; color: #0f172a; margin-bottom: 0.5rem;">Tahap Seminar Belum Terbuka</h2>
        <p style="color: var(--text-muted); max-width: 500px; margin: 0 auto 1.5rem; font-size: 0.9rem;">
            Untuk dapat mengajukan seminar, Anda harus menyelesaikan <strong>Tahap 1: Pengajuan Judul</strong> hingga Surat Keputusan (SK) Bimbingan resmi diterbitkan oleh Program Studi.
        </p>
        <a href="{{ route('mahasiswa.skripsi.index') }}" class="btn btn-secondary">
            Periksa Status Judul & SK Bimbingan
        </a>
    </div>
@elseif (!$seminar)
    <div class="card" style="text-align: center; padding: 3rem 1.5rem;">
        <div style="font-size: 3rem; margin-bottom: 1rem;">🎯</div>
        <h2 style="font-size: 1.35rem; font-weight: 800; color: #0f172a; margin-bottom: 0.5rem;">Siap Mengajukan Seminar Skripsi</h2>
        <p style="color: var(--text-muted); max-width: 500px; margin: 0 auto 1.25rem; font-size: 0.9rem;">
            SK Bimbingan Anda telah aktif (No: <strong>{{ $skripsi->nomor_sk_bimbingan }}</strong>). Silakan siapkan naskah proposal, lembar persetujuan (ACC) pembimbing, bukti bayar seminar, dan sertifikat TOEFL untuk mendaftar.
        </p>

        @if (isset($kartuKendali))
            @php
                $syaratTerpenuhi = $kartuKendali['memenuhi_syarat_seminar'] ?? false;
            @endphp
            <div style="max-width: 540px; margin: 0 auto 1.5rem; text-align: left; padding: 1rem 1.15rem; border-radius: 0.65rem; border: 1px solid {{ $syaratTerpenuhi ? '#bbf7d0' : '#fde68a' }}; background: {{ $syaratTerpenuhi ? '#f0fdf4' : '#fffbeb' }};">
                <div style="font-size: 0.82rem; font-weight: 700; color: {{ $syaratTerpenuhi ? '#166534' : '#92400e' }}; margin-bottom: 0.35rem;">
                    🎫 Status Kartu Kendali Audiens Seminar ({{ $kartuKendali['total_hadir'] }}/{{ $kartuKendali['maks_total'] }} Tonton Hadir)
                </div>
                <div style="font-size: 0.8rem; color: #334155;">
                    Penyanggah Hadir: <strong>{{ $kartuKendali['penyanggah_hadir'] }}/{{ $kartuKendali['maks_penyanggah'] }}</strong> &bull;
                    Peserta Hadir: <strong>{{ $kartuKendali['peserta_hadir'] }}/{{ $kartuKendali['maks_total'] - $kartuKendali['maks_penyanggah'] }}</strong>
                </div>
                <div style="margin-top: 0.5rem; display: flex; justify-content: space-between; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                    <span style="font-size: 0.76rem; color: #475569;">
                        @if ($syaratTerpenuhi)
                            Persyaratan {{ $kartuKendali['maks_total'] }}x menonton seminar telah terpenuhi.
                        @elseif (!empty($enforceSyaratAudiens))
                            Wajib memenuhi {{ $kartuKendali['maks_total'] }}x kehadiran audiens sebelum mendaftar seminar.
                        @else
                            Pantau progres kehadiran seminar Anda melalui Kartu Kendali.
                        @endif
                    </span>
                    <a href="{{ route('mahasiswa.audiens.index') }}" class="btn btn-secondary btn-sm" style="font-size: 0.76rem;">Buka Kartu Kendali</a>
                </div>
            </div>
        @endif

        @if (!empty($enforceSyaratAudiens) && isset($kartuKendali) && !($kartuKendali['memenuhi_syarat_seminar'] ?? false))
            <a href="{{ route('mahasiswa.audiens.index') }}" class="btn btn-primary">
                Lengkapi Kehadiran Audiens ({{ $kartuKendali['total_hadir'] }}/{{ $kartuKendali['maks_total'] }})
            </a>
        @else
            <a href="{{ route('mahasiswa.seminar.create') }}" class="btn btn-primary">
                + Daftar Seminar Skripsi Sekarang
            </a>
        @endif
    </div>
@else
    <!-- Tampilan Detail Seminar Terdaftar -->
    <div class="card">
        <div class="card-header">
            <div>
                <span style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Status Seminar</span>
                <h2 class="card-title" style="margin-top: 0.2rem;">
                    Tahap 2: {{ $seminar->status->label() }}
                </h2>
            </div>
            <span class="badge badge-{{ $seminar->status->value }}">{{ $seminar->status->label() }}</span>
        </div>

        @if ($seminar->status->value === 'diajukan')
            <div class="alert alert-warning" style="margin-bottom: 1rem;">
                <span>⏳</span>
                <div>
                    <strong>Pendaftaran Seminar Berhasil Diajukan</strong>
                    <p style="margin-top: 0.2rem;">Menunggu Ketua Program Studi menetapkan Dosen Penguji dan Admin mengatur jadwal pelaksanaan serta menerbitkan surat undangan.</p>
                </div>
            </div>
        @elseif ($seminar->status->value === 'diproses')
            <div class="alert alert-info" style="margin-bottom: 1rem;">
                <span>📅</span>
                <div>
                    <strong>Jadwal & Penguji Seminar Telah Ditetapkan</strong>
                    <p style="margin-top: 0.2rem;">Perhatikan tanggal, jam, dan ruangan di bawah. Pastikan Anda mengunduh Surat Undangan dan SK Seminar.</p>
                </div>
            </div>
        @elseif ($seminar->status->value === 'selesai')
            <div class="alert alert-success" style="margin-bottom: 1rem;">
                <span>🎉</span>
                <div>
                    <strong>Selamat! Anda Telah Lulus Seminar Skripsi</strong>
                    <p style="margin-top: 0.2rem;">Nilai akhir seminar Anda telah diinput. Anda sekarang dapat melanjutkan perbaikan naskah dan mendaftar ke <strong>Tahap 3: Sidang Skripsi</strong>.</p>
                </div>
            </div>
        @endif

        <div style="display: grid; grid-template-columns: 1fr; gap: 1.5rem; margin-top: 1rem;">
            <!-- Informasi Jadwal & Penguji -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1rem;">
                <div style="background: #f8fafc; border: 1px solid var(--border); border-radius: 0.5rem; padding: 1.25rem;">
                    <span style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Dosen Penguji Seminar</span>
                    <div style="font-size: 1.05rem; font-weight: 700; color: #0f172a; margin-top: 0.35rem;">
                        {{ $seminar->penguji ? $seminar->penguji->name : 'Menunggu Penetapan Kaprodi' }}
                    </div>
                </div>

                <div style="background: #f8fafc; border: 1px solid var(--border); border-radius: 0.5rem; padding: 1.25rem;">
                    <span style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Jadwal Pelaksanaan & Ruang</span>
                    <div style="font-size: 1.05rem; font-weight: 700; color: #0f172a; margin-top: 0.35rem;">
                        @if ($seminar->tgl_seminar)
                            {{ $seminar->tgl_seminar->translatedFormat('l, d F Y') }} | Pukul {{ $seminar->jam_seminar }}
                            <div style="font-size: 0.85rem; font-weight: 500; color: #475569; margin-top: 0.2rem;">Ruangan: <strong>{{ $seminar->ruangan }}</strong></div>
                        @else
                            <span style="color: var(--text-muted); font-size: 0.95rem;">Jadwal belum ditentukan admin</span>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Box Hasil Nilai Seminar (Jika Selesai) -->
            @if ($seminar->nilai_seminar !== null)
                @php
                    $evalSem = $seminar->hasil_evaluasi;
                @endphp
                <div style="background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 0.5rem; padding: 1.25rem;">
                    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.5rem;">
                        <div>
                            <span style="font-size: 0.75rem; font-weight: 700; color: #065f46; text-transform: uppercase;">Hasil / Nilai Akhir Seminar</span>
                            <div style="font-size: 1.5rem; font-weight: 800; color: #047857; margin-top: 0.25rem;">
                                {{ number_format($seminar->nilai_seminar, 2) }}
                            </div>
                        </div>
                        <span class="badge {{ $evalSem ? $evalSem->badgeClass() : 'badge-selesai' }}" style="font-size: 0.85rem; padding: 0.4rem 0.8rem;">
                            {{ $evalSem ? strtoupper($evalSem->label()) : 'LULUS SEMINAR' }}
                        </span>
                    </div>
                    @if (!empty($seminar->rincian_nilai))
                        <div style="font-size: 0.78rem; color: #065f46; margin-top: 0.5rem; display: flex; flex-wrap: wrap; gap: 0.75rem;">
                            @if (isset($seminar->rincian_nilai['pembimbing_1'])) <span>Pembimbing 1: <strong>{{ $seminar->rincian_nilai['pembimbing_1'] }}</strong></span> @endif
                            @if (isset($seminar->rincian_nilai['pembimbing_2'])) <span>Pembimbing 2: <strong>{{ $seminar->rincian_nilai['pembimbing_2'] }}</strong></span> @endif
                            @if (isset($seminar->rincian_nilai['penguji'])) <span>Penguji: <strong>{{ $seminar->rincian_nilai['penguji'] }}</strong></span> @endif
                        </div>
                    @endif
                    @if ($seminar->catatan)
                        <div style="font-size: 0.85rem; color: #065f46; margin-top: 0.75rem; border-top: 1px dashed #a7f3d0; padding-top: 0.5rem;">
                            <strong>Catatan / Saran Penguji:</strong> {{ $seminar->catatan }}
                        </div>
                    @endif
                    @if ($seminar->catatan_revisi || $seminar->tenggat_revisi || $seminar->bebas_revisi_at)
                        <div style="font-size: 0.82rem; color: #064e3b; margin-top: 0.65rem; border-top: 1px dashed #a7f3d0; padding-top: 0.55rem;">
                            @if ($seminar->catatan_revisi)
                                <div><strong>Catatan Revisi:</strong> {{ $seminar->catatan_revisi }}</div>
                            @endif
                            @if ($seminar->tenggat_revisi)
                                <div><strong>Tenggat Revisi:</strong> {{ $seminar->tenggat_revisi->translatedFormat('d F Y') }}</div>
                            @endif
                            <div>
                                <strong>Status Bebas Revisi:</strong>
                                @if ($seminar->bebas_revisi_at)
                                    <span style="color:#047857; font-weight:700;">Disetujui Digital ({{ $seminar->bebas_revisi_at->translatedFormat('d M Y, H:i') }})</span>
                                @else
                                    <span style="color:#b45309; font-weight:600;">Menunggu Verifikasi Bebas Revisi</span>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            @endif

            <!-- Berkas Syarat Terunggah -->
            <div>
                <div style="font-size: 0.85rem; font-weight: 700; margin-bottom: 0.75rem; color: #334155;">Berkas Persyaratan Seminar:</div>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 0.75rem;">
                    @if ($seminar->file_naskah_seminar)
                        <a href="{{ route('dokumen.download', base64_encode($seminar->file_naskah_seminar)) }}" class="btn btn-secondary btn-sm" style="justify-content: flex-start;">
                            <span>📄</span> Naskah Proposal (PDF)
                        </a>
                    @endif
                    @if ($seminar->file_acc_pembimbing)
                        <a href="{{ route('dokumen.download', base64_encode($seminar->file_acc_pembimbing)) }}" class="btn btn-secondary btn-sm" style="justify-content: flex-start;">
                            <span>✍️</span> Lembar ACC Pembimbing
                        </a>
                    @endif
                    @if ($seminar->file_bukti_bayar_seminar)
                        <a href="{{ route('dokumen.download', base64_encode($seminar->file_bukti_bayar_seminar)) }}" class="btn btn-secondary btn-sm" style="justify-content: flex-start;">
                            <span>💳</span> Bukti Bayar Seminar
                        </a>
                    @endif
                    @if ($seminar->file_toefl)
                        <a href="{{ route('dokumen.download', base64_encode($seminar->file_toefl)) }}" class="btn btn-secondary btn-sm" style="justify-content: flex-start;">
                            <span>📜</span> Sertifikat TOEFL
                        </a>
                    @endif
                </div>
            </div>

            <!-- Dokumen Surat Undangan & SK Seminar -->
            <div style="border-top: 1px solid var(--border); padding-top: 1.25rem;">
                <div style="font-size: 0.95rem; font-weight: 700; margin-bottom: 0.75rem;">Surat Undangan & SK Penguji Seminar</div>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 1rem;">
                    <div style="background: #fff; border: 1px solid var(--border); border-radius: 0.5rem; padding: 1rem;">
                        <span style="font-size: 0.72rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Surat Undangan Seminar</span>
                        <div style="font-weight: 700; font-size: 0.92rem; margin: 0.25rem 0 0.5rem;">
                            {{ $seminar->nomor_undangan_seminar ?? 'Belum diterbitkan' }}
                        </div>
                        @if ($seminar->file_undangan_seminar)
                            <a href="{{ route('dokumen.download', base64_encode($seminar->file_undangan_seminar)) }}" class="btn btn-secondary btn-sm" style="width: 100%;">
                                📥 Unduh Surat Undangan (PDF)
                            </a>
                        @endif
                    </div>

                    <div style="background: #fff; border: 1px solid var(--border); border-radius: 0.5rem; padding: 1rem;">
                        <span style="font-size: 0.72rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">SK Dosen Penguji Seminar</span>
                        <div style="font-weight: 700; font-size: 0.92rem; margin: 0.25rem 0 0.5rem;">
                            {{ $seminar->nomor_sk_seminar ?? 'Belum diterbitkan' }}
                        </div>
                        @if ($seminar->file_sk_seminar)
                            <a href="{{ route('dokumen.download', base64_encode($seminar->file_sk_seminar)) }}" class="btn btn-secondary btn-sm" style="width: 100%;">
                                📥 Unduh SK Penguji (PDF)
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif

@endsection
