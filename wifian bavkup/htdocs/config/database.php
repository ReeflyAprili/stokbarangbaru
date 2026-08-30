<?php
// config/database.php
// Connection configuration for PT Wifian Solution Inventory System

define('DB_HOST', 'sql113.infinityfree.com');
define('DB_USER', 'if0_42584863');
define('DB_PASS', 'STOKBARANG2026');
define('DB_NAME', 'if0_42584863_testing');
define('DB_PORT', '3306');

function getDBConnection() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Attempt to connect without dbname to auto-create or show helpful guide
            try {
                $dsnNoDb = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=utf8mb4";
                $tmpPdo = new PDO($dsnNoDb, DB_USER, DB_PASS);
                $tmpPdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                
                // Reconnect to newly created DB
                $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);

                // Check if users table exists, if not auto seed database_inventory.sql
                $tableCheck = $pdo->query("SHOW TABLES LIKE 'users'")->rowCount();
                if ($tableCheck === 0) {
                    $sqlFile = __DIR__ . '/../database/database_inventory.sql';
                    if (file_exists($sqlFile)) {
                        $sqlContent = file_get_contents($sqlFile);
                        $pdo->exec($sqlContent);
                    }
                }
                // Ensure stock_out return feature columns exist (idempotent migration)
                try { ensureStockOutReturnColumns($pdo); } catch (Exception $e) { /* ignore migration error */ }
            } catch (PDOException $ex) {
                die("<div style='font-family: sans-serif; padding: 2rem; background: #fef2f2; border: 1px solid #fca5a5; color: #991b1b; border-radius: 8px; margin: 2rem;'>
                    <h2 style='margin-top:0;'>Koneksi Database Gagal</h2>
                    <p>Gagal terhubung ke MySQL Database server. Pastikan XAMPP/Laragon MySQL service sudah aktif dan konfigurasi di <code>config/database.php</code> sudah sesuai.</p>
                    <p><strong>Pesan Error:</strong> " . htmlspecialchars($ex->getMessage()) . "</p>
                </div>");
            }
        }
        // Ensure stock_out return feature columns exist for already-connected PDO too
        try { ensureStockOutReturnColumns($pdo); } catch (Exception $e) { /* ignore */ }
    }
    return $pdo;
}

/**
 * Idempotent migration for fitur "Riwayat Barang Keluar Sedang Dipakai"
 * Menambahkan kolom status_pakai, tanggal_kembali, dikembalikan_by, alasan_rusak, catatan_pemasangan jika belum ada
 */
function ensureStockOutReturnColumns(PDO $pdo) {
    // cek apakah tabel stock_out ada
    $exists = $pdo->query("SHOW TABLES LIKE 'stock_out'")->rowCount();
    if ($exists === 0) return;

    $cols = [];
    $stmt = $pdo->query("SHOW COLUMNS FROM stock_out");
    foreach ($stmt->fetchAll() as $c) { $cols[$c['Field']] = true; }

    $alterSql = [];
    if (!isset($cols['keterangan'])) $alterSql[] = "ADD COLUMN keterangan TEXT DEFAULT NULL";
    if (!isset($cols['alasan_rusak'])) $alterSql[] = "ADD COLUMN alasan_rusak TEXT DEFAULT NULL";
    if (!isset($cols['catatan_pemasangan'])) $alterSql[] = "ADD COLUMN catatan_pemasangan TEXT DEFAULT NULL";
    if (!isset($cols['status_pakai'])) $alterSql[] = "ADD COLUMN status_pakai ENUM('Dipakai','Dikembalikan') NOT NULL DEFAULT 'Dipakai'";
    if (!isset($cols['tanggal_kembali'])) $alterSql[] = "ADD COLUMN tanggal_kembali DATETIME DEFAULT NULL";
    if (!isset($cols['dikembalikan_by'])) $alterSql[] = "ADD COLUMN dikembalikan_by INT DEFAULT NULL";

    if (!empty($alterSql)) {
        $sql = "ALTER TABLE stock_out " . implode(", ", $alterSql);
        $pdo->exec($sql);
    }
    // Ensure FK for dikembalikan_by (ignore if already exists)
    // Tidak paksa FK agar tidak gagal di hosting dengan constraint ketat
}
