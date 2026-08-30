<?php
// includes/sidebar.php
$currentPage = basename($_SERVER['PHP_SELF']);
$userRole    = $_SESSION['role'] ?? 'Admin';
$settings    = getStoreSettings();

// Sub-menu Stok Rusak
$stokRusakItems = [
    ['key' => 'jenis_barang.php',  'label' => 'Jenis Barang',  'icon' => 'fa-boxes-stacked'],
    ['key' => 'riwayat_masuk.php', 'label' => 'Riwayat Masuk', 'icon' => 'fa-right-to-bracket'],
    ['key' => 'riwayat_keluar.php','label' => 'Riwayat Keluar','icon' => 'fa-right-from-bracket'],
];

// Cek apakah halaman saat ini adalah salah satu dari sub-menu Stok Rusak
$isStokRusakActive = in_array($currentPage, array_column($stokRusakItems, 'key'));

// Daftar sub-menu Gudang
$gudangSubItems = [
    ['key' => 'produk.php', 'label' => 'Produk', 'icon' => 'fa-boxes-stacked', 'roles' => ['Admin', 'Warehouse']],
    ['key' => 'barang_masuk.php', 'label' => 'Barang Masuk', 'icon' => 'fa-download', 'roles' => ['Admin', 'NOC']],
    ['key' => 'barang_keluar.php', 'label' => 'Barang Keluar', 'icon' => 'fa-upload', 'roles' => ['Admin', 'NOC']],
    ['key' => 'stok_opname.php', 'label' => 'Stok Opname', 'icon' => 'fa-clipboard-check', 'roles' => ['Admin', 'User']],
];

$allowedGudangItems = array_filter($gudangSubItems, function($item) use ($userRole) {
    return in_array($userRole, $item['roles']);
});

$isGudangActive = in_array($currentPage, array_column($gudangSubItems, 'key')) || $isStokRusakActive;

$menuItems = [
    ['key' => 'dashboard.php', 'label' => 'Dashboard', 'icon' => 'fa-gauge-high', 'roles' => ['Admin', 'Warehouse', 'User', 'NOC']],
    ['key' => 'laporan.php', 'label' => 'Laporan', 'icon' => 'fa-file-invoice-dollar', 'roles' => ['Admin', 'Warehouse']],
    ['key' => 'kategori.php', 'label' => 'Kategori Barang', 'icon' => 'fa-tags', 'roles' => ['Admin', 'Warehouse']],
    ['key' => 'pengguna.php', 'label' => 'Pengguna', 'icon' => 'fa-users-gear', 'roles' => ['Admin']],
    ['key' => 'pengaturan.php', 'label' => 'Pengaturan', 'icon' => 'fa-sliders', 'roles' => ['Admin']],
];
?>

<script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

<aside id="sidebar" class="w-64 bg-sky-50 text-slate-900 flex flex-col fixed lg:static inset-y-0 left-0 z-50 transform -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-in-out shadow-lg lg:shadow-none border-r-2 border-sky-200">
    
    <div class="p-4 border-b border-sky-200 flex items-center gap-3 bg-sky-100/90">
        <div class="bg-white px-2 py-1 rounded-xl shadow-sm flex-shrink-0 border border-sky-200">
            <img src="../assets/images/logo.svg" alt="Logo PT Wifian Solution" class="h-7 w-auto mx-auto">
        </div>
        <div class="flex-1 min-w-0">
            <h1 class="text-sm font-extrabold text-slate-800 leading-snug break-words">
                <?= e($settings['nama_toko']) ?>
            </h1>
        </div>
    </div>

    <nav class="flex-1 px-3 py-5 space-y-1.5 overflow-y-auto">
        <div class="px-3 pb-2 text-[10px] font-black text-slate-500 uppercase tracking-widest">Menu Utama</div>
        
        <?php foreach ($menuItems as $item): ?>
            <?php if ($item['key'] === 'laporan.php' && (!empty($allowedGudangItems) || $isStokRusakActive)): ?>
                
                <!-- DROPDOWN PARENT: GUDANG -->
                <div x-data="{ openGudang: <?= $isGudangActive ? 'true' : 'false' ?>, openRusak: <?= $isStokRusakActive ? 'true' : 'false' ?> }" class="space-y-1">
                    
                    <button type="button" @click="openGudang = !openGudang" 
                            class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl text-sm font-semibold transition-all duration-300 group <?= $isGudangActive ? 'bg-sky-200/70 text-sky-900 font-extrabold' : 'text-slate-800 hover:bg-sky-100/60' ?>">
                        <div class="flex items-center gap-3">
                            <i class="fa-solid fa-warehouse w-5 text-center text-base <?= $isGudangActive ? 'text-sky-700' : 'text-slate-600 group-hover:text-sky-600' ?>"></i>
                            <span>Gudang</span>
                        </div>
                        <i class="fa-solid fa-chevron-down text-xs transition-transform duration-300" :class="openGudang ? 'rotate-180' : 'rotate-0'"></i>
                    </button>

                    <!-- Submenu Gudang -->
                    <div x-show="openGudang" x-collapse.duration.300ms x-cloak class="pl-3 pr-1 space-y-1 pt-1 overflow-hidden">
                        
                        <?php foreach ($allowedGudangItems as $sub): ?>
                            <?php $isSubActive = ($currentPage === $sub['key']); ?>
                            <a href="<?= $sub['key'] ?>" 
                               class="flex items-center gap-3 px-3 py-2 rounded-lg text-xs font-semibold transition-all duration-200 <?= $isSubActive ? 'bg-gradient-to-r from-sky-400 to-sky-500 text-white font-extrabold shadow-sm' : 'text-slate-700 hover:bg-sky-100' ?>">
                                <i class="fa-solid <?= $sub['icon'] ?> w-4 text-center text-sm <?= $isSubActive ? 'text-white' : 'text-slate-500' ?>"></i>
                                <span><?= e($sub['label']) ?></span>
                            </a>
                        <?php endforeach; ?>

                        <!-- DROPDOWN PARENT: STOK RUSAK (TIDAK BISA DI-KLIK PINDAH HALAMAN) -->
                        <div class="space-y-1 pt-1">
                            <button type="button" @click="openRusak = !openRusak" 
                                    class="w-full flex items-center justify-between px-3 py-2 rounded-lg text-xs font-semibold transition-all duration-200 <?= $isStokRusakActive ? 'bg-sky-200/80 text-sky-900 font-extrabold' : 'text-slate-700 hover:bg-sky-100' ?>">
                                <div class="flex items-center gap-3">
                                    <i class="fa-solid fa-box-open w-4 text-center text-sm <?= $isStokRusakActive ? 'text-sky-700' : 'text-slate-500' ?>"></i>
                                    <span>Stok Rusak</span>
                                </div>
                                <i class="fa-solid fa-chevron-down text-[10px] transition-transform duration-300" :class="openRusak ? 'rotate-180' : 'rotate-0'"></i>
                            </button>

                            <!-- Submenu Stok Rusak -->
                            <div x-show="openRusak" x-collapse.duration.200ms x-cloak class="pl-4 space-y-1 pt-1">
                                <?php foreach ($stokRusakItems as $child): ?>
                                    <?php $isChildActive = ($currentPage === $child['key']); ?>
                                    <a href="<?= $child['key'] ?>" 
                                       class="flex items-center gap-2.5 px-3 py-1.5 rounded-lg text-xs font-medium transition-all <?= $isChildActive ? 'bg-sky-500 text-white font-extrabold shadow-sm' : 'text-slate-600 hover:bg-sky-100 hover:text-slate-900' ?>">
                                        <i class="fa-solid <?= $child['icon'] ?> text-xs <?= $isChildActive ? 'text-white' : 'text-slate-400' ?>"></i>
                                        <span><?= e($child['label']) ?></span>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>

                    </div>
                </div>
            <?php endif; ?>

            <?php if (in_array($userRole, $item['roles'])): ?>
                <?php $isActive = ($currentPage === $item['key']); ?>
                <a href="<?= $item['key'] ?>" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold transition-all duration-200 group relative <?= $isActive ? 'bg-gradient-to-r from-sky-300 via-sky-400 to-sky-500 text-white font-extrabold shadow-md border-l-4 border-sky-400' : 'text-slate-800 hover:text-slate-900 hover:bg-sky-100/60' ?>">
                    <i class="fa-solid <?= $item['icon'] ?> w-5 text-center text-base <?= $isActive ? 'text-white' : 'text-slate-600 group-hover:text-sky-500' ?>"></i>
                    <span><?= e($item['label']) ?></span>
                </a>
            <?php endif; ?>
        <?php endforeach; ?>
    </nav>

    <div class="p-3 border-t border-slate-200 bg-slate-50">
        <a href="../logout.php" onclick="return confirm('Apakah Anda yakin ingin keluar?');" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-bold text-red-600 hover:bg-red-100 hover:text-red-700 transition-colors">
            <i class="fa-solid fa-right-from-bracket w-5 text-center text-base"></i>
            <span>Logout</span>
        </a>
    </div>
</aside>