<?php
// index.php — Public landing page
require_once 'config/database.php';
require_once 'includes/header-public.php';

$error = '';
$nama_bulan = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
];

$bulan_aktif = isset($_GET['bulan']) && $_GET['bulan'] !== '' ? (int)$_GET['bulan'] : (int)date('n');
$tahun_aktif = isset($_GET['tahun']) ? (int)$_GET['tahun'] : (int)date('Y');

try {
    // Summary
    $stmt = $pdo->query("SELECT SUM(jumlah) AS total FROM transaksi WHERE jenis = 'pemasukan'");
    $total_pemasukan = (float)($stmt->fetch()['total'] ?? 0);
    $stmt = $pdo->query("SELECT SUM(jumlah) AS total FROM transaksi WHERE jenis = 'pengeluaran'");
    $total_pengeluaran = (float)($stmt->fetch()['total'] ?? 0);
    $saldo_kas = $total_pemasukan - $total_pengeluaran;
    $stmt = $pdo->query("SELECT COUNT(*) AS total FROM anggota");
    $total_anggota = (int)($stmt->fetch()['total'] ?? 0);

    // Rekap matriks
    $stmt_m = $pdo->query("SELECT id, nis, nama FROM anggota ORDER BY nama ASC");
    $anggota_list = $stmt_m->fetchAll();
    $stmt_p = $pdo->prepare("SELECT anggota_id, minggu, SUM(jumlah) AS total_bayar
        FROM transaksi WHERE jenis = 'pemasukan' AND anggota_id IS NOT NULL
        AND bulan = ? AND tahun = ? GROUP BY anggota_id, minggu");
    $stmt_p->execute([$bulan_aktif, $tahun_aktif]);
    $payment_data = $stmt_p->fetchAll();
    $payments = [];
    foreach ($payment_data as $pay) {
        $payments[(int)$pay['anggota_id']][(int)$pay['minggu']] = (float)$pay['total_bayar'];
    }
} catch (PDOException $e) {
    $error = 'Terjadi kesalahan: ' . $e->getMessage();
    $anggota_list = []; $payments = [];
}

function formatRupiah($a) {
    return 'Rp ' . number_format($a, 0, ',', '.');
}
?>

<!-- Hero -->
<section class="pub-hero">
    <h1>Informasi Kas Kelas secara Transparan</h1>
    <p>Pantau iuran mingguan, saldo kas, dan rekapitulasi pembayaran siswa secara real-time. Data akurat dan dapat dipercaya.</p>
    <div style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap">
        <button id="printBtn" class="btn btn-outline btn-sm">
            <i class="fa-solid fa-print"></i> Cetak / PDF
        </button>
    </div>
</section>

<!-- Summary Stats -->
<div class="stat-grid">
    <div class="stat-card">
        <div class="stat-label">Total Pemasukan</div>
        <div class="stat-value" style="color:var(--income)"><?= formatRupiah($total_pemasukan) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Total Pengeluaran</div>
        <div class="stat-value" style="color:var(--expense)"><?= formatRupiah($total_pengeluaran) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Saldo Kas</div>
        <div class="stat-value" style="color:<?= $saldo_kas >= 0 ? 'var(--primary-600)' : 'var(--expense)' ?>"><?= formatRupiah($saldo_kas) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Total Siswa</div>
        <div class="stat-value" style="color:var(--text)"><?= $total_anggota ?></div>
    </div>
</div>

<!-- Error -->
<?php if ($error): ?>
    <div style="background:#fff1f2;border-left:4px solid var(--expense);color:#9f1239;padding:14px 18px;border-radius:12px;margin-bottom:24px;font-size:14px">
        <i class="fa-solid fa-circle-exclamation" style="margin-right:8px"></i><?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>

<!-- Filter & Rekap -->
<div class="card" style="margin-bottom:36px;overflow:hidden">
    <div class="card-header" style="display:flex;flex-direction:column;gap:12px">
        <div style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:12px">
            <div>
                <h2 class="section-title">Rekapitulasi Kas Mingguan</h2>
                <p class="section-desc" style="margin-bottom:0">Status iuran mingguan siswa — <strong><?= $nama_bulan[$bulan_aktif] ?> <?= $tahun_aktif ?></strong></p>
            </div>
            <form id="filterForm" method="GET" action="index.php" style="display:flex;gap:8px;flex-wrap:wrap">
                <select name="bulan" class="filter-select" style="padding:8px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:12px;font-weight:600;font-family:inherit;background:#fff;outline:none;cursor:pointer">
                    <?php foreach ($nama_bulan as $num => $name): ?>
                        <option value="<?= $num ?>" <?= $num === $bulan_aktif ? 'selected' : '' ?>><?= $name ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="tahun" class="filter-select" style="padding:8px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:12px;font-weight:600;font-family:inherit;background:#fff;outline:none;cursor:pointer">
                    <?php for ($y = (int)date('Y') - 3; $y <= (int)date('Y') + 2; $y++): ?>
                        <option value="<?= $y ?>" <?= $y === $tahun_aktif ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </form>
        </div>
        <div style="display:flex;flex-wrap:wrap;gap:10px;align-items:center;font-size:12px;color:var(--text-muted);background:#f8fafc;padding:10px 14px;border-radius:10px;border:1px solid #e2e8f0">
            <span style="font-weight:700;color:var(--text)"><i class="fa-solid fa-circle-info" style="margin-right:4px;color:var(--primary-400)"></i> Keterangan:</span>
            <span><span style="display:inline-block;width:16px;height:16px;border-radius:50%;background:#059669;vertical-align:middle;margin-right:4px"></span> Lunas</span>
            <span><span style="display:inline-block;width:16px;height:16px;border-radius:50%;background:#e2e8f0;vertical-align:middle;margin-right:4px"></span> Belum Bayar</span>
        </div>
    </div>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width:40px;text-align:center">No</th>
                    <th style="min-width:150px;border-right:1px solid #e2e8f0">Nama Siswa</th>
                    <th style="width:80px;text-align:center;background:#fafaff">Mg 1</th>
                    <th style="width:80px;text-align:center;background:#fafaff">Mg 2</th>
                    <th style="width:80px;text-align:center;background:#fafaff">Mg 3</th>
                    <th style="width:80px;text-align:center;background:#fafaff">Mg 4</th>
                    <th style="width:80px;text-align:center;background:#fafaff">Mg 5</th>
                    <th style="width:100px;border-left:1px solid #e2e8f0;background:#f8faff;text-align:center">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($anggota_list)): ?>
                    <tr>
                        <td colspan="8" style="text-align:center;padding:60px 16px;color:#94a3b8">
                            <i class="fa-solid fa-users-slash" style="font-size:28px;display:block;margin-bottom:10px;color:#cbd5e1"></i>
                            Belum ada data siswa.
                        </td>
                    </tr>
                <?php else: $no = 1; foreach ($anggota_list as $m): ?>
                    <?php $total = 0; for ($w = 1; $w <= 5; $w++) { if (isset($payments[$m['id']][$w])) $total += $payments[$m['id']][$w]; } ?>
                    <tr>
                        <td style="text-align:center;font-weight:600;color:#94a3b8;font-size:12px"><?= $no++ ?></td>
                        <td style="border-right:1px solid #e2e8f0">
                            <span style="font-weight:600;color:var(--text)"><?= htmlspecialchars($m['nama']) ?></span>
                            <?php if ($m['nis']): ?>
                                <span style="display:block;font-size:9px;color:#94a3b8;font-family:monospace">NIS: <?= htmlspecialchars($m['nis']) ?></span>
                            <?php endif; ?>
                        </td>
                        <?php for ($w = 1; $w <= 5; $w++):
                            $paid = isset($payments[$m['id']][$w]);
                            $amt = $paid ? $payments[$m['id']][$w] : 0;
                        ?>
                            <td style="text-align:center">
                                <?php if ($paid): ?>
                                    <span style="display:inline-flex;flex-direction:column;align-items:center">
                                        <span style="width:28px;height:28px;border-radius:50%;background:#ecfdf5;display:flex;align-items:center;justify-content:center;color:#059669;font-size:12px;margin:0 auto" class="badge-check">
                                            <i class="fa-solid fa-check"></i>
                                        </span>
                                        <span style="font-size:8px;color:#059669;font-weight:700;margin-top:2px"><?= number_format($amt, 0, ',', '.') ?></span>
                                    </span>
                                <?php else: ?>
                                    <span style="display:inline-flex;flex-direction:column;align-items:center">
                                        <span style="width:28px;height:28px;border-radius:50%;background:#f1f5f9;display:flex;align-items:center;justify-content:center;color:#cbd5e1;font-size:12px;margin:0 auto">
                                            <i class="fa-solid fa-minus"></i>
                                        </span>
                                        <span style="font-size:8px;color:#cbd5e1;margin-top:2px">-</span>
                                    </span>
                                <?php endif; ?>
                            </td>
                        <?php endfor; ?>
                        <td style="text-align:center;border-left:1px solid #e2e8f0;background:#fafaff">
                            <span style="display:inline-block;padding:4px 12px;background:#eef2ff;color:#4f46e5;border-radius:8px;font-size:11px;font-weight:700;border:1px solid #e0e7ff">
                                <?= formatRupiah($total) ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'includes/footer-public.php'; ?>
