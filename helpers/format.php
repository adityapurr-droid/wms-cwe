<?php
/**
 * Helper Pemformatan Data & Generator Kode Otomatis
 */

if (!function_exists('format_tanggal')) {
    function format_tanggal(?string $date, bool $include_time = false): string {
        if (!$date) return '-';
        $timestamp = strtotime($date);
        if (!$timestamp) return '-';

        $bulan_indo = [
            1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
        ];

        $hari = date('d', $timestamp);
        $bulan = $bulan_indo[(int)date('m', $timestamp)];
        $tahun = date('Y', $timestamp);

        $result = "{$hari} {$bulan} {$tahun}";
        if ($include_time) {
            $result .= ' ' . date('H:i', $timestamp) . ' WIB';
        }
        return $result;
    }
}

if (!function_exists('generate_kode_barang')) {
    function generate_kode_barang(PDO $pdo): string {
        $stmt = $pdo->query("SELECT kode_barang FROM barang ORDER BY id DESC LIMIT 1");
        $last = $stmt->fetch();
        if ($last && preg_match('/BRG-(\d+)/', $last['kode_barang'], $m)) {
            $next_num = (int)$m[1] + 1;
        } else {
            $next_num = 1;
        }
        return sprintf("BRG-%03d", $next_num);
    }
}

if (!function_exists('generate_barcode_auto')) {
    function generate_barcode_auto(PDO $pdo): string {
        // Format EAN-13 / numeric barcode unik kampus CWE (8991 + 6 digit acak/urut)
        $prefix = "8991";
        do {
            $random = rand(100000, 999999);
            $barcode = $prefix . $random;
            $stmt = $pdo->prepare("SELECT id FROM barang WHERE barcode = ?");
            $stmt->execute([$barcode]);
        } while ($stmt->fetch());
        return $barcode;
    }
}

if (!function_exists('generate_kode_transaksi')) {
    function generate_kode_transaksi(PDO $pdo, string $prefix = 'BM'): string {
        $ym = date('Ym');
        $table = ($prefix === 'BM') ? 'barang_masuk' : 'barang_keluar';
        $search = "{$prefix}-{$ym}-%";
        
        $stmt = $pdo->prepare("SELECT no_transaksi FROM {$table} WHERE no_transaksi LIKE ? ORDER BY id DESC LIMIT 1");
        $stmt->execute([$search]);
        $last = $stmt->fetch();

        if ($last && preg_match("/{$prefix}-{$ym}-(\d+)/", $last['no_transaksi'], $m)) {
            $next_num = (int)$m[1] + 1;
        } else {
            $next_num = 1;
        }
        return sprintf("%s-%s-%03d", $prefix, $ym, $next_num);
    }
}

