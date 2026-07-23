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
            <span style="font-size:10px;font-weight:bold;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.5px;display:block">Total Pemasukan</span>
            <h3 style="font-size:1.5rem;font-weight:bold;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;color:var(--income)"><?= formatRupiah($total_pemasukan) ?></h3>
            <span style="font-size:10px;color:var(--income);font-weight:600;display:flex;align-items:center;gap:4px">
                <i class="fa-solid fa-arrow-trend-up"></i> Akumulasi Masuk
            </span>
        </div>
        <div style="width:40px;height:40px;border-radius:12px;background:var(--income-bg);color:var(--income);display:flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0;margin-left:12px">
            <i class="fa-solid fa-circle-arrow-down"></i>
        </div>
    </div>

    <!-- Card Pengeluaran -->
    <div class="card p-4 md:p-5 flex items-center justify-between card-hover">
        <div class="space-y-1 min-w-0">
            <span style="font-size:10px;font-weight:bold;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.5px;display:block">Total Pengeluaran</span>
            <h3 style="font-size:1.5rem;font-weight:bold;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;color:var(--expense)"><?= formatRupiah($total_pengeluaran) ?></h3>
            <span style="font-size:10px;color:var(--expense);font-weight:600;display:flex;align-items:center;gap:4px">
                <i class="fa-solid fa-arrow-trend-down"></i> Uang Terpakai
            </span>
        </div>
        <div style="width:40px;height:40px;border-radius:12px;background:var(--expense-bg);color:var(--expense);display:flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0;margin-left:12px">
            <i class="fa-solid fa-circle-arrow-up"></i>
        </div>
    </div>

    <!-- Card Saldo Sisa -->
    <div class="card p-4 md:p-5 flex items-center justify-between card-hover">
        <div class="space-y-1 min-w-0">
            <span style="font-size:10px;font-weight:bold;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.5px;display:block">Saldo Saat Ini</span>
            <h3 style="font-size:1.5rem;font-weight:bold;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;color:<?= $saldo_kas >= 0 ? 'var(--primary-600)' : 'var(--expense)' ?>"><?= formatRupiah($saldo_kas) ?></h3>
            <span style="font-size:10px;font-weight:600;display:flex;align-items:center;gap:4px">
                <?php if ($saldo_kas < 0): ?>
                    <span style="color:var(--expense)"><i class="fa-solid fa-triangle-exclamation"></i> Saldo Defisit</span>
                <?php else: ?>
                    <span style="color:var(--primary-400)"><i class="fa-solid fa-scale-balanced"></i> Bersih & Akurat</span>
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
                <span style="font-size:10px;font-weight:bold;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.5px;display:block">Kas Minggu Ini</span>
                <h3 style="font-size:1.5rem;font-weight:bold;color:var(--text)">
                    <?= $siswa_lunas_minggu_ini ?> <span style="font-size:12px;font-weight:600;color:var(--text-muted)">/ <?= $total_anggota ?></span>
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
        <div style="width:100%;background:var(--border-light);border-radius:99px;height:8px">
            <div class="bg-gradient-to-r from-indigo-500 to-violet-500 h-2 rounded-full transition-all duration-500 shadow-sm" style="width: <?= $pct ?>%"></div>
        </div>
        <div class="flex items-center justify-between mt-1.5">
            <span style="font-size:10px;color:var(--text-muted);font-weight:600"><?= round($pct) ?>% terkumpul</span>
            <span style="font-size:10px;color:var(--text-muted);font-weight:600">Minggu <?= $current_w ?>, <?= $nama_bulan[$current_m] ?> <?= $current_y ?></span>
        </div>
    </div>
</div>

<!-- Grid Charts & Recent Activity -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-4 md:gap-6 mb-6 md:mb-8">

    <!-- Chart.js Container -->
    <div class="lg:col-span-2 card p-4 md:p-6 flex flex-col">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-4 mb-4">
            <div class="min-w-0">
                <h4 style="font-size:14px;font-weight:800;color:var(--text)">Grafik Analisis Keuangan</h4>
                <p style="font-size:11px;color:var(--text-muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis" id="chartSubtitle">Pemasukan vs Pengeluaran Bulanan (<?= $tahun_aktif ?>)</p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <!-- Tab Switcher -->
                <div style="display:flex;align-items:center;gap:4px;background:var(--surface-bg);padding:3px;border-radius:12px">
                    <button type="button" id="btnChartBulan" onclick="switchChart('bulan')"
                            style="padding:6px 12px;border-radius:8px;font-size:12px;font-weight:600;background:var(--surface-card);color:var(--text);border:none;cursor:pointer;transition:0.15s">
                        Bulanan
                    </button>
                    <button type="button" id="btnChartMinggu" onclick="switchChart('minggu')"
                            style="padding:6px 12px;border-radius:8px;font-size:12px;font-weight:600;background:transparent;color:var(--text-muted);border:none;cursor:pointer;transition:0.15s">
                        Mingguan
                    </button>
                </div>

                <!-- Filter Tahun -->
                <form method="GET" action="dashboard.php" class="flex items-center gap-2">
                    <label for="tahun" style="font-size:12px;color:var(--text-muted);font-weight:600">Tahun:</label>
                    <select name="tahun" id="tahun" onchange="this.form.submit()" style="font-size:12px;background:var(--surface-bg);border:1px solid var(--border-table);border-radius:8px;padding:6px 10px;outline:none;color:var(--text);font-weight:500;cursor:pointer">
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
                <h4 style="font-size:14px;font-weight:800;color:var(--text)">Transaksi Terbaru</h4>
                <p style="font-size:12px;color:var(--text-muted)">5 riwayat kas teranyar</p>
            </div>
            <a href="transaksi.php" class="text-xs text-indigo-600 hover:text-indigo-700 hover:underline font-semibold transition flex items-center gap-1">
                Semua <i class="fa-solid fa-angle-right text-[10px]"></i>
            </a>
        </div>

        <div class="flex-grow space-y-1 overflow-y-auto">
            <?php if (empty($recent_transactions)): ?>
                <div style="text-align:center;padding:48px 16px;color:var(--text-muted);font-size:14px">
                    <i class="fa-solid fa-receipt" style="font-size:32px;margin-bottom:12px;display:block;color:var(--text-dim)"></i>
                    Belum ada transaksi dicatat.
                </div>
            <?php else: ?>
                <?php foreach ($recent_transactions as $trans): ?>
                    <div class="flex items-start justify-between gap-3 p-3 rounded-xl transition duration-150" style="transition:0.15s" onmouseenter="this.style.background='var(--tab-hover)'" onmouseleave="this.style.background=''">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-9 h-9 rounded-lg flex items-center justify-center text-sm flex-shrink-0 <?= $trans['jenis'] === 'pemasukan' ? 'bg-emerald-50 text-emerald-500' : 'bg-rose-50 text-rose-500' ?>">
                                <i class="fa-solid <?= $trans['jenis'] === 'pemasukan' ? 'fa-arrow-down-long' : 'fa-arrow-up-long' ?>"></i>
                            </div>
                            <div class="min-w-0">
                                <span style="display:block;font-size:14px;font-weight:600;color:var(--text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis" title="<?= htmlspecialchars($trans['keterangan']) ?>">
                                    <?= htmlspecialchars($trans['keterangan']) ?>
                                </span>
                                <span style="display:block;font-size:10px;color:var(--text-muted);margin-top:2px">
                                    <i class="fa-regular fa-calendar mr-1"></i><?= date('d M Y', strtotime($trans['tanggal'])) ?>
                                    &middot; <?= htmlspecialchars($trans['nama_petugas'] ?? 'Sistem') ?>
                                    <?php if (!empty($trans['bukti'])): ?>
                                        &middot; <a href="<?= htmlspecialchars($trans['bukti']) ?>" target="_blank" style="color:var(--primary-600);font-weight:600;text-decoration:none"><i class="fa-solid fa-image"></i> Foto</a>
                                    <?php endif; ?>
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
<div class="card-hover card" style="padding:20px 24px;display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:16px;margin-top:28px;background:var(--tab-active-bg);border-color:rgba(99,102,241,0.15)">
    <div style="display:flex;align-items:flex-start;gap:14px">
        <div style="width:44px;height:44px;border-radius:12px;background:linear-gradient(135deg,var(--primary-500),#8b5cf6);color:#fff;display:flex;align-items:center;justify-content:center;font-size:17px;flex-shrink:0;box-shadow:0 4px 14px rgba(99,102,241,0.25)">
            <i class="fa-solid fa-graduation-cap"></i>
        </div>
        <div>
            <h4 style="font-size:14px;font-weight:800;color:var(--text);margin:0 0 2px">Butuh Bantuan atau Ingin Mencatat Kas?</h4>
            <p style="font-size:12px;color:var(--text-muted);margin:0">Bendahara dapat menambahkan data siswa atau mencatat kas mingguan melalui menu di atas.</p>
        </div>
    </div>
    <div style="display:flex;gap:8px;flex-shrink:0">
            <a href="transaksi.php?action=add" class="btn btn-primary btn-sm">
                <i class="fa-solid fa-plus"></i> Catat Transaksi
            </a>
        <a href="rekap.php" class="btn btn-secondary btn-sm">
            <i class="fa-solid fa-table-cells"></i> Rekap
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
            btnBulan.style.background = 'var(--surface-card)';
            btnBulan.style.color = 'var(--text)';
            btnMinggu.style.background = 'transparent';
            btnMinggu.style.color = 'var(--text-muted)';
            containerBulan.classList.remove('hidden');
            containerMinggu.classList.add('hidden');
            subtitle.innerText = `Pemasukan vs Pengeluaran Bulanan (${tahunAktif})`;
        } else {
            btnMinggu.style.background = 'var(--surface-card)';
            btnMinggu.style.color = 'var(--text)';
            btnBulan.style.background = 'transparent';
            btnBulan.style.color = 'var(--text-muted)';
            containerMinggu.classList.remove('hidden');
            containerBulan.classList.add('hidden');
            subtitle.innerText = `Pemasukan vs Pengeluaran Mingguan (${namaBulanAktif} ${tahunAktif})`;
        }
    }

    function getCSS(varName, fallback) {
        return getComputedStyle(document.documentElement).getPropertyValue(varName).trim() || fallback;
    }
    // Read dynamic theme colors from CSS variables
    const CHART_TEXT = getCSS('--text-muted', '#64748b');
    const CHART_GRID = getCSS('--border-light', '#f1f5f9');
    const CHART_TOOLTIP = '#0f172a';
    const getIncomeColor = (alpha) => {
        const c = getCSS('--income', '#059669');
        return alpha < 1 ? c + Math.round(alpha*255).toString(16).padStart(2,'0') : c;
    };
    const getExpenseColor = (alpha) => {
        const c = getCSS('--expense', '#e11d48');
        return alpha < 1 ? c + Math.round(alpha*255).toString(16).padStart(2,'0') : c;
    };

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
                            if (!c.chartArea) return getIncomeColor(0.7);
                            return createGradient(c.ctx, c.chartArea, getIncomeColor(0.35), getIncomeColor(0.8));
                        },
                        borderColor: getIncomeColor(1),
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
                            if (!c.chartArea) return getExpenseColor(0.7);
                            return createGradient(c.ctx, c.chartArea, getExpenseColor(0.35), getExpenseColor(0.8));
                        },
                        borderColor: getExpenseColor(1),
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
                            color: CHART_TEXT,
                            boxWidth: 12,
                            usePointStyle: true,
                            pointStyle: 'circle',
                            padding: 16
                        }
                    },
                    tooltip: {
                        backgroundColor: CHART_TOOLTIP,
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
                    x: { grid: { display: false }, ticks: { font: { size: 10, weight: '500' }, color: CHART_TEXT } },
                    y: {
                        border: { dash: [4, 4] },
                        grid: { color: CHART_GRID, drawBorder: false },
                        ticks: {
                            font: { size: 10 },
                            color: CHART_TEXT,
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
                            if (!c.chartArea) return getIncomeColor(0.7);
                            return createGradient(c.ctx, c.chartArea, getIncomeColor(0.3), getIncomeColor(0.8));
                        },
                        borderColor: getIncomeColor(1),
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
                            if (!c.chartArea) return getExpenseColor(0.7);
                            return createGradient(c.ctx, c.chartArea, getExpenseColor(0.3), getExpenseColor(0.8));
                        },
                        borderColor: getExpenseColor(1),
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
                            color: CHART_TEXT,
                            boxWidth: 12,
                            usePointStyle: true,
                            pointStyle: 'circle',
                            padding: 16
                        }
                    },
                    tooltip: {
                        backgroundColor: CHART_TOOLTIP,
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
                    x: { grid: { display: false }, ticks: { font: { size: 10, weight: '500' }, color: CHART_TEXT } },
                    y: {
                        border: { dash: [4, 4] },
                        grid: { color: CHART_GRID, drawBorder: false },
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