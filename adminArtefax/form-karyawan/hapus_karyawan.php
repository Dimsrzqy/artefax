<?php
session_start();
require_once __DIR__ . "/../../config/koneksi.php";
require_once __DIR__ . "/../../class/users.php";

$db = new Database();
$conn = $db->getConnection();

if (!$conn) {
    $_SESSION['error_message'] = 'Koneksi database gagal';
    header("Location: form-karyawan.php");
    exit;
}

$user = new User($conn);

$idUser = filter_var(trim($_POST['id'] ?? ''), FILTER_SANITIZE_NUMBER_INT);

if (empty($idUser) || !is_numeric($idUser)) {
    $_SESSION['error_message'] = 'ID karyawan tidak valid';
    header("Location: form-karyawan.php");
    exit;
}

try {
    if ($user->deleteUser($idUser)) {
        $_SESSION['success_message'] = 'Karyawan berhasil dihapus.';
    } else {
        $_SESSION['error_message'] = 'Karyawan tidak ditemukan atau sudah dinonaktifkan sebelumnya.';
    }
} catch (Exception $e) {
    $_SESSION['error_message'] = 'Gagal: ' . $e->getMessage();
}

header("Location: form-karyawan.php");
exit;