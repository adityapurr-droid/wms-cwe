<?php
/**
 * Cetak Label Barcode & Rak Gudang WMS CWE
 * Politeknik Kelapa Sawit Citra Widya Edukasi
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/auth.php';

require_login();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    die("Barang tidak valid.");
}

$stmt = $pdo->prepare("
    SELECT b.*, k.nama_kategori, s.nama_satuan, r.nama_rak, r.kode_rak
    FROM barang b
    JOIN kategori k ON b.id_kategori = k.id
    JOIN satuan s ON b.id_satuan = s.id
    JOIN rak r ON b.id_rak = r.id
    WHERE b.id = ?
");
$stmt->execute([$id]);
$barang = $stmt->fetch();

if (!$barang) {
    die("Data barang tidak ditemukan.");
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Label Barcode - <?= htmlspecialchars($barang['kode_barang']) ?></title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&family=Libre+Barcode+128&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>

    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #f8f9fa;
            color: #212529;
            padding: 20px;
        }
        .label-container {
            width: 85mm;
            height: 52mm;
            border: 2px dashed #198754;
            border-radius: 8px;
            padding: 8px 12px;
            background: #ffffff;
            margin: 0 auto 20px auto;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            page-break-inside: avoid;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
        }
        .inst-header {
            border-bottom: 1.5px solid #0f5132;
            padding-bottom: 4px;
        }
        .barcode-svg {
            max-width: 100%;
            height: 48px;
        }
        @media print {
            body {
                background: #ffffff;
                padding: 0;
            }
            .no-print {
                display: none !important;
            }
            .label-container {
                border: 1px solid #000000;
                box-shadow: none;
                margin: 0;
            }
        }
    </style>
</head>
<body>

<div class="container text-center no-print mb-4">
    <h4 class="fw-bold text-success mb-2"><?= APP_NAME ?> - Pratinjau Cetak Label Barcode</h4>
    <p class="text-muted small">Label ini siap dicetak pada kertas stiker dan ditempelkan pada fisik barang atau rak gudang.</p>
    <div class="d-flex justify-content-center gap-2">
        <button class="btn btn-success fw-bold px-4" onclick="window.print()">
            <i class="bi bi-printer"></i> Cetak Sekarang
        </button>
        <button class="btn btn-light border px-4" onclick="window.close()">
            Tutup
        </button>
    </div>
</div>

<!-- Kartu Label Stiker Fisik -->
<div class="label-container">
    <div class="inst-header d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center">
            <img src="<?= BASE_URL ?>assets/img/logo-cwe.png" alt="Logo CWE" style="height: 28px; width: auto; object-fit: contain; margin-right: 6px;">
            <div>
                <div class="fw-bold" style="font-size: 0.62rem; color: #0f5132; text-transform: uppercase;">POLITEKNIK CITRA WIDYA EDUKASI</div>
                <div style="font-size: 0.52rem; color: #6c757d;">Gudang Terpusat 1 Pintu</div>
            </div>
        </div>
        <div class="badge bg-dark font-monospace" style="font-size: 0.65rem;"><?= htmlspecialchars($barang['kode_rak']) ?></div>
    </div>

    <div class="my-1">
        <div class="fw-bold text-dark text-truncate" style="font-size: 0.85rem;" title="<?= htmlspecialchars($barang['nama_barang']) ?>">
            <?= htmlspecialchars($barang['nama_barang']) ?>
        </div>
        <div class="d-flex justify-content-between align-items-center text-muted" style="font-size: 0.65rem;">
            <span>Kat: <?= htmlspecialchars($barang['nama_kategori']) ?></span>
            <span>Satuan: <?= htmlspecialchars($barang['nama_satuan']) ?></span>
        </div>
    </div>

    <!-- Barcode Rendering -->
    <div class="text-center my-auto">
        <svg id="barcodeTarget" class="barcode-svg"></svg>
    </div>

    <div class="d-flex justify-content-between align-items-center border-top pt-1 text-muted" style="font-size: 0.6rem;">
        <span class="font-monospace fw-bold text-dark"><?= htmlspecialchars($barang['kode_barang']) ?></span>
        <span>Lokasi: <?= htmlspecialchars($barang['nama_rak']) ?></span>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    JsBarcode("#barcodeTarget", "<?= $barang['barcode'] ?>", {
        format: "CODE128",
        width: 1.8,
        height: 38,
        displayValue: true,
        fontSize: 11,
        margin: 2
    });
});
</script>

</body>
</html>

