@extends('layouts.app')

@section('title', 'Skripsi')

@section('content')
    @php
        $user = auth()->user();
        $kaprodi = $user->isKetuaProdi();
        $bolehAdministrasi = $kaprodi || $user->isAdminProdi() || $user->isAdminUtama();
    @endphp
    <div class="eyebrow">Proses akademik</div>
    <h1>Skripsi</h1>
    <p class="lead">Kelola kesediaan pembimbing, verifikasi dokumen, finalisasi, dan SK sesuai hak akses.</p>
    @if (session('status'))<div class="notice notice--success" role="status" style="margin-top: 1.5rem;">{{ session('status') }}</div>@endif
    @if ($errors->any())<div class="notice notice--danger" style="margin-top: 1.5rem;">{{ $errors->first() }}</div>@endif

    <div class="grid"><section class="card">
        @if ($skripsi->isEmpty())
            <div class="empty-state">Belum ada data skripsi yang dapat ditampilkan.</div>
        @else
            <div class="workflow-list">
                @foreach ($skripsi as $item)
                    @php
                        $dosen = $dosenPerProdi->get($item->mahasiswa->program_studi_id, collect());
                        $terakhirPerPeran = $item->kesediaanBimbingan
                            ->groupBy(fn ($kesediaan) => $kesediaan->peran->value)
                            ->map(fn ($riwayat) => $riwayat->sortByDesc('siklus')->first());
                        $siapFinalisasi = $terakhirPerPeran->isNotEmpty()
                            && $terakhirPerPeran->contains(fn ($k) => $k->peran === \App\Enums\PeranKesediaanBimbingan::Pembimbing1)
                            && $terakhirPerPeran->every(fn ($k) => $k->status === \App\Enums\StatusKesediaanBimbingan::Diterima);
                    @endphp
                    <article class="workflow-card">
                        <div class="workflow-card__header"><div><h3>{{ $item->mahasiswa->nama }}</h3><p class="field-help">{{ $item->nim }} · {{ $item->mahasiswa->programStudi->nama }}</p></div><span class="badge badge--waiting status-text">{{ str_replace('_', ' ', $item->status->value) }}</span></div>
                        <p class="title-value" style="font-size: .95rem; margin-top: 1rem;">{{ $item->judul }}</p>
                        <dl class="meta" style="margin-top: 1rem;"><div><dt>Pembimbing 1</dt><dd>{{ $item->pembimbing1?->nama ?? 'Belum final' }}</dd></div><div><dt>Pembimbing 2</dt><dd>{{ $item->pembimbing2?->nama ?? '—' }}</dd></div><div><dt>Progres</dt><dd>Seminar: {{ str_replace('_', ' ', $item->seminar?->status->value ?? 'belum diajukan') }}<br>Sidang: {{ str_replace('_', ' ', $item->sidangSkripsi?->status->value ?? 'belum diajukan') }}</dd></div></dl>

                        @if ($item->kesediaanBimbingan->isNotEmpty())
                            <details open><summary>Proses kesediaan pembimbing</summary>
                                <div class="workflow-list" style="margin-top: .8rem;">
                                    @foreach ($item->kesediaanBimbingan->sortBy([['peran.value', 'asc'], ['siklus', 'asc']]) as $kesediaan)
                                        @php
                                            $dokumen = $kesediaan->dokumenPengajuan->sortByDesc('versi')->first();
                                            $suratAktif = $kesediaan->surat->whereIn('status', [\App\Enums\StatusSurat::Diterbitkan, \App\Enums\StatusSurat::Terverifikasi])->sortByDesc('versi')->first();
                                            $kesediaanTerakhir = $terakhirPerPeran->get($kesediaan->peran->value)?->is($kesediaan);
                                        @endphp
                                        <div class="workflow-card" style="background: #f8fafc;">
                                            <div class="workflow-card__header"><div><strong>{{ $kesediaan->peran === \App\Enums\PeranKesediaanBimbingan::Pembimbing1 ? 'Pembimbing 1' : 'Pembimbing 2' }} · Siklus {{ $kesediaan->siklus }}</strong><p class="field-help">{{ $kesediaan->dosen->nama }} · {{ $kesediaan->dosen_id }}</p></div><span class="badge badge--waiting status-text">{{ str_replace('_', ' ', $kesediaan->status->value) }}</span></div>
                                            <div class="compact-actions">
                                                @if ($suratAktif)<a class="table-link" href="{{ route('surat.download', $suratAktif) }}">Unduh surat kesediaan</a>@endif
                                                @if ($dokumen)<a class="table-link" href="{{ route('dokumen-pengajuan.download', $dokumen) }}">Unduh hasil konsultasi v{{ $dokumen->versi }}</a>@endif
                                            </div>

                                            @if ($bolehAdministrasi && $kesediaan->status === \App\Enums\StatusKesediaanBimbingan::Ditunjuk)
                                                <form method="POST" action="{{ route('kesediaan-bimbingan.surat.store', $kesediaan) }}" class="compact-actions">@csrf<button class="button button--primary button--compact" type="submit">Terbitkan surat kesediaan</button></form>
                                            @endif

                                            @if ($bolehAdministrasi && $kesediaan->status === \App\Enums\StatusKesediaanBimbingan::MenungguVerifikasi && $dokumen)
                                                <details><summary>Verifikasi hasil konsultasi</summary>
                                                    <form method="POST" action="{{ route('dokumen-pengajuan.verifikasi-hasil-konsultasi.store', $dokumen) }}">@csrf
                                                        <div class="inline-fields"><div class="field"><label>Keputusan</label><select name="keputusan" required><option value="">Pilih keputusan</option><option value="valid_bersedia">Valid dan bersedia</option><option value="valid_tidak_bersedia">Valid, tidak bersedia</option><option value="upload_tidak_valid">Upload tidak valid</option></select></div><div class="field"><label>Catatan verifikasi</label><textarea name="catatan_verifikasi" maxlength="2000" style="min-height: 5rem;"></textarea></div></div>
                                                        <button class="button button--primary" type="submit">Simpan keputusan</button>
                                                    </form>
                                                </details>
                                            @endif

                                            @if ($kaprodi && $kesediaanTerakhir && $kesediaan->status === \App\Enums\StatusKesediaanBimbingan::Ditolak)
                                                <details open><summary>Ganti calon pembimbing</summary>
                                                    <form method="POST" action="{{ route('kesediaan-bimbingan.calon-pengganti.store', $kesediaan) }}">@csrf
                                                        <div class="field"><label>Calon pengganti</label><select name="calon_pengganti_id" required><option value="">Pilih dosen</option>@foreach($dosen->whereNotIn('nidn', $item->kesediaanBimbingan->pluck('dosen_id')) as $orang)<option value="{{ $orang->nidn }}">{{ $orang->nama }} · {{ $orang->nidn }}</option>@endforeach</select></div>
                                                        <button class="button button--primary" type="submit">Tetapkan pengganti</button>
                                                    </form>
                                                </details>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </details>
                        @endif

                        <div class="compact-actions">
                            @if ($kaprodi && $item->status === \App\Enums\StatusSkripsi::MenungguKesediaanPembimbing && $siapFinalisasi)
                                <form method="POST" action="{{ route('skripsi.finalisasi-pembimbing.store', $item) }}">@csrf<button class="button button--success button--compact" type="submit">Finalisasi pembimbing</button></form>
                            @endif
                            @if ($bolehAdministrasi && $item->status === \App\Enums\StatusSkripsi::BimbinganAktif)
                                <form method="POST" action="{{ route('skripsi.sk-bimbingan.store', $item) }}">@csrf<button class="button button--secondary button--compact" type="submit">Terbitkan SK bimbingan</button></form>
                            @endif
                            @foreach ($item->surat->where('jenis_surat', \App\Enums\JenisSurat::SkBimbingan)->whereIn('status', [\App\Enums\StatusSurat::Diterbitkan, \App\Enums\StatusSurat::Terverifikasi]) as $sk)
                                <a class="table-link" href="{{ route('surat.download', $sk) }}">Unduh SK v{{ $sk->versi }}</a>
                            @endforeach
                        </div>
                    </article>
                @endforeach
            </div>
            <x-portal-pagination :paginator="$skripsi" />
        @endif
    </section></div>
@endsection
