<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <title>SK Bimbingan Skripsi</title>
        <style>
            @page { margin: 20mm 20mm; }
            body { color: #111827; font-family: "DejaVu Sans", sans-serif; font-size: 10.5pt; line-height: 1.45; }
            h1 { font-size: 15pt; margin: 0; text-align: center; text-transform: uppercase; }
            .subtitle { margin: 4px 0 22px; text-align: center; }
            table { border-collapse: collapse; width: 100%; }
            td { padding: 3px 0; vertical-align: top; }
            .label { width: 31%; }
            .separator { width: 3%; }
            .section { font-weight: bold; margin: 20px 0 8px; }
            .decision { border: 1px solid #1f2937; margin-top: 20px; padding: 12px 14px; }
            .signature { margin-left: auto; margin-top: 30px; text-align: center; width: 48%; }
            .signature-image { height: 58px; margin: 2px auto; max-width: 180px; object-fit: contain; }
            .unsigned { border: 1px solid #6b7280; color: #374151; font-size: 9pt; margin: 12px 0; padding: 12px; }
            .muted { color: #4b5563; font-size: 8.5pt; }
        </style>
    </head>
    <body>
        <h1>Surat Keputusan Pembimbing Skripsi</h1>
        <p class="subtitle">Program Studi {{ $programStudi->nama }}</p>

        <table>
            <tr><td class="label">Nomor</td><td class="separator">:</td><td>{{ $nomorSurat }}</td></tr>
            <tr><td>Tanggal terbit</td><td>:</td><td>{{ $tanggalTerbit->format('d/m/Y') }}</td></tr>
        </table>

        <p class="section">Mahasiswa</p>
        <table>
            <tr><td class="label">Nama</td><td class="separator">:</td><td>{{ $mahasiswa->nama }}</td></tr>
            <tr><td>NIM</td><td>:</td><td>{{ $mahasiswa->nim }}</td></tr>
            <tr><td>Judul skripsi</td><td>:</td><td>{{ $skripsi->judul }}</td></tr>
        </table>

        <div class="decision">
            <p style="margin-top: 0">Menetapkan dosen berikut sebagai pembimbing resmi:</p>
            <table>
                <tr><td class="label">Pembimbing 1</td><td class="separator">:</td><td>{{ $pembimbing1->nama }} (NIDN {{ $pembimbing1->nidn }})</td></tr>
                <tr><td>Pembimbing 2</td><td>:</td><td>{{ $pembimbing2 ? $pembimbing2->nama.' (NIDN '.$pembimbing2->nidn.')' : 'Tidak ditetapkan sesuai kebijakan program studi' }}</td></tr>
            </table>
        </div>

        <div class="signature">
            <p>Ketua Program Studi,</p>
            @if ($penandaTangan && $dataTandaTangan)
                <img class="signature-image" src="{{ $dataTandaTangan }}" alt="Tanda tangan Ketua Program Studi">
                <p><strong>{{ $penandaTangan->nama }}</strong><br>NIDN {{ $penandaTangan->nidn }}</p>
                <p class="muted">Ditandatangani oleh Ketua Program Studi</p>
            @else
                <div class="unsigned">
                    TERVERIFIKASI TANPA TANDA TANGAN KAPRODI
                </div>
                <p class="muted">Dokumen diterbitkan oleh pejabat administrasi yang berwenang dan tidak dinyatakan ditandatangani Kaprodi.</p>
            @endif
        </div>
    </body>
</html>
