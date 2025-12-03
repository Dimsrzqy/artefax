<?php
session_start();
require_once __DIR__ . "/../../config/koneksi.php";
require_once __DIR__ . "/../../class/pembayaran.php";

if (!isset($_GET['id']) || !isset($_GET['aksi']) || $_GET['aksi'] !== 'setuju') {
    $_SESSION['error'] = "Aksi tidak valid.";
    header("Location: pengajuan_refund.php");
    exit;
}

$idRefund = (int)$_GET['id'];
if ($idRefund <= 0) {
    $_SESSION['error'] = "ID refund tidak valid.";
    header("Location: pengajuan_refund.php");
    exit;
}

$db = new Database();
$pembayaran = new Pembayaran($db->getConnection());

if ($pembayaran->terimaRefund($idRefund)) {
    $_SESSION['success'] = "Refund berhasil disetujui! Booking dibatalkan & pembayaran ditandai batal.";
} else {
    $pesan = $_SESSION['debug_error'] ?? "Tidak diketahui penyebabnya.";
    unset($_SESSION['debug_error']);
    $_SESSION['error'] = "Gagal memproses refund: " . $pesan;
}

header("Location: pengajuan_refund.php");
exit;
?>