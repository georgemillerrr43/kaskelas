<?php
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
    $stmt = $pdo->query("SELECT SUM(jumlah) AS total FROM transaksi WHERE jenis = 'pemasukan'");
    $total_pemasukan = (float)($stmt->fetch()['total'] ?? 0);
    $stmt = $pdo->query("SELECT SUM(jumlah) AS total FROM transaksi WHERE jenis = 'pengeluaran'");
    $total_pengeluaran = (float)($stmt->fetch()['total'] ?? 0);
    $saldo_kas = $total_pemasukan - $total_pengeluaran;
    $stmt = $pdo->query("SELECT COUNT(*) AS total FROM anggota");
    $total_anggota = (int)($stmt->fetch()['total'] ?? 0);

    // Transactions
    $stmt_t = $pdo->query("SELECT t.*, a.nama AS nama_anggota FROM transaksi t LEFT JOIN anggota a ON t.anggota_id = a.id ORDER BY t.tanggal DESC, t.id DESC LIMIT 100");
    $semua_transaksi = $stmt_t->fetchAll();

    // Matriks for tab 1
    $stmt_m = $pdo->query("SELECT id, nis, nama FROM anggota ORDER BY nama ASC");
    $anggota_list = $stmt_m->fetchAll();
    $stmt_p = $pdo->prepare("SELECT anggota_id, minggu, SUM(jumlah) AS total_bayar FROM transaksi WHERE jenis = 'pemasukan' AND anggota_id IS NOT NULL AND bulan = ? AND tahun = ? GROUP BY anggota_id, minggu");
    $stmt_p->execute([$bulan_aktif, $tahun_aktif]);
    $payments = [];
    foreach ($stmt_p->fetchAll() as $pay) {
        $payments[(int)$pay['anggota_id']][(int)$pay['minggu']] = (float)$pay['total_bayar'];
    }
} catch (PDOException $e) {
    $error = 'Terjadi kesalahan: ' . $e->getMessage();
    $anggota_list = []; $payments = []; $semua_transaksi = [];
}

function fr($a) { return 'Rp ' . number_format($a, 0, ',', '.'); }
$count_siswa = count($anggota_list);
$count_riwayat = count($semua_transaksi);
?>

<section class="pub-hero">
    <h1>Informasi Kas Kelas secara Transparan</h1>
    <p>Pantau status pembayaran iuran mingguan dan riwayat transaksi secara real-time.</p>
    <div class="hero-actions no-print">
        <button onclick="printTab('pane-siswa')" class="btn btn-outline btn-sm"><i class="fa-solid fa-print"></i> Cetak Daftar Siswa</button>
        <button onclick="printTab('pane-riwayat')" class="btn btn-outline btn-sm"><i class="fa-solid fa-print"></i> Cetak Riwayat</button>
    </div>
</section>

<div class="stat-grid">
    <div class="stat-card"><div class="stat-label">Total Pemasukan</div><div class="stat-value" style="color:var(--income)"><?= fr($total_pemasukan) ?></div></div>
    <div class="stat-card"><div class="stat-label">Total Pengeluaran</div><div class="stat-value" style="color:var(--expense)"><?= fr($total_pengeluaran) ?></div></div>
    <div class="stat-card"><div class="stat-label">Saldo Kas</div><div class="stat-value" style="color:<?= $saldo_kas >= 0 ? 'var(--primary-600)' : 'var(--expense)' ?>"><?= fr($saldo_kas) ?></div></div>
    <div class="stat-card"><div class="stat-label">Total Siswa</div><div class="stat-value"><?= $total_anggota ?></div></div>
</div>

<?php if ($error): ?>
    <div style="background:var(--expense-bg);border-left:4px solid var(--expense);color:var(--expense);padding:14px 18px;border-radius:12px;margin-bottom:24px;font-size:14px"><i class="fa-solid fa-circle-exclamation" style="margin-right:8px"></i><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<!-- Tab Bar -->
<div class="tab-bar no-print">
    <button class="tab-btn active" data-tab="siswa"><i class="fa-solid fa-users"></i> <span>Daftar Siswa</span> <span class="tab-count"><?= $count_siswa ?></span></button>
    <button class="tab-btn" data-tab="riwayat"><i class="fa-solid fa-clock-rotate-left"></i> <span>Riwayat</span> <span class="tab-count"><?= $count_riwayat ?></span></button>
</div>

<!-- ══ TAB 1: DAFTAR SISWA ══ -->
<div id="pane-siswa" class="tab-pane active">
    <div class="card">
        <div class="card-header">
            <div>
                <h2 class="card-title">Daftar Siswa — Status Pembayaran</h2>
                <p class="card-subtitle">Status iuran mingguan — <?= $nama_bulan[$bulan_aktif] ?> <?= $tahun_aktif ?></p>
            </div>
            <form id="filterForm" method="GET" action="index.php" style="display:flex;gap:8px;flex-wrap:wrap">
                <select name="bulan" class="filter-select" style="padding:8px 12px;border:1px solid var(--border-table);border-radius:8px;font-size:12px;font-weight:600;font-family:inherit;background:var(--input-bg);color:var(--text);outline:none;cursor:pointer">
                    <?php foreach ($nama_bulan as $num => $name): ?>
                        <option value="<?= $num ?>" <?= $num === $bulan_aktif ? 'selected' : '' ?>><?= $name ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="tahun" class="filter-select" style="padding:8px 12px;border:1px solid var(--border-table);border-radius:8px;font-size:12px;font-weight:600;font-family:inherit;background:var(--input-bg);color:var(--text);outline:none;cursor:pointer">
                    <?php for ($y = (int)date('Y') - 3; $y <= (int)date('Y') + 2; $y++): ?>
                        <option value="<?= $y ?>" <?= $y === $tahun_aktif ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </form>
        </div>
        <div style="padding:12px 24px;border-bottom:1px solid var(--border)">
            <div class="legend-box">
                <span style="font-weight:700;color:var(--text)"><i class="fa-solid fa-circle-info" style="margin-right:4px;color:var(--primary-400)"></i> Keterangan:</span>
                <span><span class="legend-dot" style="background:var(--income)"></span> Lunas</span>
                <span><span class="legend-dot" style="background:var(--border-table)"></span> Belum Bayar</span>
            </div>
        </div>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width:40px;text-align:center">No</th>
                        <th style="min-width:150px;border-right:1px solid var(--border-table)">Nama Siswa</th>
                        <th style="width:72px;text-align:center">Mg 1</th>
                        <th style="width:72px;text-align:center">Mg 2</th>
                        <th style="width:72px;text-align:center">Mg 3</th>
                        <th style="width:72px;text-align:center">Mg 4</th>
                        <th style="width:72px;text-align:center">Mg 5</th>
                        <th style="width:100px;border-left:1px solid var(--border-table);text-align:center;background:var(--surface-bg)">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($anggota_list)): ?>
                        <tr><td colspan="8" style="text-align:center;padding:60px 16px;color:var(--text-dim)"><i class="fa-solid fa-users-slash empty-icon"></i>Belum ada data siswa.</td></tr>
                    <?php else: $no = 1; foreach ($anggota_list as $m):
                        $total = 0;
                        for ($w = 1; $w <= 5; $w++) { if (isset($payments[$m['id']][$w])) $total += $payments[$m['id']][$w]; }
                    ?>
                        <tr class="searchable-row">
                            <td style="text-align:center;font-weight:600;color:var(--text-dim);font-size:12px"><?= $no++ ?></td>
                            <td style="border-right:1px solid var(--border-table)">
                                <span style="font-weight:600;color:var(--text)"><?= htmlspecialchars($m['nama']) ?></span>
                                <?php if ($m['nis']): ?><span style="display:block;font-size:9px;color:var(--text-dim);font-family:monospace">NIS: <?= htmlspecialchars($m['nis']) ?></span><?php endif; ?>
                            </td>
                            <?php for ($w = 1; $w <= 5; $w++):
                                $paid = isset($payments[$m['id']][$w]);
                                $amt = $paid ? $payments[$m['id']][$w] : 0;
                            ?>
                                <td style="text-align:center">
                                    <?php if ($paid): ?>
                                        <span style="display:inline-flex;flex-direction:column;align-items:center">
                                            <span class="status-dot" style="background:var(--income-bg);color:var(--income)"><i class="fa-solid fa-check"></i></span>
                                            <span style="font-size:8px;font-weight:700;color:var(--income);margin-top:2px"><?= number_format($amt,0,',','.') ?></span>
                                        </span>
                                    <?php else: ?>
                                        <span style="display:inline-flex;flex-direction:column;align-items:center">
                                            <span class="status-dot" style="background:var(--surface-bg);color:var(--text-dim)"><i class="fa-solid fa-minus"></i></span>
                                            <span style="font-size:8px;color:var(--text-dim);margin-top:2px">-</span>
                                        </span>
                                    <?php endif; ?>
                                </td>
                            <?php endfor; ?>
                            <td style="text-align:center;border-left:1px solid var(--border-table);background:var(--surface-bg)">
                                <span style="display:inline-block;padding:4px 12px;background:var(--tab-active-bg);color:var(--tab-active-text);border-radius:8px;font-size:11px;font-weight:700;border:1px solid rgba(99,102,241,0.15)"><?= fr($total) ?></span>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
        <!-- TTD -->
        <div class="ttd-section no-print" style="padding:0 24px 24px">
            <div class="ttd-box">
                <img src="assets/images/ttd.svg" alt="Tanda Tangan" style="height:50px;margin-bottom:4px">
                <div class="ttd-line"></div>
                <div class="ttd-name">Bendahara Kelas</div>
                <div class="ttd-role">Mengetahui</div>
            </div>
        </div>
    </div>
</div>

<!-- ══ TAB 2: RIWAYAT ══ -->
<div id="pane-riwayat" class="tab-pane">
    <div class="card">
        <div class="card-header">
            <div>
                <h2 class="card-title">Riwayat Transaksi</h2>
                <p class="card-subtitle">Seluruh aktivitas kas — 100 transaksi terakhir</p>
            </div>
            <div style="position:relative;width:100%;max-width:220px" class="no-print">
                <span style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--text-dim);font-size:12px;pointer-events:none"><i class="fa-solid fa-search"></i></span>
                <input type="text" id="searchInput" style="width:100%;padding:9px 12px 9px 34px;border:1px solid var(--border-table);border-radius:8px;font-size:12px;font-family:inherit;background:var(--input-bg);color:var(--text);outline:none" placeholder="Cari...">
            </div>
        </div>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width:36px;text-align:center">No</th>
                        <th style="width:90px">Tanggal</th>
                        <th style="width:80px;text-align:center">Jenis</th>
                        <th>Keterangan</th>
                        <th style="width:140px">Siswa</th>
                        <th style="width:120px;text-align:right">Jumlah</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($semua_transaksi)): ?>
                        <tr><td colspan="6" style="text-align:center;padding:60px 16px;color:var(--text-dim)"><i class="fa-solid fa-receipt empty-icon"></i>Belum ada transaksi.</td></tr>
                    <?php else: $no = 1; foreach ($semua_transaksi as $tr): ?>
                        <tr class="searchable-row">
                            <td style="text-align:center;font-weight:600;color:var(--text-dim);font-size:12px"><?= $no++ ?></td>
                            <td style="font-weight:500"><?= date('d/m/Y', strtotime($tr['tanggal'])) ?></td>
                            <td style="text-align:center">
                                <span class="badge" style="background:<?= $tr['jenis']==='pemasukan'?'var(--income-bg)':'var(--expense-bg)'?>;color:<?= $tr['jenis']==='pemasukan'?'var(--income)':'var(--expense)'?>">
                                    <i class="fa-solid <?= $tr['jenis']==='pemasukan'?'fa-arrow-down-long':'fa-arrow-up-long'?>"></i> <?= $tr['jenis'] ?>
                                </span>
                            </td>
                            <td style="font-weight:500">
                                <?= htmlspecialchars($tr['keterangan']) ?>
                                <?php if ($tr['minggu']): ?><span style="display:block;font-size:9px;color:var(--primary-400);font-weight:600;margin-top:2px"><i class="fa-regular fa-calendar-check"></i> Mg <?= $tr['minggu'] ?>, <?= $nama_bulan[$tr['bulan']]??'' ?> <?= $tr['tahun'] ?></span><?php endif; ?>
                            </td>
                            <td style="color:var(--text-muted);font-weight:500"><?= htmlspecialchars($tr['nama_anggota'] ?? '-') ?></td>
                            <td style="text-align:right;font-weight:700;font-family:monospace;color:<?= $tr['jenis']==='pemasukan'?'var(--income)':'var(--expense)'?>"><?= $tr['jenis']==='pemasukan'?'+':'-' ?><?= number_format($tr['jumlah'],0,',','.') ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
        <!-- TTD -->
        <div class="ttd-section no-print" style="padding:0 24px 24px">
            <div class="ttd-box">
                <img src="assets/images/ttd.svg" alt="Tanda Tangan" style="height:50px;margin-bottom:4px">
                <div class="ttd-line"></div>
                <div class="ttd-name">Bendahara Kelas</div>
                <div class="ttd-role">Mengetahui</div>
            </div>
        </div>
    </div>
</div>

<script>
function printTab(paneId) {
    // Hide all panes, show only the target one temporarily for print
    document.querySelectorAll('.tab-pane').forEach(function(p) { p.style.display = 'none'; });
    document.getElementById(paneId).style.display = 'block';
    window.print();
    // Restore
    document.querySelectorAll('.tab-pane').forEach(function(p) { p.style.display = ''; });
    // Re-activate the right tab
}
</script>

<?php require_once 'includes/footer-public.php'; ?>
