<?php
require_once 'config/database.php';
require_once 'includes/header-public.php';

$nama_bulan = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
];

try {
    $stmt = $pdo->query("SELECT SUM(jumlah) AS total FROM transaksi WHERE jenis = 'pemasukan'");
    $total_pemasukan = (float)($stmt->fetch()['total'] ?? 0);
    $stmt = $pdo->query("SELECT SUM(jumlah) AS total FROM transaksi WHERE jenis = 'pengeluaran'");
    $total_pengeluaran = (float)($stmt->fetch()['total'] ?? 0);
    $saldo_kas = $total_pemasukan - $total_pengeluaran;
    $stmt = $pdo->query("SELECT COUNT(*) AS total FROM anggota");
    $total_anggota = (int)($stmt->fetch()['total'] ?? 0);
} catch (PDOException $e) {
    $total_pemasukan = 0; $total_pengeluaran = 0; $saldo_kas = 0; $total_anggota = 0;
}
function fr($a) { return 'Rp ' . number_format($a, 0, ',', '.'); }
?>
<section class="pub-hero">
    <h1>Informasi Kas Kelas secara Transparan</h1>
    <p>Pantau status pembayaran iuran mingguan dan riwayat transaksi secara real-time.</p>
    <div class="hero-actions no-print">
        <a href="public-rekap.php" class="btn btn-primary btn-sm"><i class="fa-solid fa-table-cells-large"></i> Rekap Kas</a>
        <a href="public-riwayat.php" class="btn btn-outline btn-sm"><i class="fa-solid fa-clock-rotate-left"></i> Riwayat Transaksi</a>
    </div>
</section>

<div class="stat-grid">
    <div class="stat-card"><div class="stat-label">Total Pemasukan</div><div class="stat-value" style="color:var(--income)"><?= fr($total_pemasukan) ?></div></div>
    <div class="stat-card"><div class="stat-label">Total Pengeluaran</div><div class="stat-value" style="color:var(--expense)"><?= fr($total_pengeluaran) ?></div></div>
    <div class="stat-card"><div class="stat-label">Saldo Kas</div><div class="stat-value" style="color:<?= $saldo_kas >= 0 ? 'var(--primary-600)' : 'var(--expense)' ?>"><?= fr($saldo_kas) ?></div></div>
    <div class="stat-card"><div class="stat-label">Total Siswa</div><div class="stat-value"><?= $total_anggota ?></div></div>
</div>

<div class="card-hover card" style="padding:24px;display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:16px;margin-top:12px;background:var(--tab-active-bg);border-color:rgba(99,102,241,0.15)">
    <div style="display:flex;align-items:center;gap:14px">
        <div style="width:44px;height:44px;border-radius:12px;background:linear-gradient(135deg,var(--primary-500),#8b5cf6);color:#fff;display:flex;align-items:center;justify-content:center;font-size:17px;flex-shrink:0">
            <i class="fa-solid fa-graduation-cap"></i>
        </div>
        <div>
            <h4 style="font-size:14px;font-weight:800;color:var(--text);margin:0 0 2px">Lihat Detail Data Kas Kelas</h4>
            <p style="font-size:12px;color:var(--text-muted);margin:0">Buka halaman Rekap untuk status per siswa, atau Riwayat untuk semua transaksi.</p>
        </div>
    </div>
    <div style="display:flex;gap:8px;flex-shrink:0" class="no-print">
        <a href="public-rekap.php" class="btn btn-primary btn-sm"><i class="fa-solid fa-file-invoice"></i> Rekap</a>
        <a href="public-riwayat.php" class="btn btn-outline btn-sm"><i class="fa-solid fa-list"></i> Riwayat</a>
    </div>
</div>

<?php require_once 'includes/footer-public.php'; ?>
