<?php
session_start();
require_once 'config/database.php';

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $error = 'Username dan password wajib diisi!';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                // Hanya role admin (bendahara) yang boleh login
                if ($user['role'] !== 'admin') {
                    $error = 'Akses hanya untuk Bendahara. Login siswa tidak lagi didukung.';
                } else {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['nama'] = $user['nama'];
                    $_SESSION['role'] = $user['role'];
                    header("Location: dashboard.php");
                    exit();
                }
            } else {
                $error = 'Username atau password tidak cocok!';
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
    <title>Login — Uangkas Kelas</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: { fontFamily: { jakarta: ['Plus Jakarta Sans', 'sans-serif'] } } } }
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        :root {
            --primary: #4f46e5;
            --primary-light: #6366f1;
            --card-bg: #ffffff;
            --card-text: #1e293b;
            --card-sub: #64748b;
            --input-bg: #f8fafc;
            --input-border: #e2e8f0;
            --input-text: #1e293b;
            --hint-bg: #eef2ff;
            --hint-text: #4338ca;
            --error-bg: #fef2f2;
            --error-text: #b91c1c;
            --error-border: #fecaca;
        }

        [data-theme="dark"] {
            --card-bg: #1e293b;
            --card-text: #f1f5f9;
            --card-sub: #94a3b8;
            --input-bg: #0f172a;
            --input-border: rgba(255,255,255,0.1);
            --input-text: #f1f5f9;
            --hint-bg: rgba(99,102,241,0.15);
            --hint-text: #a5b4fc;
            --error-bg: rgba(225,29,72,0.15);
            --error-text: #fb7185;
            --error-border: rgba(225,29,72,0.2);
        }

        * { box-sizing: border-box; }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            margin: 0; padding: 0;
            -webkit-font-smoothing: antialiased;
        }

        .login-bg {
            min-height: 100vh; min-height: 100dvh;
            display: flex; align-items: center; justify-content: center;
            padding: 16px;
            position: relative;
            background: linear-gradient(-45deg, #0f172a, #1e1b4b, #312e81, #1e40af);
            background-size: 400% 400%;
            animation: gradient 20s ease infinite;
            overflow: hidden;
        }
        @keyframes gradient {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .login-bg::before {
            content: '';
            position: absolute; inset: 0;
            background-image: linear-gradient(rgba(255,255,255,0.03) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.03) 1px, transparent 1px);
            background-size: 60px 60px;
            pointer-events: none;
        }

        .orb {
            position: absolute; border-radius: 50%;
            filter: blur(100px); pointer-events: none; opacity: 0.35;
        }
        .orb-1 { width: 450px; height: 450px; background: var(--primary); top: -120px; right: -120px; animation: float1 14s ease-in-out infinite; }
        .orb-2 { width: 380px; height: 380px; background: #06b6d4; bottom: -100px; left: -100px; animation: float2 16s ease-in-out infinite; }
        @keyframes float1 {
            0%, 100% { transform: translate(0,0); }
            50% { transform: translate(50px,30px); }
        }
        @keyframes float2 {
            0%, 100% { transform: translate(0,0); }
            50% { transform: translate(-40px,-50px); }
        }

        .login-card {
            position: relative; width: 100%; max-width: 400px;
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            border: 1px solid rgba(255,255,255,0.12);
            box-shadow: 0 25px 70px rgba(0,0,0,0.35);
            overflow: hidden; z-index: 10;
            transition: background 0.3s ease;
        }

        .login-header {
            padding: 36px 32px 0; text-align: center;
        }
        .login-icon {
            width: 60px; height: 60px; border-radius: 16px;
            background: linear-gradient(135deg, var(--primary), #7c3aed);
            color: #fff; font-size: 24px;
            display: inline-flex; align-items: center; justify-content: center;
            box-shadow: 0 8px 24px rgba(79,70,229,0.35);
            margin-bottom: 14px;
        }
        .login-header h1 {
            font-size: 22px; font-weight: 800;
            color: var(--card-text); margin: 0; letter-spacing: -0.4px;
        }
        .login-header p {
            font-size: 13px; color: var(--card-sub); margin: 4px 0 0;
        }

        .login-form { padding: 24px 32px 32px; }

        .form-group { margin-bottom: 18px; }
        .form-group label {
            display: block; font-size: 11px; font-weight: 700;
            color: var(--card-sub); text-transform: uppercase;
            letter-spacing: 0.5px; margin-bottom: 6px;
        }
        .input-wrap { position: relative; }
        .input-wrap .icon {
            position: absolute; left: 14px; top: 50%; transform: translateY(-50%);
            color: #94a3b8; font-size: 14px; pointer-events: none;
        }
        .input-wrap input {
            width: 100%; padding: 12px 14px 12px 42px;
            border: 1.5px solid var(--input-border);
            border-radius: 12px; font-size: 14px;
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: var(--input-text);
            background: var(--input-bg);
            transition: all 0.2s ease; outline: none;
        }
        .input-wrap input::placeholder { color: #94a3b8; }
        .input-wrap input:focus {
            border-color: var(--primary-light);
            box-shadow: 0 0 0 4px rgba(99,102,241,0.12);
        }

        .input-wrap .toggle-pw {
            position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
            color: #94a3b8; background: none; border: none;
            cursor: pointer; padding: 4px; font-size: 14px;
            transition: color 0.15s;
        }
        .input-wrap .toggle-pw:hover { color: var(--primary-light); }

        .btn-login {
            width: 100%; padding: 13px;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: #fff; font-size: 14px; font-weight: 700;
            font-family: inherit; border: none; border-radius: 12px;
            cursor: pointer; transition: all 0.2s ease;
            box-shadow: 0 8px 20px rgba(79,70,229,0.3);
            display: flex; align-items: center; justify-content: center; gap: 8px;
        }
        .btn-login:hover { transform: translateY(-1px); box-shadow: 0 10px 28px rgba(79,70,229,0.4); }
        .btn-login:active { transform: translateY(0) scale(0.98); }

        .login-hint {
            margin-top: 20px; padding: 14px;
            background: var(--hint-bg);
            border: 1px solid rgba(99,102,241,0.12);
            border-radius: 12px; font-size: 12px;
            color: var(--hint-text); line-height: 1.6;
            text-align: center; transition: background 0.3s ease;
        }

        .error-alert {
            display: flex; align-items: center; gap: 10px;
            padding: 12px 16px;
            background: var(--error-bg);
            border: 1px solid var(--error-border);
            border-radius: 12px; color: var(--error-text);
            font-size: 13px; margin-bottom: 18px; transition: background 0.3s ease;
        }
        .error-alert i { font-size: 16px; flex-shrink: 0; }

        /* ── Theme Toggle ── */
        .theme-toggle {
            position: fixed; top: 20px; right: 20px; z-index: 20;
            width: 40px; height: 40px; border-radius: 12px;
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.15);
            color: #fff; cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            font-size: 16px; transition: 0.2s ease;
        }
        .theme-toggle:hover { background: rgba(255,255,255,0.2); }
        .theme-toggle:active { transform: scale(0.92); }

        /* ── Back link ── */
        .back-link {
            display: inline-flex; align-items: center; gap: 6px;
            color: rgba(255,255,255,0.6); text-decoration: none;
            font-size: 12px; font-weight: 600; margin-bottom: 16px;
            transition: 0.15s ease;
        }
        .back-link:hover { color: rgba(255,255,255,0.9); }

        @media (max-width: 480px) {
            .login-header { padding: 28px 20px 0; }
            .login-form { padding: 20px; }
            .login-header h1 { font-size: 20px; }
            .login-icon { width: 52px; height: 52px; font-size: 20px; }
            .login-card { max-width: 360px; border-radius: 16px; }
        }
    </style>
</head>
<body>
    <button class="theme-toggle" id="themeToggle" aria-label="Ganti tema">
        <i id="themeIcon" class="fa-solid fa-moon"></i>
    </button>

    <div class="login-bg">
        <div class="orb orb-1"></div>
        <div class="orb orb-2"></div>

        <div style="position:absolute;top:24px;left:24px;z-index:20">
            <a href="index.php" class="back-link"><i class="fa-solid fa-arrow-left"></i> Kembali ke Beranda</a>
        </div>

        <div class="login-card">
            <div class="login-header">
                <div class="login-icon"><i class="fa-solid fa-wallet"></i></div>
                <h1>Uangkas Kelas</h1>
                <p>Panel Bendahara — Kelola Kas dengan Cermat</p>
            </div>

            <div class="login-form">
                <?php if ($error !== ''): ?>
                    <div class="error-alert">
                        <i class="fa-solid fa-circle-exclamation"></i>
                        <span><?= htmlspecialchars($error) ?></span>
                    </div>
                <?php endif; ?>

                <form action="login.php" method="POST">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <div class="input-wrap">
                            <span class="icon"><i class="fa-solid fa-user"></i></span>
                            <input type="text" name="username" id="username" required
                                   placeholder="Masukkan username" autocomplete="username">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="input-wrap">
                            <span class="icon"><i class="fa-solid fa-lock"></i></span>
                            <input type="password" name="password" id="password" required
                                   placeholder="••••••••" autocomplete="current-password">
                            <button type="button" class="toggle-pw" id="togglePassword" tabindex="-1">
                                <i class="fa-solid fa-eye" id="eyeIcon"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn-login">
                        Masuk ke Panel
                        <i class="fa-solid fa-arrow-right" style="font-size:12px"></i>
                    </button>
                </form>

                <div class="login-hint">
                    <i class="fa-solid fa-shield-halved" style="margin-right:6px"></i>
                    <strong>Akses khusus Bendahara</strong>
                    <div style="margin-top:4px;font-size:11px;opacity:0.7">Login: <strong>admin</strong> / <strong>adminpassword</strong></div>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function() {
            const html = document.documentElement;
            const btn = document.getElementById('themeToggle');
            const icon = document.getElementById('themeIcon');
            const stored = localStorage.getItem('theme');

            function setTheme(t) {
                html.setAttribute('data-theme', t);
                localStorage.setItem('theme', t);
                if (icon) icon.className = t === 'dark' ? 'fa-solid fa-sun' : 'fa-solid fa-moon';
            }
            if (stored) setTheme(stored);
            else if (window.matchMedia('(prefers-color-scheme: dark)').matches) setTheme('dark');
            if (btn) btn.addEventListener('click', function() {
                setTheme(html.getAttribute('data-theme') === 'dark' ? 'light' : 'dark');
            });

            const togglePassword = document.querySelector('#togglePassword');
            const password = document.querySelector('#password');
            const eyeIcon = document.querySelector('#eyeIcon');
            if (togglePassword && password) {
                togglePassword.addEventListener('click', function() {
                    const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
                    password.setAttribute('type', type);
                    eyeIcon.classList.toggle('fa-eye');
                    eyeIcon.classList.toggle('fa-eye-slash');
                });
            }
        })();
    </script>
</body>
</html>
