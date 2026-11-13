<?php
/**
 * Konfigurasi Aplikasi & Lingkungan WMS
 * Politeknik Kelapa Sawit Citra Widya Edukasi
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set zona waktu Indonesia Barat (WIB)
date_default_timezone_set('Asia/Jakarta');

// Auto-detect Base URL
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$script_name = $_SERVER['SCRIPT_NAME'] ?? '';
$dir = str_replace('\\', '/', dirname($script_name));

// Pastikan base URL menuju root folder /wms
if (preg_match('#^(.*?/wms)#i', $dir, $matches)) {
    $base_path = $matches[1];
} else {
    // Jika diakses via virtual host atau root domain
    $base_path = rtrim($dir, '/');
}

define('BASE_URL', rtrim($protocol . $host . $base_path, '/') . '/');

// Informasi Lembaga
define('APP_NAME', 'WMS CWE');
define('APP_FULL_NAME', 'Warehouse Management System');
define('INSTITUTION_NAME', 'Politeknik Kelapa Sawit Citra Widya Edukasi');
define('INSTITUTION_TAGLINE', 'Pusat Unggulan Pendidikan Vokasi Kelapa Sawit Indonesia');
define('INSTITUTION_ADDRESS', 'Jl. Gapura No. 1, Cibitung, Bekasi, Jawa Barat - 17520');
define('INSTITUTION_CONTACT', 'Telp: (021) 8832-6800 | Email: logistik.gudang@cwe.ac.id');
define('APP_VERSION', '2.0.0');

