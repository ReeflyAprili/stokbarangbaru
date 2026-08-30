<?php
// actions/setting_action.php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../pages/pengaturan.php");
    exit();
}

$csrf_token = $_POST['csrf_token'] ?? '';
if (!verifyCSRFToken($csrf_token)) {
    setFlash('danger', 'Sesi invalid (CSRF Token error).');
    header("Location: ../pages/pengaturan.php");
    exit();
}

$act = $_POST['act'] ?? '';
$pdo = getDBConnection();

if ($act === 'update_store') {
    $nama_toko = trim($_POST['nama_toko'] ?? '');
    $alamat = trim($_POST['alamat'] ?? '');
    $telepon = trim($_POST['telepon'] ?? '');
    $email = trim($_POST['email'] ?? '');

    $logoFileName = null;

    // Handle logo file upload
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['logo']['tmp_name'];
        $fileName = $_FILES['logo']['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp'];
        if (in_array($fileExtension, $allowedExts)) {
            $uploadDir = __DIR__ . '/../uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $logoFileName = 'logo_' . time() . '.' . $fileExtension;
            $destPath = $uploadDir . $logoFileName;

            if (!move_uploaded_file($fileTmpPath, $destPath)) {
                $logoFileName = null;
            }
        }
    }

    try {
        if ($logoFileName) {
            $stmt = $pdo->prepare("
                UPDATE settings 
                SET nama_toko = :nama, logo = :logo, alamat = :alamat, telepon = :telp, email = :email 
                WHERE id = 1
            ");
            $stmt->execute([
                ':nama' => $nama_toko,
                ':logo' => $logoFileName,
                ':alamat' => $alamat,
                ':telp' => $telepon,
                ':email' => $email
            ]);
        } else {
            $stmt = $pdo->prepare("
                UPDATE settings 
                SET nama_toko = :nama, alamat = :alamat, telepon = :telp, email = :email 
                WHERE id = 1
            ");
            $stmt->execute([
                ':nama' => $nama_toko,
                ':alamat' => $alamat,
                ':telp' => $telepon,
                ':email' => $email
            ]);
        }
        setFlash('success', 'Informasi toko berhasil diperbarui.');
    } catch (PDOException $e) {
        setFlash('danger', 'Gagal memperbarui informasi toko: ' . $e->getMessage());
    }
} elseif ($act === 'change_password') {
    $current_pass = trim($_POST['current_password'] ?? '');
    $new_pass = trim($_POST['new_password'] ?? '');
    $confirm_pass = trim($_POST['confirm_password'] ?? '');

    if (empty($current_pass) || empty($new_pass) || empty($confirm_pass)) {
        setFlash('danger', 'Seluruh field password wajib diisi.');
        header("Location: ../pages/pengaturan.php");
        exit();
    }

    if ($new_pass !== $confirm_pass) {
        setFlash('danger', 'Konfirmasi password baru tidak cocok.');
        header("Location: ../pages/pengaturan.php");
        exit();
    }

    $userId = $_SESSION['user_id'];
    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $userId]);
    $user = $stmt->fetch();

    if ($user && password_verify($current_pass, $user['password'])) {
        $newHash = password_hash($new_pass, PASSWORD_DEFAULT);
        $stmtUp = $pdo->prepare("UPDATE users SET password = :pass WHERE id = :id");
        $stmtUp->execute([':pass' => $newHash, ':id' => $userId]);
        setFlash('success', 'Password Anda berhasil diubah.');
    } else {
        setFlash('danger', 'Password saat ini salah.');
    }
} elseif ($act === 'backup_db') {
    // Generate MySQL dump script in PHP Native
    $tables = ['settings', 'users', 'categories', 'products', 'stock_in', 'stock_out', 'stock_opname'];
    $return = "-- DATABASE BACKUP INVENTORY PT WIFIAN SOLUTION\n";
    $return .= "-- Generated: " . date('Y-m-d H:i:s') . "\n\n";
    $return .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

    foreach ($tables as $table) {
        $result = $pdo->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
        $return .= "DROP TABLE IF EXISTS `$table`;\n";
        
        $createTable = $pdo->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_NUM);
        $return .= $createTable[1] . ";\n\n";

        foreach ($result as $row) {
            $return .= "INSERT INTO `$table` VALUES(";
            $vals = [];
            foreach ($row as $value) {
                if ($value === null) {
                    $vals[] = "NULL";
                } else {
                    $vals[] = $pdo->quote($value);
                }
            }
            $return .= implode(',', $vals);
            $return .= ");\n";
        }
        $return .= "\n";
    }

    $return .= "SET FOREIGN_KEY_CHECKS=1;\n";

    $fileName = 'backup_inventory_' . date('Ymd_His') . '.sql';
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $fileName . '"');
    echo $return;
    exit();
} elseif ($act === 'restore_db') {
    if (isset($_FILES['sql_file']) && $_FILES['sql_file']['error'] === UPLOAD_ERR_OK) {
        $tmpFile = $_FILES['sql_file']['tmp_name'];
        $sqlContent = file_get_contents($tmpFile);

        try {
            $pdo->exec("SET FOREIGN_KEY_CHECKS=0;");
            $pdo->exec($sqlContent);
            $pdo->exec("SET FOREIGN_KEY_CHECKS=1;");
            setFlash('success', 'Database berhasil di-restore secara penuh.');
        } catch (Exception $e) {
            setFlash('danger', 'Gagal memulihkan database: ' . $e->getMessage());
        }
    } else {
        setFlash('danger', 'Pilih file .sql yang valid.');
    }
}

header("Location: ../pages/pengaturan.php");
exit();
