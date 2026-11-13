<?php
/**
 * AJAX API: Lookup Data Barang Berdasarkan Barcode atau Kode Barang
 */
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/auth.php';

if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$query = trim($_GET['code'] ?? '');

if (empty($query)) {
    echo json_encode(['success' => false, 'message' => 'Kode atau barcode tidak boleh kosong.']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT b.*, k.nama_kategori, s.nama_satuan, r.nama_rak, r.kode_rak
        FROM barang b
        JOIN kategori k ON b.id_kategori = k.id
        JOIN satuan s ON b.id_satuan = s.id
        JOIN rak r ON b.id_rak = r.id
        WHERE b.barcode = ? OR b.kode_barang = ?
        LIMIT 1
    ");
    $stmt->execute([$query, $query]);
    $item = $stmt->fetch();

    if ($item) {
        $status_stok = 'aman';
        if ($item['stok'] == 0) {
            $status_stok = 'habis';
        } elseif ($item['stok'] <= $item['stok_minimum']) {
            $status_stok = 'menipis';
        }

        echo json_encode([
            'success' => true,
            'data' => [
                'id'            => $item['id'],
                'kode_barang'   => $item['kode_barang'],
                'barcode'       => $item['barcode'],
                'nama_barang'   => $item['nama_barang'],
                'nama_kategori' => $item['nama_kategori'],
                'nama_satuan'   => $item['nama_satuan'],
                'nama_rak'      => $item['nama_rak'],
                'kode_rak'      => $item['kode_rak'],
                'stok'          => (int)$item['stok'],
                'stok_minimum'  => (int)$item['stok_minimum'],
                'status_stok'   => $status_stok,
                'spesifikasi'   => $item['spesifikasi'] ?? '-'
            ]
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => "Barang dengan barcode/kode '{$query}' tidak ditemukan di gudang."
        ]);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Kesalahan database: ' . $e->getMessage()]);
}

