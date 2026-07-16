<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit(); }

$current_page = basename($_SERVER['SCRIPT_NAME']);
$user_role = 'admin';
$user_nama = $_SESSION['nama'] ?? 'Bendahara';
$user_initial = strtoupper(substr($user_nama, 0, 1));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Uangkas Kelas — Panel Bendahara</title>
    <link rel="icon" type="image/svg+xml" href="assets/images/favicon.svg">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: { extend: { fontFamily: { jakarta: ['Plus Jakarta Sans', 'sans-serif'] } } }
        }
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        /* ── Design System ───────────────────────────── */
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
            --radius: 10px;
            --radius-md: 12px;
            --radius-lg: 16px;
            --sa-bottom: env(safe-area-inset-bottom, 0px);
            --sa-top: env(safe-area-inset-top, 0px);
            --shadow-card: 0 1px 3px rgba(0,0,0,0.04);
            --nav-bg: rgba(255,255,255,0.8);
            --nav-blur: blur(18px);
            --footer-bg: #ffffff;
            --scrollbar-thumb: #cbd5e1;
            --input-bg: #ffffff;
            --input-border: #e2e8f0;
            --modal-overlay: rgba(15,23,42,0.7);
        }

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
            --shadow-card: 0 1px 3px rgba(0,0,0,0.25);
            --nav-bg: rgba(15,23,42,0.85);
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

        /* ── Navbar ──────────────────────────────── */
        .admin-nav {
            background: var(--nav-bg);
            backdrop-filter: var(--nav-blur);
            -webkit-backdrop-filter: var(--nav-blur);
            border-bottom: 1px solid var(--border);
            padding: 0 28px;
            height: 64px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky; top: 0; z-index: 50;
            transition: background 0.3s ease;
        }
        .nav-left { display: flex; align-items: center; gap: 20px; }
        .nav-brand { display: flex; align-items: center; gap: 10px; text-decoration: none; }
        .nav-brand-icon {
            width: 34px; height: 34px; border-radius: 9px;
            background: linear-gradient(135deg, var(--primary-500), #8b5cf6);
            display: flex; align-items: center; justify-content: center;
            font-size: 15px; color: #fff;
            box-shadow: 0 4px 14px rgba(99,102,241,0.3);
            flex-shrink: 0;
        }
        .nav-brand-text { font-size: 15px; font-weight: 800; color: var(--text); letter-spacing: -0.3px; }
        .nav-links { display: flex; align-items: center; gap: 2px; }
        .nav-link {
            display: flex; align-items: center; gap: 6px;
            padding: 8px 14px; border-radius: var(--radius);
            text-decoration: none;
            font-size: 13px; font-weight: 600;
            color: var(--text-muted);
            transition: all 0.15s ease;
        }
        .nav-link:hover { background: var(--tab-hover); color: var(--text); }
        .nav-link.active {
            background: var(--tab-active-bg);
            color: var(--tab-active-text);
        }
        .nav-right { display: flex; align-items: center; gap: 6px; }

        .theme-btn {
            width: 36px; height: 36px; border-radius: var(--radius);
            background: transparent; border: 1px solid var(--border);
            color: var(--text-muted);
            display: flex; align-items: center; justify-content: center;
            cursor: pointer; transition: all 0.15s ease;
            font-size: 14px;
        }
        .theme-btn:hover { background: var(--tab-hover); color: var(--text); }

        /* ── Page Content ────────────────────────── */
        .page-wrap { max-width: 1280px; margin: 0 auto; padding: 28px 28px; }
        .page-content { max-width: 1280px; margin: 0 auto; padding: 28px 28px; }

        /* ── Alert ──────────────────────────────── */
        .alert {
            display: flex; align-items: center; gap: 12px;
            padding: 14px 18px; border-radius: var(--radius-md);
            font-size: 14px; line-height: 1.4; margin-bottom: 24px;
        }
        .alert-error { background: var(--expense-bg); border-left: 4px solid var(--expense); color: var(--expense); }
        .alert-success { background: var(--income-bg); border-left: 4px solid var(--income); color: var(--income); }

        /* ── Card ────────────────────────────────── */
        .card {
            background: var(--surface-card);
            border-radius: var(--radius-lg);
            border: 1px solid var(--border);
            box-shadow: var(--shadow-card);
            transition: background 0.3s ease, box-shadow 0.3s ease;
        }
        .card-hover { transition: transform 0.2s ease, box-shadow 0.2s ease; }
        .card-hover:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,0.06); }
        .card-header { padding: 20px 24px; border-bottom: 1px solid var(--border); }
        .card-body { padding: 24px; }

        /* ── Buttons ─────────────────────────────── */
        .btn {
            display: inline-flex; align-items: center; justify-content: center;
            gap: 8px; padding: 9px 18px; border-radius: var(--radius);
            font-weight: 600; font-size: 13px;
            transition: all 0.2s ease;
            cursor: pointer; border: none; text-decoration: none; line-height: 1;
        }
        .btn:active { transform: scale(0.96); }
        .btn-primary { background: var(--primary-600); color: #fff; box-shadow: 0 4px 14px rgba(79,70,229,0.25); }
        .btn-primary:hover { background: var(--primary-500); }
        .btn-secondary { background: transparent; color: var(--text); border: 1px solid var(--border-table); }
        .btn-secondary:hover { background: var(--tab-hover); }
        .btn-danger { background: var(--expense); color: #fff; }
        .btn-danger:hover { filter: brightness(1.1); }
        .btn-sm { padding: 7px 14px; font-size: 12px; }

        /* ── Form ────────────────────────────────── */
        .input {
            width: 100%; padding: 10px 14px;
            border: 1px solid var(--input-border);
            border-radius: var(--radius);
            font-size: 14px; font-family: inherit;
            color: var(--text); background: var(--input-bg);
            transition: 0.15s ease; outline: none;
        }
        .input:focus { border-color: var(--primary-500); box-shadow: 0 0 0 3px rgba(99,102,241,0.1); }
        .input-label {
            display: block; font-size: 10px; font-weight: 700;
            color: var(--text-muted); text-transform: uppercase;
            letter-spacing: 0.5px; margin-bottom: 6px;
        }
        .select { appearance: none; padding-right: 32px;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%2364748b' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2 5l6 6 6-6'/%3e%3c/svg%3e");
            background-repeat: no-repeat; background-position: right 10px center; background-size: 14px 10px;
        }

        /* ── Badge ───────────────────────────────── */
        .badge {
            display: inline-flex; align-items: center; gap: 4px;
            padding: 3px 10px; border-radius: 99px;
            font-size: 10px; font-weight: 700;
            text-transform: uppercase; letter-spacing: 0.3px; white-space: nowrap;
        }
        .badge-income { background: var(--income-bg); color: var(--income); border: 1px solid rgba(5,150,105,0.15); }
        .badge-expense { background: var(--expense-bg); color: var(--expense); border: 1px solid rgba(225,29,72,0.15); }

        /* ── Table ───────────────────────────────── */
        .table-wrap { overflow-x: auto; width: 100%; -webkit-overflow-scrolling: touch; }
        .table-wrap::-webkit-scrollbar { height: 3px; }
        .table-wrap::-webkit-scrollbar-thumb { background: var(--scrollbar-thumb); border-radius: 99px; }
        table.data-table {
            width: 100%; border-collapse: collapse; font-size: 14px;
        }
        table.data-table thead th {
            background: var(--surface-bg);
            color: var(--text-muted);
            font-size: 10px; font-weight: 700;
            text-transform: uppercase; letter-spacing: 0.5px;
            padding: 14px 16px; text-align: left;
            border-bottom: 1px solid var(--border-table); white-space: nowrap;
        }
        table.data-table tbody td {
            padding: 12px 16px; border-bottom: 1px solid var(--border-light);
            color: var(--text-table);
        }
        table.data-table tbody tr:hover { background: var(--tab-hover); }
        table.data-table tbody tr:last-child td { border-bottom: none; }

        /* ── Stat Grid ───────────────────────────── */
        .stat-grid {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px; margin-bottom: 28px;
        }
        .stat-card {
            background: var(--surface-card);
            border-radius: var(--radius-lg);
            border: 1px solid var(--border);
            padding: 22px 24px;
            box-shadow: var(--shadow-card);
            transition: background 0.3s ease, box-shadow 0.3s ease;
        }
        .stat-label { font-size: 10px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px; }
        .stat-value { font-size: 26px; font-weight: 800; letter-spacing: -0.5px; }

        /* ── Footer ──────────────────────────────── */
        .app-footer {
            background: var(--footer-bg);
            border-top: 1px solid var(--border);
            padding: 16px 28px;
            display: flex; align-items: center; justify-content: space-between;
            flex-wrap: wrap; gap: 8px;
            transition: background 0.3s ease;
        }
        .app-footer span { font-size: 11px; color: var(--text-muted); }

        /* ── Tabs ────────────────────────────────── */
        .tab-bar {
            display: flex; gap: 4px;
            background: var(--surface-card);
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            padding: 4px; margin-bottom: 24px;
        }
        .tab-btn {
            flex: 1; padding: 10px 16px; border-radius: 8px;
            border: none; background: transparent;
            color: var(--text-muted); font-family: inherit;
            font-size: 13px; font-weight: 600; cursor: pointer;
            transition: all 0.15s ease;
            display: flex; align-items: center; justify-content: center; gap: 6px;
        }
        .tab-btn:hover { background: var(--tab-hover); color: var(--text); }
        .tab-btn.active { background: var(--tab-active-bg); color: var(--tab-active-text); }
        .tab-pane { display: none; }
        .tab-pane.active { display: block; }
        .tab-count {
            font-size: 10px; font-weight: 700;
            background: var(--tab-hover); padding: 1px 8px; border-radius: 99px;
        }
        .tab-btn.active .tab-count { background: rgba(99,102,241,0.2); }

        /* ── Legend ──────────────────────────────── */
        .legend-box {
            display: flex; flex-wrap: wrap; gap: 10px; align-items: center;
            font-size: 12px; color: var(--text-muted); font-weight: 500;
            background: var(--surface-bg); padding: 10px 14px;
            border-radius: 10px; border: 1px solid var(--border);
        }
        .legend-dot { display: inline-block; width: 14px; height: 14px; border-radius: 50%; vertical-align: middle; margin-right: 4px; }

        /* ── Modal ───────────────────────────────── */
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
        .modal-header {
            padding: 20px 24px;
            border-bottom: 1px solid var(--border);
            display: flex; align-items: center; justify-content: space-between;
        }
        .modal-header h3 { font-size: 16px; font-weight: 800; margin: 0; color: var(--text); }
        .modal-close {
            width: 32px; height: 32px; border-radius: 8px;
            border: none; background: var(--tab-hover);
            color: var(--text-muted); cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            font-size: 16px; transition: 0.15s ease;
        }
        .modal-close:hover { background: var(--expense-bg); color: var(--expense); }
        .modal-body { padding: 24px; }

        /* ── Print ───────────────────────────────── */
        .status-dot {
            display: inline-flex; align-items: center; justify-content: center;
            width: 28px; height: 28px; border-radius: 50%; font-size: 12px;
        }
        .empty-icon { font-size: 32px; display: block; margin-bottom: 10px; }

        @media print {
            .admin-nav, .app-footer, .no-print, .tab-bar { display: none !important; }
            .page-content { padding: 0; max-width: 100%; }
            .tab-pane { display: block !important; }
            .card { break-inside: avoid; box-shadow: none; border: 1px solid #e2e8f0; }
            .stat-grid { break-inside: avoid; }
            table.data-table thead th { background: #f1f5f9 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .status-dot, .legend-dot { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            [data-theme="dark"] body { background: #fff !important; color: #000 !important; }
            [data-theme="dark"] .card { background: #fff !important; }
            [data-theme="dark"] table.data-table tbody td { color: #334155 !important; }
            .ttd-section { break-inside: avoid; }
        }

        /* ── Responsive ──────────────────────────── */
        @media (max-width: 768px) {
            .admin-nav { padding: 0 14px; height: 56px; }
            .page-content { padding: 16px; }
            .nav-links { display: none; }
            .nav-mobile-menu { display: flex !important; }
            .app-footer { padding: 12px 16px calc(12px + var(--sa-bottom)); flex-direction: column; text-align: center; }
            .stat-grid { grid-template-columns: repeat(2, 1fr); gap: 10px; }
            .stat-card { padding: 16px; }
            .stat-value { font-size: 20px; }
            .card-header { padding: 16px; }
            table.data-table thead th { padding: 10px 10px; font-size: 9px; }
            table.data-table tbody td { padding: 10px; font-size: 12px; }
        }
        @media (min-width: 769px) {
            .nav-mobile-menu { display: none !important; }
        }
        @media (max-width: 480px) {
            .page-content { padding: 12px; }
            .stat-grid { grid-template-columns: repeat(2, 1fr); gap: 8px; }
            .stat-card { padding: 14px; }
            .tab-btn { font-size: 12px; padding: 8px 10px; }
        }

        /* ── Mobile Nav Drawer ──────────────────── */
        .mobile-drawer {
            position: fixed; top: 0; left: 0; bottom: 0;
            width: 280px; z-index: 200;
            background: var(--surface-card);
            border-right: 1px solid var(--border);
            transform: translateX(-100%);
            transition: transform 0.3s cubic-bezier(0.22,1,0.36,1);
            display: flex; flex-direction: column;
        }
        .mobile-drawer.open { transform: translateX(0); }
        .drawer-overlay {
            position: fixed; inset: 0; z-index: 199;
            background: rgba(0,0,0,0.5);
            backdrop-filter: blur(6px);
            display: none;
        }
        .drawer-overlay.open { display: block; }
        .drawer-header {
            padding: 20px 20px 16px; display: flex; align-items: center; gap: 12px;
            border-bottom: 1px solid var(--border);
        }
        .drawer-nav { flex: 1; padding: 12px; overflow-y: auto; }
        .drawer-link {
            display: flex; align-items: center; gap: 10px;
            padding: 12px 14px; border-radius: var(--radius);
            text-decoration: none; font-size: 13px; font-weight: 600;
            color: var(--text-muted); transition: 0.15s ease; margin-bottom: 2px;
        }
        .drawer-link:hover { background: var(--tab-hover); color: var(--text); }
        .drawer-link.active { background: var(--tab-active-bg); color: var(--tab-active-text); }
        .drawer-footer { padding: 12px; border-top: 1px solid var(--border); }

        /* TTD section */
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
    </style>
</head>
<body>

<!-- ══ DESKTOP NAV ══ -->
<nav class="admin-nav">
    <div class="nav-left">
        <a href="dashboard.php" class="nav-brand">
            <div class="nav-brand-icon"><i class="fa-solid fa-wallet"></i></div>
            <span class="nav-brand-text">Uangkas</span>
        </a>
        <div class="nav-links">
            <a href="dashboard.php" class="nav-link <?= $current_page === 'dashboard.php' ? 'active' : '' ?>">
                <i class="fa-solid fa-chart-pie"></i> Dashboard
            </a>
            <a href="transaksi.php" class="nav-link <?= $current_page === 'transaksi.php' ? 'active' : '' ?>">
                <i class="fa-solid fa-list-check"></i> Transaksi
            </a>
            <a href="rekap.php" class="nav-link <?= $current_page === 'rekap.php' ? 'active' : '' ?>">
                <i class="fa-solid fa-table-cells-large"></i> Rekap
            </a>
            <a href="anggota.php" class="nav-link <?= $current_page === 'anggota.php' ? 'active' : '' ?>">
                <i class="fa-solid fa-users"></i> Anggota
            </a>
        </div>
    </div>
    <div class="nav-right">
        <button id="themeBtn" class="theme-btn no-print" aria-label="Ganti tema">
            <i id="themeIcon" class="fa-solid fa-moon"></i>
        </button>
        <a href="logout.php" onclick="return confirm('Yakin ingin keluar?')" class="btn btn-secondary btn-sm no-print">
            <i class="fa-solid fa-right-from-bracket"></i>
            <span class="nav-label-full">Keluar</span>
        </a>
        <button class="nav-mobile-menu theme-btn no-print" id="drawerToggle" aria-label="Buka menu">
            <i class="fa-solid fa-bars"></i>
        </button>
    </div>
</nav>

<!-- ══ MOBILE DRAWER ══ -->
<div class="drawer-overlay" id="drawerOverlay"></div>
<div class="mobile-drawer" id="mobileDrawer">
    <div class="drawer-header">
        <div class="nav-brand-icon" style="width:36px;height:36px;font-size:14px"><i class="fa-solid fa-wallet"></i></div>
        <div>
            <div style="font-size:14px;font-weight:800;color:var(--text)">Uangkas Kelas</div>
            <div style="font-size:9px;font-weight:600;color:var(--text-muted);text-transform:uppercase;letter-spacing:1px">Panel Bendahara</div>
        </div>
    </div>
    <div class="drawer-nav">
        <a href="dashboard.php" class="drawer-link <?= $current_page === 'dashboard.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-chart-pie"></i> Dashboard
        </a>
        <a href="transaksi.php" class="drawer-link <?= $current_page === 'transaksi.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-list-check"></i> Transaksi
        </a>
        <a href="rekap.php" class="drawer-link <?= $current_page === 'rekap.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-table-cells-large"></i> Rekap
        </a>
        <a href="anggota.php" class="drawer-link <?= $current_page === 'anggota.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-users"></i> Anggota
        </a>
    </div>
    <div class="drawer-footer">
        <a href="logout.php" onclick="return confirm('Yakin ingin keluar?')"
           style="display:flex;align-items:center;gap:10px;padding:12px 14px;border-radius:var(--radius);text-decoration:none;font-size:13px;font-weight:600;color:rgba(248,113,113,0.6);transition:0.15s ease"
           onmouseover="this.style.background='rgba(239,68,68,0.08)';this.style.color='#fca5a5'"
           onmouseout="this.style.background='';this.style.color='rgba(248,113,113,0.6)'">
            <i class="fa-solid fa-right-from-bracket"></i> Keluar Aplikasi
        </a>
    </div>
</div>

<!-- ══ MAIN ══ -->
<div style="min-height:100vh;display:flex;flex-direction:column">
<div class="page-content">
