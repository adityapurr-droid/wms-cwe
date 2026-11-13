<?php
/**
 * Ekspor Data Laporan ke File Excel WMS CWE
 * Politeknik Kelapa Sawit Citra Widya Edukasi
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/auth.php';

require_login();

$jenis_laporan = $_GET['jenis'] ?? 'semua';
$tgl_mulai = $_GET['tgl_mulai'] ?? date('Y-m-01');
$tgl_selesai = $_GET['tgl_selesai'] ?? date('Y-m-d');
$filter_kategori = (int)($_GET['kategori'] ?? 0);
$filter_rak = (int)($_GET['rak'] ?? 0);

$filename = "Laporan_WMS_CWE_" . date('Ymd_His') . ".xls";

header('Content-Type: application/vnd.ms-excel; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// Output UTF-8 BOM untuk Excel
echo "\xEF\xBB\xBF";

$output = fopen('php://output', 'w');

if ($jenis_laporan === 'stok') {
    // Header Excel Stok
    fputcsv($output, ['No', 'Kode Barang', 'Barcode', 'Nama Barang', 'Kategori', 'Satuan', 'Lokasi Rak', 'Stok Fisik', 'Stok Minimum', 'Status', 'Spesifikasi']);

    $sql = "
        SELECT b.*, k.nama_kategori, s.nama_satuan, r.kode_rak, r.nama_rak
        FROM barang b
        JOIN kategori k ON b.id_kategori = k.id
        JOIN satuan s ON b.id_satuan = s.id
        JOIN rak r ON b.id_rak = r.id
        WHERE 1=1
    ";
    $p = [];
    if ($filter_kategori > 0) {
        $sql .= " AND b.id_kategori = ?";
        $p[] = $filter_kategori;
    }
    if ($filter_rak > 0) {
        $sql .= " AND b.id_rak = ?";
        $p[] = $filter_rak;
    }
    $sql .= " ORDER BY b.id_rak ASC, b.nama_barang ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($p);

    $no = 1;
    while ($row = $stmt->fetch()) {
        $status = ($row['stok'] == 0) ? 'Habis' : (($row['stok'] <= $row['stok_minimum']) ? 'Menipis' : 'Aman');
        fputcsv($output, [
            $no++,
            $row['kode_barang'],
            "'" . $row['barcode'], // Kutip agar barcode panjang tidak jadi scientific notation
            $row['nama_barang'],
            $row['nama_kategori'],
            $row['nama_satuan'],
            $row['kode_rak'] . ' (' . $row['nama_rak'] . ')',
            $row['stok'],
            $row['stok_minimum'],
            $status,
            $row['spesifikasi'] ?? ''
        ]);
    }
} else {
    // Header Excel Mutasi
    fputcsv($output, ['No', 'Tanggal', 'No. Transaksi', 'Tipe', 'Kode Barang', 'Nama Barang', 'Satuan', 'Lokasi Rak', 'Jumlah', 'Pihak Terkait (Supplier/Unit)', 'Referensi / No SJ / Peruntukan', 'Keterangan', 'Petugas']);

    $list_in = [];
    $list_out = [];

    if ($jenis_laporan === 'semua' || $jenis_laporan === 'masuk') {
        $sql_in = "
            SELECT 
                bm.tanggal_masuk as tanggal,
                bm.created_at,
                bm.no_transaksi,
                'MASUK' as tipe,
                b.kode_barang, b.nama_barang,
                s.nama_satuan,
                r.kode_rak,
                bm.jumlah,
                COALESCE(sup.nama_supplier, 'Penerimaan Langsung') as pihak_terkait,
                bm.no_surat_jalan as ref_dokumen,
                bm.keterangan,
                u.nama as nama_petugas
            FROM barang_masuk bm
            JOIN barang b ON bm.id_barang = b.id
            JOIN satuan s ON b.id_satuan = s.id
            JOIN rak r ON b.id_rak = r.id
            LEFT JOIN supplier sup ON bm.id_supplier = sup.id
            JOIN users u ON bm.user_id = u.id
            WHERE bm.tanggal_masuk BETWEEN ? AND ?
        ";
        $p_in = [$tgl_mulai, $tgl_selesai];
        if ($filter_kategori > 0) {
            $sql_in .= " AND b.id_kategori = ?";
            $p_in[] = $filter_kategori;
        }
        if ($filter_rak > 0) {
            $sql_in .= " AND b.id_rak = ?";
            $p_in[] = $filter_rak;
        }
        $stmt_in = $pdo->prepare($sql_in);
        $stmt_in->execute($p_in);
        $list_in = $stmt_in->fetchAll();
    }

    if ($jenis_laporan === 'semua' || $jenis_laporan === 'keluar') {
        $sql_out = "
            SELECT 
                bk.tanggal_keluar as tanggal,
                bk.created_at,
                bk.no_transaksi,
                'KELUAR' as tipe,
                b.kode_barang, b.nama_barang,
                s.nama_satuan,
                r.kode_rak,
                bk.jumlah,
                COALESCE(dept.nama_departemen, 'Unit Kampus') as pihak_terkait,
                bk.peruntukan as ref_dokumen,
                bk.keterangan,
                u.nama as nama_petugas
            FROM barang_keluar bk
            JOIN barang b ON bk.id_barang = b.id
            JOIN satuan s ON b.id_satuan = s.id
            JOIN rak r ON b.id_rak = r.id
            LEFT JOIN departemen dept ON bk.id_departemen = dept.id
            JOIN users u ON bk.user_id = u.id
            WHERE bk.tanggal_keluar BETWEEN ? AND ?
        ";
        $p_out = [$tgl_mulai, $tgl_selesai];
        if ($filter_kategori > 0) {
            $sql_out .= " AND b.id_kategori = ?";
            $p_out[] = $filter_kategori;
        }
        if ($filter_rak > 0) {
            $sql_out .= " AND b.id_rak = ?";
            $p_out[] = $filter_rak;
        }
        $stmt_out = $pdo->prepare($sql_out);
        $stmt_out->execute($p_out);
        $list_out = $stmt_out->fetchAll();
    }

    $data = array_merge($list_in, $list_out);
    usort($data, function($a, $b) {
        $t1 = strtotime($a['tanggal'] . ' ' . date('H:i:s', strtotime($a['created_at'])));
        $t2 = strtotime($b['tanggal'] . ' ' . date('H:i:s', strtotime($b['created_at'])));
        return $t1 <=> $t2;
    });

    $no = 1;
    foreach ($data as $r) {
        fputcsv($output, [
            $no++,
            $r['tanggal'],
            $r['no_transaksi'],
            $r['tipe'],
            $r['kode_barang'],
            $r['nama_barang'],
            $r['nama_satuan'],
            $r['kode_rak'],
            ($r['tipe'] === 'MASUK' ? '+' : '-') . $r['jumlah'],
            $r['pihak_terkait'],
            $r['ref_dokumen'] ?? '',
            $r['keterangan'] ?? '',
            $r['nama_petugas']
        ]);
    }
}

fclose($output);
exit;

