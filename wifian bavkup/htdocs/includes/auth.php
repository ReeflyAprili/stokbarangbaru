<?php
// includes/auth.php
// Session Authentication, CSRF & Security Helpers

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

// Helper for XSS protection
function e($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Force user login
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: ../login.php");
        exit();
    }
}

// Check role
function hasRole($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

// Force Admin role
function requireAdmin() {
    requireLogin();
    if (!hasRole('Admin')) {
        setFlash('danger', 'Akses ditolak! Halaman ini hanya untuk Admin.');
        header("Location: dashboard.php");
        exit();
    }
}

// CSRF Token Generation
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// CSRF Field Output
function csrfField() {
    $token = generateCSRFToken();
    return '<input type="hidden" name="csrf_token" value="' . e($token) . '">';
}
// Localized date with weekday
function formatDateWithDay($date) {
    $timestamp = strtotime($date);
    if (!$timestamp) {
        return e($date);
    }
    $days = [
        'Sunday' => 'Minggu',
        'Monday' => 'Senin',
        'Tuesday' => 'Selasa',
        'Wednesday' => 'Rabu',
        'Thursday' => 'Kamis',
        'Friday' => 'Jumat',
        'Saturday' => 'Sabtu',
    ];
    $dayName = date('l', $timestamp);
    return $days[$dayName] . ', ' . date('d/m/Y', $timestamp);
}
// CSRF Token Validation
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token ?? '');
}

// Flash Message Helpers
function setFlash($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type, // success, danger, warning, info
        'message' => $message
    ];
}

function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// Get system settings
function getStoreSettings() {
    static $settings = null;
    if ($settings === null) {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->query("SELECT * FROM settings WHERE id = 1 LIMIT 1");
            $settings = $stmt->fetch();
            if (!$settings) {
                $settings = [
                    'nama_toko' => 'PT Wifian Solution',
                    'logo' => 'logo_wifian.png',
                    'alamat' => 'Jl. Merdeka No. 45, Jakarta',
                    'telepon' => '0812-3456-7890',
                    'email' => 'info@wifiansolution.co.id'
                ];
            }
        } catch (Exception $e) {
            $settings = [
                'nama_toko' => 'PT Wifian Solution',
                'logo' => '',
                'alamat' => '',
                'telepon' => '',
                'email' => ''
            ];
        }
    }
    return $settings;
}
