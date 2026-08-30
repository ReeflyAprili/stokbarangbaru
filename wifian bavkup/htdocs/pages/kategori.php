<?php
// pages/kategori.php
$pageTitle = 'Kategori Barang';
require_once __DIR__ . '/../includes/header.php';
$pdo = getDBConnection();

// Fetch all categories with product count
$categories = $pdo->query("
    SELECT c.*, COUNT(p.id) as total_produk 
    FROM categories c 
    LEFT JOIN products p ON c.id = p.kategori_id 
    GROUP BY c.id 
    ORDER BY c.id DESC
")->fetchAll();
?>

<!-- Header Actions Bar -->
<div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6">
    <div>
        <h2 class="text-xl font-bold text-slate-800">Manajemen Kategori Barang</h2>
        <p class="text-xs text-slate-500">Kelompokkan barang untuk mempermudah inventarisasi</p>
    </div>
    
    <button onclick="openModal('modalAddCategory')" class="inline-flex items-center gap-2 bg-sky-600 hover:bg-sky-700 text-white font-semibold text-sm px-4 py-2.5 rounded-xl shadow-md shadow-sky-600/20 transition-all">
        <i class="fa-solid fa-plus"></i>
        <span>Tambah Kategori</span>
    </button>
</div>

<!-- Table Card -->
<div class="bg-white rounded-2xl border border-slate-200/80 shadow-subtle overflow-hidden">
    
    <!-- Table Header Toolbar -->
    <div class="p-4 border-b border-slate-100 flex flex-col sm:flex-row gap-3 justify-between items-center bg-slate-50/50">
        <div class="relative w-full sm:w-72">
            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                <i class="fa-solid fa-magnifying-glass"></i>
            </span>
            <input type="text" id="searchKategori" onkeyup="filterTable('searchKategori', 'tableKategori')" placeholder="Cari nama kategori..." class="w-full pl-9 pr-4 py-2 text-sm bg-white border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-sky-500">
        </div>
        <div class="text-xs text-slate-500">
            Total Kategori: <strong class="text-slate-800 font-bold"><?= count($categories) ?></strong>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table id="tableKategori" class="w-full text-left text-sm text-slate-600">
            <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500 font-semibold border-b border-slate-200/80">
                <tr>
                    <th class="px-6 py-4 w-20">ID</th>
                    <th class="px-6 py-4">Nama Kategori</th>
                    <th class="px-6 py-4 text-center">Jumlah Barangk</th>
                    <th class="px-6 py-4 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($categories)): ?>
                    <tr>
                        <td colspan="4" class="px-6 py-10 text-center text-slate-400 text-sm">
                            Belum ada data kategori. Klik <strong>Tambah Kategori</strong> untuk menambah.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($categories as $cat): ?>
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="px-6 py-4 font-mono text-xs font-bold text-slate-500">
                                #<?= e($cat['id']) ?>
                            </td>
                            <td class="px-6 py-4 font-semibold text-slate-800">
                                <i class="fa-solid fa-folder text-amber-500 mr-2"></i><?= e($cat['nama_kategori']) ?>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="inline-block px-3 py-1 rounded-full text-xs font-semibold bg-sky-50 text-sky-700 border border-sky-200">
                                    <?= number_format($cat['total_produk']) ?> Item
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right whitespace-nowrap">
                                <div class="inline-flex gap-2">
                                    <button onclick="editCategory(<?= $cat['id'] ?>, '<?= e(addslashes($cat['nama_kategori'])) ?>')" class="px-3 py-1.5 rounded-lg text-xs font-medium bg-slate-100 hover:bg-sky-50 text-slate-700 hover:text-sky-600 transition-colors" title="Edit">
                                        <i class="fa-solid fa-pen-to-square mr-1"></i> Edit
                                    </button>
                                    <form action="../actions/kategori_action.php" method="POST" inline onsubmit="return confirm('Apakah Anda yakin ingin menghapus kategori ini?');" class="inline">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="act" value="delete">
                                        <input type="hidden" name="id" value="<?= $cat['id'] ?>">
                                        <button type="submit" class="px-3 py-1.5 rounded-lg text-xs font-medium bg-slate-100 hover:bg-rose-50 text-slate-700 hover:text-rose-600 transition-colors" title="Hapus">
                                            <i class="fa-solid fa-trash mr-1"></i> Hapus
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

<!-- Modal Tambah Kategori -->
<div id="modalAddCategory" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-100">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
            <h3 class="text-lg font-bold text-slate-800">Tambah Kategori Baru</h3>
            <button onclick="closeModal('modalAddCategory')" class="text-slate-400 hover:text-slate-600">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>
        <form action="../actions/kategori_action.php" method="POST" class="mt-4 space-y-4">
            <?= csrfField() ?>
            <input type="hidden" name="act" value="add">
            
            <div>
                <label for="add_nama_kategori" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Nama Kategori</label>
                <input type="text" id="add_nama_kategori" name="nama_kategori" required placeholder="Contoh: Kabel, Semen, Cat, Lampu" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-sky-500 focus:outline-none text-sm">
            </div>

            <div class="flex justify-end gap-3 pt-3 border-t border-slate-100">
                <button type="button" onclick="closeModal('modalAddCategory')" class="px-4 py-2 rounded-xl text-sm font-medium bg-slate-100 text-slate-600 hover:bg-slate-200 transition-colors">Batal</button>
                <button type="submit" class="px-4 py-2 rounded-xl text-sm font-semibold bg-sky-600 text-white hover:bg-sky-700 shadow-md shadow-sky-600/20 transition-colors">Simpan Kategori</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit Kategori -->
<div id="modalEditCategory" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-100">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
            <h3 class="text-lg font-bold text-slate-800">Edit Kategori</h3>
            <button onclick="closeModal('modalEditCategory')" class="text-slate-400 hover:text-slate-600">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>
        <form action="../actions/kategori_action.php" method="POST" class="mt-4 space-y-4">
            <?= csrfField() ?>
            <input type="hidden" name="act" value="edit">
            <input type="hidden" id="edit_id" name="id" value="">
            
            <div>
                <label for="edit_nama_kategori" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Nama Kategori</label>
                <input type="text" id="edit_nama_kategori" name="nama_kategori" required class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-sky-500 focus:outline-none text-sm">
            </div>

            <div class="flex justify-end gap-3 pt-3 border-t border-slate-100">
                <button type="button" onclick="closeModal('modalEditCategory')" class="px-4 py-2 rounded-xl text-sm font-medium bg-slate-100 text-slate-600 hover:bg-slate-200 transition-colors">Batal</button>
                <button type="submit" class="px-4 py-2 rounded-xl text-sm font-semibold bg-sky-600 text-white hover:bg-sky-700 shadow-md shadow-sky-600/20 transition-colors">Update Kategori</button>
            </div>
        </form>
    </div>
</div>

<script>
function editCategory(id, nama) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_nama_kategori').value = nama;
    openModal('modalEditCategory');
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
