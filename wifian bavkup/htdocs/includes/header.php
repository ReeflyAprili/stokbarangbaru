<?php
// includes/header.php
require_once __DIR__ . '/auth.php';
requireLogin();

$currentUser = $_SESSION['nama'] ?? 'User';
$currentRole = $_SESSION['role'] ?? 'Staff Gudang';
$settings = getStoreSettings();
$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? 'Dashboard') ?> - <?= e($settings['nama_toko']) ?></title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        wifian: {
                            blue: '#1e3a8a',
                            darkblue: '#0f172a',
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
    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/custom.css">
</head>
<body class="bg-slate-100 text-slate-800 antialiased font-sans min-h-screen flex flex-col">

<!-- Mobile Sidebar Overlay -->
<div id="sidebar-overlay" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-40 hidden lg:hidden"></div>

<div class="flex flex-1 min-h-screen overflow-hidden">
    <!-- Sidebar Include -->
    <?php include __DIR__ . '/sidebar.php'; ?>

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col min-w-0 overflow-y-auto">
        
        <!-- Topbar Header -->
        <header id="topbar" class="bg-white border-b border-slate-200 sticky top-0 z-30 px-4 py-3 sm:px-6 flex items-center justify-between shadow-sm">
            <div class="flex items-center gap-3">
                <button id="sidebar-toggle" class="lg:hidden p-2 rounded-lg text-slate-600 hover:bg-slate-100 focus:outline-none">
                    <i class="fa-solid fa-bars text-xl"></i>
                </button>
                <div class="flex items-center gap-3">
                    <h1 class="text-xl font-black tracking-tight text-slate-800 hidden sm:block"><?= e($pageTitle ?? 'Dashboard') ?></h1>
                </div>
            </div>

            <div class="flex items-center gap-4">
                <div class="flex items-center gap-3 border-l border-slate-200 pl-4">
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-wifian-blue to-blue-600 text-white flex items-center justify-center font-black text-sm shadow-md border border-white/20">
                        <?= strtoupper(substr($currentUser, 0, 1)) ?>
                    </div>
                    <div class="hidden md:block text-left">
                        <div class="text-sm font-extrabold text-slate-800 leading-tight"><?= e($currentUser) ?></div>
                        <span class="inline-block text-[10px] uppercase tracking-wider px-2 py-0.5 rounded-md font-extrabold <?= $currentRole === 'Admin' ? 'bg-red-50 text-wifian-red border border-red-200' : 'bg-blue-50 text-wifian-blue border border-blue-200' ?>">
                            <?= e($currentRole) ?>
                        </span>
                    </div>
                </div>

                <a href="../logout.php" onclick="return confirm('Apakah Anda yakin ingin keluar?');" class="p-2 text-slate-400 hover:text-wifian-red hover:bg-red-50 rounded-xl transition-colors" title="Logout">
                    <i class="fa-solid fa-right-from-bracket text-lg"></i>
                </a>
            </div>
        </header>

        <!-- Main Body Wrapper -->
        <main class="p-4 sm:p-6 lg:p-8 flex-1">
            <?php if ($flash): ?>
                <div id="flash-alert" class="mb-6 p-4 rounded-xl shadow-sm border flex items-center justify-between transition-all duration-300 <?= 
                    $flash['type'] === 'success' ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : 
                    ($flash['type'] === 'danger' ? 'bg-red-50 border-red-200 text-wifian-red' : 
                    ($flash['type'] === 'warning' ? 'bg-amber-50 border-amber-200 text-amber-800' : 'bg-blue-50 border-blue-200 text-blue-800')) ?>">
                    <div class="flex items-center gap-3">
                        <i class="fa-solid <?= 
                            $flash['type'] === 'success' ? 'fa-circle-check text-emerald-600' : 
                            ($flash['type'] === 'danger' ? 'fa-circle-exclamation text-wifian-red' : 
                            ($flash['type'] === 'warning' ? 'fa-triangle-exclamation text-amber-600' : 'fa-circle-info text-blue-600')) ?> text-xl"></i>
                        <span class="font-semibold text-sm"><?= e($flash['message']) ?></span>
                    </div>
                    <button onclick="this.parentElement.remove();" class="text-slate-400 hover:text-slate-600">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
            <?php endif; ?>
