<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <title>Surat Kesediaan Pembimbing Gabungan</title>
        <style>
            @page { margin: 18mm 18mm; }
            body { color: #111827; font-family: "DejaVu Sans", sans-serif; font-size: 10.5pt; line-height: 1.38; }
            .page { page-break-after: always; }
            .page:last-child { page-break-after: auto; }
            h1 { font-size: 14pt; margin: 0 0 3px; text-align: center; text-transform: uppercase; }
            .subtitle { margin: 0 0 18px; text-align: center; }
            table { border-collapse: collapse; width: 100%; }
            td { padding: 2px 0; vertical-align: top; }
            .label { width: 34%; }
            .separator { width: 3%; }
            .intro { margin: 16px 0 8px; }
            .box { border: 1px solid #111827; display: inline-block; height: 12px; margin-right: 7px; vertical-align: -2px; width: 12px; }
            .choice { margin: 8px 0; }
            .lines { border-bottom: 1px solid #6b7280; height: 19px; }
            .signatures { display: table; margin-top: 22px; table-layout: fixed; width: 100%; }
            .signature { display: table-cell; text-align: center; vertical-align: top; width: 50%; }
            .signature-space { height: 62px; }
            .signature-image { height: 58px; margin: 1px auto 3px; max-width: 170px; object-fit: contain; }
            .muted { color: #4b5563; font-size: 8.5pt; }
        </style>
    </head>
    <body>
        @foreach ($items as $item)
            <section class="page">
                <h1>Surat Kesediaan Menjadi Pembimbing Skripsi</h1>
                <p class="subtitle">Program Studi {{ $item['programStudi']->nama }}</p>

                <table>
                    <tr><td class="label">Nomor surat</td><td class="separator">:</td><td>{{ $item['surat']->no_surat }}</td></tr>
                    <tr><td>Tanggal terbit</td><td>:</td><td>{{ $item['surat']->generated_at?->format('d/m/Y') }}</td></tr>
                    <tr><td>Siklus penunjukan</td><td>:</td><td>{{ $item['kesediaan']->siklus }}</td></tr>
                    <tr><td>Peran</td><td>:</td><td>{{ $item['kesediaan']->peran === \App\Enums\PeranKesediaanBimbingan::Pembimbing1 ? 'Pembimbing 1' : 'Pembimbing 2' }}</td></tr>
                </table>

                <p class="intro">Yang bertanda tangan di bawah ini:</p>
                <table>
                    <tr><td class="label">Nama dosen</td><td class="separator">:</td><td>{{ $item['dosen']->nama }}</td></tr>
                    <tr><td>NIDN</td><td>:</td><td>{{ $item['dosen']->nidn }}</td></tr>
                </table>

                <p class="intro">menyatakan pilihan kesediaan untuk membimbing mahasiswa berikut:</p>
                <table>
                    <tr><td class="label">Nama mahasiswa</td><td class="separator">:</td><td>{{ $item['mahasiswa']->nama }}</td></tr>
                    <tr><td>NIM</td><td>:</td><td>{{ $item['mahasiswa']->nim }}</td></tr>
                    <tr><td>Judul skripsi</td><td>:</td><td>{{ $item['skripsi']->judul }}</td></tr>
                </table>

                <div class="choice"><span class="box"></span>Bersedia menjadi pembimbing</div>
                <div class="choice"><span class="box"></span>Tidak bersedia menjadi pembimbing</div>
                <p>Catatan:</p><div class="lines"></div><div class="lines"></div>

                <div class="signatures">
                    @if ($item['penandaTangan'] && $item['dataTandaTangan'])
                        <div class="signature">
                            <p>Mengetahui,<br>Ketua Program Studi</p>
                            <img class="signature-image" src="{{ $item['dataTandaTangan'] }}" alt="Tanda tangan Ketua Program Studi">
                            <p><strong>{{ $item['penandaTangan']->nama }}</strong><br>NIDN {{ $item['penandaTangan']->nidn }}</p>
                        </div>
                    @else
                        <div class="signature"><p>Mengetahui,<br>Ketua Program Studi</p><div class="signature-space"></div><p class="muted">Dokumen diterbitkan tanpa tanda tangan digital</p></div>
                    @endif
                    <div class="signature">
                        <p>Tanggal: ____________________</p>
                        <div class="signature-space"></div>
                        <p><strong>{{ $item['dosen']->nama }}</strong><br>NIDN {{ $item['dosen']->nidn }}</p>
                        <p class="muted">Tanda tangan asli calon pembimbing</p>
                    </div>
                </div>
            </section>
        @endforeach
    </body>
</html>
