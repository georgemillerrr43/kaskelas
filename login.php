<?php
// login.php
session_start();
require_once 'config/database.php';

// Jika sudah login, langsung alihkan ke dashboard
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
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['nama'] = $user['nama'];
                $_SESSION['role'] = $user['role'];

                header("Location: dashboard.php");
                exit();
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
    <title>Login - Uangkas Kelas</title>
    <!-- Tailwind CSS via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        jakarta: ['Plus Jakarta Sans', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Font Awesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        * { box-sizing: border-box; }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            margin: 0;
            padding: 0;
        }

        /* Animated gradient background */
        .login-bg {
            min-height: 100vh;
            min-height: 100dvh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px;
            position: relative;
            background: linear-gradient(-45deg, #1e1b4b, #312e81, #3730a3, #1e40af, #0e7490);
            background-size: 400% 400%;
            animation: gradientShift 18s ease infinite;
            overflow: hidden;
        }

        @keyframes gradientShift {
            0%   { background-position: 0% 50%; }
            25%  { background-position: 100% 0%; }
            50%  { background-position: 100% 100%; }
            75%  { background-position: 0% 100%; }
            100% { background-position: 0% 50%; }
        }

        /* Decorative grid overlay */
        .login-bg::before {
            content: '';
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(rgba(255,255,255,0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,0.03) 1px, transparent 1px);
            background-size: 60px 60px;
            pointer-events: none;
        }

        /* Floating orbs */
        .orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            pointer-events: none;
            opacity: 0.4;
        }
        .orb-1 {
            width: 400px; height: 400px;
            background: #6366f1;
            top: -100px; right: -100px;
            animation: float1 12s ease-in-out infinite;
        }
        .orb-2 {
            width: 350px; height: 350px;
            background: #06b6d4;
            bottom: -80px; left: -80px;
            animation: float2 15s ease-in-out infinite;
        }
        .orb-3 {
            width: 200px; height: 200px;
            background: #a78bfa;
            top: 50%; left: 50%;
            animation: float3 10s ease-in-out infinite;
        }

        @keyframes float1 {
            0%, 100% { transform: translate(0, 0); }
            50% { transform: translate(40px, 30px); }
        }
        @keyframes float2 {
            0%, 100% { transform: translate(0, 0); }
            50% { transform: translate(-30px, -40px); }
        }
        @keyframes float3 {
            0%, 100% { transform: translate(-50%, -50%) scale(1); }
            50% { transform: translate(-50%, -50%) scale(1.3); }
        }

        /* Glass card */
        .login-card {
            position: relative;
            width: 100%;
            max-width: 420px;
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.35);
            box-shadow:
                0 20px 60px rgba(0,0,0,0.25),
                0 0 0 1px rgba(255,255,255,0.1);
            overflow: hidden;
            z-index: 10;
        }

        /* Subtle glass shine */
        .login-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.8), transparent);
        }

        .login-header {
            padding: 40px 36px 0;
            text-align: center;
        }
        .login-icon {
            width: 64px; height: 64px;
            border-radius: 18px;
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            color: #fff;
            font-size: 26px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 8px 24px rgba(79,70,229,0.35);
            margin-bottom: 16px;
        }
        .login-header h1 {
            font-size: 24px;
            font-weight: 800;
            color: #1e293b;
            margin: 0;
            letter-spacing: -0.5px;
        }
        .login-header p {
            font-size: 14px;
            color: #94a3b8;
            margin: 6px 0 0;
        }

        .login-form {
            padding: 28px 36px 36px;
        }

        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            font-size: 12px;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }
        .input-wrap {
            position: relative;
        }
        .input-wrap .icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 14px;
            pointer-events: none;
        }
        .input-wrap input {
            width: 100%;
            padding: 12px 14px 12px 42px;
            border: 1.5px solid #e2e8f0;
            border-radius: 12px;
            font-size: 14px;
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: #1e293b;
            background: rgba(248,250,252,0.8);
            transition: all 0.2s ease;
            outline: none;
        }
        .input-wrap input::placeholder {
            color: #cbd5e1;
        }
        .input-wrap input:focus {
            border-color: #6366f1;
            background: #fff;
            box-shadow: 0 0 0 4px rgba(99,102,241,0.1);
        }

        .input-wrap .toggle-pw {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            background: none;
            border: none;
            cursor: pointer;
            padding: 4px;
            font-size: 14px;
            transition: color 0.15s;
        }
        .input-wrap .toggle-pw:hover { color: #4f46e5; }

        .btn-login {
            width: 100%;
            padding: 13px;
            background: linear-gradient(135deg, #4f46e5, #6366f1);
            color: #fff;
            font-size: 14px;
            font-weight: 700;
            font-family: 'Plus Jakarta Sans', sans-serif;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 8px 20px rgba(79,70,229,0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .btn-login:hover {
            transform: translateY(-1px);
            box-shadow: 0 10px 28px rgba(79,70,229,0.4);
        }
        .btn-login:active {
            transform: translateY(0) scale(0.98);
        }

        .login-hint {
            margin-top: 24px;
            padding: 16px;
            background: rgba(238,242,255,0.7);
            border: 1px solid rgba(99,102,241,0.15);
            border-radius: 12px;
            font-size: 12px;
            color: #4338ca;
            line-height: 1.6;
        }
        .login-hint strong {
            font-weight: 700;
        }
        .login-hint .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2px 16px;
            margin-top: 6px;
        }

        .error-alert {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 16px;
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 12px;
            color: #b91c1c;
            font-size: 13px;
            margin-bottom: 20px;
        }
        .error-alert i {
            font-size: 16px;
            flex-shrink: 0;
        }

        @media (max-width: 480px) {
            .login-header { padding: 28px 20px 0; }
            .login-form { padding: 20px; }
            .login-header h1 { font-size: 20px; }
            .login-header p { font-size: 12px; }
            .login-icon { width: 52px; height: 52px; font-size: 22px; }
            .login-card { max-width: 360px; border-radius: 16px; }
            .login-hint .grid { grid-template-columns: 1fr; gap: 4px; }
            .input-wrap input { padding: 11px 14px 11px 38px; font-size: 13px; }
            .btn-login { padding: 14px; }
        }

        @media (max-width: 360px) {
            .login-header { padding: 24px 16px 0; }
            .login-form { padding: 16px; }
        }
    </style>
</head>
<body>
    <div class="login-bg">
        <!-- Floating orbs -->
        <div class="orb orb-1"></div>
        <div class="orb orb-2"></div>
        <div class="orb orb-3"></div>

        <!-- Login Card -->
        <div class="login-card">
            <div class="login-header">
                <div class="login-icon"><i class="fa-solid fa-wallet"></i></div>
                <h1>Uangkas Kelas</h1>
                <p>Aplikasi Keuangan Kas Kelas yang Aman &amp; Profesional</p>
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
                                   placeholder="Masukkan username"
                                   autocomplete="username">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="input-wrap">
                            <span class="icon"><i class="fa-solid fa-lock"></i></span>
                            <input type="password" name="password" id="password" required
                                   placeholder="••••••••"
                                   autocomplete="current-password">
                            <button type="button" class="toggle-pw" id="togglePassword" tabindex="-1">
                                <i class="fa-solid fa-eye" id="eyeIcon"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn-login">
                        Masuk ke Sistem
                        <i class="fa-solid fa-arrow-right" style="font-size:12px"></i>
                    </button>
                </form>

                <div class="login-hint" style="text-align:center">
                    <i class="fa-solid fa-shield-halved" style="margin-right:6px;color:var(--primary-400)"></i>
                    <strong>Akses khusus Bendahara Kelas</strong>
                    <div style="margin-top:8px;font-size:11px;color:var(--text-muted)">Login: <strong>admin</strong> / <strong>adminpassword</strong></div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const togglePassword = document.querySelector('#togglePassword');
        const password = document.querySelector('#password');
        const eyeIcon = document.querySelector('#eyeIcon');

        if (togglePassword && password) {
            togglePassword.addEventListener('click', function () {
                const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
                password.setAttribute('type', type);
                eyeIcon.classList.toggle('fa-eye');
                eyeIcon.classList.toggle('fa-eye-slash');
            });
        }
    </script>
</body>
</html>