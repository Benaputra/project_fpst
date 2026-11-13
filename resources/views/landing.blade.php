<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Portal Administrasi Skripsi Fakultas Pertanian, Sains dan Teknologi (FPST) Universitas Panca Bhakti. Pengajuan judul, penerbitan SK Bimbingan, Seminar Skripsi, hingga Sidang Skripsi.">
    <title>Portal Skripsi — Fakultas Pertanian, Sains dan Teknologi</title>

    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/logo.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary: #446850;
            --primary-hover: #35533f;
            --primary-light: #eaf0eb;
            --primary-border: #b8ccbe;
            --forest-dark: #18241c;
            --forest-mid: #223328;
            --canvas: #f3f6f3;
            --card-bg: #ffffff;
            --text-main: #142017;
            --text-muted: #576d5c;
            --border: #dbe4dc;
            --accent-gold: #c79a3b;
        }

        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background-color: var(--canvas);
            color: var(--text-main);
            line-height: 1.6;
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
        }

        .container {
            width: 100%;
            max-width: 1180px;
            margin: 0 auto;
            padding: 0 1.25rem;
        }

        /* Navbar */
        .navbar {
            position: sticky;
            top: 0;
            z-index: 50;
            background: rgba(243, 246, 243, 0.88);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border);
        }

        .navbar-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            min-height: 74px;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            text-decoration: none;
            color: inherit;
            min-width: 0;
        }

        .brand-logo {
            width: 44px;
            height: 44px;
            object-fit: contain;
            flex-shrink: 0;
            filter: drop-shadow(0 2px 6px rgba(20, 32, 23, 0.14));
        }

        .brand-text {
            display: flex;
            flex-direction: column;
            min-width: 0;
        }

        .brand-name {
            font-size: 0.94rem;
            font-weight: 800;
            color: var(--text-main);
            line-height: 1.2;
            letter-spacing: -0.01em;
        }

        .brand-sub {
            font-size: 0.72rem;
            font-weight: 600;
            color: var(--primary);
            line-height: 1.25;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .nav-link {
            text-decoration: none;
            font-size: 0.88rem;
            font-weight: 600;
            color: var(--text-muted);
            transition: color 0.18s ease;
        }

        .nav-link:hover {
            color: var(--primary);
        }

        .nav-actions {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            flex-shrink: 0;
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.7rem 1.3rem;
            border-radius: 0.65rem;
            font-size: 0.9rem;
            font-weight: 700;
            text-decoration: none;
            border: 1px solid transparent;
            cursor: pointer;
            transition: transform 0.18s ease, background-color 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease;
            min-height: 44px;
            white-space: nowrap;
        }

        .btn-primary {
            background: var(--primary);
            color: #ffffff;
            box-shadow: 0 6px 18px rgba(68, 104, 80, 0.24);
        }

        .btn-primary:hover {
            background: var(--primary-hover);
            transform: translateY(-1px);
            box-shadow: 0 10px 22px rgba(53, 83, 63, 0.28);
        }

        .btn-outline {
            background: #ffffff;
            color: var(--text-main);
            border-color: var(--primary-border);
        }

        .btn-outline:hover {
            background: var(--primary-light);
            border-color: var(--primary);
        }

        .btn-light {
            background: #ffffff;
            color: var(--forest-dark);
        }

        .btn-light:hover {
            background: var(--primary-light);
            transform: translateY(-1px);
        }

        /* Scroll Fade In / Out Animation */
        .scroll-fade {
            opacity: 0;
            transform: translateY(24px);
            transition: opacity 0.6s cubic-bezier(0.22, 1, 0.36, 1), transform 0.6s cubic-bezier(0.22, 1, 0.36, 1);
        }

        .scroll-fade.is-visible {
            opacity: 1;
            transform: translateY(0);
        }

        .delay-1 { transition-delay: 0.08s; }
        .delay-2 { transition-delay: 0.16s; }
        .delay-3 { transition-delay: 0.24s; }

        @media (prefers-reduced-motion: reduce) {
            html { scroll-behavior: auto; }
            .scroll-fade {
                opacity: 1 !important;
                transform: none !important;
                transition: none !important;
            }
        }

        /* Hero Section */
        .hero {
            padding: 3.5rem 0 4rem;
            position: relative;
        }

        .hero-grid {
            display: grid;
            grid-template-columns: 1.05fr 0.95fr;
            gap: 2.75rem;
            align-items: center;
        }

        .hero-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: var(--primary-light);
            color: var(--primary-hover);
            border: 1px solid var(--primary-border);
            padding: 0.4rem 0.9rem;
            border-radius: 999px;
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 0.01em;
            margin-bottom: 1.25rem;
        }

        .hero-pill-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--primary);
        }

        .hero-title {
            font-size: clamp(2rem, 3.6vw, 3rem);
            font-weight: 800;
            line-height: 1.15;
            letter-spacing: -0.025em;
            color: var(--text-main);
            margin-bottom: 1rem;
        }

        .hero-title span {
            color: var(--primary);
        }

        .hero-desc {
            font-size: 1.02rem;
            color: var(--text-muted);
            max-width: 560px;
            margin-bottom: 1.75rem;
        }

        .hero-cta {
            display: flex;
            flex-wrap: wrap;
            gap: 0.85rem;
            margin-bottom: 2.25rem;
        }

        .hero-metrics {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 1rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--border);
        }

        .metric-value {
            font-size: 1.35rem;
            font-weight: 800;
            color: var(--forest-dark);
            line-height: 1.2;
        }

        .metric-label {
            font-size: 0.78rem;
            color: var(--text-muted);
            font-weight: 600;
            margin-top: 0.15rem;
        }

        /* Hero Campus Image Card */
        .campus-card {
            position: relative;
            background: var(--card-bg);
            border-radius: 1.25rem;
            padding: 0.65rem;
            border: 1px solid var(--border);
            box-shadow: 0 22px 45px rgba(24, 36, 28, 0.1);
        }

        .campus-frame {
            position: relative;
            border-radius: 0.95rem;
            overflow: hidden;
            aspect-ratio: 4 / 3;
            background: linear-gradient(110deg, #dde7df 8%, #eaf0eb 18%, #dde7df 33%);
            background-size: 200% 100%;
            animation: shimmer 1.6s linear infinite;
        }

        .campus-frame.loaded {
            animation: none;
            background: var(--forest-dark);
        }

        @keyframes shimmer {
            to { background-position-x: -200%; }
        }

        .campus-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            transition: transform 0.5s ease, opacity 0.4s ease;
        }

        .campus-card:hover .campus-img {
            transform: scale(1.02);
        }

        .campus-overlay {
            position: absolute;
            inset: auto 0 0 0;
            padding: 1.25rem;
            background: linear-gradient(to top, rgba(24, 36, 28, 0.88) 0%, rgba(24, 36, 28, 0.45) 60%, transparent 100%);
            color: #ffffff;
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 0.75rem;
        }

        .campus-caption-title {
            font-size: 0.95rem;
            font-weight: 700;
            line-height: 1.3;
        }

        .campus-caption-sub {
            font-size: 0.76rem;
            color: #d1ded4;
        }

        .campus-badge {
            background: rgba(255, 255, 255, 0.16);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.25);
            color: #ffffff;
            padding: 0.35rem 0.7rem;
            border-radius: 999px;
            font-size: 0.72rem;
            font-weight: 700;
            flex-shrink: 0;
        }

        /* Section Common */
        .section {
            padding: 4.25rem 0;
        }

        .section-white {
            background: #ffffff;
            border-top: 1px solid var(--border);
            border-bottom: 1px solid var(--border);
        }

        .section-header {
            text-align: center;
            max-width: 680px;
            margin: 0 auto 3rem;
        }

        .section-eyebrow {
            display: inline-block;
            font-size: 0.76rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }

        .section-title {
            font-size: clamp(1.55rem, 2.6vw, 2.15rem);
            font-weight: 800;
            color: var(--text-main);
            letter-spacing: -0.02em;
            line-height: 1.25;
            margin-bottom: 0.75rem;
        }

        .section-subtitle {
            font-size: 0.95rem;
            color: var(--text-muted);
        }

        /* Flowchart Alur Pelaksanaan */
        .flow-legend {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 0.75rem 1.25rem;
            margin-bottom: 1.75rem;
            padding: 0.85rem 1.25rem;
            background: var(--canvas);
            border: 1px solid var(--border);
            border-radius: 0.85rem;
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--text-muted);
        }

        .legend-item {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
        }

        .legend-dot {
            width: 12px;
            height: 12px;
            border-radius: 4px;
            display: inline-block;
            flex-shrink: 0;
        }

        .legend-dot.process { background: var(--primary); }
        .legend-dot.decision { background: #d97706; transform: rotate(45deg); border-radius: 2px; width: 10px; height: 10px; }
        .legend-dot.reject { background: #dc2626; }
        .legend-dot.done { background: #166534; }

        .flow-tabs {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 0.5rem;
            margin-bottom: 2rem;
        }

        .flow-tab-btn {
            padding: 0.55rem 1.05rem;
            border-radius: 999px;
            border: 1px solid var(--border);
            background: #ffffff;
            color: var(--text-muted);
            font-family: inherit;
            font-size: 0.84rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.18s ease;
        }

        .flow-tab-btn:hover {
            border-color: var(--primary);
            color: var(--primary);
            background: var(--primary-light);
        }

        .flow-tab-btn.active {
            background: var(--primary);
            border-color: var(--primary);
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(68, 104, 80, 0.22);
        }

        .flow-lanes {
            display: flex;
            flex-direction: column;
            gap: 1.75rem;
        }

        .flow-lane {
            background: var(--canvas);
            border: 1px solid var(--border);
            border-radius: 1.15rem;
            padding: 1.5rem;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .flow-lane:hover {
            border-color: var(--primary-border);
            box-shadow: 0 14px 30px rgba(24, 36, 28, 0.06);
        }

        .flow-lane.is-hidden {
            display: none;
        }

        .flow-lane-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 0.75rem;
            margin-bottom: 1.35rem;
            padding-bottom: 0.9rem;
            border-bottom: 1px dashed var(--primary-border);
        }

        .flow-lane-title-wrap {
            display: flex;
            align-items: center;
            gap: 0.85rem;
        }

        .flow-number {
            width: 42px;
            height: 42px;
            border-radius: 0.8rem;
            background: var(--primary);
            color: #ffffff;
            font-size: 1rem;
            font-weight: 800;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            box-shadow: 0 6px 14px rgba(68, 104, 80, 0.24);
        }

        .flow-phase {
            font-size: 0.72rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: var(--primary);
        }

        .flow-title {
            font-size: 1.15rem;
            font-weight: 800;
            color: var(--text-main);
            line-height: 1.25;
        }

        .flow-gate-pill {
            font-size: 0.76rem;
            font-weight: 700;
            color: var(--primary-hover);
            background: var(--primary-light);
            border: 1px solid var(--primary-border);
            padding: 0.35rem 0.75rem;
            border-radius: 999px;
        }

        /* Flowchart Track (4 Columns Desktop -> Vertical Mobile) */
        .fc-track {
            display: grid;
            grid-template-columns: 1fr auto 1fr auto 1fr auto 1fr;
            gap: 0.5rem;
            align-items: start;
        }

        .fc-col {
            display: flex;
            flex-direction: column;
            gap: 0.65rem;
            min-width: 0;
        }

        .fc-node {
            background: #ffffff;
            border: 1.5px solid var(--border);
            border-radius: 0.85rem;
            padding: 0.95rem 1rem;
            position: relative;
            min-height: 164px;
            display: flex;
            flex-direction: column;
            gap: 0.38rem;
        }

        .fc-node-start {
            border-color: var(--primary);
            background: #ffffff;
            box-shadow: 0 3px 10px rgba(68, 104, 80, 0.08);
        }

        .fc-node-decision {
            border-color: #eab308;
            background: #fffdf5;
        }

        .fc-node-done {
            border-color: #2e6840;
            background: #f0fdf4;
        }

        .fc-node-tag {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            align-self: flex-start;
            font-size: 0.67rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            padding: 0.18rem 0.55rem;
            border-radius: 999px;
            white-space: nowrap;
        }

        .tag-input { background: var(--primary-light); color: var(--primary-hover); }
        .tag-decision { background: #fef3c7; color: #92400e; }
        .tag-done { background: #dcfce7; color: #166534; }

        .fc-node-title {
            font-size: 0.88rem;
            font-weight: 800;
            color: var(--text-main);
            line-height: 1.3;
        }

        .fc-node-desc {
            font-size: 0.77rem;
            color: var(--text-muted);
            line-height: 1.45;
        }

        .fc-arrow {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: var(--primary);
            padding: 0 0.1rem;
            align-self: start;
            margin-top: 62px;
        }

        .fc-arrow-badge {
            font-size: 0.65rem;
            font-weight: 800;
            color: #166534;
            background: #dcfce7;
            border: 1px solid #bbf7d0;
            padding: 0.12rem 0.45rem;
            border-radius: 999px;
            margin-bottom: 0.25rem;
            white-space: nowrap;
        }

        .fc-arrow-icon-v {
            display: none;
        }

        /* Rejection / Revision Branch Box */
        .fc-branch-reject {
            position: relative;
            background: #fef2f2;
            border: 1.5px dashed #fca5a5;
            border-radius: 0.75rem;
            padding: 0.7rem 0.85rem;
            font-size: 0.75rem;
            color: #991b1b;
            line-height: 1.42;
            margin-top: 0.2rem;
        }

        .fc-branch-reject::before {
            content: '↓ Jika Ditolak';
            display: inline-block;
            font-size: 0.66rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            background: #fee2e2;
            color: #b91c1c;
            border: 1px solid #fecaca;
            padding: 0.1rem 0.45rem;
            border-radius: 999px;
            margin-bottom: 0.35rem;
        }

        .fc-branch-reject strong {
            display: block;
            color: #7f1d1d;
            font-weight: 800;
            margin-bottom: 0.12rem;
        }

        .fc-loop-hint {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            margin-top: 0.3rem;
            font-size: 0.7rem;
            font-weight: 800;
            color: #b91c1c;
        }

        .fc-branch-warn {
            background: #fffbeb;
            border-color: #fcd34d;
            color: #92400e;
        }

        .fc-branch-warn::before {
            content: '↓ Jika Dosen Menolak';
            background: #fef3c7;
            color: #92400e;
            border-color: #fde68a;
        }

        .fc-branch-warn strong {
            color: #78350f;
        }

        .fc-branch-warn .fc-loop-hint {
            color: #b45309;
        }

        /* Macro Connector Banner */
        .macro-flow {
            display: flex;
            align-items: center;
            justify-content: center;
            flex-wrap: wrap;
            gap: 0.65rem;
            margin-bottom: 1.5rem;
        }

        .macro-step {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            padding: 0.45rem 0.9rem;
            border-radius: 0.65rem;
            background: #ffffff;
            border: 1px solid var(--border);
            font-size: 0.8rem;
            font-weight: 700;
            color: var(--text-main);
        }

        .macro-step-num {
            width: 22px;
            height: 22px;
            border-radius: 999px;
            background: var(--primary);
            color: #ffffff;
            font-size: 0.72rem;
            font-weight: 800;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .macro-step.macro-finish {
            background: #dcfce7;
            border-color: #bbf7d0;
            color: #166534;
        }

        .macro-sep {
            color: var(--primary);
            font-weight: 800;
            font-size: 0.95rem;
        }

        /* Program Studi & Roles Grid */
        .prodi-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1.5rem;
            margin-bottom: 2.5rem;
        }

        .prodi-card {
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: 1rem;
            padding: 1.65rem;
            box-shadow: 0 2px 8px rgba(24, 36, 28, 0.04);
            display: flex;
            flex-direction: column;
            gap: 0.65rem;
        }

        .prodi-code {
            display: inline-flex;
            align-items: center;
            align-self: flex-start;
            padding: 0.25rem 0.65rem;
            border-radius: 999px;
            background: var(--primary-light);
            color: var(--primary-hover);
            border: 1px solid var(--primary-border);
            font-size: 0.74rem;
            font-weight: 800;
            letter-spacing: 0.04em;
        }

        .prodi-name {
            font-size: 1.2rem;
            font-weight: 800;
            color: var(--text-main);
        }

        .prodi-desc {
            font-size: 0.88rem;
            color: var(--text-muted);
        }

        .role-strip {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 1rem;
        }

        .role-box {
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: 0.85rem;
            padding: 1.15rem;
        }

        .role-box-title {
            font-size: 0.92rem;
            font-weight: 800;
            color: var(--text-main);
            margin-bottom: 0.3rem;
        }

        .role-box-desc {
            font-size: 0.8rem;
            color: var(--text-muted);
            line-height: 1.45;
        }

        /* CTA Banner */
        .cta-banner {
            background: linear-gradient(135deg, var(--forest-dark) 0%, var(--primary-hover) 55%, var(--primary) 100%);
            border-radius: 1.35rem;
            padding: 2.75rem 2.25rem;
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 2rem;
            flex-wrap: wrap;
            box-shadow: 0 22px 45px rgba(24, 36, 28, 0.16);
        }

        .cta-left {
            display: flex;
            align-items: center;
            gap: 1.25rem;
            max-width: 650px;
        }

        .cta-logo {
            width: 68px;
            height: 68px;
            object-fit: contain;
            flex-shrink: 0;
            filter: drop-shadow(0 6px 14px rgba(0, 0, 0, 0.3));
        }

        .cta-title {
            font-size: clamp(1.3rem, 2.2vw, 1.75rem);
            font-weight: 800;
            line-height: 1.25;
            margin-bottom: 0.35rem;
        }

        .cta-desc {
            font-size: 0.92rem;
            color: #dbe6dd;
        }

        /* Footer */
        .footer {
            background: var(--forest-dark);
            color: #cbd8ce;
            padding: 2.25rem 0;
            border-top: 1px solid var(--forest-mid);
        }

        .footer-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1.25rem;
        }

        .footer-brand {
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }

        .footer-logo {
            width: 38px;
            height: 38px;
            object-fit: contain;
        }

        .footer-title {
            font-size: 0.9rem;
            font-weight: 700;
            color: #ffffff;
        }

        .footer-sub {
            font-size: 0.76rem;
            color: #9cb1a0;
        }

        .footer-links {
            display: flex;
            align-items: center;
            gap: 1.25rem;
            flex-wrap: wrap;
            font-size: 0.82rem;
        }

        .footer-links a {
            color: #cbd8ce;
            text-decoration: none;
        }

        .footer-links a:hover {
            color: #ffffff;
        }

        /* Responsive */
        @media (max-width: 960px) {
            .hero-grid {
                grid-template-columns: 1fr;
                gap: 2rem;
            }
            .fc-track {
                grid-template-columns: 1fr;
                gap: 0.45rem;
            }
            .fc-arrow {
                padding: 0.2rem 0;
                align-self: center;
                margin-top: 0;
            }
            .fc-node {
                min-height: auto;
            }
            .fc-arrow-icon-h {
                display: none;
            }
            .fc-arrow-icon-v {
                display: block;
            }
            .role-strip {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 720px) {
            .nav-links {
                display: none;
            }
            .prodi-grid,
            .role-strip {
                grid-template-columns: 1fr;
            }
            .hero {
                padding: 2.25rem 0 3rem;
            }
            .section {
                padding: 3rem 0;
            }
            .cta-left {
                flex-direction: column;
                align-items: flex-start;
            }
            .hero-metrics {
                grid-template-columns: repeat(3, minmax(0, 1fr));
                gap: 0.65rem;
            }
            .metric-value {
                font-size: 1.1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Sticky Header -->
    <header class="navbar">
        <div class="container navbar-inner">
            <a href="#beranda" class="brand">
                <picture>
                    <source type="image/webp" srcset="{{ asset('images/optimized/logo-64.webp') }}">
                    <img
                        src="{{ asset('images/logo.png') }}"
                        alt="Logo Fakultas Pertanian, Sains dan Teknologi"
                        class="brand-logo"
                        width="44"
                        height="43"
                        loading="lazy"
                        decoding="async"
                    >
                </picture>
                <div class="brand-text">
                    <span class="brand-name">Fakultas Pertanian, Sains dan Teknologi</span>
                    <span class="brand-sub">Portal Administrasi Skripsi • FPST</span>
                </div>
            </a>

            <nav class="nav-links" aria-label="Navigasi Landing Page">
                <a href="#beranda" class="nav-link">Beranda</a>
                <a href="#alur" class="nav-link">Alur Skripsi</a>
                <a href="#prodi" class="nav-link">Program Studi</a>
            </nav>

            <div class="nav-actions">
                @auth
                    <a href="{{ route('dashboard') }}" class="btn btn-primary">Buka Dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="btn btn-primary">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/>
                            <polyline points="10 17 15 12 10 7"/>
                            <line x1="15" y1="12" x2="3" y2="12"/>
                        </svg>
                        Login Portal
                    </a>
                @endauth
            </div>
        </div>
    </header>

    <main>
        <!-- Hero Section -->
        <section id="beranda" class="hero">
            <div class="container hero-grid">
                <div class="scroll-fade is-visible">
                    <div class="hero-pill">
                        <span class="hero-pill-dot" aria-hidden="true"></span>
                        Sistem Administrasi Skripsi Terpadu FPST
                    </div>

                    <h1 class="hero-title">
                        Layanan Digital Skripsi <span>Fakultas Pertanian, Sains dan Teknologi</span>
                    </h1>

                    <p class="hero-desc">
                        Kelola seluruh tahapan tugas akhir mahasiswa mulai dari pengajuan judul, penerbitan SK Bimbingan, pendaftaran Seminar Skripsi, hingga Sidang Skripsi dalam satu portal yang terstruktur, cepat, dan transparan.
                    </p>

                    <div class="hero-cta">
                        @auth
                            <a href="{{ route('dashboard') }}" class="btn btn-primary">Masuk ke Dashboard</a>
                        @else
                            <a href="{{ route('login') }}" class="btn btn-primary">Masuk / Login Akun</a>
                        @endauth
                        <a href="#alur" class="btn btn-outline">Lihat Alur Pelaksanaan</a>
                    </div>

                    <div class="hero-metrics">
                        <div>
                            <div class="metric-value">3 Fase</div>
                            <div class="metric-label">Judul, Seminar &amp; Sidang</div>
                        </div>
                        <div>
                            <div class="metric-value">AGT &amp; AGB</div>
                            <div class="metric-label">Program Studi Resmi</div>
                        </div>
                        <div>
                            <div class="metric-value">Digital</div>
                            <div class="metric-label">SK &amp; Undangan Resmi</div>
                        </div>
                    </div>
                </div>

                <!-- Foto Gedung Pertanian (Lazy Loaded + Responsive WebP) -->
                <div class="campus-card scroll-fade is-visible delay-1">
                    <div class="campus-frame" id="campus-frame">
                        <picture>
                            <source
                                type="image/webp"
                                srcset="{{ asset('images/optimized/gedung-fpst-720.webp') }} 720w, {{ asset('images/optimized/gedung-fpst-1200.webp') }} 1200w"
                                sizes="(max-width: 960px) 100vw, 560px"
                            >
                            <img
                                src="{{ asset('images/Foto Pertanian Baru.jpg') }}"
                                alt="Gedung Fakultas Pertanian, Sains dan Teknologi"
                                class="campus-img"
                                width="1200"
                                height="900"
                                loading="lazy"
                                decoding="async"
                                onload="this.closest('.campus-frame')?.classList.add('loaded')"
                            >
                        </picture>
                        <div class="campus-overlay">
                            <div>
                                <div class="campus-caption-title">Gedung Fakultas Pertanian, Sains dan Teknologi</div>
                                <div class="campus-caption-sub">Universitas Panca Bhakti</div>
                            </div>
                            <span class="campus-badge">Kampus FPST</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Alur Pelaksanaan & Pendaftaran (Flowchart) -->
        <section id="alur" class="section section-white">
            <div class="container">
                <div class="section-header scroll-fade" style="margin-bottom: 2rem;">
                    <span class="section-eyebrow">Flowchart Terstruktur</span>
                    <h2 class="section-title">Alur Pelaksanaan &amp; Penanganan Penolakan Skripsi</h2>
                    <p class="section-subtitle">
                        Ikuti alur keputusan di bawah ini untuk mengetahui langkah lanjutan jika pengajuan disetujui, maupun mekanisme perbaikan apabila judul, berkas, atau penugasan dosen ditolak.
                    </p>
                </div>

                <!-- Ringkasan Alur Makro -->
                <div class="macro-flow scroll-fade" aria-label="Alur Besar Tahapan Skripsi">
                    <span class="macro-step"><span class="macro-step-num">1</span> Pengajuan Judul &amp; SK</span>
                    <span class="macro-sep" aria-hidden="true">→</span>
                    <span class="macro-step"><span class="macro-step-num">2</span> Seminar Skripsi</span>
                    <span class="macro-sep" aria-hidden="true">→</span>
                    <span class="macro-step"><span class="macro-step-num">3</span> Sidang Skripsi</span>
                    <span class="macro-sep" aria-hidden="true">→</span>
                    <span class="macro-step macro-finish">✓ Lulus Skripsi</span>
                </div>

                <!-- Legenda Flowchart -->
                <div class="flow-legend scroll-fade">
                    <span class="legend-item"><span class="legend-dot process"></span> Proses / Pengajuan</span>
                    <span class="legend-item"><span class="legend-dot decision"></span> Titik Keputusan</span>
                    <span class="legend-item"><span class="legend-dot reject"></span> Jalur Ditolak / Revisi (Ulangi)</span>
                    <span class="legend-item"><span class="legend-dot done"></span> Lolos &amp; Tahap Terbuka</span>
                </div>

                <!-- Filter Tab Interaktif -->
                <div class="flow-tabs scroll-fade" role="tablist" aria-label="Filter Fase Flowchart">
                    <button type="button" class="flow-tab-btn active" data-phase="all" role="tab" aria-selected="true">Semua Fase</button>
                    <button type="button" class="flow-tab-btn" data-phase="fase-1" role="tab" aria-selected="false">01. Judul &amp; SK Bimbingan</button>
                    <button type="button" class="flow-tab-btn" data-phase="fase-2" role="tab" aria-selected="false">02. Seminar Skripsi</button>
                    <button type="button" class="flow-tab-btn" data-phase="fase-3" role="tab" aria-selected="false">03. Sidang Skripsi</button>
                </div>

                <div class="flow-lanes">
                    <!-- FASE 1 FLOWCHART -->
                    <article class="flow-lane scroll-fade" data-lane="fase-1">
                        <div class="flow-lane-header">
                            <div class="flow-lane-title-wrap">
                                <div class="flow-number">01</div>
                                <div>
                                    <div class="flow-phase">Fase Pertama</div>
                                    <h3 class="flow-title">Flowchart Pengajuan Judul &amp; SK Bimbingan</h3>
                                </div>
                            </div>
                            <span class="flow-gate-pill">Syarat Awal Masuk Portal</span>
                        </div>

                        <div class="fc-track">
                            <!-- Col 1: Input -->
                            <div class="fc-col">
                                <div class="fc-node fc-node-start">
                                    <span class="fc-node-tag tag-input">1. Mahasiswa</span>
                                    <div class="fc-node-title">Ajukan Judul &amp; Berkas Awal</div>
                                    <p class="fc-node-desc">Input judul, sinopsis, draf proposal (PDF), transkrip sementara, &amp; bukti bayar skripsi.</p>
                                </div>
                            </div>

                            <div class="fc-arrow" aria-hidden="true">
                                <svg class="fc-arrow-icon-h" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M5 12h14"/><path d="m13 6 6 6-6 6"/></svg>
                                <svg class="fc-arrow-icon-v" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M12 5v14"/><path d="m6 13 6 6 6-6"/></svg>
                            </div>

                            <!-- Col 2: Decision Kaprodi -->
                            <div class="fc-col">
                                <div class="fc-node fc-node-decision">
                                    <span class="fc-node-tag tag-decision">◆ 2. Review Kaprodi</span>
                                    <div class="fc-node-title">Kelayakan Judul Skripsi</div>
                                    <p class="fc-node-desc">Kaprodi memeriksa orisinalitas, kesesuaian topik prodi (AGT/AGB), dan kelengkapan berkas.</p>
                                </div>
                                <div class="fc-branch-reject">
                                    <strong>Judul Ditolak / Perlu Revisi</strong>
                                    Mahasiswa menerima notifikasi &amp; catatan alasan dari Kaprodi.
                                    <span class="fc-loop-hint">↺ Perbaiki &amp; Ajukan Judul Baru</span>
                                </div>
                            </div>

                            <div class="fc-arrow">
                                <span class="fc-arrow-badge">✓ Disetujui</span>
                                <svg class="fc-arrow-icon-h" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path d="M5 12h14"/><path d="m13 6 6 6-6 6"/></svg>
                                <svg class="fc-arrow-icon-v" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path d="M12 5v14"/><path d="m6 13 6 6 6-6"/></svg>
                            </div>

                            <!-- Col 3: Decision Dosen -->
                            <div class="fc-col">
                                <div class="fc-node fc-node-decision">
                                    <span class="fc-node-tag tag-decision">◆ 3. Respon Dosen</span>
                                    <div class="fc-node-title">Penetapan &amp; Kesediaan Pembimbing 1 &amp; 2</div>
                                    <p class="fc-node-desc">Kaprodi menetapkan dosen pembimbing, lalu dosen mengonfirmasi kesediaan di Portal Dosen.</p>
                                </div>
                                <div class="fc-branch-reject fc-branch-warn">
                                    <strong>Pembimbing Berhalangan</strong>
                                    Dosen dapat menolak dengan alasan &amp; rekomendasi pengganti.
                                    <span class="fc-loop-hint">↺ Kaprodi Tetapkan Pengganti</span>
                                </div>
                            </div>

                            <div class="fc-arrow">
                                <span class="fc-arrow-badge">✓ Bersedia</span>
                                <svg class="fc-arrow-icon-h" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path d="M5 12h14"/><path d="m13 6 6 6-6 6"/></svg>
                                <svg class="fc-arrow-icon-v" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path d="M12 5v14"/><path d="m6 13 6 6 6-6"/></svg>
                            </div>

                            <!-- Col 4: Output -->
                            <div class="fc-col">
                                <div class="fc-node fc-node-done">
                                    <span class="fc-node-tag tag-done">4. Admin &amp; Selesai</span>
                                    <div class="fc-node-title">SK Bimbingan Terbit</div>
                                    <p class="fc-node-desc">Admin menerbitkan Nomor &amp; Dokumen SK Bimbingan resmi. Mahasiswa mulai bimbingan dan <strong>Fase 2: Seminar</strong> terbuka.</p>
                                </div>
                            </div>
                        </div>
                    </article>

                    <!-- FASE 2 FLOWCHART -->
                    <article class="flow-lane scroll-fade" data-lane="fase-2">
                        <div class="flow-lane-header">
                            <div class="flow-lane-title-wrap">
                                <div class="flow-number">02</div>
                                <div>
                                    <div class="flow-phase">Fase Kedua</div>
                                    <h3 class="flow-title">Flowchart Seminar Skripsi (Proposal / Hasil)</h3>
                                </div>
                            </div>
                            <span class="flow-gate-pill">Syarat: SK Bimbingan Sudah Terbit</span>
                        </div>

                        <div class="fc-track">
                            <!-- Col 1: Input -->
                            <div class="fc-col">
                                <div class="fc-node fc-node-start">
                                    <span class="fc-node-tag tag-input">1. Mahasiswa</span>
                                    <div class="fc-node-title">Daftar Seminar Skripsi</div>
                                    <p class="fc-node-desc">Unggah naskah proposal lengkap, lembar ACC pembimbing, bukti bayar seminar, &amp; sertifikat TOEFL.</p>
                                </div>
                            </div>

                            <div class="fc-arrow" aria-hidden="true">
                                <svg class="fc-arrow-icon-h" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M5 12h14"/><path d="m13 6 6 6-6 6"/></svg>
                                <svg class="fc-arrow-icon-v" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M12 5v14"/><path d="m6 13 6 6 6-6"/></svg>
                            </div>

                            <!-- Col 2: Kaprodi & Penguji -->
                            <div class="fc-col">
                                <div class="fc-node fc-node-decision">
                                    <span class="fc-node-tag tag-decision">◆ 2. Kaprodi &amp; Dosen</span>
                                    <div class="fc-node-title">Penetapan &amp; Kesediaan Penguji Seminar</div>
                                    <p class="fc-node-desc">Kaprodi menetapkan Dosen Penguji Seminar (non-pembimbing), lalu dosen merespon kesediaan.</p>
                                </div>
                                <div class="fc-branch-reject fc-branch-warn">
                                    <strong>Penguji Berhalangan</strong>
                                    Jika dosen penguji menolak penugasan karena jadwal atau halangan.
                                    <span class="fc-loop-hint">↺ Kaprodi Tunjuk Penguji Pengganti</span>
                                </div>
                            </div>

                            <div class="fc-arrow">
                                <span class="fc-arrow-badge">✓ Siap Uji</span>
                                <svg class="fc-arrow-icon-h" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path d="M5 12h14"/><path d="m13 6 6 6-6 6"/></svg>
                                <svg class="fc-arrow-icon-v" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path d="M12 5v14"/><path d="m6 13 6 6 6-6"/></svg>
                            </div>

                            <!-- Col 3: Jadwal & Evaluasi -->
                            <div class="fc-col">
                                <div class="fc-node fc-node-decision">
                                    <span class="fc-node-tag tag-decision">◆ 3. Admin &amp; Tim</span>
                                    <div class="fc-node-title">Jadwal, Undangan &amp; Evaluasi Seminar</div>
                                    <p class="fc-node-desc">Admin menetapkan ruang &amp; waktu serta menerbitkan Surat Undangan. Tim menguji &amp; menilai seminar.</p>
                                </div>
                                <div class="fc-branch-reject">
                                    <strong>Pendaftaran Ditolak / Revisi Naskah</strong>
                                    Jika berkas tidak valid/ditolak, mahasiswa memperbaiki dokumen. Jika ada revisi seminar, wajib diselesaikan dahulu.
                                    <span class="fc-loop-hint">↺ Perbaiki &amp; Daftar / Ajukan Ulang</span>
                                </div>
                            </div>

                            <div class="fc-arrow">
                                <span class="fc-arrow-badge">✓ Lulus</span>
                                <svg class="fc-arrow-icon-h" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path d="M5 12h14"/><path d="m13 6 6 6-6 6"/></svg>
                                <svg class="fc-arrow-icon-v" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path d="M12 5v14"/><path d="m6 13 6 6 6-6"/></svg>
                            </div>

                            <!-- Col 4: Output -->
                            <div class="fc-col">
                                <div class="fc-node fc-node-done">
                                    <span class="fc-node-tag tag-done">4. Selesai &amp; Lanjut</span>
                                    <div class="fc-node-title">Nilai Seminar Keluar &amp; Lulus</div>
                                    <p class="fc-node-desc">Admin memfinalisasi nilai kelulusan seminar. Setelah bebas revisi, <strong>Fase 3: Sidang Skripsi</strong> terbuka.</p>
                                </div>
                            </div>
                        </div>
                    </article>

                    <!-- FASE 3 FLOWCHART -->
                    <article class="flow-lane scroll-fade" data-lane="fase-3">
                        <div class="flow-lane-header">
                            <div class="flow-lane-title-wrap">
                                <div class="flow-number">03</div>
                                <div>
                                    <div class="flow-phase">Fase Ketiga</div>
                                    <h3 class="flow-title">Flowchart Sidang Skripsi</h3>
                                </div>
                            </div>
                            <span class="flow-gate-pill">Syarat: Lulus Seminar &amp; Bebas Revisi</span>
                        </div>

                        <div class="fc-track">
                            <!-- Col 1: Input -->
                            <div class="fc-col">
                                <div class="fc-node fc-node-start">
                                    <span class="fc-node-tag tag-input">1. Mahasiswa</span>
                                    <div class="fc-node-title">Daftar Sidang Skripsi</div>
                                    <p class="fc-node-desc">Unggah naskah skripsi final, lembar ACC sidang pembimbing, bebas revisi seminar, &amp; bukti bayar sidang/SPP.</p>
                                </div>
                            </div>

                            <div class="fc-arrow" aria-hidden="true">
                                <svg class="fc-arrow-icon-h" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M5 12h14"/><path d="m13 6 6 6-6 6"/></svg>
                                <svg class="fc-arrow-icon-v" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M12 5v14"/><path d="m6 13 6 6 6-6"/></svg>
                            </div>

                            <!-- Col 2: Kaprodi & Dewan Penguji -->
                            <div class="fc-col">
                                <div class="fc-node fc-node-decision">
                                    <span class="fc-node-tag tag-decision">◆ 2. Kaprodi &amp; Dosen</span>
                                    <div class="fc-node-title">Penetapan Penguji 1 &amp; 2 Sidang Skripsi</div>
                                    <p class="fc-node-desc">Kaprodi menunjuk Dewan Penguji Sidang Skripsi dan menunggu konfirmasi kesediaan masing-masing penguji.</p>
                                </div>
                                <div class="fc-branch-reject fc-branch-warn">
                                    <strong>Penguji Berhalangan</strong>
                                    Salah satu atau kedua penguji menolak penugasan sidang.
                                    <span class="fc-loop-hint">↺ Kaprodi Tetapkan Penguji Baru</span>
                                </div>
                            </div>

                            <div class="fc-arrow">
                                <span class="fc-arrow-badge">✓ Terkonfirmasi</span>
                                <svg class="fc-arrow-icon-h" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path d="M5 12h14"/><path d="m13 6 6 6-6 6"/></svg>
                                <svg class="fc-arrow-icon-v" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path d="M12 5v14"/><path d="m6 13 6 6 6-6"/></svg>
                            </div>

                            <!-- Col 3: Jadwal & Evaluasi -->
                            <div class="fc-col">
                                <div class="fc-node fc-node-decision">
                                    <span class="fc-node-tag tag-decision">◆ 3. Admin &amp; Sidang</span>
                                    <div class="fc-node-title">Jadwal, Undangan &amp; Pelaksanaan Sidang</div>
                                    <p class="fc-node-desc">Admin menjadwalkan Sidang Skripsi &amp; menerbitkan Surat Undangan. Dewan Penguji menguji kelayakan skripsi.</p>
                                </div>
                                <div class="fc-branch-reject">
                                    <strong>Berkas Ditolak / Mengulang</strong>
                                    Jika syarat administrasi ditolak, mahasiswa memperbarui berkas dan mendaftar ulang Sidang Skripsi.
                                    <span class="fc-loop-hint">↺ Perbaiki Berkas &amp; Daftar Ulang</span>
                                </div>
                            </div>

                            <div class="fc-arrow">
                                <span class="fc-arrow-badge">✓ Lulus</span>
                                <svg class="fc-arrow-icon-h" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path d="M5 12h14"/><path d="m13 6 6 6-6 6"/></svg>
                                <svg class="fc-arrow-icon-v" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path d="M12 5v14"/><path d="m6 13 6 6 6-6"/></svg>
                            </div>

                            <!-- Col 4: Output -->
                            <div class="fc-col">
                                <div class="fc-node fc-node-done">
                                    <span class="fc-node-tag tag-done">4. Final &amp; Lulus</span>
                                    <div class="fc-node-title">Lulus Sidang Skripsi</div>
                                    <p class="fc-node-desc">Admin menginput nilai akhir Sidang Skripsi dan memfinalisasi status kelulusan resmi mahasiswa FPST.</p>
                                </div>
                            </div>
                        </div>
                    </article>
                </div>
            </div>
        </section>

        <!-- Program Studi & Peran Pengguna -->
        <section id="prodi" class="section">
            <div class="container">
                <div class="section-header scroll-fade">
                    <span class="section-eyebrow">Lingkungan Akademik</span>
                    <h2 class="section-title">Program Studi &amp; Ekosistem Portal FPST</h2>
                    <p class="section-subtitle">
                        Dirancang khusus untuk mendukung layanan administrasi akademik di lingkungan Fakultas Pertanian, Sains dan Teknologi.
                    </p>
                </div>

                <div class="prodi-grid">
                    <div class="prodi-card scroll-fade">
                        <span class="prodi-code">KODE PRODI: AGT</span>
                        <h3 class="prodi-name">Program Studi Agroteknologi</h3>
                        <p class="prodi-desc">
                            Mendukung administrasi skripsi bidang budidaya tanaman, agronomi, kesuburan tanah, proteksi tanaman, dan inovasi teknologi pertanian berkelanjutan.
                        </p>
                    </div>

                    <div class="prodi-card scroll-fade delay-1">
                        <span class="prodi-code">KODE PRODI: AGB</span>
                        <h3 class="prodi-name">Program Studi Agribisnis</h3>
                        <p class="prodi-desc">
                            Mendukung administrasi skripsi bidang manajemen agribisnis, ekonomi pertanian, pemasaran komoditas, rantai pasok, serta pemberdayaan masyarakat tani.
                        </p>
                    </div>
                </div>

                <div class="role-strip">
                    <div class="role-box scroll-fade">
                        <div class="role-box-title">Mahasiswa</div>
                        <p class="role-box-desc">Unggah berkas persyaratan secara online dan pantau progres status dari pengajuan hingga Sidang Skripsi.</p>
                    </div>
                    <div class="role-box scroll-fade delay-1">
                        <div class="role-box-title">Dosen</div>
                        <p class="role-box-desc">Terima notifikasi penugasan bimbingan atau penguji, konfirmasi kesediaan, dan akses daftar bimbingan.</p>
                    </div>
                    <div class="role-box scroll-fade delay-2">
                        <div class="role-box-title">Kaprodi</div>
                        <p class="role-box-desc">Verifikasi judul skripsi dan tetapkan dosen pembimbing serta dewan penguji seminar dan sidang.</p>
                    </div>
                    <div class="role-box scroll-fade delay-3">
                        <div class="role-box-title">Admin Prodi &amp; Utama</div>
                        <p class="role-box-desc">Terbitkan SK Bimbingan, atur jadwal &amp; Surat Undangan PDF, kelola data master, dan audit log aktivitas.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Call to Action Banner -->
        <section class="section" style="padding-top: 0;">
            <div class="container">
                <div class="cta-banner scroll-fade">
                    <div class="cta-left">
                        <picture>
                            <source type="image/webp" srcset="{{ asset('images/optimized/logo-160.webp') }}">
                            <img
                                src="{{ asset('images/logo.png') }}"
                                alt="Logo Fakultas Pertanian, Sains dan Teknologi"
                                class="cta-logo"
                                width="68"
                                height="66"
                                loading="lazy"
                                decoding="async"
                            >
                        </picture>
                        <div>
                            <h2 class="cta-title">Siap Memulai atau Memantau Proses Skripsi Anda?</h2>
                            <p class="cta-desc">
                                Masuk menggunakan akun resmi Mahasiswa, Dosen, Kaprodi, atau Admin Fakultas Pertanian, Sains dan Teknologi.
                            </p>
                        </div>
                    </div>
                    <div>
                        @auth
                            <a href="{{ route('dashboard') }}" class="btn btn-light">Buka Dashboard Saya</a>
                        @else
                            <a href="{{ route('login') }}" class="btn btn-light">Login ke Portal Skripsi</a>
                        @endauth
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="container footer-inner">
            <div class="footer-brand">
                <picture>
                    <source type="image/webp" srcset="{{ asset('images/optimized/logo-64.webp') }}">
                    <img
                        src="{{ asset('images/logo.png') }}"
                        alt="Logo FPST"
                        class="footer-logo"
                        width="38"
                        height="37"
                        loading="lazy"
                        decoding="async"
                    >
                </picture>
                <div>
                    <div class="footer-title">Fakultas Pertanian, Sains dan Teknologi</div>
                    <div class="footer-sub">Sistem Administrasi Skripsi • Universitas Panca Bhakti</div>
                </div>
            </div>

            <div class="footer-links">
                <a href="#beranda">Beranda</a>
                <a href="#alur">Alur Skripsi</a>
                <a href="#prodi">Program Studi</a>
                <a href="{{ route('login') }}">Login</a>
            </div>
        </div>
    </footer>

    <script>
        (function () {
            const elements = document.querySelectorAll('.scroll-fade');
            if (!('IntersectionObserver' in window)) {
                elements.forEach((el) => el.classList.add('is-visible'));
                return;
            }

            const observer = new IntersectionObserver(
                (entries) => {
                    entries.forEach((entry) => {
                        if (entry.isIntersecting) {
                            entry.target.classList.add('is-visible');
                        } else {
                            entry.target.classList.remove('is-visible');
                        }
                    });
                },
                {
                    threshold: 0.12,
                    rootMargin: '0px 0px -36px 0px'
                }
            );

            elements.forEach((el) => observer.observe(el));

            // Tandai frame gambar kampus sudah selesai dimuat jika berasal dari cache
            const campusImg = document.querySelector('.campus-img');
            if (campusImg && campusImg.complete) {
                campusImg.closest('.campus-frame')?.classList.add('loaded');
            }

            // Filter interaktif fase flowchart
            const tabButtons = document.querySelectorAll('.flow-tab-btn');
            const lanes = document.querySelectorAll('.flow-lane');
            tabButtons.forEach((btn) => {
                btn.addEventListener('click', () => {
                    const target = btn.getAttribute('data-phase');
                    tabButtons.forEach((b) => {
                        const isActive = b === btn;
                        b.classList.toggle('active', isActive);
                        b.setAttribute('aria-selected', isActive ? 'true' : 'false');
                    });
                    lanes.forEach((lane) => {
                        const match = target === 'all' || lane.getAttribute('data-lane') === target;
                        lane.classList.toggle('is-hidden', !match);
                        if (match) {
                            lane.classList.add('is-visible');
                        }
                    });
                });
            });
        })();
    </script>
</body>
</html>
