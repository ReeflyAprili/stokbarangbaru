<?php
// actions/login_action.php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../login.php");
    exit();
}

$csrf_token = $_POST['csrf_token'] ?? '';
if (!verifyCSRFToken($csrf_token)) {
    setFlash('danger', 'Sesi invalid (CSRF Error). Silakan coba lagi.');
    header("Location: ../login.php");
    exit();
}

$username = trim($_POST['username'] ?? '');
$password = trim($_POST['password'] ?? '');

if (empty($username) || empty($password)) {
    setFlash('danger', 'Username dan password wajib diisi.');
    header("Location: ../login.php");
    exit();
}

try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username LIMIT 1");
    $stmt->execute([':username' => $username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        // Successful login
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['nama'] = $user['nama'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];

        setFlash('success', 'Selamat datang kembali, ' . $user['nama'] . '!');
        header("Location: ../pages/dashboard.php");
        exit();
    } else {
        setFlash('danger', 'Username atau password salah.');
        header("Location: ../login.php");
        exit();
    }
} catch (Exception $e) {
    setFlash('danger', 'Terjadi kesalahan sistem: ' . $e->getMessage());
    header("Location: ../login.php");
    exit();
}
