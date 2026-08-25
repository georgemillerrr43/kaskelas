<?php
/**
 * config/helpers.php
 * Fungsi-fungsi pembantu (Helper Functions) global untuk aplikasi Uangkas Kelas.
 */

if (!function_exists('start_app_session')) {
    /**
     * Memulai session PHP dengan nama session yang unik dan terisolasi per folder website.
     * Mencegah konflik login session jika ada 2 atau lebih website di server yang sama.
     */
    function start_app_session() {
        if (session_status() === PHP_SESSION_NONE) {
            $sess_name = 'UANGKAS_' . substr(md5(dirname(__DIR__)), 0, 8);
            @session_name($sess_name);
            @session_start();
        }
    }
}

if (!function_exists('load_env')) {
    /**
     * Memuat file .env mandiri tanpa dependensi composer (Pure PHP).
     * Selalu membaca langsung file .env lokal dari direktori proyek masing-masing
     * sehingga aman digunakan untuk multi-website di satu server yang sama.
     *
     * @param string $path Path absolut atau relatif ke file .env
     * @return array Array asosiatif berisi pasangan key => value dari .env
     */
    function load_env($path) {
        if (!file_exists($path) || !is_readable($path)) {
            return [];
        }

        $vars = [];
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            
            // Abaikan baris komentar atau baris kosong
            if ($line === '' || strpos($line, '#') === 0 || strpos($line, '//') === 0) {
                continue;
            }

            // Pisahkan key dan value berdasarkan tanda sama dengan pertama (=)
            $parts = explode('=', $line, 2);
            if (count($parts) === 2) {
                $key = trim($parts[0]);
                $value = trim($parts[1]);

                // Bersihkan tanda kutip ganda atau tunggal di awal dan akhir value jika ada
                if (
                    (str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                    (str_starts_with($value, "'") && str_ends_with($value, "'"))
                ) {
                    $value = substr($value, 1, -1);
                } else {
                    // Hapus inline comment jika ada
                    $comment_pos = strpos($value, '#');
                    if ($comment_pos !== false) {
                        $value = trim(substr($value, 0, $comment_pos));
                    }
                }

                $vars[$key] = $value;
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
                if (function_exists('putenv')) {
                    @putenv(sprintf('%s=%s', $key, $value));
                }
            }
        }

        return $vars;
    }
}

if (!function_exists('env')) {
    /**
     * Mengambil nilai environment variable dengan fallback nilai default.
     * Aman dari disable_functions bawaan server / aaPanel.
     *
     * @param string $key Nama environment variable
     * @param mixed $default Nilai default jika tidak ditemukan
     * @return mixed
     */
    function env($key, $default = null) {
        $val = $_ENV[$key] ?? $_SERVER[$key] ?? null;

        if ($val === null && function_exists('getenv')) {
            $get_val = @getenv($key);
            if ($get_val !== false) {
                $val = $get_val;
            }
        }

        if ($val === null) {
            $val = $default;
        }

        if ($val === 'true' || $val === '(true)') return true;
        if ($val === 'false' || $val === '(false)') return false;
        if ($val === 'empty' || $val === '(empty)') return '';
        if ($val === 'null' || $val === '(null)') return null;

        return $val;
    }
}

if (!function_exists('format_rupiah')) {
    /**
     * Memformat angka nominal menjadi format mata uang Rupiah.
     *
     * @param float|int $angka
     * @param bool $pakai_prefix Menggunakan prefix 'Rp ' atau tidak
     * @return string
     */
    function format_rupiah($angka, $pakai_prefix = true) {
        $nominal = number_format((float)$angka, 0, ',', '.');
        return $pakai_prefix ? 'Rp ' . $nominal : $nominal;
    }
}

if (!function_exists('nama_bulan')) {
    /**
     * Mengembalikan daftar semua nama bulan atau nama bulan tertentu dalam bahasa Indonesia.
     *
     * @param int|null $bulan_ke Nomor bulan 1-12 (opsional)
     * @return array|string
     */
    function nama_bulan($bulan_ke = null) {
        $daftar = [
            1  => 'Januari',
            2  => 'Februari',
            3  => 'Maret',
            4  => 'April',
            5  => 'Mei',
            6  => 'Juni',
            7  => 'Juli',
            8  => 'Agustus',
            9  => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember'
        ];

        if ($bulan_ke !== null) {
            $bulan_ke = (int)$bulan_ke;
            return $daftar[$bulan_ke] ?? '';
        }

        return $daftar;
    }
}

if (!function_exists('e')) {
    /**
     * Melakukan sanitasi string untuk mencegah celah XSS pada output HTML.
     *
     * @param string|null $string
     * @return string
     */
    function e($string) {
        return htmlspecialchars((string)$string, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('get_ringkasan_kas')) {
    /**
     * Menghitung total pemasukan, total pengeluaran, dan saldo kas aktual dari database.
     *
     * @param PDO $pdo
     * @return array ['pemasukan' => float, 'pengeluaran' => float, 'saldo' => float, 'total_anggota' => int]
     */
    function get_ringkasan_kas($pdo) {
        try {
            $stmt = $pdo->query("
                SELECT 
                    COALESCE(SUM(CASE WHEN jenis = 'pemasukan' THEN jumlah ELSE 0 END), 0) AS total_pemasukan,
                    COALESCE(SUM(CASE WHEN jenis = 'pengeluaran' THEN jumlah ELSE 0 END), 0) AS total_pengeluaran
                FROM transaksi
            ");
            $row = $stmt->fetch();
            $pemasukan = (float)($row['total_pemasukan'] ?? 0);
            $pengeluaran = (float)($row['total_pengeluaran'] ?? 0);
            $saldo = $pemasukan - $pengeluaran;

            $stmt_m = $pdo->query("SELECT COUNT(*) AS total FROM anggota");
            $total_anggota = (int)($stmt_m->fetch()['total'] ?? 0);

            return [
                'pemasukan'     => $pemasukan,
                'pengeluaran'   => $pengeluaran,
                'saldo'         => $saldo,
                'total_anggota' => $total_anggota
            ];
        } catch (PDOException $e) {
            return [
                'pemasukan'     => 0,
                'pengeluaran'   => 0,
                'saldo'         => 0,
                'total_anggota' => 0
            ];
        }
    }
}
