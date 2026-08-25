<?php
/**
 * logout.php
 * Keluar Aplikasi — Menghapus data sesi dan redirect ke halaman beranda publik.
 */

require_once __DIR__ . '/config/helpers.php';
start_app_session();

// Hapus semua data session
$_SESSION = [];

// Hancurkan session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Hancurkan session server
session_destroy();

// Redirect ke beranda
header("Location: index.php");
exit();
