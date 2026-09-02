@extends('layouts.app')

@section('title', 'Fase 3: Sidang Skripsi')
@section('page_title', 'Tahap 3: Pengajuan & Pelaksanaan Sidang Skripsi')

@section('content')

@php
    $seminarDone = $skripsi && $skripsi->seminar && $skripsi->seminar->isSelesai();
    $sidang = $skripsi ? $skripsi->sidang : null;
@endphp

@if (!$seminarDone)
    <div class="card" style="text-align: center; padding: 3rem 1.5rem;">
        <div style="font-size: 3rem; margin-bottom: 1rem;">🔒</div>
        <h2 style="font-size: 1.35rem; font-weight: 800; color: #0f172a; margin-bottom: 0.5rem;">Tahap Sidang Skripsi Belum Terbuka</h2>
        <p style="color: var(--text-muted); max-width: 500px; margin: 0 auto 1.5rem; font-size: 0.9rem;">
            Untuk dapat mengajukan Sidang Skripsi (Meja Hijau), Anda harus telah <strong>Lulus Tahap 2: Seminar Skripsi</strong> dan menyelesaikan revisi seminar.
        </p>
        <a href="{{ route('mahasiswa.seminar.index') }}" class="btn btn-secondary">
            Periksa Status Seminar Skripsi
        </a>
    </div>
@elseif (!$sidang)
    <div class="card" style="text-align: center; padding: 3rem 1.5rem;">
        <div style="font-size: 3rem; margin-bottom: 1rem;">🎓</div>
        <h2 style="font-size: 1.35rem; font-weight: 800; color: #0f172a; margin-bottom: 0.5rem;">Siap Mengajukan Sidang Skripsi (Meja Hijau)</h2>
        <p style="color: var(--text-muted); max-width: 500px; margin: 0 auto 1.5rem; font-size: 0.9rem;">
            Selamat! Anda telah lulus seminar dengan nilai <strong>{{ number_format($skripsi->seminar->nilai_seminar, 2) }}</strong>. Silakan siapkan naskah skripsi final, lembar ACC sidang dari pembimbing, lembar bebas revisi seminar, dan bukti bayar sidang/SPP.
        </p>
        <a href="{{ route('mahasiswa.sidang.create') }}" class="btn btn-primary">
            + Daftar Sidang Skripsi Sekarang
        </a>
    </div>
@else
    <!-- Tampilan Detail Sidang Terdaftar -->
    <div class="card">
        <div class="card-header">
            <div>
                <span style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Status Sidang Meja Hijau</span>
                <h2 class="card-title" style="margin-top: 0.2rem;">
                    Tahap 3: {{ $sidang->status->label() }}
                </h2>
            </div>
            <span class="badge badge-{{ $sidang->status->value }}">{{ $sidang->status->label() }}</span>
        </div>

        @if ($sidang->status->value === 'diajukan')
            <div class="alert alert-warning" style="margin-bottom: 1rem;">
                <span>⏳</span>
                <div>
                    <strong>Pendaftaran Sidang Skripsi Berhasil Diajukan</strong>
                    <p style="margin-top: 0.2rem;">Menunggu Ketua Program Studi menetapkan 2 Orang Dosen Penguji Sidang dan Admin menjadwalkan sidang serta menerbitkan SK Dewan Penguji.</p>
                </div>
            </div>
        @elseif ($sidang->status->value === 'diproses')
            <div class="alert alert-info" style="margin-bottom: 1rem;">
                <span>📅</span>
                <div>
                    <strong>Jadwal & 2 Penguji Sidang Telah Ditetapkan</strong>
                    <p style="margin-top: 0.2rem;">Perhatikan tanggal, jam, dan ruangan di bawah. Pastikan Anda mengunduh Surat Undangan dan SK Dewan Penguji Sidang.</p>
                </div>
            </div>
        @elseif ($sidang->status->value === 'selesai')
            <div class="alert alert-success" style="margin-bottom: 1rem;">
                <span>🎉</span>
                <div>
                    <strong>Selamat! Anda Telah Dinyatakan LULUS SIDANG SKRIPSI</strong>
                    <p style="margin-top: 0.2rem;">Seluruh proses penyusunan skripsi Anda telah tuntas dengan nilai akhir yang memuaskan. Silakan lakukan proses administrasi kelulusan & yudisium.</p>
                </div>
            </div>
        @endif

        <div style="display: grid; grid-template-columns: 1fr; gap: 1.5rem; margin-top: 1rem;">
            <!-- Informasi 2 Penguji & Jadwal -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1rem;">
                <div style="background: #f8fafc; border: 1px solid var(--border); border-radius: 0.5rem; padding: 1.25rem;">
                    <span style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">2 Dosen Penguji Sidang</span>
                    <div style="font-size: 0.95rem; font-weight: 700; color: #0f172a; margin-top: 0.4rem;">
                        <div>1. {{ $sidang->penguji1 ? $sidang->penguji1->name : 'Menunggu Penetapan' }}</div>
                        <div style="margin-top: 0.25rem;">2. {{ $sidang->penguji2 ? $sidang->penguji2->name : 'Menunggu Penetapan' }}</div>
                    </div>
                </div>

                <div style="background: #f8fafc; border: 1px solid var(--border); border-radius: 0.5rem; padding: 1.25rem;">
                    <span style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Jadwal Pelaksanaan Sidang</span>
                    <div style="font-size: 1.05rem; font-weight: 700; color: #0f172a; margin-top: 0.35rem;">
                        @if ($sidang->tgl_sidang)
                            {{ $sidang->tgl_sidang->translatedFormat('l, d F Y') }} | Pukul {{ $sidang->jam_sidang }}
                            <div style="font-size: 0.85rem; font-weight: 500; color: #475569; margin-top: 0.2rem;">Ruangan: <strong>{{ $sidang->ruangan }}</strong></div>
                        @else
                            <span style="color: var(--text-muted); font-size: 0.95rem;">Jadwal belum ditentukan admin</span>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Box Hasil Nilai Sidang (Jika Selesai) -->
            @if ($sidang->nilai_sidang !== null)
                <div style="background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 0.5rem; padding: 1.25rem;">
                    <div style="display: flex; align-items: center; justify-content: space-between;">
                        <div>
                            <span style="font-size: 0.75rem; font-weight: 700; color: #065f46; text-transform: uppercase;">Nilai Akhir Sidang Skripsi</span>
                            <div style="font-size: 1.6rem; font-weight: 800; color: #047857; margin-top: 0.25rem;">
                                {{ number_format($sidang->nilai_sidang, 2) }}
                            </div>
                        </div>
                        <span class="badge badge-selesai" style="font-size: 0.9rem; padding: 0.5rem 1rem;">LULUS MEJA HIJAU</span>
                    </div>
                    @if ($sidang->catatan)
                        <div style="font-size: 0.85rem; color: #065f46; margin-top: 0.75rem; border-top: 1px dashed #a7f3d0; padding-top: 0.5rem;">
                            <strong>Catatan Dewan Penguji:</strong> {{ $sidang->catatan }}
                        </div>
                    @endif
                </div>
            @endif

            <!-- Berkas Syarat Terunggah -->
            <div>
                <div style="font-size: 0.85rem; font-weight: 700; margin-bottom: 0.75rem; color: #334155;">Berkas Persyaratan Sidang:</div>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 0.75rem;">
                    @if ($sidang->file_naskah_sidang)
                        <a href="{{ route('dokumen.download', base64_encode($sidang->file_naskah_sidang)) }}" class="btn btn-secondary btn-sm" style="justify-content: flex-start;">
                            <span>📘</span> Naskah Skripsi Final (PDF)
                        </a>
                    @endif
                    @if ($sidang->file_acc_sidang)
                        <a href="{{ route('dokumen.download', base64_encode($sidang->file_acc_sidang)) }}" class="btn btn-secondary btn-sm" style="justify-content: flex-start;">
                            <span>✍️</span> Lembar ACC Sidang
                        </a>
                    @endif
                    @if ($sidang->file_bebas_revisi_seminar)
                        <a href="{{ route('dokumen.download', base64_encode($sidang->file_bebas_revisi_seminar)) }}" class="btn btn-secondary btn-sm" style="justify-content: flex-start;">
                            <span>📄</span> Bebas Revisi Seminar
                        </a>
                    @endif
                    @if ($sidang->file_bukti_bayar_sidang)
                        <a href="{{ route('dokumen.download', base64_encode($sidang->file_bukti_bayar_sidang)) }}" class="btn btn-secondary btn-sm" style="justify-content: flex-start;">
                            <span>💳</span> Bukti Bayar Sidang
                        </a>
                    @endif
                </div>
            </div>

            <!-- Dokumen Undangan & SK Sidang -->
            <div style="border-top: 1px solid var(--border); padding-top: 1.25rem;">
                <div style="font-size: 0.95rem; font-weight: 700; margin-bottom: 0.75rem;">Surat Undangan & SK Dewan Penguji Sidang</div>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 1rem;">
                    <div style="background: #fff; border: 1px solid var(--border); border-radius: 0.5rem; padding: 1rem;">
                        <span style="font-size: 0.72rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Surat Undangan Sidang</span>
                        <div style="font-weight: 700; font-size: 0.92rem; margin: 0.25rem 0 0.5rem;">
                            {{ $sidang->nomor_undangan_sidang ?? 'Belum diterbitkan' }}
                        </div>
                        @if ($sidang->file_undangan_sidang)
                            <a href="{{ route('dokumen.download', base64_encode($sidang->file_undangan_sidang)) }}" class="btn btn-secondary btn-sm" style="width: 100%;">
                                📥 Unduh Surat Undangan Sidang (PDF)
                            </a>
                        @endif
                    </div>

                    <div style="background: #fff; border: 1px solid var(--border); border-radius: 0.5rem; padding: 1rem;">
                        <span style="font-size: 0.72rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">SK Dewan Penguji Sidang</span>
                        <div style="font-weight: 700; font-size: 0.92rem; margin: 0.25rem 0 0.5rem;">
                            {{ $sidang->nomor_sk_sidang ?? 'Belum diterbitkan' }}
                        </div>
                        @if ($sidang->file_sk_sidang)
                            <a href="{{ route('dokumen.download', base64_encode($sidang->file_sk_sidang)) }}" class="btn btn-secondary btn-sm" style="width: 100%;">
                                📥 Unduh SK Dewan Penguji (PDF)
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif

@endsection
