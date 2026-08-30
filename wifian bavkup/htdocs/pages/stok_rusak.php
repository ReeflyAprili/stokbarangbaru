<?php
// pages/stok_rusak.php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="p-6 md:p-8 space-y-6 bg-slate-50 min-h-screen">
    
    <!-- Header Halaman -->
    <div>
        <h1 class="text-2xl font-black text-slate-800 tracking-tight">Monitoring Stok Rusak</h1>
        <p class="text-sm font-medium text-slate-400 mt-1">
            Laporan rekapitulasi jenis barang rusak serta riwayat transaksi masuk dan keluar.
        </p>
    </div>

    <!-- Navigasi Tombol Teks -->
    <div class="space-y-3 py-2">
        <button type="button" onclick="toggleSection('sec-jenis')" class="flex items-center gap-2.5 text-slate-800 font-extrabold text-base hover:opacity-80 transition-opacity cursor-pointer text-left w-full focus:outline-none">
            <span class="w-2.5 h-2.5 rounded-full bg-red-500 inline-block"></span>
            Total Jenis Barang Rusak
        </button>

        <button type="button" onclick="toggleSection('sec-masuk')" class="flex items-center gap-2.5 text-slate-800 font-extrabold text-base hover:opacity-80 transition-opacity cursor-pointer text-left w-full focus:outline-none">
            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 inline-block"></span>
            Riwayat Stok Rusak Masuk
        </button>

        <button type="button" onclick="toggleSection('sec-keluar')" class="flex items-center gap-2.5 text-slate-800 font-extrabold text-base hover:opacity-80 transition-opacity cursor-pointer text-left w-full focus:outline-none">
            <span class="w-2.5 h-2.5 rounded-full bg-rose-500 inline-block"></span>
            Riwayat Stok Rusak Keluar
        </button>
    </div>

    <!-- Container Content Area -->
    <div class="space-y-6 pt-2">
        
        <!-- Section 1: Jenis Barang Rusak -->
        <div id="sec-jenis" class="section-content">
            <?php 
            if (file_exists(__DIR__ . '/jenis_barang.php')) {
                include __DIR__ . '/jenis_barang.php'; 
            }
            ?>
        </div>

        <!-- Section 2: Riwayat Masuk -->
        <div id="sec-masuk" class="section-content hidden">
            <?php 
            if (file_exists(__DIR__ . '/riwayat_masuk.php')) {
                include __DIR__ . '/riwayat_masuk.php'; 
            }
            ?>
        </div>

        <!-- Section 3: Riwayat Keluar -->
        <div id="sec-keluar" class="section-content hidden">
            <?php 
            if (file_exists(__DIR__ . '/riwayat_keluar.php')) {
                include __DIR__ . '/riwayat_keluar.php'; 
            }
            ?>
        </div>

    </div>

</div>

<!-- Script Logika Buka-Tutup & Scroll -->
<script>
function toggleSection(idTarget) {
    const sections = document.querySelectorAll('.section-content');
    sections.forEach(sec => {
        sec.classList.add('hidden');
    });

    const target = document.getElementById(idTarget);
    if (target) {
        target.classList.remove('hidden');
        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>