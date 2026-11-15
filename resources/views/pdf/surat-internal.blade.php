<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $pengajuan->perihal }} - {{ $mahasiswa->name }}</title>
    <style>
        @page {
            margin: 16mm 20mm 16mm 20mm;
            size: a4 portrait;
        }
        body {
            color: #000000;
            font-family: "Times New Roman", Times, serif;
            font-size: 11pt;
            line-height: 1.4;
        }
        .kop-table { border-collapse: collapse; width: 100%; }
        .kop-table td { vertical-align: middle; }
        .kop-header { text-align: center; }
        .kop-univ { font-size: 15.5pt; font-weight: bold; letter-spacing: 0.5px; line-height: 1.15; }
        .kop-fakultas { font-size: 14pt; font-weight: bold; letter-spacing: 0.3px; line-height: 1.15; margin-top: 1px; }
        .kop-prodi { font-size: 9pt; font-weight: bold; margin-top: 2px; }
        .kop-alamat, .kop-kontak { font-size: 8.5pt; margin-top: 1.5px; }
        .kop-kota { font-size: 9pt; font-weight: bold; letter-spacing: 0.5px; margin-top: 1.5px; }
        .kop-line {
            border-top: 3px solid #000000;
            border-bottom: 1px solid #000000;
            height: 2px;
            margin: 4px 0 14px 0;
        }
        .meta-table, .content-table, .ttd-table { border-collapse: collapse; width: 100%; }
        .meta-table td, .content-table td { padding: 2px 0; vertical-align: top; }
        .qr-box {
            border: 1px solid #cccccc;
            padding: 6px;
            display: inline-block;
            text-align: center;
        }
        .qr-caption {
            font-size: 7.5pt;
            color: #333333;
            margin-top: 3px;
            line-height: 1.25;
        }
    </style>
</head>
<body>
    <table class="kop-table">
        <tr>
            <td style="width: 105px; text-align: left; padding-right: 10px;">
                @if (!empty($logoBase64))
                    <img src="{{ $logoBase64 }}" style="width: 96px; height: auto;" alt="Logo FPST UPB">
                @endif
            </td>
            <td class="kop-header">
                <div class="kop-univ">UNIVERSITAS PANCA BHAKTI</div>
                <div class="kop-fakultas">FAKULTAS PERTANIAN, SAINS DAN TEKNOLOGI</div>
                <div class="kop-prodi">PROGRAM STUDI : AGROTEKNOLOGI DAN AGRIBISNIS</div>
                <div class="kop-alamat">Jalan Kom. Yos Sudarso Telp. (0561) 772627 PO BOX 1049</div>
                <div class="kop-kontak">E-mail : fpst@upb.ac.id Website : http://fpst.upb.ac.id</div>
                <div class="kop-kota">PONTIANAK 78113 – KALIMANTAN BARAT</div>
            </td>
        </tr>
    </table>

    <div class="kop-line"></div>

    <table class="meta-table" style="margin-bottom: 14px;">
        <tr>
            <td style="width: 13%;">Nomor</td>
            <td style="width: 2%;">:</td>
            <td style="width: 47%;"><strong>{{ $nomorSurat }}</strong></td>
            <td style="width: 38%; text-align: right;">Pontianak, {{ $tanggalSurat }}</td>
        </tr>
        <tr>
            <td>Lampiran</td>
            <td>:</td>
            <td colspan="2">-</td>
        </tr>
        <tr>
            <td>Perihal</td>
            <td>:</td>
            <td colspan="2" style="font-weight: bold;">{{ $pengajuan->perihal }}</td>
        </tr>
    </table>

    <div style="margin-bottom: 14px;">
        Kepada Yth.<br>
        <strong>{{ $pengajuan->tujuan_instansi }}</strong><br>
        @if ($pengajuan->alamat_instansi)
            {{ $pengajuan->alamat_instansi }}<br>
        @endif
        Di Tempat
    </div>

    <div style="margin-bottom: 8px;">Dengan hormat,</div>

    <div style="margin-bottom: 10px; text-align: justify;">
        Dekan / Pimpinan Fakultas Pertanian, Sains dan Teknologi (FPST) Universitas Panca Bhakti dengan ini menerangkan dan mengajukan permohonan kepada Bapak/Ibu agar mahasiswa berikut dapat diberikan izin/fasilitas dalam rangka kegiatan akademik:
    </div>

    <table class="content-table" style="margin-left: 20px; width: 95%; margin-bottom: 12px;">
        <tr>
            <td style="width: 26%;">Nama Mahasiswa</td>
            <td style="width: 3%;">:</td>
            <td style="font-weight: bold;">{{ $mahasiswa->name }}</td>
        </tr>
        <tr>
            <td>Nomor Induk Mahasiswa</td>
            <td>:</td>
            <td>{{ $mahasiswa->nomor_induk ?: '-' }}</td>
        </tr>
        <tr>
            <td>Program Studi</td>
            <td>:</td>
            <td>{{ $programStudi->nama }} ({{ $programStudi->kode }})</td>
        </tr>
        <tr>
            <td>Jenis Keperluan</td>
            <td>:</td>
            <td>{{ $pengajuan->jenis_surat->label() }}</td>
        </tr>
        @if ($pengajuan->tanggal_mulai || $pengajuan->tanggal_selesai)
            <tr>
                <td>Waktu Pelaksanaan</td>
                <td>:</td>
                <td>
                    {{ $pengajuan->tanggal_mulai?->translatedFormat('d F Y') ?? '-' }}
                    @if ($pengajuan->tanggal_selesai)
                        s.d. {{ $pengajuan->tanggal_selesai->translatedFormat('d F Y') }}
                    @endif
                </td>
            </tr>
        @endif
        <tr>
            <td>Keterangan / Topik</td>
            <td>:</td>
            <td style="text-align: justify;">{{ $pengajuan->keterangan_kebutuhan }}</td>
        </tr>
    </table>

    <div style="margin-bottom: 20px; text-align: justify;">
        Demikian surat resmi ini kami sampaikan. Atas perhatian, dukungan, dan kerja sama Bapak/Ibu kami ucapkan terima kasih.
    </div>

    <table class="ttd-table">
        <tr>
            <td style="width: 48%; vertical-align: bottom;">
                @if (!empty($qrEnabled) && !empty($qrDataUri))
                    <div class="qr-box">
                        <img src="{{ $qrDataUri }}" style="width: 78px; height: 78px;" alt="QR Verifikasi Surat FPST">
                        <div class="qr-caption">
                            <strong>Verifikasi Dokumen Resmi</strong><br>
                            Kode: {{ substr($verificationToken, 0, 12) }}...<br>
                            Pindai QR untuk cek keaslian
                        </div>
                    </div>
                @endif
            </td>
            <td style="width: 52%; text-align: center; vertical-align: top;">
                <div>A.n. Dekan Fakultas Pertanian, Sains dan Teknologi</div>
                <div>{{ $jabatanPenandaTangan }}</div>
                @if (!empty($ttdBase64))
                    <div style="height: 62px; margin: 4px auto;">
                        <img src="{{ $ttdBase64 }}" style="height: 62px; width: auto;" alt="Tanda Tangan Pejabat FPST">
                    </div>
                @else
                    <div style="height: 62px;"></div>
                @endif
                <div style="font-weight: bold; text-decoration: underline;">
                    {{ $penandaTangan?->name ?? 'Pejabat Berwenang FPST' }}
                </div>
                <div>NIP/NIDN. {{ $penandaTangan?->nomor_induk ?: '-' }}</div>
            </td>
        </tr>
    </table>
</body>
</html>
