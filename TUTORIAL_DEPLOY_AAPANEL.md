# 🚀 Panduan Lengkap Deploy Uangkas Kelas di aaPanel (MySQL)

Dokumen ini adalah panduan langkah demi langkah (*step-by-step*) untuk mengunggah, mengkonfigurasi, dan menjalankan aplikasi **Uangkas Kelas** di VPS/Server yang menggunakan panel kontrol **aaPanel** dengan database **MySQL / MariaDB**.

---

## 📋 Daftar Isi
1. [Prasyarat Server aaPanel](#1-prasyarat-server-aapanel)
2. [Langkah 1: Membuat Website di aaPanel](#langkah-1-membuat-website-di-aapanel)
3. [Langkah 2: Mengunggah File Source Code](#langkah-2-mengunggah-file-source-code)
4. [Langkah 3: Membuat Database MySQL](#langkah-3-membuat-database-mysql)
5. [Langkah 4: Konfigurasi File `.env`](#langkah-4-konfigurasi-file-env)
6. [Langkah 5: Mengimpor Skema Database (`database.sql`)](#langkah-5-mengimpor-skema-database-databasesql)
7. [Langkah 6: Pengaturan Hak Akses (Permissions)](#langkah-6-pengaturan-hak-akses-permissions)
8. [Langkah 7: Konfigurasi Keamanan Web Server (Nginx / Apache)](#langkah-7-konfigurasi-keamanan-web-server-nginx--apache)
9. [Langkah 8: Uji Coba Login & Ganti Password](#langkah-8-uji-coba-login--ganti-password)
10. [Panduan Troubleshooting & Tips Optimasi](#panduan-troubleshooting--tips-optimasi)

---

## 1. Prasyarat Server aaPanel

Pastikan server aaPanel Anda telah terpasang komponen berikut melalui menu **App Store** di aaPanel:
- **Web Server:** Nginx 1.20+ atau Apache 2.4+
- **PHP:** PHP 8.1, PHP 8.2, atau PHP 8.3
  - Ekstensi PHP wajib: `pdo`, `pdo_mysql`, `fileinfo`, `mbstring`, `json`, `gd` (biasanya sudah aktif secara default).
- **Database:** MySQL 5.7+, MySQL 8.0+, atau MariaDB 10.3+
- **phpMyAdmin** (opsional, untuk melihat dan mengelola tabel lewat antarmuka grafis).

---

## Langkah 1: Membuat Website di aaPanel

1. Masuk ke Dashboard aaPanel Anda (`https://IP-Server:8888`).
2. Klik menu **Website** pada sidebar sebelah kiri.
3. Klik tombol **Add site** (Tambah Situs).
4. Isi form pembuatan situs:
   - **Domain name:** Masukkan nama domain Anda (misal: `kas.sekolahku.sch.id` atau IP server jika belum ada domain).
   - **Root directory:** Biarkan default (misal: `/www/wwwroot/kas.sekolahku.sch.id`).
   - **FTP:** Kosongkan (Do not create).
   - **Database:** Anda bisa memilih *MySQL* langsung di sini atau membuatnya secara manual pada Langkah 3.
   - **PHP Version:** Pilih **PHP-81**, **PHP-82**, atau **PHP-83**.
5. Klik tombol **Submit**.

---

## Langkah 2: Mengunggah File Source Code

1. Siapkan file source code di komputer Anda (compress seluruh isi folder proyek `kaskelas` menjadi file `.zip`).
2. Di aaPanel, klik menu **Files** pada sidebar kiri.
3. Navigasikan ke folder website Anda, misalnya: `/www/wwwroot/kas.sekolahku.sch.id/`.
4. Hapus file default aaPanel (`index.html`, `404.html`) jika ada.
5. Klik tombol **Upload** di bagian atas, lalu pilih file zip proyek Anda.
6. Setelah upload selesai, klik kanan pada file zip tersebut dan pilih **Unzip** (Ekstrak ke direktori website).
7. Pastikan susunan folder di direktori root website seperti berikut:
   ```text
   /www/wwwroot/kas.sekolahku.sch.id/
   ├── assets/
   │   ├── images/
   │   └── uploads/
   ├── config/
   │   ├── database.php
   │   └── helpers.php
   ├── includes/
   │   ├── header.php
   │   ├── header-public.php
   │   ├── footer.php
   │   └── footer-public.php
   ├── .env
   ├── .env.example
   ├── .gitignore
   ├── anggota.php
   ├── dashboard.php
   ├── database.sql
   ├── ganti-password.php
   ├── index.php
   ├── login.php
   ├── logout.php
   ├── public-rekap.php
   ├── public-riwayat.php
   ├── rekap.php
   └── transaksi.php
   ```

---

## Langkah 3: Membuat Database MySQL

Jika Anda belum membuat database pada Langkah 1:
1. Klik menu **Databases** pada sidebar sebelah kiri aaPanel.
2. Klik tombol **Add database**.
3. Isi informasi database:
   - **DB Name:** `db_kas_kelas` (atau sesuai keinginan Anda).
   - **Username:** `db_kas_kelas` (atau biarkan sama dengan nama database).
   - **Password:** Masukkan password yang kuat atau klik ikon dadu untuk membuat password acak. **(Salin & catat password ini!)**
   - **Access Permission:** Pilih `Localhost (127.0.0.1)`.
   - **Character Set:** `utf8mb4`.
4. Klik **Submit**.

---

## Langkah 4: Konfigurasi File `.env`

1. Di aaPanel, buka menu **Files** > buka direktori website Anda.
2. Cari file bernama `.env`. Jika belum ada, salin file `.env.example` menjadi `.env`.
3. Klik ganda (Double-click) pada file `.env` untuk mengeditnya secara langsung di text editor bawaan aaPanel.
4. Sesuaikan nilainya dengan database yang Anda buat pada Langkah 3:
   ```env
   # ==============================================================================
   # KONFIGURASI DATABASE MYSQL
   # ==============================================================================
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_NAME=db_kas_kelas
   DB_USER=db_kas_kelas
   DB_PASS=password_database_yang_anda_catat_tadi

   # ==============================================================================
   # PENGATURAN APLIKASI
   # ==============================================================================
   APP_NAME="Uangkas Kelas"
   APP_ENV=production
   ```
5. Klik **Save** (Simpan).

---

## Langkah 5: Mengimpor Skema Database (`database.sql`)

> **Catatan:** Aplikasi telah dilengkapi fitur *Auto-Migration*, sehingga jika database masih kosong, sistem akan otomatis membuat tabel saat aplikasi pertama kali dibuka. Namun, melakukan impor manual sangat disarankan untuk memastikan seluruh indeks terpasang sempurna.

### Cara Impor via aaPanel Database Manager:
1. Buka menu **Databases** di aaPanel.
2. Pada baris database Anda (`db_kas_kelas`), klik tombol **Import**.
3. Klik tombol **Upload from local** > pilih file `database.sql` dari komputer Anda (atau pilih file `database.sql` yang sudah ada di server).
4. Klik **Import** untuk mengeksekusi script SQL.

### Atau Cara Impor via phpMyAdmin:
1. Di menu **Databases** aaPanel, klik **phpMyAdmin**.
2. Pilih database `db_kas_kelas` di sidebar kiri phpMyAdmin.
3. Klik tab **Import** di bagian atas.
4. Pilih file `database.sql` lalu klik **Go** (Kirim).

---

## Langkah 6: Pengaturan Hak Akses (Permissions)

Agar fitur upload foto bukti nota transaksi berjalan lancar dan file sistem tetap aman:
1. Di menu **Files** aaPanel, navigasikan ke folder `/assets/uploads/`.
2. Klik kanan pada folder `uploads` > pilih **Permission**.
3. Atur permission ke **755** (atau **777** jika menggunakan server dengan user permission terpisah) dan Owner ke **www:www**.
4. Centang opsi *Recurse to subdirectories* jika ada, lalu klik **Save**.
5. Pastikan kepemilikan seluruh folder proyek adalah user `www`:
   - Anda juga dapat menjalankan perintah singkat via menu **Terminal** di aaPanel:
     ```bash
     chown -R www:www /www/wwwroot/kas.sekolahku.sch.id
     chmod -R 755 /www/wwwroot/kas.sekolahku.sch.id
     chmod -R 775 /www/wwwroot/kas.sekolahku.sch.id/assets/uploads
     ```

---

## Langkah 7: Konfigurasi Keamanan Web Server (Nginx / Apache)

Sangat penting untuk mencegah orang lain mengunduh file `.env` atau mengeksekusi script PHP berbahaya di dalam folder upload.

### Jika Menggunakan Nginx:
1. Buka menu **Website** di aaPanel > klik nama website Anda.
2. Pilih tab **Config file** atau tab **URL rewrite**.
3. Tambahkan baris aturan keamanan berikut sebelum baris `access_log`:
   ```nginx
   # Blokir akses publik ke file sensitif (.env, .git, .sql)
   location ~ /\.(env|git|htaccess|sql) {
       deny all;
       return 404;
   }

   # Cegah eksekusi script PHP berbahaya di folder uploads
   location ~ ^/assets/uploads/.*\.php$ {
       deny all;
       return 403;
   }
   ```
4. Klik **Save**. Nginx akan otomatis memuat ulang konfigurasi.

### Jika Menggunakan Apache:
Aplikasi sudah otomatis membuat file proteksi `.htaccess` di dalam folder `assets/uploads/` untuk menonaktifkan mesin eksekusi PHP pada file yang diupload.

---

## Langkah 8: Uji Coba Login & Ganti Password

1. Buka browser dan akses alamat website Anda:
   - Halaman Publik: `https://kas.sekolahku.sch.id/`
   - Halaman Login: `https://kas.sekolahku.sch.id/login.php`
2. Masuk menggunakan akun bawaan default:
   - **Username:** `admin`
   - **Password:** `adminpassword`
3. Setelah berhasil masuk ke Dashboard Bendahara:
   - Klik menu **Password** pada bilah navigasi atas.
   - Masukkan password lama (`adminpassword`), lalu tentukan password baru yang aman.
   - Klik **Simpan Password**.
4. Coba lakukan penambahan 1 data siswa di menu **Anggota** dan catat 1 transaksi kas di menu **Transaksi** untuk memverifikasi bahwa koneksi database MySQL sudah berfungsi 100% normal.

---

## Panduan Troubleshooting & Tips Optimasi

### 1. Pesan "Gagal Terhubung ke Database MySQL"
- **Penyebab:** Kredensial di file `.env` tidak cocok dengan database MySQL di aaPanel.
- **Solusi:** Buka menu **Databases** di aaPanel, salin ulang nama database, username, dan password ke file `.env`. Pastikan `DB_HOST=127.0.0.1` dan `DB_PORT=3306`.

### 2. Gagal Upload Foto Bukti Nota (Ukuran Terlalu Besar)
- **Penyebab:** Batas upload file PHP bawaan server masih 2MB.
- **Solusi:**
  1. Buka menu **App Store** di aaPanel > cari versi PHP yang Anda gunakan (misal PHP 8.1) > klik **Setting**.
  2. Pilih menu **Configuration modification**.
  3. Cari dan ubah:
     ```ini
     upload_max_filesize = 10M
     post_max_size = 12M
     memory_limit = 256M
     ```
  4. Klik **Save** lalu **Restart PHP**.

### 3. Mengaktifkan SSL / HTTPS Gratis (Let's Encrypt)
1. Buka menu **Website** di aaPanel > klik nama website Anda.
2. Pilih tab **SSL** > centang **Let's Encrypt**.
3. Pilih domain Anda, lalu klik **Apply**.
4. Centang toggle **Force HTTPS** agar seluruh akses dialihkan ke koneksi aman (HTTPS).

### 4. Backup Otomatis Database Kas via Cron aaPanel
Agar data kas kelas selalu aman dari risiko kehilangan:
1. Buka menu **Cron** pada sidebar kiri aaPanel.
2. Pilih **Type of Task:** `Backup Database`.
3. Pilih database: `db_kas_kelas`.
4. Tentukan jadwal (misal: Setiap hari pukul 02:00 malam).
5. Tentukan jumlah salinan yang disimpan (misal: 7 salinan terakhir).
6. Klik **Add task**.

---

<p align="center">
  <b>Selamat! Aplikasi Uangkas Kelas Anda kini telah siap digunakan secara live dan terhubung dengan database MySQL di aaPanel.</b>
</p>
