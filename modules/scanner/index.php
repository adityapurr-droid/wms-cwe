<?php
/**
 * Modul Live Barcode Scanner WMS CWE
 * Politeknik Kelapa Sawit Citra Widya Edukasi
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/auth.php';
require_once __DIR__ . '/../../helpers/alerts.php';
require_once __DIR__ . '/../../helpers/format.php';

require_login();

$page_title = 'Scanner Barcode Barang';
$current_page = 'scanner';

// Ambil beberapa sampel barang untuk kemudahan pengujian klik langsung
$stmt_sample = $pdo->query("SELECT kode_barang, barcode, nama_barang, stok FROM barang ORDER BY id ASC LIMIT 6");
$sample_barang = $stmt_sample->fetchAll();

require_once __DIR__ . '/../../layout/header.php';
require_once __DIR__ . '/../../layout/sidebar.php';
?>

<div id="content-wrapper">
    <?php require_once __DIR__ . '/../../layout/navbar.php'; ?>

    <main class="container-fluid px-4 py-4">
        <?= render_flash() ?>

        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
            <div>
                <h4 class="fw-bold mb-1 text-dark"><i class="bi bi-upc-scan text-success me-2"></i> Scanner Barcode & QR Code</h4>
                <p class="text-muted small mb-0">Pindai kode fisik barang untuk identifikasi instan, pemeriksaan stok, dan pencatatan transaksi 1 pintu.</p>
            </div>
            <div class="mt-3 mt-md-0">
                <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill">
                    <i class="bi bi-broadcast me-1"></i> Mode Scanner Terhubung
                </span>
            </div>
        </div>

        <div class="row g-4">
            <!-- Kolom Kiri: Area Pemindaian Barcode -->
            <div class="col-lg-7">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white border-bottom p-3">
                        <ul class="nav nav-pills card-header-pills" id="scannerTab" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active fw-semibold" id="camera-tab" data-bs-toggle="pill" data-bs-target="#camera-pane" type="button" role="tab">
                                    <i class="bi bi-camera-video me-1"></i> Kamera Laptop / HP
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link fw-semibold" id="usb-tab" data-bs-toggle="pill" data-bs-target="#usb-pane" type="button" role="tab">
                                    <i class="bi bi-upc me-1"></i> Scanner USB / Laser Gun
                                </button>
                            </li>
                        </ul>
                    </div>

                    <div class="card-body p-4">
                        <div class="tab-content" id="scannerTabContent">
                            <!-- Tab 1: Kamera Scanner -->
                            <div class="tab-pane fade show active" id="camera-pane" role="tabpanel">
                                <div class="text-center mb-3">
                                    <div id="reader" style="width: 100%; max-width: 480px; margin: 0 auto; border-radius: 12px; overflow: hidden; background: #000;"></div>
                                </div>
                                <div class="alert alert-light border small text-muted text-center mb-0">
                                    <i class="bi bi-info-circle text-primary me-1"></i> Arahkan kamera pada stiker Barcode / QR Code pada fisik barang. Sistem akan mendeteksi otomatis.
                                </div>
                            </div>

                            <!-- Tab 2: USB Barcode Gun Scanner -->
                            <div class="tab-pane fade" id="usb-pane" role="tabpanel">
                                <div class="p-4 text-center">
                                    <div class="rounded-circle bg-light d-inline-flex p-4 mb-3 border">
                                        <i class="bi bi-upc-scan fs-1 text-success"></i>
                                    </div>
                                    <h5 class="fw-bold">Mode USB / Bluetooth Scanner</h5>
                                    <p class="text-muted small mb-4" style="max-width: 420px; margin: 0 auto;">
                                        Gunakan alat *barcode reader gun*. Sistem akan otomatis mendeteksi kode begitu Anda menembakkan laser ke barcode barang.
                                    </p>

                                    <div class="input-group input-group-lg shadow-sm" style="max-width: 450px; margin: 0 auto;">
                                        <span class="input-group-text bg-white text-muted border-end-0"><i class="bi bi-barcode"></i></span>
                                        <input type="text" id="manualBarcodeInput" class="form-control border-start-0" placeholder="Tembak barcode atau ketik di sini..." autofocus>
                                        <button class="btn btn-success fw-bold px-4" type="button" id="btnCheckManual">
                                            Cari
                                        </button>
                                    </div>
                                    <small class="text-muted d-block mt-2 font-monospace">Tekan tombol Enter setelah memasukkan kode.</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Kolom Kanan: Hasil Pemindaian & Sampel Uji Coba -->
            <div class="col-lg-5">
                <!-- Card Detail Barang Hasil Scan -->
                <div class="card shadow-sm mb-4 border-success" id="resultCard" style="display: none;">
                    <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                        <span class="fw-bold"><i class="bi bi-check-circle-fill me-1"></i> Barang Ditemukan!</span>
                        <span class="badge bg-white text-success font-monospace" id="resBarcodeBadge">-</span>
                    </div>
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center mb-3">
                            <div class="rounded-3 bg-success-subtle text-success p-3 me-3 text-center" style="min-width: 60px;">
                                <i class="bi bi-box-seam fs-2"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold mb-1 text-dark" id="resNamaBarang">-</h5>
                                <div class="text-muted small">
                                    <span class="badge bg-light text-secondary border font-monospace me-1" id="resKodeBarang">-</span>
                                    <span class="badge bg-secondary-subtle text-secondary" id="resKategori">-</span>
                                </div>
                            </div>
                        </div>

                        <div class="p-3 bg-light rounded-3 mb-3 border">
                            <div class="row g-2">
                                <div class="col-6">
                                    <small class="text-muted d-block">Lokasi Rak:</small>
                                    <span class="fw-bold text-dark" id="resRak"><i class="bi bi-grid-3x3 me-1 text-primary"></i> -</span>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted d-block">Sisa Stok Fisik:</small>
                                    <span class="fw-bold fs-5" id="resStokText">-</span>
                                </div>
                                <div class="col-12 mt-2 pt-2 border-top">
                                    <small class="text-muted d-block">Spesifikasi:</small>
                                    <span class="small text-secondary" id="resSpesifikasi">-</span>
                                </div>
                            </div>
                        </div>

                        <!-- Tombol Aksi Instan -->
                        <div class="d-grid gap-2">
                            <div class="row g-2">
                                <div class="col-6">
                                    <a href="#" id="btnActionMasuk" class="btn btn-primary w-100 py-2 fw-semibold">
                                        <i class="bi bi-plus-circle me-1"></i> Terima Masuk
                                    </a>
                                </div>
                                <div class="col-6">
                                    <a href="#" id="btnActionKeluar" class="btn btn-outline-danger w-100 py-2 fw-semibold">
                                        <i class="bi bi-dash-circle me-1"></i> Catat Keluar
                                    </a>
                                </div>
                            </div>
                            <div class="row g-2">
                                <div class="col-6">
                                    <a href="#" id="btnActionKartu" class="btn btn-light border w-100 py-2 small fw-semibold text-secondary">
                                        <i class="bi bi-journal-text me-1"></i> Kartu Stok
                                    </a>
                                </div>
                                <div class="col-6">
                                    <a href="#" id="btnActionCetak" target="_blank" class="btn btn-light border w-100 py-2 small fw-semibold text-secondary">
                                        <i class="bi bi-printer me-1"></i> Cetak Label
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card Placeholder Jika Belum Scan -->
                <div class="card shadow-sm mb-4" id="placeholderCard">
                    <div class="card-body p-5 text-center text-muted">
                        <i class="bi bi-upc-scan fs-1 text-secondary opacity-50 mb-3 d-block"></i>
                        <h6 class="fw-bold text-dark">Menunggu Pemindaian</h6>
                        <p class="small text-muted mb-0">Hasil pembacaan barcode dan aksi operasional barang akan langsung tampil di sini.</p>
                    </div>
                </div>

                <!-- Card Uji Coba Cepat (Klik Sampel Barcode) -->
                <div class="card shadow-sm">
                    <div class="card-header bg-white py-3">
                        <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-magic text-warning me-2"></i> Uji Coba Cepat (Klik Barcode)</h6>
                        <small class="text-muted">Klik salah satu kode di bawah untuk simulasi pembacaan instan tanpa alat:</small>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            <?php foreach ($sample_barang as $sb): ?>
                                <button type="button" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-2 px-3 btn-test-code" data-code="<?= htmlspecialchars($sb['barcode']) ?>">
                                    <div>
                                        <div class="fw-semibold small text-dark"><?= htmlspecialchars($sb['nama_barang']) ?></div>
                                        <small class="text-muted font-monospace"><i class="bi bi-upc me-1"></i> <?= htmlspecialchars($sb['barcode']) ?> (<?= htmlspecialchars($sb['kode_barang']) ?>)</small>
                                    </div>
                                    <span class="badge bg-light text-dark border">Stok: <?= $sb['stok'] ?></span>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

<?php require_once __DIR__ . '/../../layout/footer.php'; ?>

<script>
(function () {
'use strict';

let html5QrcodeScanner = null;
let lastScannedCode = null;

// Fungsi AJAX Lookup Barcode ke Backend
function lookupBarcode(code) {
    if (!code || code === lastScannedCode) return;
    lastScannedCode = code;

    // Tampilkan efek loading
    Swal.fire({
        title: 'Mencari Data...',
        text: 'Memeriksa kode ' + code,
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });

    fetch('<?= BASE_URL ?>modules/scanner/ajax_lookup.php?code=' + encodeURIComponent(code))
        .then(response => response.json())
        .then(res => {
            Swal.close();
            if (res.success) {
                const item = res.data;
                document.getElementById('placeholderCard').style.display = 'none';
                const rc = document.getElementById('resultCard');
                rc.style.display = 'block';

                document.getElementById('resBarcodeBadge').textContent = item.barcode;
                document.getElementById('resNamaBarang').textContent = item.nama_barang;
                document.getElementById('resKodeBarang').textContent = item.kode_barang;
                document.getElementById('resKategori').textContent = item.nama_kategori;
                document.getElementById('resRak').innerHTML = '<i class="bi bi-grid-3x3 me-1 text-primary"></i> ' + item.kode_rak + ' (' + item.nama_rak + ')';

                let badgeClass = 'text-success';
                let statusBadge = '<span class="badge bg-success ms-2">Aman</span>';
                if (item.status_stok === 'menipis') {
                    badgeClass = 'text-warning';
                    statusBadge = '<span class="badge bg-warning text-dark ms-2">Menipis</span>';
                } else if (item.status_stok === 'habis') {
                    badgeClass = 'text-danger';
                    statusBadge = '<span class="badge bg-danger ms-2">Habis</span>';
                }

                document.getElementById('resStokText').innerHTML = `<span class="${badgeClass}">${item.stok} ${item.nama_satuan}</span> ${statusBadge}`;
                document.getElementById('resSpesifikasi').textContent = item.spesifikasi || '-';

                // Update Link Tombol
                document.getElementById('btnActionMasuk').href = '<?= BASE_URL ?>modules/barang_masuk/tambah.php?id_barang=' + item.id;
                document.getElementById('btnActionKeluar').href = '<?= BASE_URL ?>modules/barang_keluar/tambah.php?id_barang=' + item.id;
                document.getElementById('btnActionKartu').href = '<?= BASE_URL ?>modules/barang/kartu_stok.php?id=' + item.id;
                document.getElementById('btnActionCetak').href = '<?= BASE_URL ?>modules/barang/cetak_barcode.php?id=' + item.id;

                // Suara beep notifikasi sukses (Audio synthesis)
                playBeep();

                // Scroll lembut ke result card
                rc.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Tidak Ditemukan',
                    text: res.message,
                    confirmButtonColor: '#198754'
                });
            }
        })
        .catch(err => {
            Swal.fire({
                icon: 'error',
                title: 'Kesalahan Sistem',
                text: 'Gagal menghubungi server: ' + err,
                confirmButtonColor: '#198754'
            });
        })
        .finally(() => {
            setTimeout(() => { lastScannedCode = null; }, 2000);
        });
}

// Suara Beep saat Scan Berhasil
function playBeep() {
    try {
        const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        const osc = audioCtx.createOscillator();
        const gain = audioCtx.createGain();
        osc.connect(gain);
        gain.connect(audioCtx.destination);
        osc.type = 'sine';
        osc.frequency.value = 1200; // Frekuensi Beep Scanner
        gain.gain.setValueAtTime(0.1, audioCtx.currentTime);
        osc.start();
        osc.stop(audioCtx.currentTime + 0.15);
    } catch(e) {}
}

// Inisialisasi Kamera HTML5-QRCode
function initCameraScanner() {
    if (typeof Html5QrcodeScanner !== 'undefined') {
        html5QrcodeScanner = new Html5QrcodeScanner("reader", { 
            fps: 10, 
            qrbox: { width: 280, height: 180 },
            aspectRatio: 1.333333
        });
        html5QrcodeScanner.render((decodedText) => {
            lookupBarcode(decodedText);
        }, (errorMessage) => {
            // parsing error normal saat kamera scanning, diabaikan
        });
    }
}

document.addEventListener('DOMContentLoaded', function() {
    initCameraScanner();

    // Event input manual USB scanner
    const manualInput = document.getElementById('manualBarcodeInput');
    const btnCheck = document.getElementById('btnCheckManual');

    btnCheck.addEventListener('click', function() {
        lookupBarcode(manualInput.value.trim());
    });

    manualInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            lookupBarcode(manualInput.value.trim());
            manualInput.value = '';
        }
    });

    // Event tombol klik sampel barcode
    document.querySelectorAll('.btn-test-code').forEach(btn => {
        btn.addEventListener('click', function() {
            const code = this.getAttribute('data-code');
            lookupBarcode(code);
        });
    });

    // Auto-focus input saat beralih ke tab USB
    document.getElementById('usb-tab').addEventListener('shown.bs.tab', function() {
        manualInput.focus();
    });
});

// Hentikan kamera saat halaman ditinggalkan melalui navigasi PJAX
document.addEventListener('wms:before-replace', function () {
    if (html5QrcodeScanner) {
        try { html5QrcodeScanner.clear(); } catch (e) {}
        html5QrcodeScanner = null;
    }
});

})();
</script>

