<?php
// config/database.php

// Database Configuration — sesuaikan dengan lingkungan Anda
// Untuk keamanan di production, gunakan environment variable atau file .env
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'db_kas_kelas');
define('DB_USER', getenv('DB_USER') ?: 'db_kas_kelas');
define('DB_PASS', getenv('DB_PASS') ?: 'mdzn3hGBPNTmYx4f');

try {
    // 1. Koneksi awal ke MySQL Server
    $pdo = new PDO("mysql:host=" . DB_HOST, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
    ]);
    
    // 2. Buat database jika belum ada
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
    $pdo->exec("USE `" . DB_NAME . "`");
    
    // 3. Buat tabel `users` (Admin dan Siswa/View Only)
    $pdo->exec("CREATE TABLE IF NOT EXISTS `users` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `username` VARCHAR(50) NOT NULL UNIQUE,
      `password` VARCHAR(255) NOT NULL,
      `nama` VARCHAR(100) NOT NULL,
      `role` ENUM('admin') NOT NULL,
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // 4. Buat tabel `anggota` (Daftar siswa di kelas)
    $pdo->exec("CREATE TABLE IF NOT EXISTS `anggota` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `nis` VARCHAR(20) NULL UNIQUE,
      `nama` VARCHAR(100) NOT NULL,
      `jenis_kelamin` ENUM('L', 'P') NOT NULL,
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // 5. Buat tabel `transaksi` (Buku kas masuk/keluar) dengan kolom `minggu`
    $pdo->exec("CREATE TABLE IF NOT EXISTS `transaksi` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `tanggal` DATE NOT NULL,
      `jenis` ENUM('pemasukan', 'pengeluaran') NOT NULL,
      `jumlah` DECIMAL(15, 2) NOT NULL,
      `keterangan` TEXT NOT NULL,
      `anggota_id` INT NULL,
      `minggu` TINYINT NULL COMMENT '1-5 untuk uang kas mingguan',
      `bulan` TINYINT NULL COMMENT '1-12 untuk bulan uang kas',
      `tahun` INT NULL COMMENT 'Tahun pembayaran uang kas',
      `created_by` INT NOT NULL,
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY (`anggota_id`) REFERENCES `anggota`(`id`) ON DELETE SET NULL,
      FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // 6. Migrasi Otomatis: Jika kolom `minggu` belum ada pada tabel `transaksi`, tambahkan dinamis!
    $check_column = $pdo->query("SHOW COLUMNS FROM `transaksi` LIKE 'minggu'")->fetch();
    if (!$check_column) {
        $pdo->exec("ALTER TABLE `transaksi` ADD COLUMN `minggu` TINYINT NULL COMMENT '1-5 untuk uang kas mingguan' AFTER `anggota_id`");
    }

    // 7. Auto-seed akun bendahara jika tabel `users` masih kosong
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    if ($stmt->fetchColumn() == 0) {
        $admin_pass = password_hash('adminpassword', PASSWORD_BCRYPT);
        $stmt_insert = $pdo->prepare("INSERT INTO users (username, password, nama, role) VALUES (?, ?, ?, ?)");
        $stmt_insert->execute(['admin', $admin_pass, 'Bendahara Kelas', 'admin']);
    }
    
    // 8. Auto-seed data anggota default jika tabel `anggota` masih kosong
    $stmt_m = $pdo->query("SELECT COUNT(*) FROM anggota");
    if ($stmt_m->fetchColumn() == 0) {
        $stmt_insert_m = $pdo->prepare("INSERT INTO anggota (nis, nama, jenis_kelamin) VALUES (?, ?, ?)");
        $stmt_insert_m->execute(['10001', 'Ahmad Dhani', 'L']);
        $stmt_insert_m->execute(['10002', 'Budi Utomo', 'L']);
        $stmt_insert_m->execute(['10003', 'Citra Lestari', 'P']);
        $stmt_insert_m->execute(['10004', 'Dewi Sartika', 'P']);
        $stmt_insert_m->execute(['10005', 'Eko Prasetyo', 'L']);
        
        // Ambil ID admin yang baru saja diinsert untuk referensi created_by
        $admin_id = $pdo->query("SELECT id FROM users WHERE username = 'admin'")->fetchColumn();
        if ($admin_id) {
            $stmt_t = $pdo->prepare("INSERT INTO transaksi (tanggal, jenis, jumlah, keterangan, anggota_id, minggu, bulan, tahun, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt_t->execute(['2026-06-01', 'pemasukan', 20000.00, 'Uang Kas Mgg 1 Juni 2026 Ahmad Dhani', 1, 1, 6, 2026, $admin_id]);
            $stmt_t->execute(['2026-06-01', 'pemasukan', 20000.00, 'Uang Kas Mgg 1 Juni 2026 Budi Utomo', 2, 1, 6, 2026, $admin_id]);
            $stmt_t->execute(['2026-06-02', 'pemasukan', 20000.00, 'Uang Kas Mgg 1 Juni 2026 Citra Lestari', 3, 1, 6, 2026, $admin_id]);
            $stmt_t->execute(['2026-06-08', 'pemasukan', 20000.00, 'Uang Kas Mgg 2 Juni 2026 Ahmad Dhani', 1, 2, 6, 2026, $admin_id]);
            $stmt_t->execute(['2026-06-09', 'pemasukan', 150000.00, 'Donasi Wali Kelas', NULL, NULL, NULL, NULL, $admin_id]);
            $stmt_t->execute(['2026-06-10', 'pengeluaran', 75000.00, 'Pembelian Sapu dan Ember Kelas', NULL, NULL, NULL, NULL, $admin_id]);
        }
    }
} catch (PDOException $e) {
    die("Kesalahan Koneksi Database: " . $e->getMessage());
}
