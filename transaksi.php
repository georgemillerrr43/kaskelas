<?php
// transaksi.php
require_once 'config/database.php';
require_once 'includes/header.php';

$error = '';
$success = '';

// Ambil Saldo Kas Aktual Global (Untuk Validasi Pengeluaran Backend/Frontend)
try {
    $stmt_bal = $pdo->query("
        SELECT 
            (SELECT COALESCE(SUM(jumlah), 0) FROM transaksi WHERE jenis = 'pemasukan') - 
            (SELECT COALESCE(SUM(jumlah), 0) FROM transaksi WHERE jenis = 'pengeluaran') 
        AS saldo
    ");
    $actual_saldo_kas = (float)($stmt_bal->fetch()['saldo'] ?? 0);
} catch (PDOException $e) {
    $actual_saldo_kas = 0;
}

// 1. Tangani Penambahan Transaksi (Hanya Admin)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_type']) && $_POST['action_type'] === 'add') {
    $tanggal = $_POST['tanggal'] ?? date('Y-m-d');
    $jenis = $_POST['jenis'] ?? 'pemasukan';
    $jumlah = (float)($_POST['jumlah'] ?? 0);
    $keterangan = trim($_POST['keterangan'] ?? '');

    // Handle file upload untuk pengeluaran
    $bukti_path = null;
    if ($jenis === 'pengeluaran' && isset($_FILES['bukti']) && $_FILES['bukti']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];
        $max_size = 2 * 1024 * 1024;
        if (!in_array($_FILES['bukti']['type'], $allowed)) {
            $error = 'File bukti harus berupa gambar (JPEG/PNG/WebP)!';
        } elseif ($_FILES['bukti']['size'] > $max_size) {
            $error = 'Ukuran file maksimal 2MB!';
        } else {
            $ext = pathinfo($_FILES['bukti']['name'], PATHINFO_EXTENSION);
            $filename = 'bukti_' . time() . '_' . uniqid() . '.' . $ext;
            $dest = 'assets/uploads/' . $filename;
            if (move_uploaded_file($_FILES['bukti']['tmp_name'], $dest)) {
                $bukti_path = $dest;
            }
        }
    }

    // if upload error, jangan lanjut
    if ($error === '') {
    // Uang Kas Mingguan Anggota
    $is_kas = isset($_POST['is_kas']) ? (int)$_POST['is_kas'] : 0;
    $anggota_id = ($jenis === 'pemasukan' && $is_kas === 1) ? (int)$_POST['anggota_id'] : null;
    $minggu = ($jenis === 'pemasukan' && $is_kas === 1) ? (int)$_POST['minggu'] : null;
    $bulan = ($jenis === 'pemasukan' && $is_kas === 1) ? (int)$_POST['bulan'] : null;
    $tahun = ($jenis === 'pemasukan' && $is_kas === 1) ? (int)$_POST['tahun'] : null;

    if ($jumlah <= 0 || $keterangan === '') {
        $error = 'Jumlah transaksi harus lebih dari Rp 0 dan keterangan wajib diisi!';
    } else {
        try {
            $pdo->beginTransaction();

            // Ambil saldo aktual dengan kunci transaksi (FOR UPDATE) untuk menghindari race condition
            $stmt_lock = $pdo->query("
                SELECT 
                    (SELECT COALESCE(SUM(jumlah), 0) FROM transaksi WHERE jenis = 'pemasukan') - 
                    (SELECT COALESCE(SUM(jumlah), 0) FROM transaksi WHERE jenis = 'pengeluaran') 
                AS saldo 
                FOR UPDATE
            ");
            $current_lock_balance = (float)($stmt_lock->fetch()['saldo'] ?? 0);

            // Validasi Saldo Pengeluaran (Anti-Minus) di Backend
            if ($jenis === 'pengeluaran' && $jumlah > $current_lock_balance) {
                throw new Exception('Saldo kas tidak mencukupi untuk pengeluaran ini! Saldo kas saat ini: Rp ' . number_format($current_lock_balance, 0, ',', '.'));
            }

            // Simpan Transaksi (Dengan Kolom `minggu` + `bukti`)
            $stmt = $pdo->prepare("
                INSERT INTO transaksi (tanggal, jenis, jumlah, keterangan, bukti, anggota_id, minggu, bulan, tahun, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$tanggal, $jenis, $jumlah, $keterangan, $bukti_path, $anggota_id ?: null, $minggu ?: null, $bulan ?: null, $tahun ?: null, $_SESSION['user_id']]);
            
            $pdo->commit();
            $success = 'Transaksi berhasil dicatat ke database!';
            
            // Update saldo kas setelah insert sukses
            $actual_saldo_kas = $jenis === 'pemasukan' ? ($actual_saldo_kas + $jumlah) : ($actual_saldo_kas - $jumlah);
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = $e->getMessage();
        }
    }
}

// 2. Tangani Penghapusan Transaksi (Hanya Admin)
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id > 0) {
        try {
            $stmt = $pdo->prepare("DELETE FROM transaksi WHERE id = ?");
            $stmt->execute([$id]);
            $success = 'Transaksi berhasil dihapus dari riwayat!';
            
            // Rekalkulasi saldo aktual
            $stmt_bal = $pdo->query("
                SELECT 
                    (SELECT COALESCE(SUM(jumlah), 0) FROM transaksi WHERE jenis = 'pemasukan') - 
                    (SELECT COALESCE(SUM(jumlah), 0) FROM transaksi WHERE jenis = 'pengeluaran') 
                AS saldo
            ");
            $actual_saldo_kas = (float)($stmt_bal->fetch()['saldo'] ?? 0);
        } catch (PDOException $e) {
            $error = 'Gagal menghapus transaksi: ' . $e->getMessage();
        }
    }
}

// 3. Setup Filter Transaksi
$filter_jenis = $_GET['filter_jenis'] ?? 'semua';
$filter_bulan = isset($_GET['filter_bulan']) && $_GET['filter_bulan'] !== '' ? (int)$_GET['filter_bulan'] : '';
$filter_tahun = isset($_GET['filter_tahun']) && $_GET['filter_tahun'] !== '' ? (int)$_GET['filter_tahun'] : date('Y');

$conditions = [];
$params = [];

if ($filter_jenis !== 'semua') {
    $conditions[] = "t.jenis = ?";
    $params[] = $filter_jenis;
}
if ($filter_bulan !== '') {
    $conditions[] = "MONTH(t.tanggal) = ?";
    $params[] = $filter_bulan;
}
if ($filter_tahun !== '') {
    $conditions[] = "YEAR(t.tanggal) = ?";
    $params[] = $filter_tahun;
}

$where_clause = '';
if (!empty($conditions)) {
    $where_clause = "WHERE " . implode(" AND ", $conditions);
}

// 4. Ambil Data Transaksi yang Sesuai Filter
try {
    $query = "
        SELECT t.*, a.nama AS nama_anggota, u.nama AS nama_petugas 
        FROM transaksi t
        LEFT JOIN anggota a ON t.anggota_id = a.id
        LEFT JOIN users u ON t.created_by = u.id
        $where_clause
        ORDER BY t.tanggal DESC, t.id DESC
    ";
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $transactions = $stmt->fetchAll();
    
    // Hitung ringkasan khusus data terfilter
    $total_pemasukan_filter = 0;
    $total_pengeluaran_filter = 0;
    foreach ($transactions as $t) {
        if ($t['jenis'] === 'pemasukan') {
            $total_pemasukan_filter += (float)$t['jumlah'];
        } else {
            $total_pengeluaran_filter += (float)$t['jumlah'];
        }
    }
    $saldo_filter = $total_pemasukan_filter - $total_pengeluaran_filter;

    // Ambil daftar semua anggota untuk form drop-down
    $stmt_m = $pdo->query("SELECT id, nis, nama FROM anggota ORDER BY nama ASC");
    $anggota_list = $stmt_m->fetchAll();
} catch (PDOException $e) {
    $error = 'Gagal mengambil riwayat transaksi: ' . $e->getMessage();
    $transactions = [];
    $anggota_list = [];
}

// Array nama bulan bahasa Indonesia
$nama_bulan = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 
    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 
    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
];
?>

<!-- Banner Alerts -->
<?php if ($error !== ''): ?>
    <div class="alert alert-error mb-6"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<?php if ($success !== ''): ?>
    <div class="alert alert-success mb-6"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<div class="space-y-6">
    <!-- Header Control Bar: Filter & Tambah Transaksi -->
    <div class="card p-4 md:p-5 flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        <form method="GET" action="transaksi.php" class="grid grid-cols-2 sm:grid-cols-4 gap-3 flex-grow max-w-4xl">
            <div>
                <label for="filter_jenis" class="input-label">Jenis Transaksi</label>
                <select name="filter_jenis" id="filter_jenis" class="input select text-xs">
                    <option value="semua" <?= $filter_jenis === 'semua' ? 'selected' : '' ?>>Semua Jenis</option>
                    <option value="pemasukan" <?= $filter_jenis === 'pemasukan' ? 'selected' : '' ?>>Pemasukan (+)</option>
                    <option value="pengeluaran" <?= $filter_jenis === 'pengeluaran' ? 'selected' : '' ?>>Pengeluaran (-)</option>
                </select>
            </div>
            <div>
                <label for="filter_bulan" class="input-label">Bulan</label>
                <select name="filter_bulan" id="filter_bulan" class="input select text-xs">
                    <option value="">Semua Bulan</option>
                    <?php foreach ($nama_bulan as $num => $name): ?>
                        <option value="<?= $num ?>" <?= $filter_bulan === $num ? 'selected' : '' ?>><?= $name ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="filter_tahun" class="input-label">Tahun</label>
                <select name="filter_tahun" id="filter_tahun" class="input select text-xs">
                    <option value="">Semua Tahun</option>
                    <?php
                    $start_y = (int)date('Y') - 5;
                    $end_y = (int)date('Y') + 2;
                    for ($y = $start_y; $y <= $end_y; $y++):
                    ?>
                        <option value="<?= $y ?>" <?= $y === $filter_tahun ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="btn btn-sm" style="background:var(--primary-600);color:#fff;width:100%;border:none">
                    <i class="fa-solid fa-filter"></i> Terapkan Filter
                </button>
            </div>
        </form>

        <div class="flex flex-wrap items-center gap-3">
            <button onclick="exportLaporanPDF()" class="btn btn-secondary btn-sm">
                <i class="fa-solid fa-file-pdf"></i> Export PDF
            </button>
            <button onclick="toggleTransactionModal(true)" class="btn btn-primary btn-sm">
                <i class="fa-solid fa-plus"></i> Catat Kas
            </button>
        </div>
    </div>

    <!-- Ringkasan Filter -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 md:gap-4">
        <div class="card p-3 md:p-4 flex items-center justify-between card-hover">
            <div class="min-w-0">
                <div class="input-label mb-0.5">Pemasukan Terfilter</div>
                <span style="font-size:14px;font-weight:bold;color:var(--income);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;display:block">Rp <?= number_format($total_pemasukan_filter, 0, ',', '.') ?></span>
            </div>
            <div style="width:32px;height:32px;border-radius:8px;background:var(--income-bg);color:var(--income);display:flex;align-items:center;justify-content:center;font-size:12px;flex-shrink:0;margin-left:8px">
                <i class="fa-solid fa-arrow-down-long"></i>
            </div>
        </div>
        <div class="card p-3 md:p-4 flex items-center justify-between card-hover">
            <div class="min-w-0">
                <div class="input-label mb-0.5">Pengeluaran Terfilter</div>
                <span style="font-size:14px;font-weight:bold;color:var(--expense);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;display:block">Rp <?= number_format($total_pengeluaran_filter, 0, ',', '.') ?></span>
            </div>
            <div style="width:32px;height:32px;border-radius:8px;background:var(--expense-bg);color:var(--expense);display:flex;align-items:center;justify-content:center;font-size:12px;flex-shrink:0;margin-left:8px">
                <i class="fa-solid fa-arrow-up-long"></i>
            </div>
        </div>
        <div class="card p-3 md:p-4 flex items-center justify-between card-hover">
            <div class="min-w-0">
                <div class="input-label mb-0.5">Saldo Hasil Filter</div>
                <span style="font-size:14px;font-weight:bold;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;display:block;color:<?= $saldo_filter >= 0 ? 'var(--primary-600)' : 'var(--expense)' ?>">Rp <?= number_format($saldo_filter, 0, ',', '.') ?></span>
            </div>
            <div style="width:32px;height:32px;border-radius:8px;background:var(--income-bg);color:var(--income);display:flex;align-items:center;justify-content:center;font-size:12px;flex-shrink:0;margin-left:8px">
                <i class="fa-solid fa-calculator"></i>
            </div>
        </div>
    </div>

    <!-- Ledger Table -->
    <div class="card overflow-hidden">
        <div class="card-header">
            <h4 class="font-bold" style="color:var(--text);font-size:14px">Riwayat Transaksi Kas</h4>
            <p style="font-size:12px;color:var(--text-muted);margin-top:2px">Ditemukan <strong><?= count($transactions) ?></strong> transaksi berdasarkan filter</p>
        </div>

        <div class="table-wrap">
            <table class="data-table" id="transTable">
                <thead>
                    <tr>
                        <th class="text-center" style="width:48px">No</th>
                        <th style="width:100px">Tanggal</th>
                        <th class="text-center" style="width:110px">Jenis</th>
                        <th>Keterangan</th>
                        <th style="width:180px">Pembayar</th>
                        <th class="text-right" style="width:140px">Jumlah</th>
                        <th class="text-center" style="width:60px">Bukti</th>
                            <th class="text-center" style="width:80px">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($transactions)): ?>
                        <tr>
                            <td colspan="8" style="text-align:center;padding:60px 16px;color:var(--text-dim)">
                                <i class="fa-solid fa-receipt" style="font-size:32px;display:block;margin-bottom:10px;color:var(--text-dim)"></i>
                                Tidak ada data transaksi yang cocok dengan filter.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php $no = 1; foreach ($transactions as $trans): ?>
                            <tr>
                                <td style="text-align:center;font-weight:600;color:var(--text-dim);font-size:12px"><?= $no++ ?></td>
                                <td class="font-medium"><?= date('d/m/Y', strtotime($trans['tanggal'])) ?></td>
                                <td class="text-center">
                                    <span class="badge <?= $trans['jenis'] === 'pemasukan' ? 'badge-income' : 'badge-expense' ?>">
                                        <i class="fa-solid <?= $trans['jenis'] === 'pemasukan' ? 'fa-arrow-down-long' : 'fa-arrow-up-long' ?>"></i>
                                        <?= $trans['jenis'] ?>
                                    </span>
                                </td>
                                <td class="font-semibold">
                                    <?= htmlspecialchars($trans['keterangan']) ?>
                                    <?php if ($trans['minggu'] && $trans['bulan'] && $trans['tahun']): ?>
                                        <span class="block text-[10px] text-indigo-500 font-medium mt-0.5">
                                            <i class="fa-regular fa-calendar-check mr-0.5"></i> Kas Minggu <?= $trans['minggu'] ?>, <?= $nama_bulan[$trans['bulan']] ?> <?= $trans['tahun'] ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if (!empty($trans['bukti'])): ?>
                                        <span style="display:block;margin-top:4px">
                                            <a href="#" onclick="window.open('<?= htmlspecialchars($trans['bukti']) ?>','_blank','width=800,height=600');return false" style="font-size:10px;color:var(--primary-600);font-weight:600;text-decoration:none">
                                                <i class="fa-solid fa-image"></i> Lihat Bukti
                                            </a>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="font-medium" style="color:var(--text-muted)">
                                    <?= htmlspecialchars($trans['nama_anggota'] ?? '-') ?>
                                </td>
                                <td class="text-right font-mono font-bold" style="color:<?= $trans['jenis'] === 'pemasukan' ? 'var(--income)' : 'var(--expense)' ?>">
                                    <?= $trans['jenis'] === 'pemasukan' ? '+' : '-' ?>Rp <?= number_format($trans['jumlah'], 0, ',', '.') ?>
                                </td>
                                    <td class="text-center">
                                        <a href="transaksi.php?action=delete&id=<?= $trans['id'] ?>"
                                           onclick="return confirm('Apakah Anda yakin ingin menghapus data transaksi ini? Penghapusan akan memicu perubahan pada saldo saat ini.')"
                                           style="display:inline-flex;width:28px;height:28px;border-radius:8px;align-items:center;justify-content:center;font-size:12px;text-decoration:none;transition:0.15s;color:var(--text-muted);background:var(--surface-bg)" onmouseenter="this.style.background='var(--expense-bg)';this.style.color='var(--expense)'" onmouseleave="this.style.background='var(--surface-bg)';this.style.color='var(--text-muted)'"
                                           title="Hapus Transaksi">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </a>
                                    </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Dialog Input Transaksi -->
<div id="transactionModal" class="modal-overlay">
    <div class="modal-card" id="modalCard">
        <div class="modal-header" style="background:linear-gradient(135deg,var(--primary-600),#7c3aed)">
            <h3 style="color:#fff"><i class="fa-solid fa-wallet mr-2"></i> Catat Transaksi Baru</h3>
            <button onclick="toggleTransactionModal(false)" class="modal-close" style="color:rgba(255,255,255,0.7);background:rgba(255,255,255,0.1)"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <form action="transaksi.php" method="POST" id="transactionForm" class="p-6 space-y-5" enctype="multipart/form-data">
            <input type="hidden" name="action_type" value="add">

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="jenis" class="input-label">Jenis Transaksi</label>
                    <select name="jenis" id="jenis" required onchange="handleJenisChange()" class="input select text-sm font-semibold">
                        <option value="pemasukan" selected>Pemasukan (+)</option>
                        <option value="pengeluaran">Pengeluaran (-)</option>
                    </select>
                </div>
                <div>
                    <label for="tanggal" class="input-label">Tanggal</label>
                    <input type="date" name="tanggal" id="tanggal" required value="<?= date('Y-m-d') ?>" class="input text-sm font-medium">
                </div>
            </div>

            <!-- Dues Section -->
            <div id="duesSection" style="padding:16px;border-radius:12px;border:1px solid var(--border);background:var(--surface-bg)">
                <label style="display:inline-flex;align-items:center;font-size:12px;font-weight:600;color:var(--text);cursor:pointer;gap:8px">
                    <input type="checkbox" name="is_kas" id="is_kas" value="1" onchange="toggleDuesForm()"
                           style="width:16px;height:16px;accent-color:var(--primary-600)">
                    Apakah ini pembayaran kas mingguan siswa?
                </label>

                <div id="duesFields" class="hidden space-y-3 pt-3" style="border-top:1px solid var(--border)">
                    <div>
                        <label for="anggota_id" class="input-label" style="color:var(--text)">Pilih Siswa</label>
                        <select name="anggota_id" id="anggota_id" class="input text-xs font-semibold select">
                            <option value="">-- Pilih Anggota --</option>
                            <?php foreach ($anggota_list as $member): ?>
                                <option value="<?= $member['id'] ?>"><?= htmlspecialchars($member['nama']) ?> (NIS: <?= htmlspecialchars($member['nis'] ?? '-') ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="grid grid-cols-3 gap-2">
                        <div>
                            <label for="minggu" class="input-label" style="color:#312e81">Minggu Ke</label>
                            <select name="minggu" id="minggu" class="input text-xs select">
                                <?php
                                $c_w = (int)ceil(date('j') / 7);
                                for ($w = 1; $w <= 5; $w++):
                                ?>
                                    <option value="<?= $w ?>" <?= $c_w === $w ? 'selected' : '' ?>>Minggu <?= $w ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div>
                            <label for="bulan" class="input-label" style="color:#312e81">Bulan</label>
                            <select name="bulan" id="bulan" class="input text-xs select">
                                <?php foreach ($nama_bulan as $num => $name): ?>
                                    <option value="<?= $num ?>" <?= (int)date('n') === $num ? 'selected' : '' ?>><?= $name ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label for="tahun" class="input-label" style="color:#312e81">Tahun</label>
                            <select name="tahun" id="tahun" class="input text-xs select">
                                <?php
                                $c_y = (int)date('Y');
                                for ($y = $c_y - 2; $y <= $c_y + 2; $y++):
                                ?>
                                    <option value="<?= $y ?>" <?= $c_y === $y ? 'selected' : '' ?>><?= $y ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div>
                <label for="jumlah" class="input-label">Jumlah (Rupiah)</label>
                <div class="relative">
                    <span style="position:absolute;top:0;bottom:0;left:0;display:flex;align-items:center;padding-left:12px;color:var(--text-muted);font-weight:bold;font-size:14px;pointer-events:none">Rp</span>
                    <input type="number" name="jumlah" id="jumlah" required min="100" step="100"
                           class="input pl-10 text-sm font-semibold"
                           placeholder="Contoh: 2000" value="2000">
                </div>
            </div>

            <div>
                <label for="keterangan" class="input-label">Keterangan / Deskripsi</label>
                <textarea name="keterangan" id="keterangan" required rows="2"
                          class="input text-sm"
                          placeholder="Keterangan singkat transaksi"></textarea>
            </div>

            <!-- Upload bukti (khusus pengeluaran) -->
            <div id="buktiSection" style="display:none">
                <label for="bukti" class="input-label">Upload Bukti / Nota (Gambar)</label>
                <input type="file" name="bukti" id="bukti" accept="image/jpeg,image/png,image/webp"
                       class="input text-sm" style="padding:8px 12px">
                <p style="font-size:10px;color:var(--text-dim);margin-top:4px">Format: JPEG/PNG/WebP. Maks 2MB.</p>
            </div>

            <script>
            document.getElementById('jenis').addEventListener('change', function() {
                document.getElementById('buktiSection').style.display = this.value === 'pengeluaran' ? 'block' : 'none';
            });
            </script>

            <button type="submit" class="btn btn-primary w-full py-3">
                <i class="fa-solid fa-floppy-disk"></i> Simpan Transaksi
            </button>
        </form>
    </div>
</div>

<!-- Libraries for PDF Generation -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.29/jspdf.plugin.autotable.min.js"></script>

<script>
    // Menyimpan Saldo Kas Aktual Global untuk Validasi Frontend JS
    const currentActualBalance = <?= $actual_saldo_kas ?>;
    const userRole = '<?= $user_role ?>';

    // 1. Toggles modal visibility
    function toggleTransactionModal(show) {
        const modal = document.getElementById('transactionModal');
        if (!modal) return;
        if (show) {
            modal.classList.add('open');
        } else {
            modal.classList.remove('open');
        }
    }

    // 2. Toggles dues panel under modal
    function toggleDuesForm() {
        const isKasCheckbox = document.getElementById('is_kas');
        const duesFields = document.getElementById('duesFields');
        const selectAnggota = document.getElementById('anggota_id');
        const selectMinggu = document.getElementById('minggu');
        const selectBulan = document.getElementById('bulan');
        const selectTahun = document.getElementById('tahun');

        if (isKasCheckbox && isKasCheckbox.checked) {
            duesFields.classList.remove('hidden');
            selectAnggota.setAttribute('required', 'true');
            // Auto update keterangan saat siswa dipilih
            updateDuesKeterangan();
        } else {
            duesFields.classList.add('hidden');
            selectAnggota.removeAttribute('required');
        }
    }

    function updateDuesKeterangan() {
        const selectAnggota = document.getElementById('anggota_id');
        const selectMinggu = document.getElementById('minggu');
        const selectBulan = document.getElementById('bulan');
        const selectTahun = document.getElementById('tahun');
        const inputKeterangan = document.getElementById('keterangan');

        if (selectAnggota && selectAnggota.selectedIndex > 0 && selectMinggu && selectBulan && selectTahun) {
            const namaSiswa = selectAnggota.options[selectAnggota.selectedIndex].text.split(" (NIS")[0];
            const nomorMinggu = selectMinggu.value;
            const namaBulan = selectBulan.options[selectBulan.selectedIndex].text;
            const tahun = selectTahun.value;
            inputKeterangan.value = `Uang Kas Mgg ${nomorMinggu} - ${namaBulan} ${tahun} - ${namaSiswa}`;
        }
    }

    // Bind event update keterangan pada pilihan dues
    document.addEventListener("DOMContentLoaded", function() {
        const selectAnggota = document.getElementById('anggota_id');
        const selectMinggu = document.getElementById('minggu');
        const selectBulan = document.getElementById('bulan');
        const selectTahun = document.getElementById('tahun');
        
        if (selectAnggota) selectAnggota.addEventListener('change', updateDuesKeterangan);
        if (selectMinggu) selectMinggu.addEventListener('change', updateDuesKeterangan);
        if (selectBulan) selectBulan.addEventListener('change', updateDuesKeterangan);
        if (selectTahun) selectTahun.addEventListener('change', updateDuesKeterangan);
    });

    // 3. Handles form type changes
    function handleJenisChange() {
        const jenisSelect = document.getElementById('jenis');
        const duesSection = document.getElementById('duesSection');
        const isKasCheckbox = document.getElementById('is_kas');
        
        if (jenisSelect && jenisSelect.value === 'pengeluaran') {
            duesSection.classList.add('hidden');
            if (isKasCheckbox) isKasCheckbox.checked = false;
            toggleDuesForm();
        } else if (duesSection) {
            duesSection.classList.remove('hidden');
        }
    }

    // 4. FRONTEND STRIKT VALIDATION (Anti-Minus Saldo Pengeluaran)
    const transactionForm = document.getElementById('transactionForm');
    if (transactionForm) {
        transactionForm.addEventListener('submit', function (e) {
            const jenis = document.getElementById('jenis').value;
            const jumlah = parseFloat(document.getElementById('jumlah').value) || 0;
            
            if (jenis === 'pengeluaran' && jumlah > currentActualBalance) {
                // Batalkan proses submit
                e.preventDefault();
                // Tampilkan pesan error mencolok
                alert(`Saldo kas tidak mencukupi untuk pengeluaran ini!\n\nPengeluaran diajukan: Rp ${jumlah.toLocaleString('id-ID')}\nSaldo kas tersedia: Rp ${currentActualBalance.toLocaleString('id-ID')}`);
                document.getElementById('jumlah').focus();
            }
        });
    }

    // 5. PDF EXPORT GENERATION — Professional Light Spreadsheet Style
    function exportLaporanPDF() {
        const sigImg = new Image();
        // Enable CORS to allow canvas drawing
        sigImg.crossOrigin = "anonymous";
        // Use relative path for subfolders and root domain compatibility
        sigImg.src = 'assets/images/ttd.svg';

        sigImg.onload = function() {
            // Create a canvas to convert SVG to standard PNG base64 data URL
            const canvas = document.createElement('canvas');
            canvas.width = 300;
            canvas.height = 120;
            const ctx = canvas.getContext('2d');
            ctx.drawImage(sigImg, 0, 0, 300, 120);
            try {
                const pngDataUrl = canvas.toDataURL('image/png');
                generatePDF(pngDataUrl);
            } catch(e) {
                // Fallback: draw text signature if canvas conversion fails due to browser security flags
                generatePDF(null);
            }
        };
        sigImg.onerror = function() {
            // Fallback: generate PDF without signature image if file cannot be loaded
            generatePDF(null);
        };
    }

    function generatePDF(sigImgData) {
        const doc = new window.jspdf.jsPDF('p', 'mm', 'a4');
        const PW = 210, PH = 297;
        const ML = 14, MR = 14;
        const CW = PW - ML - MR; // content width

        // ── Warna Palet ────────────────────────────────────────────────
        const C = {
            white:      [255, 255, 255],
            pageGray:   [248, 250, 252],   // faint page bg
            borderGray: [203, 213, 225],   // cell borders (slate-300)
            headerBg:   [241, 245, 249],   // thead background (slate-100)
            headerText: [15,  23,  42],    // slate-900
            subText:    [71,  85,  105],   // slate-600
            dimText:    [148, 163, 184],   // slate-400
            rowAlt:     [248, 250, 252],   // zebra stripe (slate-50)
            incomeText: [4,   120,  87],   // emerald-700
            incomeBg:   [236, 253, 245],   // emerald-50
            expText:    [190,  18,  60],   // rose-700
            expBg:      [255, 241, 242],   // rose-50
            accentBlue: [79,  70,  229],   // indigo-600
            accentLtBg: [238, 242, 255],   // indigo-50
            black:      [15,  23,  42],    // slate-900
            summaryBg:  [243, 244, 246],
        };

        // ── Filter info ────────────────────────────────────────────────
        const filterJenisEl  = document.getElementById('filter_jenis');
        const filterBulanEl  = document.getElementById('filter_bulan');
        const filterTahunEl  = document.getElementById('filter_tahun');
        const filterJenisVal = filterJenisEl.options[filterJenisEl.selectedIndex].text;
        const filterBulanVal = filterBulanEl.options[filterBulanEl.selectedIndex].text;
        const filterTahunVal = filterTahunEl.value || 'Semua Tahun';

        const now        = new Date();
        const tglCetak   = now.toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' });
        const jamCetak   = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) + ' WIB';
        const periode    = (filterBulanVal === 'Semua Bulan' ? '' : filterBulanVal + ' ') + filterTahunVal;

        // ══════════════════════════════════════════════════════════════
        //  KOP SURAT  (clean white + accent stripe)
        // ══════════════════════════════════════════════════════════════
        // Page background
        doc.setFillColor(...C.pageGray);
        doc.rect(0, 0, PW, PH, 'F');

        // White content card with rounded feel
        doc.setFillColor(...C.white);
        doc.roundedRect(10, 10, PW - 20, PH - 20, 3, 3, 'F');

        // Top accent bar (thin, clean)
        doc.setFillColor(...C.accentBlue);
        doc.rect(10, 10, PW - 20, 4, 'F');

        const logoY = 20;

        // ── Kop Text ─────────────────────────────────────────────────
        const textX = ML + 2;
        doc.setFont('helvetica', 'bold');
        doc.setFontSize(14);
        doc.setTextColor(...C.black);
        doc.text('LAPORAN KEUANGAN KAS KELAS', textX, logoY + 5);

        doc.setFont('helvetica', 'normal');
        doc.setFontSize(8.5);
        doc.setTextColor(...C.subText);
        doc.text('Sistem Informasi Keuangan Uangkas Kelas — Laporan Resmi Bendahara', textX, logoY + 10.5);

        // Right-side info block
        const rx = PW - MR - 3;
        doc.setFont('helvetica', 'bold');
        doc.setFontSize(8);
        doc.setTextColor(...C.subText);
        doc.text('Tanggal Cetak', rx, logoY + 3, { align: 'right' });
        doc.setFont('helvetica', 'normal');
        doc.setTextColor(...C.black);
        doc.text(`${tglCetak}  ${jamCetak}`, rx, logoY + 7.5, { align: 'right' });

        // Double underline Kop Surat
        // Thick line
        doc.setDrawColor(...C.black);
        doc.setLineWidth(0.8);
        doc.line(ML + 2, logoY + 16, PW - MR - 2, logoY + 16);
        // Thin line
        doc.setLineWidth(0.25);
        doc.line(ML + 2, logoY + 17, PW - MR - 2, logoY + 17);

        // ── Info Grid (Periode, Jenis, No. Laporan) ──────────────────
        const infoY = logoY + 24;
        const cols3 = CW / 3;

        const infoItems = [
            { label: 'Periode Laporan',     value: periode },
            { label: 'Jenis Transaksi',     value: filterJenisVal },
            { label: 'No. Laporan',         value: `KAS/${now.getFullYear()}/${String(now.getMonth()+1).padStart(2,'0')}` },
        ];

        infoItems.forEach((item, idx) => {
            const ix = ML + 2 + idx * cols3;
            // Light box
            doc.setFillColor(250, 251, 253);
            doc.setDrawColor(...C.borderGray);
            doc.setLineWidth(0.3);
            doc.roundedRect(ix, infoY - 4, cols3 - 4, 14, 1.5, 1.5, 'FD');

            doc.setFont('helvetica', 'bold');
            doc.setFontSize(6.5);
            doc.setTextColor(...C.subText);
            doc.text(item.label.toUpperCase(), ix + 4, infoY + 1.5);

            doc.setFont('helvetica', 'bold');
            doc.setFontSize(9);
            doc.setTextColor(...C.black);
            doc.text(item.value, ix + 4, infoY + 7.5);
        });

        // ══════════════════════════════════════════════════════════════
        //  DATA PROCESSING
        // ══════════════════════════════════════════════════════════════
        const tableEl   = document.getElementById('transTable');
        const tbodyRows = tableEl.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
        const rows      = [];
        let totalPemasukan  = 0;
        let totalPengeluaran = 0;

        for (let i = 0; i < tbodyRows.length; i++) {
            const cells = tbodyRows[i].getElementsByTagName('td');
            if (cells.length < 5) continue;

            const no         = (cells[0].innerText || cells[0].textContent).trim();
            const tanggal    = (cells[1].innerText || cells[1].textContent).trim();
            const jenis      = (cells[2].innerText || cells[2].textContent).trim().toLowerCase();
            const keterangan = (cells[3].innerText || cells[3].textContent).replace(/\s+/g, ' ').trim();
            const anggota    = (cells[4].innerText || cells[4].textContent).trim();
            const rawAmt     = (cells[5].innerText || cells[5].textContent).replace(/[^\d]/g, '');
            const jumlahNum  = parseFloat(rawAmt) || 0;
            const isPemasukan = jenis.includes('pemasukan');

            if (isPemasukan) totalPemasukan  += jumlahNum;
            else             totalPengeluaran += jumlahNum;

            const sign = isPemasukan ? '+' : '-';
            rows.push({
                no, tanggal, jenis, keterangan, anggota, jumlahNum,
                isPemasukan,
                jumlahStr: `${sign} Rp ${jumlahNum.toLocaleString('id-ID')}`,
            });
        }
        const saldoAkhir = totalPemasukan - totalPengeluaran;
        const isSurplus  = saldoAkhir >= 0;

        // ══════════════════════════════════════════════════════════════
        //  MAIN TABLE via autoTable
        // ══════════════════════════════════════════════════════════════
        const startY = infoY + 16;

        doc.autoTable({
            startY,
            margin: { left: ML + 2, right: MR + 2 },
            head: [[
                { content: 'No.',     styles: { halign: 'center' } },
                { content: 'Tanggal', styles: { halign: 'center' } },
                { content: 'Jenis',   styles: { halign: 'center' } },
                { content: 'Keterangan / Deskripsi Transaksi' },
                { content: 'Nama Siswa / Pembayar' },
                { content: 'Jumlah (Rp)', styles: { halign: 'right' } },
            ]],
            body: rows.map(r => [
                r.no,
                r.tanggal,
                r.jenis.toUpperCase(),
                r.keterangan,
                r.anggota || '-',
                r.jumlahStr,
            ]),
            theme: 'grid',
            headStyles: {
                fillColor: C.headerBg,
                textColor: C.headerText,
                fontStyle: 'bold',
                fontSize: 8.5,
                lineColor: C.borderGray,
                lineWidth: 0.25,
                cellPadding: { top: 5, bottom: 5, left: 4, right: 4 },
                valign: 'middle',
            },
            columnStyles: {
                0: { halign: 'center', cellWidth: 10,  fontStyle: 'bold', textColor: C.subText },
                1: { halign: 'center', cellWidth: 24,  font: 'helvetica' },
                2: { halign: 'center', cellWidth: 26,  fontStyle: 'bold' },
                3: { halign: 'left',   overflow: 'linebreak' },
                4: { halign: 'left',   cellWidth: 38  },
                5: { halign: 'right',  cellWidth: 34,  fontStyle: 'bold' },
            },
            styles: {
                font: 'helvetica',
                fontSize: 8,
                cellPadding: { top: 4, bottom: 4, left: 4, right: 4 },
                lineColor: C.borderGray,
                lineWidth: 0.2,
                valign: 'middle',
                textColor: C.black,
                overflow: 'linebreak',
            },
            // Per-row colour coding (income = green text, expense = red text)
            didParseCell: function(data) {
                if (data.section === 'body') {
                    const rowData = rows[data.row.index];
                    if (!rowData) return;
                    if (rowData.isPemasukan) {
                        if (data.column.index === 2) {
                            data.cell.styles.textColor = C.incomeText;
                            data.cell.styles.fillColor = C.incomeBg;
                        }
                        if (data.column.index === 5) {
                            data.cell.styles.textColor = C.incomeText;
                        }
                    } else {
                        if (data.column.index === 2) {
                            data.cell.styles.textColor = C.expText;
                            data.cell.styles.fillColor = C.expBg;
                        }
                        if (data.column.index === 5) {
                            data.cell.styles.textColor = C.expText;
                        }
                    }
                    // Zebra stripe on even rows
                    if (data.row.index % 2 === 1 && data.column.index !== 2) {
                        data.cell.styles.fillColor = C.rowAlt;
                    }
                }
            }
        });

        // ══════════════════════════════════════════════════════════════
        //  RINGKASAN / SUMMARY BOX
        // ══════════════════════════════════════════════════════════════
        const afterTableY = doc.lastAutoTable.finalY + 8;
        const sumW = CW;
        const sumX = ML + 2;
        const rowH = 7;
        const summaryHeight = 10 + (3 * rowH);

        let sy = afterTableY;

        if (sy + summaryHeight > PH - 20) {
            doc.addPage();
            doc.setFillColor(...C.pageGray);
            doc.rect(0, 0, PW, PH, 'F');
            doc.setFillColor(...C.white);
            doc.roundedRect(10, 10, PW - 20, PH - 20, 3, 3, 'F');
            doc.setFillColor(...C.accentBlue);
            doc.rect(10, 10, PW - 20, 3.5, 'F');
            sy = 20;
        }

        doc.setFont('helvetica', 'bold');
        doc.setFontSize(7.5);
        doc.setTextColor(...C.subText);
        doc.text('RINGKASAN KEUANGAN', sumX, sy + 4);
        sy += 7;

        const sumItems = [
            { label: 'Total Pemasukan',     value: `+Rp ${totalPemasukan.toLocaleString('id-ID')}`,  color: C.incomeText, bg: C.incomeBg },
            { label: 'Total Pengeluaran',   value: `-Rp ${totalPengeluaran.toLocaleString('id-ID')}`, color: C.expText,    bg: C.expBg },
            { label: 'Saldo Akhir (Bersih)', value: `Rp ${Math.abs(saldoAkhir).toLocaleString('id-ID')}${isSurplus ? '' : ' (Defisit)'}`, color: isSurplus ? C.accentBlue : C.expText, bg: [238,242,255] },
        ];

        sumItems.forEach((item, idx) => {
            doc.setFillColor(...item.bg);
            doc.setDrawColor(...C.borderGray);
            doc.setLineWidth(0.3);
            doc.rect(sumX, sy, sumW, rowH, 'FD');
            doc.setFont('helvetica', idx === 2 ? 'bold' : 'normal');
            doc.setFontSize(7);
            doc.setTextColor(...C.subText);
            doc.text(item.label, sumX + 3, sy + 5);
            doc.setFont('helvetica', 'bold');
            doc.setFontSize(idx === 2 ? 8 : 7.5);
            doc.setTextColor(...item.color);
            doc.text(item.value, sumX + sumW - 3, sy + 5, { align: 'right' });
            sy += rowH;
        });

        // ── Total rows count ──────────────────────────────────────────
        doc.setFont('helvetica', 'normal');
        doc.setFontSize(7);
        doc.setTextColor(...C.dimText);
        doc.text(`Jumlah baris data: ${rows.length} transaksi`, ML + 2, afterTableY - 2);

        // ══════════════════════════════════════════════════════════════
// ==================================================================
        // TTD / TANDA TANGAN AREA
        // ==================================================================
        let ttdY = sy + 12;
        const ttdHeight = 42;
        if (ttdY + ttdHeight > PH - 18) {
            doc.addPage();
            doc.setFillColor(...C.pageGray);
            doc.rect(0, 0, PW, PH, 'F');
            doc.setFillColor(...C.white);
            doc.roundedRect(10, 10, PW - 20, PH - 20, 3, 3, 'F');
            doc.setFillColor(...C.accentBlue);
            doc.rect(10, 10, PW - 20, 3.5, 'F');
            ttdY = 22;
        }

        const centerX = PW - MR - 28;

        doc.setFont('helvetica', 'normal');
        doc.setFontSize(8);
        doc.setTextColor(...C.subText);
        doc.text('Mengetahui,', centerX, ttdY, { align: 'center' });
        doc.text('Bendahara Kelas', centerX, ttdY + 4, { align: 'center' });

        doc.setFont('helvetica', 'bold');
        doc.setFontSize(9);
        doc.setTextColor(...C.black);
        doc.text('Rizky perdana putra sam', centerX, ttdY + 9, { align: 'center' });

        if (sigImgData) {
            try { doc.addImage(sigImgData, 'PNG', centerX - 22, ttdY + 11, 44, 20); } catch(e) {}
        }

        doc.setDrawColor(...C.borderGray);
        doc.setLineWidth(0.4);
        doc.line(centerX - 24, ttdY + 34, centerX + 24, ttdY + 34);
    
        // ══════════════════════════════════════════════════════════════
        //  PAGE FOOTER — all pages
        // ══════════════════════════════════════════════════════════════
        const totalPages = doc.internal.getNumberOfPages();
        for (let pg = 1; pg <= totalPages; pg++) {
            doc.setPage(pg);

            // Footer separator
            doc.setDrawColor(...C.borderGray);
            doc.setLineWidth(0.3);
            doc.line(ML + 2, PH - 16, PW - MR - 2, PH - 16);

            // Left footer text
            doc.setFont('helvetica', 'normal');
            doc.setFontSize(7);
            doc.setTextColor(...C.dimText);
            doc.text(`Uangkas Kelas  •  Laporan Resmi Keuangan Kelas  •  Dicetak ${tglCetak}`, ML + 2, PH - 11.5);

            // Right footer text
            doc.text(`Hal. ${pg} / ${totalPages}`, PW - MR - 2, PH - 11.5, { align: 'right' });

            // Bottom accent bar
            doc.setFillColor(...C.accentBlue);
            doc.rect(10, PH - 10, PW - 20, 2, 'F');
        }

        // ── Save / Download ───────────────────────────────────────────
        const filename = `Laporan_Kas_Kelas_${periode.replace(/\s+/g,'_')}.pdf`;
        doc.save(filename);
    }
</script>

<?php require_once 'includes/footer.php'; ?>
