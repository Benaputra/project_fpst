<!DOCTYPE html>
<html lang="id">
<head><meta charset="utf-8"><title>Surat Seminar</title>
<style>
@page{margin:20mm} body{font-family:"DejaVu Sans",sans-serif;color:#111827;font-size:10.5pt;line-height:1.5} h1{text-align:center;font-size:15pt;margin:0;text-transform:uppercase}.sub{text-align:center;margin:4px 0 22px}table{border-collapse:collapse;width:100%}td{padding:3px 0;vertical-align:top}.l{width:31%}.s{width:3%}.box{border:1px solid #374151;margin:18px 0;padding:12px}.sign{margin-left:auto;margin-top:28px;text-align:center;width:48%}.sign img{height:58px;max-width:180px;object-fit:contain}.unsigned{border:1px solid #6b7280;padding:10px;font-size:9pt}.muted{color:#4b5563;font-size:8.5pt}
</style></head>
<body>
@php($namaProses = $proses instanceof \App\Models\Seminar ? 'Seminar' : 'Sidang')
@php($undangan = in_array($jenis, [\App\Enums\JenisSurat::UndanganSeminar, \App\Enums\JenisSurat::UndanganSidang], true))
<h1>{{ $undangan ? 'Undangan' : 'Surat Tugas' }} {{ $namaProses }} Skripsi</h1>
<p class="sub">Program Studi {{ $proses->skripsi->mahasiswa->programStudi->nama }}</p>
<table><tr><td class="l">Nomor</td><td class="s">:</td><td>{{ $nomor }}</td></tr><tr><td>Tanggal terbit</td><td>:</td><td>{{ $terbit->format('d/m/Y') }}</td></tr></table>
<div class="box"><table>
<tr><td class="l">Mahasiswa</td><td class="s">:</td><td>{{ $proses->skripsi->mahasiswa->nama }} ({{ $proses->skripsi->nim }})</td></tr>
<tr><td>Judul</td><td>:</td><td>{{ $proses->skripsi->judul }}</td></tr>
<tr><td>Jadwal</td><td>:</td><td>{{ $proses->tanggal->format('d/m/Y H:i') }}</td></tr>
<tr><td>Tempat</td><td>:</td><td>{{ $proses->tempat }}</td></tr>
<tr><td>Penguji 1</td><td>:</td><td>{{ $proses->penguji1->nama }}</td></tr>
<tr><td>Penguji 2</td><td>:</td><td>{{ $proses->penguji2->nama }}</td></tr>
<tr><td>Pembimbing 1</td><td>:</td><td>{{ $proses->skripsi->pembimbing1->nama }}</td></tr>
@if($proses->skripsi->pembimbing2)<tr><td>Pembimbing 2</td><td>:</td><td>{{ $proses->skripsi->pembimbing2->nama }}</td></tr>@endif
</table></div>
<div class="sign"><p>Ketua Program Studi,</p>
@if($signer && $ttd)<img src="{{ $ttd }}" alt="Tanda tangan Kaprodi"><p><strong>{{ $signer->nama }}</strong><br>NIDN {{ $signer->nidn }}</p>
@else<div class="unsigned">TERVERIFIKASI TANPA TANDA TANGAN KAPRODI</div><p class="muted">Diterbitkan oleh pejabat administrasi berwenang.</p>@endif
</div></body></html>
