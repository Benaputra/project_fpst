<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $perihal }} - {{ $mahasiswa->name }}</title>
    <style>
        @page {
            margin: 16mm 20mm 16mm 20mm;
            size: a4 portrait;
        }
        body {
            color: #000000;
            font-family: "Times New Roman", Times, serif;
            font-size: 11pt;
            line-height: 1.35;
        }
        .kop-table {
            border-collapse: collapse;
            width: 100%;
        }
        .kop-table td {
            vertical-align: middle;
        }
        .kop-header {
            color: #000000;
            font-family: "Times New Roman", Times, serif;
            text-align: center;
        }
        .kop-univ {
            font-size: 15.5pt;
            font-weight: bold;
            letter-spacing: 0.5px;
            line-height: 1.15;
        }
        .kop-fakultas {
            font-size: 14pt;
            font-weight: bold;
            letter-spacing: 0.3px;
            line-height: 1.15;
            margin-top: 1px;
        }
        .kop-prodi {
            font-size: 9pt;
            font-weight: bold;
            margin-top: 2px;
        }
        .kop-alamat {
            font-size: 8.5pt;
            margin-top: 1.5px;
        }
        .kop-kontak {
            font-size: 8.5pt;
            margin-top: 1px;
        }
        .kop-kota {
            font-size: 9pt;
            font-weight: bold;
            letter-spacing: 0.5px;
            margin-top: 1.5px;
        }
        .kop-line {
            border-top: 3px solid #000000;
            border-bottom: 1px solid #000000;
            height: 2px;
            margin: 3px 0 14px 0;
        }
        .meta-table, .content-table, .tim-table, .ttd-table {
            border-collapse: collapse;
            width: 100%;
        }
        .meta-table td {
            padding: 1px 0;
            vertical-align: top;
        }
        .content-table td {
            padding: 2.5px 0;
            vertical-align: top;
        }
        .tim-table td {
            padding: 1.5px 0;
            vertical-align: top;
        }
    </style>
</head>
<body>

    <!-- KOP SURAT RESMI FPST UNIVERSITAS PANCA BHAKTI -->
    <table class="kop-table">
        <tr>
            <td style="width: 105px; text-align: left; padding-right: 10px;">
                @if(!empty($logoBase64))
                    <img src="{{ $logoBase64 }}" style="width: 100px; height: auto;" alt="Logo FPST UPB">
                @endif
            </td>
            <td class="kop-header">
                <div class="kop-univ">UNIVERSITAS PANCA BHAKTI</div>
                <div class="kop-fakultas">FAKULTAS PERTANIAN, SAINS DAN TEKNOLOGI</div>
                <div class="kop-prodi">PROGRAM STUDI : AGROTEKNOLOGI (AKREDITASI B) DAN AGRIBISNIS (BAIK SEKALI)</div>
                <div class="kop-alamat">Jalan Kom. Yos Sudarso Telp. (0561) 772627 PO BOX 1049</div>
                <div class="kop-kontak">E-mail : fpst@upb.ac.id Website : http:fpst.upb.ac.id</div>
                <div class="kop-kota">PONTIANAK 78113 – KALIMANTAN BARAT</div>
            </td>
        </tr>
    </table>

    <!-- GARIS GANDA PEMISAH KOP SURAT -->
    <div class="kop-line"></div>

    <!-- METADATA SURAT (NOMOR & TANGGAL) -->
    <table class="meta-table" style="margin-bottom: 12px;">
        <tr>
            <td style="width: 12%;">Nomor</td>
            <td style="width: 2%;">:</td>
            <td style="width: 46%;">{{ $nomorSurat }}</td>
            <td style="width: 40%; text-align: right;">{{ $tanggalSurat }}</td>
        </tr>
        <tr>
            <td>Lampiran</td>
            <td>:</td>
            <td colspan="2">-</td>
        </tr>
        <tr>
            <td>Perihal</td>
            <td>:</td>
            <td colspan="2" style="font-weight: bold;">{{ $perihal }}</td>
        </tr>
    </table>

    <!-- KEPADA TIM SEMINAR -->
    <div style="margin-bottom: 10px;">
        Kepada<br>
        <strong>Yth. Bapak /Ibu Tim Seminar :</strong>
        <table class="tim-table" style="margin-top: 3px; margin-left: 10px;">
            @foreach($timSeminar as $index => $dosen)
                <tr>
                    <td style="width: 22px;"><strong>{{ $index + 1 }}.</strong></td>
                    <td style="width: 55%;"><strong>{{ $dosen['nama'] }}</strong></td>
                    <td><strong>({{ $dosen['peran'] }})</strong></td>
                </tr>
            @endforeach
        </table>
        <div style="margin-top: 6px;">
            Di -<br>
            &nbsp;&nbsp;&nbsp;&nbsp;<strong>Pontianak</strong>
        </div>
    </div>

    <!-- SALAM PEMBUKA & KATA PENGANTAR -->
    <div style="margin-bottom: 6px;">Dengan hormat,</div>
    <div style="margin-bottom: 8px; text-align: justify;">
        Sehubungan dengan akan dilaksanakan Seminar Rencana Penelitian Mahasiswa atas nama :
    </div>

    <!-- DATA MAHASISWA & JADWAL ACARA -->
    <table class="content-table" style="margin-left: 24px; width: 95%; margin-bottom: 12px;">
        <tr>
            <td style="width: 22%;">Nama</td>
            <td style="width: 3%;">:</td>
            <td style="font-weight: bold;">{{ $mahasiswa->name }}</td>
        </tr>
        <tr>
            <td>No. Mahasiswa</td>
            <td>:</td>
            <td>{{ $mahasiswa->nomor_induk }}</td>
        </tr>
        <tr>
            <td>Jurusan</td>
            <td>:</td>
            <td>{{ $programStudi->nama }}</td>
        </tr>
        <tr>
            <td>Hari / Tanggal</td>
            <td>:</td>
            <td>{{ $hariTanggal }}</td>
        </tr>
        <tr>
            <td>Pukul</td>
            <td>:</td>
            <td>{{ $jam }}</td>
        </tr>
        <tr>
            <td>Tempat</td>
            <td>:</td>
            <td>{{ $ruangan }}</td>
        </tr>
        <tr>
            <td>Judul Penelitian</td>
            <td>:</td>
            <td style="text-align: justify; font-style: italic; line-height: 1.3;">
                {{ $skripsi->judul }}
            </td>
        </tr>
    </table>

    <!-- PENUTUP -->
    <div style="margin-bottom: 6px; text-align: justify;">
        Untuk itu kami mengharapkan kesediaan Bapak/Ibu menghadiri Seminar tersebut sebagai Ketua / Anggota Tim Seminar / Penguji Seminar
    </div>
    <div style="margin-bottom: 20px;">
        Demikian, atas perhatian dan kerjasamanya kami ucapkan terima kasih.
    </div>

    <!-- TANDA TANGAN KAPRODI A.N. DEKAN -->
    <table class="ttd-table">
        <tr>
            <td style="width: 52%;"></td>
            <td style="width: 48%; text-align: center; vertical-align: top;">
                <div>A.n. Dekan</div>
                <div>Ketua Program Studi {{ $programStudi->nama }}</div>
                @if(!empty($ttdBase64))
                    <div style="height: 65px; margin: 3px auto;">
                        <img src="{{ $ttdBase64 }}" style="height: 65px; width: auto;" alt="Tanda Tangan & Cap Kaprodi">
                    </div>
                @else
                    <div style="height: 65px;"></div>
                @endif
                <div style="font-weight: bold; text-decoration: underline;">{{ $kaprodi ? $kaprodi->name : 'Ketua Program Studi' }}</div>
                <div>NIDN. {{ $kaprodi ? $kaprodi->nomor_induk : '-' }}</div>
            </td>
        </tr>
    </table>

</body>
</html>
