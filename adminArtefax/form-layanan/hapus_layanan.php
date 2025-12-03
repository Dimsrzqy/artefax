<?php
session_start();
require_once __DIR__ . "/../../config/koneksi.php";
require_once __DIR__ . "/../../class/paketjasa.php";

$db = new Database();
$conn = $db->getConnection();
$paket = new PaketJasa($conn);

$paket->IDPaket = (int)($_POST['id'] ?? 0);
 
if ($paket->IDPaket <= 0) {
    $_SESSION['error_message'] = "ID layanan tidak valid.";
    header("Location: form-layanan.php");
    exit;
}

$stmt = $conn->prepare("SELECT PaketDirGbr FROM paketjasa WHERE IDPaket = ?");
$stmt->bind_param("i", $paket->IDPaket);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$gambarPath = $row['PaketDirGbr'] ?? null;
$stmt->close();

if ($paket->delete()) {

    if (!empty($gambarPath)) {
        $fullPath = $_SERVER['DOCUMENT_ROOT'] . "/artefax/Paket/img/produk/" . $gambarPath;

        if (file_exists($fullPath)) {
            if (unlink($fullPath)) {
                error_log("Gambar berhasil dihapus: " . $fullPath); 
            } else {
                error_log("Gagal menghapus file gambar: " . $fullPath);
            }
        }
    }

    $_SESSION['success_message'] = "Layanan dan gambar terkait berhasil dihapus!";
} else {
    $_SESSION['error_message'] = "Gagal menghapus layanan dari database.";
}

header("Location: form-layanan.php");
exit;
?>