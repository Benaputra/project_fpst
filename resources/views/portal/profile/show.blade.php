@extends('layouts.app')

@section('title', 'Profil Mahasiswa')

@section('content')
    <div class="eyebrow">Data pribadi</div>
    <h1>Profil Mahasiswa</h1>
    <p class="lead">Informasi akademik yang terhubung dengan akun Anda.</p>
    <div class="grid"><section class="card">
        @if (! $mahasiswa)
            <div class="notice notice--warning">Akun belum terhubung dengan profil mahasiswa. Hubungi Admin Prodi.</div>
        @else
            <div class="profile-grid">
                <div class="profile-item"><span>Nama lengkap</span><strong>{{ $mahasiswa->nama }}</strong></div>
                <div class="profile-item"><span>NIM</span><strong>{{ $mahasiswa->nim }}</strong></div>
                <div class="profile-item"><span>Program studi</span><strong>{{ $mahasiswa->programStudi->nama }}</strong></div>
                <div class="profile-item"><span>Angkatan</span><strong>{{ $mahasiswa->angkatan }}</strong></div>
                <div class="profile-item"><span>Pembimbing akademik</span><strong>{{ $mahasiswa->pembimbingAkademik->nama }}</strong></div>
                <div class="profile-item"><span>Nomor HP</span><strong>{{ $mahasiswa->no_hp ?: 'Belum diisi' }}</strong></div>
                <div class="profile-item"><span>Tempat, tanggal lahir</span><strong>{{ $mahasiswa->tempat_lahir }}, {{ $mahasiswa->tanggal_lahir?->format('d/m/Y') }}</strong></div>
                <div class="profile-item"><span>Email akun</span><strong>{{ auth()->user()->email }}</strong></div>
            </div>
        @endif
    </section></div>
@endsection
