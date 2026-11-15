<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Portal Jadwal Publik Seminar dan Sidang Skripsi Fakultas Pertanian, Sains dan Teknologi Universitas Panca Bhakti">
    <title>Jadwal Seminar &amp; Sidang Skripsi - FPST UPB</title>
    <style>
        :root {
            --bg-cream: #f8f7f3;
            --bg-sage: #f1f5f1;
            --surface: #ffffff;
            --primary: #446850;
            --primary-dark: #2f4b39;
            --primary-light: #e5eee7;
            --ink: #16241b;
            --muted: #55655b;
            --border: #d8e2db;
            --radius: 0.9rem;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: var(--bg-cream);
            color: var(--ink);
            line-height: 1.55;
        }
        .navbar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(8px);
            border-bottom: 1px solid var(--border);
            position: sticky;
            top: 0;
            z-index: 20;
        }
        .container {
            max-width: 1180px;
            margin: 0 auto;
            padding: 0 1.25rem;
        }
        .navbar-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 0.85rem 0;
        }
        .brand {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            text-decoration: none;
            color: inherit;
        }
        .brand img { width: 40px; height: auto; }
        .brand-title { font-weight: 700; font-size: 0.98rem; color: var(--ink); }
        .brand-sub { font-size: 0.76rem; color: var(--muted); }
        .nav-actions { display: flex; gap: 0.65rem; align-items: center; }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.52rem 0.95rem;
            border-radius: 0.6rem;
            font-size: 0.85rem;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            border: 1px solid transparent;
            transition: all 0.15s ease;
        }
        .btn-primary { background: var(--primary); color: #fff; }
        .btn-primary:hover { background: var(--primary-dark); }
        .btn-outline { background: #fff; color: var(--ink); border-color: var(--border); }
        .btn-outline:hover { background: var(--bg-sage); }
        .hero-band {
            background: linear-gradient(135deg, #213729 0%, #375842 100%);
            color: #fff;
            padding: 2.35rem 0 2rem;
        }
        .hero-pill {
            display: inline-block;
            background: rgba(255,255,255,0.14);
            border: 1px solid rgba(255,255,255,0.22);
            color: #e4f0e6;
            padding: 0.28rem 0.75rem;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-bottom: 0.75rem;
        }
        .hero-band h1 {
            font-size: clamp(1.45rem, 3vw, 2.05rem);
            margin-bottom: 0.45rem;
            line-height: 1.25;
        }
        .hero-band p {
            color: rgba(255,255,255,0.85);
            font-size: 0.95rem;
            max-width: 720px;
        }
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(175px, 1fr));
            gap: 0.9rem;
            margin-top: -1.4rem;
            margin-bottom: 1.4rem;
        }
        .stat-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 0.95rem 1.1rem;
            box-shadow: 0 4px 14px rgba(22, 36, 27, 0.05);
        }
        .stat-label { font-size: 0.78rem; color: var(--muted); font-weight: 600; }
        .stat-num { font-size: 1.55rem; font-weight: 700; color: var(--primary-dark); margin-top: 0.15rem; }
        .filter-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 1rem 1.15rem;
            margin-bottom: 1.35rem;
        }
        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 0.75rem;
            align-items: end;
        }
        .filter-label {
            display: block;
            font-size: 0.76rem;
            font-weight: 600;
            color: var(--muted);
            margin-bottom: 0.3rem;
        }
        .form-control {
            width: 100%;
            height: 39px;
            padding: 0 0.75rem;
            border: 1px solid var(--border);
            border-radius: 0.55rem;
            font-size: 0.86rem;
            background: #fff;
            color: var(--ink);
        }
        .schedule-list {
            display: grid;
            gap: 0.95rem;
            margin-bottom: 2.5rem;
        }
        .schedule-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 1.15rem 1.25rem;
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 1rem;
            align-items: start;
        }
        .badges { display: flex; flex-wrap: wrap; gap: 0.45rem; margin-bottom: 0.55rem; }
        .badge {
            display: inline-flex;
            align-items: center;
            padding: 0.24rem 0.65rem;
            border-radius: 999px;
            font-size: 0.73rem;
            font-weight: 700;
        }
        .badge-seminar { background: #e8f3eb; color: #245234; border: 1px solid #c3decb; }
        .badge-sidang { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
        .badge-prodi { background: #f1f5f9; color: #334155; border: 1px solid #cbd5e1; }
        .badge-mendatang { background: #dcfce7; color: #166534; }
        .badge-selesai { background: #e5e7eb; color: #374151; }
        .judul { font-size: 1.03rem; font-weight: 700; color: var(--ink); margin-bottom: 0.45rem; }
        .meta-line { font-size: 0.85rem; color: var(--muted); display: flex; flex-wrap: wrap; gap: 0.4rem 1.15rem; }
        .meta-line strong { color: var(--ink); }
        .right-meta {
            text-align: right;
            min-width: 210px;
            background: var(--bg-sage);
            border: 1px solid var(--border);
            border-radius: 0.7rem;
            padding: 0.75rem 0.9rem;
            font-size: 0.82rem;
        }
        .right-meta .date { font-weight: 700; color: var(--primary-dark); font-size: 0.9rem; }
        .empty-state {
            background: var(--surface);
            border: 1px dashed var(--border);
            border-radius: var(--radius);
            padding: 2.5rem 1.25rem;
            text-align: center;
            color: var(--muted);
            margin-bottom: 2.5rem;
        }
        .footer {
            border-top: 1px solid var(--border);
            padding: 1.25rem 0;
            font-size: 0.82rem;
            color: var(--muted);
            text-align: center;
        }
        @media (max-width: 768px) {
            .schedule-card { grid-template-columns: 1fr; }
            .right-meta { text-align: left; }
        }
    </style>
</head>
<body>
    <header class="navbar">
        <div class="container navbar-inner">
            <a href="{{ route('landing.preview') }}" class="brand">
                <img src="{{ asset('images/logo.png') }}" alt="Logo FPST Universitas Panca Bhakti">
                <div>
                    <div class="brand-title">Fakultas Pertanian, Sains dan Teknologi</div>
                    <div class="brand-sub">Universitas Panca Bhakti • Portal Jadwal Akademik Publik</div>
                </div>
            </a>
            <div class="nav-actions">
                <a href="{{ route('landing.preview') }}" class="btn btn-outline">Beranda</a>
                @auth
                    <a href="{{ route('dashboard') }}" class="btn btn-primary">Dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="btn btn-primary">Login Civitas</a>
                @endauth
            </div>
        </div>
    </header>

    <section class="hero-band">
        <div class="container">
            <span class="hero-pill">Transparansi Akademik FPST • Agroteknologi &amp; Agribisnis</span>
            <h1>Jadwal Terbuka Seminar Skripsi &amp; Sidang Skripsi</h1>
            <p>
                Informasi jadwal Seminar Skripsi dan Sidang Skripsi resmi di lingkungan Fakultas Pertanian, Sains dan Teknologi (FPST) Universitas Panca Bhakti. Data pribadi sensitif mahasiswa dilindungi dan hanya informasi agenda publik yang ditampilkan.
            </p>
        </div>
    </section>

    <main class="container">
        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-label">Total Agenda Terjadwal</div>
                <div class="stat-num">{{ $summary['total'] }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Agenda Mendatang</div>
                <div class="stat-num">{{ $summary['mendatang'] }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Seminar Skripsi</div>
                <div class="stat-num">{{ $summary['seminar'] }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Sidang Skripsi</div>
                <div class="stat-num">{{ $summary['sidang'] }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Seminar PL</div>
                <div class="stat-num">{{ $summary['pl'] ?? 0 }}</div>
            </div>
        </div>

        <form method="GET" action="{{ route('jadwal.publik') }}" class="filter-card">
            <div class="filter-grid">
                <div>
                    <label class="filter-label" for="filter-jenis">Jenis Agenda</label>
                    <select id="filter-jenis" name="jenis" class="form-control">
                        <option value="all" {{ $jenis === 'all' ? 'selected' : '' }}>Semua Agenda</option>
                        <option value="seminar" {{ $jenis === 'seminar' ? 'selected' : '' }}>Seminar Skripsi</option>
                        <option value="sidang" {{ $jenis === 'sidang' ? 'selected' : '' }}>Sidang Skripsi</option>
                        <option value="pl" {{ $jenis === 'pl' ? 'selected' : '' }}>Seminar Praktik Lapang</option>
                    </select>
                </div>
                <div>
                    <label class="filter-label" for="filter-prodi">Program Studi</label>
                    <select id="filter-prodi" name="prodi_id" class="form-control">
                        <option value="">Semua Program Studi (AGT &amp; AGB)</option>
                        @foreach ($daftarProdi as $prodi)
                            <option value="{{ $prodi->id }}" {{ (int) $prodiId === (int) $prodi->id ? 'selected' : '' }}>
                                {{ $prodi->nama }} ({{ $prodi->kode }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="filter-label" for="filter-periode">Periode</label>
                    <select id="filter-periode" name="periode" class="form-control">
                        <option value="semua" {{ $periode === 'semua' ? 'selected' : '' }}>Semua Jadwal</option>
                        <option value="mendatang" {{ $periode === 'mendatang' ? 'selected' : '' }}>Hanya Jadwal Mendatang</option>
                    </select>
                </div>
                <div>
                    <label class="filter-label" for="filter-search">Cari Nama / Judul / Ruang</label>
                    <input id="filter-search" type="text" name="search" value="{{ $search }}" class="form-control" placeholder="Ketik kata kunci...">
                </div>
                <div style="display: flex; gap: 0.5rem;">
                    <button type="submit" class="btn btn-primary" style="height: 39px; flex: 1; justify-content: center;">Filter Jadwal</button>
                    @if ($jenis !== 'all' || $prodiId || $periode !== 'semua' || $search)
                        <a href="{{ route('jadwal.publik') }}" class="btn btn-outline" style="height: 39px;">Reset</a>
                    @endif
                </div>
            </div>
        </form>

        @if ($daftarJadwal->isEmpty())
            <div class="empty-state">
                <p style="font-weight: 700; color: var(--ink); margin-bottom: 0.25rem;">Belum ada jadwal yang sesuai filter.</p>
                <p style="font-size: 0.88rem;">Silakan ubah filter Program Studi / Periode atau cek kembali setelah jadwal ditetapkan oleh Admin Prodi.</p>
            </div>
        @else
            <div class="schedule-list">
                @foreach ($daftarJadwal as $item)
                    <article class="schedule-card">
                        <div>
                            <div class="badges">
                                <span class="badge {{ $item['jenis'] === 'sidang' ? 'badge-sidang' : 'badge-seminar' }}">
                                    {{ $item['jenis_label'] }}
                                </span>
                                <span class="badge badge-prodi">
                                    {{ $item['prodi_nama'] }} ({{ $item['prodi_kode'] }})
                                </span>
                                <span class="badge {{ $item['is_mendatang'] ? 'badge-mendatang' : 'badge-selesai' }}">
                                    {{ $item['is_mendatang'] ? 'Akan Datang' : $item['status_label'] }}
                                </span>
                            </div>
                            <h2 class="judul">{{ $item['judul'] }}</h2>
                            <div class="meta-line">
                                <span>Penyaji: <strong>{{ $item['mahasiswa_nama'] }}</strong></span>
                                @if (!empty($item['pembimbing']))
                                    <span>Pembimbing: <strong>{{ implode(' / ', $item['pembimbing']) }}</strong></span>
                                @endif
                                @if (!empty($item['penguji']))
                                    <span>Penguji: <strong>{{ implode(' / ', $item['penguji']) }}</strong></span>
                                @endif
                            </div>
                        </div>
                        <div class="right-meta">
                            <div class="date">📅 {{ $item['tanggal_label'] }}</div>
                            <div style="margin-top: 0.25rem;">⏰ {{ $item['jam'] }}</div>
                            <div style="margin-top: 0.2rem;">📍 {{ $item['ruangan'] }}</div>
                            @if ($item['jenis'] === 'seminar' && isset($item['sisa_penonton']))
                                <div style="margin-top: 0.45rem; padding-top: 0.45rem; border-top: 1px dashed var(--border); font-size: 0.76rem; color: var(--primary-dark);">
                                    🪑 Sisa Kursi Audiens: <strong>{{ $item['sisa_penonton'] }}/{{ $item['kuota_penonton'] }}</strong>
                                    <div>🎙️ Slot Penyanggah: <strong>{{ $item['sisa_penyanggah'] }}</strong></div>
                                </div>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </main>

    <footer class="footer">
        <div class="container">
            Fakultas Pertanian, Sains dan Teknologi (FPST) • Universitas Panca Bhakti • Data publik dilindungi dengan prinsip privasi akademik.
        </div>
    </footer>
</body>
</html>
