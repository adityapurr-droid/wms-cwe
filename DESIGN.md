# Design Direction: WMS CWE
Politeknik Kelapa Sawit Citra Widya Edukasi (CWE)

## 1. Identitas & Karakter
Sistem operasional gudang kampus terpusat 1 pintu. Bersih, profesional, bebas dari efek artificial/slop, dan memiliki kejelasan fungsional tinggi.

## 2. Warna Tema Utama
Kombinasi warna utama adalah **#153618** (Hijau Hutan Sawit CWE) dan **#FFFFFF** (Putih Bersih):
- **Latar Belakang Sidebar:** `#153618` solid dengan border halus.
- **Sidebar Nav Active:** Latar `#ffffff` dengan teks dan ikon `#153618` (kontras tajam).
- **Latar Belakang Aplikasi:** `#f8faf9` (bersih dan nyaman di mata).
- **Latar Belakang Kartu (Card):** `#ffffff` dengan border tipis `#e2e8e4`.
- **Top Bar:** `#ffffff` dengan logo dan aksen `#153618`.

## 3. Corner Radius (Kompak & Tegas)
Menghilangkan gaya membulat oval/pill besar yang kekanak-kanakan (anti-slop R-11/R-12):
- **Tombol & Input Form:** `4px`
- **Badge & Dropdown:** `4px`
- **Kartu (Card) & Panel:** `6px`
- **Modal:** `6px`

## 4. Tipografi & Warna Fungsional Data
Sistem pewarnaan teks dan tombol diatur secara fungsional berdasarkan perannya:

| Jenis Konten | Warna Font / Aksen | Kode Hex | Implementasi Elemen |
|---|---|---|---|
| **Font Normal** | Hitam / Dark Charcoal | `#1a1d20` | Teks paragraf, tabel standar, label form |
| **Persediaan Barang** | Biru Persediaan | `#184AC4` | Angka stok, SKU, lokasi rak, kartu stok, tombol inventori |
| **Input Data** | Hijau Input | `#17AB03` | Barang masuk, tombol tambah/penerimaan, indikator masuk |
| **Output Data** | Merah Output | `#C7260A` | Barang keluar, pengeluaran, cetak dokumen, ekspor excel, tombol hapus |

## 5. Aturan Ikon & Gerak
- **Ikon:** Bootstrap Icons (BI) seragam putih pada sidebar, dan mengikuti warna fungsional pada modul.
- **Gerak / Motion:** Bebas dari animasi berkedip/pulse konstan (`.scan-pulse` dihapus). Transisi halus 150ms pada hover.