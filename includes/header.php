<?php
// includes/header.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$current_page = basename($_SERVER['SCRIPT_NAME']);
$user_role = $_SESSION['role'] ?? 'anggota';
$user_nama = $_SESSION['nama'] ?? 'Pengguna';
$user_initial = strtoupper(substr($user_nama, 0, 1));
$is_admin = $user_role === 'admin';
$role_label = $is_admin ? 'Bendahara' : 'Anggota';
$role_icon = $is_admin ? 'fa-shield-halved' : 'fa-user-graduate';
$role_color_class = $is_admin ? 'role-admin' : 'role-anggota';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Uangkas Kelas - Aplikasi Kas Kelas</title>
    <link rel="icon" type="image/svg+xml" href="assets/images/favicon.svg">
    <link rel="alternate icon" href="assets/images/favicon.svg">
    <!-- Tailwind CSS via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        jakarta: ['Plus Jakarta Sans', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400&display=swap" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        /* ══════════════════════════════════════════
           DESIGN TOKENS — Uangkas Antigravity v2
           ══════════════════════════════════════════ */
        :root {
            --sidebar-width: 260px;
            --topbar-h: 64px;
            --mobile-topbar-h: 60px;
            --sa-bottom: env(safe-area-inset-bottom, 0px);
            --sa-top: env(safe-area-inset-top, 0px);

            /* Primary */
            --primary-400: #818cf8;
            --primary-500: #6366f1;
            --primary-600: #4f46e5;
            --primary-700: #4338ca;

            /* Semantic */
            --income: #059669;
            --income-bg: #ecfdf5;
            --expense: #e11d48;
            --expense-bg: #fff1f2;

            /* Surfaces */
            --surface: #ffffff;
            --surface-bg: #f8fafc;
            --border: #e2e8f0;

            /* Sidebar */
            --sidebar-bg: #0f172a;
            --sidebar-border: rgba(255,255,255,0.07);
            --sidebar-text: rgba(255,255,255,0.5);
            --sidebar-text-hover: rgba(255,255,255,0.9);
            --sidebar-accent: #6366f1;

            /* Radius */
            --radius-sm: 6px;
            --radius-md: 10px;
            --radius-lg: 12px;
            --radius-xl: 16px;

            /* Shadows */
            --shadow-card: 0 1px 3px rgba(0,0,0,0.06), 0 1px 2px rgba(0,0,0,0.04);
            --shadow-elevated: 0 4px 6px -1px rgba(0,0,0,0.08), 0 2px 4px -2px rgba(0,0,0,0.05);

            /* Transitions */
            --transition-fast: 150ms cubic-bezier(0.4,0,0.2,1);
            --transition-normal: 200ms cubic-bezier(0.4,0,0.2,1);
        }

        * { box-sizing: border-box; }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: var(--surface-bg);
            margin: 0;
            padding: 0;
            overflow-x: hidden;
            width: 100%;
            color: #1e293b;
        }

        /* ── dvh fallback for mobile ─── */
        .app-wrapper {
            display: flex;
            min-height: 100vh;
            min-height: 100dvh;
            width: 100%;
            overflow-x: hidden;
        }

        /* ── Sidebar ────────────────────── */
        #sidebar {
            width: var(--sidebar-width);
            background: var(--sidebar-bg);
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
            position: fixed;
            top: 0; left: 0; bottom: 0;
            z-index: 50;
            transition: transform 0.3s cubic-bezier(0.4,0,0.2,1);
            overflow: hidden;
        }

        /* Sidebar subtle gradient atmosphere */
        #sidebar::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(170deg, rgba(99,102,241,0.10) 0%, rgba(15,23,42,0.6) 50%, transparent 80%);
            pointer-events: none;
        }

        /* ── Sidebar Branding ─────────── */
        .sidebar-brand {
            padding: 24px 20px 20px;
            border-bottom: 1px solid var(--sidebar-border);
            display: flex;
            align-items: center;
            gap: 12px;
            position: relative;
        }
        .brand-icon {
            width: 42px; height: 42px;
            border-radius: var(--radius-lg);
            background: linear-gradient(135deg, var(--primary-500), #8b5cf6);
            display: flex; align-items: center; justify-content: center;
            font-size: 18px; color: #fff;
            box-shadow: 0 4px 14px rgba(99,102,241,0.4);
            flex-shrink: 0;
        }
        .brand-text { line-height: 1; }
        .brand-title { font-size: 15px; font-weight: 800; color: #fff; letter-spacing: -0.3px; }
        .brand-sub { font-size: 9px; font-weight: 600; color: var(--primary-400); letter-spacing: 1.2px; text-transform: uppercase; margin-top: 3px; }

        /* ── Sidebar User Card ────────── */
        .sidebar-user {
            padding: 16px 20px;
            border-bottom: 1px solid var(--sidebar-border);
            display: flex; align-items: center; gap: 12px;
            background: rgba(255,255,255,0.03);
            position: relative;
        }
        .user-avatar {
            width: 40px; height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-500), #a78bfa);
            color: #fff;
            font-weight: 800; font-size: 15px;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
            border: 2px solid rgba(255,255,255,0.12);
            position: relative;
        }
        /* Status indicator dot */
        .user-avatar::after {
            content: '';
            position: absolute;
            bottom: 0; right: 0;
            width: 10px; height: 10px;
            border-radius: 50%;
            background: #22c55e;
            border: 2px solid var(--sidebar-bg);
        }
        .user-info { overflow: hidden; flex: 1; }
        .user-name {
            font-size: 13px; font-weight: 700;
            color: #fff;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .user-role-badge {
            display: inline-flex; align-items: center; gap: 4px;
            padding: 2px 8px; border-radius: 99px;
            font-size: 9px; font-weight: 700;
            margin-top: 4px; letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        .role-admin { background: rgba(99,102,241,0.25); color: #a5b4fc; border: 1px solid rgba(99,102,241,0.3); }
        .role-anggota { background: rgba(16,185,129,0.2); color: #6ee7b7; border: 1px solid rgba(16,185,129,0.25); }

        /* ── Nav Links ───────────────── */
        .sidebar-nav { flex: 1; padding: 16px 12px; overflow-y: auto; position: relative; }
        .nav-section-label {
            font-size: 9px; font-weight: 700;
            color: rgba(255,255,255,0.25);
            letter-spacing: 1.5px; text-transform: uppercase;
            padding: 0 10px; margin-bottom: 8px; margin-top: 6px;
        }
        .nav-link {
            display: flex; align-items: center; gap: 10px;
            padding: 11px 14px; border-radius: var(--radius-md);
            text-decoration: none;
            font-size: 13px; font-weight: 600;
            color: var(--sidebar-text);
            transition: var(--transition-fast);
            margin-bottom: 3px;
            position: relative;
        }
        .nav-link:hover {
            background: rgba(255,255,255,0.06);
            color: var(--sidebar-text-hover);
        }
        .nav-link.active {
            background: linear-gradient(135deg, rgba(99,102,241,0.35), rgba(139,92,246,0.2));
            color: #fff;
            border: 1px solid rgba(99,102,241,0.25);
            box-shadow: 0 2px 12px rgba(99,102,241,0.15);
        }
        /* Premium active indicator — left accent bar */
        .nav-link.active::before {
            content: '';
            position: absolute;
            left: -1px; top: 20%; bottom: 20%;
            width: 3px; border-radius: 0 99px 99px 0;
            background: linear-gradient(180deg, var(--primary-400), #a78bfa);
        }
        .nav-icon {
            width: 20px; text-align: center;
            font-size: 14px; flex-shrink: 0;
        }

        /* ── Sidebar Logout ─────────── */
        .sidebar-footer {
            padding: 12px;
            padding-bottom: calc(12px + var(--sa-bottom));
            border-top: 1px solid var(--sidebar-border);
            position: relative;
        }
        .logout-link {
            display: flex; align-items: center; gap: 10px;
            padding: 11px 14px; border-radius: var(--radius-md);
            text-decoration: none;
            font-size: 13px; font-weight: 600;
            color: rgba(248,113,113,0.7);
            transition: var(--transition-fast);
        }
        .logout-link:hover {
            background: rgba(239,68,68,0.12);
            color: #fca5a5;
        }

        /* ── Main Content ───────────── */
        .main-content {
            flex: 1;
            min-width: 0;
            margin-left: var(--sidebar-width);
            display: flex; flex-direction: column;
            min-height: 100vh;
        }

        /* ── Desktop Topbar ─────────── */
        .topbar {
            background: var(--surface);
            border-bottom: 1px solid var(--border);
            padding: 0 32px;
            height: var(--topbar-h);
            display: flex; align-items: center; justify-content: space-between;
            position: sticky; top: 0; z-index: 30;
            box-shadow: var(--shadow-card);
        }
        .topbar-left h2 {
            font-size: 18px; font-weight: 800;
            color: #1e293b; letter-spacing: -0.3px;
        }
        .topbar-left p {
            font-size: 11px; color: #94a3b8;
            margin-top: 1px;
        }
        .topbar-right { display: flex; align-items: center; gap: 12px; }
        .topbar-badge {
            display: flex; align-items: center; gap: 6px;
            padding: 6px 12px; border-radius: var(--radius-sm);
            background: var(--surface-bg); border: 1px solid var(--border);
            font-size: 11px; font-weight: 600; color: #64748b;
        }
        .topbar-user-mini {
            display: flex; align-items: center; gap: 8px;
            padding: 6px 12px 6px 8px;
            border-radius: var(--radius-md);
            background: var(--surface-bg); border: 1px solid var(--border);
            font-size: 11px; font-weight: 700; color: #374151;
            cursor: default;
        }
        .topbar-avatar-mini {
            width: 26px; height: 26px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-500), #8b5cf6);
            color: #fff; font-size: 11px; font-weight: 800;
            display: flex; align-items: center; justify-content: center;
        }

        /* ── Mobile Top Bar ─────────── */
        .mobile-topbar {
            display: none;
            background: var(--sidebar-bg);
            padding: calc(6px + var(--sa-top)) 12px 6px;
            min-height: calc(var(--mobile-topbar-h) + var(--sa-top));
            align-items: center;
            justify-content: space-between;
            position: sticky; top: 0; z-index: 60;
            box-shadow: 0 2px 12px rgba(0,0,0,0.25);
        }
        .mobile-brand { display: flex; align-items: center; gap: 8px; flex-shrink: 0; }
        .mobile-brand-icon {
            width: 32px; height: 32px; border-radius: 8px;
            background: linear-gradient(135deg, var(--primary-500), #8b5cf6);
            display: flex; align-items: center; justify-content: center;
            font-size: 13px; color: #fff;
        }
        .mobile-brand-text { font-size: 13px; font-weight: 800; color: #fff; letter-spacing: -0.3px; }

        .mobile-user-pill {
            display: flex; align-items: center; gap: 6px;
            padding: 4px 8px 4px 5px;
            border-radius: 99px;
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.1);
            flex-shrink: 1;
            min-width: 0;
            overflow: hidden;
        }
        .mobile-user-avatar {
            width: 26px; height: 26px; border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-500), #8b5cf6);
            color: #fff; font-size: 10px; font-weight: 800;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }
        .mobile-user-info { line-height: 1; overflow: hidden; min-width: 0; }
        .mobile-user-name { font-size: 10px; font-weight: 700; color: #fff; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 70px; }
        .mobile-user-role { font-size: 8px; font-weight: 600; margin-top: 1px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .mobile-user-role.role-admin { color: #a5b4fc; }
        .mobile-user-role.role-anggota { color: #6ee7b7; }

        .mobile-menu-btn {
            width: 34px; height: 34px; border-radius: 8px;
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.12);
            color: #fff; font-size: 14px;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer; transition: var(--transition-fast);
            flex-shrink: 0;
        }
        .mobile-menu-btn:hover { background: rgba(255,255,255,0.15); }

        /* ── Sidebar Overlay (mobile) ─ */
        #sidebarOverlay {
            display: none;
            position: fixed; inset: 0;
            background: rgba(0,0,0,0.5);
            z-index: 49;
            backdrop-filter: blur(6px);
            -webkit-backdrop-filter: blur(6px);
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        #sidebarOverlay.active {
            display: block;
            opacity: 1;
        }

        /* ── Page Content Area ───────── */
        .page-content { flex: 1; padding: 28px 32px; }

        /* ── Global Footer ───────────── */
        .app-footer {
            background: var(--surface);
            border-top: 1px solid var(--border);
            padding: 16px 32px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 8px;
        }
        .app-footer span {
            font-size: 11px;
            color: #94a3b8;
        }

        /* ── Responsive ─────────────── */
        @media (max-width: 768px) {
            #sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s cubic-bezier(0.4,0,0.2,1);
            }
            #sidebar.open { transform: translateX(0); }
            .main-content { margin-left: 0; width: 100%; min-width: 0; }
            .topbar { display: none; }
            .mobile-topbar { display: flex; }
            .page-content { padding: 16px; }
            .app-footer { padding: 12px 16px calc(12px + var(--sa-bottom)); flex-direction: column; text-align: center; }
            table.data-table thead th { padding: 10px 8px; font-size: 9px; }
            table.data-table tbody td { padding: 8px 8px; font-size: 12px; }
        }

        @media (max-width: 480px) {
            .page-content { padding: 12px; }
            .card-header { padding: 14px 16px; }
            .card-body { padding: 16px; }
            .mobile-topbar { gap: 4px; padding-left: 10px; padding-right: 10px; }
            .mobile-user-name { max-width: 50px; }
        }

        /* ── Extra-small screens (≤360px) ── */
        @media (max-width: 360px) {
            .mobile-topbar { gap: 3px; padding-left: 8px; padding-right: 8px; }
            .mobile-brand-text { font-size: 11px; }
            .mobile-brand-icon { width: 28px; height: 28px; font-size: 11px; }
            .mobile-user-info { display: none; }
            .mobile-user-pill { padding: 3px; }
            .mobile-user-avatar { width: 28px; height: 28px; }
            .mobile-menu-btn { width: 30px; height: 30px; font-size: 12px; }
            .page-content { padding: 10px; }
            table.data-table thead th { padding: 8px 6px; font-size: 8px; }
            table.data-table tbody td { padding: 6px; font-size: 11px; }
        }

        @media (min-width: 769px) and (max-width: 1024px) {
            .page-content { padding: 24px; }
        }

        /* ── Utility: Card ───────────── */
        .card {
            background: var(--surface);
            border-radius: var(--radius-lg);
            border: 1px solid var(--border);
            box-shadow: var(--shadow-card);
        }
        .card-hover {
            transition: transform var(--transition-fast), box-shadow var(--transition-fast);
        }
        .card-hover:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-elevated);
        }
        .card-header {
            padding: 20px 24px;
            border-bottom: 1px solid var(--border);
        }
        .card-body {
            padding: 24px;
        }

        /* ── Alert Banners ──────────── */
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
            background: var(--expense-bg);
            border-left: 4px solid var(--expense);
            color: #9f1239;
        }
        .alert-success {
            background: var(--income-bg);
            border-left: 4px solid var(--income);
            color: #065f46;
        }

        /* ── Button Base ─────────────── */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 10px 20px;
            border-radius: var(--radius-md);
            font-weight: 600;
            font-size: 13px;
            transition: var(--transition-normal);
            cursor: pointer;
            border: none;
            text-decoration: none;
            line-height: 1;
        }
        .btn:active { transform: scale(0.97); }
        .btn-primary {
            background: var(--primary-600);
            color: #fff;
            box-shadow: 0 4px 14px rgba(79,70,229,0.25);
        }
        .btn-primary:hover { background: var(--primary-700); }
        .btn-secondary {
            background: var(--surface);
            color: #475569;
            border: 1px solid var(--border);
        }
        .btn-secondary:hover { background: var(--surface-bg); }
        .btn-danger {
            background: var(--expense);
            color: #fff;
        }
        .btn-danger:hover { background: #be123c; }
        .btn-sm { padding: 7px 14px; font-size: 11px; }

        /* ── Form Inputs ─────────────── */
        .input {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            font-size: 14px;
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: #1e293b;
            background: var(--surface);
            transition: var(--transition-fast);
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
            color: #64748b;
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

        /* ── Badge / Tag ─────────────── */
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
        .badge-income {
            background: var(--income-bg);
            color: var(--income);
            border: 1px solid rgba(5,150,105,0.2);
        }
        .badge-expense {
            background: var(--expense-bg);
            color: var(--expense);
            border: 1px solid rgba(225,29,72,0.2);
        }

        /* ── Table ───────────────────── */
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
            color: #64748b;
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
    </style>
</head>

<body>
<div class="app-wrapper">

    <!-- ══ Sidebar Navigation ══ -->
    <aside id="sidebar">
        <!-- Brand -->
        <div class="sidebar-brand">
            <div class="brand-icon"><i class="fa-solid fa-wallet"></i></div>
            <div class="brand-text">
                <div class="brand-title">Uangkas Kelas</div>
                <div class="brand-sub">Dashboard Keuangan</div>
            </div>
        </div>

        <!-- User Info -->
        <div class="sidebar-user">
            <div class="user-avatar"><?= $user_initial ?></div>
            <div class="user-info">
                <div class="user-name" title="<?= htmlspecialchars($user_nama) ?>"><?= htmlspecialchars($user_nama) ?></div>
                <span class="user-role-badge <?= $role_color_class ?>">
                    <i class="fa-solid <?= $role_icon ?>" style="font-size:8px"></i>
                    <?= $role_label ?>
                </span>
            </div>
        </div>

        <!-- Navigation -->
        <nav class="sidebar-nav">
            <div class="nav-section-label">Menu</div>
            <a href="dashboard.php" class="nav-link <?= $current_page === 'dashboard.php' ? 'active' : '' ?>">
                <span class="nav-icon"><i class="fa-solid fa-chart-pie"></i></span>
                Dashboard
            </a>
            <a href="transaksi.php" class="nav-link <?= $current_page === 'transaksi.php' ? 'active' : '' ?>">
                <span class="nav-icon"><i class="fa-solid fa-list-check"></i></span>
                Riwayat Transaksi
            </a>
            <a href="rekap.php" class="nav-link <?= $current_page === 'rekap.php' ? 'active' : '' ?>">
                <span class="nav-icon"><i class="fa-solid fa-table-cells-large"></i></span>
                Matriks Rekap Kas
            </a>
            <a href="anggota.php" class="nav-link <?= $current_page === 'anggota.php' ? 'active' : '' ?>">
                <span class="nav-icon"><i class="fa-solid fa-users"></i></span>
                Anggota Kelas
            </a>
        </nav>

        <!-- Logout -->
        <div class="sidebar-footer">
            <a href="logout.php"
               onclick="return confirm('Yakin ingin keluar dari aplikasi?')"
               class="logout-link">
                <span class="nav-icon"><i class="fa-solid fa-right-from-bracket"></i></span>
                Keluar Aplikasi
            </a>
        </div>
    </aside>

    <!-- Overlay for mobile -->
    <div id="sidebarOverlay"></div>

    <!-- ══ Main Content ══ -->
    <div class="main-content">

        <!-- Mobile Top Bar -->
        <header class="mobile-topbar">
            <div class="mobile-brand">
                <div class="mobile-brand-icon"><i class="fa-solid fa-wallet"></i></div>
                <span class="mobile-brand-text">Uangkas</span>
            </div>

            <!-- User info pill (always visible on mobile) -->
            <div class="mobile-user-pill">
                <div class="mobile-user-avatar"><?= $user_initial ?></div>
                <div class="mobile-user-info">
                    <div class="mobile-user-name"><?= htmlspecialchars($user_nama) ?></div>
                    <div class="mobile-user-role <?= $role_color_class ?>">
                        <i class="fa-solid <?= $role_icon ?>" style="font-size:8px; margin-right:2px"></i><?= $role_label ?>
                    </div>
                </div>
            </div>

            <button class="mobile-menu-btn" id="mobileMenuToggle" aria-label="Buka menu navigasi">
                <i class="fa-solid fa-bars" id="menuIcon"></i>
            </button>
        </header>

        <!-- Desktop Top Bar -->
        <div class="topbar">
            <div class="topbar-left">
                <h2>
                    <?php
                    switch($current_page) {
                        case 'dashboard.php': echo 'Dashboard Utama'; break;
                        case 'transaksi.php': echo 'Riwayat Kas Kelas'; break;
                        case 'rekap.php':     echo 'Matriks Pembayaran Kas'; break;
                        case 'anggota.php':   echo 'Manajemen Anggota'; break;
                        default:              echo 'Sistem Uangkas Kelas';
                    }
                    ?>
                </h2>
                <p>Pantau dan kelola keuangan kelas dengan akurat &amp; transparan</p>
            </div>
            <div class="topbar-right">
                <div class="topbar-badge">
                    <i class="fa-regular fa-clock text-slate-400"></i>
                    <span>T.A. <?= date('Y') ?></span>
                </div>
                <div class="topbar-user-mini">
                    <div class="topbar-avatar-mini"><?= $user_initial ?></div>
                    <div>
                        <div style="font-size:12px;font-weight:700;color:#1e293b"><?= htmlspecialchars($user_nama) ?></div>
                        <div style="font-size:9px;font-weight:600;color:<?= $is_admin ? '#4f46e5' : '#059669' ?>;text-transform:uppercase;letter-spacing:0.5px">
                            <i class="fa-solid <?= $role_icon ?>" style="font-size:8px;margin-right:2px"></i><?= $role_label ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Page Content -->
        <div class="page-content">