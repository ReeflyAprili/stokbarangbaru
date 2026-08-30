<?php
// actions/barang_masuk_action.php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../pages/barang_masuk.php");
    exit();
}

$csrf_token = $_POST['csrf_token'] ?? '';
if (!verifyCSRFToken($csrf_token)) {
    setFlash('danger', 'Sesi invalid (CSRF Token error).');
    header("Location: ../pages/barang_masuk.php");
    exit();
}

$nomor_transaksi = trim($_POST['nomor_transaksi'] ?? '');
$tanggal         = trim($_POST['tanggal'] ?? date('Y-m-d'));
$product_id      = (int)($_POST['product_id'] ?? 0);
$jumlah          = (int)($_POST['jumlah'] ?? 0);
$kondisi         = trim($_POST['kondisi'] ?? 'Tidak Rusak');
$alasan_rusak    = trim($_POST['alasan_rusak'] ?? '');
$keterangan      = trim($_POST['catatan_vendor'] ?? '');
$user_id         = $_SESSION['user_id'] ?? null;

$pdo = getDBConnection();

if ($kondisi === 'Rusak') {
    $keterangan = $alasan_rusak;
    if (trim($keterangan) === '') {
        setFlash('danger', 'Alasan barang rusak wajib diisi.');
        header("Location: ../pages/barang_masuk.php");
        exit();
    }
}

function generateUniqueTransactionNumber(PDO $pdo, string $prefix, string $tanggal, ?string $kode = null, string $table = 'stock_in'): string {
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
    $nomor_transaksi = generateUniqueTransactionNumber($pdo, 'BM', $tanggal, $kodeBarang, 'stock_in');
}

if ($product_id <= 0 || $jumlah <= 0) {
    setFlash('danger', 'Pilih barang dan jumlah masuk wajib lebih dari 0.');
    header("Location: ../pages/barang_masuk.php");
    exit();
}

try {
    $pdo->beginTransaction();

    $stmtCheckNo = $pdo->prepare("SELECT 1 FROM stock_in WHERE nomor_transaksi = :no_tx LIMIT 1");
    $stmtCheckNo->execute([':no_tx' => $nomor_transaksi]);
    if ($stmtCheckNo->fetch()) {
        $kodeBarang = null;
        $stmtKode = $pdo->prepare("SELECT kode_barang FROM products WHERE id = :pid LIMIT 1");
        $stmtKode->execute([':pid' => $product_id]);
        $kodeBarang = $stmtKode->fetchColumn();
        $nomor_transaksi = generateUniqueTransactionNumber($pdo, 'BM', $tanggal, $kodeBarang, 'stock_in');
    }

    // 1. Insert transaction into stock_in (Tanpa harga_beli)
    $stmtIn = $pdo->prepare("
        INSERT INTO stock_in (nomor_transaksi, tanggal, product_id, jumlah, kondisi, keterangan, created_by)
        VALUES (:no_tx, :tgl, :pid, :jml, :kond, :ket, :uid)
    ");
    $stmtIn->execute([
        ':no_tx' => $nomor_transaksi,
        ':tgl'   => $tanggal,
        ':pid'   => $product_id,
        ':jml'   => $jumlah,
        ':kond'  => $kondisi,
        ':ket'   => $keterangan,
        ':uid'   => $user_id
    ]);

    // 2. Logika Pemisahan Stok
    if ($kondisi !== 'Rusak') {
        // Jika Tidak Rusak -> Tambah Stok Utama (Tanpa update harga_beli)
        $stmtUp = $pdo->prepare("
            UPDATE products 
            SET stok = stok + :jml
            WHERE id = :pid
        ");
        $stmtUp->execute([
            ':jml' => $jumlah,
            ':pid' => $product_id
        ]);
    } else {
        // Jika Rusak -> Ambil Detail Produk Terlebih Dahulu
        $stmtProduct = $pdo->prepare("
            SELECT p.kode_barang, p.nama_barang, c.nama_kategori 
            FROM products p
            LEFT JOIN categories c ON p.kategori_id = c.id
            WHERE p.id = :pid LIMIT 1
        ");
        $stmtProduct->execute([':pid' => $product_id]);
        $pDetail = $stmtProduct->fetch(PDO::FETCH_ASSOC);

        $kodeBarang   = $pDetail['kode_barang'] ?? 'UNKNOWN';
        $namaBarang   = $pDetail['nama_barang'] ?? 'Barang Tanpa Nama';
        $namaKategori = $pDetail['nama_kategori'] ?? 'Umum';

        // A. Tambah/Update Stok ke Jenis Barang Rusak (Tanpa harga_beli)
        $stmtCheckSR = $pdo->prepare("SELECT id FROM stok_rusak_barang WHERE kode = :kode LIMIT 1");
        $stmtCheckSR->execute([':kode' => $kodeBarang]);

        if ($stmtCheckSR->fetch()) {
            $stmtUpdateSR = $pdo->prepare("
                UPDATE stok_rusak_barang 
                SET stok_rusak = stok_rusak + :jml 
                WHERE kode = :kode
            ");
            $stmtUpdateSR->execute([
                ':jml'  => $jumlah,
                ':kode' => $kodeBarang
            ]);
        } else {
            $stmtInsertSR = $pdo->prepare("
                INSERT INTO stok_rusak_barang (kode, nama_barang, kategori, stok_rusak, min_stok, lokasi_rak, status) 
                VALUES (:kode, :nama, :kat, :jml, '5 Pcs', 'LEMARI 1', 'Rusak')
            ");
            $stmtInsertSR->execute([
                ':kode' => $kodeBarang,
                ':nama' => $namaBarang,
                ':kat'  => $namaKategori,
                ':jml'  => $jumlah
            ]);
        }

        // B. Catat ke Tabel Riwayat Stok Rusak Masuk
        $stmtLogMasuk = $pdo->prepare("
            INSERT INTO stok_rusak_masuk (tanggal, info, kode, nama_barang, alasan_rusak, jumlah) 
            VALUES (:tgl, 'Masuk', :kode, :nama, :alasan, :jml)
        ");
        $stmtLogMasuk->execute([
            ':tgl'    => $tanggal,
            ':kode'   => $kodeBarang,
            ':nama'   => $namaBarang,
            ':alasan' => $keterangan,
            ':jml'    => $jumlah
        ]);
    }

    $pdo->commit();

    if ($kondisi === 'Rusak') {
        setFlash('success', "Transaksi barang rusak [$nomor_transaksi] berhasil disimpan dan dicatat di Riwayat Stok Rusak.");
    } else {
        setFlash('success', "Transaksi Barang Masuk [$nomor_transaksi] berhasil disimpan dan stok telah bertambah $jumlah unit.");
    }
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    if ($e->getCode() == 23000) {
        setFlash('danger', "Nomor transaksi '$nomor_transaksi' sudah digunakan.");
    } else {
        setFlash('danger', 'Gagal memproses barang masuk: ' . $e->getMessage());
    }
}

header("Location: ../pages/barang_masuk.php");
exit();