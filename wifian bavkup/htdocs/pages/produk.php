<?php
// pages/produk.php
$pageTitle = 'Data Produk';
require_once __DIR__ . '/../includes/header.php';
$pdo = getDBConnection();

$filter = $_GET['filter'] ?? 'semua'; // semua, menipis, habis

$query = "
    SELECT p.*, c.nama_kategori 
    FROM products p 
    LEFT JOIN categories c ON p.kategori_id = c.id 
";

if ($filter === 'menipis') {
    $query .= " WHERE p.stok <= p.stok_minimum AND p.stok > 0 ";
} elseif ($filter === 'habis') {
    $query .= " WHERE p.stok = 0 ";
}

$query .= " ORDER BY p.id DESC ";
$products = $pdo->query($query)->fetchAll();

// Fetch categories for dropdown
$categories = $pdo->query("SELECT * FROM categories ORDER BY nama_kategori ASC")->fetchAll();

// Auto generate next code
$lastId = $pdo->query("SELECT MAX(id) FROM products")->fetchColumn() ?: 0;
$nextCode = 'BRG-' . str_pad($lastId + 1, 4, '0', STR_PAD_LEFT);
?>

<!-- Header Actions -->
<div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6">
    <div>
        <h2 class="text-xl font-bold text-slate-800">Katalog & Data Barang</h2>
        <p class="text-xs text-slate-500">Kelola informasi barang, stok minimum, dan lokasi barang</p>
    </div>

    <button onclick="openModal('modalAddProduct')" class="inline-flex items-center gap-2 bg-sky-600 hover:bg-sky-700 text-white font-semibold text-sm px-4 py-2.5 rounded-xl shadow-md shadow-sky-600/20 transition-all">
        <i class="fa-solid fa-plus"></i>
        <span>Tambah Barang Baru</span>
    </button>
</div>

<!-- Product Table -->
<!-- Product Table -->
<div class="bg-white rounded-2xl border border-slate-200/80 shadow-subtle overflow-hidden">
    <div class="overflow-x-auto">
        <table id="tableProduct" class="w-full text-left text-sm text-slate-600">
            <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500 font-semibold border-b border-slate-200/80">
                <tr>
                    <th class="px-5 py-4">Kode</th>
                    <th class="px-5 py-4">Nama Barang</th>
                    <th class="px-5 py-4">Kategori</th>
                    <th class="px-5 py-4">Lokasi Rak</th>
                    <th class="px-5 py-4 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($products)): ?>
                    <tr>
                        <td colspan="5" class="px-5 py-12 text-center text-slate-400">
                            <i class="fa-solid fa-box-open text-4xl mb-2 block text-slate-300"></i>
                            Tidak ada data barang ditemukan.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($products as $p): ?>
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="px-5 py-4 font-mono text-xs font-bold text-slate-800">
                                <?= e($p['kode_barang']) ?>
                            </td>
                            <td class="px-5 py-4 font-semibold text-slate-800">
                                <?= e($p['nama_barang']) ?>
                                <?php if (!empty($p['deskripsi'])): ?>
                                    <div class="text-[11px] font-normal text-slate-400 truncate max-w-xs"><?= e($p['deskripsi']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="px-5 py-4 text-xs font-medium text-slate-600">
                                <span class="bg-slate-100 px-2.5 py-1 rounded-lg border border-slate-200">
                                    <?= e($p['nama_kategori'] ?? 'Tanpa Kategori') ?>
                                </span>
                            </td>
                            <td class="px-5 py-4 text-xs font-medium text-slate-700">
                                <i class="fa-solid fa-layer-group text-slate-400 mr-1"></i><?= e($p['lokasi_rak'] ?: '-') ?>
                            </td>
                            <td class="px-5 py-4 text-right whitespace-nowrap">
                                <div class="inline-flex items-center justify-end gap-1.5">
                                    <!-- Tombol Stok Opname -->
                                    <a href="stok_opname.php?product_id=<?= $p['id'] ?>" class="inline-flex items-center justify-center px-3 py-1.5 rounded-lg text-xs font-semibold bg-purple-50 hover:bg-purple-100 text-purple-700 transition-colors" title="Stok Opname">
                                        <i class="fa-solid fa-clipboard-check"></i>
                                    </a>

                                    <!-- Tombol Edit -->
                                    <button onclick='editProduct(<?= json_encode($p) ?>)' class="inline-flex items-center justify-center px-3 py-1.5 rounded-lg text-xs font-semibold bg-slate-100 hover:bg-sky-50 text-slate-700 hover:text-sky-600 transition-colors" title="Edit Produk">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>

                                    <!-- Tombol Hapus -->
                                    <form action="../actions/produk_action.php" method="POST" class="inline-flex m-0 p-0" onsubmit="return confirm('Apakah Anda yakin ingin menghapus produk ini?');">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="act" value="delete">
                                        <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                        <button type="submit" class="inline-flex items-center justify-center px-3 py-1.5 rounded-lg text-xs font-semibold bg-slate-100 hover:bg-rose-50 text-slate-700 hover:text-rose-600 transition-colors" title="Hapus Produk">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<!-- Modal Tambah Produk -->
<div id="modalAddProduct" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-2xl w-full p-6 shadow-2xl border border-slate-100 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
            <h3 class="text-lg font-bold text-slate-800">Tambah Barang Baru</h3>
            <button onclick="closeModal('modalAddProduct')" class="text-slate-400 hover:text-slate-600">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>
        <form action="../actions/produk_action.php" method="POST" class="mt-4 space-y-4">
            <?= csrfField() ?>
            <input type="hidden" name="act" value="add">

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Kode Barang</label>
                    <input type="text" name="kode_barang" value="<?= e($nextCode) ?>" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-sky-500 font-mono text-sm font-bold bg-slate-50">
                    <span class="text-[11px] text-slate-400">Kode dibuat otomatis, dapat disesuaikan.</span>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Nama Barang</label>
                    <input type="text" name="nama_barang" required placeholder="Contoh: Kabel UTP Cat6 305m" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-sky-500 text-sm">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Kategori</label>
                    <select name="kategori_id" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-sky-500 text-sm">
                        <option value="0">-- Pilih Kategori --</option>
                        <?php foreach ($categories as $c): ?>
                            <option value="<?= $c['id'] ?>"><?= e($c['nama_kategori']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Lokasi Barang</label>
                    <input type="text" name="lokasi_rak" placeholder="Contoh: Rak A1, Gudang Utama B" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-sky-500 text-sm">
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Deskripsi Barang</label>
                    <textarea name="deskripsi" rows="2" placeholder="Catatan spesifikasi atau detail produk..." class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-sky-500 text-sm"></textarea>
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
                <button type="button" onclick="closeModal('modalAddProduct')" class="px-4 py-2 rounded-xl text-sm font-medium bg-slate-100 text-slate-600 hover:bg-slate-200">Batal</button>
                <button type="submit" class="px-5 py-2 rounded-xl text-sm font-semibold bg-sky-600 text-white hover:bg-sky-700 shadow-md shadow-sky-600/20">Simpan Barang</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit Produk -->
<div id="modalEditProduct" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-2xl w-full p-6 shadow-2xl border border-slate-100 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
            <h3 class="text-lg font-bold text-slate-800">Edit Barang</h3>
            <button onclick="closeModal('modalEditProduct')" class="text-slate-400 hover:text-slate-600">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>
        <form action="../actions/produk_action.php" method="POST" class="mt-4 space-y-4">
            <?= csrfField() ?>
            <input type="hidden" name="act" value="edit">
            <input type="hidden" id="edit_p_id" name="id">

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Kode Barang</label>
                    <input type="text" id="edit_p_kode" name="kode_barang" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-sky-500 font-mono text-sm font-bold bg-slate-50">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Nama Barang</label>
                    <input type="text" id="edit_p_nama" name="nama_barang" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-sky-500 text-sm">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Kategori</label>
                    <select id="edit_p_kategori" name="kategori_id" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-sky-500 text-sm">
                        <option value="0">-- Pilih Kategori --</option>
                        <?php foreach ($categories as $c): ?>
                            <option value="<?= $c['id'] ?>"><?= e($c['nama_kategori']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Satuan</label>
                    <input type="text" id="edit_p_satuan" name="satuan" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-sky-500 text-sm">
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Lokasi Barang</label>
                    <input type="text" id="edit_p_rak" name="lokasi_rak" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-sky-500 text-sm">
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Deskripsi Barangs</label>
                    <textarea id="edit_p_desk" name="deskripsi" rows="2" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-sky-500 text-sm"></textarea>
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
                <button type="button" onclick="closeModal('modalEditProduct')" class="px-4 py-2 rounded-xl text-sm font-medium bg-slate-100 text-slate-600 hover:bg-slate-200">Batal</button>
                <button type="submit" class="px-5 py-2 rounded-xl text-sm font-semibold bg-sky-600 text-white hover:bg-sky-700 shadow-md shadow-sky-600/20">Update Produk</button>
            </div>
        </form>
    </div>
</div>

<script>
function editProduct(product) {
    document.getElementById('edit_p_id').value = product.id;
    document.getElementById('edit_p_kode').value = product.kode_barang;
    document.getElementById('edit_p_nama').value = product.nama_barang;
    document.getElementById('edit_p_kategori').value = product.kategori_id || 0;
    document.getElementById('edit_p_satuan').value = product.satuan;
    document.getElementById('edit_p_rak').value = product.lokasi_rak || '';
    document.getElementById('edit_p_desk').value = product.deskripsi || '';
    openModal('modalEditProduct');
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
