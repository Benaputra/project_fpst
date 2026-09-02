@extends('layouts.app')

@section('title', 'Masuk - Portal Skripsi')

@section('content')
<div style="max-width: 420px; width: 100%;">
    <div style="text-align: center; margin-bottom: 2rem;">
        <div style="display: inline-flex; width: 3.5rem; height: 3.5rem; background: linear-gradient(135deg, #5b8769, #385642); border-radius: 1rem; align-items: center; justify-content: center; color: #fff; font-size: 1.5rem; font-weight: 800; margin-bottom: 1rem; box-shadow: 0 10px 25px rgba(68,104,80,0.25);">
            FP
        </div>
        <h1 style="font-size: 1.5rem; font-weight: 800; color: #142017;">Portal Skripsi</h1>
        <p style="font-size: 0.88rem; color: #576d5c; margin-top: 0.25rem;">Masuk ke sistem pengajuan & administrasi skripsi</p>
    </div>

    <div class="card" style="padding: 2rem; box-shadow: 0 20px 40px rgba(24,36,28,0.06);">
        <form method="POST" action="{{ route('login.store') }}">
            @csrf

            <div class="form-group">
                <label for="email" class="form-label">Alamat Email</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" class="form-control" placeholder="nama@example.test" required autofocus>
                @error('email')
                    <div class="form-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Kata Sandi</label>
                <input id="password" type="password" name="password" class="form-control" placeholder="••••••••" required>
                @error('password')
                    <div class="form-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group" style="display: flex; align-items: center; gap: 0.5rem; margin-top: -0.25rem;">
                <input type="checkbox" name="remember" id="remember">
                <label for="remember" style="font-size: 0.82rem; color: #3b5040; margin: 0; cursor: pointer;">Ingat saya di perangkat ini</label>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 0.75rem; font-size: 0.95rem;">
                Masuk ke Akun
            </button>
        </form>

        <div style="margin-top: 1.75rem; padding-top: 1.25rem; border-top: 1px dashed var(--border);">
            <div style="font-size: 0.72rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 0.75rem; text-align: center;">
                Akun Uji Coba Cepat (Password: password)
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.4rem; font-size: 0.78rem;">
                <button type="button" class="btn btn-secondary btn-sm" onclick="setLogin('mahasiswa1@example.test')">Mahasiswa 1 (Baru)</button>
                <button type="button" class="btn btn-secondary btn-sm" onclick="setLogin('mahasiswa2@example.test')">Mahasiswa 2 (SK)</button>
                <button type="button" class="btn btn-secondary btn-sm" onclick="setLogin('kaprodi.ti@example.test')">Kaprodi TI</button>
                <button type="button" class="btn btn-secondary btn-sm" onclick="setLogin('admin.ti@example.test')">Admin Prodi TI</button>
                <button type="button" class="btn btn-secondary btn-sm" onclick="setLogin('dosen1@example.test')">Dosen (Penguji)</button>
                <button type="button" class="btn btn-secondary btn-sm" onclick="setLogin('admin.utama@example.test')">Admin Utama</button>
            </div>
        </div>
    </div>
</div>

<script>
function setLogin(email) {
    document.getElementById('email').value = email;
    document.getElementById('password').value = 'password';
}
</script>
@endsection
