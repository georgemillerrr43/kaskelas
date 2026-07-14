<?php
// dashboard.php
require_once 'config/database.php';
require_once 'includes/header.php';

// Array nama bulan bahasa Indonesia
$nama_bulan = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
];

// Ambil tahun aktif dari query parameter, default tahun ini
$tahun_aktif = isset($_GET['tahun']) ? (int)$_GET['tahun'] : (int)date('Y');

// 1. Ambil data ringkasan keuangan
try {
    // Total Pemasukan
    $stmt = $pdo->query("SELECT SUM(jumlah) AS total FROM transaksi WHERE jenis = 'pemasukan'");
    $total_pemasukan = (float)($stmt->fetch()['total'] ?? 0);

    // Total Pengeluaran
    $stmt = $pdo->query("SELECT SUM(jumlah) AS total FROM transaksi WHERE jenis = 'pengeluaran'");
    $total_pengeluaran = (float)($stmt->fetch()['total'] ?? 0);

    // Saldo Sisa (Total Pemasukan - Total Pengeluaran) secara Real-Time
    $saldo_kas = $total_pemasukan - $total_pengeluaran;

    // Total Anggota
    $stmt = $pdo->query("SELECT COUNT(*) AS total FROM anggota");
    $total_anggota = (int)($stmt->fetch()['total'] ?? 0);

    // 2. Kalkulasi Kas Mingguan untuk Minggu Terkini
    $current_w = (int)ceil(date('j') / 7);
    $current_m = (int)date('n');
    $current_y = (int)date('Y');

    // Query berapa siswa yang sudah bayar iuran minggu ini
    $stmt_paid_week = $pdo->prepare("
        SELECT COUNT(DISTINCT anggota_id)
        FROM transaksi
        WHERE jenis = 'pemasukan'
          AND anggota_id IS NOT NULL
          AND minggu = ?
          AND bulan = ?
          AND tahun = ?
    ");
    $stmt_paid_week->execute([$current_w, $current_m, $current_y]);
    $siswa_lunas_minggu_ini = (int)($stmt_paid_week->fetchColumn() ?? 0);

    // 3. Data Grafik Bulanan untuk Tahun Aktif
    $monthly_income = array_fill(1, 12, 0);
    $monthly_expense = array_fill(1, 12, 0);

    $stmt = $pdo->prepare("SELECT MONTH(tanggal) AS bulan, SUM(jumlah) AS total FROM transaksi WHERE jenis = 'pemasukan' AND YEAR(tanggal) = ? GROUP BY MONTH(tanggal)");
    $stmt->execute([$tahun_aktif]);
    while ($row = $stmt->fetch()) {
        $monthly_income[(int)$row['bulan']] = (float)$row['total'];
    }

    $stmt = $pdo->prepare("SELECT MONTH(tanggal) AS bulan, SUM(jumlah) AS total FROM transaksi WHERE jenis = 'pengeluaran' AND YEAR(tanggal) = ? GROUP BY MONTH(tanggal)");
    $stmt->execute([$tahun_aktif]);
    while ($row = $stmt->fetch()) {
        $monthly_expense[(int)$row['bulan']] = (float)$row['total'];
    }

    // 4. Data Grafik Mingguan untuk Bulan Ini
    $weekly_income = array_fill(1, 5, 0);
    $weekly_expense = array_fill(1, 5, 0);

    $stmt = $pdo->prepare("
        SELECT CEIL(DAY(tanggal) / 7) AS mgg, SUM(jumlah) AS total
        FROM transaksi
        WHERE jenis = 'pemasukan' AND MONTH(tanggal) = ? AND YEAR(tanggal) = ?
        GROUP BY CEIL(DAY(tanggal) / 7)
    ");
    $stmt->execute([$current_m, $tahun_aktif]);
    while ($row = $stmt->fetch()) {
        $mgg_idx = (int)$row['mgg'];
        if ($mgg_idx >= 1 && $mgg_idx <= 5) {
            $weekly_income[$mgg_idx] = (float)$row['total'];
        }
    }

    $stmt = $pdo->prepare("
        SELECT CEIL(DAY(tanggal) / 7) AS mgg, SUM(jumlah) AS total
        FROM transaksi
        WHERE jenis = 'pengeluaran' AND MONTH(tanggal) = ? AND YEAR(tanggal) = ?
        GROUP BY CEIL(DAY(tanggal) / 7)
    ");
    $stmt->execute([$current_m, $tahun_aktif]);
    while ($row = $stmt->fetch()) {
        $mgg_idx = (int)$row['mgg'];
        if ($mgg_idx >= 1 && $mgg_idx <= 5) {
            $weekly_expense[$mgg_idx] = (float)$row['total'];
        }
    }

    // 5. Ambil 5 Transaksi Terakhir
    $stmt = $pdo->query("
        SELECT t.*, a.nama AS nama_anggota, u.nama AS nama_petugas
        FROM transaksi t
        LEFT JOIN anggota a ON t.anggota_id = a.id
        LEFT JOIN users u ON t.created_by = u.id
        ORDER BY t.tanggal DESC, t.id DESC
        LIMIT 5
    ");
    $recent_transactions = $stmt->fetchAll();

} catch (PDOException $e) {
    echo "<div class='alert alert-error'>Terjadi kesalahan query: " . $e->getMessage() . "</div>";
    exit();
}

function formatRupiah($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}
?>

<!-- Grid Summary Cards -->
<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 md:gap-5 mb-6 md:mb-8">
    <!-- Card Pemasukan -->
    <div class="card p-4 md:p-5 flex items-center justify-between card-hover">
        <div class="space-y-1 min-w-0">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Total Pemasukan</span>
            <h3 class="text-lg md:text-xl font-bold text-emerald-600 truncate"><?= formatRupiah($total_pemasukan) ?></h3>
            <span class="text-[10px] text-emerald-500 font-semibold flex items-center gap-1">
                <i class="fa-solid fa-arrow-trend-up"></i> Akumulasi Masuk
            </span>
        </div>
        <div class="w-10 h-10 md:w-11 md:h-11 rounded-xl bg-emerald-50 text-emerald-500 flex items-center justify-center text-base md:text-lg flex-shrink-0 ml-3">
            <i class="fa-solid fa-circle-arrow-down"></i>
        </div>
    </div>

    <!-- Card Pengeluaran -->
    <div class="card p-4 md:p-5 flex items-center justify-between card-hover">
        <div class="space-y-1 min-w-0">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Total Pengeluaran</span>
            <h3 class="text-lg md:text-xl font-bold text-rose-600 truncate"><?= formatRupiah($total_pengeluaran) ?></h3>
            <span class="text-[10px] text-rose-500 font-semibold flex items-center gap-1">
                <i class="fa-solid fa-arrow-trend-down"></i> Uang Terpakai
            </span>
        </div>
        <div class="w-10 h-10 md:w-11 md:h-11 rounded-xl bg-rose-50 text-rose-500 flex items-center justify-center text-base md:text-lg flex-shrink-0 ml-3">
            <i class="fa-solid fa-circle-arrow-up"></i>
        </div>
    </div>

    <!-- Card Saldo Sisa -->
    <div class="card p-4 md:p-5 flex items-center justify-between card-hover">
        <div class="space-y-1 min-w-0">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Saldo Saat Ini</span>
            <h3 class="text-lg md:text-xl font-bold truncate <?= $saldo_kas >= 0 ? 'text-indigo-600' : 'text-rose-600' ?>"><?= formatRupiah($saldo_kas) ?></h3>
            <span class="text-[10px] font-semibold flex items-center gap-1">
                <?php if ($saldo_kas < 0): ?>
                    <span class="text-rose-500"><i class="fa-solid fa-triangle-exclamation"></i> Saldo Defisit</span>
                <?php else: ?>
                    <span class="text-indigo-500"><i class="fa-solid fa-scale-balanced"></i> Bersih & Akurat</span>
                <?php endif; ?>
            </span>
        </div>
        <div class="w-10 h-10 md:w-11 md:h-11 rounded-xl bg-indigo-50 text-indigo-500 flex items-center justify-center text-base md:text-lg flex-shrink-0 ml-3">
            <i class="fa-solid fa-wallet"></i>
        </div>
    </div>

    <!-- Card Kas Minggu Ini -->
    <div class="card p-4 md:p-5 card-hover">
        <div class="flex items-start justify-between mb-3">
            <div class="min-w-0">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Kas Minggu Ini</span>
                <h3 class="text-lg md:text-xl font-bold text-slate-800">
                    <?= $siswa_lunas_minggu_ini ?> <span class="text-xs font-semibold text-slate-400">/ <?= $total_anggota ?></span>
                </h3>
            </div>
            <div class="w-10 h-10 md:w-11 md:h-11 rounded-xl bg-blue-50 text-blue-500 flex items-center justify-center text-base md:text-lg flex-shrink-0 ml-3">
                <i class="fa-solid fa-calendar-check"></i>
            </div>
        </div>
        <?php
        $pct = $total_anggota > 0 ? min(100, ($siswa_lunas_minggu_ini / $total_anggota) * 100) : 0;
        ?>
        <!-- Progress bar -->
        <div class="w-full bg-slate-100 rounded-full h-2">
            <div class="bg-gradient-to-r from-indigo-500 to-violet-500 h-2 rounded-full transition-all duration-500 shadow-sm" style="width: <?= $pct ?>%"></div>
        </div>
        <div class="flex items-center justify-between mt-1.5">
            <span class="text-[10px] text-slate-400 font-semibold"><?= round($pct) ?>% terkumpul</span>
            <span class="text-[10px] text-slate-400 font-semibold">Minggu <?= $current_w ?>, <?= $nama_bulan[$current_m] ?> <?= $current_y ?></span>
        </div>
    </div>
</div>

<!-- Grid Charts & Recent Activity -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-4 md:gap-6 mb-6 md:mb-8">

    <!-- Chart.js Container -->
    <div class="lg:col-span-2 card p-4 md:p-6 flex flex-col">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-4 mb-4">
            <div class="min-w-0">
                <h4 class="font-bold text-slate-800 text-sm md:text-base">Grafik Analisis Keuangan</h4>
                <p class="text-[11px] text-slate-400 truncate" id="chartSubtitle">Pemasukan vs Pengeluaran Bulanan (<?= $tahun_aktif ?>)</p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <!-- Tab Switcher -->
                <div class="flex items-center gap-1 bg-slate-100 p-0.5 rounded-xl">
                    <button type="button" id="btnChartBulan" onclick="switchChart('bulan')"
                            class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-white text-indigo-700 shadow-sm transition focus:outline-none">
                        Bulanan
                    </button>
                    <button type="button" id="btnChartMinggu" onclick="switchChart('minggu')"
                            class="px-3 py-1.5 rounded-lg text-xs font-semibold text-slate-500 hover:text-slate-700 transition focus:outline-none">
                        Mingguan
                    </button>
                </div>

                <!-- Filter Tahun -->
                <form method="GET" action="dashboard.php" class="flex items-center gap-2">
                    <label for="tahun" class="text-xs text-slate-500 font-semibold">Tahun:</label>
                    <select name="tahun" id="tahun" onchange="this.form.submit()" class="text-xs bg-slate-50 border border-slate-200 rounded-lg px-2.5 py-1.5 focus:outline-none focus:ring-2 focus:ring-indigo-500 font-medium">
                        <?php
                        $start_year = (int)date('Y') - 5;
                        $end_year = (int)date('Y') + 2;
                        for ($y = $start_year; $y <= $end_year; $y++):
                        ?>
                            <option value="<?= $y ?>" <?= $y === $tahun_aktif ? 'selected' : '' ?>><?= $y ?></option>
                        <?php endfor; ?>
                    </select>
                </form>
            </div>
        </div>

        <!-- Chart Bulanan -->
        <div id="chartBulanContainer" class="relative w-full" style="height:240px;min-height:240px">
            <canvas id="financialChart"></canvas>
        </div>

        <!-- Chart Mingguan -->
        <div id="chartMingguContainer" class="relative w-full hidden" style="height:240px;min-height:240px">
            <canvas id="weeklyFinancialChart"></canvas>
        </div>
    </div>

    <!-- Recent Transactions -->
    <div class="card p-4 md:p-6 flex flex-col">
        <div class="border-b border-slate-100 pb-4 mb-4 flex items-center justify-between">
            <div>
                <h4 class="font-bold text-slate-800 text-base">Transaksi Terbaru</h4>
                <p class="text-xs text-slate-400">5 riwayat kas teranyar</p>
            </div>
            <a href="transaksi.php" class="text-xs text-indigo-600 hover:text-indigo-700 hover:underline font-semibold transition flex items-center gap-1">
                Semua <i class="fa-solid fa-angle-right text-[10px]"></i>
            </a>
        </div>

        <div class="flex-grow space-y-1 overflow-y-auto">
            <?php if (empty($recent_transactions)): ?>
                <div class="text-center py-12 text-slate-400 text-sm">
                    <i class="fa-solid fa-receipt text-3xl mb-3 text-slate-300 block"></i>
                    Belum ada transaksi dicatat.
                </div>
            <?php else: ?>
                <?php foreach ($recent_transactions as $trans): ?>
                    <div class="flex items-start justify-between gap-3 p-3 hover:bg-slate-50 rounded-xl transition duration-150">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-9 h-9 rounded-lg flex items-center justify-center text-sm flex-shrink-0 <?= $trans['jenis'] === 'pemasukan' ? 'bg-emerald-50 text-emerald-500' : 'bg-rose-50 text-rose-500' ?>">
                                <i class="fa-solid <?= $trans['jenis'] === 'pemasukan' ? 'fa-arrow-down-long' : 'fa-arrow-up-long' ?>"></i>
                            </div>
                            <div class="min-w-0">
                                <span class="block text-sm font-semibold text-slate-800 truncate" title="<?= htmlspecialchars($trans['keterangan']) ?>">
                                    <?= htmlspecialchars($trans['keterangan']) ?>
                                </span>
                                <span class="block text-[10px] text-slate-400 mt-0.5">
                                    <i class="fa-regular fa-calendar mr-1"></i><?= date('d M Y', strtotime($trans['tanggal'])) ?>
                                    &middot; <?= htmlspecialchars($trans['nama_petugas'] ?? 'Sistem') ?>
                                </span>
                            </div>
                        </div>
                        <div class="text-right flex-shrink-0 font-mono">
                            <span class="block text-xs font-bold <?= $trans['jenis'] === 'pemasukan' ? 'text-emerald-600' : 'text-rose-600' ?>">
                                <?= $trans['jenis'] === 'pemasukan' ? '+' : '-' ?><?= number_format($trans['jumlah'], 0, ',', '.') ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Info Banner -->
<div class="bg-gradient-to-r from-indigo-50 to-violet-50 border border-indigo-100 rounded-xl md:rounded-2xl p-4 md:p-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
    <div class="flex items-start gap-3 md:gap-4">
        <div class="w-10 h-10 md:w-12 md:h-12 rounded-xl bg-gradient-to-br from-indigo-500 to-violet-500 text-white flex items-center justify-center text-base md:text-lg flex-shrink-0 shadow-md shadow-indigo-500/20">
            <i class="fa-solid fa-graduation-cap"></i>
        </div>
        <div class="min-w-0">
            <h4 class="font-bold text-indigo-900 text-sm md:text-base">Butuh Bantuan atau Ingin Mencatat Kas?</h4>
            <p class="text-xs md:text-sm text-indigo-700/80 mt-0.5">Bendahara dapat menambahkan data siswa atau mencatat kas mingguan dengan navigasi cepat di sebelah kiri.</p>
        </div>
    </div>
    <div class="flex items-center gap-3 flex-shrink-0">
        <?php if ($user_role === 'admin'): ?>
            <a href="transaksi.php?action=add" class="btn btn-primary btn-sm">
                <i class="fa-solid fa-plus"></i> Catat Transaksi
            </a>
        <?php endif; ?>
        <a href="rekap.php" class="btn btn-secondary btn-sm">
            <i class="fa-solid fa-table-cells"></i> Lihat Matriks Rekap
        </a>
    </div>
</div>

<!-- Chart.js Library -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const namaBulanAktif = '<?= $nama_bulan[$current_m] ?>';
    const tahunAktif = '<?= $tahun_aktif ?>';

    function switchChart(type) {
        const btnBulan = document.getElementById('btnChartBulan');
        const btnMinggu = document.getElementById('btnChartMinggu');
        const containerBulan = document.getElementById('chartBulanContainer');
        const containerMinggu = document.getElementById('chartMingguContainer');
        const subtitle = document.getElementById('chartSubtitle');

        if (type === 'bulan') {
            btnBulan.classList.add('bg-white', 'text-indigo-700', 'shadow-sm');
            btnBulan.classList.remove('text-slate-500');
            btnMinggu.classList.remove('bg-white', 'text-indigo-700', 'shadow-sm');
            btnMinggu.classList.add('text-slate-500');
            containerBulan.classList.remove('hidden');
            containerMinggu.classList.add('hidden');
            subtitle.innerText = `Pemasukan vs Pengeluaran Bulanan (${tahunAktif})`;
        } else {
            btnMinggu.classList.add('bg-white', 'text-indigo-700', 'shadow-sm');
            btnMinggu.classList.remove('text-slate-500');
            btnBulan.classList.remove('bg-white', 'text-indigo-700', 'shadow-sm');
            btnBulan.classList.add('text-slate-500');
            containerMinggu.classList.remove('hidden');
            containerBulan.classList.add('hidden');
            subtitle.innerText = `Pemasukan vs Pengeluaran Mingguan (${namaBulanAktif} ${tahunAktif})`;
        }
    }

    document.addEventListener("DOMContentLoaded", function () {
        // ── Chart defaults ──
        Chart.defaults.font.family = "'Plus Jakarta Sans', sans-serif";
        Chart.defaults.font.size = 11;
        Chart.defaults.responsive = true;
        Chart.defaults.maintainAspectRatio = false;

        function createGradient(ctx, chartArea, color1, color2) {
            const gradient = ctx.createLinearGradient(0, chartArea.bottom, 0, chartArea.top);
            gradient.addColorStop(0, color1);
            gradient.addColorStop(1, color2);
            return gradient;
        }

        // ── Chart 1: Bulanan ──
        const ctxBulan = document.getElementById('financialChart').getContext('2d');
        new Chart(ctxBulan, {
            type: 'bar',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'],
                datasets: [
                    {
                        label: 'Pemasukan',
                        data: <?= json_encode(array_values($monthly_income)) ?>,
                        backgroundColor: function(context) {
                            const c = context.chart;
                            if (!c.chartArea) return 'rgba(5,150,105,0.7)';
                            return createGradient(c.ctx, c.chartArea, 'rgba(5,150,105,0.35)', 'rgba(5,150,105,0.8)');
                        },
                        borderColor: '#059669',
                        borderWidth: 1,
                        borderRadius: 6,
                        barPercentage: 0.6,
                        categoryPercentage: 0.7
                    },
                    {
                        label: 'Pengeluaran',
                        data: <?= json_encode(array_values($monthly_expense)) ?>,
                        backgroundColor: function(context) {
                            const c = context.chart;
                            if (!c.chartArea) return 'rgba(225,29,72,0.7)';
                            return createGradient(c.ctx, c.chartArea, 'rgba(225,29,72,0.35)', 'rgba(225,29,72,0.8)');
                        },
                        borderColor: '#e11d48',
                        borderWidth: 1,
                        borderRadius: 6,
                        barPercentage: 0.6,
                        categoryPercentage: 0.7
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            font: { size: 11, weight: '600' },
                            color: '#475569',
                            boxWidth: 12,
                            usePointStyle: true,
                            pointStyle: 'circle',
                            padding: 16
                        }
                    },
                    tooltip: {
                        backgroundColor: '#0f172a',
                        titleFont: { size: 12, weight: '700' },
                        bodyFont: { size: 12 },
                        padding: 14,
                        cornerRadius: 10,
                        callbacks: {
                            label: function(context) {
                                const label = context.dataset.label || '';
                                const val = context.parsed.y;
                                return label + ': ' + new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(val);
                            }
                        }
                    }
                },
                scales: {
                    x: { grid: { display: false }, ticks: { font: { size: 10, weight: '500' }, color: '#94a3b8' } },
                    y: {
                        border: { dash: [4, 4] },
                        grid: { color: '#f1f5f9', drawBorder: false },
                        ticks: {
                            font: { size: 10 },
                            color: '#94a3b8',
                            callback: v => v >= 1000000 ? (v / 1000000) + 'M' : v >= 1000 ? (v / 1000) + 'rb' : v
                        }
                    }
                }
            }
        });

        // ── Chart 2: Mingguan ──
        const ctxMinggu = document.getElementById('weeklyFinancialChart').getContext('2d');
        new Chart(ctxMinggu, {
            type: 'bar',
            data: {
                labels: ['Minggu 1', 'Minggu 2', 'Minggu 3', 'Minggu 4', 'Minggu 5'],
                datasets: [
                    {
                        label: 'Pemasukan',
                        data: <?= json_encode(array_values($weekly_income)) ?>,
                        backgroundColor: function(context) {
                            const c = context.chart;
                            if (!c.chartArea) return 'rgba(79,70,229,0.7)';
                            return createGradient(c.ctx, c.chartArea, 'rgba(79,70,229,0.3)', 'rgba(79,70,229,0.8)');
                        },
                        borderColor: '#4f46e5',
                        borderWidth: 1,
                        borderRadius: 6,
                        barPercentage: 0.5,
                        categoryPercentage: 0.6
                    },
                    {
                        label: 'Pengeluaran',
                        data: <?= json_encode(array_values($weekly_expense)) ?>,
                        backgroundColor: function(context) {
                            const c = context.chart;
                            if (!c.chartArea) return 'rgba(225,29,72,0.7)';
                            return createGradient(c.ctx, c.chartArea, 'rgba(225,29,72,0.3)', 'rgba(225,29,72,0.8)');
                        },
                        borderColor: '#e11d48',
                        borderWidth: 1,
                        borderRadius: 6,
                        barPercentage: 0.5,
                        categoryPercentage: 0.6
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            font: { size: 11, weight: '600' },
                            color: '#475569',
                            boxWidth: 12,
                            usePointStyle: true,
                            pointStyle: 'circle',
                            padding: 16
                        }
                    },
                    tooltip: {
                        backgroundColor: '#0f172a',
                        titleFont: { size: 12, weight: '700' },
                        bodyFont: { size: 12 },
                        padding: 14,
                        cornerRadius: 10,
                        callbacks: {
                            label: function(context) {
                                const label = context.dataset.label || '';
                                const val = context.parsed.y;
                                return label + ': ' + new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(val);
                            }
                        }
                    }
                },
                scales: {
                    x: { grid: { display: false }, ticks: { font: { size: 10, weight: '500' }, color: '#94a3b8' } },
                    y: {
                        border: { dash: [4, 4] },
                        grid: { color: '#f1f5f9', drawBorder: false },
                        ticks: {
                            font: { size: 10 },
                            color: '#94a3b8',
                            callback: v => v >= 1000000 ? (v / 1000000) + 'M' : v >= 1000 ? (v / 1000) + 'rb' : v
                        }
                    }
                }
            }
        });
    });
</script>

<?php require_once 'includes/footer.php'; ?>