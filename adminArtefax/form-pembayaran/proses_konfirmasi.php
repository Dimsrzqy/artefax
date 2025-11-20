<?php
session_start();
require_once __DIR__ . "/../../config/koneksi.php";
require_once __DIR__ . "/../../class/pembayaran.php";

if (!isset($_GET['id']) || !isset($_GET['status'])) {
    $_SESSION['error'] = "Aksi tidak valid.";
    header("Location: konfirmasi_pembayaran.php");
    exit;
}

$id = (int)$_GET['id'];
$status = $_GET['status'] === 'Sukses' ? 'Sukses' : 'Gagal';

$db = new Database();
$pembayaran = new Pembayaran($db->getConnection());

if ($pembayaran->updateStatus($id, $status, $status === 'Sukses' ? 1 : 0)) {
    $_SESSION['success'] = "Pembayaran telah di" . ($status === 'Sukses' ? "disetujui" : "ditolak") . ".";
} else {
    $_SESSION['error'] = "Gagal memperbarui status.";
}

header("Location: konfirmasi_pembayaran.php");
exit;
?>