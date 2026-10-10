@extends('layouts.app')

@section('title', 'Masuk - Portal Skripsi')

@section('content')
<div style="max-width: 480px; width: 100%; margin: 0 auto;">
    <div style="text-align: center; margin-bottom: 1.75rem;">
        <div style="display: inline-flex; align-items: center; justify-content: center; margin-bottom: 0.85rem;">
            <img src="{{ asset('images/logo.png') }}" alt="Logo Fakultas Pertanian, Sains dan Teknologi - UPB" style="width: 5.25rem; height: 5.25rem; max-width: 100%; object-fit: contain; filter: drop-shadow(0 8px 20px rgba(0,0,0,0.15)); transition: transform 0.2s ease;">
        </div>
        <h1 style="font-size: 1.45rem; font-weight: 800; color: #142017; letter-spacing: -0.01em;">Portal Skripsi</h1>
        <p style="font-size: 0.88rem; font-weight: 600; color: #446850; margin-top: 0.2rem;">Fakultas Pertanian, Sains dan Teknologi</p>
        <p style="font-size: 0.78rem; color: #627967; margin-top: 0.1rem;">Universitas Panca Bhakti</p>
    </div>

    <div class="card" style="padding: 1.75rem; box-shadow: 0 20px 40px rgba(24,36,28,0.06); border-radius: 1rem;">
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
            <div style="font-size: 0.72rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 0.85rem; text-align: center; letter-spacing: 0.05em;">
                Akun Uji Coba Cepat (Password: <code>password</code>)
            </div>

            <!-- Kategori 1: Mahasiswa (3 Tahapan Alur) -->
            <div style="margin-bottom: 0.75rem;">
                <div style="font-size: 0.68rem; font-weight: 700; color: #446850; text-transform: uppercase; margin-bottom: 0.35rem; display: flex; align-items: center; gap: 0.3rem;">
                    <span>👨‍🎓</span> Mahasiswa (3 Tahapan Alur):
                </div>
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.35rem;">
                    <button type="button" class="btn btn-secondary btn-sm" style="font-size: 0.72rem; padding: 0.35rem 0.4rem; justify-content: center; text-align: center; line-height: 1.2;" onclick="setLogin('mahasiswa1@example.test')">
                        Mahasiswa 1<br><span style="font-size: 0.65rem; color: var(--text-muted);">(Baru)</span>
                    </button>
                    <button type="button" class="btn btn-secondary btn-sm" style="font-size: 0.72rem; padding: 0.35rem 0.4rem; justify-content: center; text-align: center; line-height: 1.2;" onclick="setLogin('mahasiswa2@example.test')">
                        Mahasiswa 2<br><span style="font-size: 0.65rem; color: var(--text-muted);">(SK Terbit)</span>
                    </button>
                    <button type="button" class="btn btn-secondary btn-sm" style="font-size: 0.72rem; padding: 0.35rem 0.4rem; justify-content: center; text-align: center; line-height: 1.2;" onclick="setLogin('mahasiswa3@example.test')">
                        Mahasiswa 3<br><span style="font-size: 0.65rem; color: var(--text-muted);">(Lulus Seminar)</span>
                    </button>
                </div>
            </div>

            <!-- Kategori 2: Kaprodi & Admin Prodi FPST -->
            <div style="margin-bottom: 0.75rem;">
                <div style="font-size: 0.68rem; font-weight: 700; color: #446850; text-transform: uppercase; margin-bottom: 0.35rem; display: flex; align-items: center; gap: 0.3rem;">
                    <span>🏛️</span> Program Studi (AGT & AGB):
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.35rem;">
                    <button type="button" class="btn btn-secondary btn-sm" style="font-size: 0.72rem; padding: 0.35rem 0.5rem; justify-content: flex-start; text-align: left; line-height: 1.2;" onclick="setLogin('kaprodi.agt@example.test')">
                        ⚖️ Kaprodi Agroteknologi
                    </button>
                    <button type="button" class="btn btn-secondary btn-sm" style="font-size: 0.72rem; padding: 0.35rem 0.5rem; justify-content: flex-start; text-align: left; line-height: 1.2;" onclick="setLogin('kaprodi.agb@example.test')">
                        ⚖️ Kaprodi Agribisnis
                    </button>
                    <button type="button" class="btn btn-secondary btn-sm" style="font-size: 0.72rem; padding: 0.35rem 0.5rem; justify-content: flex-start; text-align: left; line-height: 1.2;" onclick="setLogin('admin.agt@example.test')">
                        📂 Admin Prodi AGT
                    </button>
                    <button type="button" class="btn btn-secondary btn-sm" style="font-size: 0.72rem; padding: 0.35rem 0.5rem; justify-content: flex-start; text-align: left; line-height: 1.2;" onclick="setLogin('admin.agb@example.test')">
                        📂 Admin Prodi AGB
                    </button>
                </div>
            </div>

            <!-- Kategori 3: Dosen & Admin Utama -->
            <div>
                <div style="font-size: 0.68rem; font-weight: 700; color: #446850; text-transform: uppercase; margin-bottom: 0.35rem; display: flex; align-items: center; gap: 0.3rem;">
                    <span>👨‍🏫</span> Dosen & Super Admin:
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.35rem;">
                    <button type="button" class="btn btn-secondary btn-sm" style="font-size: 0.72rem; padding: 0.35rem 0.5rem; justify-content: flex-start; text-align: left; line-height: 1.2;" onclick="setLogin('dosen1@example.test')">
                        🎓 Dosen Pembimbing/Penguji
                    </button>
                    <button type="button" class="btn btn-secondary btn-sm" style="font-size: 0.72rem; padding: 0.35rem 0.5rem; justify-content: flex-start; text-align: left; line-height: 1.2;" onclick="setLogin('admin.utama@example.test')">
                        👑 Admin Utama FPST
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function setLogin(email) {
    const emailEl = document.getElementById('email');
    const passEl = document.getElementById('password');
    emailEl.value = email;
    passEl.value = 'password';
    emailEl.focus();
}
</script>
@endsection
