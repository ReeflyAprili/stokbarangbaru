<?php
// pages/pengguna.php
$pageTitle = 'Manajemen Pengguna';
require_once __DIR__ . '/../includes/header.php';
requireAdmin();

$pdo = getDBConnection();
$users = $pdo->query("SELECT * FROM users ORDER BY id DESC")->fetchAll();
?>

<!-- Header Bar -->
<div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6">
    <div>
        <h2 class="text-xl font-bold text-slate-800">Manajemen Pengguna & Hak Akses</h2>
        <p class="text-xs text-slate-500">Kelola akun pengguna, peran (Admin & Staff Gudang), serta otorisasi sistem</p>
    </div>

    <button onclick="openModal('modalAddUser')" class="inline-flex items-center gap-2 bg-sky-600 hover:bg-sky-700 text-white font-semibold text-sm px-4 py-2.5 rounded-xl shadow-md shadow-sky-600/20 transition-all">
        <i class="fa-solid fa-user-plus"></i>
        <span>Tambah Pengguna Baru</span>
    </button>
</div>

<!-- Table Card -->
<div class="bg-white rounded-2xl border border-slate-200/80 shadow-subtle overflow-hidden">
    <div class="p-4 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
        <div class="relative w-full sm:w-72">
            <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                <i class="fa-solid fa-magnifying-glass"></i>
            </span>
            <input type="text" id="searchUser" onkeyup="filterTable('searchUser', 'tableUser')" placeholder="Cari nama atau username..." class="w-full pl-10 pr-4 py-2 text-sm bg-white border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-sky-500">
        </div>
        <div class="text-xs text-slate-500">
            Total Pengguna: <strong class="text-slate-800 font-bold"><?= count($users) ?></strong>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table id="tableUser" class="w-full text-left text-sm text-slate-600">
            <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500 font-semibold border-b border-slate-200/80">
                <tr>
                    <th class="px-6 py-4">Nama Lengkap</th>
                    <th class="px-6 py-4">Username</th>
                    <th class="px-6 py-4">Role Akses</th>
                    <th class="px-6 py-4">Tanggal Dibuat</th>
                    <th class="px-6 py-4 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php foreach ($users as $u): ?>
                    <tr class="hover:bg-slate-50/80 transition-colors">
                        <td class="px-6 py-4 font-semibold text-slate-800 flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-slate-200 text-slate-700 font-bold flex items-center justify-center text-xs">
                                <?= strtoupper(substr($u['nama'], 0, 1)) ?>
                            </div>
                            <span><?= e($u['nama']) ?></span>
                            <?php if ($u['id'] == $_SESSION['user_id']): ?>
                                <span class="text-[10px] bg-emerald-100 text-emerald-700 px-2 py-0.5 rounded-full font-bold">Anda</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 font-mono text-xs font-bold text-slate-600">
                            @<?= e($u['username']) ?>
                        </td>
                        <td class="px-6 py-4">
                             <?php if ($u['role'] === 'Admin'): ?>
                                 <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-purple-100 text-purple-700 border border-purple-200">
                                 <i class="fa-solid fa-user-shield text-[10px]"></i> Admin
                                 </span>
                             <?php elseif ($u['role'] === 'Warehouse'): ?>
                                 <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-blue-100 text-blue-700 border border-blue-200">
                                 <i class="fa-solid fa-warehouse text-[10px]"></i> Warehouse
                                 </span>
                             <?php elseif ($u['role'] === 'NOC'): ?>
                                 <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-700 border border-amber-200">
                                 <i class="fa-solid fa-network-wired text-[10px]"></i> NOC
                                 </span>
                             <?php else: ?>
                                 <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-700 border border-slate-200">
                                 <i class="fa-solid fa-user text-[10px]"></i> User
                                 </span>
                             <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 text-xs text-slate-500">
                            <?= date('d/m/Y H:i', strtotime($u['created_at'])) ?>
                        </td>
                        <td class="px-6 py-4 text-right whitespace-nowrap">
                            <div class="inline-flex gap-2">
                                <button onclick='editUser(<?= json_encode($u) ?>)' class="px-3 py-1.5 rounded-lg text-xs font-medium bg-slate-100 hover:bg-sky-50 text-slate-700 hover:text-sky-600 transition-colors">
                                    <i class="fa-solid fa-pen-to-square mr-1"></i> Edit
                                </button>
                                <?php if ($u['id'] != $_SESSION['user_id']): ?>
                                    <form action="../actions/user_action.php" method="POST" class="inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus akun pengguna ini?');">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="act" value="delete">
                                        <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                        <button type="submit" class="px-3 py-1.5 rounded-lg text-xs font-medium bg-slate-100 hover:bg-rose-50 text-slate-700 hover:text-rose-600 transition-colors">
                                            <i class="fa-solid fa-trash mr-1"></i> Hapus
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Tambah User -->
<div id="modalAddUser" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-100">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
            <h3 class="text-lg font-bold text-slate-800">Tambah Pengguna Baru</h3>
            <button onclick="closeModal('modalAddUser')" class="text-slate-400 hover:text-slate-600">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>
        <form action="../actions/user_action.php" method="POST" class="mt-4 space-y-4">
            <?= csrfField() ?>
            <input type="hidden" name="act" value="add">

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Nama Lengkap</label>
                <input type="text" name="nama" required placeholder="Contoh: Reefly Aprilian" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-sky-500 text-sm">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Username</label>
                <input type="text" name="username" required placeholder="Contoh: reefly" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-sky-500 font-mono text-sm">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Password</label>
                <input type="password" name="password" required placeholder="Masukkan password" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-sky-500 text-sm">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Role / Hak Akses</label>
                <select name="role" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-sky-500 text-sm font-semibold">
                    <option value="Users">User</option>
                    <option value="NOC">NOC</option>
                    <option value="Warehouse">Staff Gudang</option>
                    <option value="Admin">Administrator</option>
                </select>
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
                <button type="button" onclick="closeModal('modalAddUser')" class="px-4 py-2 rounded-xl text-sm font-medium bg-slate-100 text-slate-600 hover:bg-slate-200">Batal</button>
                <button type="submit" class="px-5 py-2 rounded-xl text-sm font-semibold bg-sky-600 text-white hover:bg-sky-700 shadow-md shadow-sky-600/20">Simpan Pengguna</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit User -->
<div id="modalEditUser" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-100">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
            <h3 class="text-lg font-bold text-slate-800">Edit Pengguna</h3>
            <button onclick="closeModal('modalEditUser')" class="text-slate-400 hover:text-slate-600">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>
        <form action="../actions/user_action.php" method="POST" class="mt-4 space-y-4">
            <?= csrfField() ?>
            <input type="hidden" name="act" value="edit">
            <input type="hidden" id="edit_u_id" name="id">

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Nama Lengkap</label>
                <input type="text" id="edit_u_nama" name="nama" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-sky-500 text-sm">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Username</label>
                <input type="text" id="edit_u_username" name="username" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-sky-500 font-mono text-sm">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Password Baru (Kosongkan jika tidak diubah)</label>
                <input type="password" name="password" placeholder="Password baru..." class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-sky-500 text-sm">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Role / Hak Akses</label>
                <select id="edit_u_role" name="role" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-sky-500 text-sm font-semibold">
                    <option value="User">Users</option>
                    <option value="NOC">NOC</option>
                    <option value="Warehouse">Staff Gudang</option>
                    <option value="Admin">Administrator</option>
                </select>
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
                <button type="button" onclick="closeModal('modalEditUser')" class="px-4 py-2 rounded-xl text-sm font-medium bg-slate-100 text-slate-600 hover:bg-slate-200">Batal</button>
                <button type="submit" class="px-5 py-2 rounded-xl text-sm font-semibold bg-sky-600 text-white hover:bg-sky-700 shadow-md shadow-sky-600/20">Update Pengguna</button>
            </div>
        </form>
    </div>
</div>

<script>
function editUser(user) {
    document.getElementById('edit_u_id').value = user.id;
    document.getElementById('edit_u_nama').value = user.nama;
    document.getElementById('edit_u_username').value = user.username;
    document.getElementById('edit_u_role').value = user.role;
    openModal('modalEditUser');
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
