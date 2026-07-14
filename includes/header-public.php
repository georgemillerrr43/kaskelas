<?php
// includes/header-public.php — Public header, no login required
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
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
        :root {
            --primary-400: #818cf8;
            --primary-500: #6366f1;
            --primary-600: #4f46e5;
            --surface: #ffffff;
            --surface-bg: #f8fafc;
            --border: rgba(0,0,0,0.06);
            --text: #0f172a;
            --text-muted: #64748b;
            --income: #059669;
            --expense: #e11d48;
            --radius: 12px;
            --radius-lg: 16px;
            --sa-bottom: env(safe-area-inset-bottom, 0px);
        }
        * { box-sizing: border-box; }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: var(--surface-bg);
            margin: 0; padding: 0;
            overflow-x: hidden; width: 100%;
            color: var(--text);
            -webkit-font-smoothing: antialiased;
        }

        /* ── Public Top Bar ───────────── */
        .pub-topbar {
            background: rgba(255,255,255,0.8);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-bottom: 1px solid var(--border);
            padding: 0 32px;
            height: 68px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky; top: 0; z-index: 50;
        }
        .pub-brand {
            display: flex; align-items: center; gap: 12px;
        }
        .pub-brand-mark {
            width: 40px; height: 40px; border-radius: 10px;
            background: linear-gradient(135deg, var(--primary-500), #8b5cf6);
            display: flex; align-items: center; justify-content: center;
            font-size: 17px; color: #fff;
            box-shadow: 0 6px 20px rgba(99,102,241,0.3);
        }
        .pub-brand-text { line-height: 1.2; }
        .pub-brand-title { font-size: 16px; font-weight: 800; color: var(--text); letter-spacing: -0.3px; }
        .pub-brand-sub { font-size: 9px; font-weight: 600; color: var(--primary-400); letter-spacing: 1.5px; text-transform: uppercase; margin-top: 1px; }

        .pub-actions { display: flex; align-items: center; gap: 10px; }

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
        .btn-primary:hover { background: #4338ca; }
        .btn-outline {
            background: transparent; color: var(--text);
            border: 1.5px solid #e2e8f0;
        }
        .btn-outline:hover { background: #f1f5f9; border-color: #cbd5e1; }
        .btn-sm { padding: 8px 16px; font-size: 12px; }
        .btn-ghost {
            background: transparent; color: var(--text-muted);
            padding: 8px 14px; font-size: 13px; font-weight: 600;
        }
        .btn-ghost:hover { background: #f1f5f9; color: var(--text); border-radius: var(--radius); }

        /* ── Page ──────────────────────── */
        .pub-content { padding: 36px 32px; max-width: 1280px; margin: 0 auto; }

        /* ── Hero ──────────────────────── */
        .pub-hero {
            text-align: center;
            padding: 60px 0 48px;
        }
        .pub-hero h1 {
            font-size: clamp(2rem, 4vw, 3.2rem);
            font-weight: 800;
            color: var(--text);
            letter-spacing: -1px;
            margin: 0 0 12px;
            line-height: 1.15;
        }
        .pub-hero p {
            font-size: clamp(0.9rem, 1.2vw, 1.05rem);
            color: var(--text-muted);
            max-width: 560px;
            margin: 0 auto 28px;
            line-height: 1.6;
        }

        /* ── Stat Cards ────────────────── */
        .stat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 14px;
            margin-bottom: 36px;
        }
        .stat-card {
            background: var(--surface);
            border-radius: var(--radius-lg);
            border: 1px solid var(--border);
            padding: 22px 24px;
            text-align: center;
        }
        .stat-label {
            font-size: 10px; font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
        }
        .stat-value {
            font-size: clamp(1.4rem, 2.5vw, 1.8rem);
            font-weight: 800;
            letter-spacing: -0.5px;
        }

        /* ── Section Title ─────────────── */
        .section-title {
            font-size: 18px; font-weight: 800;
            color: var(--text);
            letter-spacing: -0.3px;
            margin: 0 0 4px;
        }
        .section-desc {
            font-size: 12px; color: var(--text-muted);
            margin: 0 0 20px;
        }

        /* ── Card ──────────────────────── */
        .card {
            background: var(--surface);
            border-radius: var(--radius-lg);
            border: 1px solid var(--border);
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        }
        .card-header {
            padding: 20px 24px;
            border-bottom: 1px solid var(--border);
        }
        .card-body { padding: 24px; }

        /* ── Table ─────────────────────── */
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
        table.data-table tbody tr:hover { background: #f8fafc; }
        table.data-table tbody tr:last-child td { border-bottom: none; }

        /* ── Footer ────────────────────── */
        .pub-footer {
            background: var(--surface);
            border-top: 1px solid var(--border);
            padding: 20px 32px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 8px;
        }
        .pub-footer span { font-size: 11px; color: var(--text-muted); }

        /* ── Responsive ────────────────── */
        @media (max-width: 768px) {
            .pub-topbar { padding: 0 16px; height: 60px; }
            .pub-content { padding: 20px 16px; }
            .pub-hero { padding: 32px 0 24px; }
            .pub-hero h1 { font-size: 1.6rem; }
            .pub-footer { padding: 14px 16px calc(14px + var(--sa-bottom)); flex-direction: column; text-align: center; }
        }
        @media (max-width: 480px) {
            .pub-content { padding: 14px 12px; }
            .pub-hero { padding: 24px 0 20px; }
            .pub-brand-sub { display: none; }
            .stat-grid { grid-template-columns: repeat(2, 1fr); gap: 10px; }
            .stat-card { padding: 16px; }
        }

        /* ── Print Styles ──────────────── */
        @media print {
            .pub-topbar, .pub-footer, .no-print { display: none !important; }
            body { background: #fff; }
            .pub-content { padding: 0; }
            .stat-grid { break-inside: avoid; }
            table.data-table thead th { background: #f1f5f9 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .badge-check {
                -webkit-print-color-adjust: exact; print-color-adjust: exact;
            }
        }

        .badge {
            display: inline-flex; align-items: center; gap: 4px;
            padding: 3px 10px; border-radius: 99px;
            font-size: 10px; font-weight: 700;
            text-transform: uppercase; letter-spacing: 0.3px;
            white-space: nowrap;
        }
        .badge-income { background: #ecfdf5; color: var(--income); border: 1px solid rgba(5,150,105,0.15); }
        .badge-expense { background: #fff1f2; color: var(--expense); border: 1px solid rgba(225,29,72,0.15); }
    </style>
</head>
<body>

<!-- Public Top Bar -->
<header class="pub-topbar">
    <div class="pub-brand">
        <div class="pub-brand-mark"><i class="fa-solid fa-wallet"></i></div>
        <div class="pub-brand-text">
            <div class="pub-brand-title">Uangkas Kelas</div>
            <div class="pub-brand-sub">Sistem Informasi Kas</div>
        </div>
    </div>
    <div class="pub-actions">
        <?php if ($is_logged_in): ?>
            <a href="dashboard.php" class="btn btn-primary btn-sm">
                <i class="fa-solid fa-gauge-high"></i> Dashboard
            </a>
        <?php else: ?>
            <a href="login.php" class="btn btn-primary btn-sm">
                <i class="fa-solid fa-lock"></i> Login Bendahara
            </a>
        <?php endif; ?>
    </div>
</header>

<main class="pub-content">
