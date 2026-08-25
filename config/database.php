<?php
/**
 * config/database.php
 * Konfigurasi koneksi MySQL Database, inisialisasi tabel, dan manajemen lingkungan (.env).
 */

require_once __DIR__ . '/helpers.php';

// 1. Muat file .env dari root direktori
$env_path = dirname(__DIR__) . '/.env';
load_env($env_path);

// 2. Ambil parameter konfigurasi database dari environment
$db_host = env('DB_HOST', '127.0.0.1');
$db_port = env('DB_PORT', '3306');
$db_name = env('DB_NAME', 'db_kas_kelas');
$db_user = env('DB_USER', 'root');
$db_pass = env('DB_PASS', '');

// Simpan sebagai konstanta untuk kompatibilitas
if (!defined('DB_HOST')) define('DB_HOST', $db_host);
if (!defined('DB_PORT')) define('DB_PORT', $db_port);
if (!defined('DB_NAME')) define('DB_NAME', $db_name);
if (!defined('DB_USER')) define('DB_USER', $db_user);
if (!defined('DB_PASS')) define('DB_PASS', $db_pass);

$pdo_options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
];

try {
    // 3. Coba koneksi langsung ke database yang ditentukan
    try {
        $dsn = "mysql:host={$db_host};port={$db_port};dbname={$db_name};charset=utf8mb4";
        $pdo = new PDO($dsn, $db_user, $db_pass, $pdo_options);
    } catch (PDOException $e) {
        // Jika database belum ada (Error 1049: Unknown database), coba buat secara otomatis jika user memiliki hak akses
        if ($e->getCode() == 1049 || strpos($e->getMessage(), 'Unknown database') !== false) {
            $dsn_no_db = "mysql:host={$db_host};port={$db_port};charset=utf8mb4";
            $pdo_init = new PDO($dsn_no_db, $db_user, $db_pass, $pdo_options);
            $pdo_init->exec("CREATE DATABASE IF NOT EXISTS `{$db_name}` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            unset($pdo_init);

            $dsn = "mysql:host={$db_host};port={$db_port};dbname={$db_name};charset=utf8mb4";
            $pdo = new PDO($dsn, $db_user, $db_pass, $pdo_options);
        } else {
            throw $e;
        }
    }

    // 4. Inisialisasi tabel `users` (Admin/Bendahara)
    $pdo->exec("CREATE TABLE IF NOT EXISTS `users` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `username` VARCHAR(50) NOT NULL UNIQUE,
        `password` VARCHAR(255) NOT NULL,
        `nama` VARCHAR(100) NOT NULL,
        `role` ENUM('admin') NOT NULL DEFAULT 'admin',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    // 5. Inisialisasi tabel `anggota` (Data Siswa/Anggota Kelas)
    $pdo->exec("CREATE TABLE IF NOT EXISTS `anggota` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `nis` VARCHAR(20) NULL UNIQUE,
        `nama` VARCHAR(100) NOT NULL,
        `jenis_kelamin` ENUM('L', 'P') NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    // 6. Inisialisasi tabel `transaksi` (Pemasukan, Pengeluaran, & Iuran Kas)
    $pdo->exec("CREATE TABLE IF NOT EXISTS `transaksi` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `tanggal` DATE NOT NULL,
        `jenis` ENUM('pemasukan', 'pengeluaran') NOT NULL,
        `jumlah` DECIMAL(15, 2) NOT NULL,
        `keterangan` TEXT NOT NULL,
        `bukti` VARCHAR(255) NULL COMMENT 'Path file bukti transaksi',
        `anggota_id` INT NULL,
        `minggu` TINYINT NULL COMMENT '1-5 untuk uang kas mingguan',
        `bulan` TINYINT NULL COMMENT '1-12 untuk bulan uang kas',
        `tahun` INT NULL COMMENT 'Tahun pembayaran uang kas',
        `created_by` INT NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX `idx_transaksi_tanggal` (`tanggal`),
        INDEX `idx_transaksi_kas` (`jenis`, `bulan`, `tahun`, `minggu`),
        CONSTRAINT `fk_transaksi_anggota` FOREIGN KEY (`anggota_id`) REFERENCES `anggota`(`id`) ON DELETE SET NULL,
        CONSTRAINT `fk_transaksi_user` FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    // 7. Migrasi kolom otomatis (jika database sudah ada namun belum memiliki kolom tertentu)
    $check_minggu = $pdo->query("SHOW COLUMNS FROM `transaksi` LIKE 'minggu'")->fetch();
    if (!$check_minggu) {
        $pdo->exec("ALTER TABLE `transaksi` ADD COLUMN `minggu` TINYINT NULL COMMENT '1-5 untuk kas mingguan' AFTER `anggota_id`");
    }

    $check_bukti = $pdo->query("SHOW COLUMNS FROM `transaksi` LIKE 'bukti'")->fetch();
    if (!$check_bukti) {
        $pdo->exec("ALTER TABLE `transaksi` ADD COLUMN `bukti` VARCHAR(255) NULL COMMENT 'Path file bukti transaksi' AFTER `keterangan`");
    }

    // 8. Pastikan folder uploads tersedia dan terlindungi
    $upload_dir = dirname(__DIR__) . '/assets/uploads';
    if (!is_dir($upload_dir)) {
        @mkdir($upload_dir, 0755, true);
    }
    
    // Buat .htaccess proteksi keamanan folder upload (mencegah eksekusi script PHP berbahaya)
    $htaccess_file = $upload_dir . '/.htaccess';
    if (!file_exists($htaccess_file)) {
        @file_put_contents($htaccess_file, "<FilesMatch \"\\.(php|phtml|php3|php4|php5|php7|phps)$\">\n    Require all denied\n</FilesMatch>\nOptions -ExecCGI\n");
    }

    // 9. Auto-seed akun bendahara jika tabel `users` masih kosong
    $stmt_user_count = $pdo->query("SELECT COUNT(*) FROM `users`");
    if ($stmt_user_count->fetchColumn() == 0) {
        $admin_pass = password_hash('adminpassword', PASSWORD_BCRYPT);
        $stmt_insert = $pdo->prepare("INSERT INTO `users` (`username`, `password`, `nama`, `role`) VALUES (?, ?, ?, ?)");
        $stmt_insert->execute(['admin', $admin_pass, 'Bendahara Kelas', 'admin']);
    }

} catch (PDOException $e) {
    // Tampilan error yang rapi dan informatif tanpa mengekspos kredensial secara mentah
    $error_msg = htmlspecialchars($e->getMessage());
    die("
    <!DOCTYPE html>
    <html lang='id'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Kesalahan Database — Uangkas</title>
        <style>
            body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background: #f8fafc; color: #1e293b; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; padding: 20px; box-sizing: border-box; }
            .card { background: #fff; max-width: 580px; width: 100%; border-radius: 16px; padding: 32px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); border: 1px solid #e2e8f0; }
            .icon { width: 48px; height: 48px; border-radius: 12px; background: #fee2e2; color: #dc2626; display: flex; align-items: center; justify-content: center; font-size: 24px; margin-bottom: 20px; font-weight: bold; }
            h2 { margin: 0 0 10px; font-size: 20px; color: #0f172a; }
            p { margin: 0 0 16px; color: #64748b; font-size: 14px; line-height: 1.6; }
            .code-box { background: #f1f5f9; padding: 14px; border-radius: 8px; font-family: monospace; font-size: 12px; color: #be123c; word-break: break-all; margin-bottom: 20px; border: 1px solid #e2e8f0; }
            .guide { background: #eff6ff; border-left: 4px solid #3b82f6; padding: 12px 16px; border-radius: 6px; font-size: 13px; color: #1e40af; line-height: 1.5; }
            .guide ol { margin: 6px 0 0; padding-left: 20px; }
        </style>
    </head>
    <body>
        <div class='card'>
            <div class='icon'>!</div>
            <h2>Gagal Terhubung ke Database MySQL</h2>
            <p>Aplikasi tidak dapat membuat koneksi ke server MySQL. Silakan pastikan server database sudah berjalan dan pengaturan di file <code>.env</code> sudah benar.</p>
            <div class='code-box'>{$error_msg}</div>
            <div class='guide'>
                <strong>Langkah Pengecekan di aaPanel / Server:</strong>
                <ol>
                    <li>Buka file <code>.env</code> di root direktori proyek.</li>
                    <li>Sesuaikan <code>DB_HOST</code>, <code>DB_NAME</code>, <code>DB_USER</code>, dan <code>DB_PASS</code>.</li>
                    <li>Pastikan service MySQL di aaPanel dalam status <strong>Running</strong>.</li>
                    <li>Jika database belum dibuat, buat database baru melalui menu <strong>Databases</strong> di aaPanel.</li>
                </ol>
            </div>
        </div>
    </body>
    </html>
    ");
}
