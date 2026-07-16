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
    // Summary
    $stmt = $pdo->query("SELECT SUM(jumlah) AS total FROM transaksi WHERE jenis = 'pemasukan'");
    $total_pemasukan = (float)($stmt->fetch()['total'] ?? 0);
    $stmt = $pdo->query("SELECT SUM(jumlah) AS total FROM transaksi WHERE jenis = 'pengeluaran'");
    $total_pengeluaran = (float)($stmt->fetch()['total'] ?? 0);
    $saldo_kas = $total_pemasukan - $total_pengeluaran;
    $stmt = $pdo->query("SELECT COUNT(*) AS total FROM anggota");
    $total_anggota = (int)($stmt->fetch()['total'] ?? 0);

    // All transactions for Riwayat tab
    $stmt_t = $pdo->query("
        SELECT t.*, a.nama AS nama_anggota
        FROM transaksi t
        LEFT JOIN anggota a ON t.anggota_id = a.id
        ORDER BY t.tanggal DESC, t.id DESC
        LIMIT 100
    ");
    $semua_transaksi = $stmt_t->fetchAll();

    // Rekap matriks for Lunas / Belum Bayar
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
    $anggota_list = []; $payments = []; $semua_transaksi = [];
}

function fr($a) {
    return 'Rp ' . number_format($a, 0, ',', '.');
}

// Split members
$lunas_ids = []; $blm_ids = [];
foreach ($anggota_list as $m) {
    $total_bayar = 0;
    for ($w = 1; $w <= 5; $w++) {
        if (isset($payments[$m['id']][$w])) $total_bayar += $payments[$m['id']][$w];
    }
    if ($total_bayar > 0) $lunas_ids[] = $m['id'];
    else $blm_ids[] = $m['id'];
}
$count_lunas = count($lunas_ids);
$count_blm = count($blm_ids);
$count_riwayat = count($semua_transaksi);
?>

<!-- Hero -->
<section class="pub-hero">
    <h1>Informasi Kas Kelas secara Transparan</h1>
    <p>Pantau iuran mingguan, saldo kas, dan riwayat pembayaran siswa secara real-time.</p>
    <div class="hero-actions">
        <button id="printBtn" class="btn btn-soft btn-sm no-print">
            <i class="fa-solid fa-print"></i> Cetak / PDF
        </button>
    </div>
</section>

<!-- Stats -->
<div class="stat-grid">
    <div class="stat-card">
        <div class="stat-label">Total Pemasukan</div>
        <div class="stat-value" style="color:var(--income)"><?= fr($total_pemasukan) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Total Pengeluaran</div>
        <div class="stat-value" style="color:var(--expense)"><?= fr($total_pengeluaran) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Saldo Kas</div>
        <div class="stat-value" style="color:<?= $saldo_kas >= 0 ? 'var(--primary-600)' : 'var(--expense)' ?>"><?= fr($saldo_kas) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Total Siswa</div>
        <div class="stat-value"><?= $total_anggota ?></div>
    </div>
</div>

<?php if ($error): ?>
    <div style="background:var(--expense-bg);border-left:4px solid var(--expense);color:var(--expense);padding:14px 18px;border-radius:12px;margin-bottom:24px;font-size:14px">
        <i class="fa-solid fa-circle-exclamation" style="margin-right:8px"></i><?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>

<!-- Tab Bar -->
<div class="tab-bar no-print">
    <button class="tab-btn active" data-tab="lunas">
        <i class="fa-solid fa-check-circle"></i>
        <span class="tab-label">Lunas</span>
        <span class="tab-count"><?= $count_lunas ?></span>
    </button>
    <button class="tab-btn" data-tab="belum">
        <i class="fa-solid fa-circle-minus"></i>
        <span class="tab-label">Belum Bayar</span>
        <span class="tab-count"><?= $count_blm ?></span>
    </button>
    <button class="tab-btn" data-tab="riwayat">
        <i class="fa-solid fa-clock-rotate-left"></i>
        <span class="tab-label">Riwayat</span>
        <span class="tab-count"><?= $count_riwayat ?></span>
    </button>
</div>

<!-- ══════════════════════════════════════
     T A B  1  —  L U N A S
     ══════════════════════════════════════ -->
<div id="pane-lunas" class="tab-pane active">
    <div class="card">
        <div class="card-header">
            <div>
                <h2 class="card-title">Sudah Bayar Kas</h2>
                <p class="card-subtitle">Siswa yang sudah lunas — <?= $nama_bulan[$bulan_aktif] ?> <?= $tahun_aktif ?></p>
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
                        <th style="min-width:160px;border-right:1px solid var(--border-table)">Nama Siswa</th>
                        <th style="width:76px;text-align:center">Mg 1</th>
                        <th style="width:76px;text-align:center">Mg 2</th>
                        <th style="width:76px;text-align:center">Mg 3</th>
                        <th style="width:76px;text-align:center">Mg 4</th>
                        <th style="width:76px;text-align:center">Mg 5</th>
                        <th style="width:100px;border-left:1px solid var(--border-table);text-align:center;background:var(--surface-bg)">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $found_lunas = false;
                    $no = 1;
                    foreach ($anggota_list as $m):
                        $total_bayar = 0;
                        for ($w = 1; $w <= 5; $w++) {
                            if (isset($payments[$m['id']][$w])) $total_bayar += $payments[$m['id']][$w];
                        }
                        if ($total_bayar <= 0) continue;
                        $found_lunas = true;
                    ?>
                        <tr class="searchable-row">
                            <td style="text-align:center;font-weight:600;color:var(--text-dim);font-size:12px"><?= $no++ ?></td>
                            <td style="border-right:1px solid var(--border-table)">
                                <span style="font-weight:600;color:var(--text)"><?= htmlspecialchars($m['nama']) ?></span>
                                <?php if ($m['nis']): ?>
                                    <span style="display:block;font-size:9px;color:var(--text-dim);font-family:monospace">NIS: <?= htmlspecialchars($m['nis']) ?></span>
                                <?php endif; ?>
                            </td>
                            <?php for ($w = 1; $w <= 5; $w++):
                                $paid = isset($payments[$m['id']][$w]);
                                $amt = $paid ? $payments[$m['id']][$w] : 0;
                            ?>
                                <td style="text-align:center">
                                    <?php if ($paid): ?>
                                        <span style="display:inline-flex;flex-direction:column;align-items:center">
                                            <span class="status-dot" style="background:var(--income-bg);color:var(--income)">
                                                <i class="fa-solid fa-check"></i>
                                            </span>
                                            <span style="font-size:8px;font-weight:700;color:var(--income);margin-top:2px"><?= number_format($amt, 0, ',', '.') ?></span>
                                        </span>
                                    <?php else: ?>
                                        <span style="display:inline-flex;flex-direction:column;align-items:center">
                                            <span class="status-dot" style="background:var(--surface-bg);color:var(--text-dim)">
                                                <i class="fa-solid fa-minus"></i>
                                            </span>
                                            <span style="font-size:8px;color:var(--text-dim);margin-top:2px">-</span>
                                        </span>
                                    <?php endif; ?>
                                </td>
                            <?php endfor; ?>
                            <td style="text-align:center;border-left:1px solid var(--border-table);background:var(--surface-bg)">
                                <span style="display:inline-block;padding:4px 12px;background:var(--tab-active-bg);color:var(--tab-active-text);border-radius:8px;font-size:11px;font-weight:700;border:1px solid rgba(99,102,241,0.15)">
                                    <?= fr($total_bayar) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; if (!$found_lunas): ?>
                        <tr>
                            <td colspan="8" style="text-align:center;padding:60px 16px;color:var(--text-dim)">
                                <i class="fa-solid fa-face-smile empty-icon" style="color:var(--text-dim)"></i>
                                Belum ada siswa yang lunas bulan ini.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════
     T A B  2  —  B E L U M  B A Y A R
     ══════════════════════════════════════ -->
<div id="pane-belum" class="tab-pane">
    <div class="card">
        <div class="card-header">
            <div>
                <h2 class="card-title">Belum Bayar Kas</h2>
                <p class="card-subtitle">Siswa yang belum membayar — <?= $nama_bulan[$bulan_aktif] ?> <?= $tahun_aktif ?></p>
            </div>
            <form method="GET" action="index.php" style="display:flex;gap:8px;flex-wrap:wrap">
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
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width:40px;text-align:center">No</th>
                        <th style="min-width:200px">Nama Siswa</th>
                        <th style="width:100px">NIS</th>
                        <th style="width:100px">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $found_blm = false;
                    $no = 1;
                    foreach ($anggota_list as $m):
                        $total_bayar = 0;
                        for ($w = 1; $w <= 5; $w++) {
                            if (isset($payments[$m['id']][$w])) $total_bayar += $payments[$m['id']][$w];
                        }
                        if ($total_bayar > 0) continue;
                        $found_blm = true;
                    ?>
                        <tr class="searchable-row">
                            <td style="text-align:center;font-weight:600;color:var(--text-dim);font-size:12px"><?= $no++ ?></td>
                            <td><span style="font-weight:600;color:var(--text)"><?= htmlspecialchars($m['nama']) ?></span></td>
                            <td><span style="font-family:monospace;font-size:13px;color:var(--text-dim)"><?= htmlspecialchars($m['nis'] ?? '-') ?></span></td>
                            <td>
                                <span class="badge" style="background:var(--expense-bg);color:var(--expense);border:1px solid rgba(225,29,72,0.15)">
                                    <i class="fa-solid fa-hourglass"></i> Belum Bayar
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; if (!$found_blm): ?>
                        <tr>
                            <td colspan="4" style="text-align:center;padding:60px 16px;color:var(--text-dim)">
                                <i class="fa-solid fa-party-horn empty-icon" style="color:var(--income)"></i>
                                Semua siswa sudah lunas untuk bulan ini!
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════
     T A B  3  —  R I W A Y A T
     ══════════════════════════════════════ -->
<div id="pane-riwayat" class="tab-pane">
    <div class="card">
        <div class="card-header">
            <div>
                <h2 class="card-title">Riwayat Transaksi</h2>
                <p class="card-subtitle">Seluruh aktivitas kas tercatat — 100 transaksi terakhir</p>
            </div>
            <div style="position:relative;width:100%;max-width:240px">
                <span style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--text-dim);font-size:12px;pointer-events:none">
                    <i class="fa-solid fa-search"></i>
                </span>
                <input type="text" id="searchInput" class="no-print"
                       style="width:100%;padding:9px 12px 9px 34px;border:1px solid var(--border-table);border-radius:8px;font-size:12px;font-family:inherit;background:var(--input-bg);color:var(--text);outline:none"
                       placeholder="Cari keterangan atau nama...">
            </div>
        </div>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width:40px;text-align:center">No</th>
                        <th style="width:100px">Tanggal</th>
                        <th style="width:100px;text-align:center">Jenis</th>
                        <th>Keterangan</th>
                        <th style="width:160px">Siswa</th>
                        <th style="width:130px;text-align:right">Jumlah</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($semua_transaksi)): ?>
                        <tr>
                            <td colspan="6" style="text-align:center;padding:60px 16px;color:var(--text-dim)">
                                <i class="fa-solid fa-receipt empty-icon" style="color:var(--text-dim)"></i>
                                Belum ada transaksi tercatat.
                            </td>
                        </tr>
                    <?php else: $no = 1; foreach ($semua_transaksi as $tr): ?>
                        <tr class="searchable-row">
                            <td style="text-align:center;font-weight:600;color:var(--text-dim);font-size:12px"><?= $no++ ?></td>
                            <td style="font-weight:500"><?= date('d/m/Y', strtotime($tr['tanggal'])) ?></td>
                            <td style="text-align:center">
                                <span class="badge" style="background:<?= $tr['jenis'] === 'pemasukan' ? 'var(--income-bg)' : 'var(--expense-bg)' ?>;color:<?= $tr['jenis'] === 'pemasukan' ? 'var(--income)' : 'var(--expense)' ?>;border:1px solid rgba(<?= $tr['jenis'] === 'pemasukan' ? '5,150,105' : '225,29,72' ?>,0.15)">
                                    <i class="fa-solid <?= $tr['jenis'] === 'pemasukan' ? 'fa-arrow-down-long' : 'fa-arrow-up-long' ?>"></i>
                                    <?= $tr['jenis'] ?>
                                </span>
                            </td>
                            <td style="font-weight:500;color:var(--text)">
                                <?= htmlspecialchars($tr['keterangan']) ?>
                                <?php if ($tr['minggu']): ?>
                                    <span style="display:block;font-size:9px;color:var(--primary-400);font-weight:600;margin-top:2px">
                                        <i class="fa-regular fa-calendar-check"></i> Mg <?= $tr['minggu'] ?>, <?= $nama_bulan[$tr['bulan']] ?? '' ?> <?= $tr['tahun'] ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td style="color:var(--text-muted);font-weight:500">
                                <?= htmlspecialchars($tr['nama_anggota'] ?? '-') ?>
                            </td>
                            <td style="text-align:right;font-weight:700;font-family:monospace;color:<?= $tr['jenis'] === 'pemasukan' ? 'var(--income)' : 'var(--expense)' ?>">
                                <?= $tr['jenis'] === 'pemasukan' ? '+' : '-' ?><?= number_format($tr['jumlah'], 0, ',', '.') ?>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'includes/footer-public.php'; ?>
