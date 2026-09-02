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
        <p style="color: var(--text-muted); max-width: 500px; margin: 0 auto 1.5rem; font-size: 0.9rem;">
            SK Bimbingan Anda telah aktif (No: <strong>{{ $skripsi->nomor_sk_bimbingan }}</strong>). Silakan siapkan naskah proposal, lembar persetujuan (ACC) pembimbing, bukti bayar seminar, dan sertifikat TOEFL untuk mendaftar.
        </p>
        <a href="{{ route('mahasiswa.seminar.create') }}" class="btn btn-primary">
            + Daftar Seminar Skripsi Sekarang
        </a>
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
                <div style="background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 0.5rem; padding: 1.25rem;">
                    <div style="display: flex; align-items: center; justify-content: space-between;">
                        <div>
                            <span style="font-size: 0.75rem; font-weight: 700; color: #065f46; text-transform: uppercase;">Hasil / Nilai Akhir Seminar</span>
                            <div style="font-size: 1.5rem; font-weight: 800; color: #047857; margin-top: 0.25rem;">
                                {{ number_format($seminar->nilai_seminar, 2) }}
                            </div>
                        </div>
                        <span class="badge badge-selesai" style="font-size: 0.85rem; padding: 0.4rem 0.8rem;">LULUS SEMINAR</span>
                    </div>
                    @if ($seminar->catatan)
                        <div style="font-size: 0.85rem; color: #065f46; margin-top: 0.75rem; border-top: 1px dashed #a7f3d0; padding-top: 0.5rem;">
                            <strong>Catatan / Saran Penguji:</strong> {{ $seminar->catatan }}
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
