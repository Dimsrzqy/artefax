<?php
session_start();
require_once __DIR__ . "/../../../config/koneksi.php";
require_once __DIR__ . "/../../../class/alat.php";

$db = new Database();
$conn = $db->getConnection();
$alat = new Alat($conn);

$alat->IDAlat = (int)($_POST['id'] ?? 0);
 
if ($alat->IDAlat <= 0) {
    $_SESSION['error_message'] = "ID layanan tidak valid.";
    header("Location: form-alat.php");
    exit;
}

$stmt = $conn->prepare("SELECT AlatDirGbr FROM alat WHERE IDAlat = ?");
$stmt->bind_param("i", $alat->IDAlat);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$gambarPath = $row['AlatDirGbr'] ?? null;
$stmt->close();

if ($alat->delete()) {

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

header("Location: form-alat.php");
exit;
?>