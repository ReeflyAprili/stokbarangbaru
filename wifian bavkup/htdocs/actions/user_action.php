<?php
// actions/user_action.php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../pages/pengguna.php");
    exit();
}

$csrf_token = $_POST['csrf_token'] ?? '';
if (!verifyCSRFToken($csrf_token)) {
    setFlash('danger', 'Sesi invalid (CSRF Token error).');
    header("Location: ../pages/pengguna.php");
    exit();
}

$act = $_POST['act'] ?? '';
$pdo = getDBConnection();

if ($act === 'add') {
    $nama = trim($_POST['nama'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $role = trim($_POST['role'] ?? 'Staff Gudang');

    if (empty($nama) || empty($username) || empty($password)) {
        setFlash('danger', 'Nama, username, dan password wajib diisi.');
        header("Location: ../pages/pengguna.php");
        exit();
    }

    $passHash = password_hash($password, PASSWORD_DEFAULT);

    try {
        $stmt = $pdo->prepare("INSERT INTO users (nama, username, password, role) VALUES (:nama, :uname, :pass, :role)");
        $stmt->execute([
            ':nama' => $nama,
            ':uname' => $username,
            ':pass' => $passHash,
            ':role' => $role
        ]);
        setFlash('success', "Pengguna '$username' ($role) berhasil dibuat.");
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            setFlash('danger', "Username '$username' sudah terdaftar.");
        } else {
            setFlash('danger', 'Gagal membuat pengguna: ' . $e->getMessage());
        }
    }
} elseif ($act === 'edit') {
    $id = (int)($_POST['id'] ?? 0);
    $nama = trim($_POST['nama'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $role = trim($_POST['role'] ?? 'Staff Gudang');

    if ($id <= 0 || empty($nama) || empty($username)) {
        setFlash('danger', 'Data pengguna tidak valid.');
        header("Location: ../pages/pengguna.php");
        exit();
    }

    try {
        if (!empty($password)) {
            $passHash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET nama = :nama, username = :uname, password = :pass, role = :role WHERE id = :id");
            $stmt->execute([
                ':nama' => $nama,
                ':uname' => $username,
                ':pass' => $passHash,
                ':role' => $role,
                ':id' => $id
            ]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET nama = :nama, username = :uname, role = :role WHERE id = :id");
            $stmt->execute([
                ':nama' => $nama,
                ':uname' => $username,
                ':role' => $role,
                ':id' => $id
            ]);
        }
        setFlash('success', "Data pengguna '$username' berhasil diperbarui.");
    } catch (PDOException $e) {
        setFlash('danger', 'Gagal memperbarui pengguna: ' . $e->getMessage());
    }
} elseif ($act === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id === (int)$_SESSION['user_id']) {
        setFlash('danger', 'Anda tidak dapat menghapus akun Anda sendiri yang sedang aktif.');
        header("Location: ../pages/pengguna.php");
        exit();
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id");
        $stmt->execute([':id' => $id]);
        setFlash('success', 'Pengguna berhasil dihapus.');
    } catch (PDOException $e) {
        setFlash('danger', 'Gagal menghapus pengguna: ' . $e->getMessage());
    }
}

header("Location: ../pages/pengguna.php");
exit();
