<?php
// actions/barang_keluar_return_action.php
// Handler untuk mengembalikan barang keluar yang sedang dipakai kembali ke stok opname
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

$fallbackUrl = '../pages/dashboard.php';
$redirectTo = $_POST['from'] ?? $fallbackUrl;
if ($redirectTo === 'dashboard') $redirectTo = '../pages/dashboard.php';
elseif ($redirectTo === 'barang_keluar') $redirectTo = '../pages/barang_keluar.php';
elseif (strpos($redirectTo, '..') === false) $redirectTo = '../pages/dashboard.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: $redirectTo");
    exit();
}

if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    setFlash('danger', 'Sesi invalid (CSRF Token error).');
    header("Location: $redirectTo");
    exit();
}

$id = intval($_POST['id'] ?? 0);
if ($id <= 0) {
    setFlash('danger', 'ID transaksi tidak valid.');
    header("Location: $redirectTo");
    exit();
}

$pdo = getDBConnection();

// Pastikan kolom migrasi ada
try { ensureStockOutReturnColumns($pdo); } catch (Exception $e) {}

$userId = $_SESSION['user_id'] ?? null;

try {
    $pdo->beginTransaction();

    // Ambil data stock_out
    $hasStatusCol = $pdo->query("SHOW COLUMNS FROM stock_out LIKE 'status_pakai'")->rowCount() > 0;
    $stmt = $pdo->prepare("SELECT so.*, p.kode_barang, p.nama_barang FROM stock_out so JOIN products p ON so.product_id = p.id WHERE so.id = :id LIMIT 1");
    // Fallback jika tidak join (produk terhapus)
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        // Coba tanpa join produk (untuk data lama yg produknya sudah dihapus)
        $stmt2 = $pdo->prepare("SELECT * FROM stock_out WHERE id = :id LIMIT 1");
        $stmt2->execute([':id' => $id]);
        $row = $stmt2->fetch(PDO::FETCH_ASSOC);
        if (!$row) throw new Exception('Data barang keluar tidak ditemukan.');
        // Tetap lanjut tapi tidak bisa kembalikan stok karena product_id tidak valid
        throw new Exception('Produk terkait tidak ditemukan, tidak bisa mengembalikan stok.');
    }

    if ($hasStatusCol && isset($row['status_pakai']) && $row['status_pakai'] === 'Dikembalikan') {
        throw new Exception('Barang ini sudah dikembalikan sebelumnya.');
    }

    $productId = intval($row['product_id']);
    $jumlah = intval($row['jumlah']);
    $kondisi = $row['kondisi'] ?? 'Tidak Rusak';
    $kodeBarang = $row['kode_barang'] ?? null;

    if ($productId <= 0 || $jumlah <= 0) throw new Exception('Data transaksi tidak valid.');

    // Cek produk masih ada
    $stmtProd = $pdo->prepare("SELECT id, kode_barang, stok FROM products WHERE id = :pid LIMIT 1");
    $stmtProd->execute([':pid' => $productId]);
    $prod = $stmtProd->fetch(PDO::FETCH_ASSOC);
    if (!$prod) throw new Exception('Produk tidak ditemukan di master stok opname.');

    // Kembalikan stok: jika Rusak -> kembalikan ke stok_rusak_barang, else ke products.stok
    if ($kondisi === 'Rusak') {
        // Cek tabel stok_rusak_barang ada?
        $hasStokRusakTable = $pdo->query("SHOW TABLES LIKE 'stok_rusak_barang'")->rowCount() > 0;
        if ($hasStokRusakTable && $kodeBarang) {
            $stmtUpSR = $pdo->prepare("UPDATE stok_rusak_barang SET stok_rusak = stok_rusak + :jml WHERE kode = :kode");
            $stmtUpSR->execute([':jml' => $jumlah, ':kode' => $kodeBarang]);
            // Jika tidak ada row terupdate (kode tidak ditemukan), insert baru?
            if ($stmtUpSR->rowCount() === 0) {
                // Cek apakah ada produk dengan kode tersebut di stok_rusak_barang
                $stmtCheck = $pdo->prepare("SELECT id FROM stok_rusak_barang WHERE kode = :kode LIMIT 1");
                $stmtCheck->execute([':kode' => $kodeBarang]);
                if (!$stmtCheck->fetch()) {
                    // Insert minimal
                    try {
                        $pdo->prepare("INSERT INTO stok_rusak_barang (kode, nama_barang, stok_rusak) VALUES (:kode, :nama, :jml)")
                            ->execute([':kode' => $kodeBarang, ':nama' => $row['nama_barang'] ?? $prod['kode_barang'], ':jml' => $jumlah]);
                    } catch (Exception $e) { /* ignore */ }
                }
            }
            // Catat riwayat masuk rusak (opsional)
            $hasRiwayatMasukRusak = $pdo->query("SHOW TABLES LIKE 'stok_rusak_masuk'")->rowCount() > 0;
            // Tidak wajib
        } else {
            // Fallback: kembalikan ke stok biasa jika tabel rusak tidak ada
            $stmtUp = $pdo->prepare("UPDATE products SET stok = stok + :jml WHERE id = :pid");
            $stmtUp->execute([':jml' => $jumlah, ':pid' => $productId]);
        }
    } else {
        $stmtUp = $pdo->prepare("UPDATE products SET stok = stok + :jml WHERE id = :pid");
        $stmtUp->execute([':jml' => $jumlah, ':pid' => $productId]);
    }

    // Update status_pakai menjadi Dikembalikan
    if ($hasStatusCol) {
        $hasTanggalKembali = $pdo->query("SHOW COLUMNS FROM stock_out LIKE 'tanggal_kembali'")->rowCount() > 0;
        $hasDikembalikanBy = $pdo->query("SHOW COLUMNS FROM stock_out LIKE 'dikembalikan_by'")->rowCount() > 0;
        if ($hasTanggalKembali && $hasDikembalikanBy) {
            $stmtUpd = $pdo->prepare("UPDATE stock_out SET status_pakai='Dikembalikan', tanggal_kembali=NOW(), dikembalikan_by=:uid WHERE id=:id");
            $stmtUpd->execute([':uid' => $userId, ':id' => $id]);
        } elseif ($hasTanggalKembali) {
            $stmtUpd = $pdo->prepare("UPDATE stock_out SET status_pakai='Dikembalikan', tanggal_kembali=NOW() WHERE id=:id");
            $stmtUpd->execute([':id' => $id]);
        } else {
            $stmtUpd = $pdo->prepare("UPDATE stock_out SET status_pakai='Dikembalikan' WHERE id=:id");
            $stmtUpd->execute([':id' => $id]);
        }
    } else {
        // Jika kolom belum ada, fallback hapus? Tidak, tetap success tanpa update status
    }

    $pdo->commit();
    setFlash('success', "Barang '{$row['nama_barang']}' sejumlah $jumlah berhasil dikembalikan ke Stok Opname. Stok telah ditambahkan.");
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    setFlash('danger', 'Gagal mengembalikan barang: ' . $e->getMessage());
}

header("Location: $redirectTo");
exit();
