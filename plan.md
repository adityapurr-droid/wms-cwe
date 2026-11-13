# Rencana & Dokumentasi Pengembangan Warehouse Management System (WMS)
## Politeknik Kelapa Sawit Citra Widya Edukasi (CWE)

## 1. Spesifikasi Sistem
- **Tujuan**: Membangun sistem inventori terpadu berbasis penyimpanan rak 1 pintu untuk Politeknik Kelapa Sawit Citra Widya Edukasi (CWE). Ramah pemula, mudah di-hosting (*cPanel shared hosting ready*), aman (PDO Prepared Statements), dan memiliki UI/UX intuitif bagi staf perguruan tinggi non-IT.
- **Tech Stack**:
  - **Backend**: Clean Modular PHP 8+ dengan PDO MySQL (Arsitektur MVC Sederhana tanpa dependensi build tools).
  - **Database**: MySQL 8.0 (`wms_db`) dengan foreign keys & database transactions (ACID).
  - **Frontend/UI**: Bootstrap 5.3 + FontAwesome 6 + Bootstrap Icons + Custom Palm Emerald Theme (Hijau Kelapa Sawit CWE).
  - **Interaktivitas & Visual**:
    - **DataTables**: Pencarian instan, filter, dan paginasi tabel.
    - **Chart.js**: Grafik tren mutasi harian, sebaran kategori donat, dan kapasitas keterisian rak gudang.
    - **SweetAlert2**: Notifikasi pop-up modern dan dialog konfirmasi hapus data.
    - **Barcode & QR Scanner**: Library `html5-qrcode` (kamera HP/laptop) dan mode input laser scanner USB.
    - **JsBarcode**: Generator & cetak label barcode stiker fisik.

---

## 2. Struktur Database (`wms_db`)
Telah diimplementasikan 9 tabel relasional terpadu:

| Nama Tabel      | Fungsi Utama                                                        | Relasi                                  |
| :-------------- | :------------------------------------------------------------------ | :-------------------------------------- |
| `users`         | Akun staf & hak akses (`admin`, `operator`)                         | -                                       |
| `kategori`      | Pengelompokan barang (Sarana Panen, Pupuk, Reagen Lab, ATK, dll)    | Relasi ke `barang`                      |
| `satuan`        | Satuan unit kemasan (Sak, Liter, Kg, Botol, Pcs, Rim, Unit)         | Relasi ke `barang`                      |
| `rak`           | Penataan rak penyimpanan gudang 1 pintu (RAK-A1, RAK-B1, RAK-C1)    | Relasi ke `barang`                      |
| `supplier`      | Data rekanan/vendor penyedia barang masuk                           | Relasi ke `barang_masuk`                |
| `departemen`    | Unit kampus/prodi pemohon barang keluar (BKS, TPHP, ML, Lab, Umum)  | Relasi ke `barang_keluar`               |
| `barang`        | Master data barang, kode, barcode, stok fisik, batas minimum, rak   | Relasi ke `kategori`, `satuan`, `rak`   |
| `barang_masuk`  | Pencatatan pasokan masuk 1 pintu gudang (tambah stok otomatis)      | Relasi ke `barang`, `supplier`, `users` |
| `barang_keluar` | Penyerahan barang ke prodi/unit kampus (kurangi stok otomatis)      | Relasi ke `barang`, `departemen`, `users`|

---

## 3. Hak Akses (Role-Based Access Control)
- **Administrator**:
  - Akses penuh seluruh sistem: Dashboard KPI Interaktif, Live Barcode Scanner, Transaksi Barang Masuk & Keluar, Master Data Barang, Kartu Stok, Kelola Rak, Kategori, Satuan, Supplier, Departemen, Rekapitulasi Laporan Cetak & Excel, serta Kelola Pengguna.
- **Operator**:
  - Operasional harian: Dashboard Operator, Live Barcode Scanner, Penerimaan Barang Masuk, Pengeluaran Barang Keluar, Katalog Master Barang, Kartu Stok Barang, Cetak Bukti Transaksi, dan Rekapitulasi Laporan. Menu konfigurasi master & akun diproteksi otomatis.

---

## 4. Akun Bawaan (Default Credentials)
| Role          | Username    | Password      | Hak Akses                                      |
| :------------ | :---------- | :------------ | :--------------------------------------------- |
| Administrator | `admin`     | `admin123`    | Akses penuh ke seluruh menu & kelola pengguna |
| Operator      | `operator`  | `operator123` | Akses operasional transaksi gudang & laporan   |

---

## 5. Checklist Pengerjaan (Status Selesai)

### Fase 1: Persiapan & Basis Data
- [x] Install Local Environment (Laragon / XAMPP).
- [x] Buat database `wms_db` di MySQL.
- [x] Buat tabel `users`, `kategori`, `satuan`, `rak`, `supplier`, `departemen`, `barang`, `barang_masuk`, `barang_keluar`.
- [x] Seeder data inisial barang & sarana perkebunan kelapa sawit CWE.

### Fase 2: Autentikasi & Templating
- [x] Desain layout responsif bertema Kampus CWE (`header`, `navbar`, `sidebar`, `footer`).
- [x] Buat halaman Login modern dengan fitur *Quick Demo Fill* & intip password.
- [x] Logika autentikasi `password_verify` bcrypt, proteksi CSRF, dan RBAC (`admin` vs `operator`).
- [x] Middleware proteksi halaman dan auto-redirect session.

### Fase 3: Master Data & Lokasi Rak Gudang
- [x] CRUD Manajemen Rak Gudang 1 Pintu (`modules/rak`).
- [x] CRUD Master Kategori Barang (`modules/kategori`).
- [x] CRUD Master Satuan Ukur (`modules/satuan`).
- [x] CRUD Pemasok / Rekanan Vendor (`modules/supplier`).
- [x] CRUD Unit / Program Studi Kampus (`modules/departemen`).
- [x] CRUD Master Data Barang lengkap dengan auto-generate kode barang & barcode (`modules/barang`).
- [x] Fitur **Kartu Stok (Stock Ledger)** dengan saldo berjalan per barang.
- [x] Fitur **Cetak Label Barcode & Rak** siap tempel fisik (`modules/barang/cetak_barcode.php`).

### Fase 4: Transaksi & Integrasi Barcode Scanner
- [x] Modul **Live Barcode & QR Scanner** (`modules/scanner`):
  - Dukungan kamera laptop / smartphone via `html5-qrcode`.
  - Dukungan alat barcode scanner laser USB / wireless dengan mode auto-focus & instant lookup.
  - Quick action langsung (Terima Masuk, Catat Keluar, Cek Kartu Stok).
- [x] Modul Transaksi Barang Masuk (Penerimaan 1 Pintu):
  - Form pencatatan pasokan supplier dengan auto update stok (PDO Transaction).
  - Cetak Bukti Penerimaan Barang resmi ber-Kop Surat CWE.
- [x] Modul Transaksi Barang Keluar:
  - Validasi ketat (stok tidak boleh minus).
  - Form pengeluaran ke prodi/unit dengan auto pengurangan stok (PDO Transaction).
  - Cetak Berita Acara Serah Terima Barang (BASTB) ber-Kop Surat CWE.

### Fase 5: Dashboard Interaktif & Laporan
- [x] Dashboard Interaktif & Informatif:
  - 4 Kartu KPI Metrik (Total SKU, Total Unit Fisik, Transaksi Bulan Ini, Alert Stok Kritis).
  - Grafik Chart.js: Tren mutasi 7 hari, sebaran donat kategori, dan keterisian kapasitas rak.
  - Tabel Reorder Point / Peringatan Stok Menipis.
  - Timeline aktivitas transaksi mutasi terkini.
- [x] Modul Rekapitulasi Laporan:
  - Filter rentang tanggal, jenis laporan (Masuk, Keluar, Saldo Stok Rak), kategori, dan rak.
  - Format Cetak A4 resmi dengan Kop Surat Politeknik Kelapa Sawit CWE & lembar pengesahan tanda tangan.
  - Ekspor data bersih ke file Excel / CSV.

### Fase 6: Hosting & Deployment (Panduan cPanel)
- [x] Skrip database siap impor: `database/wms_cwe.sql`.
- [x] Konfigurasi koneksi mudah diatur: `config/database.php`.
- [x] URL otomatis mendeteksi domain server: `config/app.php`.
