<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$is_logged_in = isset($_SESSION['user_id']) && $_SESSION['role'] === 'admin';
$current_page = basename($_SERVER['SCRIPT_NAME']);
$user_nama = $_SESSION['nama'] ?? '';
$user_initial = $user_nama ? strtoupper(substr($user_nama, 0, 1)) : '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Uangkas Kelas — Sistem Informasi Kas Kelas</title>
    <link rel="icon" type="image/svg+xml" href="assets/images/favicon.svg">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.29/jspdf.plugin.autotable.min.js"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { jakarta: ['Plus Jakarta Sans', 'sans-serif'] }
                }
            }
        }
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        /* ── Nav Links ───────────────────────────── */
        .pub-nav { display: flex; align-items: center; gap: 2px; }
        .pub-nav-link {
            display: flex; align-items: center; gap: 6px;
            padding: 8px 14px; border-radius: 10px;
            text-decoration: none;
            font-size: 13px; font-weight: 600;
            color: var(--text-muted);
            transition: all 0.15s ease;
        }
        .pub-nav-link:hover { background: var(--tab-hover); color: var(--text); }
        .pub-nav-link.active { background: var(--tab-active-bg); color: var(--tab-active-text); }

        /* ── Mobile Drawer (Publik) ──────────────── */
        .pub-drawer-overlay { position:fixed; inset:0; z-index:199; background:rgba(0,0,0,0.5); backdrop-filter:blur(6px); display:none; }
        .pub-drawer-overlay.open { display:block; }
        .pub-mobile-drawer {
            position:fixed; top:0; left:0; bottom:0; width:280px; z-index:200;
            background:var(--surface-card); border-right:1px solid var(--border);
            transform:translateX(-100%); transition:transform 0.3s cubic-bezier(0.22,1,0.36,1);
            display:flex; flex-direction:column;
        }
        .pub-mobile-drawer.open { transform:translateX(0); }
        .pub-drawer-header { padding:20px 20px 16px; display:flex; align-items:center; gap:12px; border-bottom:1px solid var(--border); }
        .pub-drawer-nav { flex:1; padding:12px; overflow-y:auto; }
        .pub-drawer-link {
            display:flex; align-items:center; gap:10px; padding:12px 14px; border-radius:10px;
            text-decoration:none; font-size:13px; font-weight:600;
            color:var(--text-muted); transition:0.15s ease; margin-bottom:2px;
        }
        .pub-drawer-link:hover { background:var(--tab-hover); color:var(--text); }
        .pub-drawer-link.active { background:var(--tab-active-bg); color:var(--tab-active-text); }
        .pub-drawer-footer { padding:12px; border-top:1px solid var(--border); }
        .pub-hamburger { display:none; }

        /* ── Design System ───────────────────────── */
        :root {
            --primary-400: #818cf8;
            --primary-500: #6366f1;
            --primary-600: #4f46e5;
            --surface: #ffffff;
            --surface-bg: #f8fafc;
            --surface-card: #ffffff;
            --border: rgba(0,0,0,0.06);
            --border-table: #e2e8f0;
            --border-light: #f1f5f9;
            --text: #0f172a;
            --text-muted: #64748b;
            --text-dim: #94a3b8;
            --text-table: #334155;
            --income: #059669;
            --income-bg: #ecfdf5;
            --expense: #e11d48;
            --expense-bg: #fff1f2;
            --tab-hover: #f1f5f9;
            --tab-active-bg: #eef2ff;
            --tab-active-text: #4f46e5;
            --radius-sm: 8px;
            --radius: 12px;
            --radius-lg: 16px;
            --radius-xl: 20px;
            --sa-bottom: env(safe-area-inset-bottom, 0px);
            --shadow-card: 0 1px 3px rgba(0,0,0,0.04);
            --shadow-elevated: 0 4px 20px rgba(0,0,0,0.06);
            --topbar-bg: rgba(255,255,255,0.8);
            --hero-bg: transparent;
            --footer-bg: #ffffff;
            --scrollbar-thumb: #cbd5e1;
            --input-bg: #ffffff;
            --input-border: #e2e8f0;
            --modal-overlay: rgba(15,23,42,0.7);
        }

        /* ── Dark Mode ──────────────────────────── */
        [data-theme="dark"] {
            --primary-400: #a5b4fc;
            --primary-500: #818cf8;
            --primary-600: #6366f1;
            --surface: #0f172a;
            --surface-bg: #020617;
            --surface-card: #1e293b;
            --border: rgba(255,255,255,0.08);
            --border-table: rgba(255,255,255,0.08);
            --border-light: rgba(255,255,255,0.04);
            --text: #f1f5f9;
            --text-muted: #94a3b8;
            --text-dim: #64748b;
            --text-table: #cbd5e1;
            --income: #34d399;
            --income-bg: rgba(5,150,105,0.15);
            --expense: #fb7185;
            --expense-bg: rgba(225,29,72,0.15);
            --tab-hover: rgba(255,255,255,0.05);
            --tab-active-bg: rgba(99,102,241,0.15);
            --tab-active-text: #a5b4fc;
            --shadow-card: 0 1px 3px rgba(0,0,0,0.2);
            --shadow-elevated: 0 4px 24px rgba(0,0,0,0.3);
            --topbar-bg: rgba(15,23,42,0.85);
            --hero-bg: transparent;
            --footer-bg: #0f172a;
            --scrollbar-thumb: #334155;
            --input-bg: #1e293b;
            --input-border: rgba(255,255,255,0.1);
            --modal-overlay: rgba(2,6,23,0.8);
        }

        * { box-sizing: border-box; }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: var(--surface-bg);
            margin: 0; padding: 0;
            overflow-x: hidden; width: 100%;
            color: var(--text);
            -webkit-font-smoothing: antialiased;
            transition: background 0.3s ease, color 0.3s ease;
        }

        /* ── Top Bar ────────────────────────────── */
        .pub-topbar {
            background: var(--topbar-bg);
            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);
            border-bottom: 1px solid var(--border);
            padding: 0 32px;
            height: 68px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky; top: 0; z-index: 50;
            transition: background 0.3s ease;
        }
        .pub-brand { display: flex; align-items: center; gap: 12px; }
        .pub-brand-mark {
            width: 40px; height: 40px; border-radius: 10px;
            background: linear-gradient(135deg, var(--primary-500), #8b5cf6);
            display: flex; align-items: center; justify-content: center;
            font-size: 17px; color: #fff;
            box-shadow: 0 6px 20px rgba(99,102,241,0.3);
            flex-shrink: 0;
        }
        .pub-brand-text { line-height: 1.2; }
        .pub-brand-title { font-size: 16px; font-weight: 800; color: var(--text); letter-spacing: -0.3px; }
        .pub-brand-sub { font-size: 9px; font-weight: 600; color: var(--primary-400); letter-spacing: 1.5px; text-transform: uppercase; margin-top: 1px; }
        .pub-actions { display: flex; align-items: center; gap: 8px; }

        /* ── Theme Toggle ───────────────────────── */
        .theme-btn {
            width: 38px; height: 38px; border-radius: 10px;
            background: var(--tab-hover);
            border: 1px solid var(--border);
            color: var(--text-muted);
            display: flex; align-items: center; justify-content: center;
            cursor: pointer; transition: all 0.2s ease;
            font-size: 15px;
        }
        .theme-btn:hover { background: var(--tab-active-bg); color: var(--tab-active-text); }
        .theme-btn:active { transform: scale(0.92); }

        /* ── Buttons ────────────────────────────── */
        .btn {
            display: inline-flex; align-items: center; justify-content: center;
            gap: 8px; padding: 10px 20px; border-radius: var(--radius);
            font-weight: 600; font-size: 13px;
            transition: all 0.2s ease;
            cursor: pointer; border: none; text-decoration: none; line-height: 1;
        }
        .btn:active { transform: scale(0.96); }
        .btn-primary {
            background: var(--primary-600); color: #fff;
            box-shadow: 0 4px 14px rgba(79,70,229,0.25);
        }
        .btn-primary:hover { background: var(--primary-500); }
        .btn-outline {
            background: transparent; color: var(--text);
            border: 1.5px solid var(--border-table);
        }
        .btn-outline:hover { background: var(--tab-hover); }
        .btn-soft {
            background: var(--tab-active-bg); color: var(--tab-active-text);
        }
        .btn-soft:hover { filter: brightness(1.05); }
        .btn-sm { padding: 8px 16px; font-size: 12px; }

        /* ── Pub Content ────────────────────────── */
        .pub-content { padding: 36px 32px; max-width: 1280px; margin: 0 auto; }

        /* ── Hero Section ───────────────────────── */
        .pub-hero {
            text-align: center;
            padding: 48px 0 36px;
        }
        .pub-hero h1 {
            font-size: clamp(1.6rem, 3.5vw, 2.8rem);
            font-weight: 800;
            color: var(--text);
            letter-spacing: -0.8px;
            margin: 0 0 10px;
            line-height: 1.15;
        }
        .pub-hero p {
            font-size: clamp(0.85rem, 1.1vw, 1rem);
            color: var(--text-muted);
            max-width: 520px;
            margin: 0 auto 24px;
            line-height: 1.65;
        }
        .hero-actions { display: flex; gap: 10px; justify-content: center; flex-wrap: wrap; }

        /* ── Stat Grid ──────────────────────────── */
        .stat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
            gap: 14px;
            margin-bottom: 32px;
        }
        .stat-card {
            background: var(--surface-card);
            border-radius: var(--radius-lg);
            border: 1px solid var(--border);
            padding: 22px 24px;
            text-align: center;
            box-shadow: var(--shadow-card);
            transition: background 0.3s ease;
        }
        .stat-label {
            font-size: 10px; font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
        }
        .stat-value {
            font-size: clamp(1.3rem, 2.2vw, 1.7rem);
            font-weight: 800;
            letter-spacing: -0.5px;
        }

        /* ── Tabs ───────────────────────────────── */
        .tab-bar {
            display: flex; gap: 4px;
            background: var(--surface-card);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 4px;
            margin-bottom: 24px;
            box-shadow: var(--shadow-card);
        }
        .tab-btn {
            flex: 1;
            padding: 12px 16px;
            border-radius: 10px;
            border: none;
            background: transparent;
            color: var(--text-muted);
            font-family: inherit;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex; align-items: center; justify-content: center; gap: 8px;
        }
        .tab-btn:hover { background: var(--tab-hover); color: var(--text); }
        .tab-btn.active {
            background: var(--tab-active-bg);
            color: var(--tab-active-text);
            box-shadow: 0 1px 4px rgba(99,102,241,0.12);
        }
        .tab-count {
            font-size: 10px; font-weight: 700;
            background: var(--tab-hover);
            padding: 1px 8px; border-radius: 99px;
            line-height: 1.6;
        }
        .tab-btn.active .tab-count { background: rgba(99,102,241,0.2); }
        .tab-pane { display: none; }
        .tab-pane.active { display: block; }

        /* ── Card ───────────────────────────────── */
        .card {
            background: var(--surface-card);
            border-radius: var(--radius-lg);
            border: 1px solid var(--border);
            box-shadow: var(--shadow-card);
            transition: background 0.3s ease, box-shadow 0.3s ease;
        }
        .card-header {
            padding: 20px 24px;
            border-bottom: 1px solid var(--border);
            display: flex; flex-wrap: wrap;
            align-items: center; justify-content: space-between; gap: 12px;
        }
        .card-title { font-size: 15px; font-weight: 800; color: var(--text); letter-spacing: -0.2px; margin: 0; }
        .card-subtitle { font-size: 11px; color: var(--text-muted); margin: 2px 0 0; }

        /* ── Table ──────────────────────────────── */
        .table-wrap {
            overflow-x: auto;
            width: 100%;
            -webkit-overflow-scrolling: touch;
        }
        .table-wrap::-webkit-scrollbar { height: 3px; }
        .table-wrap::-webkit-scrollbar-thumb { background: var(--scrollbar-thumb); border-radius: 99px; }
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }
        table.data-table thead th {
            background: var(--surface-bg);
            color: var(--text-muted);
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 14px 16px;
            text-align: left;
            border-bottom: 1px solid var(--border-table);
            white-space: nowrap;
        }
        table.data-table tbody td {
            padding: 12px 16px;
            border-bottom: 1px solid var(--border-light);
            color: var(--text-table);
        }
        table.data-table tbody tr:hover { background: var(--tab-hover); }
        table.data-table tbody tr:last-child td { border-bottom: none; }

        /* ── Badge ──────────────────────────────── */
        .badge {
            display: inline-flex; align-items: center; gap: 4px;
            padding: 3px 10px; border-radius: 99px;
            font-size: 10px; font-weight: 700;
            text-transform: uppercase; letter-spacing: 0.3px;
            white-space: nowrap;
        }

        /* ── Legend ─────────────────────────────── */
        .legend-box {
            display: flex; flex-wrap: wrap; gap: 10px; align-items: center;
            font-size: 12px; color: var(--text-muted); font-weight: 500;
            background: var(--surface-bg);
            padding: 10px 14px;
            border-radius: 10px;
            border: 1px solid var(--border);
        }
        .legend-dot {
            display: inline-block; width: 14px; height: 14px;
            border-radius: 50%; vertical-align: middle; margin-right: 4px;
        }

        /* ── Footer ─────────────────────────────── */
        .pub-footer {
            background: var(--footer-bg);
            border-top: 1px solid var(--border);
            padding: 20px 32px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 8px;
            transition: background 0.3s ease;
        }
        .pub-footer span { font-size: 11px; color: var(--text-muted); }

        /* ── Modal ──────────────────────────────── */
        .modal-overlay {
            position: fixed; inset: 0; z-index: 100;
            background: var(--modal-overlay);
            backdrop-filter: blur(4px);
            display: none; align-items: center; justify-content: center; padding: 16px;
        }
        .modal-overlay.open { display: flex; }
        .modal-card {
            background: var(--surface-card);
            border-radius: var(--radius-lg);
            box-shadow: 0 20px 60px rgba(0,0,0,0.2);
            width: 100%; max-width: 520px;
            max-height: 90vh; overflow-y: auto;
            transition: background 0.3s ease;
        }
        .modal-close {
            width: 32px; height: 32px; border-radius: 8px;
            border: none; background: var(--tab-hover);
            color: var(--text-muted); cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            font-size: 16px; transition: 0.15s ease;
        }
        .modal-close:hover { background: var(--expense-bg); color: var(--expense); }

        /* ── Empty State ────────────────────────── */
        .empty-icon { font-size: 32px; display: block; margin-bottom: 10px; }

        /* ── Legend badges inside table ─────────── */
        .status-dot {
            display: inline-flex; align-items: center; justify-content: center;
            width: 28px; height: 28px; border-radius: 50%;
            font-size: 12px;
        }

        /* ── Responsive ─────────────────────────── */
        @media (max-width: 768px) {
            .pub-topbar { padding: 0 12px; height: 56px; gap: 6px; }
            .pub-content { padding: 16px 12px; }
            .pub-hero { padding: 24px 0 20px; }
            .pub-hero h1 { font-size: 1.3rem; }
            .pub-hero p { font-size: 0.85rem; margin-bottom: 16px; }
            .pub-nav { display: none; }
            .pub-hamburger { display: flex !important; }
            .pub-brand-mark { width: 34px; height: 34px; font-size: 14px; }
            .pub-brand-title { font-size: 14px; }
            .pub-footer { padding: 12px 16px calc(12px + var(--sa-bottom)); flex-direction: column; text-align: center; }
            .tab-btn { font-size: 12px; padding: 8px 10px; }
            .tab-btn span.tab-label { display: none; }
            .stat-grid { grid-template-columns: repeat(2, 1fr); gap: 10px; }
            .stat-card { padding: 14px; }
            .stat-value { font-size: clamp(1rem, 3vw, 1.3rem); }
            .card-header { padding: 14px 16px; flex-direction: column; align-items: stretch; }
            table.data-table thead th,
            table.data-table tbody td { padding: 8px 6px; font-size: 10px; }
            .hero-actions { flex-direction: column; align-items: center; }
        }
        @media (max-width: 480px) {
            .pub-content { padding: 12px 10px; }
            .pub-hero { padding: 16px 0 14px; }
            .pub-hero h1 { font-size: 1.15rem; }
            .pub-brand-sub { display: none; }
            .pub-nav-link { padding: 5px 8px; font-size: 11px; }
            pub-nav-link i { font-size: 13px; }
            .stat-grid { grid-template-columns: repeat(2, 1fr); gap: 8px; }
            .stat-card { padding: 12px; }
            .stat-value { font-size: 1rem; }
            .card-header { padding: 12px 14px; }
            table.data-table thead th { padding: 6px 4px; font-size: 8px; }
            table.data-table tbody td { padding: 6px 4px; font-size: 10px; }
        }

        /* ── TTD ─────────────────────────────────── */
        .ttd-section {
            display: flex; justify-content: flex-end;
            padding-top: 32px; margin-top: 24px;
        }
        .ttd-box {
            text-align: center; width: 200px;
        }
        .ttd-box img {
            height: 50px; margin-bottom: 4px;
        }
        .ttd-box .ttd-line {
            border-top: 1px solid var(--text);
            width: 100%; margin: 4px 0;
        }
        .ttd-box .ttd-name {
            font-size: 12px; font-weight: 700; color: var(--text);
        }
        .ttd-box .ttd-role {
            font-size: 9px; color: var(--text-muted);
        }

        /* ── Print ──────────────────────────────── */
        @media print {
            .pub-topbar, .pub-footer, .no-print, .tab-bar { display: none !important; }
            body { background: #fff !important; }
            .pub-content { padding: 0; max-width: 100%; }
            .tab-pane { display: block !important; }
            .card { break-inside: avoid; border: 1px solid #e2e8f0; box-shadow: none; }
            .stat-grid { break-inside: avoid; }
            table.data-table thead th { background: #f1f5f9 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .status-dot, .legend-dot { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            [data-theme="dark"] body { background: #fff !important; color: #000 !important; }
            [data-theme="dark"] .card { background: #fff !important; border-color: #e2e8f0 !important; }
            [data-theme="dark"] table.data-table tbody td { color: #334155 !important; }
            [data-theme="dark"] .pub-content, [data-theme="dark"] .stat-card { background: #fff !important; }
        }
    </style>
</head>
<body>

<!-- Top Bar -->
<header class="pub-topbar">
    <div class="pub-brand">
        <div class="pub-brand-mark"><i class="fa-solid fa-wallet"></i></div>
        <div class="pub-brand-text">
            <div class="pub-brand-title">Uangkas Kelas</div>
            <div class="pub-brand-sub">Sistem Informasi Kas</div>
        </div>
    </div>
    <div class="pub-actions">
        <nav class="pub-nav no-print">
            <a href="index.php" class="pub-nav-link <?= $current_page === 'index.php' ? 'active' : '' ?>">
                <i class="fa-solid fa-house"></i> <span>Beranda</span>
            </a>
            <a href="public-rekap.php" class="pub-nav-link <?= $current_page === 'public-rekap.php' ? 'active' : '' ?>">
                <i class="fa-solid fa-table-cells-large"></i> <span>Rekap</span>
            </a>
            <a href="public-riwayat.php" class="pub-nav-link <?= $current_page === 'public-riwayat.php' ? 'active' : '' ?>">
                <i class="fa-solid fa-clock-rotate-left"></i> <span>Riwayat</span>
            </a>
        </nav>
        <button id="themeToggle" class="theme-btn no-print" aria-label="Ganti tema">
            <i id="themeIcon" class="fa-solid fa-moon"></i>
        </button>
        <?php if ($is_logged_in): ?>
            <a href="dashboard.php" class="btn btn-primary btn-sm no-print">
                <i class="fa-solid fa-gauge-high"></i> <span>Dashboard</span>
            </a>
        <?php else: ?>
            <a href="login.php" class="btn btn-primary btn-sm no-print">
                <i class="fa-solid fa-lock"></i> <span>Login Bendahara</span>
            </a>
        <?php endif; ?>
        <button id="pubHamburger" class="pub-hamburger theme-btn no-print" aria-label="Buka menu">
            <i class="fa-solid fa-bars"></i>
        </button>
    </div>
</header>

<!-- ══ MOBILE DRAWER (Publik) ══ -->
<div class="pub-drawer-overlay" id="pubDrawerOverlay"></div>
<div class="pub-mobile-drawer" id="pubMobileDrawer">
    <div class="pub-drawer-header">
        <div style="width:36px;height:36px;border-radius:10px;background:linear-gradient(135deg,var(--primary-500),#8b5cf6);display:flex;align-items:center;justify-content:center;font-size:14px;color:#fff;flex-shrink:0;box-shadow:0 6px 20px rgba(99,102,241,0.3)"><i class="fa-solid fa-wallet"></i></div>
        <div>
            <div style="font-size:14px;font-weight:800;color:var(--text)">Uangkas Kelas</div>
            <div style="font-size:9px;font-weight:600;color:var(--text-muted);text-transform:uppercase;letter-spacing:1px">Informasi Publik</div>
        </div>
    </div>
    <div class="pub-drawer-nav">
        <a href="index.php" class="pub-drawer-link <?= $current_page === 'index.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-house"></i> Beranda
        </a>
        <a href="public-rekap.php" class="pub-drawer-link <?= $current_page === 'public-rekap.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-table-cells-large"></i> Rekap Kas
        </a>
        <a href="public-riwayat.php" class="pub-drawer-link <?= $current_page === 'public-riwayat.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-clock-rotate-left"></i> Riwayat Transaksi
        </a>
    </div>
    <div class="pub-drawer-footer">
        <?php if ($is_logged_in): ?>
            <a href="dashboard.php" style="display:flex;align-items:center;gap:10px;padding:12px 14px;border-radius:10px;text-decoration:none;font-size:13px;font-weight:600;color:var(--text-muted)">
                <i class="fa-solid fa-gauge-high"></i> Dashboard Bendahara
            </a>
        <?php else: ?>
            <a href="login.php" style="display:flex;align-items:center;gap:10px;padding:12px 14px;border-radius:10px;text-decoration:none;font-size:13px;font-weight:600;color:var(--primary-600)">
                <i class="fa-solid fa-lock"></i> Login Bendahara
            </a>
        <?php endif; ?>
    </div>
</div>

<main class="pub-content">
