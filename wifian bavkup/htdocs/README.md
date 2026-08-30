# SISTEM INVENTORY PT WIFIAN SOLUTION (WEB-BASED)

Sistem Inventory Manajemen Stok Barang Perusahaan Internet Provider (ISP) & Toko Bangunan berbasis Web yang modern, responsif, aman, dan mudah digunakan.

---

## 🚀 TEKNOLOGI YANG DIGUNAKAN

### Frontend
- **HTML5** & **Vanilla CSS**
- **Tailwind CSS** (via CDN)
- **JavaScript (ES6)**
- **Chart.js** (Dashboard Grafik Batang & Doughnut)
- **Font Awesome 6** (Icons)

### Backend
- **PHP 8+** (Native tanpa Framework)
- **PDO Prepared Statements** (Keamanan dari SQL Injection)
- **Session Authentication** & **Role-Based Access Control (RBAC)**
- **CSRF Protection & XSS Escaping**

### Database
- **MySQL / MariaDB**

---

## 🔑 AKUN DEMO DEFAULT

Saat database pertama kali di-import atau aplikasi dijalankan, akun default berikut dapat langsung digunakan:

### 1. Akun Admin (Akses Penuh)
- **Username**: `admin`
- **Password**: `admin123`

### 2. Akun Staff Gudang
- **Username**: `staff`
- **Password**: `admin123`

> Password di-hash menggunakan algoritma standar `password_hash()` (BCRYPT).

---

## 📋 FITUR UTAMA APLIKASI

1. **Dashboard Realtime**:
   - Card Statistik: Total Produk, Total Stok, Barang Masuk, Barang Keluar, Total Kategori, Stok Menipis, Stok Habis.
   - Bar Chart: Perbandingan Barang Masuk & Keluar per Bulan.
   - Pie Chart: Distribusi Stok per Kategori.
   - 10 Transaksi Terbaru.

2. **Manajemen Produk**:
   - Kode Barang Otomatis (misal `BRG-0001`).
   - CRUD Produk lengkap (Nama, Kategori, Satuan, Harga Beli/Jual, Stok Awal, Stok Minimum, Lokasi Rak, Deskripsi).
   - Filter Tab (Semua Produk, Stok Menipis, Stok Habis).
   - Pencarian Live Realtime.
   - Status Badge (Aman, Menipis, Habis).

3. **Kategori Barang**:
   - CRUD Kategori dengan perhitungan jumlah produk per kategori.

4. **Barang Masuk**:
   - Auto Nomor Transaksi (`BM-YYYYMMDD-001`).
   - Otomatisasi Stok: `Stok Baru = Stok Lama + Jumlah Masuk`.
   - Riwayat Transaksi Barang Masuk.

5. **Barang Keluar**:
   - Auto Nomor Transaksi (`BK-YYYYMMDD-001`).
   - Otomatisasi Stok: `Stok Baru = Stok Lama - Jumlah Keluar`.
   - Validasi Stok: Mencegah stok minus dengan alert *"Stok barang tidak mencukupi."*
   - Riwayat Transaksi Barang Keluar.

6. **Stok Opname**:
   - Audit Fisik vs Sistem dengan kalkulasi selisih otomatis (`Stok Fisik - Stok Sistem`).
   - Otomatisasi Penyesuaian: `Stok Sistem = Stok Fisik`.
   - Status Opname (Sesuai, Lebih, Kurang).

7. **Laporan & Export**:
   - Filter Periode: Harian, Mingguan, Bulanan, Tahunan, Rentang Tanggal Custom.
   - Jenis Laporan: Stok Barang, Barang Masuk, Barang Keluar, Stok Opname, Stok Menipis, Nilai Persediaan (Total Aset Inventaris).
   - Export Mode: **Print Layout**, **Export PDF**, dan **Export Excel / CSV**.

8. **Pengguna & Otorisasi**:
   - Role Admin & Staff Gudang.

9. **Pengaturan & Backup**:
   - Pengaturan Nama Toko, Alamat, Telepon, Email, & Upload Logo.
   - One-Click **Backup Database (.sql)**.
   - **Restore Database (.sql)** dari file upload.
   - Ganti Password Pengguna.

---

## 🛠️ PANDUAN INSTALASI

### 1. Instalasi di XAMPP
1. Download atau ekstraksi file `inventaris.zip` ke folder: `C:\xampp\htdocs\inventory-wifian\`
2. Buka **XAMPP Control Panel** dan jalankan modul **Apache** dan **MySQL**.
3. Buka browser dan akses [http://localhost/phpmyadmin](http://localhost/phpmyadmin).
4. Buat database baru bernama `database_inventory`.
5. Import file SQL yang berada di `database/database_inventory.sql`.
6. Buka aplikasi di browser: [http://localhost/inventory-wifian](http://localhost/inventory-wifian).

### 2. Instalasi di Laragon
1. Ekstraksi folder project ke `C:\laragon\www\inventory-wifian\`.
2. Start All Services pada Laragon.
3. Import `database/database_inventory.sql` via HeidiSQL / phpMyAdmin.
4. Akses melalui [http://inventory-wifian.test](http://inventory-wifian.test) atau [http://localhost/inventory-wifian](http://localhost/inventory-wifian).

### 3. Deploy di Shared Hosting / cPanel
1. Upload isi project ke folder `public_html` atau sub-domain Anda.
2. Buat database MySQL baru melalui cPanel Database Wizard.
3. Import file `database/database_inventory.sql` melalui cPanel phpMyAdmin.
4. Sesuaikan konstanta `DB_HOST`, `DB_USER`, `DB_PASS`, dan `DB_NAME` di file `config/database.php`.

---

## 📂 STRUKTUR FOLDER PROJECT

```
Stock Barang PT Wifian Solution/
├── actions/
│   ├── barang_keluar_action.php
│   ├── barang_masuk_action.php
│   ├── export_action.php
│   ├── kategori_action.php
│   ├── login_action.php
│   ├── opname_action.php
│   ├── produk_action.php
│   ├── setting_action.php
│   └── user_action.php
├── assets/
│   ├── css/
│   │   └── custom.css
│   ├── js/
│   │   └── main.js
│   └── images/
├── config/
│   └── database.php
├── database/
│   └── database_inventory.sql
├── includes/
│   ├── auth.php
│   ├── footer.php
│   ├── header.php
│   └── sidebar.php
├── pages/
│   ├── barang_keluar.php
│   ├── barang_masuk.php
│   ├── dashboard.php
│   ├── kategori.php
│   ├── laporan.php
│   ├── pengaturan.php
│   ├── pengguna.php
│   ├── produk.php
│   └── stok_opname.php
├── uploads/
├── index.php
├── login.php
├── logout.php
└── README.md
```

---
© <?= date('Y') ?> **PT Wifian Solution**. All rights reserved.
