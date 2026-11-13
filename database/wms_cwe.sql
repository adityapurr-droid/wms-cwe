-- Database Warehouse Management System (WMS)
-- Politeknik Kelapa Sawit Citra Widya Edukasi (CWE)

CREATE DATABASE IF NOT EXISTS `wms_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `wms_db`;

-- 1. Tabel Users
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nama` VARCHAR(100) NOT NULL,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('admin', 'operator') NOT NULL DEFAULT 'operator',
  `telepon` VARCHAR(20) DEFAULT NULL,
  `email` VARCHAR(100) DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Tabel Kategori
CREATE TABLE IF NOT EXISTS `kategori` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nama_kategori` VARCHAR(100) NOT NULL,
  `keterangan` TEXT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Tabel Satuan
CREATE TABLE IF NOT EXISTS `satuan` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nama_satuan` VARCHAR(50) NOT NULL,
  `keterangan` VARCHAR(100) DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Tabel Rak (Manajemen Rak Gudang 1 Pintu)
CREATE TABLE IF NOT EXISTS `rak` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `kode_rak` VARCHAR(30) NOT NULL UNIQUE,
  `nama_rak` VARCHAR(100) NOT NULL,
  `kapasitas` VARCHAR(50) DEFAULT NULL,
  `keterangan` TEXT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Tabel Supplier (Penyedia Barang Masuk)
CREATE TABLE IF NOT EXISTS `supplier` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nama_supplier` VARCHAR(120) NOT NULL,
  `kontak_person` VARCHAR(100) DEFAULT NULL,
  `telepon` VARCHAR(25) DEFAULT NULL,
  `email` VARCHAR(100) DEFAULT NULL,
  `alamat` TEXT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Tabel Departemen / Unit Pemohon (Tujuan Barang Keluar)
CREATE TABLE IF NOT EXISTS `departemen` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `kode_departemen` VARCHAR(20) NOT NULL UNIQUE,
  `nama_departemen` VARCHAR(120) NOT NULL,
  `penanggung_jawab` VARCHAR(100) DEFAULT NULL,
  `kontak` VARCHAR(30) DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. Tabel Barang
CREATE TABLE IF NOT EXISTS `barang` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `kode_barang` VARCHAR(30) NOT NULL UNIQUE,
  `barcode` VARCHAR(60) NOT NULL UNIQUE,
  `nama_barang` VARCHAR(150) NOT NULL,
  `id_kategori` INT NOT NULL,
  `id_satuan` INT NOT NULL,
  `id_rak` INT NOT NULL,
  `stok` INT NOT NULL DEFAULT 0,
  `stok_minimum` INT NOT NULL DEFAULT 5,
  `spesifikasi` TEXT DEFAULT NULL,
  `foto` VARCHAR(255) DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`id_kategori`) REFERENCES `kategori`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  FOREIGN KEY (`id_satuan`) REFERENCES `satuan`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  FOREIGN KEY (`id_rak`) REFERENCES `rak`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. Tabel Barang Masuk (Penerimaan 1 Pintu di Gudang)
CREATE TABLE IF NOT EXISTS `barang_masuk` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `no_transaksi` VARCHAR(50) NOT NULL UNIQUE,
  `id_barang` INT NOT NULL,
  `id_supplier` INT DEFAULT NULL,
  `jumlah` INT NOT NULL,
  `tanggal_masuk` DATE NOT NULL,
  `no_surat_jalan` VARCHAR(100) DEFAULT NULL,
  `keterangan` TEXT DEFAULT NULL,
  `user_id` INT NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`id_barang`) REFERENCES `barang`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`id_supplier`) REFERENCES `supplier`(`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 9. Tabel Barang Keluar
CREATE TABLE IF NOT EXISTS `barang_keluar` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `no_transaksi` VARCHAR(50) NOT NULL UNIQUE,
  `id_barang` INT NOT NULL,
  `id_departemen` INT DEFAULT NULL,
  `jumlah` INT NOT NULL,
  `tanggal_keluar` DATE NOT NULL,
  `peruntukan` VARCHAR(150) DEFAULT NULL,
  `keterangan` TEXT DEFAULT NULL,
  `user_id` INT NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`id_barang`) REFERENCES `barang`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`id_departemen`) REFERENCES `departemen`(`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================================================================
-- SEEDER DATA INISIAL (Khas Politeknik Kelapa Sawit Citra Widya Edukasi)
-- =========================================================================

-- Akun Pengguna: admin / admin123 & operator / operator123
INSERT INTO `users` (`id`, `nama`, `username`, `password`, `role`, `telepon`, `email`) VALUES
(1, 'Administrator Gudang CWE', 'admin', '$2y$12$dJSVdiTBV7xKQn7Vp7lnd.K8mTcwo5n4Q3EPdy9afNPvUHAvLfffu', 'admin', '081234567890', 'admin.gudang@cwe.ac.id'),
(2, 'Operator Gudang 1', 'operator', '$2y$12$bFk2oOiFtZ7mdXd8xr7cp.lPP3S3lianCKaTSwWvfFLLcwJK4gCYW', 'operator', '081298765432', 'operator.gudang@cwe.ac.id')
ON DUPLICATE KEY UPDATE `username`=`username`;

-- Kategori Barang
INSERT INTO `kategori` (`id`, `nama_kategori`, `keterangan`) VALUES
(1, 'Sarana Perkebunan Sawit', 'Peralatan lapang panen, dodos, egrek, karung TBS, dll.'),
(2, 'Pupuk & Agrokimia', 'Pupuk NPK, Urea, Herbisida, Insektisida untuk kebun praktikum.'),
(3, 'Bahan Kimia & Reagen Laboratorium', 'Bahan uji asam lemak bebas, analisis laboratorium mutu CPO/PKO.'),
(4, 'Alat Praktikum & Gelas Lab', 'Tabung reaksi, erlenmeyer, pipet tetes, buret, mikroskop.'),
(5, 'Suku Cadang Mesin & Pabrik Mini', 'Sparepart mini mill PKS, conveyor, bearing, vanbelt, pelumas mesin.'),
(6, 'ATK & Keperluan Kantor', 'Kertas A4, toner printer, spidol board, map arsip.')
ON DUPLICATE KEY UPDATE `nama_kategori`=`nama_kategori`;

-- Satuan Barang
INSERT INTO `satuan` (`id`, `nama_satuan`, `keterangan`) VALUES
(1, 'Pcs', 'Pieces / Satuan Unit'),
(2, 'Sak', 'Sak kemasan pupuk 50 Kg'),
(3, 'Liter', 'Volume cairan agrokimia / reagen'),
(4, 'Kg', 'Kilogram'),
(5, 'Botol', 'Kemasan botol kimia kaca/plastik'),
(6, 'Rim', 'Rim kertas dokumen'),
(7, 'Unit', 'Peralatan mesin / instrumen lab'),
(8, 'Box', 'Kotak perlengkapan')
ON DUPLICATE KEY UPDATE `nama_satuan`=`nama_satuan`;

-- Rak Penyimpanan Gudang CWE
INSERT INTO `rak` (`id`, `kode_rak`, `nama_rak`, `kapasitas`, `keterangan`) VALUES
(1, 'RAK-A1', 'Rak A1 - Sarana Panen & Lapang', '100 Unit', 'Penyimpanan dodos, egrek, gancu, batu asah'),
(2, 'RAK-A2', 'Rak A2 - Pupuk & Karung TBS', '50 Sak', 'Zona kering berventilasi khusus pupuk & karung'),
(3, 'RAK-B1', 'Rak B1 - Reagen & Kimia Analisis CPO', '120 Botol', 'Lemari berpintu kaca tahan korosi asam'),
(4, 'RAK-B2', 'Rak B2 - Gelas & Instrumen Lab', '80 Pcs', 'Busa pelindung anti getaran untuk alat kaca'),
(5, 'RAK-C1', 'Rak C1 - Suku Cadang Pabrik Mini PKS', '50 Unit', 'Rak besi beban berat untuk bearing, rantai, sparepart'),
(6, 'RAK-D1', 'Rak D1 - ATK & Dokumen Administrasi', '200 Rim', 'Penyimpanan kertas A4, spidol, binder, toner')
ON DUPLICATE KEY UPDATE `kode_rak`=`kode_rak`;

-- Supplier / Rekanan
INSERT INTO `supplier` (`id`, `nama_supplier`, `kontak_person`, `telepon`, `email`, `alamat`) VALUES
(1, 'PT Sawit Agro Pratama', 'Hendra Wijaya', '081122334455', 'sales@sawitagro.co.id', 'Kawasan Industri Mitra Karawang'),
(2, 'CV Mitra Lab Sentosa', 'Dewi Lestari', '081233445566', 'dewi@mitralab.com', 'Ruko Sentra Niaga, Jl. Sudirman Bekasi'),
(3, 'PT PKS Teknik Perkasa', 'Ir. Bambang S.', '081344556677', 'teknik@pkssinergi.com', 'Cikarang Industrial Estate, Jawa Barat'),
(4, 'Toko Berkah ATK & Offset', 'Siti Aminah', '081566778899', 'berkahatk@gmail.com', 'Jl. Raya Cibitung Km 38, Bekasi')
ON DUPLICATE KEY UPDATE `nama_supplier`=`nama_supplier`;

-- Departemen / Program Studi Pemohon
INSERT INTO `departemen` (`id`, `kode_departemen`, `nama_departemen`, `penanggung_jawab`, `kontak`) VALUES
(1, 'BKS', 'Prodi Budidaya Kelapa Sawit', 'Dr. Ir. Sugeng Priyono, M.Si', '081211112222'),
(2, 'TPHP', 'Prodi Teknologi Pengolahan Hasil Perkebunan', 'Nur Hasanah, S.TP, M.Sc', '081222223333'),
(3, 'ML', 'Prodi Manajemen Logistik', 'Ahmad Ridwan, S.E, M.M', '081233334444'),
(4, 'LAB', 'Laboratorium Terpadu & Quality Control CPO', 'Kepala Lab Terpadu CWE', '081244445555'),
(5, 'PKS-MINI', 'Unit Mini Mill & Bengkel Pengolahan Sawit', 'Koordinator Workshop PKS', '081255556666'),
(6, 'UMUM', 'Bagian Rumah Tangga & Perlengkapan Kampus', 'Kabag Umum CWE', '081266667777')
ON DUPLICATE KEY UPDATE `kode_departemen`=`kode_departemen`;

-- Master Barang
INSERT INTO `barang` (`id`, `kode_barang`, `barcode`, `nama_barang`, `id_kategori`, `id_satuan`, `id_rak`, `stok`, `stok_minimum`, `spesifikasi`) VALUES
(1, 'BRG-001', '8991001001', 'Dodos Sawit Standar 10 cm', 1, 1, 1, 25, 10, 'Baja pegas tempa tajam khusus panen pohon kelapa sawit muda'),
(2, 'BRG-002', '8991001002', 'Egrek Sawit Super Spring Steel', 1, 1, 1, 18, 5, 'Bilah sabit lengkung baja Jerman untuk panen pokok tinggi'),
(3, 'BRG-003', '8991001003', 'Pupuk NPK Mutiara 16-16-16', 2, 2, 2, 45, 15, 'Pupuk granular slow release kemasan sak 50 kg'),
(4, 'BRG-004', '8991001004', 'Herbisida Glifosat 480 SL', 2, 3, 2, 12, 10, 'Sistemik purna tumbuh pengendali gulma piringan sawit, botol 1 Liter'),
(5, 'BRG-005', '8991001005', 'Indikator Phenolphthalein (PP) 1%', 3, 5, 3, 8, 3, 'Larutan uji asam lemak bebas (FFA) CPO di laboratorium, botol 250 ml'),
(6, 'BRG-006', '8991001006', 'Labu Erlenmeyer 250 ml Iwaki Pyrex', 4, 1, 4, 30, 8, 'Kaca borosilikat tahan panas untuk titrasi asam basa'),
(7, 'BRG-007', '8991001007', 'V-Belt Bando B-68 Industri', 5, 1, 5, 6, 4, 'Sabuk transmisi penggerak motor mini thresher PKS'),
(8, 'BRG-008', '8991001008', 'Kertas HVS A4 80 GSM PaperOne', 6, 6, 6, 50, 10, 'Kertas laporan praktikum dan surat menyurat akademik')
ON DUPLICATE KEY UPDATE `kode_barang`=`kode_barang`;

-- Sample Barang Masuk (Penerimaan di Pintu Gudang)
INSERT INTO `barang_masuk` (`id`, `no_transaksi`, `id_barang`, `id_supplier`, `jumlah`, `tanggal_masuk`, `no_surat_jalan`, `keterangan`, `user_id`) VALUES
(1, 'BM-202611-001', 1, 1, 20, '2026-11-01', 'SJ-SAP-2026-091', 'Penerimaan sarana panen praktikum semester ganjil', 1),
(2, 'BM-202611-002', 3, 1, 40, '2026-11-02', 'SJ-SAP-2026-104', 'Pasokan pupuk blok kebun pendidikan CWE', 1),
(3, 'BM-202611-003', 5, 2, 10, '2026-11-03', 'SJ-MLS-8821', 'Reagen uji FFA lab pengolahan', 2),
(4, 'BM-202611-004', 8, 4, 50, '2026-11-04', 'SJ-B-449', 'Kebutuhan ATK ujian tengah semester', 1)
ON DUPLICATE KEY UPDATE `no_transaksi`=`no_transaksi`;

-- Sample Barang Keluar
INSERT INTO `barang_keluar` (`id`, `no_transaksi`, `id_barang`, `id_departemen`, `jumlah`, `tanggal_keluar`, `peruntukan`, `keterangan`, `user_id`) VALUES
(1, 'BK-202611-001', 1, 1, 5, '2026-11-05', 'Praktikum Panen Mahasiswa BKS Angkatan 2024', 'Dipinjam/digunakan di kebun praktik blok C', 2),
(2, 'BK-202611-002', 5, 4, 2, '2026-11-06', 'Uji CPO Laboratorium Terpadu', 'Analisis FFA minyak kelapa sawit', 2)
ON DUPLICATE KEY UPDATE `no_transaksi`=`no_transaksi`;
