<?php
session_start();
ob_start();

require_once __DIR__ . "/../../config/koneksi.php";
require_once __DIR__ . "/../../class/Users.php";

$db = new Database();
$conn = $db->getConnection();

if (!$conn) {
    $_SESSION['error_message'] = 'Koneksi database gagal';
    header("Location: form-karyawan.php");
    ob_end_clean();
    exit;
}

$user = new User($conn);

try {
    // Ambil input
    $user->UserNama     = trim($_POST['NamaUser'] ?? '');
    $user->UserEmail    = trim($_POST['Email'] ?? '');
    $user->UserPassword = $_POST['Password'] ?? '';
    $user->UserNoHP     = trim($_POST['NoHP'] ?? '');
    $user->UserAlamat   = trim($_POST['Alamat'] ?? '');

    // Validasi
    if (
        empty($user->UserNama) || empty($user->UserEmail) || empty($user->UserPassword) ||
        empty($user->UserNoHP) || empty($user->UserAlamat)
    ) {
        $_SESSION['error_message'] = 'Semua kolom wajib diisi.';
        header("Location: form-karyawan.php");
        ob_end_clean();
        exit;
    }

    if (!filter_var($user->UserEmail, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error_message'] = 'Format email tidak valid.';
        header("Location: form-karyawan.php");
        ob_end_clean();
        exit;
    }

    if (!preg_match('/^[0-9]{10,15}$/', $user->UserNoHP)) {
        $_SESSION['error_message'] = 'Nomor HP harus 10–15 digit angka.';
        header("Location: form-karyawan.php");
        ob_end_clean();
        exit;
    }

    if (strlen($user->UserPassword) < 6) {
        $_SESSION['error_message'] = 'Password minimal 6 karakter.';
        header("Location: form-karyawan.php");
        ob_end_clean();
        exit;
    }

    // OTOMATIS JADI KARYAWAN
    if ($user->register('Karyawan')) {
        $_SESSION['success_message'] = 'Karyawan berhasil ditambahkan.';
    } else {
        $_SESSION['error_message'] = 'Email sudah terdaftar atau gagal menyimpan.';
    }
} catch (Exception $e) {
    error_log("Error tambah karyawan: " . $e->getMessage());
    $_SESSION['error_message'] = 'Terjadi kesalahan sistem.';
} finally {
    header("Location: form-karyawan.php");
    ob_end_clean();
    exit;
}
