<?php
// pages/laporan.php
$pageTitle = 'Laporan Inventory';
require_once __DIR__ . '/../includes/header.php';
$pdo = getDBConnection();
$settings = getStoreSettings();

// Filter parameters
$jenis = $_GET['jenis'] ?? 'stok'; // stok, masuk, keluar, opname, menipis, nilai
$periode = $_GET['periode'] ?? 'semua'; // harian, mingguan, bulanan, tahunan, rentang, semua
$tgl_mulai = $_GET['tgl_mulai'] ?? date('Y-m-01');
$tgl_selesai = $_GET['tgl_selesai'] ?? date('Y-m-d');

// Date filter condition generator
function getDateCondition($tableAlias, $periode, $tgl_mulai, $tgl_selesai) {
    if ($periode === 'harian') {
        return " DATE({$tableAlias}.tanggal) = CURDATE() ";
    } elseif ($periode === 'mingguan') {
        return " {$tableAlias}.tanggal >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) ";
    } elseif ($periode === 'bulanan') {
        return " DATE_FORMAT({$tableAlias}.tanggal, '%Y-%m') = '" . date('Y-m') . "' ";
    } elseif ($periode === 'tahunan') {
        return " YEAR({$tableAlias}.tanggal) = '" . date('Y') . "' ";
    } elseif ($periode === 'rentang' && !empty($tgl_mulai) && !empty($tgl_selesai)) {
        return " {$tableAlias}.tanggal BETWEEN '$tgl_mulai' AND '$tgl_selesai' ";
    }
    return " 1=1 ";
}

// Fetch report dataset based on type
$reportData = [];
$reportTitle = 'Laporan Stok Barang';

if ($jenis === 'stok') {
    $reportTitle = 'Laporan Stok Barang Saat Ini';
    $reportData = $pdo->query("
        SELECT p.*, c.nama_kategori 
        FROM products p 
        LEFT JOIN categories c ON p.kategori_id = c.id 
        ORDER BY p.kode_barang ASC
    ")->fetchAll();
} elseif ($jenis === 'masuk') {
    $reportTitle = 'Laporan Transaksi Barang Masuk';
    $cond = getDateCondition('si', $periode, $tgl_mulai, $tgl_selesai);
    $reportData = $pdo->query("
        SELECT si.*, p.kode_barang, p.nama_barang, p.satuan, u.nama as nama_user
        FROM stock_in si
        JOIN products p ON si.product_id = p.id
        LEFT JOIN users u ON si.created_by = u.id
        WHERE $cond
        ORDER BY si.tanggal DESC, si.id DESC
    ")->fetchAll();
} elseif ($jenis === 'keluar') {
    $reportTitle = 'Laporan Transaksi Barang Keluar';
    $cond = getDateCondition('so', $periode, $tgl_mulai, $tgl_selesai);
    $reportData = $pdo->query("
        SELECT so.*, p.kode_barang, p.nama_barang, p.satuan, u.nama as nama_user
        FROM stock_out so
        JOIN products p ON so.product_id = p.id
        LEFT JOIN users u ON so.created_by = u.id
        WHERE $cond
        ORDER BY so.tanggal DESC, so.id DESC
    ")->fetchAll();
} elseif ($jenis === 'opname') {
    $reportTitle = 'Laporan Hasil Stok Opname';
    $cond = getDateCondition('op', $periode, $tgl_mulai, $tgl_selesai);
    $reportData = $pdo->query("
        SELECT op.*, p.kode_barang, p.nama_barang, p.satuan, u.nama as nama_user
        FROM stock_opname op
        JOIN products p ON op.product_id = p.id
        LEFT JOIN users u ON op.created_by = u.id
        WHERE $cond
        ORDER BY op.tanggal DESC, op.id DESC
    ")->fetchAll();
} elseif ($jenis === 'menipis') {
    $reportTitle = 'Laporan Produk Stok Menipis & Habis';
    $reportData = $pdo->query("
        SELECT p.*, c.nama_kategori 
        FROM products p 
        LEFT JOIN categories c ON p.kategori_id = c.id 
        WHERE p.stok <= p.stok_minimum
        ORDER BY p.stok ASC
    ")->fetchAll();
} elseif ($jenis === 'nilai') {
    $reportTitle = 'Laporan Total Nilai Persediaan Inventaris';
    $reportData = $pdo->query("
        SELECT p.*, c.nama_kategori, (p.stok * p.harga_beli) as total_nilai 
        FROM products p 
        LEFT JOIN categories c ON p.kategori_id = c.id 
        ORDER BY total_nilai DESC
    ")->fetchAll();
}
?>

<!-- Print Sheet Header (Only visible on print/PDF export) -->
<div class="print-only mb-6 pb-4 border-b-2 border-slate-900">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
            <img src="../assets/images/logo.svg" alt="Logo PT Wifian Solution" class="h-14 w-auto">
            <div>
                <h1 class="text-2xl font-black text-slate-900 uppercase tracking-tight"><?= e($settings['nama_toko']) ?></h1>
                <p class="text-xs text-slate-600"><?= e($settings['alamat']) ?> | Telp: <?= e($settings['telepon']) ?> | Email: <?= e($settings['email']) ?></p>
            </div>
        </div>
        <div class="text-right">
            <h2 class="text-lg font-bold text-sky-800 uppercase"><?= e($reportTitle) ?></h2>
            <p class="text-xs text-slate-500">Tanggal Dicetak: <?= date('d/m/Y H:i:s') ?></p>
        </div>
    </div>
</div>

<!-- Controls & Filters Card (Hidden on print) -->
<div class="no-print bg-white p-6 rounded-2xl border border-slate-200/80 shadow-subtle mb-6">
    <form action="laporan.php" method="GET" class="space-y-4">
        
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            
            <!-- Jenis Laporan -->
            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Jenis Laporan</label>
                <select name="jenis" onchange="this.form.submit()" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-sky-500 text-sm font-semibold text-slate-800">
                    <option value="stok" <?= $jenis === 'stok' ? 'selected' : '' ?>>Laporan Stok Barang</option>
                    <option value="masuk" <?= $jenis === 'masuk' ? 'selected' : '' ?>>Laporan Barang Masuk</option>
                    <option value="keluar" <?= $jenis === 'keluar' ? 'selected' : '' ?>>Laporan Barang Keluar</option>
                    <option value="opname" <?= $jenis === 'opname' ? 'selected' : '' ?>>Laporan Stok Opname</option>
                    <option value="menipis" <?= $jenis === 'menipis' ? 'selected' : '' ?>>Laporan Stok Menipis</option>
                    <option value="nilai" <?= $jenis === 'nilai' ? 'selected' : '' ?>>Laporan Nilai Persediaan</option>
                </select>
            </div>

            <!-- Periode Filter -->
            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Periode Waktu</label>
                <select name="periode" onchange="toggleDateInputs(this.value); this.form.submit()" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-sky-500 text-sm">
                    <option value="semua" <?= $periode === 'semua' ? 'selected' : '' ?>>Semua Data</option>
                    <option value="harian" <?= $periode === 'harian' ? 'selected' : '' ?>>Hari Ini (Harian)</option>
                    <option value="mingguan" <?= $periode === 'mingguan' ? 'selected' : '' ?>>7 Hari Terakhir (Mingguan)</option>
                    <option value="bulanan" <?= $periode === 'bulanan' ? 'selected' : '' ?>>Bulan Ini (Bulanan)</option>
                    <option value="tahunan" <?= $periode === 'tahunan' ? 'selected' : '' ?>>Tahun Ini (Tahunan)</option>
                    <option value="rentang" <?= $periode === 'rentang' ? 'selected' : '' ?>>Custom Rentang Tanggal</option>
                </select>
            </div>

            <!-- Custom Date Range -->
            <div id="dateRangeBox" class="sm:col-span-2 lg:col-span-1 grid grid-cols-2 gap-2 <?= $periode === 'rentang' ? '' : 'hidden' ?>">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Mulai</label>
                    <input type="date" name="tgl_mulai" value="<?= e($tgl_mulai) ?>" class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Selesai</label>
                    <input type="date" name="tgl_selesai" value="<?= e($tgl_selesai) ?>" class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs">
                </div>
            </div>

        </div>

        <div class="flex flex-wrap items-center justify-between gap-3 pt-3 border-t border-slate-100">
            <button type="submit" class="px-4 py-2 rounded-xl bg-slate-800 text-white text-xs font-semibold hover:bg-slate-900 transition-colors">
                <i class="fa-solid fa-filter mr-1"></i> Terapkan Filter
            </button>

            <!-- Export Buttons -->
            <div class="flex items-center gap-2">
                <button type="button" onclick="triggerPrint()" class="px-4 py-2 rounded-xl bg-sky-600 hover:bg-sky-700 text-white text-xs font-bold shadow-md shadow-sky-600/20 transition-all flex items-center gap-1.5">
                    <i class="fa-solid fa-print"></i>
                    <span>Print / Cetak</span>
                </button>

                <button type="button" onclick="triggerPrint()" class="px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold shadow-md shadow-rose-600/20 transition-all flex items-center gap-1.5">
                    <i class="fa-solid fa-file-pdf"></i>
                    <span>Export PDF</span>
                </button>

                <a href="../actions/export_action.php?jenis=<?= e($jenis) ?>&periode=<?= e($periode) ?>&tgl_mulai=<?= e($tgl_mulai) ?>&tgl_selesai=<?= e($tgl_selesai) ?>" class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-md shadow-emerald-600/20 transition-all flex items-center gap-1.5">
                    <i class="fa-solid fa-file-excel"></i>
                    <span>Export Excel (CSV)</span>
                </a>
            </div>
        </div>

    </form>
</div>

<!-- Report Content Table Card -->
<div class="bg-white rounded-2xl border border-slate-200/80 shadow-subtle overflow-hidden">
    
    <div class="p-5 border-b border-slate-100 flex items-center justify-between">
        <div>
            <h3 class="text-lg font-bold text-slate-800"><?= e($reportTitle) ?></h3>
            <p class="text-xs text-slate-500">Hasil filter: Periode <?= e(strtoupper($periode)) ?></p>
        </div>
        <span class="text-xs font-bold text-slate-500 bg-slate-100 px-3 py-1 rounded-full">
            Total Record: <?= count($reportData) ?>
        </span>
    </div>

    <div class="overflow-x-auto">
        
        <?php if ($jenis === 'stok' || $jenis === 'menipis'): ?>
            <table class="w-full text-left text-sm text-slate-600">
                <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500 font-semibold border-b border-slate-200/80">
                    <tr>
                        <th class="px-5 py-3.5">Kode</th>
                        <th class="px-5 py-3.5">Nama Barang</th>
                        <th class="px-5 py-3.5">Kategori</th>
                        <th class="px-5 py-3.5 text-right">Harga Beli</th>
                        <th class="px-5 py-3.5 text-center">Stok</th>
                        <th class="px-5 py-3.5 text-center">Minimum</th>
                        <th class="px-5 py-3.5">Rak</th>
                        <th class="px-5 py-3.5">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (empty($reportData)): ?>
                        <tr><td colspan="8" class="px-5 py-8 text-center text-slate-400">Tidak ada data ditemukan.</td></tr>
                    <?php else: ?>
                        <?php foreach ($reportData as $row): ?>
                            <tr class="hover:bg-slate-50/80 transition-colors">
                                <td class="px-5 py-3.5 font-mono text-xs font-bold text-slate-800"><?= e($row['kode_barang']) ?></td>
                                <td class="px-5 py-3.5 font-semibold text-slate-800"><?= e($row['nama_barang']) ?></td>
                                <td class="px-5 py-3.5 text-xs text-slate-600"><?= e($row['nama_kategori'] ?: '-') ?></td>
                                <td class="px-5 py-3.5 text-right font-mono">Rp <?= number_format($row['harga_beli']) ?></td>
                                <td class="px-5 py-3.5 text-center font-mono font-bold text-slate-900"><?= number_format($row['stok']) ?> <?= e($row['satuan']) ?></td>
                                <td class="px-5 py-3.5 text-center font-mono text-slate-400"><?= number_format($row['stok_minimum']) ?></td>
                                <td class="px-5 py-3.5 text-xs"><?= e($row['lokasi_rak'] ?: '-') ?></td>
                                <td class="px-5 py-3.5">
                                    <?php if ($row['stok'] == 0): ?>
                                        <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-rose-100 text-rose-800">Habis</span>
                                    <?php elseif ($row['stok'] <= $row['stok_minimum']): ?>
                                        <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-amber-100 text-amber-800">Menipis</span>
                                    <?php else: ?>
                                        <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-emerald-100 text-emerald-800">Aman</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

        <?php elseif ($jenis === 'masuk'): ?>
            <table class="w-full text-left text-sm text-slate-600">
                <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500 font-semibold border-b border-slate-200/80">
                    <tr>
                        <th class="px-5 py-3.5">No. Transaksi</th>
                        <th class="px-5 py-3.5">Tanggal</th>
                        <th class="px-5 py-3.5">Kode</th>
                        <th class="px-5 py-3.5">Nama Barang</th>
                        <th class="px-5 py-3.5 text-right">Jumlah</th>
                        <th class="px-5 py-3.5 text-right">Harga Beli</th>
                        <th class="px-5 py-3.5 text-right">Total (Rp)</th>
                        <th class="px-5 py-3.5">User</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (empty($reportData)): ?>
                        <tr><td colspan="8" class="px-5 py-8 text-center text-slate-400">Tidak ada data transaksi masuk.</td></tr>
                    <?php else: ?>
                        <?php $grandTotal = 0; ?>
                        <?php foreach ($reportData as $row): ?>
                            <?php $tot = $row['jumlah'] * $row['harga_beli']; $grandTotal += $tot; ?>
                            <tr class="hover:bg-slate-50/80 transition-colors">
                                <td class="px-5 py-3.5 font-mono text-xs font-bold text-sky-700"><?= e($row['nomor_transaksi']) ?></td>
                                <td class="px-5 py-3.5 text-xs"><?= date('d/m/Y', strtotime($row['tanggal'])) ?></td>
                                <td class="px-5 py-3.5 font-mono text-xs text-slate-500"><?= e($row['kode_barang']) ?></td>
                                <td class="px-5 py-3.5 font-semibold text-slate-800"><?= e($row['nama_barang']) ?></td>
                                <td class="px-5 py-3.5 text-right font-mono font-bold text-emerald-600">+<?= number_format($row['jumlah']) ?> <?= e($row['satuan']) ?></td>
                                <td class="px-5 py-3.5 text-right font-mono">Rp <?= number_format($row['harga_beli']) ?></td>
                                <td class="px-5 py-3.5 text-right font-mono font-bold text-slate-800">Rp <?= number_format($tot) ?></td>
                                <td class="px-5 py-3.5 text-xs"><?= e($row['nama_user'] ?: 'System') ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <tr class="bg-slate-100 font-bold text-slate-800">
                            <td colspan="6" class="px-5 py-3 text-right">GRAND TOTAL PEMBELIAN:</td>
                            <td class="px-5 py-3 text-right font-mono text-emerald-700 text-base">Rp <?= number_format($grandTotal) ?></td>
                            <td></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

        <?php elseif ($jenis === 'keluar'): ?>
            <table class="w-full text-left text-sm text-slate-600">
                <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500 font-semibold border-b border-slate-200/80">
                    <tr>
                        <th class="px-5 py-3.5">No. Transaksi</th>
                        <th class="px-5 py-3.5">Tanggal</th>
                        <th class="px-5 py-3.5">Kode</th>
                        <th class="px-5 py-3.5">Nama Barang</th>
                        <th class="px-5 py-3.5 text-right">Jumlah Keluar</th>
                        <th class="px-5 py-3.5">Tujuan</th>
                        <th class="px-5 py-3.5">User</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (empty($reportData)): ?>
                        <tr><td colspan="7" class="px-5 py-8 text-center text-slate-400">Tidak ada data transaksi keluar.</td></tr>
                    <?php else: ?>
                        <?php foreach ($reportData as $row): ?>
                            <tr class="hover:bg-slate-50/80 transition-colors">
                                <td class="px-5 py-3.5 font-mono text-xs font-bold text-amber-700"><?= e($row['nomor_transaksi']) ?></td>
                                <td class="px-5 py-3.5 text-xs"><?= date('d/m/Y', strtotime($row['tanggal'])) ?></td>
                                <td class="px-5 py-3.5 font-mono text-xs text-slate-500"><?= e($row['kode_barang']) ?></td>
                                <td class="px-5 py-3.5 font-semibold text-slate-800"><?= e($row['nama_barang']) ?></td>
                                <td class="px-5 py-3.5 text-right font-mono font-bold text-amber-600">-<?= number_format($row['jumlah']) ?> <?= e($row['satuan']) ?></td>
                                <td class="px-5 py-3.5 text-xs font-medium text-slate-700"><?= e($row['tujuan']) ?></td>
                                <td class="px-5 py-3.5 text-xs"><?= e($row['nama_user'] ?: 'System') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

        <?php elseif ($jenis === 'opname'): ?>
            <table class="w-full text-left text-sm text-slate-600">
                <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500 font-semibold border-b border-slate-200/80">
                    <tr>
                        <th class="px-5 py-3.5">Tanggal</th>
                        <th class="px-5 py-3.5">Kode</th>
                        <th class="px-5 py-3.5">Nama Barang</th>
                        <th class="px-5 py-3.5 text-center">Stok Sistem</th>
                        <th class="px-5 py-3.5 text-center">Stok Fisik</th>
                        <th class="px-5 py-3.5 text-center">Selisih</th>
                        <th class="px-5 py-3.5">Status</th>
                        <th class="px-5 py-3.5">User</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (empty($reportData)): ?>
                        <tr><td colspan="8" class="px-5 py-8 text-center text-slate-400">Tidak ada data stok opname.</td></tr>
                    <?php else: ?>
                        <?php foreach ($reportData as $row): ?>
                            <?php
                                $sel = (int)$row['selisih'];
                                $stLbl = ($sel === 0) ? 'Sesuai' : (($sel > 0) ? 'Lebih' : 'Kurang');
                            ?>
                            <tr class="hover:bg-slate-50/80 transition-colors">
                                <td class="px-5 py-3.5 text-xs"><?= date('d/m/Y', strtotime($row['tanggal'])) ?></td>
                                <td class="px-5 py-3.5 font-mono text-xs font-bold text-slate-700"><?= e($row['kode_barang']) ?></td>
                                <td class="px-5 py-3.5 font-semibold text-slate-800"><?= e($row['nama_barang']) ?></td>
                                <td class="px-5 py-3.5 text-center font-mono"><?= number_format($row['stok_sistem']) ?></td>
                                <td class="px-5 py-3.5 text-center font-mono font-bold"><?= number_format($row['stok_fisik']) ?></td>
                                <td class="px-5 py-3.5 text-center font-mono font-bold <?= $sel === 0 ? 'text-emerald-600' : ($sel > 0 ? 'text-blue-600' : 'text-rose-600') ?>">
                                    <?= ($sel > 0 ? '+' : '') . number_format($sel) ?>
                                </td>
                                <td class="px-5 py-3.5 text-xs font-bold"><?= $stLbl ?></td>
                                <td class="px-5 py-3.5 text-xs"><?= e($row['nama_user'] ?: 'System') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

        <?php elseif ($jenis === 'nilai'): ?>
            <table class="w-full text-left text-sm text-slate-600">
                <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500 font-semibold border-b border-slate-200/80">
                    <tr>
                        <th class="px-5 py-3.5">Kode</th>
                        <th class="px-5 py-3.5">Nama Barang</th>
                        <th class="px-5 py-3.5">Kategori</th>
                        <th class="px-5 py-3.5 text-center">Stok Unit</th>
                        <th class="px-5 py-3.5 text-right">Harga Beli</th>
                        <th class="px-5 py-3.5 text-right">Total Nilai Inventory (Rp)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (empty($reportData)): ?>
                        <tr><td colspan="6" class="px-5 py-8 text-center text-slate-400">Tidak ada data persediaan.</td></tr>
                    <?php else: ?>
                        <?php $grandValue = 0; ?>
                        <?php foreach ($reportData as $row): ?>
                            <?php $val = (float)$row['total_nilai']; $grandValue += $val; ?>
                            <tr class="hover:bg-slate-50/80 transition-colors">
                                <td class="px-5 py-3.5 font-mono text-xs font-bold text-slate-800"><?= e($row['kode_barang']) ?></td>
                                <td class="px-5 py-3.5 font-semibold text-slate-800"><?= e($row['nama_barang']) ?></td>
                                <td class="px-5 py-3.5 text-xs text-slate-600"><?= e($row['nama_kategori'] ?: '-') ?></td>
                                <td class="px-5 py-3.5 text-center font-mono font-bold text-slate-900"><?= number_format($row['stok']) ?> <?= e($row['satuan']) ?></td>
                                <td class="px-5 py-3.5 text-right font-mono">Rp <?= number_format($row['harga_beli']) ?></td>
                                <td class="px-5 py-3.5 text-right font-mono font-extrabold text-sky-700">Rp <?= number_format($val) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <tr class="bg-sky-50 font-bold text-sky-900">
                            <td colspan="5" class="px-5 py-4 text-right uppercase text-xs tracking-wider">TOTAL NILAI ASET INVENTARIS:</td>
                            <td class="px-5 py-4 text-right font-mono text-lg text-sky-900 font-black">Rp <?= number_format($grandValue) ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        <?php endif; ?>

    </div>
</div>

<script>
function toggleDateInputs(val) {
    const box = document.getElementById('dateRangeBox');
    if (val === 'rentang') {
        box.classList.remove('hidden');
    } else {
        box.classList.add('hidden');
    }
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
