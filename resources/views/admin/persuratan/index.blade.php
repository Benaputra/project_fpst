@extends('layouts.app')

@section('title', 'Persuratan Internal Fakultas - FPST UPB')
@section('page_title', 'Layanan Persuratan & Surat Internal Mahasiswa')

@section('content')
<div class="grid-stats" style="margin-bottom: 1.25rem;">
    <div class="stat-box">
        <div class="stat-box-title">Menunggu Verifikasi (Tendik/Admin)</div>
        <div class="stat-box-value">{{ $stats['diajukan'] }}</div>
    </div>
    <div class="stat-box">
        <div class="stat-box-title">Menunggu Persetujuan (Kaprodi/Pimpinan)</div>
        <div class="stat-box-value">{{ $stats['diverifikasi'] }}</div>
    </div>
    <div class="stat-box">
        <div class="stat-box-title">Siap Penomoran &amp; Terbit PDF</div>
        <div class="stat-box-value">{{ $stats['disetujui'] }}</div>
    </div>
    <div class="stat-box">
        <div class="stat-box-title">Surat Resmi Terbit</div>
        <div class="stat-box-value">{{ $stats['diterbitkan'] }}</div>
    </div>
</div>

<div class="card" style="padding: 1.25rem;">
    <form method="GET" action="{{ route('admin.persuratan.index') }}" style="display: flex; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 1.25rem; align-items: center;">
        <select name="status" class="form-control" style="width: 210px; height: 38px; font-size: 0.82rem;" onchange="this.form.submit()">
            <option value="">Semua Status</option>
            @foreach (\App\Enums\StatusPengajuanSurat::cases() as $statusCase)
                <option value="{{ $statusCase->value }}" {{ $statusFilter === $statusCase->value ? 'selected' : '' }}>
                    {{ $statusCase->label() }}
                </option>
            @endforeach
        </select>

        <select name="jenis" class="form-control" style="width: 220px; height: 38px; font-size: 0.82rem;" onchange="this.form.submit()">
            <option value="">Semua Jenis Surat</option>
            @foreach (\App\Enums\JenisSuratInternal::cases() as $jenisCase)
                <option value="{{ $jenisCase->value }}" {{ $jenisFilter === $jenisCase->value ? 'selected' : '' }}>
                    {{ $jenisCase->label() }}
                </option>
            @endforeach
        </select>

        @if (!$user->hasRole(\App\Enums\UserRole::AdminProdi, \App\Enums\UserRole::Kaprodi) || $user->hasRole(\App\Enums\UserRole::AdminUtama, \App\Enums\UserRole::PimpinanFakultas, \App\Enums\UserRole::Tendik))
            <select name="prodi_id" class="form-control" style="width: 185px; height: 38px; font-size: 0.82rem;" onchange="this.form.submit()">
                <option value="">Semua Prodi</option>
                @foreach ($daftarProdi as $prodi)
                    <option value="{{ $prodi->id }}" {{ (int) $prodiFilter === (int) $prodi->id ? 'selected' : '' }}>
                        {{ $prodi->nama }} ({{ $prodi->kode }})
                    </option>
                @endforeach
            </select>
        @endif

        <input type="text" name="search" value="{{ $search }}" placeholder="Cari nama, NIM, instansi, nomor..." class="form-control" style="width: 240px; height: 38px; font-size: 0.82rem;">
        <button type="submit" class="btn btn-secondary btn-sm" style="height: 38px;">Cari</button>
        @if ($statusFilter || $jenisFilter || $prodiFilter || $search)
            <a href="{{ route('admin.persuratan.index') }}" class="btn btn-secondary btn-sm" style="height: 38px;">Reset</a>
        @endif
    </form>

    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Pemohon (Mahasiswa)</th>
                    <th>Jenis &amp; Tujuan Surat</th>
                    <th>Status &amp; Disposisi</th>
                    <th style="text-align: center; width: 250px;">Aksi Disposisi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($daftarPengajuan as $item)
                    <tr>
                        <td>
                            <div style="font-weight: 700; color: #1c2b20;">{{ $item->mahasiswa?->name ?? '-' }}</div>
                            <div style="font-size: 0.78rem; color: var(--text-muted); font-family: monospace;">
                                NIM: {{ $item->mahasiswa?->nomor_induk ?: '-' }}
                            </div>
                            <span class="badge badge--primary" style="font-size: 0.7rem; margin-top: 0.25rem;">
                                {{ $item->programStudi?->nama }} ({{ $item->programStudi?->kode }})
                            </span>
                        </td>
                        <td style="font-size: 0.84rem;">
                            <div style="font-weight: 700;">{{ $item->jenis_surat->label() }}</div>
                            <div style="color: var(--text-muted); margin-top: 0.15rem;">{{ $item->perihal }}</div>
                            <div style="margin-top: 0.2rem;">Kepada: <strong>{{ $item->tujuan_instansi }}</strong></div>
                            @if ($item->nomor_surat_terbit)
                                <div style="font-family: monospace; font-size: 0.78rem; color: var(--primary-dark); margin-top: 0.2rem;">
                                    Nomor: {{ $item->nomor_surat_terbit }}
                                </div>
                            @endif
                            @if ($item->file_pendukung)
                                <div style="margin-top: 0.3rem;">
                                    <a href="{{ route('dokumen.download', base64_encode($item->file_pendukung)) }}" class="btn btn-secondary btn-sm" style="font-size: 0.73rem;">📎 Berkas Pendukung</a>
                                </div>
                            @endif
                        </td>
                        <td>
                            <span class="badge {{ $item->status->badgeClass() }}">{{ $item->status->label() }}</span>
                            @if ($item->verifikator)
                                <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.25rem;">
                                    Verifikator: {{ $item->verifikator->name }}
                                </div>
                            @endif
                            @if ($item->penyetuju)
                                <div style="font-size: 0.75rem; color: var(--text-muted);">
                                    Disetujui: {{ $item->penyetuju->name }}
                                </div>
                            @endif
                            @if ($item->catatan_penolakan)
                                <div style="font-size: 0.75rem; color: #991b1b; margin-top: 0.2rem;">
                                    Alasan: {{ $item->catatan_penolakan }}
                                </div>
                            @endif
                        </td>
                        <td>
                            <div style="display: flex; flex-direction: column; gap: 0.4rem;">
                                @if ($item->status === \App\Enums\StatusPengajuanSurat::Diajukan && $user->isStaffPersuratan())
                                    <form method="POST" action="{{ route('admin.persuratan.verifikasi', $item->id) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-primary btn-sm" style="width: 100%;">
                                            ✓ Verifikasi Berkas (Tendik/Admin)
                                        </button>
                                    </form>
                                @endif

                                @if (in_array($item->status, [\App\Enums\StatusPengajuanSurat::Diajukan, \App\Enums\StatusPengajuanSurat::Diverifikasi], true) && $user->canApproveSurat())
                                    <form method="POST" action="{{ route('admin.persuratan.setujui', $item->id) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-primary btn-sm" style="width: 100%; background: #1d4ed8; border-color: #1d4ed8;">
                                            ✅ Setujui Surat (Kaprodi/Pimpinan)
                                        </button>
                                    </form>
                                @endif

                                @if ($item->status === \App\Enums\StatusPengajuanSurat::Disetujui && $user->isStaffPersuratan())
                                    <button type="button" class="btn btn-primary btn-sm" style="width: 100%;" onclick="openTerbitkanModal({{ $item->id }}, '{{ addslashes($item->perihal) }}')">
                                        🏛️ Beri Nomor &amp; Terbitkan PDF
                                    </button>
                                @endif

                                @if ($item->file_surat_terbit)
                                    <a href="{{ route('dokumen.download', base64_encode($item->file_surat_terbit)) }}" class="btn btn-secondary btn-sm" style="width: 100%; justify-content: center;">
                                        📄 Unduh Surat Resmi
                                    </a>
                                @endif

                                @if (config('fpst.surat_internal.qr_verifikasi_enabled') && $item->verification_token)
                                    <a href="{{ route('surat.verifikasi', $item->verification_token) }}" target="_blank" class="btn btn-secondary btn-sm" style="width: 100%; justify-content: center;">
                                        🔍 Halaman Verifikasi QR
                                    </a>
                                @endif

                                @if (!in_array($item->status, [\App\Enums\StatusPengajuanSurat::Diterbitkan, \App\Enums\StatusPengajuanSurat::Ditolak], true))
                                    <button type="button" class="btn btn-sm" style="width: 100%; background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5;" onclick="openTolakModal({{ $item->id }}, '{{ addslashes($item->perihal) }}')">
                                        ✕ Tolak Permohonan
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" style="text-align: center; padding: 2.25rem; color: var(--text-muted);">
                            Belum ada permohonan surat internal pada filter ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top: 1rem;">
        {{ $daftarPengajuan->links() }}
    </div>
</div>

<script>
    function openTerbitkanModal(id, perihal) {
        document.getElementById('terbit-perihal').textContent = perihal;
        document.getElementById('form-terbitkan-surat').action = "{{ url('admin/persuratan') }}/" + id + "/terbitkan";
        openModal('modal-terbitkan-surat');
    }

    function openTolakModal(id, perihal) {
        document.getElementById('tolak-perihal').textContent = perihal;
        document.getElementById('form-tolak-surat').action = "{{ url('admin/persuratan') }}/" + id + "/tolak";
        openModal('modal-tolak-surat');
    }
</script>
@endsection

@push('modals')
<div id="modal-terbitkan-surat" class="modal-backdrop" style="display: none;">
    <div class="modal-dialog">
        <div class="modal-dialog-header">
            <h3 class="modal-dialog-title">🏛️ {{ config('fpst.surat_internal.qr_verifikasi_enabled') ? 'Terbitkan Surat Resmi Ber-QR Code' : 'Terbitkan Surat Resmi' }}</h3>
            <button type="button" onclick="closeModal('modal-terbitkan-surat')" class="modal-dialog-close">&times;</button>
        </div>
        <form id="form-terbitkan-surat" method="POST" action="">
            @csrf
            <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 1rem;">
                Perihal: <strong id="terbit-perihal" style="color: var(--text-dark);"></strong>
            </p>
            <div class="form-group">
                <label class="form-label">Nomor Surat (Kosongkan untuk Penomoran Otomatis Atomik)</label>
                <input type="text" name="nomor_surat_manual" class="form-control" placeholder="Otomatis: 001/PNL/AGT/FPST-UPB/XI/2026">
            </div>
            <div class="form-group">
                <label class="form-label">Pejabat Penanda Tangan (Opsional)</label>
                <select name="penanda_tangan_id" class="form-control">
                    <option value="">Otomatis (Penyetuju / Pimpinan / Kaprodi Prodi)</option>
                    @foreach ($daftarPejabat as $pejabat)
                        <option value="{{ $pejabat->id }}">
                            {{ $pejabat->name }} ({{ $pejabat->jabatan ?: $pejabat->role->label() }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="modal-dialog-footer">
                <button type="button" onclick="closeModal('modal-terbitkan-surat')" class="btn btn-secondary btn-sm">Batal</button>
                <button type="submit" class="btn btn-primary btn-sm">Generate PDF &amp; Terbitkan</button>
            </div>
        </form>
    </div>
</div>

<div id="modal-tolak-surat" class="modal-backdrop" style="display: none;">
    <div class="modal-dialog">
        <div class="modal-dialog-header">
            <h3 class="modal-dialog-title">✕ Tolak Pengajuan Surat</h3>
            <button type="button" onclick="closeModal('modal-tolak-surat')" class="modal-dialog-close">&times;</button>
        </div>
        <form id="form-tolak-surat" method="POST" action="">
            @csrf
            <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 0.85rem;">
                Perihal: <strong id="tolak-perihal" style="color: var(--text-dark);"></strong>
            </p>
            <div class="form-group">
                <label class="form-label">Alasan Penolakan / Perbaikan Berkas <span style="color: #dc2626;">*</span></label>
                <textarea name="catatan_penolakan" rows="3" required class="form-control" style="height: auto;" placeholder="Tuliskan catatan perbaikan agar mahasiswa dapat memperbaiki permohonan..."></textarea>
            </div>
            <div class="modal-dialog-footer">
                <button type="button" onclick="closeModal('modal-tolak-surat')" class="btn btn-secondary btn-sm">Batal</button>
                <button type="submit" class="btn btn-sm" style="background: #dc2626; color: #fff; border: 1px solid #dc2626;">Kirim Penolakan</button>
            </div>
        </form>
    </div>
</div>
@endpush
