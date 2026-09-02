<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0">
    <title>@yield('title', 'Sistem Administrasi Skripsi')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            /* Tema Hijau Sage (Sage Green) */
            --primary: #446850;
            --primary-hover: #35533f;
            --primary-light: #eaf0eb;
            --primary-border: #b8ccbe;
            --sidebar-bg: #18241c;
            --sidebar-hover: #24352a;
            --sidebar-border: #223328;
            --canvas: #f3f6f3;
            --card-bg: #ffffff;
            --text-main: #142017;
            --text-muted: #576d5c;
            --border: #dbe4dc;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            background-color: var(--canvas);
            color: var(--text-main);
            min-height: 100vh;
            -webkit-tap-highlight-color: transparent;
        }
        .layout-shell {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Desktop */
        .sidebar {
            width: 260px;
            background: var(--sidebar-bg);
            color: #f1f5f2;
            display: flex;
            flex-direction: column;
            position: sticky;
            top: 0;
            height: 100vh;
            flex-shrink: 0;
            border-right: 1px solid var(--sidebar-border);
            z-index: 60;
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .sidebar-brand {
            padding: 1.25rem 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            border-bottom: 1px solid var(--sidebar-border);
            text-decoration: none;
            color: inherit;
        }
        .brand-icon {
            width: 2.25rem;
            height: 2.25rem;
            background: linear-gradient(135deg, #5b8769, #385642);
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            color: #fff;
            font-size: 1.1rem;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            flex-shrink: 0;
        }
        .brand-title {
            font-size: 0.95rem;
            font-weight: 700;
            line-height: 1.2;
        }
        .brand-subtitle {
            font-size: 0.7rem;
            color: #9cb1a0;
        }
        .sidebar-menu {
            padding: 1rem 0.75rem;
            display: flex;
            flex-direction: column;
            gap: 0.35rem;
            flex: 1;
            overflow-y: auto;
        }
        .menu-label {
            font-size: 0.68rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #748c79;
            padding: 0.5rem 0.75rem 0.25rem;
        }
        .nav-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.65rem 0.85rem;
            border-radius: 0.5rem;
            color: #ccd8ce;
            text-decoration: none;
            font-size: 0.88rem;
            font-weight: 550;
            transition: all 0.15s ease;
        }
        .nav-link:hover {
            background: var(--sidebar-hover);
            color: #ffffff;
        }
        .nav-link.active {
            background: #446850;
            color: #ffffff;
            font-weight: 600;
        }
        .nav-badge {
            margin-left: auto;
            font-size: 0.7rem;
            background: #e11d48;
            color: #fff;
            padding: 0.15rem 0.45rem;
            border-radius: 999px;
            font-weight: 700;
        }
        .sidebar-footer {
            padding: 1rem;
            border-top: 1px solid var(--sidebar-border);
            background: rgba(0, 0, 0, 0.2);
        }
        .user-info {
            font-size: 0.82rem;
            margin-bottom: 0.75rem;
        }
        .user-name {
            font-weight: 700;
            color: #fff;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .user-role {
            font-size: 0.72rem;
            color: #9cb1a0;
        }
        .btn-logout {
            width: 100%;
            background: transparent;
            border: 1px solid #334a3b;
            color: #d1ded4;
            padding: 0.5rem 0.75rem;
            border-radius: 0.375rem;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.15s;
        }
        .btn-logout:hover {
            background: #dc2626;
            border-color: #dc2626;
            color: #fff;
        }

        /* Overlay Backdrop */
        .sidebar-backdrop {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.55);
            backdrop-filter: blur(2px);
            z-index: 55;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        /* Main Content */
        .content-area {
            flex: 1;
            min-width: 0;
            display: flex;
            flex-direction: column;
        }
        .topbar {
            background: #fff;
            border-bottom: 1px solid var(--border);
            padding: 0.75rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 30;
        }
        .topbar-left {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .menu-toggle-btn {
            display: none;
            background: #f1f5f2;
            border: 1px solid var(--border);
            border-radius: 0.45rem;
            width: 2.25rem;
            height: 2.25rem;
            align-items: center;
            justify-content: center;
            font-size: 1.15rem;
            color: var(--text-main);
            cursor: pointer;
        }
        .topbar-title {
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--text-main);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .topbar-actions {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .bell-btn {
            position: relative;
            background: #f1f5f2;
            border: 1px solid var(--border);
            border-radius: 0.5rem;
            width: 2.25rem;
            height: 2.25rem;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-main);
            text-decoration: none;
            cursor: pointer;
            transition: background 0.15s;
            flex-shrink: 0;
        }
        .bell-btn:hover {
            background: #e3ebe4;
        }
        .bell-badge {
            position: absolute;
            top: -4px;
            right: -4px;
            background: #e11d48;
            color: #fff;
            font-size: 0.65rem;
            font-weight: 800;
            padding: 0.15rem 0.35rem;
            border-radius: 999px;
            border: 2px solid #fff;
            min-width: 1.1rem;
            text-align: center;
        }
        .main-container {
            padding: 1.5rem;
            max-width: 1200px;
            width: 100%;
            margin: 0 auto;
        }

        /* Bottom Navigation Bar (Khusus Mobile) */
        .bottom-nav {
            display: none;
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: #ffffff;
            border-top: 1px solid var(--border);
            padding: 0.35rem 0.5rem;
            z-index: 50;
            box-shadow: 0 -4px 15px rgba(0, 0, 0, 0.05);
            justify-content: space-around;
            align-items: center;
        }
        .bottom-nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-decoration: none;
            color: #576d5c;
            font-size: 0.68rem;
            font-weight: 600;
            padding: 0.3rem 0.5rem;
            border-radius: 0.5rem;
            position: relative;
            flex: 1;
            max-width: 80px;
        }
        .bottom-nav-item.active {
            color: #446850;
            font-weight: 800;
        }
        .bottom-nav-icon {
            font-size: 1.25rem;
            line-height: 1.2;
            margin-bottom: 0.15rem;
        }
        .bottom-nav-badge {
            position: absolute;
            top: 2px;
            right: 14px;
            background: #e11d48;
            color: #fff;
            font-size: 0.6rem;
            font-weight: 800;
            padding: 0.1rem 0.3rem;
            border-radius: 999px;
        }

        /* Flash Alerts */
        .alert {
            padding: 0.85rem 1rem;
            border-radius: 0.65rem;
            margin-bottom: 1.25rem;
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            font-size: 0.88rem;
            line-height: 1.4;
        }
        .alert-success { background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; }
        .alert-error { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
        .alert-info { background: #f1f7f2; border: 1px solid #c0d8c4; color: #2c4a35; }
        .alert-warning { background: #fffbeb; border: 1px solid #fde68a; color: #92400e; }

        /* Card & Components */
        .card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 0.85rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.03);
            padding: 1.25rem;
            margin-bottom: 1.25rem;
        }
        .card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1rem;
            border-bottom: 1px solid var(--border);
            padding-bottom: 0.75rem;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        .card-title {
            font-size: 1.05rem;
            font-weight: 700;
        }
        .badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.65rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            line-height: 1;
        }
        .badge-diajukan { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
        .badge-diproses { background: #eaf1eb; color: #35533f; border: 1px solid #bfd3c3; }
        .badge-selesai { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .badge-ditolak { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }

        /* Stepper Component */
        .stepper {
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: relative;
            margin: 1.25rem 0 1.5rem;
        }
        .stepper::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 1.5rem;
            right: 1.5rem;
            height: 3px;
            background: #dbe4dc;
            z-index: 1;
            transform: translateY(-50%);
        }
        .step-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            z-index: 2;
            text-decoration: none;
            color: inherit;
            flex: 1;
        }
        .step-circle {
            width: 2.25rem;
            height: 2.25rem;
            border-radius: 9999px;
            background: #fff;
            border: 3px solid #cbd5cd;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            color: #576d5c;
            font-size: 0.85rem;
            margin-bottom: 0.35rem;
            transition: all 0.2s;
        }
        .step-item.active .step-circle {
            border-color: #446850;
            color: #446850;
            box-shadow: 0 0 0 4px #eaf1eb;
        }
        .step-item.completed .step-circle {
            background: #446850;
            border-color: #446850;
            color: #fff;
        }
        .step-label {
            font-size: 0.72rem;
            font-weight: 600;
            color: var(--text-muted);
            text-align: center;
            line-height: 1.2;
        }
        .step-item.active .step-label {
            color: #2e4a36;
            font-weight: 700;
        }

        /* Buttons & Forms (Touch Friendly) */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.45rem;
            padding: 0.65rem 1.15rem;
            font-size: 0.88rem;
            font-weight: 600;
            border-radius: 0.5rem;
            border: 1px solid transparent;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.15s ease;
            min-height: 42px;
        }
        .btn-primary { background: #446850; color: #fff; }
        .btn-primary:hover { background: #35533f; }
        .btn-success { background: #2e6840; color: #fff; }
        .btn-success:hover { background: #235232; }
        .btn-danger { background: #dc2626; color: #fff; }
        .btn-danger:hover { background: #b91c1c; }
        .btn-secondary { background: #eef3ee; color: #2c3f31; border-color: #cbd8ce; }
        .btn-secondary:hover { background: #e1ebe2; }
        .btn-sm { padding: 0.4rem 0.75rem; font-size: 0.78rem; min-height: 36px; }

        .form-group {
            margin-bottom: 1.2rem;
        }
        .form-label {
            display: block;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 0.4rem;
            color: #2b3d30;
        }
        .form-control {
            width: 100%;
            padding: 0.7rem 0.85rem;
            font-size: 0.92rem;
            border: 1px solid #cbd8ce;
            border-radius: 0.5rem;
            background: #fff;
            color: var(--text-main);
            outline: none;
            transition: border-color 0.15s;
            min-height: 44px;
        }
        .form-control:focus {
            border-color: #446850;
            box-shadow: 0 0 0 3px #eaf1eb;
        }
        textarea.form-control { min-height: 6.5rem; resize: vertical; }
        .form-help {
            font-size: 0.75rem;
            color: var(--text-muted);
            margin-top: 0.35rem;
        }
        .form-error {
            font-size: 0.78rem;
            color: #dc2626;
            margin-top: 0.35rem;
        }

        /* Tables Responsive */
        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            border-radius: 0.5rem;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 0.88rem;
            min-width: 600px;
        }
        th {
            background: #f4f8f4;
            color: #425848;
            font-weight: 600;
            padding: 0.75rem 0.85rem;
            border-bottom: 1px solid var(--border);
            text-transform: uppercase;
            font-size: 0.72rem;
            letter-spacing: 0.05em;
        }
        td {
            padding: 0.85rem;
            border-bottom: 1px solid var(--border);
            vertical-align: middle;
        }
        tr:hover td { background: #f7faf7; }

        /* Tabs Scrollable on Mobile */
        .tab-nav {
            display: flex;
            gap: 0.5rem;
            border-bottom: 1px solid var(--border);
            margin-bottom: 1.25rem;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: none;
        }
        .tab-nav::-webkit-scrollbar { display: none; }
        .tab-btn {
            padding: 0.65rem 1rem;
            font-size: 0.85rem;
            font-weight: 600;
            border: none;
            background: none;
            cursor: pointer;
            color: var(--text-muted);
            border-bottom: 2px solid transparent;
            margin-bottom: -1px;
            white-space: nowrap;
            flex-shrink: 0;
        }
        .tab-btn.active {
            color: #446850;
            border-bottom-color: #446850;
            font-weight: 700;
        }
        .tab-content { display: none; }
        .tab-content.active { display: block; }

        /* Grid stats */
        .grid-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 0.85rem;
            margin-bottom: 1.25rem;
        }
        .stat-box {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 0.75rem;
            padding: 1.15rem;
        }
        .stat-box-title { font-size: 0.75rem; font-weight: 600; color: var(--text-muted); text-transform: uppercase; }
        .stat-box-value { font-size: 1.6rem; font-weight: 800; color: #1c2b20; margin-top: 0.2rem; }

        /* ==================================================== */
        /* MEDIA QUERIES UNTUK SMARTPHONE / TABLET (< 768px)    */
        /* ==================================================== */
        @media (max-width: 768px) {
            .sidebar {
                position: fixed;
                top: 0;
                bottom: 0;
                left: 0;
                transform: translateX(-100%);
                box-shadow: 0 0 25px rgba(0, 0, 0, 0.4);
            }
            .sidebar.open {
                transform: translateX(0);
            }
            .sidebar-backdrop.open {
                display: block;
                opacity: 1;
            }
            .menu-toggle-btn {
                display: flex;
            }
            .topbar {
                padding: 0.65rem 1rem;
            }
            .topbar-date {
                display: none;
            }
            .main-container {
                padding: 1rem 0.85rem 5.5rem; /* extra space at bottom for mobile bottom nav */
            }
            .bottom-nav {
                display: flex;
            }
            .grid-stats {
                grid-template-columns: repeat(2, 1fr);
                gap: 0.65rem;
            }
            .stat-box {
                padding: 0.85rem;
            }
            .stat-box-value {
                font-size: 1.35rem;
            }
            .card {
                padding: 1rem;
                border-radius: 0.75rem;
            }
            .stepper::before {
                left: 1rem;
                right: 1rem;
            }
            .step-circle {
                width: 2rem;
                height: 2rem;
                font-size: 0.78rem;
            }
            .step-label {
                font-size: 0.65rem;
            }
        }
    </style>
</head>
<body>
    @auth
        @php
            $user = auth()->user();
            $unreadCount = $user->unreadNotifikasiCount();
        @endphp
        <div class="layout-shell">
            <!-- Overlay Backdrop Mobile -->
            <div class="sidebar-backdrop" id="sidebar-backdrop" onclick="toggleSidebar(false)"></div>

            <!-- Sidebar (Desktop & Mobile Drawer) -->
            <aside class="sidebar" id="sidebar">
                <a href="{{ route('dashboard') }}" class="sidebar-brand">
                    <div class="brand-icon">FP</div>
                    <div>
                        <div class="brand-title">Portal Skripsi</div>
                        <div class="brand-subtitle">{{ $user->programStudi ? $user->programStudi->nama : 'Fakultas FPST' }}</div>
                    </div>
                </a>

                <div class="sidebar-menu">
                    <div class="menu-label">Navigasi Utama</div>
                    <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" onclick="toggleSidebar(false)">
                        <span>📊</span> Dashboard
                    </a>

                    <a href="{{ route('notifikasi.index') }}" class="nav-link {{ request()->routeIs('notifikasi.*') ? 'active' : '' }}" onclick="toggleSidebar(false)">
                        <span>🔔</span> Notifikasi
                        @if ($unreadCount > 0)
                            <span class="nav-badge">{{ $unreadCount }}</span>
                        @endif
                    </a>

                    @if ($user->isMahasiswa())
                        <div class="menu-label">Tahapan Skripsi</div>
                        <a href="{{ route('mahasiswa.skripsi.index') }}" class="nav-link {{ request()->routeIs('mahasiswa.skripsi.*') ? 'active' : '' }}" onclick="toggleSidebar(false)">
                            <span>📝</span> 1. Judul & SK
                        </a>
                        <a href="{{ route('mahasiswa.seminar.index') }}" class="nav-link {{ request()->routeIs('mahasiswa.seminar.*') ? 'active' : '' }}" onclick="toggleSidebar(false)">
                            <span>🎯</span> 2. Seminar
                        </a>
                        <a href="{{ route('mahasiswa.sidang.index') }}" class="nav-link {{ request()->routeIs('mahasiswa.sidang.*') ? 'active' : '' }}" onclick="toggleSidebar(false)">
                            <span>🎓</span> 3. Sidang Skripsi
                        </a>
                    @endif

                    @if ($user->isKaprodi())
                        <div class="menu-label">Menu Kaprodi</div>
                        <a href="{{ route('kaprodi.penetapan.index') }}" class="nav-link {{ request()->routeIs('kaprodi.*') ? 'active' : '' }}" onclick="toggleSidebar(false)">
                            <span>⚖️</span> Penetapan Pembimbing & Penguji
                        </a>
                    @endif

                    @if ($user->isAdmin())
                        <div class="menu-label">Menu Administrasi</div>
                        <a href="{{ route('admin.administrasi.index') }}" class="nav-link {{ request()->routeIs('admin.administrasi.*') ? 'active' : '' }}" onclick="toggleSidebar(false)">
                            <span>📂</span> Administrasi & Surat/SK
                        </a>
                        @if ($user->isAdminUtama())
                            <a href="{{ route('admin.log-aktivitas.index') }}" class="nav-link {{ request()->routeIs('admin.log-aktivitas.*') ? 'active' : '' }}" onclick="toggleSidebar(false)">
                                <span>📜</span> Log Aktivitas Sistem
                            </a>
                        @endif
                    @endif

                    @if ($user->isDosen())
                        <div class="menu-label">Menu Dosen</div>
                        <a href="{{ route('dosen.bimbingan.index') }}" class="nav-link {{ request()->routeIs('dosen.*') ? 'active' : '' }}" onclick="toggleSidebar(false)">
                            <span>👥</span> Bimbingan & Jadwal Uji
                        </a>
                    @endif
                </div>

                <div class="sidebar-footer">
                    <div class="user-info">
                        <div class="user-name">{{ $user->name }}</div>
                        <div class="user-role">{{ $user->role->label() }}</div>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="btn-logout">Keluar Akun</button>
                    </form>
                </div>
            </aside>

            <!-- Main Content Area -->
            <div class="content-area">
                <header class="topbar">
                    <div class="topbar-left">
                        <button type="button" class="menu-toggle-btn" onclick="toggleSidebar(true)" title="Menu">
                            ☰
                        </button>
                        <div class="topbar-title">@yield('page_title', 'Administrasi Skripsi')</div>
                    </div>

                    <div class="topbar-actions">
                        <a href="{{ route('notifikasi.index') }}" class="bell-btn" title="Notifikasi">
                            <span>🔔</span>
                            @if ($unreadCount > 0)
                                <span class="bell-badge">{{ $unreadCount }}</span>
                            @endif
                        </a>
                        <div class="topbar-date" style="font-size: 0.82rem; color: var(--text-muted);">
                            {{ now()->translatedFormat('d M Y') }}
                        </div>
                    </div>
                </header>

                <main class="main-container">
                    @if (session('success'))
                        <div class="alert alert-success">
                            <span>✅</span>
                            <div>{{ session('success') }}</div>
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="alert alert-error">
                            <span>❌</span>
                            <div>{{ session('error') }}</div>
                        </div>
                    @endif

                    @if (session('info'))
                        <div class="alert alert-info">
                            <span>ℹ️</span>
                            <div>{{ session('info') }}</div>
                        </div>
                    @endif

                    @yield('content')
                </main>
            </div>

            <!-- Bottom Navigation Bar Mobile (Khusus Smartphone) -->
            <nav class="bottom-nav">
                <a href="{{ route('dashboard') }}" class="bottom-nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <div class="bottom-nav-icon">📊</div>
                    <div>Beranda</div>
                </a>

                @if ($user->isMahasiswa())
                    <a href="{{ route('mahasiswa.skripsi.index') }}" class="bottom-nav-item {{ request()->routeIs('mahasiswa.skripsi.*') ? 'active' : '' }}">
                        <div class="bottom-nav-icon">📝</div>
                        <div>1. Judul</div>
                    </a>
                    <a href="{{ route('mahasiswa.seminar.index') }}" class="bottom-nav-item {{ request()->routeIs('mahasiswa.seminar.*') ? 'active' : '' }}">
                        <div class="bottom-nav-icon">🎯</div>
                        <div>2. Seminar</div>
                    </a>
                    <a href="{{ route('mahasiswa.sidang.index') }}" class="bottom-nav-item {{ request()->routeIs('mahasiswa.sidang.*') ? 'active' : '' }}">
                        <div class="bottom-nav-icon">🎓</div>
                        <div>3. Sidang</div>
                    </a>
                @elseif ($user->isKaprodi())
                    <a href="{{ route('kaprodi.penetapan.index') }}" class="bottom-nav-item {{ request()->routeIs('kaprodi.*') ? 'active' : '' }}">
                        <div class="bottom-nav-icon">⚖️</div>
                        <div>Penetapan</div>
                    </a>
                    <a href="{{ route('dosen.bimbingan.index') }}" class="bottom-nav-item {{ request()->routeIs('dosen.*') ? 'active' : '' }}">
                        <div class="bottom-nav-icon">👥</div>
                        <div>Bimbingan</div>
                    </a>
                @elseif ($user->isAdmin())
                    <a href="{{ route('admin.administrasi.index') }}" class="bottom-nav-item {{ request()->routeIs('admin.administrasi.*') ? 'active' : '' }}">
                        <div class="bottom-nav-icon">📂</div>
                        <div>Kelola SK</div>
                    </a>
                    @if ($user->isAdminUtama())
                        <a href="{{ route('admin.log-aktivitas.index') }}" class="bottom-nav-item {{ request()->routeIs('admin.log-aktivitas.*') ? 'active' : '' }}">
                            <div class="bottom-nav-icon">📜</div>
                            <div>Log</div>
                        </a>
                    @endif
                @elseif ($user->isDosen())
                    <a href="{{ route('dosen.bimbingan.index') }}" class="bottom-nav-item {{ request()->routeIs('dosen.*') ? 'active' : '' }}">
                        <div class="bottom-nav-icon">👥</div>
                        <div>Bimbingan</div>
                    </a>
                @endif

                <a href="{{ route('notifikasi.index') }}" class="bottom-nav-item {{ request()->routeIs('notifikasi.*') ? 'active' : '' }}">
                    <div class="bottom-nav-icon">🔔</div>
                    <div>Notif</div>
                    @if ($unreadCount > 0)
                        <span class="bottom-nav-badge">{{ $unreadCount }}</span>
                    @endif
                </a>
            </nav>
        </div>
    @else
        <div style="min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 1rem; background: var(--canvas);">
            @yield('content')
        </div>
    @endauth

    <script>
        // Toggle Sidebar Mobile
        function toggleSidebar(open) {
            const sidebar = document.getElementById('sidebar');
            const backdrop = document.getElementById('sidebar-backdrop');
            if (!sidebar || !backdrop) return;
            if (open) {
                sidebar.classList.add('open');
                backdrop.classList.add('open');
            } else {
                sidebar.classList.remove('open');
                backdrop.classList.remove('open');
            }
        }

        // Tab switcher helper
        function switchTab(evt, tabId) {
            const container = evt.currentTarget.closest('.card') || document;
            container.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            container.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
            evt.currentTarget.classList.add('active');
            const target = container.querySelector('#' + tabId);
            if (target) target.classList.add('active');
        }
    </script>
</body>
</html>
