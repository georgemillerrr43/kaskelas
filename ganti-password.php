<?php
require_once 'config/database.php';
require_once 'includes/header.php';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = trim($_POST['password_lama'] ?? '');
    $new = trim($_POST['password_baru'] ?? '');
    $confirm = trim($_POST['password_konfirmasi'] ?? '');

    if ($old === '' || $new === '' || $confirm === '') {
        $error = 'Semua field wajib diisi!';
    } elseif ($new !== $confirm) {
        $error = 'Konfirmasi password baru tidak cocok!';
    } elseif (strlen($new) < 6) {
        $error = 'Password baru minimal 6 karakter!';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch();

            if (!$user || !password_verify($old, $user['password'])) {
                $error = 'Password lama tidak sesuai!';
            } else {
                $hash = password_hash($new, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$hash, $_SESSION['user_id']]);
                $success = 'Password berhasil diubah!';
            }
        } catch (PDOException $e) {
            $error = 'Kesalahan sistem: ' . $e->getMessage();
        }
    }
}
?>
<div style="max-width:480px;margin:0 auto">
    <div class="card p-6">
        <div style="text-align:center;margin-bottom:24px">
            <div style="width:56px;height:56px;border-radius:14px;background:linear-gradient(135deg,var(--primary-500),#8b5cf6);color:#fff;display:inline-flex;align-items:center;justify-content:center;font-size:22px;margin-bottom:12px;box-shadow:0 8px 24px rgba(99,102,241,0.3)">
                <i class="fa-solid fa-key"></i>
            </div>
            <h3 style="margin:0;font-size:18px;font-weight:800;color:var(--text)">Ganti Password</h3>
            <p style="margin:4px 0 0;font-size:13px;color:var(--text-muted)">Ubah kata sandi akun bendahara</p>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success mb-6"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error mb-6"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" style="display:flex;flex-direction:column;gap:16px">
            <div>
                <label class="input-label">Password Lama</label>
                <div style="position:relative">
                    <input type="password" name="password_lama" id="pwLama" required class="input" placeholder="Password saat ini" style="padding-right:40px">
                    <button type="button" onclick="togglePW('pwLama',this)" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--text-muted);cursor:pointer;padding:4px;font-size:14px">
                        <i class="fa-solid fa-eye"></i>
                    </button>
                </div>
            </div>
            <div>
                <label class="input-label">Password Baru</label>
                <div style="position:relative">
                    <input type="password" name="password_baru" id="pwBaru" required class="input" placeholder="Minimal 6 karakter" style="padding-right:40px">
                    <button type="button" onclick="togglePW('pwBaru',this)" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--text-muted);cursor:pointer;padding:4px;font-size:14px">
                        <i class="fa-solid fa-eye"></i>
                    </button>
                </div>
            </div>
            <div>
                <label class="input-label">Konfirmasi Password Baru</label>
                <div style="position:relative">
                    <input type="password" name="password_konfirmasi" id="pwKonfirm" required class="input" placeholder="Ulangi password baru" style="padding-right:40px">
                    <button type="button" onclick="togglePW('pwKonfirm',this)" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--text-muted);cursor:pointer;padding:4px;font-size:14px">
                        <i class="fa-solid fa-eye"></i>
                    </button>
                </div>
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%">
                <i class="fa-solid fa-floppy-disk"></i> Simpan Password
            </button>
        </form>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
<script>
function togglePW(id, btn) {
    var inp = document.getElementById(id);
    var icon = btn.querySelector('i');
    if (inp.type === 'password') {
        inp.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        inp.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}
</script>
