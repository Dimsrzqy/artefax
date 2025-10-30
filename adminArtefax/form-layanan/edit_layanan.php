<?php
session_start();
require_once __DIR__ . "/../../config/koneksi.php";
require_once __DIR__ . "/../../class/paketjasa.php";

$db = new Database();
$conn = $db->getConnection();
$paket = new PaketJasa($conn);

$paket->IDPaket = (int)$_POST['IDPaket'];

if ($paket->IDPaket <= 0) {
    $_SESSION['error_message'] = "ID tidak valid.";
    header("Location: daftarlayanan.php");
    exit;
}

$paket->PaketNama = trim($_POST['PaketNama'] ?? '');
$paket->PaketKategori = $_POST['PaketKategori'] ?? '';
$paket->PaketDeskripsi = trim($_POST['PaketDeskripsi'] ?? '');
$paket->PaketHarga = (int)$_POST['PaketHarga'];
$paket->PaketDurasi = trim($_POST['PaketDurasi'] ?? '');
$paket->PaketStatus = $_POST['PaketStatus'] ?? '';

if (empty($paket->PaketNama) || empty($paket->PaketKategori) || $paket->PaketHarga < 0) {
    $_SESSION['error_message'] = "Harap isi semua field wajib.";
} elseif ($paket->update()) {
    $_SESSION['success_message'] = "Layanan berhasil diperbarui!";
} else {
    $_SESSION['error_message'] = "Gagal memperbarui layanan.";
}

header("Location: form-layanan.php");
exit;
?>