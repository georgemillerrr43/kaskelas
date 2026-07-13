<?php
// anggota.php
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
                $edit_nis = $member['nis'];
                $edit_nama = $member['nama'];
                $edit_jenis_kelamin = $member['jenis_kelamin'];
            }
        } catch (PDOException $e) {
            $error = 'Gagal memuat data anggota: ' . $e->getMessage();
        }
    } elseif ($action === 'delete' && $id > 0) {
        try {
            $stmt = $pdo->prepare("DELETE FROM anggota WHERE id = ?");
            $stmt->execute([$id]);
            $success = 'Anggota berhasil dihapus!';
        } catch (PDOException $e) {
            $error = 'Gagal menghapus anggota: ' . $e->getMessage();
        }
    }
}

// Tangani input POST (Tambah & Edit)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user_role === 'admin') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $nis = trim($_POST['nis'] ?? '');
    $nama = trim($_POST['nama'] ?? '');
    $jenis_kelamin = trim($_POST['jenis_kelamin'] ?? '');

    if ($nama === '' || $jenis_kelamin === '') {
        $error = 'Nama dan Jenis Kelamin harus diisi!';
    } else {
        try {
            if ($id > 0) {
                if ($nis !== '') {
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM anggota WHERE nis = ? AND id != ?");
                    $stmt->execute([$nis, $id]);
                    if ($stmt->fetchColumn() > 0) {
                        throw new Exception('NIS sudah digunakan oleh anggota lain!');
                    }
                }
                $stmt = $pdo->prepare("UPDATE anggota SET nis = ?, nama = ?, jenis_kelamin = ? WHERE id = ?");
                $stmt->execute([$nis !== '' ? $nis : null, $nama, $jenis_kelamin, $id]);
                $success = 'Data anggota berhasil diperbarui!';
                $edit_mode = false;
            } else {
                if ($nis !== '') {
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM anggota WHERE nis = ?");
                    $stmt->execute([$nis]);
                    if ($stmt->fetchColumn() > 0) {
                        throw new Exception('NIS sudah terdaftar!');
                    }
                }
                $stmt = $pdo->prepare("INSERT INTO anggota (nis, nama, jenis_kelamin) VALUES (?, ?, ?)");
                $stmt->execute([$nis !== '' ? $nis : null, $nama, $jenis_kelamin]);
                $success = 'Anggota baru berhasil ditambahkan!';
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

// Ambil semua daftar anggota
try {
    $stmt = $pdo->query("SELECT * FROM anggota ORDER BY nama ASC");
    $anggota_list = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = 'Gagal memuat daftar anggota: ' . $e->getMessage();
    $anggota_list = [];
}
?>

<!-- Alert Notifikasi -->
<?php if ($error !== ''): ?>
    <div class="alert alert-error mb-6"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<?php if ($success !== ''): ?>
    <div class="alert alert-success mb-6"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">

    <!-- Form Tambah/Edit (Admin only) -->
    <?php if ($user_role === 'admin'): ?>
        <div class="lg:col-span-1 card p-6">
            <h3 class="font-bold text-slate-800 text-base mb-1">
                <?= $edit_mode ? 'Edit Data Anggota' : 'Tambah Anggota Baru' ?>
            </h3>
            <p class="text-xs text-slate-400 mb-5">
                <?= $edit_mode ? 'Ubah informasi anggota kelas terpilih.' : 'Daftarkan nama siswa baru ke dalam sistem kas kelas.' ?>
            </p>

            <form action="anggota.php" method="POST" class="space-y-4">
                <input type="hidden" name="id" value="<?= $edit_id ?>">

                <div>
                    <label for="nis" class="input-label">NIS (Nomor Induk Siswa)</label>
                    <input type="text" name="nis" id="nis" class="input text-sm"
                           placeholder="Contoh: 10023 (Opsional)" value="<?= htmlspecialchars($edit_nis) ?>">
                </div>

                <div>
                    <label for="nama" class="input-label">Nama Lengkap</label>
                    <input type="text" name="nama" id="nama" required class="input text-sm"
                           placeholder="Contoh: Ahmad Dhani" value="<?= htmlspecialchars($edit_nama) ?>">
                </div>

                <div>
                    <label class="input-label mb-2">Jenis Kelamin</label>
                    <div class="flex gap-4">
                        <label class="inline-flex items-center text-sm font-medium text-slate-700 cursor-pointer">
                            <input type="radio" name="jenis_kelamin" value="L" required <?= $edit_jenis_kelamin === 'L' ? 'checked' : '' ?>
                                   class="w-4 h-4 text-indigo-600 border-slate-300 focus:ring-indigo-500 mr-2">
                            Laki-laki
                        </label>
                        <label class="inline-flex items-center text-sm font-medium text-slate-700 cursor-pointer">
                            <input type="radio" name="jenis_kelamin" value="P" required <?= $edit_jenis_kelamin === 'P' ? 'checked' : '' ?>
                                   class="w-4 h-4 text-indigo-600 border-slate-300 focus:ring-indigo-500 mr-2">
                            Perempuan
                        </label>
                    </div>
                </div>

                <div class="pt-2 flex gap-2">
                    <button type="submit" class="btn btn-primary flex-grow btn-sm">
                        <i class="fa-solid fa-floppy-disk"></i> <?= $edit_mode ? 'Simpan Perubahan' : 'Tambah Anggota' ?>
                    </button>
                    <?php if ($edit_mode): ?>
                        <a href="anggota.php" class="btn btn-secondary btn-sm">Batal</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    <?php endif; ?>

    <!-- Tabel Daftar Anggota -->
    <div class="<?= $user_role === 'admin' ? 'lg:col-span-2' : 'lg:col-span-3' ?> card overflow-hidden">
        <div class="card-header flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h4 class="font-bold text-slate-800 text-base">Daftar Anggota Kelas</h4>
                <p class="text-xs text-slate-400">Total terdaftar: <strong><?= count($anggota_list) ?></strong> siswa</p>
            </div>
            <div class="relative w-full sm:w-64">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400 text-xs">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </span>
                <input type="text" id="searchInput"
                       class="input text-xs pl-9"
                       placeholder="Cari nama atau NIS...">
            </div>
        </div>

        <div class="table-wrap">
            <table class="data-table" id="anggotaTable">
                <thead>
                    <tr>
                        <th class="text-center" style="width:48px">No</th>
                        <th style="width:110px">NIS</th>
                        <th>Nama Lengkap</th>
                        <th class="text-center" style="width:110px">Gender</th>
                        <th style="width:140px">Terdaftar</th>
                        <?php if ($user_role === 'admin'): ?>
                            <th class="text-center" style="width:100px">Aksi</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($anggota_list)): ?>
                        <tr>
                            <td colspan="<?= $user_role === 'admin' ? 6 : 5 ?>" class="text-center py-12 text-slate-400">
                                <i class="fa-solid fa-users-slash text-4xl mb-3 text-slate-300 block"></i>
                                Belum ada data anggota kelas.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php $no = 1; foreach ($anggota_list as $member): ?>
                            <tr>
                                <td class="text-center font-semibold text-slate-400 text-xs"><?= $no++ ?></td>
                                <td class="font-mono text-xs text-slate-600"><?= htmlspecialchars($member['nis'] ?? '-') ?></td>
                                <td class="font-semibold"><?= htmlspecialchars($member['nama']) ?></td>
                                <td class="text-center">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold <?= $member['jenis_kelamin'] === 'L' ? 'bg-indigo-50 text-indigo-700' : 'bg-pink-50 text-pink-700' ?>">
                                        <i class="fa-solid <?= $member['jenis_kelamin'] === 'L' ? 'fa-mars' : 'fa-venus' ?> text-[10px]"></i>
                                        <?= $member['jenis_kelamin'] === 'L' ? 'Laki-laki' : 'Perempuan' ?>
                                    </span>
                                </td>
                                <td class="text-xs text-slate-400"><?= date('d/m/Y', strtotime($member['created_at'])) ?></td>
                                <?php if ($user_role === 'admin'): ?>
                                    <td class="text-center">
                                        <div class="flex items-center justify-center gap-2">
                                            <a href="anggota.php?action=edit&id=<?= $member['id'] ?>"
                                               class="w-7 h-7 rounded-lg bg-slate-100 hover:bg-indigo-50 text-slate-500 hover:text-indigo-600 flex items-center justify-center text-xs transition"
                                               title="Edit data">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </a>
                                            <a href="anggota.php?action=delete&id=<?= $member['id'] ?>"
                                               onclick="return confirm('Apakah Anda yakin ingin menghapus siswa ini? Transaksi yang terkait akan tetap tersimpan tapi tidak terikat nama siswa.')"
                                               class="w-7 h-7 rounded-lg bg-slate-100 hover:bg-rose-50 text-slate-500 hover:text-rose-600 flex items-center justify-center text-xs transition"
                                               title="Hapus data">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </a>
                                        </div>
                                    </td>
                                <?php endif; ?>
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
</stan>