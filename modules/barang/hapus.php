<?php
/**
 * Hapus Master Barang WMS CWE
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/auth.php';
require_once __DIR__ . '/../../helpers/alerts.php';

require_admin();

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    header('Location: ' . BASE_URL . 'modules/barang/index.php');
    exit;
}

try {
    // Periksa apakah ada riwayat mutasi masuk atau keluar
    $cek_bm = $pdo->prepare("SELECT COUNT(*) FROM barang_masuk WHERE id_barang = ?");
    $cek_bm->execute([$id]);
    $count_bm = $cek_bm->fetchColumn();

    $cek_bk = $pdo->prepare("SELECT COUNT(*) FROM barang_keluar WHERE id_barang = ?");
    $cek_bk->execute([$id]);
    $count_bk = $cek_bk->fetchColumn();

    if ($count_bm > 0 || $count_bk > 0) {
        set_flash('error', "Barang tidak dapat dihapus karena sudah memiliki riwayat mutasi transaksi ({$count_bm} masuk, {$count_bk} keluar). Ini dilindungi untuk menjaga keakuratan audit inventori.");
    } else {
        $del = $pdo->prepare("DELETE FROM barang WHERE id = ?");
        $del->execute([$id]);
        set_flash('success', "Barang berhasil dihapus.");
    }
} catch (PDOException $e) {
    set_flash('error', 'Gagal menghapus barang: ' . $e->getMessage());
}

header('Location: ' . BASE_URL . 'modules/barang/index.php');
exit;

