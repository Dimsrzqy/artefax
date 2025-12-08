<?php

session_start();

require_once __DIR__ . "/../../../config/koneksi.php";

require_once __DIR__ . "/../../../class/alat.php";


$db = new Database();

$conn = $db->getConnection();

$alat = new Alat($conn);


// --- PERBAIKAN JALUR UPLOAD (Menggunakan jalur relatif dari file PHP) ---
// File ini ada di: adminArtefax/form-layanan/Alat/
// Target folder upload: Paket/img/produk/alat/
// Kita naik 3 level dari lokasi saat ini untuk mencapai root direktori aplikasi.
$baseDir = dirname(dirname(dirname(__DIR__))); 
$uploadDir = $baseDir . "/Paket/img/produk/alat/"; 
$webPath = "alat/";
// ------------------------------------------------------------------------


$gambarPath = null;


// --- SOLUSI: Cek dan buat folder jika belum ada/bisa ditulis ---
if (!is_dir($uploadDir)) {
    if (!mkdir($uploadDir, 0755, true)) {
        $_SESSION['error_message'] = "Gagal membuat direktori upload: Izin folder tidak benar atau path salah.";
        header("Location: form-alat.php"); exit;
    }
} elseif (!is_writable($uploadDir)) {
    // Jika folder ada tetapi tidak dapat ditulis
    $_SESSION['error_message'] = "Direktori upload ada tetapi tidak dapat ditulis. Mohon atur izin folder ($uploadDir) ke 755 atau 777.";
    header("Location: form-alat.php"); exit;
}
// ---------------------------------------------------------------


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
    $stmt = $conn->query("SELECT AlatDirGbr FROM alat WHERE AlatDirGbr LIKE '{$webPath}alat%' ORDER BY IDAlat DESC LIMIT 1");
    $last = $stmt->fetch_assoc();
    $nextNum = 1;

    if ($last) {
        preg_match('/alat(\d+)/', $last['AlatDirGbr'], $m);
        $nextNum = isset($m[1]) ? (intval($m[1]) + 1) : 1;
    }

    $newFilename = "alat" . $nextNum . "." . $ext;
    $destination = $uploadDir . $newFilename;

    if (move_uploaded_file($file['tmp_name'], $destination)) {
        // Simpan path relatif web ke database
        $gambarPath = $webPath . $newFilename;
    } else {
        // Jika gagal move_uploaded_file()
        $_SESSION['error_message'] = "Gagal upload gambar. Server tidak bisa memindahkan file. (Cek izin folder $uploadDir)";
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
    $_SESSION['error_message'] = "Gagal menambahkan layanan. Database error.";
}


header("Location: form-alat.php");
exit; 
?>