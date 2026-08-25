<p align="center">
  <img src="assets/images/joji.svg" alt="Uangkas Kelas" width="80" height="80">
</p>

<h1 align="center">📊 Uangkas Kelas</h1>

<p align="center">
  <strong>Sistem Informasi & Manajemen Kas Kelas Berbasis Web</strong>
  <br>
  Kelola iuran mingguan, pantau saldo, dan lacak transaksi keuangan kelas secara transparan & akurat dengan MySQL.
</p>

<p align="center">
  <img src="https://img.shields.io/badge/PHP-8.1+-777BB4?style=flat&logo=php&logoColor=white" alt="PHP">
  <img src="https://img.shields.io/badge/MySQL-5.7+-4479A1?style=flat&logo=mysql&logoColor=white" alt="MySQL">
  <img src="https://img.shields.io/badge/Tailwind_CSS-3.4-06B6D4?style=flat&logo=tailwindcss&logoColor=white" alt="Tailwind CSS">
  <img src="https://img.shields.io/badge/License-MIT-green?style=flat" alt="License">
</p>

---

## ✨ Fitur Utama

| Fitur | Keterangan |
|-------|------------|
| **Dashboard Real-Time** | Ringkasan saldo, grafik pemasukan/pengeluaran bulanan & mingguan |
| **Manajemen Transaksi** | Catat pemasukan & pengeluaran kas dengan validasi anti-defisit di database |
| **Iuran Kas Mingguan** | Pencatatan iuran kas mingguan per siswa (Minggu 1–5) |
| **Matriks Rekap Kas** | Tabel matriks interaktif status pembayaran per siswa |
| **Upload Bukti Nota** | Unggah dan pratinjau foto kuitansi/nota belanja kas kelas |
| **Manajemen Anggota** | CRUD data siswa lengkap dengan NIS, pencarian instan, dan pengurutan |
| **Export PDF Resmi** | Cetak laporan transaksi & rekapitulasi ke format PDF resmi siap tanda tangan |
| **Panel Publik & Admin** | Halaman publik untuk transparansi siswa/wali murid & panel khusus bendahara |
| **Clean Architecture** | Kode bersih, aman dari SQL Injection (PDO Prepared Statements), sanitasi XSS, dan manajemen `.env` mandiri |

---

## 🚀 Panduan Deployment ke aaPanel

Untuk petunjuk lengkap dan panduan visual langkah demi langkah deployment ke server **aaPanel**, silakan baca dokumen berikut:

👉 **[TUTORIAL DEPLOY KE AAPANEL (Klik Disini)](file:///home/joji/Website/kaskelas/TUTORIAL_DEPLOY_AAPANEL.md)**

---

## 🛠️ Instalasi & Konfigurasi Cepat

### 1. Salin Konfigurasi `.env`
Salin file `.env.example` menjadi `.env`:
```bash
cp .env.example .env
```

Sesuaikan kredensial database MySQL Anda di dalam file `.env`:
```env
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=db_kas_kelas
DB_USER=root
DB_PASS=password_anda
```

### 2. Impor Database (Opsional / Otomatis)
Aplikasi sudah dilengkapi fitur **Auto-Migration** yang akan otomatis membuat tabel saat aplikasi pertama kali dijalankan. Namun Anda juga dapat mengimpor file `database.sql` secara manual melalui phpMyAdmin atau MySQL CLI:
```bash
mysql -u root -p db_kas_kelas < database.sql
```

### 3. Akun Login Default Bendahara
- **URL Login:** `http://localhost/login.php` (atau `https://domain-anda.com/login.php`)
- **Username:** `admin`
- **Password:** `adminpassword`

*(Segera ganti password default setelah login pertama kali melalui menu **Password**)*

---

## 📁 Struktur Proyek

```text
kaskelas/
├── assets/
│   ├── images/                # Aset gambar & favicon
│   └── uploads/               # Direktori penyimpanan foto bukti nota transaksi
├── config/
│   ├── database.php           # Inisialisasi PDO MySQL & migrasi tabel
│   └── helpers.php            # Fungsi helper global (.env parser, format rupiah, sanitasi)
├── includes/
│   ├── header.php             # Template header panel admin/bendahara
│   ├── header-public.php      # Template header halaman publik
│   ├── footer.php             # Template footer panel admin/bendahara
│   └── footer-public.php      # Template footer halaman publik
├── .env                       # File konfigurasi lokal/server (tidak masuk git)
├── .env.example               # Template contoh konfigurasi environment
├── .gitignore                 # Daftar file yang diabaikan git
├── anggota.php                # CRUD manajemen siswa
├── dashboard.php              # Dashboard analitik keuangan
├── database.sql               # Skema database MySQL
├── ganti-password.php         # Ubah kata sandi bendahara
├── index.php                  # Halaman beranda publik
├── login.php                  # Halaman login autentikasi bendahara
├── logout.php                 # Logout session handler
├── public-rekap.php           # Halaman rekap mingguan publik
├── public-riwayat.php         # Halaman riwayat transaksi publik
├── rekap.php                  # Matriks rekapitulasi kas bendahara
├── transaksi.php              # Riwayat & pencatatan transaksi kas
├── TUTORIAL_DEPLOY_AAPANEL.md # Panduan deployment ke aaPanel
└── README.md
```

---

## 👤 Pengembang

**Joji** — Pembuat & Pengelola Utama

---

<p align="center">
  <i>Dibuat dengan ❤️ untuk transparansi keuangan kelas yang lebih baik</i>
</p>
