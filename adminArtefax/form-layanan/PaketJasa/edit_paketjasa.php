<?php
session_start();
require_once __DIR__ . "/../../../config/koneksi.php";
require_once __DIR__ . "/../../../class/paketjasa.php";

$db = new Database();
$conn = $db->getConnection();
$paket = new PaketJasa($conn);

$paket->IDPaket = (int)$_POST['IDPaket'];

if ($paket->IDPaket <= 0) {
    $_SESSION['error_message'] = "ID tidak valid.";
    header("Location: form-paketjasa.php");
    exit;
}

$stmt = $conn->prepare("SELECT PaketDirGbr FROM paketjasa WHERE IDPaket = ?");
$stmt->bind_param("i", $paket->IDPaket);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$gambarLamaDb = $row ? trim($row['PaketDirGbr'] ?? '') : '';


$uploadDir = $_SERVER['DOCUMENT_ROOT'] . "/artefax/Paket/img/produk/paketjasa/";
$webPath = "paketjasa/";
$gambarBaruPath = $gambarLamaDb; 

$gambarLamaPost = trim($_POST['gambarLama'] ?? '');

 
if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['gambar'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif'];

    if (!in_array($ext, $allowed)) {
        $_SESSION['error_message'] = "Format gambar tidak didukung!";
        header("Location: form-paketjasa.php"); 
        exit;
    }

    if ($file['size'] > 5 * 1024 * 1024) {
        $_SESSION['error_message'] = "Ukuran gambar maksimal 5MB!";
        header("Location: form-paketjasa.php"); 
        exit;
    }

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
        $gambarBaruPath = $webPath . $newFilename;
        if ($gambarLamaDb !== '') {
            $fileLamaPath = $_SERVER['DOCUMENT_ROOT'] . "/artefax/Paket/img/produk/" . $gambarLamaDb;
            if (file_exists($fileLamaPath)) {
                unlink($fileLamaPath);
            }
        }
    } else {
        $_SESSION['error_message'] = "Gagal upload gambar baru.";
        header("Location: form-paketjasa.php"); 
        exit;
    }
}
elseif ($gambarLamaPost === '' && empty($_FILES['gambar']['name'])) {
    $gambarBaruPath = null;
    if ($gambarLamaDb !== '') {
        $fileLamaPath = $_SERVER['DOCUMENT_ROOT'] . "/artefax/Paket/img/produk/" . $gambarLamaDb;
        if (file_exists($fileLamaPath)) {
            unlink($fileLamaPath);
        }
    }
}

if ($gambarLamaPost !== '' && $gambarLamaPost !== $gambarLamaDb) {
}
 
$paket->PaketNama       = trim($_POST['PaketNama'] ?? '');
$paket->PaketKategori   = $_POST['PaketKategori'] ?? '';
$paket->PaketDeskripsi  = trim($_POST['PaketDeskripsi'] ?? '');
$paket->PaketHarga      = (int)$_POST['PaketHarga'];
$paket->PaketDurasi     = trim($_POST['PaketDurasi'] ?? '');
$paket->PaketStatus     = $_POST['PaketStatus'] ?? '';
$paket->PaketDirGbr     = $gambarBaruPath; 

if (empty($paket->PaketNama) || empty($paket->PaketKategori) || $paket->PaketHarga < 0) {
    $_SESSION['error_message'] = "Harap isi semua field wajib.";
    header("Location: form-paketjasa.php");
    exit;
}

if ($paket->update()) {
    $_SESSION['success_message'] = "Layanan berhasil diperbarui!";
} else {
    $_SESSION['error_message'] = "Gagal memperbarui layanan.";
}

header("Location: form-paketjasa.php");
exit;
?>