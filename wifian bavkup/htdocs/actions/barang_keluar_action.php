<?php
// actions/barang_keluar_action.php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../pages/barang_keluar.php");
    exit();
}

$csrf_token = $_POST['csrf_token'] ?? '';
if (!verifyCSRFToken($csrf_token)) {
    setFlash('danger', 'Sesi invalid (CSRF Token error).');
    header("Location: ../pages/barang_keluar.php");
    exit();
}

$nomor_transaksi = trim($_POST['nomor_transaksi'] ?? '');
$tanggal         = trim($_POST['tanggal'] ?? date('Y-m-d'));
$product_id      = (int)($_POST['product_id'] ?? 0);
$jumlah          = (int)($_POST['jumlah'] ?? 0);
$kondisi         = trim($_POST['kondisi'] ?? 'Tidak Rusak');
$tujuan          = trim($_POST['tujuan'] ?? '');
$alasan_rusak    = trim($_POST['alasan_rusak'] ?? '');
$keterangan      = trim($_POST['catatan_pemasangan'] ?? '');
$user_id         = $_SESSION['user_id'] ?? null;

$pdo = getDBConnection();

if ($kondisi === 'Rusak') {
    $keterangan = $alasan_rusak;
    if (trim($keterangan) === '') {
        setFlash('danger', 'Alasan barang rusak wajib diisi.');
        header("Location: ../pages/barang_keluar.php");
        exit();
    }
}

function generateUniqueTransactionNumber(PDO $pdo, string $prefix, string $tanggal, ?string $kode = null, string $table = 'stock_out'): string {
    $datePart = date('dmY', strtotime($tanggal ?: date('Y-m-d')));
    $base = $prefix . '-' . $datePart;
    if (!empty($kode)) {
        $base .= '-' . preg_replace('/[^A-Za-z0-9]/', '', strtoupper($kode));
    }

    $sequence = 1;
    while (true) {
        $candidate = $base . '-' . str_pad((string) $sequence, 3, '0', STR_PAD_LEFT);
        $stmt = $pdo->prepare("SELECT 1 FROM {$table} WHERE nomor_transaksi = :no LIMIT 1");
        $stmt->execute([':no' => $candidate]);
        if (!$stmt->fetch()) {
            return $candidate;
        }
        $sequence++;
    }
}

if (empty($nomor_transaksi)) {
    $kodeBarang = null;
    if ($product_id > 0) {
        $stmtKode = $pdo->prepare("SELECT kode_barang FROM products WHERE id = :pid LIMIT 1");
        $stmtKode->execute([':pid' => $product_id]);
        $kodeBarang = $stmtKode->fetchColumn();
    }
    $nomor_transaksi = generateUniqueTransactionNumber($pdo, 'BK', $tanggal, $kodeBarang, 'stock_out');
}

if ($product_id <= 0 || $jumlah <= 0) {
    setFlash('danger', 'Pilih barang dan jumlah keluar wajib lebih dari 0.');
    header("Location: ../pages/barang_keluar.php");
    exit();
}

try {
    // Ambil data produk untuk pengecekan stok
    $stmtProd = $pdo->prepare("SELECT kode_barang, nama_barang, stok, satuan FROM products WHERE id = :pid LIMIT 1");
    $stmtProd->execute([':pid' => $product_id]);
    $prod = $stmtProd->fetch(PDO::FETCH_ASSOC);

    if (!$prod) {
        setFlash('danger', 'Barang tidak ditemukan.');
        header("Location: ../pages/barang_keluar.php");
        exit();
    }

    // Pengecekan stok sesuai kondisi
    if ($kondisi !== 'Rusak') {
        if ($prod['stok'] < $jumlah) {
            setFlash('danger', 'Stok barang tidak mencukupi.');
            header("Location: ../pages/barang_keluar.php");
            exit();
        }
    } else {
        // Cek stok rusak yang tersedia di tabel stok_rusak_barang
        $stmtCheckSR = $pdo->prepare("SELECT stok_rusak FROM stok_rusak_barang WHERE kode = :kode LIMIT 1");
        $stmtCheckSR->execute([':kode' => $prod['kode_barang']]);
        $stokRusakAwal = (int)$stmtCheckSR->fetchColumn();

        if ($stokRusakAwal < $jumlah) {
            setFlash('danger', "Stok barang rusak tidak mencukupi. Stok rusak saat ini: $stokRusakAwal");
            header("Location: ../pages/barang_keluar.php");
            exit();
        }
    }

    $pdo->beginTransaction();

    $stmtCheckNo = $pdo->prepare("SELECT 1 FROM stock_out WHERE nomor_transaksi = :no_tx LIMIT 1");
    $stmtCheckNo->execute([':no_tx' => $nomor_transaksi]);
    if ($stmtCheckNo->fetch()) {
        $kodeBarang = null;
        $stmtKode = $pdo->prepare("SELECT kode_barang FROM products WHERE id = :pid LIMIT 1");
        $stmtKode->execute([':pid' => $product_id]);
        $kodeBarang = $stmtKode->fetchColumn();
        $nomor_transaksi = generateUniqueTransactionNumber($pdo, 'BK', $tanggal, $kodeBarang, 'stock_out');
    }

    // 1. Insert transaction into stock_out (dengan status_pakai='Dipakai' bila kolom tersedia)
    $hasStatusCol = false;
    try { $hasStatusCol = $pdo->query("SHOW COLUMNS FROM stock_out LIKE 'status_pakai'")->rowCount() > 0; } catch (Exception $e) { $hasStatusCol = false; }
    $hasAlasanCol = false;
    try { $hasAlasanCol = $pdo->query("SHOW COLUMNS FROM stock_out LIKE 'alasan_rusak'")->rowCount() > 0; } catch (Exception $e) { $hasAlasanCol = false; }
    $hasCatatanCol = false;
    try { $hasCatatanCol = $pdo->query("SHOW COLUMNS FROM stock_out LIKE 'catatan_pemasangan'")->rowCount() > 0; } catch (Exception $e) { $hasCatatanCol = false; }

    if ($hasStatusCol) {
        // Build dynamic insert untuk kompatibilitas kolom tambahan
        $cols = "nomor_transaksi, tanggal, product_id, jumlah, tujuan, kondisi, keterangan, status_pakai, created_by";
        $vals = ":no_tx, :tgl, :pid, :jml, :tujuan, :kond, :ket, 'Dipakai', :uid";
        if ($hasAlasanCol) { $cols .= ", alasan_rusak"; $vals .= ", :alasan"; }
        if ($hasCatatanCol) { $cols .= ", catatan_pemasangan"; $vals .= ", :catatan"; }
        $stmtOut = $pdo->prepare("INSERT INTO stock_out ($cols) VALUES ($vals)");
        $params = [
            ':no_tx'  => $nomor_transaksi,
            ':tgl'    => $tanggal,
            ':pid'    => $product_id,
            ':jml'    => $jumlah,
            ':tujuan' => $tujuan,
            ':kond'   => $kondisi,
            ':ket'    => $keterangan,
            ':uid'    => $user_id
        ];
        if ($hasAlasanCol) $params[':alasan'] = $keterangan;
        if ($hasCatatanCol) $params[':catatan'] = $keterangan;
        $stmtOut->execute($params);
    } else {
        $stmtOut = $pdo->prepare("
            INSERT INTO stock_out (nomor_transaksi, tanggal, product_id, jumlah, tujuan, kondisi, keterangan, created_by)
            VALUES (:no_tx, :tgl, :pid, :jml, :tujuan, :kond, :ket, :uid)
        ");
        $stmtOut->execute([
            ':no_tx'  => $nomor_transaksi,
            ':tgl'    => $tanggal,
            ':pid'    => $product_id,
            ':jml'    => $jumlah,
            ':tujuan' => $tujuan,
            ':kond'   => $kondisi,
            ':ket'    => $keterangan,
            ':uid'    => $user_id
        ]);
    }

    // 2. Logika Pemisahan Pengurangan Stok
    if ($kondisi !== 'Rusak') {
        // Potong Stok Utama
        $stmtUp = $pdo->prepare("UPDATE products SET stok = stok - :jml WHERE id = :pid");
        $stmtUp->execute([
            ':jml' => $jumlah,
            ':pid' => $product_id
        ]);
    } else {
        // A. Potong Stok dari Jenis Barang Rusak
        $stmtUpSR = $pdo->prepare("
            UPDATE stok_rusak_barang 
            SET stok_rusak = stok_rusak - :jml 
            WHERE kode = :kode
        ");
        $stmtUpSR->execute([
            ':jml'  => $jumlah,
            ':kode' => $prod['kode_barang']
        ]);

        // B. Catat ke Tabel Riwayat Stok Rusak Keluar
        $stmtLogKeluar = $pdo->prepare("
            INSERT INTO stok_rusak_keluar (tanggal, info, kode, nama_barang, keterangan, jumlah) 
            VALUES (:tgl, 'Keluar', :kode, :nama, :ket, :jml)
        ");
        $stmtLogKeluar->execute([
            ':tgl'  => $tanggal,
            ':kode' => $prod['kode_barang'],
            ':nama' => $prod['nama_barang'],
            ':ket'  => $keterangan,
            ':jml'  => $jumlah
        ]);
    }

    $pdo->commit();
    setFlash('success', "Transaksi Barang Keluar [$nomor_transaksi] berhasil disimpan.");
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    if ($e->getCode() == 23000) {
        setFlash('danger', "Nomor transaksi '$nomor_transaksi' sudah digunakan.");
    } else {
        setFlash('danger', 'Gagal memproses barang keluar: ' . $e->getMessage());
    }
}

header("Location: ../pages/barang_keluar.php");
exit();