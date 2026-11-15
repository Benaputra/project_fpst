<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verifikasi Keaslian Surat Resmi - FPST UPB</title>
    <style>
        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: #f4f7f4;
            color: #16241b;
            margin: 0;
            padding: 2rem 1rem;
        }
        .card {
            max-width: 680px;
            margin: 0 auto;
            background: #ffffff;
            border: 1px solid #d8e2db;
            border-radius: 1rem;
            padding: 1.75rem;
            box-shadow: 0 10px 28px rgba(22, 36, 27, 0.06);
        }
        .header {
            display: flex;
            align-items: center;
            gap: 0.85rem;
            border-bottom: 1px solid #e4ece6;
            padding-bottom: 1rem;
            margin-bottom: 1.25rem;
        }
        .header img { width: 48px; height: auto; }
        .status-banner {
            padding: 0.9rem 1rem;
            border-radius: 0.75rem;
            margin-bottom: 1.25rem;
            font-weight: 600;
            font-size: 0.92rem;
        }
        .status-valid { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .status-invalid { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        table { width: 100%; border-collapse: collapse; font-size: 0.9rem; }
        td { padding: 0.55rem 0; border-bottom: 1px solid #eef3ef; vertical-align: top; }
        .label { width: 38%; color: #55655b; font-weight: 600; }
        .actions { margin-top: 1.25rem; display: flex; gap: 0.65rem; flex-wrap: wrap; }
        .btn {
            display: inline-block;
            padding: 0.5rem 0.95rem;
            border-radius: 0.55rem;
            text-decoration: none;
            font-size: 0.84rem;
            font-weight: 600;
            background: #446850;
            color: #fff;
        }
        .btn-outline { background: #fff; color: #16241b; border: 1px solid #d8e2db; }
    </style>
</head>
<body>
    <div class="card">
        <div class="header">
            <img src="{{ asset('images/logo.png') }}" alt="Logo FPST Universitas Panca Bhakti">
            <div>
                <div style="font-weight: 800; font-size: 1.05rem;">Verifikasi Keaslian Dokumen Resmi</div>
                <div style="font-size: 0.82rem; color: #55655b;">Fakultas Pertanian, Sains dan Teknologi (FPST) • Universitas Panca Bhakti</div>
            </div>
        </div>

        @if (!empty($qrDisabled))
            <div class="status-banner" style="background: #fef3c7; color: #92400e; border: 1px solid #fde68a;">
                ℹ️ Layanan Verifikasi QR Code Dokumen Sedang Dinonaktifkan Sementara.
            </div>
            <p style="font-size: 0.88rem; color: #55655b; line-height: 1.55;">
                Pemeriksaan keaslian surat melalui QR Code saat ini sedang dinonaktifkan oleh Fakultas Pertanian, Sains dan Teknologi (FPST) Universitas Panca Bhakti. Silakan hubungi bagian Tata Usaha / Akademik FPST untuk konfirmasi manual.
            </p>
        @elseif ($surat && $isValidSignature)
            <div class="status-banner status-valid">
                ✅ Dokumen Terdaftar dan Terverifikasi Asli pada Sistem Administrasi FPST UPB ({{ strtoupper($surat->status) }}).
            </div>

            <table>
                <tr>
                    <td class="label">Nomor Surat</td>
                    <td><strong style="font-family: monospace;">{{ $surat->nomor_surat }}</strong></td>
                </tr>
                <tr>
                    <td class="label">Jenis Dokumen</td>
                    <td>{{ $surat->jenisLabel() }}</td>
                </tr>
                <tr>
                    <td class="label">Perihal / Nama Surat</td>
                    <td>{{ $surat->nama_surat }}</td>
                </tr>
                <tr>
                    <td class="label">Nama Mahasiswa</td>
                    <td><strong>{{ $pemilikNama }}</strong></td>
                </tr>
                <tr>
                    <td class="label">Program Studi</td>
                    <td>{{ $surat->programStudi?->nama ?? '-' }} ({{ $surat->programStudi?->kode ?? '-' }})</td>
                </tr>
                <tr>
                    <td class="label">Tanggal Terbit</td>
                    <td>{{ $surat->tgl_surat?->translatedFormat('d F Y') ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="label">Pejabat Penanda Tangan</td>
                    <td>
                        {{ $surat->penandaTangan?->name ?? 'Pejabat Berwenang FPST' }}
                        @if ($surat->jabatan_penanda_tangan)
                            <div style="font-size: 0.8rem; color: #55655b;">{{ $surat->jabatan_penanda_tangan }}</div>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td class="label">Status Versi Surat</td>
                    <td>{{ ucfirst($surat->status) }} (Versi ke-{{ $surat->versi }})</td>
                </tr>
            </table>
        @else
            <div class="status-banner status-invalid">
                ❌ Dokumen Tidak Dikenali atau Token Verifikasi Tidak Valid.
            </div>
            <p style="font-size: 0.88rem; color: #55655b; line-height: 1.55;">
                Kode verifikasi (<code>{{ $token }}</code>) tidak terdaftar di arsip persuratan resmi Fakultas Pertanian, Sains dan Teknologi (FPST) Universitas Panca Bhakti atau tanda tangan digital tidak cocok.
            </p>
        @endif

        <div class="actions">
            <a href="{{ route('jadwal.publik') }}" class="btn btn-outline">Lihat Jadwal Publik</a>
            <a href="{{ route('login') }}" class="btn">Portal Civitas FPST</a>
        </div>
    </div>
</body>
</html>
