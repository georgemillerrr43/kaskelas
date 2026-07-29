<?php
// transaksi.php
require_once 'config/database.php';

// Handle hapus transaksi SEBELUM header.php dipanggil (biar header redirect gak error)
if (session_status() === PHP_SESSION_NONE) session_start();
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_SESSION['user_id'])) {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($id > 0) {
        try {
            // Hapus file bukti jika ada
            $stmt = $pdo->prepare("SELECT bukti FROM transaksi WHERE id = ?");
            $stmt->execute([$id]);
            $trans_data = $stmt->fetch();
            if ($trans_data && !empty($trans_data['bukti'])) {
                $file_path = __DIR__ . '/' . $trans_data['bukti'];
                if (file_exists($file_path)) @unlink($file_path);
            }

            $stmt = $pdo->prepare("DELETE FROM transaksi WHERE id = ?");
            $stmt->execute([$id]);

            header('Location: transaksi.php');
            exit;
        } catch (PDOException $e) {
            // fallback: biar error muncul di halaman
            $_SESSION['delete_error'] = $e->getMessage();
            header('Location: transaksi.php');
            exit;
        }
    }
}

require_once 'includes/header.php';

$error = isset($_SESSION['delete_error']) ? $_SESSION['delete_error'] : '';
unset($_SESSION['delete_error']);
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

    // Handle file upload untuk pengeluaran (OPSIONAL)
    $bukti_path = null;
    $has_upload_attempt = isset($_FILES['bukti']) && $_FILES['bukti']['error'] !== UPLOAD_ERR_NO_FILE;
    if ($jenis === 'pengeluaran' && $has_upload_attempt) {
        $upload_err = $_FILES['bukti']['error'];
        if ($upload_err !== UPLOAD_ERR_OK) {
            $err_msgs = [
                UPLOAD_ERR_INI_SIZE   => 'Ukuran file melebihi batas maksimum (upload_max_filesize)',
                UPLOAD_ERR_FORM_SIZE  => 'Ukuran file melebihi batas maksimum form',
                UPLOAD_ERR_PARTIAL    => 'File hanya terupload sebagian, coba lagi',
                UPLOAD_ERR_NO_TMP_DIR => 'Folder temporary server tidak ditemukan',
                UPLOAD_ERR_CANT_WRITE => 'Gagal menulis file ke disk server',
            ];
            $error = 'Gagal upload bukti: ' . ($err_msgs[$upload_err] ?? 'Error tidak dikenal (kode ' . $upload_err . ')');
        } else {
            $allowed_ext  = ['jpg', 'jpeg', 'png', 'webp'];
            $max_size = 2 * 1024 * 1024;

            if ($_FILES['bukti']['size'] > $max_size) {
                $error = 'Ukuran file bukti maksimal 2MB!';
            } else {
                $ext = strtolower(pathinfo($_FILES['bukti']['name'], PATHINFO_EXTENSION));
                if (!in_array($ext, $allowed_ext)) {
                    $error = 'File bukti harus berupa gambar (JPEG/PNG/WebP)!';
                } else {
                    // Validasi isi file via server
                    $detected_mime = @mime_content_type($_FILES['bukti']['tmp_name']);
                    $finfo_ok = $detected_mime && strpos($detected_mime, 'image/') === 0;
                    if (!$finfo_ok) {
                        $error = 'File bukti tidak dikenali sebagai gambar.';
                    } else {
                        $filename = 'bukti_' . time() . '_' . uniqid() . '.' . $ext;
                        $dest = __DIR__ . '/assets/uploads/' . $filename;
                        if (move_uploaded_file($_FILES['bukti']['tmp_name'], $dest)) {
                            $bukti_path = 'assets/uploads/' . $filename;
                        } else {
                            $error = 'Gagal menyimpan file bukti. Periksa izin folder uploads.';
                        }
                    }
                }
            }
        }
    }

    // if upload error, jangan lanjut
    if ($error === '') {
    // Uang Kas Mingguan Anggota
    $is_kas = isset($_POST['is_kas']) ? (int)$_POST['is_kas'] : 0;
    $anggota_id = null;
    if ($jenis === 'pemasukan' && $is_kas === 1) {
        $anggota_id = (int)$_POST['anggota_id'];
    } elseif ($jenis === 'pengeluaran' && !empty($_POST['pengguna_id'])) {
        $anggota_id = (int)$_POST['pengguna_id'];
    }
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
	} // <-- nutup if ($error === '')
} // <-- nutup POST handler (line 23)

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
                                </td>
                                <td class="font-medium" style="color:var(--text-muted)">
                                    <?= htmlspecialchars($trans['nama_anggota'] ?? '-') ?>
                                </td>
                                <td class="text-right font-mono font-bold" style="color:<?= $trans['jenis'] === 'pemasukan' ? 'var(--income)' : 'var(--expense)' ?>">
                                    <?= $trans['jenis'] === 'pemasukan' ? '+' : '-' ?>Rp <?= number_format($trans['jumlah'], 0, ',', '.') ?>
                                </td>
                                <td class="text-center">
                                    <?php if (!empty($trans['bukti'])): ?>
                                        <a href="javascript:void(0)" onclick="previewBukti('<?= htmlspecialchars($trans['bukti']) ?>')"
                                           style="display:inline-flex;width:32px;height:32px;border-radius:8px;align-items:center;justify-content:center;font-size:12px;text-decoration:none;transition:0.15s;color:var(--primary-600);background:var(--tab-active-bg)"
                                           title="Lihat Bukti">
                                            <i class="fa-solid fa-image"></i>
                                        </a>
                                    <?php else: ?>
                                        <span style="color:var(--text-dim);font-size:10px">—</span>
                                    <?php endif; ?>
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
<div id="transactionModal" class="modal-overlay" onclick="if(event.target===this)toggleTransactionModal(false)">
    <div class="modal-card" id="modalCard">
        <!-- Header -->
        <div class="modal-header" style="border-bottom:1px solid var(--border);border-radius:16px 16px 0 0;background:var(--surface-card)">
            <div>
                <h3 style="font-size:15px;font-weight:700;margin:0;color:var(--text)"><i class="fa-solid fa-wallet mr-2" style="color:var(--primary-500)"></i> Catat Transaksi Baru</h3>
                <p style="color:var(--text-muted);font-size:11px;margin:2px 0 0">Pemasukan / Pengeluaran kas kelas</p>
            </div>
            <button onclick="toggleTransactionModal(false)" class="modal-close" style="color:var(--text-muted);background:var(--tab-hover);border-radius:10px;width:32px;height:32px;border:none;cursor:pointer;font-size:16px;display:flex;align-items:center;justify-content:center">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form action="transaksi.php" method="POST" id="transactionForm" class="p-5 md:p-6" enctype="multipart/form-data" style="display:flex;flex-direction:column;gap:18px">
            <input type="hidden" name="action_type" value="add">

            <!-- Jenis + Tanggal -->
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="input-label"><i class="fa-solid fa-arrow-right-arrow-left mr-1"></i> Jenis</label>
                    <div style="display:flex;gap:6px;background:var(--surface-bg);padding:4px;border-radius:10px;border:1px solid var(--input-border)">
                        <button type="button" id="jenisPemasukan" class="jenis-tab"
                            onclick="setJenis('pemasukan')"
                            style="flex:1;padding:8px 12px;border-radius:7px;border:none;font-weight:600;font-size:12px;cursor:pointer;transition:0.15s;color:var(--primary-600);background:var(--input-bg);box-shadow:0 1px 3px rgba(0,0,0,0.08)">
                            <i class="fa-solid fa-arrow-down-long mr-1"></i> Masuk
                        </button>
                        <button type="button" id="jenisPengeluaran" class="jenis-tab"
                            onclick="setJenis('pengeluaran')"
                            style="flex:1;padding:8px 12px;border-radius:7px;border:none;font-weight:600;font-size:12px;cursor:pointer;transition:0.15s;color:var(--text-muted);background:transparent">
                            <i class="fa-solid fa-arrow-up-long mr-1"></i> Keluar
                        </button>
                    </div>
                    <input type="hidden" name="jenis" id="jenis" value="pemasukan">
                </div>
                <div>
                    <label for="tanggal" class="input-label"><i class="fa-regular fa-calendar mr-1"></i> Tanggal</label>
                    <input type="date" name="tanggal" id="tanggal" required value="<?= date('Y-m-d') ?>" class="input text-sm font-medium">
                </div>
            </div>

            <!-- Dues Section (hanya pemasukan) -->
            <div id="duesSection" style="padding:14px;border-radius:10px;border:1px solid var(--border);background:var(--surface-bg)">
                <label style="display:flex;align-items:center;gap:8px;font-size:12px;font-weight:600;color:var(--text);cursor:pointer;user-select:none">
                    <input type="checkbox" name="is_kas" id="is_kas" value="1" onchange="toggleDuesForm()"
                           style="width:15px;height:15px;accent-color:var(--primary-600);flex-shrink:0">
                    <span><i class="fa-regular fa-clock mr-1"></i> Kas mingguan siswa</span>
                </label>
                <div id="duesFields" class="hidden" style="margin-top:12px;padding-top:12px;border-top:1px solid var(--border);display:grid;gap:10px">
                    <div>
                        <label for="anggota_id" class="input-label" style="color:var(--text-muted)">Siswa</label>
                        <select name="anggota_id" id="anggota_id" class="input text-xs font-semibold select">
                            <option value="">-- Pilih --</option>
                            <?php foreach ($anggota_list as $member): ?>
                                <option value="<?= $member['id'] ?>"><?= htmlspecialchars($member['nama']) ?> (<?= htmlspecialchars($member['nis'] ?? '-') ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="grid grid-cols-3 gap-2">
                        <div><label for="minggu" class="input-label">Mgg</label><select name="minggu" id="minggu" class="input text-xs select"><?php $c_w=(int)ceil(date('j')/7);for($w=1;$w<=5;$w++):?><option value="<?=$w?>"<?=$c_w===$w?' selected':''?>>Mg <?=$w?></option><?php endfor;?></select></div>
                        <div><label for="bulan" class="input-label">Bln</label><select name="bulan" id="bulan" class="input text-xs select"><?php foreach($nama_bulan as $num=>$name):?><option value="<?=$num?>"<?=(int)date('n')===$num?' selected':''?>><?=$name?></option><?php endforeach;?></select></div>
                        <div><label for="tahun" class="input-label">Thn</label><select name="tahun" id="tahun" class="input text-xs select"><?php $c_y=(int)date('Y');for($y=$c_y-2;$y<=$c_y+2;$y++):?><option value="<?=$y?>"<?=$c_y===$y?' selected':''?>><?=$y?></option><?php endfor;?></select></div>
                    </div>
                </div>
            </div>

            <!-- Jumlah -->
            <div>
                <label for="jumlah" class="input-label"><i class="fa-solid fa-money-bill mr-1"></i> Jumlah</label>
                <div class="relative">
                    <span style="position:absolute;inset:0;display:flex;align-items:center;padding-left:14px;color:var(--text-muted);font-weight:700;font-size:15px;pointer-events:none">Rp</span>
                    <input type="number" name="jumlah" id="jumlah" required min="100" step="100"
                           class="input pl-12 text-sm font-semibold" style="font-size:16px;padding-top:12px;padding-bottom:12px"
                           placeholder="0">
                </div>
            </div>

            <!-- Keterangan -->
            <div>
                <label for="keterangan" class="input-label"><i class="fa-solid fa-pen mr-1"></i> Keterangan</label>
                <textarea name="keterangan" id="keterangan" required rows="2"
                          class="input text-sm" style="resize:none"
                          placeholder="Misal: Uang kas mingguan, beli alat kelas, isi ulang air galon, dll"></textarea>
            </div>

            <!-- Fields khusus pengeluaran -->
            <div id="pengeluaranFields" style="display:none">
                <div style="padding:14px;border-radius:10px;border:1px solid var(--border);background:var(--surface-bg);display:grid;gap:12px">
                    <div>
                        <label for="pengguna_id" class="input-label"><i class="fa-solid fa-user mr-1"></i> Yang Mengeluarkan</label>
                        <select name="pengguna_id" id="pengguna_id" class="input select text-xs font-semibold">
                            <option value="">-- Pilih Siswa --</option>
                            <?php foreach ($anggota_list as $member): ?>
                                <option value="<?= $member['id'] ?>"><?= htmlspecialchars($member['nama']) ?> (<?= htmlspecialchars($member['nis'] ?? '-') ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="bukti" class="input-label"><i class="fa-solid fa-camera mr-1"></i> Bukti Nota <span style="color:var(--text-dim);font-weight:400;text-transform:none;letter-spacing:0;font-size:9px">(opsional)</span></label>
                        <div style="border:1px dashed var(--input-border);border-radius:10px;padding:3px;transition:0.15s">
                            <input type="file" name="bukti" id="bukti" accept="image/jpeg,image/png,image/webp"
                                   class="input text-sm" style="padding:10px;border:none;background:transparent;font-size:12px">
                        </div>
                        <p style="font-size:10px;color:var(--text-dim);margin-top:4px;display:flex;align-items:center;gap:4px"><i class="fa-solid fa-circle-info"></i> JPEG/PNG/WebP, maks 2MB</p>
                    </div>
                </div>
            </div>

            <script>
            function setJenis(val) {
                document.getElementById('jenis').value = val;
                var pi = document.getElementById('jenisPemasukan');
                var po = document.getElementById('jenisPengeluaran');
                var ds = document.getElementById('duesSection');
                var pf = document.getElementById('pengeluaranFields');
                var ic = document.getElementById('is_kas');
                if (val === 'pemasukan') {
                    pi.style.background='var(--input-bg)'; pi.style.color='var(--primary-600)'; pi.style.boxShadow='0 1px 3px rgba(0,0,0,0.08)';
                    po.style.background='transparent'; po.style.color='var(--text-muted)'; po.style.boxShadow='none';
                    ds.style.display='block'; pf.style.display='none';
                } else {
                    po.style.background='var(--input-bg)'; po.style.color='var(--expense)'; po.style.boxShadow='0 1px 3px rgba(0,0,0,0.08)';
                    pi.style.background='transparent'; pi.style.color='var(--text-muted)'; pi.style.boxShadow='none';
                    ds.style.display='none'; if(ic)ic.checked=false; toggleDuesForm(); pf.style.display='block';
                }
            }
            </script>

            <button type="submit" class="btn btn-primary w-full" style="padding:12px;border-radius:10px;font-size:14px">
                <i class="fa-solid fa-floppy-disk"></i> Simpan Transaksi
            </button>
        </form>
    </div>
</div>

<!-- Libraries for PDF Generation -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.29/jspdf.plugin.autotable.min.js"></script>

<!-- ══ MODAL BUKTI ══ -->
<div id="buktiModal" class="modal-overlay" onclick="closeBuktiModal(event)">
    <div class="modal-card modal-bukti" onclick="event.stopPropagation()">
        <div class="modal-header">
            <div style="display:flex;align-items:center;gap:10px">
                <div style="width:32px;height:32px;border-radius:8px;background:var(--tab-active-bg);color:var(--tab-active-text);display:flex;align-items:center;justify-content:center;font-size:13px">
                    <i class="fa-solid fa-image"></i>
                </div>
                <div>
                    <h3 style="margin:0;font-size:14px;font-weight:700;color:var(--text)">Bukti Transaksi</h3>
                    <p style="margin:1px 0 0;font-size:10px;color:var(--text-muted)">Dokumen pendukung transaksi kas kelas</p>
                </div>
            </div>
            <button onclick="closeBuktiModal()" class="modal-close" aria-label="Tutup">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div style="padding:16px">
            <div style="background:var(--surface-bg);border-radius:12px;padding:12px;display:flex;align-items:center;justify-content:center;min-height:160px;max-height:60vh;overflow:hidden;border:1px solid var(--border)">
                <img id="buktiImg" src="" alt="Bukti Transaksi" style="max-width:100%;max-height:55vh;object-fit:contain;border-radius:8px;display:none">
                <div id="buktiLoading" style="color:var(--text-dim);font-size:13px;display:flex;flex-direction:column;align-items:center;gap:8px">
                    <div style="width:28px;height:28px;border:3px solid var(--border-table);border-top-color:var(--primary-500);border-radius:50%;animation:spin 0.7s linear infinite"></div>
                    <span>Memuat gambar...</span>
                </div>
            </div>
            <div style="margin-top:12px;display:flex;gap:8px;justify-content:flex-end">
                <button onclick="closeBuktiModal()" class="btn btn-outline btn-sm">
                    <i class="fa-solid fa-xmark"></i> Tutup
                </button>
                <a id="buktiDownload" href="#" target="_blank" class="btn btn-primary btn-sm">
                    <i class="fa-solid fa-arrow-up-right-from-square"></i> Buka di Tab Baru
                </a>
            </div>
        </div>
    </div>
</div>

<style>
@keyframes spin { to { transform: rotate(360deg); } }
.modal-bukti { max-width: 600px !important; border-radius: 16px !important; overflow: hidden; animation: modalIn 0.2s ease; }
@keyframes modalIn { from { opacity: 0; transform: scale(0.96) translateY(8px); } to { opacity: 1; transform: scale(1) translateY(0); } }
@media (max-width: 768px) { .modal-bukti { max-width: calc(100% - 16px) !important; margin: 0; } }
</style>

<script>
function previewBukti(url) {
    var img = document.getElementById('buktiImg');
    var ld = document.getElementById('buktiLoading');
    var dl = document.getElementById('buktiDownload');
    var mo = document.getElementById('buktiModal');
    img.style.display = 'none'; ld.style.display = 'flex';
    dl.href = url; mo.classList.add('open');
    img.onload = function() { ld.style.display = 'none'; img.style.display = 'block'; };
    img.onerror = function() { ld.innerHTML = '<i class="fa-solid fa-image-slash" style="font-size:28px;color:var(--expense)"></i><span>Gagal memuat gambar</span>'; };
    img.src = url;
}
function closeBuktiModal(e) {
    if (e && e.target !== e.currentTarget) return;
    document.getElementById('buktiModal').classList.remove('open');
}
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeBuktiModal();
});

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
