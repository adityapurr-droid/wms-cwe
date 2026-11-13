<?php
/**
 * Pembatalan Transaksi Barang Keluar WMS CWE
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/auth.php';
require_once __DIR__ . '/../../helpers/alerts.php';

require_admin();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: ' . BASE_URL . 'modules/barang_keluar/index.php');
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM barang_keluar WHERE id = ?");
    $stmt->execute([$id]);
    $trx = $stmt->fetch();

    if (!$trx) {
        set_flash('error', 'Transaksi tidak ditemukan.');
        header('Location: ' . BASE_URL . 'modules/barang_keluar/index.php');
        exit;
    }

    $pdo->beginTransaction();

    // 1. Tambahkan kembali kuantitas barang ke rak gudang
    $stmt_upd = $pdo->prepare("UPDATE barang SET stok = stok + ? WHERE id = ?");
    $stmt_upd->execute([$trx['jumlah'], $trx['id_barang']]);

    // 2. Hapus catatan transaksi pengeluaran
    $stmt_del = $pdo->prepare("DELETE FROM barang_keluar WHERE id = ?");
    $stmt_del->execute([$id]);

    $pdo->commit();
    set_flash('success', "Transaksi pengeluaran '{$trx['no_transaksi']}' berhasil dibatalkan dan kuantitas barang telah dikembalikan ke stok rak gudang.");
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    set_flash('error', 'Gagal membatalkan transaksi pengeluaran: ' . $e->getMessage());
}

header('Location: ' . BASE_URL . 'modules/barang_keluar/index.php');
exit;

