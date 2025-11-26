<?php
session_start();
require_once __DIR__ . "/../../config/koneksi.php";
require_once __DIR__ . "/../../class/paketjasa.php";

$db = new Database();
$conn = $db->getConnection();
$paket = new PaketJasa($conn);

$uploadDir = $_SERVER['DOCUMENT_ROOT'] . "/artefax/Paket/img/produk/paketjasa/";
$webPath = "paketjasa/";

$gambarPath = null;

if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['gambar'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif'];

    if (!in_array($ext, $allowed)) {
        $_SESSION['error_message'] = "Format gambar tidak didukung!";
        header("Location: form-layanan.php"); exit;
    }

    if ($file['size'] > 5 * 1024 * 1024) {
        $_SESSION['error_message'] = "Ukuran gambar maksimal 5MB!";
        header("Location: form-layanan.php"); exit;
    }

    // Cari nomor urut terakhir
    $stmt = $conn->query("SELECT PaketDirGbr FROM paketjasa WHERE PaketDirGbr LIKE 'paketjasa/jasa%' ORDER BY IDPaket DESC LIMIT 1");
    $last = $stmt->fetch_assoc();
    $nextNum = 1;

    if ($last) {
        preg_match('/jasa(\d+)/', $last['PaketDirGbr'], $m);
        $nextNum = isset($m[1]) ? (intval($m[1]) + 1) : 1;
    }

    $newFilename = "jasa" . $nextNum . "." . $ext;
    $destination = $uploadDir . $newFilename;

    if (move_uploaded_file($file['tmp_name'], $destination)) {
        $gambarPath = $webPath . $newFilename;
    } else {
        $_SESSION['error_message'] = "Gagal upload gambar.";
        header("Location: form-layanan.php"); exit;
    }
}

$paket->PaketNama = trim($_POST['PaketNama']);
$paket->PaketDirGbr = $gambarPath;
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