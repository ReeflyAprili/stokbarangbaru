<?php
// actions/opname_action.php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

$pdo = getDBConnection();

// ==========================================
// 1. PROSES HAPUS (GET METHOD)
// ==========================================
if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    $id = (int)($_GET['id'] ?? 0);

    if ($id > 0) {
        try {
            // HANYA HAPUS RIWAYAT DI TABEL STOCK_OPNAME
            // (Tanpa menyentuh/mengubah tabel products)
            $stmt = $pdo->prepare("DELETE FROM stock_opname WHERE id = :id");
            $stmt->execute([':id' => $id]);

            setFlash('success', 'Riwayat stok opname berhasil dihapus.');
        } catch (PDOException $e) {
            setFlash('danger', 'Gagal menghapus riwayat: ' . $e->getMessage());
        }
    } else {
        setFlash('danger', 'ID riwayat tidak valid.');
    }

    header("Location: ../pages/stok_opname.php");
    exit();
}

// ==========================================
// 2. PROSES TAMBAH (POST METHOD)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../pages/stok_opname.php");
    exit();
}

$csrf_token = $_POST['csrf_token'] ?? '';
if (!verifyCSRFToken($csrf_token)) {
    setFlash('danger', 'Sesi invalid (CSRF Token error).');
    header("Location: ../pages/stok_opname.php");
    exit();
}

$tanggal = trim($_POST['tanggal'] ?? date('Y-m-d'));
$product_id = (int)($_POST['product_id'] ?? 0);
$stok_sistem = (int)($_POST['stok_sistem'] ?? 0);
$stok_fisik = (int)($_POST['stok_fisik'] ?? 0);
$selisih = $stok_fisik - $stok_sistem;
$keterangan = trim($_POST['keterangan'] ?? '');
$user_id = $_SESSION['user_id'] ?? null;

if ($product_id <= 0) {
    setFlash('danger', 'Pilih barang untuk stok opname.');
    header("Location: ../pages/stok_opname.php");
    exit();
}

try {
    $pdo->beginTransaction();

    // 1. Insert record into stock_opname
    $stmtOp = $pdo->prepare("
        INSERT INTO stock_opname (tanggal, product_id, stok_sistem, stok_fisik, selisih, keterangan, created_by)
        VALUES (:tgl, :pid, :sis, :fis, :sel, :ket, :uid)
    ");
    $stmtOp->execute([
        ':tgl' => $tanggal,
        ':pid' => $product_id,
        ':sis' => $stok_sistem,
        ':fis' => $stok_fisik,
        ':sel' => $selisih,
        ':ket' => $keterangan,
        ':uid' => $user_id
    ]);

    // 2. Update product stock: Stok Sistem = Stok Fisik
    $stmtUp = $pdo->prepare("
        UPDATE products 
        SET stok = :stok_fisik 
        WHERE id = :pid
    ");
    $stmtUp->execute([
        ':stok_fisik' => $stok_fisik,
        ':pid' => $product_id
    ]);

    $pdo->commit();
    setFlash('success', "Stok Opname berhasil disimpan. Stok sistem kini disesuaikan menjadi $stok_fisik unit.");
} catch (PDOException $e) {
    $pdo->rollBack();
    setFlash('danger', 'Gagal memproses stok opname: ' . $e->getMessage());
}

header("Location: ../pages/stok_opname.php");
exit();