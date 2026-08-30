<?php
// pages/dashboard.php
$pageTitle = 'Dashboard Inventory';
require_once __DIR__ . '/../includes/header.php';
$pdo = getDBConnection();

// Pastikan migrasi kolom status_pakai sudah ada (fitur Riwayat Sedang Dipakai)
try { if (function_exists('ensureStockOutReturnColumns')) ensureStockOutReturnColumns($pdo); } catch (Exception $e) {}

// Fetch Stat Cards Data
$totalProduk = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn() ?: 0;
$totalStok = $pdo->query("SELECT SUM(stok) FROM products")->fetchColumn() ?: 0;
$totalMasuk = $pdo->query("SELECT SUM(jumlah) FROM stock_in WHERE kondisi <> 'Rusak'")->fetchColumn() ?: 0;
$totalKeluar = $pdo->query("SELECT SUM(jumlah) FROM stock_out WHERE kondisi <> 'Rusak'")->fetchColumn() ?: 0;
$totalKategori = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn() ?: 0;
$stokMenipis = $pdo->query("SELECT COUNT(*) FROM products WHERE stok <= stok_minimum AND stok > 0")->fetchColumn() ?: 0;
$stokHabis = $pdo->query("SELECT COUNT(*) FROM products WHERE stok = 0")->fetchColumn() ?: 0;
$stokAman = $pdo->query("SELECT COUNT(*) FROM products WHERE stok > stok_minimum")->fetchColumn() ?: 0;
try {
    $hasStatusCol = $pdo->query("SHOW COLUMNS FROM stock_out LIKE 'status_pakai'")->rowCount() > 0;
    if ($hasStatusCol) {
        $totalDipakai = $pdo->query("SELECT COUNT(*) FROM stock_out WHERE status_pakai='Dipakai'")->fetchColumn() ?: 0;
        $totalDipakaiUnit = $pdo->query("SELECT SUM(jumlah) FROM stock_out WHERE status_pakai='Dipakai'")->fetchColumn() ?: 0;
    } else {
        $totalDipakai = $pdo->query("SELECT COUNT(*) FROM stock_out")->fetchColumn() ?: 0;
        $totalDipakaiUnit = $pdo->query("SELECT SUM(jumlah) FROM stock_out")->fetchColumn() ?: 0;
    }
} catch (Exception $e) { $totalDipakai = 0; $totalDipakaiUnit = 0; }

// Fetch Monthly Bar Chart Data (Last 6 Months)
$months = [];
$dataMasuk = [];
$dataKeluar = [];

for ($i = 5; $i >= 0; $i--) {
    $monthKey = date('Y-m', strtotime("-$i months"));
    $monthLabel = date('M Y', strtotime("-$i months"));
    $months[] = $monthLabel;

    $stmtM = $pdo->prepare("SELECT SUM(jumlah) FROM stock_in WHERE kondisi <> 'Rusak' AND DATE_FORMAT(tanggal, '%Y-%m') = :m");
    $stmtM->execute([':m' => $monthKey]);
    $dataMasuk[] = (int)($stmtM->fetchColumn() ?: 0);

    $stmtK = $pdo->prepare("SELECT SUM(jumlah) FROM stock_out WHERE kondisi <> 'Rusak' AND DATE_FORMAT(tanggal, '%Y-%m') = :m");
    $stmtK->execute([':m' => $monthKey]);
    $dataKeluar[] = (int)($stmtK->fetchColumn() ?: 0);
}

// Fetch Category Pie Chart Data
$catQuery = $pdo->query("
    SELECT c.nama_kategori, COALESCE(SUM(p.stok), 0) as total_stok
    FROM categories c
    LEFT JOIN products p ON c.id = p.kategori_id
    GROUP BY c.id, c.nama_kategori
    ORDER BY total_stok DESC
");
$catLabels = [];
$catData = [];
while ($row = $catQuery->fetch()) {
    $catLabels[] = $row['nama_kategori'];
    $catData[] = (int)$row['total_stok'];
}

// Fetch 10 Latest Transactions from activity_logs (fallback jika tabel tidak ada)
$recentTransactions = [];
try {
    $recentTransactionsQuery = $pdo->query("
        SELECT * 
        FROM activity_logs 
        ORDER BY created_at DESC, id DESC 
        LIMIT 10
    ");
    while ($t = $recentTransactionsQuery->fetch()) {
        $recentTransactions[] = $t;
    }
} catch (Exception $e) {
    // Fallback: ambil dari stock_out/stock_in terbaru jika activity_logs tidak ada
    try {
        $fallback = $pdo->query("
            (SELECT so.tanggal, so.nomor_transaksi, 'Keluar' as jenis, p.nama_barang, so.jumlah, u.nama as nama_user, so.id, so.created_at FROM stock_out so JOIN products p ON so.product_id=p.id LEFT JOIN users u ON so.created_by=u.id ORDER BY so.created_at DESC LIMIT 5)
            UNION ALL
            (SELECT si.tanggal, si.nomor_transaksi, 'Masuk' as jenis, p.nama_barang, si.jumlah, u.nama as nama_user, si.id, si.created_at FROM stock_in si JOIN products p ON si.product_id=p.id LEFT JOIN users u ON si.created_by=u.id ORDER BY si.created_at DESC LIMIT 5)
            ORDER BY created_at DESC LIMIT 10
        ");
        while ($t = $fallback->fetch()) { $recentTransactions[] = $t; }
    } catch (Exception $e2) {}
}

// Fetch Riwayat Barang Keluar Sedang Dipakai (untuk fitur return ke Stok Opname)
$barangDipakai = [];
try {
    $hasStatusCol = $pdo->query("SHOW COLUMNS FROM stock_out LIKE 'status_pakai'")->rowCount() > 0;
    if ($hasStatusCol) {
        $stmtDipakai = $pdo->query("
            SELECT so.*, p.kode_barang, p.nama_barang, p.satuan, u.nama as nama_user
            FROM stock_out so
            JOIN products p ON so.product_id = p.id
            LEFT JOIN users u ON so.created_by = u.id
            WHERE so.status_pakai = 'Dipakai'
            ORDER BY so.tanggal DESC, so.created_at DESC, so.id DESC
        ");
    } else {
        // Jika kolom belum ada, tampilkan semua sebagai 'Dipakai' (kompatibel backward)
        $stmtDipakai = $pdo->query("
            SELECT so.*, p.kode_barang, p.nama_barang, p.satuan, u.nama as nama_user, 'Dipakai' as status_pakai
            FROM stock_out so
            JOIN products p ON so.product_id = p.id
            LEFT JOIN users u ON so.created_by = u.id
            ORDER BY so.tanggal DESC, so.created_at DESC, so.id DESC
        ");
    }
    $barangDipakai = $stmtDipakai->fetchAll();
} catch (Exception $e) { $barangDipakai = []; }
?>

<!-- STAT CARDS GRID -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
    
    <!-- Total Produk -->
    <?php if (in_array($userRole, ['Admin', 'Warehouse'])): ?>
    <a href="produk.php" class="block bg-white p-5 rounded-2xl border border-slate-200/80 shadow-subtle flex items-center justify-between group hover:border-sky-300 transition-all opacity-0 translate-y-4 reveal-item no-underline text-inherit">
        <div>
            <div class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Produk</div>
            <div class="text-2xl font-extrabold text-slate-800 mt-1"><span class="counter" data-target="<?= $totalProduk ?>">0</span></div>
            <div class="text-xs text-slate-500 mt-1">Item terdaftar</div>
        </div>
        <div class="w-12 h-12 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center text-xl font-bold group-hover:scale-110 transition-transform">
            <i class="fa-solid fa-boxes-stacked"></i>
        </div>
    </a>
    <?php endif; ?> 

    <!-- Total Stok Barang -->
    <?php if (in_array($userRole, ['Admin', 'User'])): ?>
    <a href="stok_opname.php" class="block bg-white p-5 rounded-2xl border border-slate-200/80 shadow-subtle flex items-center justify-between group hover:border-emerald-300 transition-all opacity-0 translate-y-4 reveal-item no-underline text-inherit">
        <div>
            <div class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Stok Barang</div>
            <div class="text-2xl font-extrabold text-slate-800 mt-1"><span class="counter" data-target="<?= $totalStok ?>">0</span></div>
            <div class="text-xs text-slate-500 mt-1">Total seluruh unit</div>
        </div>
        <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl font-bold group-hover:scale-110 transition-transform">
            <i class="fa-solid fa-cubes"></i>
        </div>
    </a>
    <?php endif; ?>

    <!-- Total Barang Masuk -->
    <?php if (in_array($userRole, ['Admin', 'NOC'])): ?>  
    <a href="barang_masuk.php#riwayat-masuk" class="block bg-white p-5 rounded-2xl border border-slate-200/80 shadow-subtle flex items-center justify-between group hover:border-blue-300 transition-all opacity-0 translate-y-4 reveal-item no-underline text-inherit">
        <div>
            <div class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Barang Masuk</div>
            <div class="text-2xl font-extrabold text-slate-800 mt-1"><span class="counter" data-target="<?= $totalMasuk ?>">0</span></div>
            <div class="text-xs text-slate-500 mt-1">Unit diterima</div>
        </div>
        <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl font-bold group-hover:scale-110 transition-transform">
            <i class="fa-solid fa-box"></i>
        </div>
    </a>
    <?php endif; ?>

    <!-- Total Barang Keluar -->
    <?php if (in_array($userRole, ['Admin', 'NOC'])): ?>    
    <a href="barang_keluar.php#riwayat-keluar" class="block bg-white p-5 rounded-2xl border border-slate-200/80 shadow-subtle flex items-center justify-between group hover:border-indigo-300 transition-all opacity-0 translate-y-4 reveal-item no-underline text-inherit">
        <div>
            <div class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Barang Keluar</div>
            <div class="text-2xl font-extrabold text-slate-800 mt-1"><span class="counter" data-target="<?= $totalKeluar ?>">0</span></div>
            <div class="text-xs text-slate-500 mt-1">Unit terdistribusi</div>
        </div>
        <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-xl font-bold group-hover:scale-110 transition-transform">
            <i class="fa-solid fa-box-open"></i>
        </div>
    </a>
    <?php endif; ?> 

    <!-- Total Kategori -->
    <?php if (in_array($userRole, ['Admin', 'Warehouse'])): ?>  
    <a href="kategori.php" class="block bg-white p-5 rounded-2xl border border-slate-200/80 shadow-subtle flex items-center justify-between group hover:border-purple-300 transition-all opacity-0 translate-y-4 reveal-item no-underline text-inherit">
        <div>
            <div class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Kategori</div>
            <div class="text-2xl font-extrabold text-slate-800 mt-1"><span class="counter" data-target="<?= $totalKategori ?>">0</span></div>
            <div class="text-xs text-slate-500 mt-1">Kelompok produk</div>
        </div>
        <div class="w-12 h-12 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-xl font-bold group-hover:scale-110 transition-transform">
            <i class="fa-solid fa-tags"></i>
        </div>
    </a>
    <?php endif; ?>


   <!-- Stok Aman -->
    <?php if (in_array($userRole, ['Admin', 'User'])): ?>
    <a href="stok_opname.php?filter=aman" class="block bg-white p-5 rounded-2xl border border-slate-200/80 shadow-subtle flex items-center justify-between group hover:border-emerald-300 transition-all opacity-0 translate-y-4 reveal-item no-underline text-inherit">
        <div>
            <div class="text-xs font-bold text-emerald-600 uppercase tracking-wider">Stok Aman</div>
            <div class="text-2xl font-extrabold text-emerald-700 mt-1"><span class="counter" data-target="<?= $stokAman ?>">0</span></div>
            <div class="text-xs text-emerald-600 mt-1">Cek stok</div>
        </div>
        <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl font-bold group-hover:scale-110 transition-transform">
            <i class="fa-solid fa-boxes-packing"></i>
        </div>
    </a>
    <?php endif; ?>

     <!-- Stok Habis -->
     <?php if (in_array($userRole, ['Admin', 'User'] )): ?>
     <a href="stok_opname.php?filter=habis" class="block bg-white p-5 rounded-2xl border border-slate-200/80 shadow-subtle flex items-center justify-between group hover:border-rose-300 transition-all opacity-0 translate-y-4 reveal-item no-underline text-inherit">
         <div>
             <div class="text-xs font-bold text-rose-600 uppercase tracking-wider">Stok Habis</div>
             <div class="text-2xl font-extrabold text-rose-700 mt-1"><span class="counter" data-target="<?= $stokHabis ?>">0</span></div>
             <div class="text-xs text-rose-600 mt-1">Kosong total</div>
         </div>
         <div class="w-12 h-12 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center text-xl font-bold group-hover:scale-110 transition-transform">
             <i class="fa-solid fa-ban"></i>
         </div>
     </a>
     <?php endif; ?>

     <!-- Barang Sedang Dipakai (NEW) -->
     <a href="#riwayat-dipakai" class="block bg-white p-5 rounded-2xl border border-amber-200/60 shadow-subtle flex items-center justify-between group hover:border-amber-300 transition-all opacity-0 translate-y-4 reveal-item no-underline text-inherit">
         <div>
             <div class="text-xs font-bold text-amber-600 uppercase tracking-wider">Sedang Dipakai</div>
             <div class="text-2xl font-extrabold text-amber-700 mt-1"><span class="counter" data-target="<?= $totalDipakai ?>">0</span><span class="text-sm font-semibold text-amber-600 ml-1">(<?= number_format($totalDipakaiUnit) ?> unit)</span></div>
             <div class="text-xs text-amber-600 mt-1">Barang keluar belum kembali</div>
         </div>
         <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl font-bold group-hover:scale-110 transition-transform">
             <i class="fa-solid fa-person-walking-luggage"></i>
         </div>
     </a>
 </div>

<!-- CHARTS SECTION -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
    
    <!-- Bar Chart -->
    <div class="lg:col-span-2 bg-white p-6 rounded-2xl border border-slate-200/80 shadow-subtle opacity-0 translate-y-4 reveal-item">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-base font-bold text-slate-800">Grafik Barang Masuk & Keluar</h3>
                <p class="text-xs text-slate-500">Perbandingan per bulan (6 bulan terakhir)</p>
            </div>
            <span class="p-2 bg-slate-100 text-slate-600 rounded-lg text-xs font-medium">Realtime</span>
        </div>
        <div class="h-72">
            <canvas id="barChart"></canvas>
        </div>
    </div>

    <!-- Pie Chart -->
    <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-subtle flex flex-col justify-between opacity-0 translate-y-4 reveal-item">
        <div>
            <h3 class="text-base font-bold text-slate-800 mb-1">Distribusi Stok Kategori</h3>
            <p class="text-xs text-slate-500 mb-4">Proporsi stok berdasarkan kategori</p>
            <div class="h-60 relative flex items-center justify-center">
                <canvas id="pieChart"></canvas>
            </div>
        </div>
    </div>

</div>

<!-- RIWAYAT BARANG KELUAR SEDANG DIPAKAI (NEW FEATURE) -->
<div id="riwayat-dipakai" class="bg-white rounded-2xl border border-amber-200/60 shadow-subtle overflow-hidden opacity-0 translate-y-4 reveal-item mb-8">
    <div class="p-5 border-b border-slate-100 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div class="flex items-start gap-3">
            <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center border border-amber-200 flex-shrink-0">
                <i class="fa-solid fa-person-walking-luggage"></i>
            </div>
            <div>
                <h3 class="text-base font-bold text-slate-800 flex items-center gap-2">Riwayat Barang Keluar — Sedang Dipakai
                    <?php if (!empty($barangDipakai)): ?>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800 border border-amber-200"><?= count($barangDipakai) ?> item</span>
                    <?php endif; ?>
                </h3>
                <p class="text-xs text-slate-500 mt-0.5">Barang yang masih dipakai diluar. Centang <i class="fa-solid fa-check text-emerald-600"></i> untuk kembalikan ke <strong>Stok Opname</strong> (stok bertambah).</p>
            </div>
        </div>
        <div class="flex items-center gap-2 w-full sm:w-auto">
            <div class="relative w-full sm:w-64">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </span>
                <input type="text" id="searchDipakai" onkeyup="filterTable('searchDipakai', 'tableDipakai')" placeholder="Cari no tx, nama barang, tujuan..." class="w-full pl-10 pr-4 py-2 text-sm bg-slate-50 border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-amber-500">
            </div>
            <a href="barang_keluar.php" class="hidden sm:inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-bold bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 whitespace-nowrap">
                <i class="fa-solid fa-arrow-up-right-from-square text-[10px]"></i> Keluar
            </a>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table id="tableDipakai" class="w-full text-left text-sm text-slate-600">
            <thead class="bg-amber-50/70 text-xs uppercase tracking-wider text-amber-900 font-semibold border-b border-amber-200/60">
                <tr>
                    <th class="px-5 py-3.5">Tanggal Keluar</th>
                    <th class="px-5 py-3.5">No. Transaksi</th>
                    <th class="px-5 py-3.5">Nama Barang</th>
                    <th class="px-5 py-3.5 text-right">Jumlah</th>
                    <th class="px-5 py-3.5">Tujuan / Pemakai</th>
                    <th class="px-5 py-3.5">User</th>
                    <th class="px-5 py-3.5 text-center">Kembalikan</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($barangDipakai)): ?>
                    <tr>
                        <td colspan="7" class="px-5 py-10 text-center text-slate-400 text-sm">
                            <i class="fa-solid fa-circle-check text-3xl mb-2 block text-emerald-300"></i>
                            Tidak ada barang yang sedang dipakai. Semua barang sudah kembali ke stok.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($barangDipakai as $d): ?>
                        <tr class="hover:bg-amber-50/40 transition-colors">
                            <td class="px-5 py-3.5 whitespace-nowrap text-xs font-medium text-slate-700">
                                <?= e(formatDateWithDay($d['tanggal'])) ?>
                                <div class="text-[11px] text-slate-400 font-normal"><?= e($d['kode_barang']) ?></div>
                            </td>
                            <td class="px-5 py-3.5 font-mono text-xs text-amber-700 font-bold">
                                <?= e($d['nomor_transaksi']) ?>
                            </td>
                            <td class="px-5 py-3.5">
                                <div class="font-semibold text-slate-800"><?= e($d['nama_barang']) ?></div>
                                <div class="text-[11px] text-slate-500"><?= e($d['satuan']) ?> • <span class="px-1.5 py-0.5 rounded text-[10px] font-bold <?= ($d['kondisi'] ?? 'Tidak Rusak')==='Rusak'?'bg-rose-50 text-rose-700 border border-rose-200':'bg-emerald-50 text-emerald-700 border border-emerald-200' ?>"><?= e($d['kondisi'] ?? 'Tidak Rusak') ?></span></div>
                            </td>
                            <td class="px-5 py-3.5 text-right font-mono font-bold text-slate-800">
                                <?= number_format($d['jumlah']) ?> <?= e($d['satuan']) ?>
                            </td>
                            <td class="px-5 py-3.5 text-xs font-medium text-slate-700">
                                <i class="fa-solid fa-location-dot text-rose-500 mr-1 text-xs"></i><?= e($d['tujuan']) ?>
                                <?php if (!empty($d['keterangan']) || !empty($d['catatan_pemasangan'])): ?>
                                    <div class="text-[11px] text-slate-400 italic"><?= e($d['keterangan'] ?? $d['catatan_pemasangan']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="px-5 py-3.5 text-xs text-slate-500">
                                <i class="fa-regular fa-user mr-1 text-slate-400"></i><?= e($d['nama_user'] ?? 'System') ?>
                            </td>
                            <td class="px-5 py-3.5 text-center">
                                <form action="../actions/barang_keluar_return_action.php" method="POST" onsubmit="return confirm('Kembalikan barang ini ke Stok Opname?\n\n[<?= e($d['nama_barang']) ?>] <?= $d['jumlah'] ?> <?= e($d['satuan']) ?> akan ditambahkan kembali ke stok.');" class="inline-block">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="id" value="<?= $d['id'] ?>">
                                    <input type="hidden" name="from" value="dashboard">
                                    <button type="submit" class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white shadow-sm shadow-emerald-500/20 transition-all hover:scale-105" title="Kembalikan ke Stok Opname (centang)">
                                        <i class="fa-solid fa-check text-sm font-black"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if (!empty($barangDipakai)): ?>
    <div class="px-5 py-3 bg-amber-50/50 border-t border-amber-100 text-xs text-amber-800 flex items-center gap-2">
        <i class="fa-solid fa-circle-info"></i>
        <span>Tip: Tombol <span class="inline-flex items-center justify-center w-5 h-5 rounded bg-emerald-500 text-white text-[10px]"><i class="fa-solid fa-check"></i></span> akan mengembalikan stok &amp; menandai transaksi menjadi <em>Dikembalikan</em>. Data tidak dihapus, hanya status berubah.</span>
    </div>
    <?php endif; ?>
</div>

<!-- 10 RECENT TRANSACTIONS TABLE -->
<div class="bg-white rounded-2xl border border-slate-200/80 shadow-subtle overflow-hidden opacity-0 translate-y-4 reveal-item">
    <div class="p-5 border-b border-slate-100 flex items-center justify-between">
        <div>
            <h3 class="text-base font-bold text-slate-800">10 Transaksi Terakhir</h3>
            <p class="text-xs text-slate-500">Aktivitas barang masuk dan keluar terbaru</p>
        </div>
        <i class="fa-solid fa-clock-rotate-left text-slate-400 text-lg"></i>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm text-slate-600">
            <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500 font-semibold border-b border-slate-200/80">
                <tr>
                    <th class="px-5 py-3.5">Tanggal</th>
                    <th class="px-5 py-3.5">No. Transaksi</th>
                    <th class="px-5 py-3.5">Jenis</th>
                    <th class="px-5 py-3.5">Nama Barang</th>
                    <th class="px-5 py-3.5 text-right">Jumlah</th>
                    <th class="px-5 py-3.5">User</th>
                    <?php if (in_array($userRole, ['admin'])): ?>
                    <th class="px-5 py-3.5 text-center">Aksi</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($recentTransactions)): ?>
                    <tr>
                        <td colspan="<?= in_array($userRole, ['admin']) ? '7' : '6' ?>" class="px-5 py-8 text-center text-slate-400 text-sm">
                            Belum ada transaksi recorded.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($recentTransactions as $tx): ?>
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="px-5 py-3.5 whitespace-nowrap text-xs font-medium text-slate-700">
                                <?= e(formatDateWithDay($tx['tanggal'])) ?>
                            </td>
                            <td class="px-5 py-3.5 font-mono text-xs text-slate-800 font-semibold">
                                <?= e($tx['nomor_transaksi']) ?>
                            </td>
                            <td class="px-5 py-3.5 whitespace-nowrap">
                                <?php if ($tx['jenis'] === 'Masuk'): ?>
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <i class="fa-solid fa-arrow-down text-[10px]"></i> Barang Masuk
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                        <i class="fa-solid fa-arrow-up text-[10px]"></i> Barang Keluar
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-5 py-3.5 font-medium text-slate-800">
                                <?= e($tx['nama_barang']) ?>
                            </td>
                            <td class="px-5 py-3.5 text-right font-bold font-mono text-slate-800">
                                <?= number_format($tx['jumlah']) ?>
                            </td>
                            <td class="px-5 py-3.5 text-xs text-slate-500">
                                <i class="fa-regular fa-user text-slate-400 mr-1"></i><?= e($tx['nama_user']) ?>
                            </td>
                            
                            <!-- HANYA ADMIN YANG DAPAT MELIHAT/MENGGUNAKAN TOMBOL HAPUS AKSI -->
                            <?php if (in_array($userRole, ['admin'])): ?>
                            <td class="px-5 py-3.5 text-center">
                                <form action="../actions/transaction_action.php" method="POST" class="inline" onsubmit="return confirm('Hapus riwayat transaksi ini dari log?');">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="act" value="delete_transaction">
                                    <input type="hidden" name="jenis" value="<?= $tx['jenis'] ?>">
                                    <input type="hidden" name="id" value="<?= $tx['id'] ?>">
                                    <input type="hidden" name="from" value="dashboard">
                                    <button type="submit" class="text-rose-600 hover:text-rose-900 text-sm font-semibold" title="Hapus">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Chart.js Setup Scripts -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Bar Chart
    const ctxBar = document.getElementById('barChart').getContext('2d');
    new Chart(ctxBar, {
        type: 'bar',
        data: {
            labels: <?= json_encode($months) ?>,
            datasets: [
                {
                    label: 'Barang Masuk',
                    data: <?= json_encode($dataMasuk) ?>,
                    backgroundColor: '#1e3a8a', // Wifian Blue
                    borderRadius: 8,
                    maxBarThickness: 40,
                },
                {
                    label: 'Barang Keluar',
                    data: <?= json_encode($dataKeluar) ?>,
                    backgroundColor: '#dc2626', // Wifian Red
                    borderRadius: 8,
                    maxBarThickness: 40,
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: {
                duration: 1300,
                easing: 'easeOutQuart',
                y: {
                    from: 0
                }
            },
            plugins: {
                legend: { position: 'top' }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: '#f1f5f9' }
                },
                x: {
                    grid: { display: false }
                }
            }
        }
    });

    // Pie Chart
    const ctxPie = document.getElementById('pieChart').getContext('2d');
    new Chart(ctxPie, {
        type: 'doughnut',
        data: {
            labels: <?= json_encode($catLabels) ?>,
            datasets: [{
                data: <?= json_encode($catData) ?>,
                backgroundColor: [
                    '#1e3a8a', '#dc2626', '#38bdf8', '#10b981', '#8b5cf6', '#64748b'
                ],
                borderWidth: 2,
                borderColor: '#ffffff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: {
                duration: 1400,
                easing: 'easeOutQuart',
                animateRotate: true,
                animateScale: true
            },
            plugins: {
                legend: { position: 'bottom', labels: { boxWidth: 12 } }
            }
        }
    });

    // Container reveal transitions
    const revealItems = document.querySelectorAll('.reveal-item');
    if ('IntersectionObserver' in window) {
        const revealObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                    entry.target.style.transition = 'opacity 0.8s ease, transform 0.8s ease';
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.2 });
        revealItems.forEach(item => revealObserver.observe(item));
    } else {
        revealItems.forEach(item => {
            item.style.opacity = '1';
            item.style.transform = 'translateY(0)';
        });
    }

    // Counter animation for stat cards
    const counters = document.querySelectorAll('.counter');
    const formatNumber = (n) => n.toLocaleString('id-ID');

    const animate = (el, to, duration = 1400) => {
        let start = 0;
        const startTime = performance.now();
        const step = (now) => {
            const progress = Math.min((now - startTime) / duration, 1);
            // easeOutQuad
            const eased = 1 - (1 - progress) * (1 - progress);
            const value = Math.round(eased * (to - start) + start);
            el.textContent = formatNumber(value);
            if (progress < 1) requestAnimationFrame(step);
        };
        requestAnimationFrame(step);
    };

    const onIntersect = (entries, io) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const el = entry.target;
                const to = parseInt(el.getAttribute('data-target')) || 0;
                animate(el, to);
                io.unobserve(el);
            }
        });
    };

    if ('IntersectionObserver' in window) {
        const io = new IntersectionObserver(onIntersect, {threshold: 0.3});
        counters.forEach(c => io.observe(c));
    } else {
        // Fallback: animate immediately
        counters.forEach(c => animate(c, parseInt(c.getAttribute('data-target')) || 0));
    }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>