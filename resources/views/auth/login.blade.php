@extends('layouts.app')

@section('title', 'Masuk - Administrasi Skripsi')

@section('content')
    <div style="margin: 2rem auto 0; max-width: 30rem;">
        <div class="eyebrow">Akses sistem</div>
        <h1>Masuk ke akun Anda</h1>
        <p class="lead">Gunakan akun yang telah dihubungkan oleh administrator program studi.</p>

        <section class="card" style="margin-top: 2rem;" aria-labelledby="login-heading">
            <h2 id="login-heading">Informasi login</h2>
            <form method="POST" action="{{ route('login.store') }}">
                @csrf
                <div class="field">
                    <label for="email">Email</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" autocomplete="username" maxlength="255" required autofocus aria-describedby="email-error">
                    @error('email')
                        <p id="email-error" class="field-error" role="alert">{{ $message }}</p>
                    @enderror
                </div>
                <div class="field">
                    <label for="password">Kata sandi</label>
                    <input id="password" type="password" name="password" autocomplete="current-password" required aria-describedby="password-error">
                    @error('password')
                        <p id="password-error" class="field-error" role="alert">{{ $message }}</p>
                    @enderror
                </div>
                <div class="field" style="align-items: center; display: flex; gap: .55rem;">
                    <input id="remember" type="checkbox" name="remember" value="1" style="min-height: auto; width: auto;">
                    <label for="remember" style="margin: 0;">Ingat saya</label>
                </div>
                <button class="button button--primary" type="submit" style="width: 100%;">Masuk</button>
            </form>
        </section>
    </div>
@endsection
