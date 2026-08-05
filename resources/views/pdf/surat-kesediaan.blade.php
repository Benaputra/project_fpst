<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <title>Surat Kesediaan Pembimbing</title>
        <style>
            @page { margin: 22mm 20mm; }
            body { color: #111827; font-family: "DejaVu Sans", sans-serif; font-size: 11pt; line-height: 1.45; }
            h1 { font-size: 15pt; margin: 0 0 4px; text-align: center; text-transform: uppercase; }
            .subtitle { margin: 0 0 24px; text-align: center; }
            table { border-collapse: collapse; width: 100%; }
            td { padding: 3px 0; vertical-align: top; }
            .label { width: 34%; }
            .separator { width: 3%; }
            .intro { margin: 22px 0 12px; }
            .box { border: 1px solid #111827; display: inline-block; height: 13px; margin-right: 7px; vertical-align: -2px; width: 13px; }
            .choice { margin: 12px 0; }
            .lines { border-bottom: 1px solid #6b7280; height: 24px; }
            .signatures { display: table; margin-top: 30px; table-layout: fixed; width: 100%; }
            .signature { display: table-cell; text-align: center; vertical-align: top; width: 50%; }
            .signature-space { height: 76px; }
            .signature-image { height: 70px; margin: 2px auto 4px; max-width: 180px; object-fit: contain; }
            .muted { color: #4b5563; font-size: 9pt; }
        </style>
    </head>
    <body>
        <h1>Surat Kesediaan Menjadi Pembimbing Skripsi</h1>
        <p class="subtitle">Program Studi {{ $programStudi->nama }}</p>

        <table>
            <tr><td class="label">Nomor surat</td><td class="separator">:</td><td>{{ $nomorSurat }}</td></tr>
            <tr><td>Tanggal terbit</td><td>:</td><td>{{ $tanggalTerbit->format('d/m/Y') }}</td></tr>
            <tr><td>Siklus penunjukan</td><td>:</td><td>{{ $kesediaan->siklus }}</td></tr>
            <tr><td>Peran</td><td>:</td><td>{{ $kesediaan->peran === \App\Enums\PeranKesediaanBimbingan::Pembimbing1 ? 'Pembimbing 1' : 'Pembimbing 2' }}</td></tr>
        </table>

        <p class="intro">Yang bertanda tangan di bawah ini:</p>
        <table>
            <tr><td class="label">Nama dosen</td><td class="separator">:</td><td>{{ $dosen->nama }}</td></tr>
            <tr><td>NIDN</td><td>:</td><td>{{ $dosen->nidn }}</td></tr>
        </table>

        <p class="intro">menyatakan pilihan kesediaan untuk membimbing mahasiswa berikut:</p>
        <table>
            <tr><td class="label">Nama mahasiswa</td><td class="separator">:</td><td>{{ $mahasiswa->nama }}</td></tr>
            <tr><td>NIM</td><td>:</td><td>{{ $mahasiswa->nim }}</td></tr>
            <tr><td>Judul skripsi</td><td>:</td><td>{{ $skripsi->judul }}</td></tr>
        </table>

        <div class="choice"><span class="box"></span>Bersedia menjadi pembimbing</div>
        <div class="choice"><span class="box"></span>Tidak bersedia menjadi pembimbing</div>

        <p>Catatan:</p>
        <div class="lines"></div>
        <div class="lines"></div>
        <div class="lines"></div>

        <div class="signatures">
            @if ($penandaTangan && $dataTandaTangan)
                <div class="signature">
                    <p>Mengetahui,<br>Ketua Program Studi</p>
                    <img class="signature-image" src="{{ $dataTandaTangan }}" alt="Tanda tangan Ketua Program Studi">
                    <p><strong>{{ $penandaTangan->nama }}</strong><br>NIDN {{ $penandaTangan->nidn }}</p>
                </div>
            @else
                <div class="signature">
                    <p>Mengetahui,<br>Ketua Program Studi</p>
                    <div class="signature-space"></div>
                    <p class="muted">Dokumen diterbitkan tanpa tanda tangan digital</p>
                </div>
            @endif
            <div class="signature">
                <p>Tanggal: ____________________</p>
                <div class="signature-space"></div>
                <p><strong>{{ $dosen->nama }}</strong><br>NIDN {{ $dosen->nidn }}</p>
                <p class="muted">Tanda tangan asli calon pembimbing</p>
            </div>
        </div>
    </body>
</html>
