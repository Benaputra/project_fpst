<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>@yield('title', 'Administrasi Skripsi')</title>
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif
        <style>
            :root {
                color-scheme: light;
                --navy: #17324d;
                --blue: #2563eb;
                --blue-dark: #1d4ed8;
                --ink: #172033;
                --muted: #64748b;
                --line: #dce4ee;
                --surface: #ffffff;
                --canvas: #f4f7fb;
                --danger: #b42318;
                --danger-soft: #fff1f0;
                --success: #067647;
                --success-soft: #ecfdf3;
                --warning: #b54708;
                --warning-soft: #fffaeb;
            }
            * { box-sizing: border-box; }
            body {
                margin: 0;
                min-height: 100vh;
                background: var(--canvas);
                color: var(--ink);
                font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            }
            .topbar { background: var(--navy); color: #fff; }
            .topbar__inner, .page { width: min(100% - 2rem, 76rem); margin-inline: auto; }
            .topbar__inner { display: flex; align-items: center; justify-content: space-between; min-height: 4.25rem; }
            .brand { font-size: 1rem; font-weight: 750; letter-spacing: .01em; }
            .user-chip { color: #dbeafe; font-size: .875rem; }
            .page { padding-block: 2.5rem 4rem; }
            .eyebrow { color: var(--blue); font-size: .75rem; font-weight: 800; letter-spacing: .12em; text-transform: uppercase; }
            h1 { margin: .45rem 0 .5rem; font-size: clamp(1.75rem, 4vw, 2.5rem); line-height: 1.15; }
            .lead { margin: 0; color: var(--muted); max-width: 44rem; }
            .grid { display: grid; gap: 1.25rem; margin-top: 2rem; }
            .card { background: var(--surface); border: 1px solid var(--line); border-radius: 1rem; box-shadow: 0 10px 30px rgba(23, 50, 77, .06); padding: 1.5rem; }
            .card__header { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; margin-bottom: 1.25rem; }
            .card h2 { margin: 0; font-size: 1.125rem; }
            .meta { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 1rem; }
            .meta dt { color: var(--muted); font-size: .75rem; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; }
            .meta dd { margin: .25rem 0 0; font-weight: 650; overflow-wrap: anywhere; }
            .badge { border-radius: 999px; display: inline-flex; font-size: .75rem; font-weight: 750; padding: .4rem .7rem; white-space: nowrap; }
            .badge--waiting { background: var(--warning-soft); color: var(--warning); }
            .badge--success { background: var(--success-soft); color: var(--success); }
            .badge--danger { background: var(--danger-soft); color: var(--danger); }
            .notice { border-radius: .75rem; border: 1px solid var(--line); margin-bottom: 1.25rem; padding: 1rem; }
            .notice--success { background: var(--success-soft); border-color: #abefc6; color: var(--success); }
            .notice--danger { background: var(--danger-soft); border-color: #fecdca; color: var(--danger); }
            .notice--warning { background: var(--warning-soft); border-color: #fedf89; color: #7a2e0e; }
            .field { margin-top: 1.1rem; }
            label { display: block; font-size: .875rem; font-weight: 700; margin-bottom: .45rem; }
            textarea { width: 100%; min-height: 9rem; resize: vertical; border: 1px solid #cbd5e1; border-radius: .65rem; padding: .8rem .9rem; color: var(--ink); background: #fff; line-height: 1.5; }
            textarea:focus { border-color: var(--blue); outline: 3px solid #dbeafe; }
            .field-help { color: var(--muted); font-size: .8rem; margin: .45rem 0 0; }
            .field-error { color: var(--danger); font-size: .82rem; margin: .4rem 0 0; }
            .form-grid { display: grid; gap: 1rem; grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .search-row { align-items: end; display: grid; gap: .75rem; grid-template-columns: minmax(0, 1fr) auto; }
            .search-status { color: var(--muted); font-size: .8rem; min-width: 8rem; padding-bottom: .75rem; }
            .button { border: 0; border-radius: .65rem; cursor: pointer; display: inline-flex; justify-content: center; margin-top: 1rem; padding: .75rem 1.05rem; font-weight: 750; }
            .button--primary { background: var(--blue); color: #fff; }
            .button--primary:hover { background: var(--blue-dark); }
            .title-value { font-size: 1.05rem; line-height: 1.65; margin: 0; white-space: pre-wrap; }
            .decision { border-left: 4px solid var(--danger); margin-top: 1.25rem; padding: .25rem 0 .25rem 1rem; }
            .decision strong { display: block; font-size: .82rem; margin-bottom: .3rem; }
            .toolbar { align-items: end; display: grid; gap: .8rem; grid-template-columns: minmax(12rem, 1fr) minmax(11rem, .45fr) auto; }
            .toolbar .field { margin-top: 0; }
            input, select { width: 100%; border: 1px solid #cbd5e1; border-radius: .65rem; min-height: 2.7rem; padding: .65rem .75rem; color: var(--ink); background: #fff; }
            input:focus, select:focus { border-color: var(--blue); outline: 3px solid #dbeafe; }
            input[type="file"] { padding: .55rem; }
            .button--secondary { background: #e8eef6; color: var(--navy); text-decoration: none; }
            .button--danger { background: #d92d20; color: #fff; }
            .button--success { background: #067647; color: #fff; }
            .button--compact { margin-top: 0; padding: .55rem .8rem; }
            .table-wrap { overflow-x: auto; }
            table { border-collapse: collapse; width: 100%; }
            th, td { border-bottom: 1px solid var(--line); padding: .85rem .7rem; text-align: left; vertical-align: top; }
            th { color: var(--muted); font-size: .72rem; letter-spacing: .06em; text-transform: uppercase; }
            td { font-size: .9rem; }
            .table-link { color: var(--blue-dark); font-weight: 700; text-decoration: none; }
            .empty-state { color: var(--muted); padding: 2.5rem 1rem; text-align: center; }
            .pagination { align-items: center; display: flex; gap: .8rem; justify-content: space-between; margin-top: 1.25rem; }
            .pagination__links { display: flex; gap: .5rem; }
            .pagination a { border: 1px solid var(--line); border-radius: .5rem; color: var(--navy); padding: .5rem .7rem; text-decoration: none; }
            .actions { display: flex; flex-wrap: wrap; gap: .65rem; margin-top: 1.25rem; }
            dialog { border: 0; border-radius: 1rem; box-shadow: 0 25px 70px rgba(15, 23, 42, .3); max-width: 31rem; padding: 0; width: calc(100% - 2rem); }
            dialog::backdrop { background: rgba(15, 23, 42, .55); }
            .dialog__body { padding: 1.5rem; }
            .dialog__body h2 { margin-bottom: .5rem; }
            .dialog__actions { display: flex; gap: .65rem; justify-content: flex-end; }
            .back-link { color: var(--blue-dark); display: inline-flex; font-size: .88rem; font-weight: 700; margin-bottom: 1rem; text-decoration: none; }
            .app-shell { display: grid; grid-template-columns: 17rem minmax(0, 1fr); min-height: 100vh; }
            .sidebar { background: #102a43; color: #fff; display: flex; flex-direction: column; min-height: 100vh; padding: 1.4rem 1rem; position: sticky; top: 0; height: 100vh; z-index: 20; }
            .sidebar__brand { align-items: center; display: flex; gap: .8rem; padding: .3rem .55rem 1.5rem; }
            .brand-mark { align-items: center; background: #fff; border-radius: .75rem; color: var(--navy); display: inline-flex; font-weight: 900; height: 2.5rem; justify-content: center; width: 2.5rem; }
            .brand-copy strong, .brand-copy span { display: block; }
            .brand-copy strong { font-size: .95rem; }
            .brand-copy span { color: #9fb3c8; font-size: .7rem; margin-top: .15rem; }
            .sidebar__label { color: #829ab1; font-size: .65rem; font-weight: 800; letter-spacing: .14em; margin: .7rem .7rem .55rem; text-transform: uppercase; }
            .nav-list { display: grid; gap: .3rem; }
            .nav-item { align-items: center; border-radius: .7rem; color: #d9e2ec; display: flex; font-size: .88rem; font-weight: 650; gap: .75rem; padding: .72rem .75rem; text-decoration: none; }
            .nav-item:hover { background: #1f3f5b; color: #fff; }
            .nav-item.is-active { background: #2563eb; color: #fff; }
            .nav-icon { align-items: center; background: rgba(255,255,255,.1); border-radius: .45rem; display: inline-flex; font-size: .62rem; font-weight: 850; height: 1.7rem; justify-content: center; letter-spacing: .03em; width: 1.7rem; }
            .sidebar__footer { border-top: 1px solid rgba(255,255,255,.12); margin-top: auto; padding: 1rem .5rem 0; }
            .sidebar-user { color: #d9e2ec; font-size: .78rem; line-height: 1.45; margin-bottom: .75rem; }
            .sidebar-user strong { color: #fff; display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
            .logout-button { background: transparent; border: 1px solid #627d98; border-radius: .55rem; color: #fff; cursor: pointer; padding: .5rem .65rem; width: 100%; }
            .content-shell { min-width: 0; }
            .mobile-topbar { align-items: center; background: #fff; border-bottom: 1px solid var(--line); display: none; justify-content: space-between; min-height: 4rem; padding: .75rem 1rem; }
            .menu-button { background: #e8eef6; border: 0; border-radius: .55rem; color: var(--navy); cursor: pointer; font-size: 1.2rem; height: 2.5rem; width: 2.5rem; }
            .stats-grid { display: grid; gap: 1rem; grid-template-columns: repeat(4, minmax(0, 1fr)); margin-top: 2rem; }
            .stat-card { background: #fff; border: 1px solid var(--line); border-radius: .9rem; padding: 1.2rem; }
            .stat-card__label { color: var(--muted); font-size: .76rem; font-weight: 750; letter-spacing: .04em; text-transform: uppercase; }
            .stat-card__value { color: var(--navy); font-size: 1.9rem; font-weight: 850; margin-top: .45rem; }
            .stat-card__hint { color: var(--muted); font-size: .75rem; margin-top: .25rem; }
            .section-heading { align-items: end; display: flex; justify-content: space-between; margin: 2rem 0 .9rem; }
            .section-heading h2 { margin: 0; }
            .role-chip { background: #dbeafe; border-radius: 999px; color: #1d4ed8; display: inline-flex; font-size: .72rem; font-weight: 800; padding: .4rem .7rem; }
            .status-text { text-transform: capitalize; }
            .profile-grid { display: grid; gap: 1rem; grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .profile-item { border-bottom: 1px solid var(--line); padding: .4rem 0 1rem; }
            .profile-item span, .profile-item strong { display: block; }
            .profile-item span { color: var(--muted); font-size: .75rem; margin-bottom: .3rem; }
            .profile-item strong { font-size: .92rem; }
            .sidebar-backdrop { display: none; }
            .workflow-list { display: grid; gap: 1rem; }
            .workflow-card { border: 1px solid var(--line); border-radius: .8rem; padding: 1rem; }
            .workflow-card__header { align-items: flex-start; display: flex; gap: 1rem; justify-content: space-between; }
            .workflow-card__header h3 { margin: 0; }
            .workflow-card details { border-top: 1px solid var(--line); margin-top: 1rem; padding-top: .8rem; }
            .workflow-card summary { color: var(--blue-dark); cursor: pointer; font-size: .86rem; font-weight: 750; }
            .inline-fields { display: grid; gap: .8rem; grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .inline-fields--four { grid-template-columns: repeat(4, minmax(0, 1fr)); }
            .compact-actions { align-items: center; display: flex; flex-wrap: wrap; gap: .6rem; margin-top: .8rem; }
            .compact-actions .button { margin-top: 0; }
            @media (max-width: 700px) {
                .meta { grid-template-columns: 1fr; }
                .card__header { align-items: flex-start; flex-direction: column; }
                .toolbar { grid-template-columns: 1fr; }
                .form-grid, .search-row { grid-template-columns: 1fr; }
                .pagination { align-items: flex-start; flex-direction: column; }
                .app-shell { display: block; }
                .sidebar { bottom: 0; left: 0; position: fixed; top: 0; transform: translateX(-105%); transition: transform .2s ease; width: 17rem; }
                .sidebar.is-open { transform: translateX(0); }
                .sidebar-backdrop { background: rgba(15,23,42,.5); inset: 0; position: fixed; z-index: 15; }
                .sidebar-backdrop.is-open { display: block; }
                .mobile-topbar { display: flex; }
                .stats-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
                .profile-grid { grid-template-columns: 1fr; }
                .inline-fields, .inline-fields--four { grid-template-columns: 1fr; }
            }
        </style>
    </head>
    <body>
        @auth
            @php
                $user = auth()->user();
                $kaprodi = $user->isKetuaProdi();
                $pengajuanRoute = $user->isMahasiswa()
                    ? 'mahasiswa.pengajuan-judul.index'
                    : ($kaprodi ? 'kaprodi.pengajuan-judul.index' : 'portal.pengajuan-judul.index');
            @endphp
            <div class="app-shell">
                <aside class="sidebar" id="sidebar">
                    <a class="sidebar__brand" href="{{ route('dashboard') }}" style="color: inherit; text-decoration: none;">
                        <span class="brand-mark">FP</span>
                        <span class="brand-copy"><strong>Portal Skripsi</strong><span>Fakultas Pascasarjana</span></span>
                    </a>
                    <div class="sidebar__label">Menu utama</div>
                    <nav class="nav-list" aria-label="Navigasi utama">
                        <a class="nav-item {{ request()->routeIs('dashboard') ? 'is-active' : '' }}" href="{{ route('dashboard') }}">
                            <span class="nav-icon">DB</span> Dashboard
                        </a>
                        <a class="nav-item {{ request()->routeIs('*pengajuan-judul*') ? 'is-active' : '' }}" href="{{ route($pengajuanRoute) }}">
                            <span class="nav-icon">PJ</span> Pengajuan Judul
                        </a>
                        <a class="nav-item {{ request()->routeIs('portal.seminar.*') ? 'is-active' : '' }}" href="{{ route('portal.seminar.index') }}">
                            <span class="nav-icon">SM</span> Seminar
                        </a>
                        <a class="nav-item {{ request()->routeIs('portal.skripsi.*') ? 'is-active' : '' }}" href="{{ route('portal.skripsi.index') }}">
                            <span class="nav-icon">SK</span> Skripsi
                        </a>
                        <a class="nav-item {{ request()->routeIs('portal.sidang.*') ? 'is-active' : '' }}" href="{{ route('portal.sidang.index') }}">
                            <span class="nav-icon">SD</span> Sidang
                        </a>
                        @if ($user->isMahasiswa())
                            <a class="nav-item {{ request()->routeIs('portal.profile.*') ? 'is-active' : '' }}" href="{{ route('portal.profile.show') }}">
                                <span class="nav-icon">PR</span> Profil
                            </a>
                        @endif
                        @if ($kaprodi || $user->isAdminProdi() || $user->isAdminUtama())
                            <a class="nav-item {{ request()->routeIs('portal.surat.*') ? 'is-active' : '' }}" href="{{ route('portal.surat.index') }}">
                                <span class="nav-icon">SR</span> Surat
                            </a>
                        @endif
                        @if ($user->isAdminUtama())
                            <a class="nav-item {{ request()->routeIs('portal.aktivitas-log.*') ? 'is-active' : '' }}" href="{{ route('portal.aktivitas-log.index') }}">
                                <span class="nav-icon">LG</span> Log Aktivitas
                            </a>
                        @endif
                    </nav>
                    <div class="sidebar__footer">
                        <div class="sidebar-user">
                            <strong>{{ $user->name }}</strong>
                            {{ $kaprodi ? 'Ketua Program Studi' : $user->role->label() }}
                        </div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="logout-button" type="submit">Keluar dari akun</button>
                        </form>
                    </div>
                </aside>
                <div class="sidebar-backdrop" id="sidebar-backdrop"></div>
                <div class="content-shell">
                    <header class="mobile-topbar">
                        <strong>Portal Skripsi</strong>
                        <button class="menu-button" id="menu-button" type="button" aria-label="Buka menu" aria-expanded="false">☰</button>
                    </header>
                    <main class="page">@yield('content')</main>
                </div>
            </div>
            <script>
                (() => {
                    const sidebar = document.getElementById('sidebar');
                    const backdrop = document.getElementById('sidebar-backdrop');
                    const button = document.getElementById('menu-button');
                    if (!button) return;
                    const close = () => {
                        sidebar.classList.remove('is-open');
                        backdrop.classList.remove('is-open');
                        button.setAttribute('aria-expanded', 'false');
                    };
                    button.addEventListener('click', () => {
                        const open = !sidebar.classList.contains('is-open');
                        sidebar.classList.toggle('is-open', open);
                        backdrop.classList.toggle('is-open', open);
                        button.setAttribute('aria-expanded', String(open));
                    });
                    backdrop.addEventListener('click', close);
                })();
            </script>
        @else
            <header class="topbar"><div class="topbar__inner"><div class="brand">Administrasi Skripsi</div></div></header>
            <main class="page">@yield('content')</main>
        @endauth
    </body>
</html>
