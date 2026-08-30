<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    header("Location: pages/dashboard.php");
    exit();
}

// BUAT TOKEN CSRF JIKA BELUM ADA DI SESSION
if (function_exists('generateCSRFToken')) {
    $csrf_token = generateCSRFToken();
} else {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    $csrf_token = $_SESSION['csrf_token'];
}

$settings = getStoreSettings();
$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?= e($settings['nama_toko']) ?></title>
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        wifian: {
                            blue: '#1e3a8a',
                            darknavy: '#121212',
                            lightblue: '#38bdf8',
                            sky: '#0284c7',
                            red: '#dc2626',
                            brightred: '#ef4444',
                        }
                    }
                }
            }
        }
    </script>
    
    <!-- FontAwesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="assets/css/custom.css">
</head>
<body class="relative overflow-hidden bg-gradient-to-br from-slate-50 via-white to-blue-50 min-h-screen flex flex-col items-center justify-center p-4">
    <div class="absolute inset-0 overflow-hidden pointer-events-none">
        <div class="absolute -top-16 -left-16 w-48 h-48 rounded-full bg-blue-100/70 blur-3xl"></div>
        <div class="absolute bottom-0 right-0 w-56 h-56 rounded-full bg-sky-100/70 blur-3xl"></div>
        <div class="absolute top-1/3 right-1/4 w-24 h-24 rounded-full border border-blue-200/60"></div>
    </div>

    <!-- CARD UTAMA LOGIN -->
    <div class="w-full max-w-md bg-white/95 backdrop-blur-xl rounded-3xl shadow-2xl overflow-hidden border border-white/10 my-auto transition-all duration-500 ease-out hover:-translate-y-1 hover:shadow-3xl">
        
        <!-- Header Section -->
        <div class="bg-slate-50 p-8 text-center text-slate-800 relative border-b border-slate-200 flex flex-col items-center">
            
            <!-- PERBAIKAN LOGO: Kotak dibuat lebih lebar (w-auto / max-w-full) dengan padding lega (px-8) & gambar dikunci tingginya (h-12) -->
            <div class="bg-white px-8 py-3 rounded-2xl shadow-xl inline-flex items-center justify-center mb-4 border border-slate-200">
                <img src="assets/images/logo.svg" alt="Logo PT Wifian Solution" class="h-12 w-auto max-w-full object-contain block mx-auto">
            </div>
            
            <h2 class="text-2xl font-black tracking-tight text-slate-800 mb-2">INVENTORY SYSTEM</h2>
            
            <!-- Teks Stock Barang di Atas, PT Wifian Solution di Bawah -->
            <p class="text-slate-500 text-xs font-semibold uppercase tracking-wider mb-1">STOCK BARANG PT Wifian Solution</p>
            <p class="text-slate-700 text-sm font-bold"><?= e($settings['nama_toko']) ?></p>
        </div>

        <!-- Form Body -->
        <div class="p-8">
            
            <?php if ($flash): ?>
                <div class="mb-6 p-4 rounded-xl text-sm flex items-center gap-3 <?= $flash['type'] === 'success' ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-red-50 text-red-800 border border-red-200' ?>">
                    <i class="fa-solid <?= $flash['type'] === 'success' ? 'fa-circle-check text-emerald-600' : 'fa-circle-exclamation text-red-600' ?> text-lg"></i>
                    <span class="font-semibold"><?= e($flash['message']) ?></span>
                </div>
            <?php endif; ?>

            <!-- Form Login -->
            <form action="actions/login_action.php" method="POST" class="space-y-4">
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-2">Username</label>
                    <div class="relative">
                        <i class="fa-solid fa-user absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input type="text" name="username" placeholder="Masukkan username" required
                            class="w-full pl-11 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-sky-500 text-slate-800 text-sm font-medium transition-all duration-300 ease-out hover:border-sky-300 focus:border-sky-500">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-2">Password</label>
                    <div class="relative">
                        <i class="fa-solid fa-lock absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input type="password" name="password" placeholder="••••••••" required
                            class="w-full pl-11 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-sky-500 text-slate-800 text-sm font-medium transition-all duration-300 ease-out hover:border-sky-300 focus:border-sky-500">
                    </div>
                </div>

                <button type="submit" 
                    class="w-full py-3.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl shadow-md transition-all duration-300 ease-out active:scale-[0.98] flex items-center justify-center gap-2 mt-6 hover:shadow-lg hover:-translate-y-0.5">
                    LOGIN KE SISTEM <i class="fa-solid fa-arrow-right"></i>
                </button>
            </form>
        </div>
    </div>

    <!-- FOOTER -->
    <footer class="mt-6 text-center text-xs text-slate-500 py-4">
        &copy; <?= date('Y') ?> PT Wifian Solution. All Rights Reserved.
    </footer>

</body>
</html>