<?php
// pages/riwayat_masuk.php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

$pdo = getDBConnection();

// Logika Pencarian berdasarkan tanggal
$search = trim($_GET['search'] ?? '');
if ($search !== '') {
    $stmt = $pdo->prepare("SELECT * FROM stok_rusak_masuk WHERE tanggal LIKE ? ORDER BY id DESC");
    $stmt->execute(["%$search%"]);
} else {
    $stmt = $pdo->query("SELECT * FROM stok_rusak_masuk ORDER BY id DESC");
}
$riwayatMasuk = $stmt->fetchAll();
?>

<div class="p-6 md:p-8 space-y-6 bg-slate-50 min-h-screen">
    
    <!-- Header Section -->
    <div>
        <h1 class="text-xl font-bold text-slate-800 tracking-tight">Riwayat Stok Rusak Masuk</h1>
        <p class="text-sm font-normal text-slate-500 mt-0.5">Daftar transaksi penambahan stok barang rusak ke dalam sistem.</p>
    </div>

    <!-- Search Bar Card Section -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5">
        <form method="GET" action="" class="w-full max-w-md">
            <div class="relative flex items-center">
                <span class="absolute left-3.5 text-slate-400">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </span>
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Cari barang..." class="w-full bg-white text-xs font-medium text-slate-700 pl-10 pr-4 py-2.5 rounded-full border border-slate-200/80 focus:outline-none focus:border-slate-400 transition-colors shadow-sm">
            </div>
        </form>
    </div>

    <!-- Table Card Section -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="text-[11px] font-bold text-slate-500 uppercase tracking-wider bg-slate-50/70 border-b border-slate-200/80">
                        <th class="py-3.5 px-5">TANGGAL</th>
                        <th class="py-3.5 px-5">INFO</th>
                        <th class="py-3.5 px-5">NAMA BARANG</th>
                        <th class="py-3.5 px-5">KETERANGAN / ALASAN</th>
                        <th class="py-3.5 px-5 text-right">JUMLAH</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs font-medium text-slate-600">
                    <?php if (empty($riwayatMasuk)): ?>
                        <tr>
                            <td colspan="5" class="py-8 text-center text-slate-400 italic">Belum ada riwayat masuk.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($riwayatMasuk as $row): ?>
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td class="py-4 px-5 text-slate-600">
                                    <?= date('d/m/Y', strtotime($row['tanggal'])) ?>
                                </td>
                                <td class="py-4 px-5">
                                    <span class="bg-emerald-50 text-emerald-600 px-2.5 py-1 rounded-full text-[10px] font-bold border border-emerald-100 inline-flex items-center gap-1">
                                        ↓ <?= htmlspecialchars($row['info'] ?? 'Masuk') ?>
                                    </span>
                                </td>
                                <td class="py-4 px-5 font-bold text-slate-900">
                                    <?= htmlspecialchars($row['nama_barang'] ?? '') ?>
                                </td>
                                <td class="py-4 px-5 text-slate-500">
                                    <?= htmlspecialchars($row['alasan_rusak'] ?? '') ?>
                                </td>
                                <td class="py-4 px-5 text-right font-bold text-emerald-600">
                                    <?= number_format($row['jumlah'] ?? 0, 0, ',', '.') ?>
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