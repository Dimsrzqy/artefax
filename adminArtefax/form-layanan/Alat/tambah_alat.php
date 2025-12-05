<?php
session_start();
require_once __DIR__ . "/../../../config/koneksi.php";
require_once __DIR__ . "/../../../class/alat.php";

$db = new Database();
$conn = $db->getConnection();
$alat = new Alat($conn);

$uploadDir = $_SERVER['DOCUMENT_ROOT'] . "/artefax/Paket/img/produk/alat/";
$webPath = "alat/";

$gambarPath = null;

if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['gambar'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg'];

    if (!in_array($ext, $allowed)) {
        $_SESSION['error_message'] = "Untuk format gambar yang diupload harus jpg!";
        header("Location: form-alat.php"); exit;
    }

    if ($file['size'] > 5 * 1024 * 1024) {
        $_SESSION['error_message'] = "Ukuran gambar maksimal 5MB!";
        header("Location: form-alat.php"); exit;
    }

    // Cari nomor urut terakhir
    $stmt = $conn->query("SELECT AlatDirGbr FROM alat WHERE AlatDirGbr LIKE 'alat/alat%' ORDER BY IDAlat DESC LIMIT 1");
    $last = $stmt->fetch_assoc();
    $nextNum = 1;

    if ($last) {
        preg_match('/alat(\d+)/', $last['AlatDirGbr'], $m);
        $nextNum = isset($m[1]) ? (intval($m[1]) + 1) : 1;
    }

    $newFilename = "alat" . $nextNum . "." . $ext;
    $destination = $uploadDir . $newFilename;

    if (move_uploaded_file($file['tmp_name'], $destination)) {
        $gambarPath = $webPath . $newFilename;
    } else {
        $_SESSION['error_message'] = "Gagal upload gambar.";
        header("Location: form-alat.php"); exit;
    }
}

$alat->AlatNama = trim($_POST['AlatNama']);
$alat->AlatDirGbr = $gambarPath;
$alat->AlatKategori = $_POST['AlatKategori'];
$alat->AlatDeskripsi = trim($_POST['AlatDeskripsi']);
$alat->AlatHarga = (int)$_POST['AlatHarga'];
$alat->AlatStok = (int)$_POST['AlatStok'];
$alat->AlatStatus = $_POST['AlatStatus'];


if ($alat->create()) {
    $_SESSION['success_message'] = "Layanan berhasil ditambahkan.";
} else {
    $_SESSION['error_message'] = "Gagal menambahkan layanan.";
}

header("Location: form-alat.php");
exit; 
?>