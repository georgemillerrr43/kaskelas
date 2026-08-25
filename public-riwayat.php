<?php
/**
 * public-riwayat.php
 * Riwayat Transaksi Kas Kelas (Informasi Publik) — Menampilkan seluruh catatan mutasi pemasukan & pengeluaran kas.
 */

require_once 'config/database.php';
require_once 'includes/header-public.php';

$error = '';
$nama_bulan = nama_bulan();

// 1. Ambil ringkasan keuangan global langsung dari database
$ringkasan = get_ringkasan_kas($pdo);
$total_pemasukan = $ringkasan['pemasukan'];
$total_pengeluaran = $ringkasan['pengeluaran'];
$saldo_kas = $ringkasan['saldo'];

try {
    // 2. Ambil 100 riwayat transaksi terbaru
    $stmt_t = $pdo->query("
        SELECT t.*, a.nama AS nama_anggota, a.nis AS nis_anggota 
        FROM transaksi t 
        LEFT JOIN anggota a ON t.anggota_id = a.id 
        ORDER BY t.tanggal DESC, t.id DESC 
        LIMIT 100
    ");
    $semua_transaksi = $stmt_t->fetchAll();
} catch (PDOException $e) {
    $error = 'Gagal memuat data transaksi: ' . $e->getMessage();
    $semua_transaksi = [];
}
?>

<div class="pub-hero" style="padding:20px 0">
    <h1>Riwayat Transaksi Kas Kelas</h1>
    <p>Seluruh aktivitas keuangan kelas — 100 transaksi terbaru secara transparan</p>
</div>

<!-- Statistik Ringkasan Global -->
<div style="display:flex;flex-wrap:wrap;justify-content:space-between;align-items:center;gap:12px;margin-bottom:20px">
    <div class="stat-grid" style="grid-template-columns:repeat(3,1fr);margin-bottom:0;flex:1;max-width:600px">
        <div class="stat-card" style="padding:14px 18px">
            <div class="stat-label">Total Pemasukan</div>
            <div class="stat-value" style="font-size:1.2rem;color:var(--income)"><?= format_rupiah($total_pemasukan) ?></div>
        </div>
        <div class="stat-card" style="padding:14px 18px">
            <div class="stat-label">Total Pengeluaran</div>
            <div class="stat-value" style="font-size:1.2rem;color:var(--expense)"><?= format_rupiah($total_pengeluaran) ?></div>
        </div>
        <div class="stat-card" style="padding:14px 18px">
            <div class="stat-label">Saldo Kas</div>
            <div class="stat-value" style="font-size:1.2rem;color:<?= $saldo_kas >= 0 ? 'var(--primary-600)' : 'var(--expense)' ?>">
                <?= format_rupiah($saldo_kas) ?>
            </div>
        </div>
    </div>
    <button onclick="exportRiwayatPublikPDF()" class="btn btn-outline btn-sm no-print">
        <i class="fa-solid fa-file-pdf"></i> Export PDF
    </button>
</div>

<?php if ($error): ?>
    <div style="background:var(--expense-bg);border-left:4px solid var(--expense);color:var(--expense);padding:14px 18px;border-radius:12px;margin-bottom:20px;font-size:14px"><?= e($error) ?></div>
<?php endif; ?>

<div class="card overflow-hidden">
    <div style="padding:12px 20px;border-bottom:1px solid var(--border);display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:8px">
        <span style="font-size:13px;font-weight:600;color:var(--text-muted)">Menampilkan <strong><?= count($semua_transaksi) ?></strong> transaksi terakhir</span>
        <div style="position:relative;width:100%;max-width:220px" class="no-print">
            <span style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--text-dim);font-size:12px;pointer-events:none"><i class="fa-solid fa-search"></i></span>
            <input type="text" id="searchInput" style="width:100%;padding:9px 12px 9px 34px;border:1px solid var(--border-table);border-radius:8px;font-size:12px;font-family:inherit;background:var(--input-bg);color:var(--text);outline:none" placeholder="Cari transaksi...">
        </div>
    </div>
    <div class="table-wrap">
        <table class="data-table" id="riwayatTable">
            <thead>
                <tr>
                    <th style="width:36px;text-align:center">No</th>
                    <th style="width:90px">Tanggal</th>
                    <th style="width:80px;text-align:center">Jenis</th>
                    <th>Keterangan</th>
                    <th style="width:140px">Siswa / Anggota</th>
                    <th style="width:120px;text-align:right">Jumlah</th>
                    <th class="text-center" style="width:50px">Bukti</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($semua_transaksi)): ?>
                    <tr><td colspan="7" style="text-align:center;padding:60px 16px;color:var(--text-dim)"><i class="fa-solid fa-receipt" style="font-size:32px;display:block;margin-bottom:10px"></i>Belum ada transaksi dicatat di database.</td></tr>
                <?php else: $no=1; foreach ($semua_transaksi as $tr): ?>
                    <tr>
                        <td style="text-align:center;font-weight:600;color:var(--text-dim);font-size:12px"><?= $no++ ?></td>
                        <td style="font-weight:500"><?= date('d/m/Y', strtotime($tr['tanggal'])) ?></td>
                        <td style="text-align:center">
                            <span class="badge" style="background:<?= $tr['jenis']==='pemasukan'?'var(--income-bg)':'var(--expense-bg)'?>;color:<?= $tr['jenis']==='pemasukan'?'var(--income)':'var(--expense)'?>">
                                <i class="fa-solid <?= $tr['jenis']==='pemasukan'?'fa-arrow-down-long':'fa-arrow-up-long'?>"></i> <?= e($tr['jenis']) ?>
                            </span>
                        </td>
                        <td style="font-weight:500">
                            <?= e($tr['keterangan']) ?>
                            <?php if ($tr['minggu']): ?>
                                <span style="display:block;font-size:9px;color:var(--primary-400);font-weight:600;margin-top:2px">
                                    Mg <?= (int)$tr['minggu'] ?>, <?= $nama_bulan[(int)$tr['bulan']] ?? '' ?> <?= (int)$tr['tahun'] ?>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td style="color:var(--text-muted);font-weight:500">
                            <?php if (!empty($tr['nama_anggota'])): ?>
                                <?= e($tr['nama_anggota']) ?>
                            <?php elseif ($tr['jenis'] === 'pemasukan'): ?>
                                <span style="color:var(--text-dim);font-size:11px">Kas Umum</span>
                            <?php else: ?>
                                <span style="color:var(--text-dim)">—</span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align:right;font-weight:700;font-family:monospace;color:<?= $tr['jenis']==='pemasukan'?'var(--income)':'var(--expense)'?>">
                            <?= $tr['jenis']==='pemasukan'?'+':'-' ?><?= number_format($tr['jumlah'],0,',','.') ?>
                        </td>
                        <td class="text-center">
                            <?php if (!empty($tr['bukti'])): ?>
                                <button onclick="previewBukti('<?= e($tr['bukti']) ?>')"
                                        style="display:inline-flex;width:28px;height:28px;border-radius:6px;align-items:center;justify-content:center;font-size:11px;color:var(--primary-600);background:var(--tab-active-bg);border:none;cursor:pointer"
                                        title="Lihat Bukti Foto">
                                    <i class="fa-solid fa-image"></i>
                                </button>
                            <?php else: ?>
                                <span style="color:var(--text-dim);font-size:10px">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ══ MODAL BUKTI TRANSAKSI ══ -->
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

// Search
document.addEventListener('DOMContentLoaded',function(){
    var si=document.getElementById('searchInput');
    if(si)si.addEventListener('input',function(){
        var q=si.value.toLowerCase().trim();
        document.querySelectorAll('#riwayatTable tbody tr').forEach(function(r){
            r.style.display=r.textContent.toLowerCase().indexOf(q)>-1?'':'none';
        });
    });
});

// PDF Export
function loadSigAndRun(cb) {
    try {
        if(typeof window.jspdf==='undefined'){alert('Library PDF gagal dimuat. Coba refresh.');return}
        var t=new window.jspdf.jsPDF('p','mm','a4');
        if(typeof t.autoTable!=='function'){alert('Plugin tabel gagal dimuat.');return}
    }catch(e){alert('Error: '+e.message);return}
    var img=new Image();img.crossOrigin="anonymous";img.src='assets/images/ttd.svg';
    img.onload=function(){try{var c=document.createElement('canvas');c.width=300;c.height=120;c.getContext('2d').drawImage(img,0,0,300,120);cb(c.toDataURL('image/png'))}catch(e){cb(null)}};
    img.onerror=function(){cb(null)};
}
function exportRiwayatPublikPDF(){loadSigAndRun(function(d){try{genRiwayatPublikPDF(d)}catch(e){alert('Gagal: '+e.message)}});}

function genRiwayatPublikPDF(sigImgData){
    var doc=new window.jspdf.jsPDF('p','mm','a4');
    var PW=210,PH=297,ML=14,MR=14,CW=PW-ML-MR;
    var C={white:[255,255,255],pageGray:[245,247,250],borderGray:[200,210,220],headerBg:[240,243,248],headerText:[20,25,35],subText:[70,80,95],dimText:[145,155,170],rowAlt:[247,249,252],incomeText:[4,120,87],incomeBg:[235,252,240],expText:[190,18,60],expBg:[255,240,242],black:[15,20,30],accentBlue:[75,70,225]};
    doc.setFillColor(...C.pageGray);doc.rect(0,0,PW,PH,'F');
    doc.setFillColor(...C.white);doc.roundedRect(10,10,PW-20,PH-20,3,3,'F');
    doc.setFillColor(...C.accentBlue);doc.rect(10,10,PW-20,3.5,'F');
    var tx=ML+2,y=20;
    doc.setFont('helvetica','bold');doc.setFontSize(14);doc.setTextColor(...C.black);
    doc.text('RIWAYAT TRANSAKSI KAS KELAS',tx,y+4);
    doc.setFont('helvetica','normal');doc.setFontSize(8);doc.setTextColor(...C.subText);
    doc.text('Informasi Publik — Sistem Informasi Uangkas Kelas',tx,y+9);
    var now=new Date(),tglCetak=now.toLocaleDateString('id-ID',{day:'2-digit',month:'long',year:'numeric'});
    var jamCetak=now.toLocaleTimeString('id-ID',{hour:'2-digit',minute:'2-digit'})+' WIB';
    doc.setFont('helvetica','bold');doc.setFontSize(7.5);doc.setTextColor(...C.subText);
    doc.text('Tanggal Cetak',PW-12,y+2,{align:'right'});
    doc.setFont('helvetica','normal');doc.setTextColor(...C.black);
    doc.text(tglCetak+'  '+jamCetak,PW-12,y+6.5,{align:'right'});
    doc.setDrawColor(...C.black);doc.setLineWidth(0.7);
    doc.line(tx,y+13,PW-12,y+13);doc.setLineWidth(0.2);doc.line(tx,y+14,PW-12,y+14);

    var table=document.getElementById('riwayatTable');
    if(!table){alert('Tabel tidak ditemukan.');return;}
    var rows=[];var totalPemasukan=0,totalPengeluaran=0;
    var tbody=table.querySelector('tbody');
    if(tbody){tbody.querySelectorAll('tr').forEach(function(tr){
        var tds=tr.querySelectorAll('td');if(tds.length<6)return;
        var no=(tds[0].innerText||'').trim();
        var tgl=(tds[1].innerText||'').trim();
        var jenis=(tds[2].innerText||'').trim().toLowerCase();
        var ket=(tds[3].innerText||'').replace(/\s+/g,' ').trim();
        var siswa=(tds[4].innerText||'').trim();
        var raw=(tds[5].innerText||'').replace(/[^\d]/g,'');
        var jml=parseFloat(raw)||0;
        var isPem=jenis.includes('pemasukan');
        if(isPem) totalPemasukan+=jml; else totalPengeluaran+=jml;
        rows.push({no:no,tanggal:tgl,jenis:jenis,ket:ket,siswa:siswa,jml:jml,isPem:isPem,str:(isPem?'+':'-')+' Rp '+jml.toLocaleString('id-ID')});
    });}

    doc.autoTable({
        startY:y+22,margin:{left:tx,right:MR+2},
        head:[[{content:'No',styles:{halign:'center'}},{content:'Tanggal',styles:{halign:'center'}},{content:'Jenis',styles:{halign:'center'}},{content:'Keterangan Transaksi'},{content:'Siswa / Pembayar'},{content:'Jumlah (Rp)',styles:{halign:'right'}}]],
        body:rows.map(function(r){return[r.no,r.tanggal,r.jenis.toUpperCase(),r.ket,r.siswa||'-',r.str]}),
        theme:'grid',
        headStyles:{fillColor:C.headerBg,textColor:C.headerText,fontStyle:'bold',fontSize:7.5,lineColor:C.borderGray,lineWidth:0.25,cellPadding:{top:3.5,bottom:3.5,left:3,right:3}},
        columnStyles:{0:{halign:'center',cellWidth:12,fontStyle:'bold',textColor:C.subText},1:{halign:'center',cellWidth:24},2:{halign:'center',cellWidth:24,fontStyle:'bold'},3:{overflow:'linebreak'},4:{cellWidth:36},5:{halign:'right',cellWidth:30,fontStyle:'bold'}},
        styles:{font:'helvetica',fontSize:7.5,cellPadding:{top:3,bottom:3,left:3,right:3},lineColor:C.borderGray,lineWidth:0.2,valign:'middle',textColor:C.black,overflow:'linebreak'},
        didParseCell:function(data){
            if(data.section==='body'){var r=rows[data.row.index];if(!r)return;
                if(r.isPem){if(data.column.index===2){data.cell.styles.textColor=C.incomeText;data.cell.styles.fillColor=C.incomeBg}if(data.column.index===5)data.cell.styles.textColor=C.incomeText}
                else{if(data.column.index===2){data.cell.styles.textColor=C.expText;data.cell.styles.fillColor=C.expBg}if(data.column.index===5)data.cell.styles.textColor=C.expText}
                if(data.row.index%2===1&&data.column.index!==2)data.cell.styles.fillColor=C.rowAlt}
        }
    });

    var saldo=totalPemasukan-totalPengeluaran;
    var sumY=doc.lastAutoTable.finalY+8;
    var sumItems=[
        {label:'Total Pemasukan (Tabel)',val:'+Rp '+totalPemasukan.toLocaleString('id-ID'),c:C.incomeText,bg:C.incomeBg},
        {label:'Total Pengeluaran (Tabel)',val:'-Rp '+totalPengeluaran.toLocaleString('id-ID'),c:C.expText,bg:C.expBg},
        {label:'Saldo Bersih',val:'Rp '+Math.abs(saldo).toLocaleString('id-ID')+(saldo<0?' (Defisit)':''),c:saldo>=0?C.accentBlue:C.expText,bg:[238,242,255]},
    ];
    if(sumY+32>PH-20){doc.addPage();sumY=22}
    doc.setFont('helvetica','bold');doc.setFontSize(7.5);doc.setTextColor(...C.subText);
    doc.text('RINGKASAN KEUANGAN',tx,sumY+1);
    sumItems.forEach(function(item,i){
        doc.setFillColor(...item.bg);doc.setDrawColor(...C.borderGray);doc.setLineWidth(0.3);
        doc.rect(tx,sumY+4+i*7,CW,7,'FD');
        doc.setFont('helvetica',i===2?'bold':'normal');doc.setFontSize(7);doc.setTextColor(...C.subText);
        doc.text(item.label,tx+3,sumY+8.5+i*7);
        doc.setFont('helvetica','bold');doc.setFontSize(i===2?8:7.5);doc.setTextColor(...item.c);
        doc.text(item.val,tx+CW-3,sumY+8.5+i*7,{align:'right'});
    });

    var ttdY=sumY+36;
    if(ttdY+42>PH-18){doc.addPage();ttdY=28}
    var cx=PW-MR-28;
    doc.setFont('helvetica','normal');doc.setFontSize(8);doc.setTextColor(...C.subText);
    doc.text('Mengetahui,',cx,ttdY,{align:'center'});doc.text('Ketua Kelas',cx,ttdY+4,{align:'center'});
    doc.setFont('helvetica','bold');doc.setFontSize(9);doc.setTextColor(...C.black);
    doc.text('Rizky perdana putra sam',cx,ttdY+9,{align:'center'});
    if(sigImgData){try{doc.addImage(sigImgData,'PNG',cx-22,ttdY+11,44,20)}catch(e){}}
    doc.setDrawColor(...C.borderGray);doc.setLineWidth(0.4);
    doc.line(cx-24,ttdY+34,cx+24,ttdY+34);

    for(var p=1;p<=doc.internal.getNumberOfPages();p++){
        doc.setPage(p);
        doc.setDrawColor(...C.borderGray);doc.setLineWidth(0.3);
        doc.line(tx,PH-14,PW-12,PH-14);
        doc.setFont('helvetica','normal');doc.setFontSize(6.5);doc.setTextColor(...C.dimText);
        doc.text('Uangkas Kelas — Informasi Publik — Dicetak '+tglCetak,tx,PH-9.5);
        doc.text('Hal. '+p+'/'+doc.internal.getNumberOfPages(),PW-12,PH-9.5,{align:'right'});
        doc.setFillColor(...C.accentBlue);doc.rect(10,PH-8,PW-20,2,'F');
    }
    doc.save('Riwayat_Transaksi_Kas_Kelas.pdf');
}
</script>

<?php require_once 'includes/footer-public.php'; ?>
