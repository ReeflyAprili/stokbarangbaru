<?php
// actions/export_action.php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

$jenis = $_GET['jenis'] ?? 'stok';
$periode = $_GET['periode'] ?? 'semua';
$tgl_mulai = $_GET['tgl_mulai'] ?? '';
$tgl_selesai = $_GET['tgl_selesai'] ?? '';
$format = $_GET['format'] ?? 'csv'; // csv / excel

$pdo = getDBConnection();
$settings = getStoreSettings();

// Build filename
$filename = "Laporan_" . strtoupper($jenis) . "_" . date('Ymd_His') . ".csv";

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');

// Insert BOM for Excel UTF-8 compatibility
fputs($output, $bom = chr(0xEF) . chr(0xBB) . chr(0xBF));

if ($jenis === 'stok') {
    fputcsv($output, ['LAPORAN STOK BARANG - ' . $settings['nama_toko']]);
    fputcsv($output, ['Tanggal Cetak: ' . date('d/m/Y H:i:s')]);
    fputcsv($output, []);
    fputcsv($output, ['No', 'Kode Barang', 'Nama Barang', 'Kategori', 'Satuan', 'Harga Beli (Rp)', 'Harga Jual (Rp)', 'Stok', 'Stok Minimum', 'Lokasi Rak', 'Status']);

    $rows = $pdo->query("
        SELECT p.*, c.nama_kategori 
        FROM products p 
        LEFT JOIN categories c ON p.kategori_id = c.id 
        ORDER BY p.kode_barang ASC
    ")->fetchAll();

    $no = 1;
    foreach ($rows as $r) {
        $stok = (int)$r['stok'];
        $min = (int)$r['stok_minimum'];
        $status = ($stok == 0) ? 'Habis' : (($stok <= $min) ? 'Menipis' : 'Aman');

        fputcsv($output, [
            $no++,
            $r['kode_barang'],
            $r['nama_barang'],
            $r['nama_kategori'] ?: 'Tanpa Kategori',
            $r['satuan'],
            $r['harga_beli'],
            $r['harga_jual'],
            $r['stok'],
            $r['stok_minimum'],
            $r['lokasi_rak'] ?: '-',
            $status
        ]);
    }
} elseif ($jenis === 'masuk') {
    fputcsv($output, ['LAPORAN BARANG MASUK - ' . $settings['nama_toko']]);
    fputcsv($output, ['Tanggal Cetak: ' . date('d/m/Y H:i:s')]);
    fputcsv($output, []);
    fputcsv($output, ['No', 'No. Transaksi', 'Tanggal', 'Kode Barang', 'Nama Barang', 'Jumlah', 'Harga Beli (Rp)', 'Total (Rp)', 'Keterangan', 'User']);

    $sql = "
        SELECT si.*, p.kode_barang, p.nama_barang, u.nama as nama_user 
        FROM stock_in si 
        JOIN products p ON si.product_id = p.id 
        LEFT JOIN users u ON si.created_by = u.id 
    ";
    $params = [];
    if (!empty($tgl_mulai) && !empty($tgl_selesai)) {
        $sql .= " WHERE si.tanggal BETWEEN :t1 AND :t2 ";
        $params[':t1'] = $tgl_mulai;
        $params[':t2'] = $tgl_selesai;
    }
    $sql .= " ORDER BY si.tanggal DESC, si.id DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    $no = 1;
    foreach ($rows as $r) {
        $total = $r['jumlah'] * $r['harga_beli'];
        fputcsv($output, [
            $no++,
            $r['nomor_transaksi'],
            date('d/m/Y', strtotime($r['tanggal'])),
            $r['kode_barang'],
            $r['nama_barang'],
            $r['jumlah'],
            $r['harga_beli'],
            $total,
            $r['keterangan'] ?: '-',
            $r['nama_user'] ?: 'System'
        ]);
    }
} elseif ($jenis === 'keluar') {
    fputcsv($output, ['LAPORAN BARANG KELUAR - ' . $settings['nama_toko']]);
    fputcsv($output, ['Tanggal Cetak: ' . date('d/m/Y H:i:s')]);
    fputcsv($output, []);
    fputcsv($output, ['No', 'No. Transaksi', 'Tanggal', 'Kode Barang', 'Nama Barang', 'Jumlah Keluar', 'Tujuan', 'Keterangan', 'User']);

    $sql = "
        SELECT so.*, p.kode_barang, p.nama_barang, u.nama as nama_user 
        FROM stock_out so 
        JOIN products p ON so.product_id = p.id 
        LEFT JOIN users u ON so.created_by = u.id 
    ";
    $params = [];
    if (!empty($tgl_mulai) && !empty($tgl_selesai)) {
        $sql .= " WHERE so.tanggal BETWEEN :t1 AND :t2 ";
        $params[':t1'] = $tgl_mulai;
        $params[':t2'] = $tgl_selesai;
    }
    $sql .= " ORDER BY so.tanggal DESC, so.id DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    $no = 1;
    foreach ($rows as $r) {
        fputcsv($output, [
            $no++,
            $r['nomor_transaksi'],
            date('d/m/Y', strtotime($r['tanggal'])),
            $r['kode_barang'],
            $r['nama_barang'],
            $r['jumlah'],
            $r['tujuan'],
            $r['keterangan'] ?: '-',
            $r['nama_user'] ?: 'System'
        ]);
    }
} elseif ($jenis === 'opname') {
    fputcsv($output, ['LAPORAN STOK OPNAME - ' . $settings['nama_toko']]);
    fputcsv($output, ['Tanggal Cetak: ' . date('d/m/Y H:i:s')]);
    fputcsv($output, []);
    fputcsv($output, ['No', 'Tanggal', 'Kode Barang', 'Nama Barang', 'Stok Sistem', 'Stok Fisik', 'Selisih', 'Status', 'Keterangan', 'User']);

    $rows = $pdo->query("
        SELECT op.*, p.kode_barang, p.nama_barang, u.nama as nama_user 
        FROM stock_opname op 
        JOIN products p ON op.product_id = p.id 
        LEFT JOIN users u ON op.created_by = u.id 
        ORDER BY op.tanggal DESC
    ")->fetchAll();

    $no = 1;
    foreach ($rows as $r) {
        $sel = (int)$r['selisih'];
        $status = ($sel === 0) ? 'Sesuai' : (($sel > 0) ? 'Lebih' : 'Kurang');
        fputcsv($output, [
            $no++,
            date('d/m/Y', strtotime($r['tanggal'])),
            $r['kode_barang'],
            $r['nama_barang'],
            $r['stok_sistem'],
            $r['stok_fisik'],
            $r['selisih'],
            $status,
            $r['keterangan'] ?: '-',
            $r['nama_user'] ?: 'System'
        ]);
    }
} elseif ($jenis === 'menipis') {
    fputcsv($output, ['LAPORAN STOK MENIPIS & HABIS - ' . $settings['nama_toko']]);
    fputcsv($output, ['Tanggal Cetak: ' . date('d/m/Y H:i:s')]);
    fputcsv($output, []);
    fputcsv($output, ['No', 'Kode Barang', 'Nama Barang', 'Kategori', 'Satuan', 'Stok', 'Stok Minimum', 'Status']);

    $rows = $pdo->query("
        SELECT p.*, c.nama_kategori 
        FROM products p 
        LEFT JOIN categories c ON p.kategori_id = c.id 
        WHERE p.stok <= p.stok_minimum 
        ORDER BY p.stok ASC
    ")->fetchAll();

    $no = 1;
    foreach ($rows as $r) {
        $status = ($r['stok'] == 0) ? 'Habis' : 'Menipis';
        fputcsv($output, [
            $no++,
            $r['kode_barang'],
            $r['nama_barang'],
            $r['nama_kategori'] ?: 'Tanpa Kategori',
            $r['satuan'],
            $r['stok'],
            $r['stok_minimum'],
            $status
        ]);
    }
} elseif ($jenis === 'nilai') {
    fputcsv($output, ['LAPORAN NILAI PERSEDIAAN INVENTARIS - ' . $settings['nama_toko']]);
    fputcsv($output, ['Tanggal Cetak: ' . date('d/m/Y H:i:s')]);
    fputcsv($output, []);
    fputcsv($output, ['No', 'Kode Barang', 'Nama Barang', 'Kategori', 'Satuan', 'Stok', 'Harga Beli (Rp)', 'Total Nilai Persediaan (Rp)']);

    $rows = $pdo->query("
        SELECT p.*, c.nama_kategori 
        FROM products p 
        LEFT JOIN categories c ON p.kategori_id = c.id 
        ORDER BY p.nama_barang ASC
    ")->fetchAll();

    $no = 1;
    $grandTotal = 0;
    foreach ($rows as $r) {
        $nilai = $r['stok'] * $r['harga_beli'];
        $grandTotal += $nilai;
        fputcsv($output, [
            $no++,
            $r['kode_barang'],
            $r['nama_barang'],
            $r['nama_kategori'] ?: 'Tanpa Kategori',
            $r['satuan'],
            $r['stok'],
            $r['harga_beli'],
            $nilai
        ]);
    }
    fputcsv($output, []);
    fputcsv($output, ['', '', '', '', '', '', 'TOTAL NILAI PERSEDIAAN:', $grandTotal]);
}

fclose($output);
exit();
