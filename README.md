<p align="center">
  <img src="assets/images/joji.svg" alt="Uangkas Kelas" width="80" height="80">
</p>

<h1 align="center">📊 Uangkas Antigravity</h1>

<p align="center">
  <strong>Aplikasi Manajemen Kas Kelas Berbasis Web</strong>
  <br>
  Kelola iuran mingguan, pantau saldo, dan lacak transaksi keuangan kelas secara transparan & akurat.
</p>

<p align="center">
  <img src="https://img.shields.io/badge/PHP-8.1+-777BB4?style=flat&logo=php&logoColor=white" alt="PHP">
  <img src="https://img.shields.io/badge/MySQL-5.7+-4479A1?style=flat&logo=mysql&logoColor=white" alt="MySQL">
  <img src="https://img.shields.io/badge/Tailwind_CSS-3.4-06B6D4?style=flat&logo=tailwindcss&logoColor=white" alt="Tailwind CSS">
  <img src="https://img.shields.io/badge/License-MIT-green?style=flat" alt="License">
</p>

---

## ✨ Fitur

| Fitur | Keterangan |
|-------|------------|
| **Dashboard Interaktif** | Ringkasan saldo, grafik pemasukan/pengeluaran (bulanan & mingguan) |
| **Manajemen Transaksi** | Catat pemasukan & pengeluaran dengan filter berdasarkan jenis, bulan, tahun |
| **Kas Mingguan** | Lacak pembayaran iuran mingguan per siswa (Minggu 1–5) |
| **Matriks Rekap** | Tabel rekap siapa saja yang sudah bayar kas mingguan |
| **Manajemen Anggota** | CRUD data siswa dengan NIS, lengkap dengan fitur pencarian |
| **Multi Role** | Bendahara (admin) dapat mencatat & mengelola; Anggota (view-only) melihat laporan |
| **Export PDF** | Ekspor laporan transaksi ke PDF profesional dengan kop surat & tanda tangan |
| **Responsive Design** | Tampilan optimal di desktop, tablet, dan mobile |

## 🖼️ Tampilan

| Halaman | Fungsi |
|---------|--------|
| `login.php` | Autentikasi pengguna |
| `dashboard.php` | Ringkasan keuangan, grafik, & transaksi terbaru |
| `transaksi.php` | Buku riwayat transaksi dengan filter & export PDF |
| `rekap.php` | Matriks pembayaran kas mingguan per siswa |
| `anggota.php` | CRUD anggota kelas dengan pencarian |

## 🚀 Instalasi

### Persyaratan

- PHP 8.1 atau lebih baru
- MySQL 5.7+ atau MariaDB 10.3+
- Web server (Apache / Nginx / XAMPP / Laragon)

### Langkah Instalasi

1. **Clone repositori**

```bash
git clone https://github.com/joji/uangkas-antigravity.git
cd uangkas-antigravity
```

2. **Konfigurasi database**

Salin `config/database.php` lalu sesuaikan kredensial database:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'db_kas_kelas');
define('DB_USER', 'root');
define('DB_PASS', 'password_anda');
```

Atau import `database.sql` melalui phpMyAdmin / command line:

```bash
mysql -u root -p < database.sql
```

> **Catatan:** Aplikasi secara otomatis membuat database dan tabel saat pertama kali dijalankan, termasuk data contoh (5 siswa, 2 user default, 6 transaksi awal).

3. **Jalankan aplikasi**

Letakkan folder project di direktori web server (htdocs, www, atau public_html), lalu akses melalui browser:

```
http://localhost/uangkas-antigravity
```

### Akun Default

| Role | Username | Password |
|------|----------|----------|
| **Bendahara** (admin) | `admin` | `adminpassword` |
| **Anggota** (view) | `siswa` | `siswapassword` |

## 🛠️ Tech Stack

- **Frontend:** Tailwind CSS (CDN), Chart.js, jsPDF + jspdf-autotable, Font Awesome 6
- **Backend:** PHP 8+ native (no framework)
- **Database:** MySQL/MariaDB with PDO
- **Font:** Plus Jakarta Sans (Google Fonts)

## 📁 Struktur Proyek

```
uangkas-antigravity/
├── assets/
│   └── images/            # Aset gambar (logo, signature)
├── config/
│   └── database.php       # Konfigurasi & migrasi database
├── includes/
│   ├── header.php         # Layout global (sidebar, navbar, CSS)
│   └── footer.php         # Footer & JavaScript
├── login.php              # Halaman login
├── dashboard.php          # Dashboard utama
├── transaksi.php          # Riwayat transaksi
├── rekap.php              # Matriks kas mingguan
├── anggota.php            # Manajemen anggota
├── logout.php             # Logout handler
├── index.php              # Entry point (redirect)
├── database.sql           # SQL dump manual
├── .gitignore
└── README.md
```

## 🤝 Kontribusi

Kontribusi selalu diterima! Silakan buka *issue* atau kirim *pull request* untuk perbaikan atau fitur baru.

1. Fork repositori
2. Buat branch fitur (`git checkout -b fitur/fitur-keren`)
3. Commit perubahan (`git commit -m 'feat: Tambah fitur keren'`)
4. Push ke branch (`git push origin fitur/fitur-keren`)
5. Buka Pull Request

## 📄 Lisensi

Distributed under the MIT License. See `LICENSE` for more information.

## 👤 Pengembang

**Joji** — Pembuat & pengelola utama

---

<p align="center">
  <i>Dibuat dengan ❤️ untuk transparansi keuangan kelas yang lebih baik</i>
</p>
