<?php
// pages/barang_keluar.php

// Mulai sesi jika belum aktif
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Handle Aksi POST & Redirect HARUS di paling atas SEBELUM ada header.php / HTML yang diload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_type'])) {
    
    // Koneksi database untuk proses POST
    require_once __DIR__ . '/../config/database.php';
    $pdo = getDBConnection();

    if (function_exists('verifyCsrfToken') && !verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Token keamanan tidak valid.'];
        header('Location: barang_keluar.php');
        exit;
    }

    // Aksi: Simpan Transaksi Barang Keluar Baru
    if ($_POST['action_type'] === 'insert') {
        $nomor_transaksi    = trim($_POST['nomor_transaksi'] ?? '');
        $tanggal            = trim($_POST['tanggal'] ?? '');
        $product_id         = intval($_POST['product_id'] ?? 0);
        $jumlah             = intval($_POST['jumlah'] ?? 0);
        $tujuan             = trim($_POST['tujuan'] ?? '');
        $kondisi            = trim($_POST['kondisi'] ?? 'Tidak Rusak');
        $alasan_rusak       = trim($_POST['alasan_rusak'] ?? '');
        $catatan_pemasangan = trim($_POST['catatan_pemasangan'] ?? '');
        $created_by         = $_SESSION['user_id'] ?? null;

        if (empty($nomor_transaksi) || empty($tanggal) || $product_id <= 0 || $jumlah <= 0 || empty($tujuan)) {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Form tidak lengkap atau jumlah tidak valid.'];
            header('Location: barang_keluar.php');
            exit;
        }

        // Cek stok produk saat ini
        $stmtCheck = $pdo->prepare("SELECT stok FROM products WHERE id = ?");
        $stmtCheck->execute([$product_id]);
        $currentStock = $stmtCheck->fetchColumn();

        if ($currentStock === false || $jumlah > $currentStock) {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Stok produk tidak mencukupi untuk transaksi ini.'];
            header('Location: barang_keluar.php');
            exit;
        }

        try {
            $pdo->beginTransaction();

            // 1. Insert ke tabel stock_out (status_pakai default 'Dipakai' untuk fitur pengembalian di dashboard)
            // Pastikan migrasi status_pakai sudah ada (ensure via getDBConnection sudah jalan)
            $hasStatusCol = false;
            try {
                $colCheck = $pdo->query("SHOW COLUMNS FROM stock_out LIKE 'status_pakai'")->rowCount();
                $hasStatusCol = $colCheck > 0;
            } catch (Exception $e) { $hasStatusCol = false; }

            if ($hasStatusCol) {
                $stmt = $pdo->prepare("
                    INSERT INTO stock_out (nomor_transaksi, tanggal, product_id, jumlah, tujuan, kondisi, alasan_rusak, catatan_pemasangan, keterangan, status_pakai, created_by, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Dipakai', ?, NOW())
                ");
                $stmt->execute([
                    $nomor_transaksi, 
                    $tanggal, 
                    $product_id, 
                    $jumlah, 
                    $tujuan, 
                    $kondisi, 
                    ($kondisi === 'Rusak' ? $alasan_rusak : null), 
                    $catatan_pemasangan,
                    $catatan_pemasangan,
                    $created_by
                ]);
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO stock_out (nomor_transaksi, tanggal, product_id, jumlah, tujuan, kondisi, alasan_rusak, catatan_pemasangan, created_by, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([
                    $nomor_transaksi, 
                    $tanggal, 
                    $product_id, 
                    $jumlah, 
                    $tujuan, 
                    $kondisi, 
                    ($kondisi === 'Rusak' ? $alasan_rusak : null), 
                    $catatan_pemasangan, 
                    $created_by
                ]);
            }

            // 2. Kurangi stok di tabel products
            $stmtUpdate = $pdo->prepare("UPDATE products SET stok = stok - ? WHERE id = ?");
            $stmtUpdate->execute([$jumlah, $product_id]);

            $pdo->commit();
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Transaksi barang keluar berhasil disimpan dan stok berhasil dikurangi.'];
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Gagal menyimpan transaksi: ' . $e->getMessage()];
        }

        header('Location: barang_keluar.php');
        exit;
    }

    if ($_POST['action_type'] === 'delete_single' && !empty($_POST['id'])) {
        $idToDelete = intval($_POST['id']);
        // Hapus riwayat saja tanpa menyentuh stok produk
        $stmt = $pdo->prepare("DELETE FROM stock_out WHERE id = ?");
        $stmt->execute([$idToDelete]);
        
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Riwayat transaksi barang keluar berhasil dihapus (stok barang aman/tidak berubah).'];
        header('Location: barang_keluar.php');
        exit;
    }

    if ($_POST['action_type'] === 'delete_all') {
        // Hapus seluruh riwayat stock_out tanpa menyentuh stok produk
        $pdo->exec("DELETE FROM stock_out");
        
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Seluruh riwayat transaksi barang keluar berhasil dibersihkan (stok barang aman/tidak berubah).'];
        header('Location: barang_keluar.php');
        exit;
    }
}

// 2. Muat header tampilan HTML halaman
$pageTitle = 'Barang Keluar';
require_once __DIR__ . '/../includes/header.php';
$pdo = getDBConnection();

// Auto generate nomor transaksi dengan format tanggal dmY (BK-DDMMYYYY-001)
$todayStr = date('dmY');
$countToday = $pdo->query("SELECT COUNT(*) FROM stock_out WHERE DATE(created_at) = CURDATE()")->fetchColumn() ?: 0;
$nextSequence = str_pad($countToday + 1, 3, '0', STR_PAD_LEFT);
$autoNoTx = 'BK-' . $todayStr . '-' . $nextSequence;

// Fetch daftar barang untuk dropdown
$products = $pdo->query("SELECT id, kode_barang, nama_barang, satuan, stok FROM products ORDER BY nama_barang ASC")->fetchAll();

// Fetch riwayat transaksi barang keluar
$history = $pdo->query("
    SELECT so.*, p.kode_barang, p.nama_barang, p.satuan, u.nama as nama_user
    FROM stock_out so
    JOIN products p ON so.product_id = p.id
    LEFT JOIN users u ON so.created_by = u.id
    ORDER BY so.created_at DESC, so.id DESC
")->fetchAll();
?>

<!-- Container Form (Full Width 100% Mengikuti Lebar Tabel di Bawah) -->
<div class="mb-8 w-full">
    
    <!-- Form Card -->
    <div class="bg-white p-6 md:p-8 rounded-2xl border border-slate-200/80 shadow-subtle">
        <div class="flex items-center justify-between pb-4 mb-4 border-b border-slate-100">
            <div>
                <h3 class="text-base font-bold text-slate-800">Form Transaksi Barang Keluar</h3>
                <p class="text-xs text-slate-500">Pengurangan stok untuk pengiriman pasokan / pelanggan / proyek</p>
            </div>
            <span class="p-2 bg-amber-50 text-amber-700 rounded-xl text-xs font-bold flex items-center gap-1.5 border border-amber-200">
                <i class="fa-solid fa-arrow-up-from-bracket"></i> Stok -
            </span>
        </div>

        <!-- PERBAIKAN: action dikosongkan agar diproses di file ini sendiri -->
        <form action="" method="POST" class="space-y-4" onsubmit="return validateStockOut()">
            <?= csrfField() ?>
            <input type="hidden" name="action_type" value="insert">

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Nomor Transaksi -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Nomor Transaksi</label>
                    <input id="nomor_transaksi" type="text" name="nomor_transaksi" value="<?= e($autoNoTx) ?>" data-next-seq="<?= e($nextSequence) ?>" required readonly class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 bg-slate-100 font-mono font-bold text-slate-700 text-sm">
                </div>

                <!-- Tanggal Keluar -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Tanggal Keluar</label>
                    <input id="tanggal_keluar" type="date" name="tanggal" value="<?= date('Y-m-d') ?>" required onchange="onTanggalChangeOut(this)" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-amber-500 text-sm">
                    <p id="tanggal_keluar_hari" class="mt-1 text-xs text-slate-500 font-medium"></p>
                </div>

                <!-- Informasi Barang -->
                <div class="col-span-full grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="md:col-span-1">
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Pilih Barang</label>
                        <select id="select_product" name="product_id" required onchange="onSelectProductOut(this)" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-amber-500 text-sm font-medium">
                            <option value="">-- Pilih Barang --</option>
                            <?php foreach ($products as $p): ?>
                                <option value="<?= $p['id'] ?>" data-satuan="<?= e($p['satuan']) ?>" data-stok="<?= $p['stok'] ?>" data-kode="<?= e($p['kode_barang']) ?>">
                                    [<?= e($p['kode_barang']) ?>] <?= e($p['nama_barang']) ?> (Stok: <?= number_format($p['stok']) ?> <?= e($p['satuan']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Jumlah Keluar</label>
                        <input type="number" id="input_jumlah" name="jumlah" min="1" required placeholder="0" onkeyup="checkStockLimit()" onchange="checkStockLimit()" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-amber-500 font-mono text-sm font-bold">
                        <div id="stock_warning" class="text-xs font-bold text-rose-600 mt-1.5 hidden flex items-center gap-1">
                            <i class="fa-solid fa-circle-exclamation"></i> Jumlah melebihi stok yang tersedia.
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Satuan</label>
                        <input id="input_satuan" name="satuan" type="text" readonly class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 bg-slate-100 text-slate-600 text-sm font-medium" placeholder="Unit">
                    </div>
                </div>

                <!-- Tujuan / Pelanggan -->
                <div class="col-span-full">
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Tujuan / Pelanggan / Proyek</label>
                    <input type="text" name="tujuan" required placeholder="Contoh: PT Telkom Akses, Proyek Site Surabaya" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-amber-500 text-sm">
                </div>

                <!-- Kondisi Barang -->
                <div class="col-span-full">
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Kondisi Barang</label>
                    <select id="kondisi_barang_keluar" name="kondisi" onchange="toggleRusakFieldKeluar()" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-amber-500 text-sm text-slate-700 font-medium">
                        <option value="Tidak Rusak">Tidak Rusak / Baik</option>
                        <option value="Rusak">Rusak</option>
                    </select>
                </div>

                <!-- Alasan Rusak (Hidden jika Tidak Rusak) -->
                <div id="alasan_rusak_group_keluar" class="col-span-full hidden">
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Alasan / Detail Kerusakan</label>
                    <textarea id="alasan_rusak_keluar" name="alasan_rusak" rows="2" placeholder="Jelaskan kondisi kerusakan barang..." class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-amber-500 text-sm"></textarea>
                </div>

                <!-- Keterangan -->
                <div class="col-span-full">
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Catatan / Keterangan</label>
                    <textarea name="catatan_pemasangan" rows="2" placeholder="Catatan tambahan keperluan pengeluaran barang..." class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-amber-500 text-sm"></textarea>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="pt-4 border-t border-slate-100 flex justify-end">
                <button type="submit" id="btn_submit" class="inline-flex items-center gap-2 bg-amber-600 hover:bg-amber-700 text-white font-bold text-xs px-5 py-2.5 rounded-xl shadow-md shadow-amber-600/20 transition-all">
                    <i class="fa-solid fa-paper-plane"></i>
                    <span>Simpan Barang Keluar</span>
                </button>
            </div>
        </form>
    </div>

</div>

<!-- Tabel Riwayat Transaksi -->
<div id="riwayat-keluar" class="bg-white rounded-2xl border border-slate-200/80 shadow-subtle overflow-hidden w-full">
    <div class="p-5 border-b border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-4">
        <div>
            <h3 class="text-base font-bold text-slate-800">Riwayat Barang Keluar</h3>
            <p class="text-xs text-slate-500">Daftar rekapan transaksi pengeluaran stok barang</p>
        </div>
        
        <div class="flex items-center gap-2 w-full sm:w-auto">
            <div class="relative w-full sm:w-72">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </span>
                <input type="text" id="searchKeluar" onkeyup="filterTable('searchKeluar', 'tableKeluar')" placeholder="Cari no tx, nama barang, tujuan..." class="w-full pl-10 pr-4 py-2 text-sm bg-slate-50 border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-amber-500">
            </div>
            
            <!-- Tombol Hapus Semua -->
            <?php if (!empty($history)): ?>
            <form action="" method="POST" onsubmit="return confirm('PERINGATAN: Apakah Anda yakin ingin menghapus SELURUH riwayat barang keluar? (Stok produk TIDAK akan berubah/dikembalikan)');">
                <?= csrfField() ?>
                <input type="hidden" name="action_type" value="delete_all">
                <button type="submit" class="inline-flex items-center gap-1.5 bg-rose-50 hover:bg-rose-100 text-rose-600 border border-rose-200 px-4 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap">
                    <i class="fa-solid fa-trash-can"></i>
                    <span>Hapus Semua</span>
                </button>
            </form>
            <?php endif; ?>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table id="tableKeluar" class="w-full text-left text-sm text-slate-600">
            <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500 font-semibold border-b border-slate-200/80">
                <tr>
                    <th class="px-5 py-3.5">No. Transaksi</th>
                    <th class="px-5 py-3.5">Tanggal</th>
                    <th class="px-5 py-3.5">Nama Barang</th>
                    <th class="px-5 py-3.5 text-right">Jumlah Keluar</th>
                    <th class="px-5 py-3.5">Tujuan / Pelanggan</th>
                    <th class="px-5 py-3.5">User / Operator</th>
                    <th class="px-5 py-3.5 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($history)): ?>
                    <tr>
                        <td colspan="7" class="px-5 py-8 text-center text-slate-400">
                            Belum ada riwayat transaksi barang keluar.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($history as $h): ?>
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="px-5 py-3.5 font-mono text-xs font-bold text-amber-700">
                                <?= e($h['nomor_transaksi']) ?>
                            </td>
                            <td class="px-5 py-3.5 text-xs text-slate-600">
                                <?= e(formatDateWithDay($h['tanggal'])) ?>
                            </td>
                            <td class="px-5 py-3.5 font-semibold text-slate-800">
                                <?= e($h['nama_barang']) ?>
                            </td>
                            <td class="px-5 py-3.5 text-right font-mono font-bold text-amber-600">
                                -<?= number_format($h['jumlah']) ?> <?= e($h['satuan']) ?>
                            </td>
                            <td class="px-5 py-3.5 font-medium text-slate-700">
                                <i class="fa-solid fa-location-dot text-rose-500 mr-1 text-xs"></i><?= e($h['tujuan']) ?>
                            </td>
                            <td class="px-5 py-3.5 text-xs text-slate-500">
                                <i class="fa-regular fa-user mr-1 text-slate-400"></i><?= e($h['nama_user'] ?? 'System') ?>
                            </td>
                            <td class="px-5 py-3.5 text-center">
                                <form action="" method="POST" class="inline-block" onsubmit="return confirm('Apakah Anda yakin ingin menghapus riwayat transaksi ini? (Stok produk TIDAK akan berubah/dikembalikan)');">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action_type" value="delete_single">
                                    <input type="hidden" name="id" value="<?= $h['id'] ?>">
                                    <button type="submit" class="p-2 text-rose-600 hover:text-rose-800 hover:bg-rose-50 rounded-lg transition-colors" title="Hapus Transaksi">
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
let currentAvailableStock = -1;

function toggleRusakFieldKeluar() {
    const kondisi = document.getElementById('kondisi_barang_keluar');
    const group = document.getElementById('alasan_rusak_group_keluar');
    const alasan = document.getElementById('alasan_rusak_keluar');
    if (!kondisi || !group || !alasan) return;

    const isRusak = kondisi.value === 'Rusak';
    if (isRusak) {
        group.classList.remove('hidden');
        alasan.setAttribute('required', 'required');
    } else {
        group.classList.add('hidden');
        alasan.removeAttribute('required');
    }
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

function onSelectProductOut(select) {
    const selectedOption = select.options[select.selectedIndex];
    if (!selectedOption || !select.value) {
        currentAvailableStock = -1;
        document.getElementById('input_satuan').value = '';
        checkStockLimit();
        return;
    }

    const satuan = selectedOption.getAttribute('data-satuan') || 'Unit';
    const kode = selectedOption.getAttribute('data-kode') || '';
    currentAvailableStock = parseInt(selectedOption.getAttribute('data-stok') || '0');

    const satuanInput = document.getElementById('input_satuan');
    if (satuanInput) satuanInput.value = satuan;

    setTransactionNumber('BK', kode, 'tanggal_keluar', 'nomor_transaksi');
    checkStockLimit();
}

function onTanggalChangeOut(input) {
    renderDayName(input, 'tanggal_keluar_hari');
    const selectProduct = document.getElementById('select_product');
    const selectedOption = selectProduct ? selectProduct.options[selectProduct.selectedIndex] : null;
    const kode = selectedOption ? selectedOption.getAttribute('data-kode') : '';
    setTransactionNumber('BK', kode, 'tanggal_keluar', 'nomor_transaksi');
}

function setTransactionNumber(prefix, kode, tanggalId, nomorId) {
    const nomorInput = document.getElementById(nomorId);
    const tanggal = document.getElementById(tanggalId).value || '';
    
    let formattedDate = '';
    if (tanggal) {
        const parts = tanggal.split('-'); 
        if (parts.length === 3) {
            formattedDate = parts[2] + parts[1] + parts[0]; 
        }
    }

    const seq = nomorInput?.getAttribute('data-next-seq') || '001';
    
    let nomor = prefix + '-' + formattedDate;
    if (kode) {
        nomor += '-' + kode;
    }
    nomor += '-' + seq;
    
    if (nomorInput) nomorInput.value = nomor;
}

function checkStockLimit() {
    const inputJml = parseInt(document.getElementById('input_jumlah').value || '0');
    const warning = document.getElementById('stock_warning');
    const btn = document.getElementById('btn_submit');

    if (currentAvailableStock < 0) {
        warning.classList.add('hidden');
        if(btn) {
            btn.disabled = false;
            btn.classList.remove('opacity-50', 'cursor-not-allowed');
        }
        return;
    }

    if (inputJml > currentAvailableStock && currentAvailableStock >= 0) {
        warning.classList.remove('hidden');
        if(btn) {
            btn.disabled = true;
            btn.classList.add('opacity-50', 'cursor-not-allowed');
        }
    } else {
        warning.classList.add('hidden');
        if(btn) {
            btn.disabled = false;
            btn.classList.remove('opacity-50', 'cursor-not-allowed');
        }
    }
}

function validateStockOut() {
    const inputJml = parseInt(document.getElementById('input_jumlah').value || '0');
    const productSelected = document.getElementById('select_product').value;
    const kondisi = document.getElementById('kondisi_barang_keluar');
    const alasan = document.getElementById('alasan_rusak_keluar');

    if (!productSelected) {
        alert('Pilih barang terlebih dahulu.');
        return false;
    }

    if (inputJml > currentAvailableStock) {
        alert('Stok barang tidak mencukupi.');
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
    toggleRusakFieldKeluar();

    const selectProduct = document.getElementById('select_product');
    if (selectProduct && selectProduct.value) {
        onSelectProductOut(selectProduct);
    }

    const tanggalKeluar = document.getElementById('tanggal_keluar');
    if (tanggalKeluar) renderDayName(tanggalKeluar, 'tanggal_keluar_hari');
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>