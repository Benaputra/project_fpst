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
            .topbar__inner, .page { width: min(100% - 2rem, 72rem); margin-inline: auto; }
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
            @media (max-width: 700px) {
                .meta { grid-template-columns: 1fr; }
                .card__header { align-items: flex-start; flex-direction: column; }
                .toolbar { grid-template-columns: 1fr; }
                .form-grid, .search-row { grid-template-columns: 1fr; }
                .pagination { align-items: flex-start; flex-direction: column; }
            }
        </style>
    </head>
    <body>
        <header class="topbar">
            <div class="topbar__inner">
                <div class="brand">Administrasi Skripsi</div>
                @auth
                    <div style="align-items: center; display: flex; gap: .8rem;">
                        <div class="user-chip">{{ auth()->user()->name }}</div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" style="background: transparent; border: 1px solid #8ca5bf; border-radius: .5rem; color: #fff; cursor: pointer; padding: .4rem .65rem;">Keluar</button>
                        </form>
                    </div>
                @endauth
            </div>
        </header>
        <main class="page">
            @yield('content')
        </main>
    </body>
</html>
