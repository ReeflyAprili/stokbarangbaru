<?php
// pages/jenis_barang.php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

$pdo = getDBConnection();
$stmt = $pdo->query("SELECT * FROM stok_rusak_barang ORDER BY id DESC");
$items = $stmt->fetchAll();
?>

<div class="p-6 md:p-8 space-y-6 bg-slate-50 min-h-screen">
    
    <!-- Header Section -->
    <div>
        <h1 class="text-xl font-bold text-slate-800 tracking-tight">Jenis Barang Rusak</h1>
        <p class="text-sm font-normal text-slate-500 mt-0.5">Daftar rekapitulasi seluruh stok barang rusak yang tersimpan.</p>
    </div>

    <!-- Table Card Section -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="text-[11px] font-bold text-slate-500 uppercase tracking-wider bg-slate-50/70 border-b border-slate-200/80">
                        <th class="py-3.5 px-5">KODE</th>
                        <th class="py-3.5 px-5">NAMA BARANG</th>
                        <th class="py-3.5 px-5">KATEGORI</th>
                        <th class="py-3.5 px-5">STOK RUSAK / MIN</th>
                        <th class="py-3.5 px-5">LOKASI RAK</th>
                        <th class="py-3.5 px-5 text-center">STATUS</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs font-medium text-slate-700">
                    <?php if (empty($items)): ?>
                        <tr>
                            <td colspan="6" class="py-8 text-center text-slate-400 italic">Belum ada data barang rusak.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($items as $row): ?>
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td class="py-4 px-5 font-bold text-slate-900"><?= htmlspecialchars($row['kode'] ?? '') ?></td>
                                <td class="py-4 px-5 font-semibold text-slate-800"><?= htmlspecialchars($row['nama_barang'] ?? '') ?></td>
                                <td class="py-4 px-5">
                                    <span class="bg-slate-100 text-slate-600 px-2.5 py-1 rounded-full text-[10px] font-bold">
                                        <?= htmlspecialchars($row['kategori'] ?? '') ?>
                                    </span>
                                </td>
                                <td class="py-4 px-5">
                                    <span class="text-red-500 font-bold"><?= number_format($row['stok_rusak'] ?? 0, 0, ',', '.') ?></span> 
                                    <span class="text-slate-400">/ <?= htmlspecialchars($row['min_stok'] ?? '') ?></span>
                                </td>
                                <td class="py-4 px-5 text-slate-600"><?= htmlspecialchars($row['lokasi_rak'] ?? '') ?></td>
                                <td class="py-4 px-5 text-center">
                                    <span class="bg-red-50 text-red-500 border border-red-200 px-2.5 py-1 rounded-full text-[10px] font-bold inline-flex items-center gap-1.5">
                                        <span class="h-1.5 w-1.5 rounded-full bg-red-500"></span> <?= htmlspecialchars($row['status'] ?? 'Rusak') ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>