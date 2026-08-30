<?php
// actions/kategori_action.php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../pages/kategori.php");
    exit();
}

$csrf_token = $_POST['csrf_token'] ?? '';
if (!verifyCSRFToken($csrf_token)) {
    setFlash('danger', 'Sesi invalid (CSRF Token error).');
    header("Location: ../pages/kategori.php");
    exit();
}

$act = $_POST['act'] ?? '';
$pdo = getDBConnection();

if ($act === 'add') {
    $nama_kategori = trim($_POST['nama_kategori'] ?? '');
    if (empty($nama_kategori)) {
        setFlash('danger', 'Nama kategori tidak boleh kosong.');
        header("Location: ../pages/kategori.php");
        exit();
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO categories (nama_kategori) VALUES (:nama)");
        $stmt->execute([':nama' => $nama_kategori]);
        setFlash('success', 'Kategori baru berhasil ditambahkan.');
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            setFlash('danger', 'Nama kategori sudah ada.');
        } else {
            setFlash('danger', 'Gagal menambah kategori: ' . $e->getMessage());
        }
    }
} elseif ($act === 'edit') {
    $id = (int)($_POST['id'] ?? 0);
    $nama_kategori = trim($_POST['nama_kategori'] ?? '');

    if ($id <= 0 || empty($nama_kategori)) {
        setFlash('danger', 'Data kategori tidak valid.');
        header("Location: ../pages/kategori.php");
        exit();
    }

    try {
        $stmt = $pdo->prepare("UPDATE categories SET nama_kategori = :nama WHERE id = :id");
        $stmt->execute([':nama' => $nama_kategori, ':id' => $id]);
        setFlash('success', 'Kategori berhasil diperbarui.');
    } catch (PDOException $e) {
        setFlash('danger', 'Gagal memperbarui kategori: ' . $e->getMessage());
    }
} elseif ($act === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        setFlash('danger', 'ID kategori invalid.');
        header("Location: ../pages/kategori.php");
        exit();
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM categories WHERE id = :id");
        $stmt->execute([':id' => $id]);
        setFlash('success', 'Kategori berhasil dihapus.');
    } catch (PDOException $e) {
        setFlash('danger', 'Gagal menghapus kategori: ' . $e->getMessage());
    }
}

header("Location: ../pages/kategori.php");
exit();
