<?php
/**
 * Pembatalan Transaksi Barang Masuk WMS CWE
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/auth.php';
require_once __DIR__ . '/../../helpers/alerts.php';

require_admin();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: ' . BASE_URL . 'modules/barang_masuk/index.php');
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM barang_masuk WHERE id = ?");
    $stmt->execute([$id]);
    $trx = $stmt->fetch();

    if (!$trx) {
        set_flash('error', 'Transaksi tidak ditemukan.');
        header('Location: ' . BASE_URL . 'modules/barang_masuk/index.php');
        exit;
    }

    // Ambil stok barang saat ini
    $stmt_b = $pdo->prepare("SELECT stok FROM barang WHERE id = ?");
    $stmt_b->execute([$trx['id_barang']]);
    $stok_sekarang = (int)$stmt_b->fetchColumn();

    if ($stok_sekarang < $trx['jumlah']) {
        set_flash('error', "Pembatalan gagal! Stok saat ini ({$stok_sekarang}) lebih sedikit dari jumlah transaksi masuk ({$trx['jumlah']}) karena sebagian stok telah dikeluarkan.");
        header('Location: ' . BASE_URL . 'modules/barang_masuk/index.php');
        exit;
    }

    $pdo->beginTransaction();

    // 1. Kurangi kembali stok di master barang
    $stmt_upd = $pdo->prepare("UPDATE barang SET stok = stok - ? WHERE id = ?");
    $stmt_upd->execute([$trx['jumlah'], $trx['id_barang']]);

    // 2. Hapus catatan transaksi
    $stmt_del = $pdo->prepare("DELETE FROM barang_masuk WHERE id = ?");
    $stmt_del->execute([$id]);

    $pdo->commit();
    set_flash('success', "Transaksi penerimaan '{$trx['no_transaksi']}' berhasil dibatalkan dan stok telah disesuaikan kembali.");
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    set_flash('error', 'Gagal membatalkan transaksi: ' . $e->getMessage());
}

header('Location: ' . BASE_URL . 'modules/barang_masuk/index.php');
exit;

