@extends('layouts.app')

@section('title', 'Kelola Praktik Lapang (PL) - FPST UPB')
@section('page_title', 'Pengelolaan Praktik Lapang (PL) & Katalog Mitra')

@section('content')
@if ($isPengelolaProdi)
    <!-- Katalog & Verifikasi Tempat PL -->
    <div class="card" style="padding: 1.25rem; margin-bottom: 1.5rem;">
        <div class="card-header" style="margin-bottom: 1rem;">
            <h2 class="card-title">Katalog &amp; Verifikasi Tempat Praktik Lapang (PL)</h2>
        </div>

        <form method="POST" action="{{ route('kelola.pl.lokasi.store') }}" style="background: #f8faf8; border: 1px solid var(--border); border-radius: 0.65rem; padding: 0.9rem; margin-bottom: 1rem;">
            @csrf
            <div style="font-size: 0.82rem; font-weight: 700; margin-bottom: 0.6rem;">+ Tambah Mitra Tempat PL Baru</div>
            <div style="display: grid; grid-template-columns: 1.5fr 110px 1.5fr 110px; gap: 0.6rem;">
                <input type="text" name="nama_instansi" class="form-control" placeholder="Nama Instansi / Perkebunan / Balai *" required>
                <select name="sektor" class="form-control">
                    <option value="AGT">AGT</option>
                    <option value="AGB">AGB</option>
                    <option value="AGT/AGB" selected>AGT/AGB</option>
                </select>
                <input type="text" name="alamat" class="form-control" placeholder="Alamat Instansi *" required>
                <input type="number" name="kuota_periode" class="form-control" min="1" max="100" value="10" required>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr auto; gap: 0.6rem; margin-top: 0.6rem;">
                <input type="text" name="kabupaten_kota" class="form-control" placeholder="Kabupaten / Kota">
                <input type="text" name="kontak_pic" class="form-control" placeholder="Nama PIC Lapang">
                <input type="text" name="telepon_pic" class="form-control" placeholder="No. Telepon PIC">
                <button type="submit" class="btn btn-primary btn-sm">Simpan Mitra</button>
            </div>
        </form>

        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Instansi &amp; Sektor</th>
                        <th>Alamat &amp; Pengusul</th>
                        <th style="text-align: center;">Kuota</th>
                        <th style="text-align: center;">Status</th>
                        <th style="text-align: center; width: 290px;">Verifikasi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($daftarLokasi as $lok)
                        <tr>
                            <td>
                                <strong>{{ $lok->nama_instansi }}</strong>
                                <div><span class="badge badge-diproses">{{ $lok->sektor }}</span></div>
                            </td>
                            <td style="font-size: 0.8rem;">
                                {{ $lok->alamat }}
                                @if ($lok->pengusul)
                                    <div style="color: var(--text-muted);">Diusulkan: {{ $lok->pengusul->name }}</div>
                                @endif
                            </td>
                            <td style="text-align: center;">{{ $lok->kuota_periode }}</td>
                            <td style="text-align: center;">
                                <span class="badge {{ $lok->status_verifikasi->badgeClass() }}">{{ $lok->status_verifikasi->label() }}</span>
                            </td>
                            <td>
                                <form method="POST" action="{{ route('kelola.pl.lokasi.verifikasi', $lok) }}" style="display: flex; gap: 0.35rem; justify-content: center; flex-wrap: wrap;">
                                    @csrf
                                    <select name="status_verifikasi" class="form-control" style="max-width: 150px; padding: 0.25rem 0.45rem; font-size: 0.78rem;">
                                        <option value="terverifikasi" {{ $lok->status_verifikasi->value === 'terverifikasi' ? 'selected' : '' }}>Terverifikasi</option>
                                        <option value="ditolak" {{ $lok->status_verifikasi->value === 'ditolak' ? 'selected' : '' }}>Tolak</option>
                                    </select>
                                    <input type="number" name="kuota_periode" min="1" max="100" value="{{ $lok->kuota_periode }}" class="form-control" style="max-width: 65px; padding: 0.25rem 0.4rem; font-size: 0.78rem;">
                                    <button type="submit" class="btn btn-secondary btn-sm" style="font-size: 0.75rem;">Simpan</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" style="text-align: center; color: var(--text-muted);">Belum ada data katalog lokasi PL.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endif

<!-- Daftar Mahasiswa Praktik Lapang -->
<div class="card" style="padding: 1.25rem;">
    <div class="card-header" style="margin-bottom: 1rem;">
        <h2 class="card-title">Daftar Pengajuan &amp; Pelaksanaan Praktik Lapang</h2>
    </div>

    @forelse ($daftarPl as $pl)
        <div style="border: 1px solid var(--border); border-radius: 0.65rem; padding: 1rem; margin-bottom: 1rem; background: #fff;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 0.75rem; flex-wrap: wrap;">
                <div>
                    <div style="font-size: 0.76rem; color: var(--text-muted); font-weight: 700;">
                        {{ $pl->mahasiswa?->nomor_induk }} • {{ $pl->mahasiswa?->name }} ({{ $pl->programStudi?->kode }}) • {{ $pl->periode_akademik }}
                    </div>
                    <h3 style="font-size: 1rem; font-weight: 700; margin-top: 0.15rem;">{{ $pl->judul_kegiatan }}</h3>
                    <div style="font-size: 0.82rem; color: #334155; margin-top: 0.2rem;">
                        📍 {{ $pl->lokasiPl?->nama_instansi }} &bull;
                        👨‍🏫 DPL: <strong>{{ $pl->dpl?->name ?? 'Belum ditetapkan' }}</strong>
                        @if ($pl->nomor_sk_dpl) &bull; SK DPL: <code>{{ $pl->nomor_sk_dpl }}</code> @endif
                    </div>
                </div>
                <span class="badge {{ $pl->status->badgeClass() }}">{{ $pl->status->label() }}</span>
            </div>

            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; margin: 0.65rem 0;">
                @if ($pl->file_surat_balasan)
                    <a href="{{ route('dokumen.download', base64_encode($pl->file_surat_balasan)) }}" class="btn btn-secondary btn-sm">📄 Surat Balasan</a>
                @endif
                @if ($pl->file_laporan_akhir)
                    <a href="{{ route('dokumen.download', base64_encode($pl->file_laporan_akhir)) }}" class="btn btn-secondary btn-sm">📘 Laporan Akhir</a>
                @endif
                @if ($pl->file_sertifikat)
                    <a href="{{ route('dokumen.download', base64_encode($pl->file_sertifikat)) }}" class="btn btn-secondary btn-sm">🏆 Sertifikat PL</a>
                @endif
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 0.85rem;">
                @if ($user->hasRole(\App\Enums\UserRole::Kaprodi, \App\Enums\UserRole::AdminUtama))
                    <form method="POST" action="{{ route('kelola.pl.dpl', $pl) }}" style="border: 1px solid var(--border); border-radius: 0.5rem; padding: 0.75rem;">
                        @csrf
                        <div style="font-size: 0.76rem; font-weight: 700; margin-bottom: 0.4rem;">Penetapan DPL</div>
                        <select name="dpl_id" class="form-control" required style="margin-bottom: 0.45rem;">
                            <option value="">-- Pilih Dosen --</option>
                            @foreach ($daftarDosen as $dsn)
                                <option value="{{ $dsn->id }}" {{ (int) $pl->dpl_id === (int) $dsn->id ? 'selected' : '' }}>{{ $dsn->name }}</option>
                            @endforeach
                        </select>
                        @if ($user->isAdminUtama())
                            <label style="display: inline-flex; align-items: center; gap: 0.35rem; font-size: 0.75rem; margin-bottom: 0.45rem;">
                                <input type="checkbox" name="is_mandat" value="1"> Mandat Langsung Admin Utama
                            </label>
                        @endif
                        <button type="submit" class="btn btn-primary btn-sm" style="width: 100%;">Tetapkan DPL</button>
                    </form>
                @endif

                @if ($user->hasRole(\App\Enums\UserRole::AdminProdi, \App\Enums\UserRole::AdminUtama))
                    <form method="POST" action="{{ route('kelola.pl.sk-dpl', $pl) }}" enctype="multipart/form-data" style="border: 1px solid var(--border); border-radius: 0.5rem; padding: 0.75rem;">
                        @csrf
                        <div style="font-size: 0.76rem; font-weight: 700; margin-bottom: 0.4rem;">Penerbitan SK DPL</div>
                        <input type="text" name="nomor_sk_dpl" class="form-control" value="{{ $pl->nomor_sk_dpl }}" placeholder="SK-DPL/01/FPST/2026" required style="margin-bottom: 0.4rem;">
                        <input type="file" name="file_sk_dpl" class="form-control" accept=".pdf" style="margin-bottom: 0.45rem;">
                        <button type="submit" class="btn btn-secondary btn-sm" style="width: 100%;">Terbitkan SK DPL</button>
                    </form>
                @endif

                @if ($user->hasRole(\App\Enums\UserRole::AdminProdi, \App\Enums\UserRole::Kaprodi, \App\Enums\UserRole::AdminUtama))
                    <form method="POST" action="{{ route('kelola.pl.seminar', $pl) }}" style="border: 1px solid var(--border); border-radius: 0.5rem; padding: 0.75rem;">
                        @csrf
                        <div style="font-size: 0.76rem; font-weight: 700; margin-bottom: 0.4rem;">Jadwalkan Seminar PL</div>
                        <input type="date" name="tgl_seminar_pl" class="form-control" value="{{ $pl->tgl_seminar_pl?->format('Y-m-d') }}" required style="margin-bottom: 0.35rem;">
                        <input type="text" name="jam_seminar_pl" class="form-control" value="{{ $pl->jam_seminar_pl ?? '09:00 - 10:30' }}" required style="margin-bottom: 0.35rem;">
                        <input type="text" name="ruangan_seminar_pl" class="form-control" value="{{ $pl->ruangan_seminar_pl ?? 'Ruang Seminar FPST' }}" required style="margin-bottom: 0.45rem;">
                        <button type="submit" class="btn btn-secondary btn-sm" style="width: 100%;">Simpan Jadwal Seminar PL</button>
                    </form>
                @endif

                @if ($user->hasRole(\App\Enums\UserRole::AdminProdi, \App\Enums\UserRole::Kaprodi, \App\Enums\UserRole::AdminUtama) || (int) $pl->dpl_id === (int) $user->id)
                    <form method="POST" action="{{ route('kelola.pl.penilaian', $pl) }}" enctype="multipart/form-data" style="border: 1px solid var(--border); border-radius: 0.5rem; padding: 0.75rem;">
                        @csrf
                        <div style="font-size: 0.76rem; font-weight: 700; margin-bottom: 0.4rem;">Penilaian &amp; Sertifikat PL</div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.4rem; margin-bottom: 0.35rem;">
                            <input type="number" step="0.01" min="0" max="100" name="nilai_pembimbing_lapang" value="{{ $pl->nilai_pembimbing_lapang }}" class="form-control" placeholder="Nilai Lapang" required>
                            <input type="number" step="0.01" min="0" max="100" name="nilai_dpl" value="{{ $pl->nilai_dpl }}" class="form-control" placeholder="Nilai DPL" required>
                        </div>
                        <input type="file" name="file_sertifikat" class="form-control" accept=".pdf,.jpg,.jpeg,.png" style="margin-bottom: 0.45rem;">
                        <button type="submit" class="btn btn-success btn-sm" style="width: 100%;">Simpan Nilai &amp; Selesaikan PL</button>
                    </form>
                @endif
            </div>

            @if ($pl->logbooks->isNotEmpty() && ($user->isAdminUtama() || (int) $pl->dpl_id === (int) $user->id))
                <div style="margin-top: 0.85rem; padding-top: 0.75rem; border-top: 1px dashed var(--border);">
                    <div style="font-size: 0.8rem; font-weight: 700; margin-bottom: 0.45rem;">Verifikasi Logbook Mahasiswa ({{ $pl->logbooks->count() }}):</div>
                    @foreach ($pl->logbooks as $log)
                        <div style="display: flex; justify-content: space-between; align-items: center; gap: 0.6rem; padding: 0.35rem 0; border-bottom: 1px solid #f1f5f9; flex-wrap: wrap;">
                            <div style="font-size: 0.8rem;">
                                <strong>{{ $log->tanggal->format('d/m/Y') }}</strong> — {{ $log->aktivitas }}
                            </div>
                            @if ($log->diverifikasi_dpl)
                                <span class="badge badge-selesai">✓ Terverifikasi DPL</span>
                            @else
                                <form method="POST" action="{{ route('kelola.pl.logbook.verifikasi', $log) }}" style="display: inline-flex; gap: 0.35rem;">
                                    @csrf
                                    <input type="text" name="catatan_dpl" class="form-control" placeholder="Catatan DPL (opsional)" style="padding: 0.2rem 0.45rem; font-size: 0.76rem;">
                                    <button type="submit" class="btn btn-primary btn-sm" style="font-size: 0.74rem;">✓ Verifikasi</button>
                                </form>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @empty
        <div style="text-align: center; color: var(--text-muted); padding: 2rem;">
            Belum ada data pengajuan Praktik Lapang (PL).
        </div>
    @endforelse

    <div style="margin-top: 1rem;">
        {{ $daftarPl->links() }}
    </div>
</div>
@endsection
