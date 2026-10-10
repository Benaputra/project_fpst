<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>SK Dewan Penguji Sidang Skripsi - {{ $mahasiswa->name ?? $mahasiswa->nama ?? '' }}</title>
    <style>
        @page {
            margin: 12mm 18mm 12mm 18mm;
            size: a4 portrait;
        }
        body {
            color: #000000;
            font-family: "Times New Roman", Times, serif;
            font-size: 9.5pt;
            line-height: 1.28;
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
            font-size: 15pt;
            font-weight: bold;
            letter-spacing: 0.5px;
            line-height: 1.15;
        }
        .kop-fakultas {
            font-size: 13.5pt;
            font-weight: bold;
            letter-spacing: 0.3px;
            line-height: 1.15;
            margin-top: 1px;
        }
        .kop-prodi {
            font-size: 8.5pt;
            font-weight: bold;
            margin-top: 2px;
        }
        .kop-alamat {
            font-size: 8pt;
            margin-top: 1px;
        }
        .kop-kontak {
            font-size: 8pt;
            margin-top: 1px;
        }
        .kop-kota {
            font-size: 8.5pt;
            font-weight: bold;
            letter-spacing: 0.5px;
            margin-top: 1px;
        }
        .kop-line {
            border-top: 3px solid #000000;
            border-bottom: 1px solid #000000;
            height: 2px;
            margin: 2px 0 10px 0;
        }
        .judul-sk {
            margin-bottom: 8px;
            text-align: center;
        }
        .judul-sk .title-main {
            font-size: 11pt;
            font-weight: bold;
            text-transform: uppercase;
        }
        .judul-sk .title-univ {
            font-size: 10.5pt;
            font-weight: bold;
            text-transform: uppercase;
        }
        .judul-sk .title-nomor {
            font-size: 10pt;
            font-weight: bold;
            margin-top: 2px;
        }
        .judul-sk .title-tentang {
            font-size: 9.5pt;
            font-weight: bold;
            margin-top: 3px;
        }
        .judul-sk .title-sub {
            font-size: 10pt;
            font-weight: bold;
            text-transform: uppercase;
        }
        .sk-table {
            border-collapse: collapse;
            font-size: 9.5pt;
            margin-bottom: 6px;
            width: 100%;
        }
        .sk-table td {
            padding: 1.5px 0;
            vertical-align: top;
        }
        .sub-table {
            border-collapse: collapse;
            font-size: 9.5pt;
            margin: 2px 0 3px 0;
            width: 100%;
        }
        .sub-table td {
            padding: 1.2px 0;
            vertical-align: top;
        }
        .ttd-table {
            border-collapse: collapse;
            margin-top: 8px;
            width: 100%;
        }
        .ttd-table td {
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

    <!-- JUDUL SURAT KEPUTUSAN -->
    <div class="judul-sk">
        <div class="title-main">SURAT KEPUTUSAN DEKAN FAKULTAS PERTANIAN, SAINS DAN TEKNOLOGI</div>
        <div class="title-univ">UNIVERSITAS PANCA BHAKTI</div>
        <div class="title-nomor">NOMOR : {{ $nomorSurat }}</div>
        <div class="title-tentang">TENTANG</div>
        <div class="title-sub">PENETAPAN DEWAN PENGUJI SIDANG SKRIPSI MAHASISWA</div>
        <div class="title-sub">PROGRAM STUDI {{ strtoupper($programStudi->nama ?? '') }}</div>
    </div>

    <!-- KONSIDERANS MENIMBANG & MENGINGAT -->
    <table class="sk-table">
        <tr>
            <td style="width: 14%; font-weight: bold;">Menimbang</td>
            <td style="width: 2%;">:</td>
            <td style="text-align: justify; padding-left: 4px;">
                bahwa mahasiswa yang bersangkutan telah menyelesaikan penyusunan naskah skripsi dan memenuhi seluruh persyaratan akademik untuk diuji dalam Sidang Skripsi pada Fakultas Pertanian, Sains dan Teknologi Universitas Panca Bhakti.
            </td>
        </tr>
        <tr>
            <td style="font-weight: bold;">Mengingat</td>
            <td>:</td>
            <td style="text-align: justify; padding-left: 4px;">
                1. Peraturan Akademik Universitas Panca Bhakti;<br>
                2. Pedoman Penyelenggaraan Sidang Skripsi Fakultas Pertanian, Sains dan Teknologi UPB.
            </td>
        </tr>
    </table>

    <div style="text-align: center; font-weight: bold; font-size: 10pt; margin: 4px 0 4px 0; letter-spacing: 0.5px;">MEMUTUSKAN:</div>

    <!-- DIKTUM KEPUTUSAN -->
    <table class="sk-table">
        <tr>
            <td style="width: 14%; font-weight: bold;">Menetapkan</td>
            <td style="width: 2%;">:</td>
            <td style="padding-left: 4px;"></td>
        </tr>
        <tr>
            <td style="font-weight: bold;">KESATU</td>
            <td>:</td>
            <td style="text-align: justify; padding-left: 4px;">
                Menetapkan pelaksanaan Sidang Skripsi bagi mahasiswa Program Studi {{ $programStudi->nama ?? '' }} berikut:
                <table class="sub-table" style="margin-left: 6px; width: 98%;">
                    <tr>
                        <td style="width: 20%;">Nama</td>
                        <td style="width: 2%;">:</td>
                        <td style="font-weight: bold;">{{ $mahasiswa->name ?? $mahasiswa->nama ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td>NPM / NIM</td>
                        <td>:</td>
                        <td>{{ $mahasiswa->nomor_induk ?? $mahasiswa->nim ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td>Program Studi</td>
                        <td>:</td>
                        <td>{{ $programStudi->nama ?? '-' }} ({{ $programStudi->kode ?? '-' }})</td>
                    </tr>
                    <tr>
                        <td>Judul Skripsi</td>
                        <td>:</td>
                        <td style="font-style: italic; line-height: 1.25;">"{{ $skripsi->judul ?? '-' }}"</td>
                    </tr>
                    <tr>
                        <td>Hari / Tanggal</td>
                        <td>:</td>
                        <td style="font-weight: bold;">{{ $hariTanggal }}</td>
                    </tr>
                    <tr>
                        <td>Pukul / Tempat</td>
                        <td>:</td>
                        <td>{{ $jam }} &bull; {{ $ruangan }}</td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td style="font-weight: bold;">KEDUA</td>
            <td>:</td>
            <td style="text-align: justify; padding-left: 4px;">
                Menetapkan Susunan Dewan Penguji Sidang Skripsi sebagai berikut:
                <table class="sub-table" style="margin-left: 6px; width: 98%;">
                    <tr>
                        <td style="width: 40%;">1. Ketua Penguji (Pembimbing 1)</td>
                        <td style="width: 2%;">:</td>
                        <td style="font-weight: bold;">
                            {{ $pembimbing1->name ?? $pembimbing1->nama ?? '-' }}
                            <span style="font-weight: normal;">(NIDN: {{ $pembimbing1->nomor_induk ?? $pembimbing1->nidn ?? '-' }})</span>
                        </td>
                    </tr>
                    <tr>
                        <td>2. Anggota Penguji (Pembimbing 2)</td>
                        <td>:</td>
                        <td style="font-weight: bold;">
                            @if($pembimbing2)
                                {{ $pembimbing2->name ?? $pembimbing2->nama }}
                                <span style="font-weight: normal;">(NIDN: {{ $pembimbing2->nomor_induk ?? $pembimbing2->nidn ?? '-' }})</span>
                            @else
                                <span style="font-weight: normal; font-style: italic;">Tidak ditetapkan sesuai ketentuan</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td>3. Dosen Penguji Utama (Penguji 1)</td>
                        <td>:</td>
                        <td style="font-weight: bold;">
                            {{ $penguji1->name ?? $penguji1->nama ?? '-' }}
                            <span style="font-weight: normal;">(NIDN: {{ $penguji1->nomor_induk ?? $penguji1->nidn ?? '-' }})</span>
                        </td>
                    </tr>
                    <tr>
                        <td>4. Dosen Penguji Anggota (Penguji 2)</td>
                        <td>:</td>
                        <td style="font-weight: bold;">
                            {{ $penguji2->name ?? $penguji2->nama ?? '-' }}
                            <span style="font-weight: normal;">(NIDN: {{ $penguji2->nomor_induk ?? $penguji2->nidn ?? '-' }})</span>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td style="font-weight: bold;">KETIGA</td>
            <td>:</td>
            <td style="text-align: justify; padding-left: 4px;">
                Dewan Penguji Sidang Skripsi bertugas menyelenggarakan ujian, menilai penguasaan materi keilmuan dan metodologi, serta menetapkan nilai dan yudisium kelulusan Sidang Skripsi mahasiswa.
            </td>
        </tr>
        <tr>
            <td style="font-weight: bold;">KEEMPAT</td>
            <td>:</td>
            <td style="text-align: justify; padding-left: 4px;">
                Surat Keputusan ini berlaku sejak tanggal ditetapkan, dan apabila di kemudian hari terdapat kekeliruan dalam keputusan ini akan diadakan perbaikan sebagaimana mestinya.
            </td>
        </tr>
    </table>

    <!-- TEMBUSAN & TANDA TANGAN PEJABAT -->
    <table class="ttd-table">
        <tr>
            <td style="width: 52%; font-size: 8.5pt; padding-top: 6px;">
                <strong>Tembusan Yth:</strong><br>
                1. Dekan FPST Universitas Panca Bhakti;<br>
                2. Anggota Dewan Penguji Sidang Skripsi;<br>
                3. Mahasiswa yang bersangkutan;<br>
                4. Arsip Program Studi.
            </td>
            <td style="width: 48%; text-align: center; vertical-align: top; font-size: 9.5pt;">
                <div>Ditetapkan di : Pontianak</div>
                <div>Pada tanggal : {{ $tanggalSurat ?? date('d/m/Y') }}</div>
                <div style="margin-top: 3px;">
                    {!! $penandatanganJabatan ?? "A.n. Dekan<br>Ketua Program Studi " . ($programStudi->nama ?? '') !!}
                </div>
                @if(!empty($ttdBase64))
                    <div style="height: 62px; margin: 2px auto;">
                        <img src="{{ $ttdBase64 }}" style="height: 62px; width: auto;" alt="Tanda Tangan & Cap FPST UPB">
                    </div>
                @else
                    <div style="height: 62px;"></div>
                @endif
                <div style="font-weight: bold; text-decoration: underline;">
                    {{ $penandatanganNama ?? ($kaprodi ? $kaprodi->name : 'Ketua Program Studi') }}
                </div>
                <div>NIDN. {{ $penandatanganNidn ?? ($kaprodi ? $kaprodi->nomor_induk : '-') }}</div>
            </td>
        </tr>
    </table>

</body>
</html>
