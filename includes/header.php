<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$current_page = basename($_SERVER['SCRIPT_NAME']);
$user_role = 'admin'; // Only admin can access protected pages
$user_nama = $_SESSION['nama'] ?? 'Bendahara';
$user_initial = strtoupper(substr($user_nama, 0, 1));
$role_label = 'Bendahara';
$role_icon = 'fa-shield-halved';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Uangkas Kelas — Sistem Manajemen Kas</title>
    <link rel="icon" type="image/svg+xml" href="assets/images/favicon.svg">
    <script src="https://cdn.tailwindcss.com"></script>
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
        /* ── Design System ─────────────────────────── */
        :root {
            --primary-bg: #0b1120;
            --primary-400: #818cf8;
            --primary-500: #6366f1;
            --primary-600: #4f46e5;
            --surface: #ffffff;
            --surface-sub: #f1f5f9;
            --border: rgba(0,0,0,0.06);
            --text: #0f172a;
            --text-muted: #64748b;
            --income: #059669;
            --expense: #e11d48;
            --radius-sm: 8px;
            --radius-md: 12px;
            --radius-lg: 16px;
            --radius-xl: 20px;
            --sa-bottom: env(safe-area-inset-bottom, 0px);
            --sa-top: env(safe-area-inset-top, 0px);
        }
        * { box-sizing: border-box; }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: #f8fafc;
            margin: 0;
            padding: 0;
            overflow-x: hidden;
            width: 100%;
            color: var(--text);
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        /* ── App Shell ──────────────────────── */
        .app-shell {
            display: flex;
            min-height: 100vh;
            min-height: 100dvh;
            width: 100%;
            overflow-x: hidden;
        }

        /* ── Sidebar ─────────────────────────── */
        #sidebar {
            width: 270px;
            background: var(--primary-bg);
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
            position: fixed;
            top: 0; left: 0; bottom: 0;
            z-index: 100;
            transition: transform 0.4s cubic-bezier(0.22,1,0.36,1);
            overflow: hidden;
        }
        #sidebar::before {
            content: '';
            position: absolute;
            inset: 0;
            background:
                radial-gradient(ellipse 140% 60% at 20% 0%, rgba(99,102,241,0.12) 0%, transparent 70%),
                radial-gradient(ellipse 60% 50% at 80% 100%, rgba(139,92,246,0.06) 0%, transparent 60%);
            pointer-events: none;
        }

        /* ── Brand ────────────────────── */
        .sb-brand {
            padding: 28px 24px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.06);
            display: flex;
            align-items: center;
            gap: 14px;
            position: relative;
        }
        .sb-brand-mark {
            width: 44px; height: 44px;
            border-radius: var(--radius-md);
            background: linear-gradient(135deg, var(--primary-500) 0%, #8b5cf6 100%);
            display: flex; align-items: center; justify-content: center;
            font-size: 18px; color: #fff;
            box-shadow: 0 8px 24px rgba(99,102,241,0.35);
            flex-shrink: 0;
            position: relative;
            overflow: hidden;
        }
        .sb-brand-mark::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(255,255,255,0.2) 0%, transparent 60%);
            pointer-events: none;
        }
        .sb-brand-text { line-height: 1.2; }
        .sb-brand-title { font-size: 16px; font-weight: 800; color: #fff; letter-spacing: -0.4px; }
        .sb-brand-sub { font-size: 9.5px; font-weight: 600; color: var(--primary-400); letter-spacing: 2px; text-transform: uppercase; margin-top: 2px; opacity: 0.7; }

        /* ── User Card ────────────────── */
        .sb-user {
            padding: 18px 24px;
            border-bottom: 1px solid rgba(255,255,255,0.06);
            display: flex; align-items: center; gap: 12px;
            position: relative;
        }
        .sb-avatar {
            width: 42px; height: 42px;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--primary-500), #a78bfa);
            color: #fff;
            font-weight: 800; font-size: 15px;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
            position: relative;
        }
        .sb-avatar::after {
            content: '';
            position: absolute;
            bottom: -1px; right: -1px;
            width: 12px; height: 12px;
            border-radius: 50%;
            background: #22c55e;
            border: 2.5px solid var(--primary-bg);
        }
        .sb-user-info { overflow: hidden; flex: 1; }
        .sb-user-name {
            font-size: 13px; font-weight: 700; color: #fff;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .sb-user-role {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 3px 10px; border-radius: 99px;
            font-size: 9px; font-weight: 700;
            margin-top: 5px; letter-spacing: 0.5px;
            text-transform: uppercase;
            background: rgba(99,102,241,0.2); color: #a5b4fc;
            border: 1px solid rgba(99,102,241,0.2);
        }

        /* ── Navigation ───────────────── */
        .sb-nav {
            flex: 1;
            padding: 20px 14px 12px;
            overflow-y: auto;
            position: relative;
        }
        .sb-nav::-webkit-scrollbar { width: 3px; }
        .sb-nav::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.15); border-radius: 99px; }

        .sb-nav-label {
            font-size: 9px; font-weight: 700;
            color: rgba(255,255,255,0.2);
            letter-spacing: 2px; text-transform: uppercase;
            padding: 0 12px; margin-bottom: 10px;
        }
        .sb-link {
            display: flex; align-items: center; gap: 12px;
            padding: 12px 16px; border-radius: 10px;
            text-decoration: none;
            font-size: 13px; font-weight: 600;
            color: rgba(255,255,255,0.45);
            transition: all 0.2s ease;
            margin-bottom: 4px;
            position: relative;
        }
        .sb-link:hover {
            background: rgba(255,255,255,0.05);
            color: rgba(255,255,255,0.85);
        }
        .sb-link.active {
            background: linear-gradient(135deg, rgba(99,102,241,0.25) 0%, rgba(139,92,246,0.12) 100%);
            color: #fff;
            border: 1px solid rgba(99,102,241,0.18);
        }
        .sb-link.active::before {
            content: '';
            position: absolute;
            left: -1px; top: 22%; bottom: 22%;
            width: 3px;
            border-radius: 0 99px 99px 0;
            background: linear-gradient(180deg, var(--primary-400), #a78bfa);
        }
        .sb-link-icon {
            width: 20px; text-align: center;
            font-size: 14px; flex-shrink: 0;
        }

        /* ── Logout ───────────────────── */
        .sb-footer {
            padding: 12px;
            padding-bottom: calc(12px + var(--sa-bottom));
            border-top: 1px solid rgba(255,255,255,0.06);
            position: relative;
        }
        .sb-logout {
            display: flex; align-items: center; gap: 10px;
            padding: 11px 16px; border-radius: 10px;
            text-decoration: none;
            font-size: 13px; font-weight: 600;
            color: rgba(248,113,113,0.5);
            transition: all 0.2s ease;
        }
        .sb-logout:hover {
            background: rgba(239,68,68,0.1);
            color: #fca5a5;
        }

        /* ── Sidebar Overlay (mobile) ─── */
        #sidebarOverlay {
            display: none;
            position: fixed; inset: 0;
            background: rgba(0,0,0,0.6);
            z-index: 99;
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            opacity: 0;
            transition: opacity 0.35s ease;
        }
        #sidebarOverlay.active {
            display: block;
            opacity: 1;
        }

        /* ── Main Area ─────────────────── */
        .main-area {
            flex: 1;
            min-width: 0;
            margin-left: 270px;
            display: flex; flex-direction: column;
            min-height: 100vh;
        }

        /* ── Top Bar ────────────────────── */
        .topbar {
            background: rgba(255,255,255,0.75);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-bottom: 1px solid var(--border);
            padding: 0 36px;
            height: 68px;
            display: flex; align-items: center; justify-content: space-between;
            position: sticky; top: 0; z-index: 50;
        }
        .topbar-left {}
        .topbar-left h1 {
            font-size: 18px; font-weight: 800;
            color: var(--text); letter-spacing: -0.3px;
            margin: 0;
        }
        .topbar-left p {
            font-size: 11px; color: var(--text-muted);
            margin: 0; margin-top: 1px;
        }
        .topbar-right {
            display: flex; align-items: center; gap: 10px;
        }
        .topbar-pill {
            display: flex; align-items: center; gap: 8px;
            padding: 7px 14px 7px 10px;
            border-radius: 99px;
            background: var(--surface-sub);
            border: 1px solid var(--border);
            font-size: 12px; font-weight: 600; color: var(--text-muted);
        }
        .topbar-user {
            display: flex; align-items: center; gap: 10px;
            padding: 6px 14px 6px 8px;
            border-radius: 99px;
            background: var(--surface-sub);
            border: 1px solid var(--border);
            cursor: default;
        }
        .topbar-user-av {
            width: 30px; height: 30px;
            border-radius: 8px;
            background: linear-gradient(135deg, var(--primary-500), #8b5cf6);
            color: #fff; font-size: 12px; font-weight: 800;
            display: flex; align-items: center; justify-content: center;
        }
        .topbar-user-info {}
        .topbar-user-name { font-size: 12px; font-weight: 700; color: var(--text); line-height: 1.2; }
        .topbar-user-role { font-size: 9px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.4px; margin-top: 1px; }

        /* ── Mobile Top Bar ─────────────── */
        .mobile-topbar {
            display: none;
            background: var(--primary-bg);
            padding: calc(8px + var(--sa-top)) 14px 8px;
            min-height: calc(60px + var(--sa-top));
            align-items: center;
            justify-content: space-between;
            position: sticky; top: 0; z-index: 80;
            gap: 8px;
        }
        .m-brand { display: flex; align-items: center; gap: 10px; flex-shrink: 0; }
        .m-brand-mark {
            width: 34px; height: 34px; border-radius: 10px;
            background: linear-gradient(135deg, var(--primary-500), #8b5cf6);
            display: flex; align-items: center; justify-content: center;
            font-size: 14px; color: #fff;
            flex-shrink: 0;
        }
        .m-brand-text { font-size: 14px; font-weight: 800; color: #fff; letter-spacing: -0.3px; }

        .m-user {
            display: flex; align-items: center; gap: 8px;
            padding: 4px 10px 4px 5px;
            border-radius: 99px;
            background: rgba(255,255,255,0.07);
            border: 1px solid rgba(255,255,255,0.08);
            flex-shrink: 1;
            min-width: 0;
            overflow: hidden;
        }
        .m-user-av {
            width: 28px; height: 28px; border-radius: 8px;
            background: linear-gradient(135deg, var(--primary-500), #8b5cf6);
            color: #fff; font-size: 10px; font-weight: 800;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }
        .m-user-info { line-height: 1.2; overflow: hidden; min-width: 0; }
        .m-user-name { font-size: 10px; font-weight: 700; color: #fff; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 70px; }
        .m-user-role { font-size: 8px; font-weight: 600; color: rgba(255,255,255,0.4); margin-top: 1px; }

        .m-menu-btn {
            width: 36px; height: 36px; border-radius: 10px;
            background: rgba(255,255,255,0.07);
            border: 1px solid rgba(255,255,255,0.1);
            color: #fff; font-size: 14px;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer; transition: 0.15s ease;
            flex-shrink: 0;
        }
        .m-menu-btn:hover { background: rgba(255,255,255,0.12); }
        .m-menu-btn:active { transform: scale(0.92); }

        /* ── Content ──────────────────────── */
        .page-content { flex: 1; padding: 32px 36px; }

        /* ── Footer ───────────────────────── */
        .app-footer {
            background: var(--surface);
            border-top: 1px solid var(--border);
            padding: 18px 36px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 8px;
        }
        .app-footer span { font-size: 11px; color: var(--text-muted); }

        /* ── Responsive ───────────────────── */
        @media (max-width: 768px) {
            #sidebar { transform: translateX(-100%); }
            #sidebar.open { transform: translateX(0); }
            .main-area { margin-left: 0; }
            .topbar { display: none; }
            .mobile-topbar { display: flex; }
            .page-content { padding: 20px; }
            .app-footer { padding: 14px 20px calc(14px + var(--sa-bottom)); flex-direction: column; text-align: center; }
        }

        @media (max-width: 480px) {
            .page-content { padding: 14px; }
            .mobile-topbar { padding-left: 10px; padding-right: 10px; gap: 6px; }
            .m-user-name { max-width: 50px; }
        }

        @media (max-width: 360px) {
            .mobile-topbar { gap: 4px; padding-left: 8px; padding-right: 8px; }
            .m-brand-text { font-size: 12px; }
            .m-brand-mark { width: 30px; height: 30px; font-size: 12px; }
            .m-user-info { display: none; }
            .m-user { padding: 4px 6px; }

86	        .m-user-av { width: 28px; height: 28px; }
            .m-menu-btn { width: 32px; height: 32px; font-size: 12px; }
            .page-content { padding: 10px; }
        }

        @media (min-width: 769px) and (max-width: 1024px) {
            .page-content { padding: 24px 28px; }
            .topbar { padding: 0 24px; }
        }

        /* ── Utility: Card ────────────────── */
        .card {
            background: var(--surface);
            border-radius: var(--radius-lg);
            border: 1px solid var(--border);
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        }
        .card-hover {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .card-hover:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.06);
        }
        .card-header {
            padding: 20px 24px;
            border-bottom: 1px solid var(--border);
        }
        .card-body { padding: 24px; }

        /* ── Alert ────────────────────────── */
        .alert {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 18px;
            border-radius: var(--radius-md);
            font-size: 14px;
            line-height: 1.4;
        }
        .alert-error {
            background: #fff1f2;
            border-left: 4px solid var(--expense);
            color: #9f1239;
        }
        .alert-success {
            background: #ecfdf5;
            border-left: 4px solid var(--income);
            color: #065f46;
        }

        /* ── Buttons ──────────────────────── */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 10px 20px;
            border-radius: var(--radius-md);
            font-weight: 600;
            font-size: 13px;
            transition: all 0.2s ease;
            cursor: pointer;
            border: none;
            text-decoration: none;
            line-height: 1;
        }
        .btn:active { transform: scale(0.96); }
        .btn-primary {
            background: var(--primary-600);
            color: #fff;
            box-shadow: 0 4px 14px rgba(79,70,229,0.25);
        }
        .btn-primary:hover { background: #4338ca; box-shadow: 0 6px 20px rgba(79,70,229,0.3); }
        .btn-secondary {
            background: var(--surface);
            color: #475569;
            border: 1px solid var(--border);
        }
        .btn-secondary:hover { background: #f8fafc; }
        .btn-danger {
            background: var(--expense);
            color: #fff;
        }
        .btn-danger:hover { background: #be123c; }
        .btn-sm { padding: 7px 16px; font-size: 11px; }

        /* ── Form Inputs ──────────────────── */
        .input {
            width: 100%;
            padding: 11px 14px;
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            font-size: 14px;
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: var(--text);
            background: var(--surface);
            transition: 0.15s ease;
            outline: none;
        }
        .input:focus {
            border-color: var(--primary-500);
            box-shadow: 0 0 0 4px rgba(99,102,241,0.1);
        }
        .input-label {
            display: block;
            font-size: 11px;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
        }
        .select {
            appearance: none;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%2364748b' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2 5l6 6 6-6'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 10px center;
            background-size: 14px 10px;
            padding-right: 32px;
        }

        /* ── Badge ────────────────────────── */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 10px;
            border-radius: 99px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            white-space: nowrap;
        }
        .badge-income { background: #ecfdf5; color: var(--income); border: 1px solid rgba(5,150,105,0.15); }
        .badge-expense { background: #fff1f2; color: var(--expense); border: 1px solid rgba(225,29,72,0.15); }

        /* ── Table ────────────────────────── */
        .table-wrap {
            overflow-x: auto;
            width: 100%;
            -webkit-overflow-scrolling: touch;
        }
        .table-wrap::-webkit-scrollbar { height: 3px; }
        .table-wrap::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 99px; }
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }
        table.data-table thead th {
            background: #f8fafc;
            color: var(--text-muted);
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 14px 16px;
            text-align: left;
            border-bottom: 1px solid var(--border);
            white-space: nowrap;
        }
        table.data-table tbody td {
            padding: 12px 16px;
            border-bottom: 1px solid #f1f5f9;
            color: #334155;
        }
        table.data-table tbody tr:hover {
            background: #f8fafc;
        }
        table.data-table tbody tr:last-child td {
            border-bottom: none;
        }

        /* ── Stat Cards ───────────────────── */
        .stat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
        }
        .stat-card {
            background: var(--surface);
            border-radius: var(--radius-lg);
            border: 1px solid var(--border);
            padding: 22px 24px;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.06);
        }
        .stat-label {
            font-size: 11px; font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }
        .stat-value {
            font-size: 28px; font-weight: 800;
            letter-spacing: -0.5px;
        }
        .stat-value.text-income { color: var(--income); }
        .stat-value.text-expense { color: var(--expense); }
        .stat-value.text-primary { color: var(--primary-600); }
    </style>
</head>
<body>
<div class="app-shell">

    <!-- ══ SIDEBAR ══ -->
    <aside id="sidebar">
        <div class="sb-brand">
            <div class="sb-brand-mark"><i class="fa-solid fa-wallet"></i></div>
            <div class="sb-brand-text">
                <div class="sb-brand-title">Uangkas Kelas</div>
                <div class="sb-brand-sub">Dashboard Keuangan</div>
            </div>
        </div>

        <div class="sb-user">
            <div class="sb-avatar"><?= $user_initial ?></div>
            <div class="sb-user-info">
                <div class="sb-user-name" title="<?= htmlspecialchars($user_nama) ?>"><?= htmlspecialchars($user_nama) ?></div>
                <span class="sb-user-role">
                    <i class="fa-solid <?= $role_icon ?>" style="font-size:8px"></i>
                    <?= $role_label ?>
                </span>
            </div>
        </div>

        <nav class="sb-nav">
            <div class="sb-nav-label">Menu</div>
            <a href="dashboard.php" class="sb-link <?= $current_page === 'dashboard.php' ? 'active' : '' ?>">
                <span class="sb-link-icon"><i class="fa-solid fa-chart-pie"></i></span>
                Dashboard
            </a>
            <a href="transaksi.php" class="sb-link <?= $current_page === 'transaksi.php' ? 'active' : '' ?>">
                <span class="sb-link-icon"><i class="fa-solid fa-list-check"></i></span>
                Riwayat Transaksi
            </a>
            <a href="rekap.php" class="sb-link <?= $current_page === 'rekap.php' ? 'active' : '' ?>">
                <span class="sb-link-icon"><i class="fa-solid fa-table-cells-large"></i></span>
                Matriks Rekap Kas
            </a>
            <a href="anggota.php" class="sb-link <?= $current_page === 'anggota.php' ? 'active' : '' ?>">
                <span class="sb-link-icon"><i class="fa-solid fa-users"></i></span>
                Anggota Kelas
            </a>
        </nav>

        <div class="sb-footer">
            <a href="logout.php"
               onclick="return confirm('Yakin ingin keluar?')"
               class="sb-logout">
                <span class="sb-link-icon"><i class="fa-solid fa-right-from-bracket"></i></span>
                Keluar Aplikasi
            </a>
        </div>
    </aside>

    <div id="sidebarOverlay"></div>

    <!-- ══ MAIN ══ -->
    <div class="main-area">

        <!-- Mobile Top Bar -->
        <header class="mobile-topbar">
            <div class="m-brand">
                <div class="m-brand-mark"><i class="fa-solid fa-wallet"></i></div>
                <span class="m-brand-text">Uangkas</span>
            </div>
            <div class="m-user">
                <div class="m-user-av"><?= $user_initial ?></div>
                <div class="m-user-info">
                    <div class="m-user-name"><?= htmlspecialchars($user_nama) ?></div>
                    <div class="m-user-role"><?= $role_label ?></div>
                </div>
            </div>
            <button class="m-menu-btn" id="mobileMenuToggle" aria-label="Buka menu">
                <i class="fa-solid fa-bars" id="menuIcon"></i>
            </button>
        </header>

        <!-- Desktop Top Bar -->
        <div class="topbar">
            <div class="topbar-left">
                <h1>
                    <?php
                    switch($current_page) {
                        case 'dashboard.php': echo 'Dashboard Utama'; break;
                        case 'transaksi.php': echo 'Riwayat Kas Kelas'; break;
                        case 'rekap.php':     echo 'Matriks Pembayaran Kas'; break;
                        case 'anggota.php':   echo 'Manajemen Anggota'; break;
                        default:              echo 'Sistem Uangkas Kelas';
                    }
                    ?>
                </h1>
                <p>Pantau dan kelola keuangan kelas dengan akurat &amp; transparan</p>
            </div>
            <div class="topbar-right">
                <div class="topbar-pill">
                    <i class="fa-regular fa-calendar" style="color:#94a3b8"></i>
                    T.A. <?= date('Y') ?>
                </div>
                <div class="topbar-user">
                    <div class="topbar-user-av"><?= $user_initial ?></div>
                    <div class="topbar-user-info">
                        <div class="topbar-user-name"><?= htmlspecialchars($user_nama) ?></div>
                        <div class="topbar-user-role"><i class="fa-solid <?= $role_icon ?>" style="font-size:8px;margin-right:2px"></i><?= $role_label ?></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="page-content">
