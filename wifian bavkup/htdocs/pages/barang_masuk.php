<?php
// pages/barang_masuk.php

// 1. Mulai sesi jika belum aktif (opsional, pastikan auth.php sudah menanganinya)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Handle Aksi POST & Redirect HARUS di paling atas SEBELUM ada file header.php / HTML yang diload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_type'])) {
    
    // Koneksi database darurat khusus untuk proses POST di atas
    require_once __DIR__ . '/../config/database.php'; // Sesuaikan path file koneksi DB Anda jika perlu
    $pdo = getDBConnection();

    if (function_exists('verifyCsrfToken') && !verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Token keamanan tidak valid.'];
        header('Location: barang_masuk.php');
        exit;
    }

    if ($_POST['action_type'] === 'delete_single' && !empty($_POST['id'])) {
        $idToDelete = intval($_POST['id']);
        $stmt = $pdo->prepare("DELETE FROM stock_in WHERE id = ?");
        $stmt->execute([$idToDelete]);
        
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Riwayat transaksi berhasil dihapus (stok barang aman/tidak berubah).'];
        header('Location: barang_masuk.php');
        exit;
    }

    if ($_POST['action_type'] === 'delete_all') {
        $pdo->exec("DELETE FROM stock_in");
        
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Seluruh riwayat transaksi berhasil dibersihkan (stok barang aman/tidak berubah).'];
        header('Location: barang_masuk.php');
        exit;
    }
}
$pageTitle = 'Barang Masuk';
require_once __DIR__ . '/../includes/header.php';
// ... lanjut ke kode berikutnya
// pages/barang_masuk.php
$pageTitle = 'Barang Masuk';
require_once __DIR__ . '/../includes/header.php';
$pdo = getDBConnection();

// Handle Aksi Hapus Satuan atau Hapus Semua
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_type'])) {
    // Validasi CSRF jika fungsi tersedia di project Anda
    if (function_exists('verifyCsrfToken') && !verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Token keamanan tidak valid.'];
        header('Location: barang_masuk.php');
        exit;
    }

    if ($_POST['action_type'] === 'delete_single' && !empty($_POST['id'])) {
        $idToDelete = intval($_POST['id']);
        // Hapus riwayat saja tanpa menyentuh stok produk
        $stmt = $pdo->prepare("DELETE FROM stock_in WHERE id = ?");
        $stmt->execute([$idToDelete]);
        
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Riwayat transaksi berhasil dihapus (stok barang aman/tidak berubah).'];
        header('Location: barang_masuk.php');
        exit;
    }

    if ($_POST['action_type'] === 'delete_all') {
        // Hapus seluruh riwayat stock_in tanpa menyentuh stok produk
        $pdo->exec("DELETE FROM stock_in");
        
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Seluruh riwayat transaksi berhasil dibersihkan (stok barang aman/tidak berubah).'];
        header('Location: barang_masuk.php');
        exit;
    }
}

// Auto generate transaction number base (BM-DDMMYYYY-001)
$todayStr = date('dmY');
$countToday = $pdo->query("SELECT COUNT(*) FROM stock_in WHERE DATE(created_at) = CURDATE()")->fetchColumn() ?: 0;
$nextSequence = str_pad($countToday + 1, 3, '0', STR_PAD_LEFT);
$autoNoTx = 'BM-' . $todayStr . '-' . $nextSequence;

// Fetch all products for dropdown
$products = $pdo->query("SELECT id, kode_barang, nama_barang, satuan, harga_beli, stok FROM products ORDER BY nama_barang ASC")->fetchAll();

// Fetch history
$history = $pdo->query("
    SELECT si.*, p.kode_barang, p.nama_barang, p.satuan, u.nama as nama_user
    FROM stock_in si
    JOIN products p ON si.product_id = p.id
    LEFT JOIN users u ON si.created_by = u.id
    ORDER BY si.created_at DESC, si.id DESC
")->fetchAll();
?>

<!-- Container Form (Full Width 100% Mengikuti Lebar Tabel di Bawah) -->
<div class="mb-8 w-full">
    
    <!-- Form Card -->
    <div class="bg-white p-6 md:p-8 rounded-2xl border border-slate-200/80 shadow-subtle">
        <div class="flex items-center justify-between pb-4 mb-4 border-b border-slate-100">
            <div>
                <h3 class="text-base font-bold text-slate-800">Form Transaksi Barang Masuk</h3>
                <p class="text-xs text-slate-500">Penerimaan pasokan barang baru ke dalam gudang</p>
            </div>
            <span class="p-2 bg-sky-50 text-sky-700 rounded-xl text-xs font-bold flex items-center gap-1.5 border border-sky-200">
                <i class="fa-solid fa-download"></i> Stok +
            </span>
        </div>

        <form id="form_barang_masuk" action="../actions/barang_masuk_action.php" method="POST" class="space-y-4" onsubmit="return validateBarangMasuk();">
            <?= csrfField() ?>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Nomor Transaksi -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Nomor Transaksi</label>
                    <input id="nomor_transaksi" type="text" name="nomor_transaksi" value="<?= e($autoNoTx) ?>" data-next-seq="<?= e($nextSequence) ?>" required readonly class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 bg-slate-100 font-mono font-bold text-slate-700 text-sm">
                </div>

                <!-- Tanggal Masuk -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Tanggal Masuk</label>
                    <input id="tanggal_masuk" type="date" name="tanggal" value="<?= date('Y-m-d') ?>" required onchange="onTanggalChangeIn(this)" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-sky-500 text-sm">
                    <p id="tanggal_masuk_hari" class="mt-1 text-xs text-slate-500 font-medium"></p>
                </div>

                <!-- Informasi Barang -->
                <div class="col-span-full grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="md:col-span-1">
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Pilih Barang</label>
                        <select id="select_product" name="product_id" required onchange="onSelectProduct(this)" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-sky-500 text-sm font-medium">
                            <option value="">-- Pilih Barang --</option>
                            <?php foreach ($products as $p): ?>
                                <option value="<?= $p['id'] ?>" data-satuan="<?= e($p['satuan']) ?>" data-harga="<?= $p['harga_beli'] ?>" data-kode="<?= e($p['kode_barang']) ?>" data-stok="<?= $p['stok'] ?>">
                                    [<?= e($p['kode_barang']) ?>] <?= e($p['nama_barang']) ?> (Stok: <?= number_format($p['stok']) ?> <?= e($p['satuan']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Jumlah Masuk</label>
                        <input type="number" name="jumlah" min="1" required placeholder="0" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-sky-500 font-mono text-sm font-bold">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Satuan</label>
                        <input id="input_satuan" name="satuan" type="text" readonly class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 bg-slate-100 text-slate-600 text-sm font-medium" placeholder="Unit">
                    </div>
                </div>

                <!-- Kondisi Barang -->
                <div class="col-span-full">
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Kondisi Barang</label>
                    <select id="kondisi_barang_masuk" name="kondisi" onchange="toggleRusakFieldMasuk()" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-sky-500 text-sm text-slate-700 font-medium">
                        <option value="Tidak Rusak">Tidak Rusak / Baik</option>
                        <option value="Rusak">Rusak</option>
                    </select>
                </div>

                <!-- Alasan Rusak (Hidden jika Tidak Rusak) -->
                <div id="alasan_rusak_group_masuk" class="col-span-full hidden">
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Alasan Barang Rusak</label>
                    <textarea id="alasan_rusak_masuk" name="alasan_rusak" rows="2" placeholder="Contoh: Kabel sobek saat pengecekan awal, kemasan rusak, dsb." class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-sky-500 text-sm"></textarea>
                </div>

                <!-- Keterangan / Catatan Vendor -->
                <div class="col-span-full">
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Keterangan / Catatan Vendor</label>
                    <textarea name="keterangan" rows="2" placeholder="Contoh: Pembelian dari Supplier PT Maju Jaya..." class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-sky-500 text-sm"></textarea>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="pt-4 border-t border-slate-100 flex justify-end">
                <button type="submit" class="inline-flex items-center gap-2 bg-sky-600 hover:bg-sky-700 text-white font-bold text-xs px-5 py-2.5 rounded-xl shadow-md shadow-sky-600/20 transition-all">
                    <i class="fa-solid fa-floppy-disk"></i>
                    <span>Simpan Barang Masuk</span>
                </button>
            </div>
        </form>
    </div>

</div>

<!-- Table History -->
<div id="riwayat-masuk" class="bg-white rounded-2xl border border-slate-200/80 shadow-subtle overflow-hidden w-full">
    <div class="p-5 border-b border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-4">
        <div>
            <h3 class="text-base font-bold text-slate-800">Riwayat Barang Masuk</h3>
            <p class="text-xs text-slate-500">Daftar transaksi penerimaan stok barang</p>
        </div>
        
        <div class="flex items-center gap-3 w-full sm:w-auto">
            <!-- Search Box -->
            <div class="relative w-full sm:w-64">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </span>
                <input type="text" id="searchMasuk" onkeyup="filterTable('searchMasuk', 'tableMasuk')" placeholder="Cari no tx, nama barang..." class="w-full pl-10 pr-4 py-2 text-sm bg-slate-50 border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-sky-500">
            </div>

            <!-- Tombol Hapus Semua Riwayat -->
            <?php if (!empty($history)): ?>
            <form action="" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus SELURUH riwayat barang masuk? Stok produk TIDAK akan berubah.');">
                <?= csrfField() ?>
                <input type="hidden" name="action_type" value="delete_all">
                <button type="submit" title="Hapus Semua Riwayat" class="inline-flex items-center gap-1.5 bg-rose-50 hover:bg-rose-100 text-rose-600 border border-rose-200 font-bold text-xs px-3.5 py-2 rounded-xl transition-all whitespace-nowrap">
                    <i class="fa-solid fa-trash-can"></i>
                    <span class="hidden md:inline">Hapus Semua</span>
                </button>
            </form>
            <?php endif; ?>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table id="tableMasuk" class="w-full text-left text-sm text-slate-600">
            <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500 font-semibold border-b border-slate-200/80">
                <tr>
                    <th class="px-5 py-3.5">No. Transaksi</th>
                    <th class="px-5 py-3.5">Tanggal</th>
                    <th class="px-5 py-3.5">Kode</th>
                    <th class="px-5 py-3.5">Nama Barang</th>
                    <th class="px-5 py-3.5 text-right">Jumlah Masuk</th>
                    <th class="px-5 py-3.5">User</th>
                    <th class="px-5 py-3.5 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($history)): ?>
                    <tr>
                        <td colspan="7" class="px-5 py-8 text-center text-slate-400">
                            Belum ada riwayat transaksi barang masuk.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($history as $h): ?>
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="px-5 py-3.5 font-mono text-xs font-bold text-sky-700">
                                <?= e($h['nomor_transaksi']) ?>
                            </td>
                            <td class="px-5 py-3.5 text-xs text-slate-600">
                                <?= e(formatDateWithDay($h['tanggal'])) ?>
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
                            <td class="px-5 py-3.5 text-right font-mono font-bold text-sky-600">
                                +<?= number_format($h['jumlah']) ?> <?= e($h['satuan']) ?>
                            </td>
                            <td class="px-5 py-3.5 text-xs text-slate-500">
                                <i class="fa-regular fa-user mr-1 text-slate-400"></i><?= e($h['nama_user'] ?? 'System') ?>
                            </td>
                            <td class="px-5 py-3.5 text-center">
                                <form action="" method="POST" class="inline-block" onsubmit="return confirm('Yakin ingin menghapus riwayat transaksi ini? Stok barang di master produk tidak akan diubah.');">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action_type" value="delete_single">
                                    <input type="hidden" name="id" value="<?= $h['id'] ?>">
                                    <button type="submit" class="p-1.5 bg-rose-50 hover:bg-rose-100 text-rose-600 rounded-lg border border-rose-200 transition-colors text-xs" title="Hapus Riwayat Ini">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function toggleRusakFieldMasuk() {
    const kondisi = document.getElementById('kondisi_barang_masuk');
    const group = document.getElementById('alasan_rusak_group_masuk');
    const alasan = document.getElementById('alasan_rusak_masuk');
    if (!kondisi || !group || !alasan) return;

    const isRusak = kondisi.value === 'Rusak';
    group.classList.toggle('hidden', !isRusak);
    alasan.toggleAttribute('required', isRusak);
}

function renderDayName(input, targetId) {
    const dayNames = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    const target = document.getElementById(targetId);
    if (!target) return;
    const value = input.value;
    if (!value) {
        target.textContent = '';
        return;
    }
    const date = new Date(value + 'T00:00');
    target.textContent = dayNames[date.getDay()] + ', ' + value.split('-').reverse().join('/');
}

function onSelectProduct(select) {
    const selectedOption = select.options[select.selectedIndex];
    const satuan = selectedOption ? selectedOption.getAttribute('data-satuan') : 'Unit';
    const kode = selectedOption ? selectedOption.getAttribute('data-kode') : '';

    const inputSatuan = document.getElementById('input_satuan');
    if (inputSatuan) inputSatuan.value = satuan;

    setTransactionNumber('BM', kode, 'tanggal_masuk', 'nomor_transaksi');
}

function onTanggalChangeIn(input) {
    renderDayName(input, 'tanggal_masuk_hari');
    const select = document.getElementById('select_product');
    const selectedOption = select ? select.options[select.selectedIndex] : null;
    const kode = selectedOption ? selectedOption.getAttribute('data-kode') : '';
    setTransactionNumber('BM', kode, 'tanggal_masuk', 'nomor_transaksi');
}

function setTransactionNumber(prefix, kode, tanggalId, nomorId) {
    const nomorInput = document.getElementById(nomorId);
    const tanggalValue = document.getElementById(tanggalId).value || '';
    
    let rawDate = '';
    if (tanggalValue) {
        const parts = tanggalValue.split('-'); 
        if (parts.length === 3) {
            rawDate = parts[2] + parts[1] + parts[0]; 
        }
    }
    
    const seq = nomorInput?.getAttribute('data-next-seq') || '001';
    
    let nomor = prefix + '-' + rawDate;
    if (kode) {
        nomor += '-' + kode;
    }
    nomor += '-' + seq;
    
    if (nomorInput) nomorInput.value = nomor;
}

function validateBarangMasuk() {
    const product = document.getElementById('select_product');
    const jumlahInput = document.querySelector('input[name="jumlah"]');
    const jumlah = parseInt(jumlahInput ? jumlahInput.value : '0');
    const kondisi = document.getElementById('kondisi_barang_masuk');
    const alasan = document.getElementById('alasan_rusak_masuk');

    if (!product || !product.value) {
        alert('Pilih barang terlebih dahulu.');
        return false;
    }

    if (!jumlah || jumlah <= 0) {
        alert('Jumlah masuk harus lebih dari 0.');
        return false;
    }

    if (kondisi && kondisi.value === 'Rusak' && alasan && (!alasan.value || !alasan.value.trim())) {
        alert('Alasan barang rusak wajib diisi.');
        alasan.focus();
        return false;
    }

    return true;
}

document.addEventListener('DOMContentLoaded', function() {
    toggleRusakFieldMasuk();

    const tanggalMasuk = document.getElementById('tanggal_masuk');
    if (tanggalMasuk) renderDayName(tanggalMasuk, 'tanggal_masuk_hari');
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>