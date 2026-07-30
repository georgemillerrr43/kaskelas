<?php
// public-rekap.php
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
    $stmt_m = $pdo->query("SELECT id, nis, nama FROM anggota ORDER BY nama ASC");
    $anggota_list = $stmt_m->fetchAll();
    $stmt_p = $pdo->prepare("SELECT anggota_id, minggu, SUM(jumlah) AS total_bayar FROM transaksi WHERE jenis = 'pemasukan' AND anggota_id IS NOT NULL AND bulan = ? AND tahun = ? GROUP BY anggota_id, minggu");
    $stmt_p->execute([$bulan_aktif, $tahun_aktif]);
    $payments = [];
    foreach ($stmt_p->fetchAll() as $pay) {
        $payments[(int)$pay['anggota_id']][(int)$pay['minggu']] = (float)$pay['total_bayar'];
    }
} catch (PDOException $e) {
    $error = 'Gagal memuat rekapitulasi: ' . $e->getMessage();
    $anggota_list = []; $payments = [];
}
function fr($a) { return 'Rp ' . number_format($a, 0, ',', '.'); }
?>
<div class="pub-hero" style="padding:20px 0">
    <h1>Rekap Kas Mingguan</h1>
    <p>Status iuran mingguan per siswa — <?= $nama_bulan[$bulan_aktif] ?> <?= $tahun_aktif ?></p>
</div>

<div style="display:flex;flex-wrap:wrap;justify-content:space-between;align-items:center;gap:12px;margin-bottom:20px">
    <form method="GET" action="public-rekap.php" style="display:flex;gap:8px">
        <select name="bulan" onchange="this.form.submit()" style="padding:8px 12px;border:1px solid var(--border-table);border-radius:8px;font-size:12px;font-weight:600;font-family:inherit;background:var(--input-bg);color:var(--text);outline:none;cursor:pointer">
            <?php foreach ($nama_bulan as $num => $name): ?>
                <option value="<?= $num ?>" <?= $num === $bulan_aktif ? 'selected' : '' ?>><?= $name ?></option>
            <?php endforeach; ?>
        </select>
        <select name="tahun" onchange="this.form.submit()" style="padding:8px 12px;border:1px solid var(--border-table);border-radius:8px;font-size:12px;font-weight:600;font-family:inherit;background:var(--input-bg);color:var(--text);outline:none;cursor:pointer">
            <?php for ($y = (int)date('Y') - 3; $y <= (int)date('Y') + 2; $y++): ?>
                <option value="<?= $y ?>" <?= $y === $tahun_aktif ? 'selected' : '' ?>><?= $y ?></option>
            <?php endfor; ?>
        </select>
    </form>
    <button onclick="exportRekapPublikPDF()" class="btn btn-outline btn-sm no-print">
        <i class="fa-solid fa-file-pdf"></i> Export PDF
    </button>
</div>

<?php if ($error): ?>
    <div style="background:var(--expense-bg);border-left:4px solid var(--expense);color:var(--expense);padding:14px 18px;border-radius:12px;margin-bottom:20px;font-size:14px"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div style="display:flex;flex-wrap:wrap;gap:10px;align-items:center;font-size:12px;color:var(--text-muted);font-weight:500;background:var(--surface-bg);padding:10px 14px;border-radius:10px;border:1px solid var(--border);margin-bottom:20px">
    <span style="font-weight:700;color:var(--text)"><i class="fa-solid fa-circle-info" style="margin-right:4px;color:var(--primary-400)"></i> Keterangan:</span>
    <span style="display:inline-flex;align-items:center;gap:5px"><span style="width:16px;height:16px;border-radius:50%;background:var(--income);display:flex;align-items:center;justify-content:center"><i class="fa-solid fa-check" style="color:#fff;font-size:8px"></i></span> Lunas</span>
    <span style="display:inline-flex;align-items:center;gap:5px"><span style="width:16px;height:16px;border-radius:50%;background:var(--border-table);display:flex;align-items:center;justify-content:center"><i class="fa-solid fa-minus" style="color:var(--text-dim);font-size:8px"></i></span> Belum Bayar</span>
</div>

<div class="card overflow-hidden">
    <div class="table-wrap">
        <table class="data-table" id="rekapTable">
            <thead>
                <tr>
                    <th style="width:40px;text-align:center">No</th>
                    <th style="min-width:150px;border-right:1px solid var(--border-table)">Nama Siswa</th>
                    <th style="width:95px;text-align:center">Mg 1</th>
                    <th style="width:95px;text-align:center">Mg 2</th>
                    <th style="width:95px;text-align:center">Mg 3</th>
                    <th style="width:95px;text-align:center">Mg 4</th>
                    <th style="width:95px;text-align:center">Mg 5</th>
                    <th style="width:100px;border-left:1px solid var(--border-table);text-align:center;background:var(--surface-bg)">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($anggota_list)): ?>
                    <tr><td colspan="8" style="text-align:center;padding:60px 16px;color:var(--text-dim)"><i class="fa-solid fa-users-slash" style="font-size:32px;display:block;margin-bottom:10px"></i>Belum ada data siswa.</td></tr>
                <?php else: $no=1; $i=0; foreach ($anggota_list as $m): $i++; $total=0;
                    for ($w=1;$w<=5;$w++){if(isset($payments[$m['id']][$w]))$total+=$payments[$m['id']][$w];}
                ?>
                    <tr style="<?= $i%2===0?'background:var(--surface-bg)':'' ?>">
                        <td style="text-align:center;font-weight:600;color:var(--text-dim);font-size:12px"><?= $no++ ?></td>
                        <td style="border-right:1px solid var(--border-table)">
                            <span style="font-weight:600;color:var(--text)"><?= htmlspecialchars($m['nama']) ?></span>
                            <?php if ($m['nis']): ?><span style="display:block;font-size:9px;color:var(--text-dim);font-family:monospace">NIS: <?= htmlspecialchars($m['nis']) ?></span><?php endif; ?>
                        </td>
                        <?php for ($w=1;$w<=5;$w++):
                            $paid = isset($payments[$m['id']][$w]);
                            $amt = $paid ? $payments[$m['id']][$w] : 0;
                        ?>
                            <td style="text-align:center;padding:8px 6px;white-space:nowrap">
                                <?php if ($paid): ?>
                                    <span style="display:inline-flex;align-items:center;gap:5px;white-space:nowrap">
                                        <span style="width:22px;height:22px;border-radius:50%;background:var(--income-bg);color:var(--income);display:inline-flex;align-items:center;justify-content:center;font-size:9px;font-weight:bold;flex-shrink:0"><i class="fa-solid fa-check"></i></span>
                                        <span style="font-size:10px;font-weight:700;color:var(--income);white-space:nowrap"><?= number_format($amt,0,',','.') ?></span>
                                    </span>
                                <?php else: ?>
                                    <span style="display:inline-flex;align-items:center;gap:5px;white-space:nowrap">
                                        <span style="width:22px;height:22px;border-radius:50%;background:var(--surface-bg);color:var(--text-dim);display:inline-flex;align-items:center;justify-content:center;font-size:9px;flex-shrink:0"><i class="fa-solid fa-minus"></i></span>
                                        <span style="font-size:10px;color:var(--text-dim);white-space:nowrap">-</span>
                                    </span>
                                <?php endif; ?>
                            </td>
                        <?php endfor; ?>
                        <td style="text-align:center;vertical-align:middle;border-left:1px solid var(--border-table);background:var(--surface-bg);white-space:nowrap">
                            <span style="display:inline-flex;align-items:center;gap:3px;padding:3px 10px;background:var(--tab-active-bg);color:var(--tab-active-text);border-radius:8px;font-size:11px;font-weight:700;border:1px solid rgba(99,102,241,0.15);white-space:nowrap">Rp <?= number_format($total,0,',','.') ?></span>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function loadSigAndRun(cb) {
    try { if(typeof window.jspdf==='undefined'){alert('Library PDF gagal dimuat. Coba refresh.');return}
    var t=new window.jspdf.jsPDF('p','mm','a4'); if(typeof t.autoTable!=='function'){alert('Plugin tabel gagal dimuat.');return}
    }catch(e){alert('Error: '+e.message);return}
    var img=new Image(); img.crossOrigin="anonymous"; img.src='assets/images/ttd.svg';
    img.onload=function(){try{var c=document.createElement('canvas');c.width=300;c.height=120;c.getContext('2d').drawImage(img,0,0,300,120);cb(c.toDataURL('image/png'))}catch(e){cb(null)}};
    img.onerror=function(){cb(null)};
}
function exportRekapPublikPDF(){loadSigAndRun(function(d){try{genRekapPublikPDF(d)}catch(e){alert('Gagal: '+e.message)}});}

function genRekapPublikPDF(sigImgData){
    var doc=new window.jspdf.jsPDF('p','mm','a4');
    var PW=210,PH=297,ML=14,MR=14;
    var C={white:[255,255,255],pageGray:[245,247,250],borderGray:[200,210,220],headerBg:[240,243,248],headerText:[20,25,35],subText:[70,80,95],dimText:[145,155,170],rowAlt:[247,249,252],black:[15,20,30],accentBlue:[75,70,225]};
    doc.setFillColor(...C.pageGray);doc.rect(0,0,PW,PH,'F');
    doc.setFillColor(...C.white);doc.roundedRect(10,10,PW-20,PH-20,3,3,'F');
    doc.setFillColor(...C.accentBlue);doc.rect(10,10,PW-20,3.5,'F');
    var tx=ML+2,y=20;
    doc.setFont('helvetica','bold');doc.setFontSize(14);doc.setTextColor(...C.black);
    doc.text('REKAP KAS MINGGUAN',tx,y+4);
    doc.setFont('helvetica','normal');doc.setFontSize(8);doc.setTextColor(...C.subText);
    var bulan='<?= $nama_bulan[$bulan_aktif] ?>',tahun='<?= $tahun_aktif ?>';
    doc.text('Periode: '+bulan+' '+tahun+' — Informasi Publik Uangkas Kelas',tx,y+9);
    var now=new Date(),tglCetak=now.toLocaleDateString('id-ID',{day:'2-digit',month:'long',year:'numeric'});
    var jamCetak=now.toLocaleTimeString('id-ID',{hour:'2-digit',minute:'2-digit'})+' WIB';
    doc.setFont('helvetica','bold');doc.setFontSize(7.5);doc.setTextColor(...C.subText);
    doc.text('Tanggal Cetak',PW-12,y+2,{align:'right'});
    doc.setFont('helvetica','normal');doc.setTextColor(...C.black);
    doc.text(tglCetak+'  '+jamCetak,PW-12,y+6.5,{align:'right'});
    doc.setDrawColor(...C.black);doc.setLineWidth(0.7);
    doc.line(tx,y+13,PW-12,y+13);doc.setLineWidth(0.2);doc.line(tx,y+14,PW-12,y+14);

    var table=document.getElementById('rekapTable');
    if(!table){alert('Tabel tidak ditemukan.');return;}
    var rows=[];var tbody=table.querySelector('tbody');
    if(tbody){tbody.querySelectorAll('tr').forEach(function(tr){
        var tds=tr.querySelectorAll('td');if(tds.length<7)return;
        var n=(tds[0].innerText||'').trim();
        var nm=(tds[1].innerText||'').trim().replace(/\s+/g,' ');
        var w=[];for(var i=2;i<=6;i++){var p=tds[i].querySelector('.fa-check');w.push(p?(tds[i].querySelector('span:last-child')?.innerText||'').trim():'-');}
        rows.push([n,nm,].concat(w, [(tds[7]?.innerText||'').trim()]));
    });}

    doc.autoTable({
        startY:y+22,margin:{left:tx,right:12},
        head:[[{content:'No',styles:{halign:'center'}},{content:'Nama Siswa'},{content:'Mg1',styles:{halign:'center'}},{content:'Mg2',styles:{halign:'center'}},{content:'Mg3',styles:{halign:'center'}},{content:'Mg4',styles:{halign:'center'}},{content:'Mg5',styles:{halign:'center'}},{content:'Total',styles:{halign:'center'}}]],
        body:rows,theme:'grid',
        headStyles:{fillColor:C.headerBg,textColor:C.headerText,fontStyle:'bold',fontSize:7,lineColor:C.borderGray,lineWidth:0.25,cellPadding:{top:3,bottom:3,left:2,right:2}},
        columnStyles:{0:{halign:'center',cellWidth:12,fontStyle:'bold',textColor:C.subText},1:{cellWidth:58},2:{halign:'center',cellWidth:20},3:{halign:'center',cellWidth:20},4:{halign:'center',cellWidth:20},5:{halign:'center',cellWidth:20},6:{halign:'center',cellWidth:20},7:{halign:'center',cellWidth:28,fontStyle:'bold'}},
        styles:{font:'helvetica',fontSize:7,cellPadding:{top:2.5,bottom:2.5,left:2,right:2},lineColor:C.borderGray,lineWidth:0.2,valign:'middle',textColor:C.black},
        didParseCell:function(data){if(data.section==='body'&&data.row.index%2===1)data.cell.styles.fillColor=C.rowAlt;}
    });

    // TTD
    var ttdY=doc.lastAutoTable.finalY+12;
    if(ttdY+42>PH-18){doc.addPage();ttdY=25;}
    var cx=PW-42;
    doc.setFont('helvetica','normal');doc.setFontSize(8);doc.setTextColor(...C.subText);
    doc.text('Mengetahui,',cx,ttdY,{align:'center'});
    doc.text('Ketua Kelas',cx,ttdY+4,{align:'center'});
    doc.setFont('helvetica','bold');doc.setFontSize(9);doc.setTextColor(...C.black);
    doc.text('Rizky perdana putra sam',cx,ttdY+9,{align:'center'});
    if(sigImgData){try{doc.addImage(sigImgData,'PNG',cx-22,ttdY+11,44,20);}catch(e){}}
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
    doc.save('Rekap_Kas_'+bulan+'_'+tahun+'.pdf');
}
</script>

<?php require_once 'includes/footer-public.php'; ?>
