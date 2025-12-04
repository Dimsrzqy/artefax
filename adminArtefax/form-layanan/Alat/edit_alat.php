<?php
session_start();
require_once __DIR__ . "/../../../config/koneksi.php";
require_once __DIR__ . "/../../../class/alat.php";

$db = new Database();
$conn = $db->getConnection();
$alat = new Alat($conn);

$alat->IDAlat = (int)$_POST['IDAlat'];

if ($alat->IDAlat <= 0) {
    $_SESSION['error_message'] = "ID tidak valid.";
    header("Location: form-alat.php");
    exit;
}

$stmt = $conn->prepare("SELECT AlatDirGbr FROM alat WHERE IDAlat = ?");
$stmt->bind_param("i", $alat->IDAlat);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$gambarLamaDb = $row ? trim($row['AlatDirGbr'] ?? '') : '';


$uploadDir = $_SERVER['DOCUMENT_ROOT'] . "/artefax/Paket/img/produk/alat/";
$webPath = "alat/";
$gambarBaruPath = $gambarLamaDb; 

$gambarLamaPost = trim($_POST['gambarLama'] ?? '');

 
if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['gambar'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif'];

    if (!in_array($ext, $allowed)) {
        $_SESSION['error_message'] = "Format gambar tidak didukung!";
        header("Location: form-alat.php"); 
        exit;
    }

    if ($file['size'] > 5 * 1024 * 1024) {
        $_SESSION['error_message'] = "Ukuran gambar maksimal 5MB!";
        header("Location: form-alat.php"); 
        exit;
    }

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
        $gambarBaruPath = $webPath . $newFilename;
        if ($gambarLamaDb !== '') {
            $fileLamaPath = $_SERVER['DOCUMENT_ROOT'] . "/artefax/Paket/img/produk/" . $gambarLamaDb;
            if (file_exists($fileLamaPath)) {
                unlink($fileLamaPath);
            }
        }
    } else {
        $_SESSION['error_message'] = "Gagal upload gambar baru.";
        header("Location: form-alat.php"); 
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
 
$alat->AlatNama       = trim($_POST['AlatNama'] ?? '');
$alat->AlatKategori   = $_POST['AlatKategori'] ?? '';
$alat->AlatDeskripsi  = trim($_POST['AlatDeskripsi'] ?? '');
$alat->AlatHarga      = (int)$_POST['AlatHarga'];
$alat->AlatStok    = (int)$_POST['AlatStok'];
$alat->AlatStatus     = $_POST['AlatStatus'] ?? '';
$alat->AlatDirGbr     = $gambarBaruPath; 

if (empty($alat->AlatNama) || empty($alat->AlatKategori) || $alat->AlatHarga < 0) {
    $_SESSION['error_message'] = "Harap isi semua field wajib.";
    header("Location: form-alat.php");
    exit;
}

if ($alat->update()) {
    $_SESSION['success_message'] = "Layanan berhasil diperbarui!";
} else {
    $_SESSION['error_message'] = "Gagal memperbarui layanan.";
}

header("Location: form-alat.php");
exit;
?>