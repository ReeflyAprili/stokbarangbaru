<?php
// actions/produk_action.php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../pages/produk.php");
    exit();
}

$csrf_token = $_POST['csrf_token'] ?? '';
if (!verifyCSRFToken($csrf_token)) {
    setFlash('danger', 'Sesi invalid (CSRF Token error).');
    header("Location: ../pages/produk.php");
    exit();
}

$act = $_POST['act'] ?? '';
$pdo = getDBConnection();

if ($act === 'add') {
    $kode_barang = trim($_POST['kode_barang'] ?? '');
    $nama_barang = trim($_POST['nama_barang'] ?? '');
    $kategori_id = (int)($_POST['kategori_id'] ?? 0);
    $satuan = trim($_POST['satuan'] ?? 'Pcs');
    $harga_beli = (float)($_POST['harga_beli'] ?? 0);
    $harga_jual = (float)($_POST['harga_jual'] ?? 0);
    $stok = (int)($_POST['stok'] ?? 0);
    $stok_minimum = (int)($_POST['stok_minimum'] ?? 5);
    $lokasi_rak = trim($_POST['lokasi_rak'] ?? '');
    $deskripsi = trim($_POST['deskripsi'] ?? '');

    // Auto generate kode_barang if empty
    if (empty($kode_barang)) {
        $lastId = $pdo->query("SELECT MAX(id) FROM products")->fetchColumn() ?: 0;
        $kode_barang = 'BRG-' . str_pad($lastId + 1, 4, '0', STR_PAD_LEFT);
    }

    if (empty($nama_barang)) {
        setFlash('danger', 'Nama barang wajib diisi.');
        header("Location: ../pages/produk.php");
        exit();
    }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO products (kode_barang, nama_barang, kategori_id, satuan, harga_beli, harga_jual, stok, stok_minimum, lokasi_rak, deskripsi)
            VALUES (:kode, :nama, :kat, :satuan, :harga_b, :harga_j, :stok, :stok_min, :rak, :desk)
        ");
        $stmt->execute([
            ':kode' => $kode_barang,
            ':nama' => $nama_barang,
            ':kat' => $kategori_id > 0 ? $kategori_id : null,
            ':satuan' => $satuan,
            ':harga_b' => $harga_beli,
            ':harga_j' => $harga_jual,
            ':stok' => $stok,
            ':stok_min' => $stok_minimum,
            ':rak' => $lokasi_rak,
            ':desk' => $deskripsi,
        ]);
        setFlash('success', "Produk [$kode_barang] $nama_barang berhasil ditambahkan.");
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            setFlash('danger', "Kode barang '$kode_barang' sudah terdaftar.");
        } else {
            setFlash('danger', 'Gagal menambah produk: ' . $e->getMessage());
        }
    }
} elseif ($act === 'edit') {
    $id = (int)($_POST['id'] ?? 0);
    $kode_barang = trim($_POST['kode_barang'] ?? '');
    $nama_barang = trim($_POST['nama_barang'] ?? '');
    $kategori_id = (int)($_POST['kategori_id'] ?? 0);
    $satuan = trim($_POST['satuan'] ?? 'Pcs');
    $harga_beli = (float)($_POST['harga_beli'] ?? 0);
    $harga_jual = (float)($_POST['harga_jual'] ?? 0);
    $stok = (int)($_POST['stok'] ?? 0);
    $stok_minimum = (int)($_POST['stok_minimum'] ?? 5);
    $lokasi_rak = trim($_POST['lokasi_rak'] ?? '');
    $deskripsi = trim($_POST['deskripsi'] ?? '');

    if ($id <= 0 || empty($nama_barang)) {
        setFlash('danger', 'Data produk tidak valid.');
        header("Location: ../pages/produk.php");
        exit();
    }

    try {
        $stmt = $pdo->prepare("
            UPDATE products 
            SET kode_barang = :kode,
                nama_barang = :nama,
                kategori_id = :kat,
                satuan = :satuan,
                harga_beli = :harga_b,
                harga_jual = :harga_j,
                stok = :stok,
                stok_minimum = :stok_min,
                lokasi_rak = :rak,
                deskripsi = :desk
            WHERE id = :id
        ");
        $stmt->execute([
            ':kode' => $kode_barang,
            ':nama' => $nama_barang,
            ':kat' => $kategori_id > 0 ? $kategori_id : null,
            ':satuan' => $satuan,
            ':harga_b' => $harga_beli,
            ':harga_j' => $harga_jual,
            ':stok' => $stok,
            ':stok_min' => $stok_minimum,
            ':rak' => $lokasi_rak,
            ':desk' => $deskripsi,
            ':id' => $id,
        ]);
        setFlash('success', "Produk [$kode_barang] berhasil diperbarui.");
    } catch (PDOException $e) {
        setFlash('danger', 'Gagal memperbarui produk: ' . $e->getMessage());
    }
} elseif ($act === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        setFlash('danger', 'ID produk invalid.');
        header("Location: ../pages/produk.php");
        exit();
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = :id");
        $stmt->execute([':id' => $id]);
        setFlash('success', 'Produk berhasil dihapus dari inventaris.');
    } catch (PDOException $e) {
        setFlash('danger', 'Gagal menghapus produk: ' . $e->getMessage());
    }
}

header("Location: ../pages/produk.php");
exit();
