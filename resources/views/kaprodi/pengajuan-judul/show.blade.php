@extends('layouts.app')

@section('title', 'Detail Pengajuan Judul')

@section('content')
    <a class="back-link" href="{{ route('kaprodi.pengajuan-judul.index') }}">&larr; Kembali ke daftar</a>
    <div class="eyebrow">Ketua Program Studi</div>
    <h1>Detail Pengajuan Judul</h1>
    <p class="lead">Pastikan identitas dan substansi judul telah diperiksa sebelum membuat keputusan.</p>

    <div class="grid">
        @if (session('status'))
            <div class="notice notice--success" role="status">{{ session('status') }}</div>
        @endif

        <section class="card" aria-labelledby="identity-heading">
            <div class="card__header">
                <h2 id="identity-heading">Identitas mahasiswa</h2>
                <span class="badge {{ match ($pengajuan->status) {
                    \App\Enums\StatusPengajuanJudul::Diajukan => 'badge--waiting',
                    \App\Enums\StatusPengajuanJudul::Diverifikasi => 'badge--success',
                    \App\Enums\StatusPengajuanJudul::Ditolak => 'badge--danger',
                } }}">{{ $pengajuan->status->label() }}</span>
            </div>
            <dl class="meta">
                <div><dt>Nama</dt><dd>{{ $pengajuan->mahasiswa->nama }}</dd></div>
                <div><dt>NIM</dt><dd>{{ $pengajuan->mahasiswa->nim }}</dd></div>
                <div><dt>Angkatan</dt><dd>{{ $pengajuan->mahasiswa->angkatan }}</dd></div>
                <div><dt>Program studi</dt><dd>{{ $pengajuan->mahasiswa->programStudi->nama }}</dd></div>
                <div><dt>Pembimbing akademik</dt><dd>{{ $pengajuan->mahasiswa->pembimbingAkademik->nama }}</dd></div>
                <div><dt>Tanggal pengajuan</dt><dd>{{ $pengajuan->created_at->format('d/m/Y H:i') }}</dd></div>
            </dl>
        </section>

        <section class="card" aria-labelledby="title-heading">
            <div class="card__header"><h2 id="title-heading">Judul yang diajukan</h2></div>
            <p class="title-value">{{ $pengajuan->judul }}</p>
            @if ($pengajuan->catatan_reject)
                <div class="decision"><strong>Catatan penolakan</strong>{{ $pengajuan->catatan_reject }}</div>
            @endif

            @if ($pengajuan->status === \App\Enums\StatusPengajuanJudul::Diajukan)
                <div class="actions">
                    <button class="button button--success button--compact" type="button" data-open-dialog="dialog-terima">Terima judul</button>
                    <button class="button button--danger button--compact" type="button" data-open-dialog="dialog-tolak">Tolak judul</button>
                </div>
            @endif
        </section>

        @if ($pengajuan->status === \App\Enums\StatusPengajuanJudul::Diverifikasi && $pengajuan->skripsi === null)
            <section class="card" aria-labelledby="candidate-heading">
                <div class="card__header">
                    <div>
                        <h2 id="candidate-heading">Tetapkan calon pembimbing</h2>
                        <p class="field-help">P1 wajib. P2 opsional. Calon belum menjadi pembimbing final sebelum proses kesediaan selesai.</p>
                    </div>
                </div>

                <div class="search-row">
                    <div class="field">
                        <label for="pencarian-calon">Cari dosen program studi</label>
                        <input id="pencarian-calon" type="search" maxlength="100" autocomplete="off"
                            placeholder="Nama, NIDN, atau NUPTK"
                            data-search-url="{{ route('pengajuan-judul.calon-pembimbing.search', $pengajuan) }}">
                    </div>
                    <div id="status-pencarian-calon" class="search-status" role="status" aria-live="polite">Menampilkan maksimal 20 dosen.</div>
                </div>

                <form id="form-calon-pembimbing" method="POST"
                    action="{{ route('pengajuan-judul.calon-pembimbing.store', $pengajuan) }}">
                    @csrf
                    <div class="form-grid">
                        <div class="field">
                            <label for="pembimbing1_id">Calon Pembimbing 1</label>
                            <select id="pembimbing1_id" name="pembimbing1_id" required>
                                <option value="">Pilih dosen</option>
                                @foreach ($calonPembimbing as $dosen)
                                    <option value="{{ $dosen->nidn }}" @selected(old('pembimbing1_id') === $dosen->nidn)>
                                        {{ $dosen->nama }} — {{ $dosen->nidn }}
                                    </option>
                                @endforeach
                            </select>
                            @error('pembimbing1_id')
                                <p class="field-error" role="alert">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="field">
                            <label for="pembimbing2_id">Calon Pembimbing 2 <span class="field-help">(opsional)</span></label>
                            <select id="pembimbing2_id" name="pembimbing2_id">
                                <option value="">Tanpa Pembimbing 2</option>
                                @foreach ($calonPembimbing as $dosen)
                                    <option value="{{ $dosen->nidn }}" @selected(old('pembimbing2_id') === $dosen->nidn)>
                                        {{ $dosen->nama }} — {{ $dosen->nidn }}
                                    </option>
                                @endforeach
                            </select>
                            @error('pembimbing2_id')
                                <p class="field-error" role="alert">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                    @error('pengajuan')
                        <p class="field-error" role="alert">{{ $message }}</p>
                    @enderror
                    <button class="button button--primary" type="submit">Tinjau dan tetapkan</button>
                </form>
            </section>
        @elseif ($pengajuan->skripsi !== null)
            <section class="card" aria-labelledby="candidate-result-heading">
                <div class="card__header">
                    <div>
                        <h2 id="candidate-result-heading">Calon pembimbing telah ditetapkan</h2>
                        <p class="field-help">Status proses: {{ str_replace('_', ' ', $pengajuan->skripsi->status->value) }}.</p>
                    </div>
                </div>
                <dl class="meta">
                    @foreach ($pengajuan->skripsi->kesediaanBimbingan as $kesediaan)
                        <div>
                            <dt>{{ $kesediaan->peran === \App\Enums\PeranKesediaanBimbingan::Pembimbing1 ? 'Calon Pembimbing 1' : 'Calon Pembimbing 2' }}</dt>
                            <dd>{{ $kesediaan->dosen->nama }} — {{ $kesediaan->dosen->nidn }}</dd>
                        </div>
                    @endforeach
                </dl>
            </section>
        @endif
    </div>

    @if ($pengajuan->status === \App\Enums\StatusPengajuanJudul::Diajukan)
        <dialog id="dialog-terima">
            <div class="dialog__body">
                <h2>Terima judul ini?</h2>
                <p class="lead">Keputusan akan dicatat atas nama Anda dan tidak dapat ditimpa.</p>
                <form method="POST" action="{{ route('pengajuan-judul.terima', $pengajuan) }}">
                    @csrf
                    <div class="dialog__actions">
                        <button class="button button--secondary" type="button" data-close-dialog>Batal</button>
                        <button class="button button--success" type="submit">Ya, terima</button>
                    </div>
                </form>
            </div>
        </dialog>
        <dialog id="dialog-tolak">
            <div class="dialog__body">
                <h2>Tolak judul ini?</h2>
                <p class="lead">Berikan alasan yang jelas agar mahasiswa dapat melakukan perbaikan.</p>
                <form method="POST" action="{{ route('pengajuan-judul.tolak', $pengajuan) }}">
                    @csrf
                    <div class="field">
                        <label for="alasan">Alasan penolakan</label>
                        <textarea id="alasan" name="alasan" maxlength="2000" required>{{ old('alasan') }}</textarea>
                        @error('alasan')
                            <p class="field-error" role="alert">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="dialog__actions">
                        <button class="button button--secondary" type="button" data-close-dialog>Batal</button>
                        <button class="button button--danger" type="submit">Ya, tolak</button>
                    </div>
                </form>
            </div>
        </dialog>
        <script>
            document.querySelectorAll('[data-open-dialog]').forEach((button) => {
                button.addEventListener('click', () => document.getElementById(button.dataset.openDialog).showModal());
            });
            document.querySelectorAll('[data-close-dialog]').forEach((button) => {
                button.addEventListener('click', () => button.closest('dialog').close());
            });
            @if ($errors->has('alasan'))
                document.getElementById('dialog-tolak').showModal();
            @endif
        </script>
    @endif

    @if ($pengajuan->status === \App\Enums\StatusPengajuanJudul::Diverifikasi && $pengajuan->skripsi === null)
        <dialog id="dialog-calon-pembimbing">
            <div class="dialog__body">
                <h2>Konfirmasi calon pembimbing</h2>
                <p class="lead">Pastikan pilihan sudah benar. Penetapan akan memulai proses kesediaan pembimbing.</p>
                <dl class="meta" style="grid-template-columns: 1fr; margin-top: 1rem;">
                    <div><dt>Pembimbing 1</dt><dd id="konfirmasi-p1">—</dd></div>
                    <div><dt>Pembimbing 2</dt><dd id="konfirmasi-p2">Tanpa Pembimbing 2</dd></div>
                </dl>
                <div class="dialog__actions">
                    <button class="button button--secondary" type="button" data-close-candidate-dialog>Batal</button>
                    <button class="button button--primary" type="button" data-confirm-candidates>Ya, tetapkan</button>
                </div>
            </div>
        </dialog>
        <script>
            (() => {
                const form = document.getElementById('form-calon-pembimbing');
                const p1 = document.getElementById('pembimbing1_id');
                const p2 = document.getElementById('pembimbing2_id');
                const search = document.getElementById('pencarian-calon');
                const searchStatus = document.getElementById('status-pencarian-calon');
                const dialog = document.getElementById('dialog-calon-pembimbing');
                let timer;
                let confirmed = false;

                const label = (select) => select.selectedOptions[0]?.textContent.trim() || '—';
                const validateDifferent = () => {
                    p2.setCustomValidity(p2.value && p1.value === p2.value
                        ? 'Calon Pembimbing 2 harus berbeda dari Pembimbing 1.'
                        : '');
                };

                form.addEventListener('submit', (event) => {
                    validateDifferent();
                    if (confirmed || !form.reportValidity()) return;
                    event.preventDefault();
                    document.getElementById('konfirmasi-p1').textContent = label(p1);
                    document.getElementById('konfirmasi-p2').textContent = p2.value ? label(p2) : 'Tanpa Pembimbing 2';
                    dialog.showModal();
                });
                p1.addEventListener('change', validateDifferent);
                p2.addEventListener('change', validateDifferent);
                document.querySelector('[data-close-candidate-dialog]').addEventListener('click', () => dialog.close());
                document.querySelector('[data-confirm-candidates]').addEventListener('click', () => {
                    confirmed = true;
                    dialog.close();
                    form.requestSubmit();
                });

                const replaceOptions = (select, items, emptyLabel) => {
                    const selected = select.value;
                    select.replaceChildren(new Option(emptyLabel, ''));
                    items.forEach((item) => select.add(new Option(`${item.nama} — ${item.id}`, item.id)));
                    if ([...select.options].some((option) => option.value === selected)) select.value = selected;
                };

                search.addEventListener('input', () => {
                    clearTimeout(timer);
                    timer = setTimeout(async () => {
                        searchStatus.textContent = 'Mencari…';
                        try {
                            const url = new URL(search.dataset.searchUrl, window.location.origin);
                            url.searchParams.set('q', search.value.trim());
                            const response = await fetch(url, { headers: { Accept: 'application/json' } });
                            if (!response.ok) throw new Error('Pencarian gagal');
                            const { data } = await response.json();
                            replaceOptions(p1, data, 'Pilih dosen');
                            replaceOptions(p2, data, 'Tanpa Pembimbing 2');
                            searchStatus.textContent = `${data.length} dosen ditemukan.`;
                        } catch (error) {
                            searchStatus.textContent = 'Pencarian gagal. Coba kembali.';
                        }
                    }, 250);
                });
            })();
        </script>
    @endif
@endsection
