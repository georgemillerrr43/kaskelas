<?php
/**
 * index.php
 * Halaman Beranda Publik — Menampilkan ringkasan kas kelas secara real-time dan transparan.
 */

require_once 'config/database.php';
require_once 'includes/header-public.php';

$ringkasan = get_ringkasan_kas($pdo);
$total_pemasukan = $ringkasan['pemasukan'];
$total_pengeluaran = $ringkasan['pengeluaran'];
$saldo_kas = $ringkasan['saldo'];
$total_anggota = $ringkasan['total_anggota'];
?>

<!-- Hero Section -->
<section class="pub-hero">
    <h1>Informasi Kas Kelas secara Transparan</h1>
    <p>Pantau status pembayaran iuran mingguan dan riwayat transaksi secara real-time langsung dari database.</p>
    <div class="hero-actions no-print">
        <a href="public-rekap.php" class="btn btn-primary btn-sm">
            <i class="fa-solid fa-table-cells-large"></i> Rekap Kas Mingguan
        </a>
        <a href="public-riwayat.php" class="btn btn-outline btn-sm">
            <i class="fa-solid fa-clock-rotate-left"></i> Riwayat Transaksi
        </a>
    </div>
</section>

<!-- Ringkasan Statistik Real-Time -->
<div class="stat-grid">
    <div class="stat-card">
        <div class="stat-label">Total Pemasukan</div>
        <div class="stat-value" style="color:var(--income)"><?= format_rupiah($total_pemasukan) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Total Pengeluaran</div>
        <div class="stat-value" style="color:var(--expense)"><?= format_rupiah($total_pengeluaran) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Saldo Kas Saat Ini</div>
        <div class="stat-value" style="color:<?= $saldo_kas >= 0 ? 'var(--primary-600)' : 'var(--expense)' ?>">
            <?= format_rupiah($saldo_kas) ?>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Total Siswa Terdaftar</div>
        <div class="stat-value"><?= $total_anggota ?></div>
    </div>
</div>

<!-- Info Card Aksi Cepat -->
<div class="card-hover card" style="padding:24px;display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:16px;margin-top:12px;background:var(--tab-active-bg);border-color:rgba(99,102,241,0.15)">
    <div style="display:flex;align-items:center;gap:14px">
        <div style="width:44px;height:44px;border-radius:12px;background:linear-gradient(135deg,var(--primary-500),#8b5cf6);color:#fff;display:flex;align-items:center;justify-content:center;font-size:17px;flex-shrink:0">
            <i class="fa-solid fa-graduation-cap"></i>
        </div>
        <div>
            <h4 style="font-size:14px;font-weight:800;color:var(--text);margin:0 0 2px">Lihat Detail Data Kas Kelas</h4>
            <p style="font-size:12px;color:var(--text-muted);margin:0">Buka halaman Rekap untuk status iuran per siswa, atau Riwayat untuk semua mutasi kas.</p>
        </div>
    </div>
    <div style="display:flex;gap:8px;flex-shrink:0" class="no-print">
        <a href="public-rekap.php" class="btn btn-primary btn-sm"><i class="fa-solid fa-file-invoice"></i> Rekap</a>
        <a href="public-riwayat.php" class="btn btn-outline btn-sm"><i class="fa-solid fa-list"></i> Riwayat</a>
    </div>
</div>

<?php require_once 'includes/footer-public.php'; ?>
