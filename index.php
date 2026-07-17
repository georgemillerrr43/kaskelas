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
        <button onclick="exportSiswaPDF()" class="btn btn-outline btn-sm"><i class="fa-solid fa-file-pdf"></i> Export PDF Siswa</button>
        <button onclick="exportRiwayatPDF()" class="btn btn-outline btn-sm"><i class="fa-solid fa-file-pdf"></i> Export PDF Riwayat</button>
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
                                <td style="text-align:center;white-space:nowrap">
                                    <?php if ($paid): ?>
                                        <span style="display:inline-flex;flex-direction:row;align-items:center;gap:4px;justify-content:center;white-space:nowrap">
                                            <span class="status-dot" style="background:var(--income-bg);color:var(--income);width:22px;height:22px;font-size:10px;flex-shrink:0"><i class="fa-solid fa-check"></i></span>
                                            <span style="font-size:9px;font-weight:700;color:var(--income);white-space:nowrap"><?= number_format($amt,0,',','.') ?></span>
                                        </span>
                                    <?php else: ?>
                                        <span style="display:inline-flex;flex-direction:row;align-items:center;gap:4px;justify-content:center;white-space:nowrap">
                                            <span class="status-dot" style="background:var(--surface-bg);color:var(--text-dim);width:22px;height:22px;font-size:10px;flex-shrink:0"><i class="fa-solid fa-minus"></i></span>
                                            <span style="font-size:9px;color:var(--text-dim);white-space:nowrap">-</span>
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
    </div>
</div>

<script>
// ── PDF EXPORT — DAFTAR SISWA ──────────────────────────
function exportSiswaPDF() {
    const sigImg = new Image();
    sigImg.crossOrigin = "anonymous";
    sigImg.src = 'assets/images/ttd.svg';
    sigImg.onload = function() {
        const c = document.createElement('canvas');
        c.width = 300; c.height = 120;
        c.getContext('2d').drawImage(sigImg, 0, 0, 300, 120);
        try { genSiswaPDF(c.toDataURL('image/png')); } catch(e) { genSiswaPDF(null); }
    };
    sigImg.onerror = function() { genSiswaPDF(null); };
}

function genSiswaPDF(sigImgData) {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF('l', 'mm', 'a4');
    const PW = 297, PH = 210, ML = 10, MR = 10;
    const C = {
        white:[255,255,255], pageGray:[248,250,252], borderGray:[203,213,225],
        headerBg:[241,245,249], headerText:[15,23,42], subText:[71,85,105],
        dimText:[148,163,184], rowAlt:[248,250,252], black:[15,23,42],
        accentBlue:[79,70,229],
    };
    doc.setFillColor(...C.pageGray); doc.rect(0,0,PW,PH,'F');
    doc.setFillColor(...C.white); doc.roundedRect(8,8,PW-16,PH-16,3,3,'F');
    doc.setFillColor(...C.accentBlue); doc.rect(8,8,PW-16,4,'F');

    // Kop
    const tx = ML+2; let y = 20;
    doc.setFont('helvetica','bold'); doc.setFontSize(14); doc.setTextColor(...C.black);
    doc.text('DAFTAR SISWA — STATUS PEMBAYARAN KAS KELAS', tx, y);
    doc.setFont('helvetica','normal'); doc.setFontSize(8.5); doc.setTextColor(...C.subText);
    const bulan = document.querySelector('#filterForm select[name="bulan"]')?.options?.[document.querySelector('#filterForm select[name="bulan"]')?.selectedIndex]?.text || '';
    const tahun = document.querySelector('#filterForm select[name="tahun"]')?.value || '';
    doc.text('Periode: ' + bulan + ' ' + tahun + ' — Sistem Informasi Keuangan Uangkas Kelas', tx, y+5.5);
    const now = new Date(), tglCetak = now.toLocaleDateString('id-ID',{day:'2-digit',month:'long',year:'numeric'});
    const jamCetak = now.toLocaleTimeString('id-ID',{hour:'2-digit',minute:'2-digit'})+' WIB';
    doc.setFont('helvetica','bold'); doc.setFontSize(8); doc.setTextColor(...C.subText);
    doc.text('Tanggal Cetak', PW-12, y, {align:'right'});
    doc.setFont('helvetica','normal'); doc.setTextColor(...C.black);
    doc.text(tglCetak+'  '+jamCetak, PW-12, y+4.5, {align:'right'});

    // Double underline
    doc.setDrawColor(...C.black); doc.setLineWidth(0.8);
    doc.line(tx, y+10, PW-12, y+10);
    doc.setLineWidth(0.25); doc.line(tx, y+11, PW-12, y+11);

    // Collect data from table
    const table = document.querySelector('#pane-siswa .data-table');
    const rows = [];
    const tbody = table?.querySelector('tbody');
    if (tbody) {
        const trs = tbody.querySelectorAll('tr');
        trs.forEach(function(tr) {
            const tds = tr.querySelectorAll('td');
            if (tds.length < 7) return;
            const no = (tds[0].innerText||'').trim();
            const nama = (tds[1].innerText||'').trim().replace(/\s+/g,' ');
            const w = [];
            for (let i=2;i<=6;i++) {
                const paid = tds[i].querySelector('.fa-check');
                const amt = tds[i].querySelector('.fa-check') ? (tds[i].querySelector('span:last-child')?.innerText||'').trim() : '-';
                w.push(paid ? amt : '-');
            }
            const total = tds[7]?.innerText?.trim()||'';
            rows.push([no, nama, ...w, total]);
        });
    }

    // AutoTable
    const sy = y+18;
    doc.autoTable({
        startY: sy, margin: {left: tx, right: 12},
        head: [[
            {content:'No', styles:{halign:'center'}},
            {content:'Nama Siswa'},
            {content:'Mg 1', styles:{halign:'center'}},
            {content:'Mg 2', styles:{halign:'center'}},
            {content:'Mg 3', styles:{halign:'center'}},
            {content:'Mg 4', styles:{halign:'center'}},
            {content:'Mg 5', styles:{halign:'center'}},
            {content:'Total', styles:{halign:'center'}},
        ]],
        body: rows,
        theme: 'grid',
        headStyles: {fillColor:C.headerBg, textColor:C.headerText, fontStyle:'bold', fontSize:8, lineColor:C.borderGray, lineWidth:0.25, cellPadding:{top:4,bottom:4,left:3,right:3}},
        columnStyles: {
            0: {halign:'center', cellWidth:10, fontStyle:'bold', textColor:C.subText},
            1: {cellWidth:70},
            2: {halign:'center', cellWidth:22},
            3: {halign:'center', cellWidth:22},
            4: {halign:'center', cellWidth:22},
            5: {halign:'center', cellWidth:22},
            6: {halign:'center', cellWidth:22},
            7: {halign:'center', cellWidth:30, fontStyle:'bold'},
        },
        styles: {font:'helvetica', fontSize:7.5, cellPadding:{top:3,bottom:3,left:3,right:3}, lineColor:C.borderGray, lineWidth:0.2, valign:'middle', textColor:C.black},
        didParseCell: function(data) {
            if (data.section==='body' && data.row.index%2===1) data.cell.styles.fillColor=C.rowAlt;
        }
    });

    // TTD
    let ttdY = doc.lastAutoTable.finalY + 10;
    if (ttdY + 44 > PH - 14) { doc.addPage(); ttdY = 20; }

    const cx = PW - 38;
    doc.setFont('helvetica','normal'); doc.setFontSize(8); doc.setTextColor(...C.subText);
    doc.text('Mengetahui,', cx, ttdY, {align:'center'});
    doc.text('Bendahara Kelas', cx, ttdY+4, {align:'center'});

    // Nama di atas TTD
    doc.setFont('helvetica','bold'); doc.setFontSize(9); doc.setTextColor(...C.black);
    doc.text('Rizky perdana putra sam', cx, ttdY+9.5, {align:'center'});

    if (sigImgData) {
        try { doc.addImage(sigImgData, 'PNG', cx-28, ttdY+11.5, 56, 24); } catch(e) {}
    }
    doc.setDrawColor(...C.borderGray); doc.setLineWidth(0.4);
    doc.line(cx-26, ttdY+39.5, cx+26, ttdY+39.5);

    // Footer
    const pg = doc.internal.getNumberOfPages();
    for (let p=1; p<=pg; p++) {
        doc.setPage(p);
        doc.setDrawColor(...C.borderGray); doc.setLineWidth(0.3);
        doc.line(tx, PH-14, PW-12, PH-14);
        doc.setFont('helvetica','normal'); doc.setFontSize(7); doc.setTextColor(...C.dimText);
        doc.text('Uangkas Kelas • Laporan Resmi Keuangan Kelas • Dicetak '+tglCetak, tx, PH-9.5);
        doc.text('Hal. '+p+'/'+pg, PW-12, PH-9.5, {align:'right'});
        doc.setFillColor(...C.accentBlue); doc.rect(8, PH-8, PW-16, 2, 'F');
    }
    doc.save('Daftar_Siswa_'+bulan+'_'+tahun+'.pdf');
}

// ── PDF EXPORT — RIWAYAT ───────────────────────────────
function exportRiwayatPDF() {
    const sigImg = new Image();
    sigImg.crossOrigin = "anonymous";
    sigImg.src = 'assets/images/ttd.svg';
    sigImg.onload = function() {
        const c = document.createElement('canvas');
        c.width = 300; c.height = 120;
        c.getContext('2d').drawImage(sigImg, 0, 0, 300, 120);
        try { genRiwayatPDF(c.toDataURL('image/png')); } catch(e) { genRiwayatPDF(null); }
    };
    sigImg.onerror = function() { genRiwayatPDF(null); };
}

function genRiwayatPDF(sigImgData) {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF('p', 'mm', 'a4');
    const PW = 210, PH = 297, ML = 14, MR = 14;
    const C = {
        white:[255,255,255], pageGray:[248,250,252], borderGray:[203,213,225],
        headerBg:[241,245,249], headerText:[15,23,42], subText:[71,85,105],
        dimText:[148,163,184], rowAlt:[248,250,252], incomeText:[4,120,87],
        incomeBg:[236,253,245], expText:[190,18,60], expBg:[255,241,242],
        black:[15,23,42], accentBlue:[79,70,229],
    };
    doc.setFillColor(...C.pageGray); doc.rect(0,0,PW,PH,'F');
    doc.setFillColor(...C.white); doc.roundedRect(10,10,PW-20,PH-20,3,3,'F');
    doc.setFillColor(...C.accentBlue); doc.rect(10,10,PW-20,4,'F');

    const tx = ML+2; let y = 20;
    doc.setFont('helvetica','bold'); doc.setFontSize(14); doc.setTextColor(...C.black);
    doc.text('RIWAYAT TRANSAKSI KAS KELAS', tx, y);
    doc.setFont('helvetica','normal'); doc.setFontSize(8.5); doc.setTextColor(...C.subText);
    doc.text('Seluruh aktivitas keuangan — Sistem Informasi Uangkas Kelas', tx, y+5.5);
    const now = new Date(), tglCetak = now.toLocaleDateString('id-ID',{day:'2-digit',month:'long',year:'numeric'});
    const jamCetak = now.toLocaleTimeString('id-ID',{hour:'2-digit',minute:'2-digit'})+' WIB';
    doc.setFont('helvetica','bold'); doc.setFontSize(8); doc.setTextColor(...C.subText);
    doc.text('Tanggal Cetak', PW-12, y, {align:'right'});
    doc.setFont('helvetica','normal'); doc.setTextColor(...C.black);
    doc.text(tglCetak+'  '+jamCetak, PW-12, y+4.5, {align:'right'});

    doc.setDrawColor(...C.black); doc.setLineWidth(0.8);
    doc.line(tx, y+10, PW-12, y+10);
    doc.setLineWidth(0.25); doc.line(tx, y+11, PW-12, y+11);

    // Collect data
    const table = document.querySelector('#pane-riwayat .data-table');
    const rows = []; let totalPemasukan = 0, totalPengeluaran = 0;
    const tbody = table?.querySelector('tbody');
    if (tbody) {
        const trs = tbody.querySelectorAll('tr');
        trs.forEach(function(tr) {
            const tds = tr.querySelectorAll('td');
            if (tds.length < 6) return;
            const no = (tds[0].innerText||'').trim();
            const tanggal = (tds[1].innerText||'').trim();
            const jenis = (tds[2].innerText||'').trim().toLowerCase();
            const ket = (tds[3].innerText||'').replace(/\s+/g,' ').trim();
            const siswa = (tds[4].innerText||'').trim();
            const raw = (tds[5].innerText||'').replace(/[^\d]/g,'');
            const jml = parseFloat(raw)||0;
            const isPem = jenis.includes('pemasukan');
            if (isPem) totalPemasukan += jml; else totalPengeluaran += jml;
            rows.push({no, tanggal, jenis, ket, siswa, jml, isPem, str: (isPem?'+':'-')+' Rp '+jml.toLocaleString('id-ID')});
        });
    }

    const sy = y+18;
    doc.autoTable({
        startY: sy, margin: {left: tx, right: MR+2},
        head: [[
            {content:'No', styles:{halign:'center'}},
            {content:'Tanggal', styles:{halign:'center'}},
            {content:'Jenis', styles:{halign:'center'}},
            {content:'Keterangan'},
            {content:'Siswa'},
            {content:'Jumlah (Rp)', styles:{halign:'right'}},
        ]],
        body: rows.map(r => [r.no, r.tanggal, r.jenis.toUpperCase(), r.ket, r.siswa||'-', r.str]),
        theme: 'grid',
        headStyles: {fillColor:C.headerBg, textColor:C.headerText, fontStyle:'bold', fontSize:8.5, lineColor:C.borderGray, lineWidth:0.25, cellPadding:{top:5,bottom:5,left:4,right:4}},
        columnStyles: {
            0: {halign:'center', cellWidth:10, fontStyle:'bold', textColor:C.subText},
            1: {halign:'center', cellWidth:24},
            2: {halign:'center', cellWidth:26, fontStyle:'bold'},
            3: {overflow:'linebreak'},
            4: {cellWidth:38},
            5: {halign:'right', cellWidth:34, fontStyle:'bold'},
        },
        styles: {font:'helvetica', fontSize:8, cellPadding:{top:4,bottom:4,left:4,right:4}, lineColor:C.borderGray, lineWidth:0.2, valign:'middle', textColor:C.black, overflow:'linebreak'},
        didParseCell: function(data) {
            if (data.section==='body') {
                const r = rows[data.row.index]; if(!r) return;
                if (r.isPem) {
                    if(data.column.index===2){data.cell.styles.textColor=C.incomeText; data.cell.styles.fillColor=C.incomeBg;}
                    if(data.column.index===5) data.cell.styles.textColor=C.incomeText;
                } else {
                    if(data.column.index===2){data.cell.styles.textColor=C.expText; data.cell.styles.fillColor=C.expBg;}
                    if(data.column.index===5) data.cell.styles.textColor=C.expText;
                }
                if(data.row.index%2===1 && data.column.index!==2) data.cell.styles.fillColor=C.rowAlt;
            }
        }
    });

    // Summary
    const saldo = totalPemasukan - totalPengeluaran;
    let sumY = doc.lastAutoTable.finalY + 6;
    const sumItems = [
        {label:'Total Pemasukan', val:'+Rp '+totalPemasukan.toLocaleString('id-ID'), c:C.incomeText, bg:C.incomeBg},
        {label:'Total Pengeluaran', val:'-Rp '+totalPengeluaran.toLocaleString('id-ID'), c:C.expText, bg:C.expBg},
        {label:'Saldo Akhir', val:'Rp '+Math.abs(saldo).toLocaleString('id-ID')+(saldo<0?' (Defisit)':''), c:saldo>=0?C.accentBlue:C.expText, bg:[238,242,255]},
    ];
    if (sumY + 30 > PH-20) { doc.addPage(); sumY = 20; }
    doc.setFont('helvetica','bold'); doc.setFontSize(7.5); doc.setTextColor(...C.subText);
    doc.text('RINGKASAN', tx+70, sumY+2);
    sumItems.forEach(function(item, i) {
        doc.setFillColor(...item.bg); doc.setDrawColor(...C.borderGray); doc.setLineWidth(0.3);
        doc.rect(tx+70, sumY+4+i*7, 80, 7, 'FD');
        doc.setFont('helvetica',i===2?'bold':'normal'); doc.setFontSize(7.5); doc.setTextColor(...C.subText);
        doc.text(item.label, tx+74, sumY+8.5+i*7);
        doc.setFont('helvetica','bold'); doc.setFontSize(i===2?8.5:8); doc.setTextColor(...item.c);
        doc.text(item.val, tx+146, sumY+8.5+i*7, {align:'right'});
    });

    // TTD
    let ttdY = sumY + 30;
    if (ttdY + 44 > PH - 14) { doc.addPage(); ttdY = 20; }
    const cx = PW - MR - 25;
    doc.setFont('helvetica','normal'); doc.setFontSize(8); doc.setTextColor(...C.subText);
    doc.text('Mengetahui,', cx, ttdY, {align:'center'});
    doc.text('Bendahara Kelas', cx, ttdY+4, {align:'center'});

    // Nama di atas TTD
    doc.setFont('helvetica','bold'); doc.setFontSize(9); doc.setTextColor(...C.black);
    doc.text('Rizky perdana putra sam', cx, ttdY+9.5, {align:'center'});

    if (sigImgData) {
        try { doc.addImage(sigImgData, 'PNG', cx-28, ttdY+11.5, 56, 24); } catch(e) {}
    }
    doc.setDrawColor(...C.borderGray); doc.setLineWidth(0.4);
    doc.line(cx-26, ttdY+39.5, cx+26, ttdY+39.5);

    // Row count
    doc.setFont('helvetica','normal'); doc.setFontSize(7.5); doc.setTextColor(...C.dimText);
    doc.text('Jumlah baris data: '+rows.length+' transaksi', tx, sumY-2);

    // Footer
    const pg = doc.internal.getNumberOfPages();
    for (let p=1; p<=pg; p++) {
        doc.setPage(p);
        doc.setDrawColor(...C.borderGray); doc.setLineWidth(0.3);
        doc.line(tx, PH-16, PW-12, PH-16);
        doc.setFont('helvetica','normal'); doc.setFontSize(7); doc.setTextColor(...C.dimText);
        doc.text('Uangkas Kelas • Laporan Resmi Keuangan Kelas • Dicetak '+tglCetak, tx, PH-11.5);
        doc.text('Hal. '+p+'/'+pg, PW-12, PH-11.5, {align:'right'});
        doc.setFillColor(...C.accentBlue); doc.rect(10, PH-10, PW-20, 2, 'F');
    }
    doc.save('Riwayat_Transaksi_Kas_Kelas.pdf');
}
</script>

<?php require_once 'includes/footer-public.php'; ?>
