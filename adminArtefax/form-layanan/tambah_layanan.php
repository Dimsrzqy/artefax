<?php
session_start();
require_once __DIR__ . "/../../config/koneksi.php";
require_once __DIR__ . "/../../class/paketjasa.php";

$db = new Database();
$conn = $db->getConnection();
$paket = new PaketJasa($conn);

$paket->PaketNama = trim($_POST['PaketNama']);
$paket->PaketKategori = $_POST['PaketKategori'];
$paket->PaketDeskripsi = trim($_POST['PaketDeskripsi']);
$paket->PaketHarga = (int)$_POST['PaketHarga'];
$paket->PaketDurasi = trim($_POST['PaketDurasi']);
$paket->PaketStatus = $_POST['PaketStatus'];

if ($paket->create()) {
    $_SESSION['success_message'] = "Layanan berhasil ditambahkan.";
} else {
    $_SESSION['error_message'] = "Gagal menambahkan layanan.";
}

header("Location: form-layanan.php");
exit; 
?>