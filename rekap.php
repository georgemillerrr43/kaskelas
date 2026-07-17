<?php
// rekap.php
require_once 'config/database.php';
require_once 'includes/header.php';

$error = '';

// Array nama bulan bahasa Indonesia
$nama_bulan = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
];

// Ambil bulan dan tahun dari parameter filter, default bulan berjalan dan tahun berjalan
$bulan_aktif = isset($_GET['bulan']) && $_GET['bulan'] !== '' ? (int)$_GET['bulan'] : (int)date('n');
$tahun_aktif = isset($_GET['tahun']) ? (int)$_GET['tahun'] : (int)date('Y');

try {
    // 1. Ambil daftar semua anggota/siswa kelas
    $stmt_m = $pdo->query("SELECT id, nis, nama FROM anggota ORDER BY nama ASC");
    $anggota_list = $stmt_m->fetchAll();

    // 2. Ambil data pembayaran kas mingguan untuk bulan dan tahun terpilih
    $stmt_p = $pdo->prepare("
        SELECT anggota_id, minggu, SUM(jumlah) AS total_bayar
        FROM transaksi
        WHERE jenis = 'pemasukan'
          AND anggota_id IS NOT NULL
          AND bulan = ?
          AND tahun = ?
        GROUP BY anggota_id, minggu
    ");
    $stmt_p->execute([$bulan_aktif, $tahun_aktif]);
    $payment_data = $stmt_p->fetchAll();

    // Petakan ke array 2 dimensi: $payments[anggota_id][minggu] = total_bayar
    $payments = [];
    foreach ($payment_data as $pay) {
        $payments[(int)$pay['anggota_id']][(int)$pay['minggu']] = (float)$pay['total_bayar'];
    }
} catch (PDOException $e) {
    $error = 'Gagal memuat rekapitulasi kas mingguan: ' . $e->getMessage();
    $anggota_list = [];
    $payments = [];
}
?>

<!-- Alert Box -->
<?php if ($error !== ''): ?>
    <div class="alert alert-error mb-6"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<!-- Header Filter -->
<div class="card p-4 md:p-6 flex flex-col lg:flex-row lg:items-center justify-between gap-4 mb-5 md:mb-6">
    <div class="min-w-0">
        <h4 style="font-size:14px;font-weight:800;color:var(--text);margin:0">Rekap Kas Mingguan</h4>
        <p style="font-size:12px;color:var(--text-muted);margin:4px 0 0">Status iuran mingguan per siswa (Mg 1 - Mg 5)</p>
    </div>

    <form method="GET" action="rekap.php" style="display:flex;flex-wrap:wrap;align-items:center;gap:8px">
        <select name="bulan" id="bulan" onchange="this.form.submit()"
                style="padding:8px 12px;border:1px solid var(--input-border);border-radius:8px;font-size:12px;font-weight:600;font-family:inherit;background:var(--input-bg);color:var(--text);outline:none;cursor:pointer;min-width:120px;width:auto;appearance:none">
            <?php foreach ($nama_bulan as $num => $name): ?>
                <option value="<?= $num ?>" <?= $num === $bulan_aktif ? 'selected' : '' ?>><?= $name ?></option>
            <?php endforeach; ?>
        </select>
        <select name="tahun" id="tahun" onchange="this.form.submit()"
                style="padding:8px 12px;border:1px solid var(--input-border);border-radius:8px;font-size:12px;font-weight:600;font-family:inherit;background:var(--input-bg);color:var(--text);outline:none;cursor:pointer;min-width:90px;width:auto;appearance:none">
            <?php
            $current_y = (int)date('Y');
            for ($y = $current_y - 3; $y <= $current_y + 2; $y++):
            ?>
                <option value="<?= $y ?>" <?= $y === $tahun_aktif ? 'selected' : '' ?>><?= $y ?></option>
            <?php endfor; ?>
        </select>
    </form>
</div>

<!-- Legend -->
<div style="display:flex;flex-wrap:wrap;gap:10px;align-items:center;font-size:12px;color:var(--text-muted);font-weight:500;background:var(--surface-bg);padding:10px 14px;border-radius:10px;border:1px solid var(--border);margin-bottom:20px">
    <span style="font-weight:700;color:var(--text)"><i class="fa-solid fa-circle-info mr-1" style="color:var(--primary-400)"></i> Keterangan:</span>
    <span style="display:inline-flex;align-items:center;gap:5px">
        <span style="width:16px;height:16px;border-radius:50%;background:var(--income);display:flex;align-items:center;justify-content:center;"><i class="fa-solid fa-check" style="color:#fff;font-size:8px"></i></span>
        Lunas
    </span>
    <span style="display:inline-flex;align-items:center;gap:5px">
        <span style="width:16px;height:16px;border-radius:50%;background:var(--border-table);display:flex;align-items:center;justify-content:center;"><i class="fa-solid fa-minus" style="color:var(--text-dim);font-size:8px"></i></span>
        Belum Bayar
    </span>
</div>

<!-- Matriks Table -->
<div class="card overflow-hidden">
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th class="text-center" style="width:40px">No</th>
                    <th style="min-width:150px;border-right:1px solid var(--border-table)">Nama Siswa</th>
                    <th class="text-center" style="width:85px;background:var(--surface-bg)">Mgg 1</th>
                    <th class="text-center" style="width:85px;background:var(--surface-bg)">Mgg 2</th>
                    <th class="text-center" style="width:85px;background:var(--surface-bg)">Mgg 3</th>
                    <th class="text-center" style="width:85px;background:var(--surface-bg)">Mgg 4</th>
                    <th class="text-center" style="width:85px;background:var(--surface-bg)">Mgg 5</th>
                    <th class="text-center" style="width:100px;border-left:1px solid var(--border-table);background:var(--surface-bg)">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($anggota_list)): ?>
                    <tr>
                        <td colspan="8" style="text-align:center;padding:60px 16px;color:var(--text-dim)">
                            <i class="fa-solid fa-users-slash" style="font-size:32px;display:block;margin-bottom:10px;color:var(--text-dim)"></i>
                            Tidak ada data siswa terdaftar. Tambahkan anggota terlebih dahulu.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php $no = 1; $i = 0; foreach ($anggota_list as $member): ?>
                        <?php $i++; ?>
                        <tr style="<?= $i % 2 === 0 ? 'background:var(--surface-bg)' : '' ?>">
                            <td style="text-align:center;font-weight:600;color:var(--text-dim);font-size:12px"><?= $no++ ?></td>
                            <td style="border-right:1px solid var(--border-table)">
                                <span style="font-weight:600;color:var(--text)"><?= htmlspecialchars($member['nama']) ?></span>
                                <?php if ($member['nis']): ?>
                                    <span style="display:block;font-size:9px;color:var(--text-dim);font-family:monospace;font-weight:500">NIS: <?= htmlspecialchars($member['nis']) ?></span>
                                <?php endif; ?>
                            </td>

                            <?php
                            $total_paid_this_month = 0;
                            for ($w = 1; $w <= 5; $w++):
                                $paid = isset($payments[$member['id']][$w]);
                                $amount = $paid ? $payments[$member['id']][$w] : 0;
                                if ($paid) $total_paid_this_month += $amount;
                            ?>
                                <td style="text-align:center;padding:8px 6px;white-space:nowrap">
                                    <?php if ($paid): ?>
                                        <span style="display:inline-flex;align-items:center;gap:5px;white-space:nowrap">
                                            <span style="width:22px;height:22px;border-radius:50%;background:var(--income-bg);color:var(--income);display:inline-flex;align-items:center;justify-content:center;font-size:9px;font-weight:bold;flex-shrink:0">
                                                <i class="fa-solid fa-check"></i>
                                            </span>
                                            <span style="font-size:10px;font-weight:700;color:var(--income);white-space:nowrap"><?= number_format($amount, 0, ',', '.') ?></span>
                                        </span>
                                    <?php else: ?>
                                        <span style="display:inline-flex;align-items:center;gap:5px;white-space:nowrap">
                                            <span style="width:22px;height:22px;border-radius:50%;background:var(--surface-bg);color:var(--text-dim);display:inline-flex;align-items:center;justify-content:center;font-size:9px;flex-shrink:0">
                                                <i class="fa-solid fa-minus"></i>
                                            </span>
                                            <span style="font-size:10px;color:var(--text-dim);font-weight:500;white-space:nowrap">-</span>
                                        </span>
                                    <?php endif; ?>
                                </td>
                            <?php endfor; ?>

                            <!-- Total Bulan Ini -->
                            <td style="text-align:center;font-weight:bold;white-space:nowrap;border-left:1px solid var(--border-table);background:var(--surface-bg)">
                                <span style="display:inline-block;padding:6px 12px;background:var(--tab-active-bg);color:var(--tab-active-text);border-radius:8px;font-size:11px;font-weight:bold;border:1px solid rgba(99,102,241,0.15);white-space:nowrap">
                                    Rp <?= number_format($total_paid_this_month, 0, ',', '.') ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>