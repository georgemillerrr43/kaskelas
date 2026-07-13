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
<div class="card p-6 flex flex-col lg:flex-row lg:items-center justify-between gap-6 mb-6">
    <div>
        <h4 class="font-bold text-slate-800 text-base">Matriks Kas Mingguan Siswa</h4>
        <p class="text-xs text-slate-400">Pilih bulan dan tahun untuk melihat rincian iuran mingguan (Minggu 1 - Minggu 5)</p>
    </div>

    <form method="GET" action="rekap.php" class="flex flex-wrap items-center gap-3">
        <select name="bulan" id="bulan" onchange="this.form.submit()"
                class="input select text-xs" style="width:auto;min-width:140px">
            <?php foreach ($nama_bulan as $num => $name): ?>
                <option value="<?= $num ?>" <?= $num === $bulan_aktif ? 'selected' : '' ?>><?= $name ?></option>
            <?php endforeach; ?>
        </select>
        <select name="tahun" id="tahun" onchange="this.form.submit()"
                class="input select text-xs" style="width:auto;min-width:100px">
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
<div class="flex flex-wrap gap-4 items-center mb-6 text-xs text-slate-500 font-medium bg-indigo-50/60 p-4 border border-indigo-100/50 rounded-xl">
    <span class="font-semibold text-indigo-900"><i class="fa-solid fa-circle-info mr-1"></i> Keterangan:</span>
    <span class="inline-flex items-center gap-1.5">
        <span class="w-5 h-5 rounded-full bg-emerald-500 flex items-center justify-center"><i class="fa-solid fa-check text-white text-[9px]"></i></span>
        Lunas / Sudah Bayar
    </span>
    <span class="inline-flex items-center gap-1.5">
        <span class="w-5 h-5 rounded-full bg-slate-200 flex items-center justify-center"><i class="fa-solid fa-minus text-slate-400 text-[9px]"></i></span>
        Belum Bayar
    </span>
</div>

<!-- Matriks Table -->
<div class="card overflow-hidden">
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th class="text-center" style="width:48px">No</th>
                    <th style="min-width:180px;border-right:1px solid #e2e8f0">Nama Siswa</th>
                    <th class="text-center" style="width:110px;background:#fafaff">Minggu 1</th>
                    <th class="text-center" style="width:110px;background:#fafaff">Minggu 2</th>
                    <th class="text-center" style="width:110px;background:#fafaff">Minggu 3</th>
                    <th class="text-center" style="width:110px;background:#fafaff">Minggu 4</th>
                    <th class="text-center" style="width:110px;background:#fafaff">Minggu 5</th>
                    <th class="text-center" style="width:140px;border-left:1px solid #e2e8f0;background:#f8faff">Total Bulan Ini</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($anggota_list)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-16 text-slate-400">
                            <i class="fa-solid fa-users-slash text-4xl mb-3 text-slate-300 block"></i>
                            Tidak ada data siswa terdaftar. Tambahkan anggota terlebih dahulu.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php $no = 1; $i = 0; foreach ($anggota_list as $member): ?>
                        <?php
                        $row_class = $i % 2 === 1 ? 'bg-slate-50/40' : '';
                        $i++;
                        ?>
                        <tr class="hover:bg-indigo-50/30 transition duration-100 <?= $row_class ?>">
                            <td class="text-center font-semibold text-slate-400 text-xs"><?= $no++ ?></td>
                            <td style="border-right:1px solid #e2e8f0">
                                <span class="font-semibold text-slate-800"><?= htmlspecialchars($member['nama']) ?></span>
                                <?php if ($member['nis']): ?>
                                    <span class="block text-[9px] text-slate-400 font-mono font-medium">NIS: <?= htmlspecialchars($member['nis']) ?></span>
                                <?php endif; ?>
                            </td>

                            <?php
                            $total_paid_this_month = 0;
                            $iuran_kas = 2000; // default iuran mingguan
                            for ($w = 1; $w <= 5; $w++):
                                $paid = isset($payments[$member['id']][$w]);
                                $amount = $paid ? $payments[$member['id']][$w] : 0;
                                if ($paid) $total_paid_this_month += $amount;
                            ?>
                                <td class="text-center px-3 py-3 group">
                                    <?php if ($paid): ?>
                                        <div class="inline-flex flex-col items-center" title="Lunas Rp <?= number_format($amount, 0, ',', '.') ?>">
                                            <span class="w-8 h-8 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center text-sm font-bold group-hover:scale-110 transition-transform duration-150 shadow-sm">
                                                <i class="fa-solid fa-check"></i>
                                            </span>
                                            <span class="text-[9px] text-emerald-600 font-bold mt-1"><?= number_format($amount, 0, ',', '.') ?></span>
                                        </div>
                                    <?php else: ?>
                                        <div class="inline-flex flex-col items-center" title="Belum membayar">
                                            <span class="w-8 h-8 rounded-full bg-slate-100 text-slate-300 flex items-center justify-center text-sm group-hover:bg-rose-50 group-hover:text-rose-300 transition-all duration-150">
                                                <i class="fa-solid fa-minus"></i>
                                            </span>
                                            <span class="text-[9px] text-slate-300 font-medium mt-1">-</span>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            <?php endfor; ?>

                            <!-- Total Bulan Ini -->
                            <td class="text-center font-bold" style="border-left:1px solid #e2e8f0;background:#fafaff">
                                <span class="inline-block px-3 py-1.5 bg-indigo-50 text-indigo-700 rounded-lg text-xs font-bold border border-indigo-100">
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
</stan>