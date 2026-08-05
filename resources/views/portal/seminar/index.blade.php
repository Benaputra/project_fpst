@extends('layouts.app')

@section('title', 'Seminar')

@section('content')
    @php $bolehKelola = auth()->user()->isAdminUtama() || auth()->user()->isAdminProdi() || auth()->user()->isKetuaProdi(); @endphp
    <div class="eyebrow">Agenda akademik</div>
    <h1>Seminar Skripsi</h1>
    <p class="lead">Ajukan, verifikasi, jadwalkan, dan terbitkan surat seminar sesuai hak akses.</p>

    @if (session('status'))<div class="notice notice--success" role="status" style="margin-top: 1.5rem;">{{ session('status') }}</div>@endif
    @if ($errors->any())<div class="notice notice--danger" style="margin-top: 1.5rem;">{{ $errors->first() }}</div>@endif

    @if (auth()->user()->isMahasiswa() && $skripsiMahasiswa)
        @php $dapatMengajukan = $skripsiMahasiswa->status === \App\Enums\StatusSkripsi::SiapSeminar && (! $skripsiMahasiswa->seminar || $skripsiMahasiswa->seminar->status === \App\Enums\StatusSeminar::Ditolak); @endphp
        @if ($dapatMengajukan)
            <div class="grid"><section class="card"><h2>Ajukan seminar</h2><p class="field-help">Berkas bersifat opsional dan disimpan privat. Ukuran maksimal 5 MB.</p>
                <form method="POST" enctype="multipart/form-data" action="{{ route('skripsi.seminar.store', $skripsiMahasiswa) }}">@csrf
                    <div class="field"><label for="berkas_seminar">Berkas seminar</label><input id="berkas_seminar" type="file" name="berkas_seminar" accept=".pdf,.jpg,.jpeg,.png"></div>
                    <button class="button button--primary" type="submit">Kirim pengajuan seminar</button>
                </form>
            </section></div>
        @endif
    @endif

    <div class="section-heading"><h2>Daftar seminar</h2><span class="field-help">{{ $seminar->total() }} data</span></div>
    <section class="card">
        @if ($seminar->isEmpty())
            <div class="empty-state">Belum ada data seminar yang dapat ditampilkan.</div>
        @else
            <div class="workflow-list">
                @foreach ($seminar as $item)
                    @php
                        $prodiId = $item->skripsi->mahasiswa->program_studi_id;
                        $dosen = $dosenPerProdi->get($prodiId, collect());
                        $dokumen = $item->dokumenPengajuan->sortByDesc('versi')->first();
                    @endphp
                    <article class="workflow-card">
                        <div class="workflow-card__header"><div><h3>{{ $item->skripsi->mahasiswa->nama }}</h3><p class="field-help">{{ $item->skripsi->nim }} · {{ $item->skripsi->judul }}</p></div><span class="badge badge--waiting status-text">{{ str_replace('_', ' ', $item->status->value) }}</span></div>
                        <dl class="meta" style="margin-top: 1rem;"><div><dt>Program studi</dt><dd>{{ $item->skripsi->mahasiswa->programStudi->nama }}</dd></div><div><dt>Jadwal</dt><dd>{{ $item->tanggal?->format('d/m/Y H:i') ?? 'Belum dijadwalkan' }}</dd></div><div><dt>Tempat</dt><dd>{{ $item->tempat ?? '—' }}</dd></div></dl>
                        <div class="compact-actions">
                            @if ($dokumen)<a class="table-link" href="{{ route('dokumen-pengajuan.download', $dokumen) }}">Unduh berkas v{{ $dokumen->versi }}</a>@endif
                            @foreach ($item->surat->whereIn('status', [\App\Enums\StatusSurat::Diterbitkan, \App\Enums\StatusSurat::Terverifikasi]) as $suratItem)<a class="table-link" href="{{ route('surat.download', $suratItem) }}">Unduh {{ str_replace('_', ' ', $suratItem->jenis_surat->value) }}</a>@endforeach
                        </div>

                        @if ($bolehKelola && $item->status === \App\Enums\StatusSeminar::Diajukan)
                            <details><summary>Verifikasi pengajuan seminar</summary>
                                <form method="POST" action="{{ route('seminar.verifikasi.store', $item) }}">@csrf
                                    <div class="inline-fields"><div class="field"><label>Keputusan</label><select name="keputusan" required><option value="">Pilih keputusan</option><option value="terima">Terima</option><option value="tolak">Tolak</option></select></div><div class="field"><label>Catatan penolakan</label><textarea name="catatan_reject" maxlength="2000" style="min-height: 5rem;"></textarea></div></div>
                                    <button class="button button--primary" type="submit">Simpan verifikasi</button>
                                </form>
                            </details>
                        @elseif ($bolehKelola && $item->status === \App\Enums\StatusSeminar::Diverifikasi)
                            <details open><summary>Jadwalkan seminar</summary>
                                <form method="POST" action="{{ route('seminar.jadwal.store', $item) }}">@csrf
                                    <div class="inline-fields--four inline-fields"><div class="field"><label>Penguji 1</label><select name="penguji1_id" required><option value="">Pilih dosen</option>@foreach($dosen as $orang)<option value="{{ $orang->nidn }}">{{ $orang->nama }}</option>@endforeach</select></div><div class="field"><label>Penguji 2</label><select name="penguji2_id" required><option value="">Pilih dosen</option>@foreach($dosen as $orang)<option value="{{ $orang->nidn }}">{{ $orang->nama }}</option>@endforeach</select></div><div class="field"><label>Tanggal dan waktu</label><input type="datetime-local" name="tanggal" required></div><div class="field"><label>Tempat</label><input name="tempat" maxlength="255" required></div></div>
                                    <button class="button button--primary" type="submit">Simpan jadwal</button>
                                </form>
                            </details>
                        @elseif ($bolehKelola && $item->status === \App\Enums\StatusSeminar::Dijadwalkan)
                            <details><summary>Terbitkan surat seminar</summary><div class="compact-actions">
                                @foreach ([[\App\Enums\JenisSurat::UndanganSeminar, 'Terbitkan undangan'], [\App\Enums\JenisSurat::SuratTugasSeminar, 'Terbitkan surat tugas']] as [$jenis, $label])
                                    <form method="POST" action="{{ route('seminar.surat.store', $item) }}">@csrf<input type="hidden" name="jenis_surat" value="{{ $jenis->value }}"><button class="button button--secondary button--compact" type="submit">{{ $label }}</button></form>
                                @endforeach
                            </div></details>
                        @endif
                    </article>
                @endforeach
            </div>
            <x-portal-pagination :paginator="$seminar" />
        @endif
    </section>
@endsection
