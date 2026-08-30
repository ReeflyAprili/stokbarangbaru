<?php
// pages/pengaturan.php
$pageTitle = 'Pengaturan Sistem';
require_once __DIR__ . '/../includes/header.php';
requireAdmin();

$settings = getStoreSettings();
?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
    
    <!-- Left Column: Store Information (2 cols) -->
    <div class="lg:col-span-2 space-y-6">
        
        <!-- Store Settings Form -->
        <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-subtle">
            <div class="flex items-center justify-between pb-4 mb-4 border-b border-slate-100">
                <div>
                    <h3 class="text-base font-bold text-slate-800">Profil & Informasi Perusahaan</h3>
                    <p class="text-xs text-slate-500">Kustomisasi nama toko, logo, dan kontak untuk laporan</p>
                </div>
                <i class="fa-solid fa-store text-sky-600 text-xl"></i>
            </div>

            <form action="../actions/setting_action.php" method="POST" enctype="multipart/form-data" class="space-y-4">
                <?= csrfField() ?>
                <input type="hidden" name="act" value="update_store">

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Nama Perusahaan / Toko</label>
                        <input type="text" name="nama_toko" value="<?= e($settings['nama_toko']) ?>" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-sky-500 text-sm font-semibold">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Nomor Telepon / WhatsApp</label>
                        <input type="text" name="telepon" value="<?= e($settings['telepon']) ?>" placeholder="0812-xxxx-xxxx" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-sky-500 text-sm">
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Email Perusahaan</label>
                        <input type="email" name="email" value="<?= e($settings['email']) ?>" placeholder="info@wifiansolution.co.id" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-sky-500 text-sm">
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Alamat Lengkap Toko / Gudang</label>
                        <textarea name="alamat" rows="2" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-sky-500 text-sm"><?= e($settings['alamat']) ?></textarea>
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Logo Toko (Optional Upload)</label>
                        <div class="flex items-center gap-4">
                            <?php if (!empty($settings['logo']) && file_exists(__DIR__ . '/../uploads/' . $settings['logo'])): ?>
                                <img src="../uploads/<?= e($settings['logo']) ?>" alt="Logo" class="w-12 h-12 rounded-xl object-cover border">
                            <?php else: ?>
                                <div class="w-12 h-12 rounded-xl bg-slate-100 flex items-center justify-center text-slate-400 font-bold text-lg">
                                    <i class="fa-solid fa-image"></i>
                                </div>
                            <?php endif; ?>
                            <input type="file" name="logo" accept="image/*" class="text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-sky-50 file:text-sky-700 hover:file:bg-sky-100">
                        </div>
                    </div>
                </div>

                <div class="pt-3 border-t border-slate-100 flex justify-end">
                    <button type="submit" class="px-5 py-2.5 rounded-xl text-sm font-semibold bg-sky-600 hover:bg-sky-700 text-white shadow-md shadow-sky-600/20 transition-all">
                        <i class="fa-solid fa-floppy-disk mr-1.5"></i> Simpan Informasi Toko
                    </button>
                </div>
            </form>
        </div>

        <!-- Database Backup & Restore Card -->
        <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-subtle">
            <div class="flex items-center justify-between pb-4 mb-4 border-b border-slate-100">
                <div>
                    <h3 class="text-base font-bold text-slate-800">Database Backup & Restore</h3>
                    <p class="text-xs text-slate-500">Amankan data inventaris dan pulihkan cadangan skema SQL</p>
                </div>
                <i class="fa-solid fa-database text-purple-600 text-xl"></i>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Backup Box -->
                <div class="p-4 rounded-xl bg-purple-50/50 border border-purple-100 flex flex-col justify-between">
                    <div>
                        <div class="font-bold text-sm text-purple-900 mb-1 flex items-center gap-2">
                            <i class="fa-solid fa-download text-purple-600"></i> Backup Database SQL
                        </div>
                        <p class="text-xs text-slate-600 mb-4">Unduh seluruh tabel dan data sistem ke dalam file file format `.sql` secara utuh.</p>
                    </div>
                    <form action="../actions/setting_action.php" method="POST">
                        <?= csrfField() ?>
                        <input type="hidden" name="act" value="backup_db">
                        <button type="submit" class="w-full py-2.5 px-4 rounded-xl text-xs font-bold bg-purple-600 hover:bg-purple-700 text-white shadow-md shadow-purple-600/20 transition-all flex items-center justify-center gap-2">
                            <i class="fa-solid fa-file-export"></i>
                            <span>Unduh Backup SQL (.sql)</span>
                        </button>
                    </form>
                </div>

                <!-- Restore Box -->
                <div class="p-4 rounded-xl bg-amber-50/50 border border-amber-100 flex flex-col justify-between">
                    <div>
                        <div class="font-bold text-sm text-amber-900 mb-1 flex items-center gap-2">
                            <i class="fa-solid fa-upload text-amber-600"></i> Restore Database SQL
                        </div>
                        <p class="text-xs text-slate-600 mb-3">Upload file `.sql` cadangan untuk memulihkan seluruh struktur dan data database.</p>
                    </div>
                    <form action="../actions/setting_action.php" method="POST" enctype="multipart/form-data" onsubmit="return confirm('PERINGATAN: Restore akan menimpa data yang ada saat ini. Lanjutkan?');">
                        <?= csrfField() ?>
                        <input type="hidden" name="act" value="restore_db">
                        <input type="file" name="sql_file" accept=".sql" required class="text-xs text-slate-500 mb-3 w-full file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-amber-100 file:text-amber-800">
                        <button type="submit" class="w-full py-2.5 px-4 rounded-xl text-xs font-bold bg-amber-600 hover:bg-amber-700 text-white shadow-md shadow-amber-600/20 transition-all flex items-center justify-center gap-2">
                            <i class="fa-solid fa-rotate-left"></i>
                            <span>Jalankan Restore Database</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>

    </div>

    <!-- Right Column: Change Password (1 col) -->
    <div class="space-y-6">
        
        <!-- Change Password Form -->
        <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-subtle">
            <div class="flex items-center justify-between pb-4 mb-4 border-b border-slate-100">
                <div>
                    <h3 class="text-base font-bold text-slate-800">Ganti Password Saya</h3>
                    <p class="text-xs text-slate-500">Perbarui kata sandi akun yang sedang aktif</p>
                </div>
                <i class="fa-solid fa-key text-amber-500 text-xl"></i>
            </div>

            <form action="../actions/setting_action.php" method="POST" class="space-y-4">
                <?= csrfField() ?>
                <input type="hidden" name="act" value="change_password">

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Password Saat Ini</label>
                    <input type="password" name="current_password" required placeholder="••••••••" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-sky-500 text-sm">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Password Baru</label>
                    <input type="password" name="new_password" required placeholder="••••••••" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-sky-500 text-sm">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Konfirmasi Password Baru</label>
                    <input type="password" name="confirm_password" required placeholder="••••••••" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-sky-500 text-sm">
                </div>

                <div class="pt-3 border-t border-slate-100">
                    <button type="submit" class="w-full py-2.5 px-4 rounded-xl text-sm font-bold bg-slate-800 hover:bg-slate-900 text-white shadow-md transition-all flex items-center justify-center gap-2">
                        <i class="fa-solid fa-lock"></i>
                        <span>Update Password</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- Security Info Card -->
        <div class="bg-gradient-to-br from-slate-900 to-slate-950 text-white p-6 rounded-2xl shadow-xl space-y-3">
            <div class="font-bold text-sky-400 text-sm flex items-center gap-2">
                <i class="fa-solid fa-shield-halved"></i> Keamanan Enkripsi
            </div>
            <p class="text-xs text-slate-300 leading-relaxed">
                Seluruh password disimpan menggunakan algoritma standar industri <code>password_hash()</code> BCRYPT yang aman dari serangan rainbow table.
            </p>
        </div>

    </div>

</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
