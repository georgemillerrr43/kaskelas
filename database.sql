-- ==============================================================================
-- SKEMA DATABASE UANGKAS KELAS (MySQL 5.7+ / 8.0+ / MariaDB 10.3+)
-- ==============================================================================
-- Petunjuk Import di aaPanel / phpMyAdmin:
-- 1. Buat database baru di menu 'Databases' aaPanel (misal: db_kas_kelas).
-- 2. Buka phpMyAdmin atau gunakan fitur Import di aaPanel.
-- 3. Impor file `database.sql` ini ke database tersebut.
-- ==============================================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

-- ------------------------------------------------------------------------------
-- 1. Struktur Tabel `users` (Akun Administrator / Bendahara)
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `nama` VARCHAR(100) NOT NULL,
  `role` ENUM('admin') NOT NULL DEFAULT 'admin',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 2. Struktur Tabel `anggota` (Daftar Siswa / Anggota Kelas)
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `anggota` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nis` VARCHAR(20) NULL UNIQUE,
  `nama` VARCHAR(100) NOT NULL,
  `jenis_kelamin` ENUM('L', 'P') NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 3. Struktur Tabel `transaksi` (Pemasukan, Pengeluaran, & Iuran Kas Mingguan)
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `transaksi` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `tanggal` DATE NOT NULL,
  `jenis` ENUM('pemasukan', 'pengeluaran') NOT NULL,
  `jumlah` DECIMAL(15, 2) NOT NULL,
  `keterangan` TEXT NOT NULL,
  `bukti` VARCHAR(255) NULL COMMENT 'Path file foto bukti transaksi',
  `anggota_id` INT NULL,
  `minggu` TINYINT NULL COMMENT 'Nomor minggu 1-5 untuk kas mingguan',
  `bulan` TINYINT NULL COMMENT 'Nomor bulan 1-12',
  `tahun` INT NULL COMMENT 'Tahun transaksi kas',
  `created_by` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_transaksi_tanggal` (`tanggal`),
  INDEX `idx_transaksi_kas` (`jenis`, `bulan`, `tahun`, `minggu`),
  CONSTRAINT `fk_transaksi_anggota` FOREIGN KEY (`anggota_id`) REFERENCES `anggota`(`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_transaksi_user` FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 4. Data Awal (Seed Data)
-- Password default admin: 'adminpassword' (Bcrypt Hash)
-- ------------------------------------------------------------------------------
INSERT INTO `users` (`id`, `username`, `password`, `nama`, `role`) VALUES
(1, 'admin', '$2y$10$42T4B37t/86O1b1K3H8gKOhK0O8d8F2NqI8o8z9k5GzL2f1b.g.mC', 'Bendahara Kelas', 'admin')
ON DUPLICATE KEY UPDATE `id` = `id`;

SET FOREIGN_KEY_CHECKS = 1;
COMMIT;
