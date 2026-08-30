<?php
// actions/transaction_action.php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

$fallbackUrl = '../pages/dashboard.php';
$redirectTo = $_SERVER['HTTP_REFERER'] ?? $fallbackUrl;

if (!empty($_POST['from'])) {
    if ($_POST['from'] === 'barang_masuk') {
        $redirectTo = '../pages/barang_masuk.php';
    } elseif ($_POST['from'] === 'barang_keluar') {
        $redirectTo = '../pages/barang_keluar.php';
    } elseif ($_POST['from'] === 'dashboard') {
        $redirectTo = '../pages/dashboard.php';
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: $redirectTo");
    exit();
}

$csrf_token = $_POST['csrf_token'] ?? '';
if (!verifyCSRFToken($csrf_token)) {
    setFlash('danger', 'Sesi invalid (CSRF Token error).');
    header("Location: $redirectTo");
    exit();
}

$act = $_POST['act'] ?? '';
if ($act !== 'delete_transaction') {
    header("Location: $redirectTo");
    exit();
}

$id = (int)($_POST['id'] ?? 0);
$jenis = $_POST['jenis'] ?? '';

if ($id <= 0 || !in_array($jenis, ['Masuk', 'Keluar'], true)) {
    setFlash('danger', 'Data transaksi tidak valid.');
    header("Location: $redirectTo");
    exit();
}

$pdo = getDBConnection();

try {
    $pdo->beginTransaction();

    // Tentukan tabel berdasarkan jenis transaksi
    $table = ($jenis === 'Masuk') ? 'stock_in' : 'stock_out';

    // Cek apakah data transaksi ada di tabel yang sesuai
    $stmt = $pdo->prepare("SELECT id FROM {$table} WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $id]);
    $tx = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$tx) {
        throw new Exception('Riwayat transaksi tidak ditemukan.');
    }

    // Hapus data riwayat TANPA menyentuh atau mengembalikan stok produk
    $stmtDel = $pdo->prepare("DELETE FROM {$table} WHERE id = :id");
    $stmtDel->execute([':id' => $id]);

    if ($jenis === 'Masuk') {
        setFlash('success', "Riwayat Barang Masuk berhasil dihapus.");
    } else {
        setFlash('success', "Riwayat Barang Keluar berhasil dihapus.");
    }

    $pdo->commit();
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    setFlash('danger', 'Gagal menghapus riwayat transaksi: ' . $e->getMessage());
}

header("Location: $redirectTo");
exit();