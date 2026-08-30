-- Database: database_inventory.sql
-- Sistem Inventory PT Wifian Solution


-- Table structure for `users`
DROP TABLE IF EXISTS `stock_opname`;
DROP TABLE IF EXISTS `stock_out`;
DROP TABLE IF EXISTS `stock_in`;
DROP TABLE IF EXISTS `products`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `settings`;

CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nama` VARCHAR(100) NOT NULL,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('Admin', 'Staff Gudang') NOT NULL DEFAULT 'Staff Gudang',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table structure for `categories`
CREATE TABLE `categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nama_kategori` VARCHAR(100) NOT NULL UNIQUE,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table structure for `products`
CREATE TABLE `products` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `kode_barang` VARCHAR(50) NOT NULL UNIQUE,
  `nama_barang` VARCHAR(150) NOT NULL,
  `kategori_id` INT DEFAULT NULL,
  `satuan` VARCHAR(30) NOT NULL,
  `harga_beli` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `harga_jual` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `stok` INT NOT NULL DEFAULT 0,
  `stok_minimum` INT NOT NULL DEFAULT 5,
  `lokasi_rak` VARCHAR(50) DEFAULT NULL,
  `deskripsi` TEXT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_products_category` FOREIGN KEY (`kategori_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table structure for `stock_in`
CREATE TABLE `stock_in` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nomor_transaksi` VARCHAR(50) NOT NULL UNIQUE,
  `tanggal` DATE NOT NULL,
  `product_id` INT NOT NULL,
  `jumlah` INT NOT NULL,
  `harga_beli` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `kondisi` ENUM('Tidak Rusak','Rusak') NOT NULL DEFAULT 'Tidak Rusak',
  `keterangan` TEXT DEFAULT NULL,
  `created_by` INT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_stock_in_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_stock_in_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table structure for `stock_out`
CREATE TABLE `stock_out` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nomor_transaksi` VARCHAR(50) NOT NULL UNIQUE,
  `tanggal` DATE NOT NULL,
  `product_id` INT NOT NULL,
  `jumlah` INT NOT NULL,
  `tujuan` VARCHAR(150) NOT NULL,
  `kondisi` ENUM('Tidak Rusak','Rusak') NOT NULL DEFAULT 'Tidak Rusak',
  `keterangan` TEXT DEFAULT NULL,
  `alasan_rusak` TEXT DEFAULT NULL,
  `catatan_pemasangan` TEXT DEFAULT NULL,
  `status_pakai` ENUM('Dipakai','Dikembalikan') NOT NULL DEFAULT 'Dipakai',
  `tanggal_kembali` DATETIME DEFAULT NULL,
  `dikembalikan_by` INT DEFAULT NULL,
  `created_by` INT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_stock_out_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_stock_out_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_stock_out_return_user` FOREIGN KEY (`dikembalikan_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table structure for `stock_opname`
CREATE TABLE `stock_opname` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `tanggal` DATE NOT NULL,
  `product_id` INT NOT NULL,
  `stok_sistem` INT NOT NULL,
  `stok_fisik` INT NOT NULL,
  `selisih` INT NOT NULL,
  `keterangan` TEXT DEFAULT NULL,
  `created_by` INT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_opname_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_opname_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table structure for `settings`
CREATE TABLE `settings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nama_toko` VARCHAR(150) NOT NULL,
  `logo` VARCHAR(255) DEFAULT NULL,
  `alamat` TEXT DEFAULT NULL,
  `telepon` VARCHAR(50) DEFAULT NULL,
  `email` VARCHAR(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Dumping initial seed data

-- Default Admin (Password: admin123)
INSERT INTO `users` (`id`, `nama`, `username`, `password`, `role`) VALUES
(1, 'Administrator PT Wifian Solution', 'admin', '$2y$10$AbZknZC7HC5TXSscfbwLzO1Ho8WTJWtRXbsnEpHkJFgQAyjeVr57W', 'Admin'),
(2, 'Staff Gudang Utama', 'staff', '$2y$10$AbZknZC7HC5TXSscfbwLzO1Ho8WTJWtRXbsnEpHkJFgQAyjeVr57W', 'Staff Gudang');

-- Sample Categories
INSERT INTO `categories` (`id`, `nama_kategori`) VALUES
(1, 'Kabel & UTP'),
(2, 'Peralatan Fiber Optic');

-- Sample Settings
INSERT INTO `settings` (`id`, `nama_toko`, `logo`, `alamat`, `telepon`, `email`) VALUES
(1, 'PT Wifian Solution', 'logo_wifian.svg', 'Jl. Merdeka No. 45, Jakarta Selatan', '0812-3456-7890', 'info@wifiansolution.co.id');

-- Sample Products
INSERT INTO `products` (`id`, `kode_barang`, `nama_barang`, `kategori_id`, `satuan`, `harga_beli`, `harga_jual`, `stok`, `stok_minimum`, `lokasi_rak`, `deskripsi`) VALUES
(1, 'BRG-0001', 'Kabel UTP Cat6 Belden 305m', 1, 'Roll', 1250000.00, 1500000.00, 25, 5, 'Rak A1', 'Kabel LAN High Speed Cat6'),
(2, 'BRG-0002', 'Kabel Dropcore FO 1 Core 1000m', 6, 'Roll', 850000.00, 1100000.00, 15, 3, 'Rak A2', 'Drop Cable FTTH 1 Core 3 Wire'),
(3, 'BRG-0003', 'Semen Tiga Roda 50kg', 2, 'Sak', 65000.00, 72000.00, 80, 20, 'Gudang B', 'Semen Portland berkualitas tinggi'),
(4, 'BRG-0004', 'Cat Tembok Dulux Putih 20L', 3, 'Pail', 850000.00, 95000.00, 4, 10, 'Rak C1', 'Cat interior anti kuman'),
(5, 'BRG-0005', 'Pipa PVC Wavin 2 Inch', 5, 'Batang', 45000.00, 55000.00, 0, 10, 'Rak D1', 'Pipa air bersih tebal'),
(6, 'BRG-0006', 'Lampu LED Philips 12W', 4, 'Pcs', 35000.00, 45000.00, 50, 15, 'Rak E1', 'Lampu hemat energi 12 Watt');

-- Sample Initial Stock In Transactions
INSERT INTO `stock_in` (`id`, `nomor_transaksi`, `tanggal`, `product_id`, `jumlah`, `harga_beli`, `keterangan`, `created_by`) VALUES
(1, 'BM-20260801-001', '2026-08-01', 1, 25, 1250000.00, 'Restock awal bulan kabel UTP', 1),
(2, 'BM-20260801-002', '2026-08-01', 3, 100, 65000.00, 'Penerimaan semen pabrik', 1);

-- Sample Initial Stock Out Transactions
INSERT INTO `stock_out` (`id`, `nomor_transaksi`, `tanggal`, `product_id`, `jumlah`, `tujuan`, `keterangan`, `created_by`) VALUES
(1, 'BK-20260802-001', '2026-08-02', 3, 20, 'Proyek Perumahan Indah', 'Pengiriman semen tahap 1', 2);
