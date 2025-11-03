<?php
session_start();
require_once __DIR__ . "/../../config/koneksi.php";
require_once __DIR__ . "/../../class/paketjasa.php";

$db = new Database();
$conn = $db->getConnection();
$paket = new PaketJasa($conn);

// Ambil ID dari POST
$paket->IDPaket = (int)$_POST['id'];

// Validasi ID
if ($paket->IDPaket <= 0) {
    $_SESSION['error_message'] = "ID tidak valid.";
    header("Location: form-layanan.php");
    exit;
}

if ($paket->delete()) {
    $_SESSION['success_message'] = "Layanan berhasil dihapus!";
} else {
    $_SESSION['error_message'] = "Gagal menghapus layanan.";
}

header("Location: form-layanan.php");
exit;
?>