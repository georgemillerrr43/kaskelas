<?php
/**
 * anggota.php
 * Manajemen Data Anggota / Siswa Kelas — Tambah, edit, hapus data siswa, pencarian cepat, dan pengurutan.
 */

require_once 'config/database.php';
require_once 'includes/header.php';

$error = '';
$success = '';

// Ambil data untuk form edit jika ada parameter edit
$edit_mode = false;
$edit_id = '';
$edit_nis = '';
$edit_nama = '';
$edit_jenis_kelamin = '';

if ($user_role === 'admin' && isset($_GET['action'])) {
    $action = $_GET['action'];
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

    if ($action === 'edit' && $id > 0) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM anggota WHERE id = ?");
            $stmt->execute([$id]);
            $member = $stmt->fetch();
            if ($member) {
                $edit_mode = true;
                $edit_id = $member['id'];
                $edit_nis = $member['nis'] ?? '';
                $edit_nama = $member['nama'];
                $edit_jenis_kelamin = $member['jenis_kelamin'];
            }
        } catch (PDOException $e) {
            $error = 'Gagal memuat data siswa: ' . $e->getMessage();
        }
    } elseif ($action === 'delete' && $id > 0) {
        try {
            $stmt = $pdo->prepare("DELETE FROM anggota WHERE id = ?");
            $stmt->execute([$id]);
            $success = 'Data siswa berhasil dihapus dari database!';
        } catch (PDOException $e) {
            $error = 'Gagal menghapus data siswa: ' . $e->getMessage();
        }
    }
}

// Tangani input POST (Tambah & Edit)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user_role === 'admin') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $nis = trim($_POST['nis'] ?? '');
    $nama = trim($_POST['nama'] ?? '');
    $jenis_kelamin = trim($_POST['jenis_kelamin'] ?? '');

    if ($nama === '' || !in_array($jenis_kelamin, ['L', 'P'])) {
        $error = 'Nama lengkap dan Jenis Kelamin wajib diisi!';
    } else {
        try {
            if ($id > 0) {
                // Mode Edit
                if ($nis !== '') {
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM anggota WHERE nis = ? AND id != ?");
                    $stmt->execute([$nis, $id]);
                    if ($stmt->fetchColumn() > 0) {
                        throw new Exception('NIS sudah digunakan oleh siswa lain!');
                    }
                }
                $stmt = $pdo->prepare("UPDATE anggota SET nis = ?, nama = ?, jenis_kelamin = ? WHERE id = ?");
                $stmt->execute([$nis !== '' ? $nis : null, $nama, $jenis_kelamin, $id]);
                $success = 'Data siswa berhasil diperbarui!';
                $edit_mode = false;
            } else {
                // Mode Tambah
                if ($nis !== '') {
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM anggota WHERE nis = ?");
                    $stmt->execute([$nis]);
                    if ($stmt->fetchColumn() > 0) {
                        throw new Exception('NIS sudah terdaftar sebelumnya!');
                    }
                }
                $stmt = $pdo->prepare("INSERT INTO anggota (nis, nama, jenis_kelamin) VALUES (?, ?, ?)");
                $stmt->execute([$nis !== '' ? $nis : null, $nama, $jenis_kelamin]);
                $success = 'Siswa baru berhasil ditambahkan ke database!';
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
            if ($id > 0) {
                $edit_mode = true;
                $edit_id = $id;
                $edit_nis = $nis;
                $edit_nama = $nama;
                $edit_jenis_kelamin = $jenis_kelamin;
            }
        }
    }
}

// Ambil daftar anggota dengan opsi urutan
$urutan = $_GET['urutan'] ?? 'nama';
if ($urutan === 'nis') {
    $order_clause = "CASE WHEN nis IS NULL OR nis = '' THEN 1 ELSE 0 END, CAST(nis AS UNSIGNED) ASC, nama ASC";
} else {
    $order_clause = "nama ASC";
}

try {
    $stmt = $pdo->query("SELECT * FROM anggota ORDER BY $order_clause");
    $anggota_list = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = 'Gagal memuat daftar siswa: ' . $e->getMessage();
    $anggota_list = [];
}
?>

<!-- Alert Notifikasi -->
<?php if ($error !== ''): ?>
    <div class="alert alert-error mb-6"><i class="fa-solid fa-circle-exclamation mr-2"></i> <?= e($error) ?></div>
<?php endif; ?>

<?php if ($success !== ''): ?>
    <div class="alert alert-success mb-6"><i class="fa-solid fa-circle-check mr-2"></i> <?= e($success) ?></div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">

    <!-- Form Tambah/Edit (Admin only) -->
    <div class="lg:col-span-1 card p-5 md:p-6">
        <h3 style="font-size:14px;font-weight:800;color:var(--text);margin-bottom:4px">
            <?= $edit_mode ? 'Edit Data Siswa' : 'Tambah Siswa Baru' ?>
        </h3>
        <p style="font-size:12px;color:var(--text-muted);margin-bottom:16px">
            <?= $edit_mode ? 'Ubah informasi siswa terpilih.' : 'Daftarkan nama siswa baru ke dalam sistem kas kelas.' ?>
        </p>

        <form action="anggota.php" method="POST" class="space-y-4">
            <input type="hidden" name="id" value="<?= e($edit_id) ?>">

            <div>
                <label for="nis" class="input-label">NIS (Nomor Induk Siswa)</label>
                <input type="text" name="nis" id="nis" class="input text-sm"
                       placeholder="Contoh: 10023 (Opsional)" value="<?= e($edit_nis) ?>">
            </div>

            <div>
                <label for="nama" class="input-label">Nama Lengkap</label>
                <input type="text" name="nama" id="nama" required class="input text-sm"
                       placeholder="Contoh: Ahmad Dhani" value="<?= e($edit_nama) ?>">
            </div>

            <div>
                <label class="input-label mb-2">Jenis Kelamin</label>
                <div class="flex gap-4">
                    <label class="inline-flex items-center text-sm font-medium" style="color:var(--text);cursor:pointer">
                        <input type="radio" name="jenis_kelamin" value="L" required <?= $edit_jenis_kelamin === 'L' || !$edit_mode ? 'checked' : '' ?>
                               class="w-4 h-4 text-indigo-600 border-slate-300 focus:ring-indigo-500 mr-2">
                        Laki-laki
                    </label>
                    <label class="inline-flex items-center text-sm font-medium" style="color:var(--text);cursor:pointer">
                        <input type="radio" name="jenis_kelamin" value="P" required <?= $edit_jenis_kelamin === 'P' ? 'checked' : '' ?>
                               class="w-4 h-4 text-indigo-600 border-slate-300 focus:ring-indigo-500 mr-2">
                        Perempuan
                    </label>
                </div>
            </div>

            <div class="pt-2 flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow btn-sm">
                    <i class="fa-solid fa-floppy-disk"></i> <?= $edit_mode ? 'Simpan Perubahan' : 'Tambah Siswa' ?>
                </button>
                <?php if ($edit_mode): ?>
                    <a href="anggota.php" class="btn btn-secondary btn-sm">Batal</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Tabel Daftar Anggota -->
    <div class="lg:col-span-2 card overflow-hidden">
        <div class="card-header flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex items-center gap-2 flex-wrap">
                <h4 style="font-size:14px;font-weight:800;color:var(--text);margin:0">Daftar Siswa</h4>
                <p style="font-size:12px;color:var(--text-muted);margin:0">Total: <strong><?= count($anggota_list) ?></strong> siswa terdaftar</p>
            </div>
            <div class="flex items-center gap-2 flex-wrap">
                <a href="anggota.php?urutan=nama" class="btn btn-sm <?= $urutan === 'nama' ? 'btn-primary' : 'btn-secondary' ?>" style="font-size:11px;padding:5px 10px">
                    <i class="fa-solid fa-sort-alpha-down"></i> Abjad
                </a>
                <a href="anggota.php?urutan=nis" class="btn btn-sm <?= $urutan === 'nis' ? 'btn-primary' : 'btn-secondary' ?>" style="font-size:11px;padding:5px 10px">
                    <i class="fa-solid fa-sort-numeric-down"></i> NIS
                </a>
            </div>
            <div class="relative w-full sm:w-56 md:w-64">
                <span style="position:absolute;top:0;bottom:0;left:0;display:flex;align-items:center;padding-left:12px;color:var(--text-muted);font-size:12px;pointer-events:none">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </span>
                <input type="text" id="searchInput"
                       class="input text-xs pl-9"
                       placeholder="Cari nama atau NIS..."
                       style="height:36px;font-size:12px">
            </div>
        </div>

        <div class="table-wrap">
            <table class="data-table" id="anggotaTable">
                <thead>
                    <tr>
                        <th class="text-center" style="width:40px">No</th>
                        <th style="width:90px">NIS</th>
                        <th>Nama Lengkap</th>
                        <th class="text-center" style="width:90px">Gender</th>
                        <th style="width:110px">Terdaftar</th>
                        <th class="text-center" style="width:85px">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($anggota_list)): ?>
                        <tr>
                            <td colspan="6" style="text-align:center;padding:48px 16px;color:var(--text-dim)">
                                <i class="fa-solid fa-users-slash" style="font-size:32px;display:block;margin-bottom:10px;color:var(--text-dim)"></i>
                                Belum ada data siswa. Tambahkan siswa melalui form di samping.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php $no = 1; foreach ($anggota_list as $member): ?>
                            <tr>
                                <td style="text-align:center;font-weight:600;color:var(--text-dim);font-size:12px"><?= $no++ ?></td>
                                <td class="font-mono text-xs" style="color:var(--text-muted)"><?= e($member['nis'] ?? '-') ?></td>
                                <td class="font-semibold"><?= e($member['nama']) ?></td>
                                <td class="text-center">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold <?= $member['jenis_kelamin'] === 'L' ? 'bg-indigo-50 text-indigo-700' : 'bg-pink-50 text-pink-700' ?>">
                                        <i class="fa-solid <?= $member['jenis_kelamin'] === 'L' ? 'fa-mars' : 'fa-venus' ?> text-[10px]"></i>
                                        <?= $member['jenis_kelamin'] === 'L' ? 'Laki-laki' : 'Perempuan' ?>
                                    </span>
                                </td>
                                <td style="font-size:12px;color:var(--text-muted)"><?= date('d/m/Y', strtotime($member['created_at'])) ?></td>
                                <td class="text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <a href="anggota.php?action=edit&id=<?= (int)$member['id'] ?>"
                                           style="width:28px;height:28px;border-radius:8px;display:inline-flex;align-items:center;justify-content:center;font-size:12px;text-decoration:none;transition:0.15s;color:var(--text-muted);background:var(--surface-bg)" onmouseenter="this.style.background='var(--tab-active-bg)';this.style.color='var(--tab-active-text)'" onmouseleave="this.style.background='var(--surface-bg)';this.style.color='var(--text-muted)'"
                                           title="Edit data">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </a>
                                        <a href="anggota.php?action=delete&id=<?= (int)$member['id'] ?>"
                                           onclick="return confirm('Apakah Anda yakin ingin menghapus siswa ini?')"
                                           style="width:28px;height:28px;border-radius:8px;display:inline-flex;align-items:center;justify-content:center;font-size:12px;text-decoration:none;transition:0.15s;color:var(--text-muted);background:var(--surface-bg)" onmouseenter="this.style.background='var(--expense-bg)';this.style.color='var(--expense)'" onmouseleave="this.style.background='var(--surface-bg)';this.style.color='var(--text-muted)'"
                                           title="Hapus data">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Live Table Filtering -->
<script>
    document.addEventListener("DOMContentLoaded", function () {
        const searchInput = document.getElementById("searchInput");
        if (searchInput) {
            searchInput.addEventListener("keyup", function () {
                const filter = searchInput.value.toLowerCase().trim();
                const table = document.getElementById("anggotaTable");
                const tr = table.getElementsByTagName("tbody")[0].getElementsByTagName("tr");
                for (let i = 0; i < tr.length; i++) {
                    const row = tr[i];
                    if (row.getElementsByTagName("td").length < 3) continue;
                    const nisTd = row.getElementsByTagName("td")[1];
                    const namaTd = row.getElementsByTagName("td")[2];
                    if (nisTd && namaTd) {
                        const nisValue = nisTd.textContent || nisTd.innerText;
                        const namaValue = namaTd.textContent || namaTd.innerText;
                        if (nisValue.toLowerCase().indexOf(filter) > -1 || namaValue.toLowerCase().indexOf(filter) > -1) {
                            row.style.display = "";
                        } else {
                            row.style.display = "none";
                        }
                    }
                }
            });
        }
    });
</script>

<?php require_once 'includes/footer.php'; ?>