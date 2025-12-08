<?php

session_start();

require_once __DIR__ . "/../../../config/koneksi.php";

require_once __DIR__ . "/../../../class/paketjasa.php";


$db = new Database();

$conn = $db->getConnection();

$paket = new PaketJasa($conn);

// --- PERBAIKAN PATH UPLOAD DENGAN JALUR RELATIF ---
// Asumsi: File ini berada 3 level di bawah root aplikasi (form-layanan/PaketJasa/)
// Ubah path ini jika struktur folder Anda berbeda.
$baseDir = dirname(dirname(dirname(__DIR__))); // Naik ke root aplikasi
$uploadDir = $baseDir . "/Paket/img/produk/paketjasa/";
$webPath = "paketjasa/";
// ----------------------------------------------------


$gambarPath = null;


// --- SOLUSI: Cek dan buat folder jika belum ada ---
if (!is_dir($uploadDir)) {
    if (!mkdir($uploadDir, 0755, true)) {
        $_SESSION['error_message'] = "Gagal membuat direktori upload. Cek izin folder!";
        header("Location: form-paketjasa.php"); exit;
    }
}
// --- SOLUSI: Pastikan folder memiliki izin tulis yang benar (opsional, tapi disarankan) ---
if (!is_writable($uploadDir)) {
     // Di hosting, set izin ke 0755 (atau 777 jika 755 tidak berfungsi, tapi 777 berisiko keamanan)
    if (!chmod($uploadDir, 0755)) { 
        // Lanjutkan, tapi tampilkan peringatan jika chmod gagal (misal karena user berbeda)
        // Kita tidak bisa memaksa izin di sini, tapi kita sudah mencoba di atas.
    }
}


if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['gambar'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg'];

    if (!in_array($ext, $allowed)) {
        $_SESSION['error_message'] = "Untuk format gambar yang diupload harus jpg!";
        header("Location: form-paketjasa.php"); exit;
    }

    if ($file['size'] > 5 * 1024 * 1024) {
        $_SESSION['error_message'] = "Ukuran gambar maksimal 5MB!";
        header("Location: form-paketjasa.php"); exit;
    }

    // Cari nomor urut terakhir
    $stmt = $conn->query("SELECT PaketDirGbr FROM paketjasa WHERE PaketDirGbr LIKE '{$webPath}jasa%' ORDER BY IDPaket DESC LIMIT 1");
    $last = $stmt->fetch_assoc();
    $nextNum = 1;

    if ($last) {
        preg_match('/jasa(\d+)/', $last['PaketDirGbr'], $m);
        $nextNum = isset($m[1]) ? (intval($m[1]) + 1) : 1;
    }

    $newFilename = "jasa" . $nextNum . "." . $ext;
    $destination = $uploadDir . $newFilename;

    if (move_uploaded_file($file['tmp_name'], $destination)) {
        // Path yang disimpan di database (hanya nama file atau path web relatif)
        $gambarPath = $webPath . $newFilename; 
    } else {
        // Jika gagal move, tampilkan error yang lebih spesifik jika memungkinkan
        $_SESSION['error_message'] = "Gagal upload gambar. Error: Tidak dapat memindahkan file ke direktori tujuan ($uploadDir). Cek izin folder!";
        header("Location: form-paketjasa.php"); exit;
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
    $_SESSION['error_message'] = "Gagal menambahkan layanan. Database error.";
}


header("Location: form-paketjasa.php");

exit; 
?>