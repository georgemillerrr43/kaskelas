-- SQL Script for database initialization (Weekly Dues Upgrade)
CREATE DATABASE IF NOT EXISTS `db_kas_kelas` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `db_kas_kelas`;

-- Table structure for `users` (Admin/Bendahara & Anggota View Only)
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `nama` VARCHAR(100) NOT NULL,
  `role` ENUM('admin', 'anggota') NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table structure for `anggota` (List of students in the class)
CREATE TABLE IF NOT EXISTS `anggota` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nis` VARCHAR(20) NULL UNIQUE,
  `nama` VARCHAR(100) NOT NULL,
  `jenis_kelamin` ENUM('L', 'P') NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table structure for `transaksi` (Class transactions ledger)
CREATE TABLE IF NOT EXISTS `transaksi` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `tanggal` DATE NOT NULL,
  `jenis` ENUM('pemasukan', 'pengeluaran') NOT NULL,
  `jumlah` DECIMAL(15, 2) NOT NULL,
  `keterangan` TEXT NOT NULL,
  `anggota_id` INT NULL,
  `minggu` TINYINT NULL COMMENT '1-5 for weekly dues',
  `bulan` TINYINT NULL COMMENT '1-12 for month of dues',
  `tahun` INT NULL COMMENT 'Year of dues',
  `created_by` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`anggota_id`) REFERENCES `anggota`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default members (sample data)
INSERT INTO `anggota` (`id`, `nis`, `nama`, `jenis_kelamin`) VALUES
(1, '10001', 'Ahmad Dhani', 'L'),
(2, '10002', 'Budi Utomo', 'L'),
(3, '10003', 'Citra Lestari', 'P'),
(4, '10004', 'Dewi Sartika', 'P'),
(5, '10005', 'Eko Prasetyo', 'L');

-- Seed users if needed (we also auto-seed in PHP code for safety)
-- Default username: admin (password: adminpassword)
-- Default username: siswa (password: siswapassword)
INSERT INTO `users` (`id`, `username`, `password`, `nama`, `role`) VALUES
(1, 'admin', '$2y$10$g1kZtP1h/g236l92rQ1XvOy5BvC6Pexm/B9t/4.y77yK7W5VzYg6G', 'Bendahara Kelas', 'admin'),
(2, 'siswa', '$2y$10$wN1G7lK6Gvj1J3gY5VvXoOtR7d3vK7s2d5K6h7J/yG7gVzW5VzYg6', 'Anggota Kelas', 'anggota')
ON DUPLICATE KEY UPDATE `id`=`id`;

-- Insert some sample weekly transactions
-- Pemasukan (initial weekly dues payments)
INSERT INTO `transaksi` (`id`, `tanggal`, `jenis`, `jumlah`, `keterangan`, `anggota_id`, `minggu`, `bulan`, `tahun`, `created_by`) VALUES
(1, '2026-06-01', 'pemasukan', 20000.00, 'Uang Kas Mgg 1 Juni 2026 Ahmad Dhani', 1, 1, 6, 2026, 1),
(2, '2026-06-01', 'pemasukan', 20000.00, 'Uang Kas Mgg 1 Juni 2026 Budi Utomo', 2, 1, 6, 2026, 1),
(3, '2026-06-02', 'pemasukan', 20000.00, 'Uang Kas Mgg 1 Juni 2026 Citra Lestari', 3, 1, 6, 2026, 1),
(4, '2026-06-08', 'pemasukan', 20000.00, 'Uang Kas Mgg 2 Juni 2026 Ahmad Dhani', 1, 2, 6, 2026, 1),
(5, '2026-06-09', 'pemasukan', 150000.00, 'Donasi Wali Kelas', NULL, NULL, NULL, NULL, 1),
-- Pengeluaran
(6, '2026-06-10', 'pengeluaran', 75000.00, 'Pembelian Sapu dan Ember Kelas', NULL, NULL, NULL, NULL, 1);
