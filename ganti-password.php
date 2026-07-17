<?php
require_once 'config/database.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

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
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ganti Password — Uangkas Kelas</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root {
            --primary: #4f46e5; --primary-light: #6366f1;
            --card-bg: #ffffff; --card-text: #1e293b; --card-sub: #64748b;
            --input-bg: #f8fafc; --input-border: #e2e8f0; --input-text: #1e293b;
            --success-bg: #f0fdf4; --success-text: #166534; --success-border: #bbf7d0;
            --error-bg: #fef2f2; --error-text: #b91c1c; --error-border: #fecaca;
        }
        [data-theme="dark"] {
            --card-bg: #1e293b; --card-text: #f1f5f9; --card-sub: #94a3b8;
            --input-bg: #0f172a; --input-border: rgba(255,255,255,0.1); --input-text: #f1f5f9;
            --success-bg: rgba(5,150,105,0.15); --success-text: #34d399; --success-border: rgba(5,150,105,0.2);
            --error-bg: rgba(225,29,72,0.15); --error-text: #fb7185; --error-border: rgba(225,29,72,0.2);
        }
        * { box-sizing: border-box; }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            margin: 0; padding: 0; -webkit-font-smoothing: antialiased;
            background: linear-gradient(-45deg, #0f172a, #1e1b4b, #312e81, #1e40af);
            background-size: 400% 400%; animation: gradient 20s ease infinite;
            min-height: 100vh; min-height: 100dvh; display: flex; align-items: center; justify-content: center; padding: 16px;
        }
        @keyframes gradient { 0%{background-position:0% 50%} 50%{background-position:100% 50%} 100%{background-position:0% 50%} }
        .card {
            width: 100%; max-width: 420px; background: var(--card-bg);
            border-radius: 20px; border: 1px solid rgba(255,255,255,0.12);
            box-shadow: 0 25px 70px rgba(0,0,0,0.35); overflow: hidden; z-index: 10;
            transition: background 0.3s ease;
        }
        .header { padding: 32px 28px 0; text-align: center; }
        .header .icon {
            width: 56px; height: 56px; border-radius: 14px;
            background: linear-gradient(135deg, var(--primary), #7c3aed); color: #fff; font-size: 22px;
            display: inline-flex; align-items: center; justify-content: center;
            box-shadow: 0 8px 24px rgba(79,70,229,0.35); margin-bottom: 12px;
        }
        .header h1 { font-size: 20px; font-weight: 800; color: var(--card-text); margin: 0; }
        .header p { font-size: 13px; color: var(--card-sub); margin: 4px 0 0; }
        .form { padding: 24px 28px 32px; }
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; font-size: 11px; font-weight: 700; color: var(--card-sub); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px; }
        .form-group input {
            width: 100%; padding: 12px 14px; border: 1.5px solid var(--input-border);
            border-radius: 12px; font-size: 14px; font-family: inherit;
            color: var(--input-text); background: var(--input-bg);
            outline: none; transition: 0.2s ease;
        }
        .form-group input:focus { border-color: var(--primary-light); box-shadow: 0 0 0 4px rgba(99,102,241,0.12); }
        .btn {
            width: 100%; padding: 13px; background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: #fff; font-size: 14px; font-weight: 700; font-family: inherit;
            border: none; border-radius: 12px; cursor: pointer; transition: 0.2s ease;
            box-shadow: 0 8px 20px rgba(79,70,229,0.3);
            display: flex; align-items: center; justify-content: center; gap: 8px;
        }
        .btn:hover { transform: translateY(-1px); box-shadow: 0 10px 28px rgba(79,70,229,0.4); }
        .alert { display: flex; align-items: center; gap: 10px; padding: 12px 16px; border-radius: 12px; font-size: 13px; margin-bottom: 16px; }
        .alert-success { background: var(--success-bg); border: 1px solid var(--success-border); color: var(--success-text); }
        .alert-error { background: var(--error-bg); border: 1px solid var(--error-border); color: var(--error-text); }
        .back-link { display: inline-flex; align-items: center; gap: 6px; color: var(--card-sub); text-decoration: none; font-size: 12px; font-weight: 600; transition: 0.15s; }
        .back-link:hover { color: var(--card-text); }
        .theme-toggle { position: fixed; top: 20px; right: 20px; z-index: 20; width: 40px; height: 40px; border-radius: 12px; background: rgba(255,255,255,0.1); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.15); color: #fff; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 16px; transition: 0.2s; }
        .theme-toggle:hover { background: rgba(255,255,255,0.2); }
    </style>
</head>
<body>
    <button class="theme-toggle" id="themeToggle"><i id="themeIcon" class="fa-solid fa-moon"></i></button>
    <div class="card">
        <div class="header">
            <div class="icon"><i class="fa-solid fa-key"></i></div>
            <h1>Ganti Password</h1>
            <p>Ubah kata sandi akun bendahara</p>
        </div>
        <div class="form">
            <a href="dashboard.php" class="back-link"><i class="fa-solid fa-arrow-left"></i> Kembali ke Dashboard</a>

            <?php if ($success): ?><div class="alert alert-success"><i class="fa-solid fa-check-circle"></i> <?= htmlspecialchars($success) ?></div><?php endif; ?>
            <?php if ($error): ?><div class="alert alert-error"><i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?></div><?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label for="password_lama">Password Lama</label>
                    <input type="password" name="password_lama" id="password_lama" required placeholder="Masukkan password saat ini" autocomplete="current-password">
                </div>
                <div class="form-group">
                    <label for="password_baru">Password Baru</label>
                    <input type="password" name="password_baru" id="password_baru" required placeholder="Minimal 6 karakter" autocomplete="new-password">
                </div>
                <div class="form-group">
                    <label for="password_konfirmasi">Konfirmasi Password Baru</label>
                    <input type="password" name="password_konfirmasi" id="password_konfirmasi" required placeholder="Ulangi password baru" autocomplete="new-password">
                </div>
                <button type="submit" class="btn"><i class="fa-solid fa-floppy-disk"></i> Simpan Password Baru</button>
            </form>
        </div>
    </div>
    <script>
        (function(){
            var h=document.documentElement,b=document.getElementById('themeToggle'),i=document.getElementById('themeIcon'),s=localStorage.getItem('theme');
            function t(t){h.setAttribute('data-theme',t);localStorage.setItem('theme',t);if(i)i.className=t==='dark'?'fa-solid fa-sun':'fa-solid fa-moon';}
            if(s)t(s);else if(window.matchMedia('(prefers-color-scheme: dark)').matches)t('dark');
            if(b)b.addEventListener('click',function(){t(h.getAttribute('data-theme')==='dark'?'light':'dark');});
        })();
    </script>
</body>
</html>
