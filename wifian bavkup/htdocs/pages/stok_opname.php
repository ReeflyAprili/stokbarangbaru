<?php
// pages/stok_opname.php
$pageTitle = 'Stok Opname';
require_once __DIR__ . '/../includes/header.php';
$pdo = getDBConnection();

// Tangkap filter URL
$filter = $_GET['filter'] ?? 'semua';

// Fetch products for dropdown
$products = $pdo->query("SELECT id, kode_barang, nama_barang, satuan, stok, stok_minimum, lokasi_rak FROM products ORDER BY nama_barang ASC")->fetchAll();

// Fetch opname history
$history = $pdo->query("
    SELECT so.*, p.kode_barang, p.nama_barang, p.satuan, u.nama as nama_user
    FROM stock_opname so
    JOIN products p ON so.product_id = p.id
    LEFT JOIN users u ON so.created_by = u.id
    ORDER BY so.created_at DESC, so.id DESC
")->fetchAll();
?>

<!-- 1. Filter & Search Bar -->
<div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-subtle mb-6 flex flex-col md:flex-row items-center justify-between gap-4">
    <!-- Filter Tabs -->
    <div class="flex items-center gap-2 w-full md:w-auto overflow-x-auto pb-1 md:pb-0">
        <a href="stok_opname.php?filter=semua" class="px-4 py-2 rounded-xl text-xs font-semibold whitespace-nowrap transition-all <?= $filter === 'semua' ? 'bg-slate-900 text-white shadow' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' ?>">
            Semua Barang
        </a>
        <a href="stok_opname.php?filter=aman" class="px-4 py-2 rounded-xl text-xs font-semibold whitespace-nowrap transition-all <?= $filter === 'aman' ? 'bg-emerald-600 text-white shadow' : 'bg-emerald-50 text-emerald-700 border border-emerald-200 hover:bg-emerald-100' ?>">
            <i class="fa-solid fa-circle-check mr-1"></i> Stok Aman
        </a>
        <a href="stok_opname.php?filter=habis" class="px-4 py-2 rounded-xl text-xs font-semibold whitespace-nowrap transition-all <?= $filter === 'habis' ? 'bg-rose-600 text-white shadow' : 'bg-rose-50 text-rose-700 border border-rose-200 hover:bg-rose-100' ?>">
            <i class="fa-solid fa-circle-xmark mr-1"></i> Stok Habis
        </a>
    </div>

    <!-- Search Input -->
    <div class="relative w-full md:w-80">
        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
            <i class="fa-solid fa-magnifying-glass"></i>
        </span>
        <input type="text" id="searchProducts" onkeyup="filterTable('searchProducts', 'tableProducts')" placeholder="Cari barang..." class="w-full pl-10 pr-4 py-2 text-sm bg-slate-50 border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-sky-500">
    </div>
</div>

<!-- 2. Grid Layout (Untuk Tabel & Guideline Card jika ada) -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
    <div class="lg:col-span-2">
        <!-- Area Tambahan / Guideline jika ada -->
    </div>
</div>

<!-- Table khusus Stock Opname -->
<div class="bg-white rounded-2xl border border-slate-200/80 shadow-subtle overflow-hidden mb-8">
    <div class="overflow-x-auto">
        <table id="tableProducts" class="w-full text-left text-sm text-slate-600">
            <thead class="bg-purple-900/5 text-xs uppercase tracking-wider text-purple-900 font-bold border-b border-purple-100">
                <tr>
                    <th class="px-5 py-4">Kode Barang</th>
                    <th class="px-5 py-4">Nama Barang</th>
                    <th class="px-5 py-4">Kategori</th>
                    <th class="px-5 py-4 text-center">Stok Sistem</th>
                    <th class="px-5 py-4 text-center">Status Stok</th>
                    <th class="px-5 py-4">Lokasi Rak</th>
                    <th class="px-5 py-4 text-center">Audit Stok</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($products)): ?>
                    <tr>
                        <td colspan="7" class="px-5 py-12 text-center text-slate-400">
                            <i class="fa-solid fa-clipboard-list text-4xl mb-2 block text-slate-300"></i>
                            Tidak ada data barang untuk di-audit.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($products as $p): ?>
                        <?php
                            $stok = (int)($p['stok'] ?? 0);
                            
                            // Filter berdasarkan parameter URL jika ada
                            if ($filter === 'aman' && $stok <= 0) continue;
                            if ($filter === 'habis' && $stok > 0) continue;
                            
                            // Penentuan Badge
                            if ($stok <= 0) {
                                $badge = '<span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-bold bg-rose-50 text-rose-600 border border-rose-200"><i class="fa-solid fa-circle-xmark text-xs"></i> Stok Habis</span>';
                            } else {
                                $badge = '<span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-bold bg-emerald-50 text-emerald-600 border border-emerald-200"><i class="fa-solid fa-circle-check text-xs"></i> Stok Aman</span>';
                            }
                        ?>
                        <tr class="hover:bg-purple-50/30 transition-colors">
                            <td class="px-5 py-4 font-mono text-xs font-bold text-purple-900">
                                <?= e($p['kode_barang']) ?>
                            </td>
                            <td class="px-5 py-4">
                                <div class="font-semibold text-slate-800"><?= e($p['nama_barang']) ?></div>
                                <div class="text-[11px] text-slate-400">Satuan: <?= e($p['satuan']) ?></div>
                            </td>
                            <td class="px-5 py-4 text-xs font-medium text-slate-600">
                            <i class= fa-solid></i>  <?= e($p['nama_kategori'] ?? '-') ?>
                            </td>
                            <td class="px-5 py-4 text-center font-mono">
                                <span class="px-3 py-1 bg-slate-100 rounded-lg font-bold text-slate-800 text-sm">
                                    <?= number_format($stok) ?>
                                </span>
                            </td>
                            <td class="px-5 py-4 text-center">
                                <?= $badge ?>
                            </td>
                            <td class="px-5 py-4 text-xs font-medium text-slate-600">
                                <i class="fa-solid fa-location-dot text-slate-400 mr-1"></i><?= e($p['lokasi_rak'] ?? '-') ?>
                            </td>
                            <td class="px-5 py-4 text-center whitespace-nowrap">
                                <a href="stok_opname.php?product_id=<?= $p['id'] ?>" class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-xl text-xs font-bold bg-purple-600 hover:bg-purple-700 text-white shadow-sm transition-colors">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                    <span>Audit</span>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Table History Opname -->
<div class="bg-white rounded-2xl border border-slate-200/80 shadow-subtle overflow-hidden">
    <div class="p-5 border-b border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-4">
        <div>
            <h3 class="text-base font-bold text-slate-800">Riwayat Stok Opname</h3>
            <p class="text-xs text-slate-500">Daftar pemeriksaan audit fisik inventaris</p>
        </div>
        
        <div class="relative w-full sm:w-72">
            <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                <i class="fa-solid fa-magnifying-glass"></i>
            </span>
            <input type="text" id="searchOpname" onkeyup="filterTable('searchOpname', 'tableOpname')" placeholder="Cari barang, user, keterangan..." class="w-full pl-10 pr-4 py-2 text-sm bg-slate-50 border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-sky-500">
        </div>
    </div>

    <div class="overflow-x-auto">
        <table id="tableOpname" class="w-full text-left text-sm text-slate-600">
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
                <?php if (empty($history)): ?>
                    <tr>
                        <td colspan="8" class="px-5 py-8 text-center text-slate-400">
                            Belum ada riwayat stok opname.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($history as $h): ?>
                        <?php
                            $sel = (int)$h['selisih'];
                            if ($sel === 0) {
                                $statusLbl = '<span class="px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-300"><i class="fa-solid fa-check text-[10px]"></i> Sesuai</span>';
                            } elseif ($sel > 0) {
                                $statusLbl = '<span class="px-2.5 py-1 rounded-full text-xs font-bold bg-blue-100 text-blue-800 border border-blue-300"><i class="fa-solid fa-plus text-[10px]"></i> Lebih</span>';
                            } else {
                                $statusLbl = '<span class="px-2.5 py-1 rounded-full text-xs font-bold bg-rose-100 text-rose-800 border border-rose-300"><i class="fa-solid fa-minus text-[10px]"></i> Kurang</span>';
                            }
                        ?>
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="px-5 py-3.5 text-xs text-slate-600 font-medium">
                                <?= date('d/m/Y', strtotime($h['tanggal'])) ?>
                            </td>
                            <td class="px-5 py-3.5 font-mono text-xs text-slate-500">
                                <?= e($h['kode_barang']) ?>
                            </td>
                            <td class="px-5 py-3.5 font-semibold text-slate-800">
                                <?= e($h['nama_barang']) ?>
                                <?php if (!empty($h['keterangan'])): ?>
                                    <div class="text-[11px] font-normal text-slate-400 italic"><?= e($h['keterangan']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="px-5 py-3.5 text-center font-mono text-slate-600">
                                <?= number_format($h['stok_sistem']) ?>
                            </td>
                            <td class="px-5 py-3.5 text-center font-mono font-bold text-slate-800">
                                <?= number_format($h['stok_fisik']) ?>
                            </td>
                            <td class="px-5 py-3.5 text-center font-mono font-bold <?= $sel === 0 ? 'text-emerald-600' : ($sel > 0 ? 'text-blue-600' : 'text-rose-600') ?>">
                                <?= ($sel > 0 ? '+' : '') . number_format($sel) ?>
                            </td>
                            <td class="px-5 py-3.5">
                                <?= $statusLbl ?>
                            </td>
                            <td class="px-5 py-3.5 text-xs text-slate-500">
                                <i class="fa-regular fa-user mr-1 text-slate-400"></i><?= e($h['nama_user'] ?? 'System') ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function onSelectProductOpname(select) {
    const selectedOption = select.options[select.selectedIndex];
    const stokSys = parseInt(selectedOption.getAttribute('data-stok') || '0');
    
    document.getElementById('stok_sistem').value = stokSys;
    document.getElementById('stok_fisik').value = stokSys;
    calcSelisih();
}

function calcSelisih() {
    const sys = parseInt(document.getElementById('stok_sistem').value || '0');
    const fis = parseInt(document.getElementById('stok_fisik').value || '0');
    const sel = fis - sys;
    
    const selInput = document.getElementById('selisih');
    selInput.value = sel;

    const statusBox = document.getElementById('status_box');
    if (sel === 0) {
        statusBox.className = "px-3.5 py-2 rounded-xl text-xs font-bold text-emerald-800 bg-emerald-50 border border-emerald-200 flex items-center h-[42px]";
        statusBox.innerHTML = '<i class="fa-solid fa-circle-check text-emerald-600 mr-1.5"></i> Sesuai (0)';
    } else if (sel > 0) {
        statusBox.className = "px-3.5 py-2 rounded-xl text-xs font-bold text-blue-800 bg-blue-50 border border-blue-200 flex items-center h-[42px]";
        statusBox.innerHTML = '<i class="fa-solid fa-circle-plus text-blue-600 mr-1.5"></i> Lebih (+' + sel + ')';
    } else {
        statusBox.className = "px-3.5 py-2 rounded-xl text-xs font-bold text-rose-800 bg-rose-50 border border-rose-200 flex items-center h-[42px]";
        statusBox.innerHTML = '<i class="fa-solid fa-circle-minus text-rose-600 mr-1.5"></i> Kurang (' + sel + ')';
    }
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>