<?php
/**
 * Konfigurasi Database WMS Politeknik Kelapa Sawit CWE
 * Menggunakan PDO (PHP Data Objects) untuk keamanan dari SQL Injection
 */

$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'wms_db';
$db_port = 3306;

try {
    $dsn = "mysql:host={$db_host};port={$db_port};dbname={$db_name};charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $pdo = new PDO($dsn, $db_user, $db_pass, $options);
} catch (PDOException $e) {
    die("Koneksi Database Gagal: " . htmlspecialchars($e->getMessage()));
}

