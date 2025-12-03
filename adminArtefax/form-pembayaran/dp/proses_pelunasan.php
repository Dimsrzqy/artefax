<?php
session_start();
require_once __DIR__ . "/../../../config/koneksi.php";
require_once __DIR__ . "/../../../class/pembayaran.php";

if (!isset($_GET['id']) || !isset($_GET['aksi'])) {
    $_SESSION['error'] = "Aksi tidak valid.";
    header("Location: pelunasan_pembayaran.php");
    exit;
}

$id = (int)$_GET['id'];
$aksi = $_GET['aksi'];

if (!in_array($aksi, ['setuju', 'tolak'])) {
    $_SESSION['error'] = "Aksi tidak dikenali.";
    header("Location: pelunasan_pembayaran.php");
    exit;
}

$db = new Database();
$pembayaran = new Pembayaran($db->getConnection());

if ($pembayaran->updatePelunasan($id, $aksi)) {
    $_SESSION['success'] = $aksi === 'setuju' 
        ? "Pembayaran telah disetujui dan booking diterima." 
        : "Pembayaran ditolak dan booking dibatalkan.";
} else {
    $_SESSION['error'] = "Gagal memperbarui status.";
}

header("Location: pelunasan_pembayaran.php");
exit;
?>