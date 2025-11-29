<?php
session_start();
include "../config/koneksi.php";

// Cek submit
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Akses ditolak.");
}

// Ambil data dari form
$IDBooking      = $_POST['IDBooking'];
$PbrMetode      = $_POST['PbrMetode'];        // BCA / BRI / GOPAY / DANA
$PbrKeterangan  = $_POST['PbrKeterangan'];    // LUNAS / DP
$PbrJumlah      = $_POST['PbrJumlah'];        // angka
$PbrStatus      = "Pending";                  // default
$PbrConfirmed   = 0;

// Validasi booking ID
if (!$IDBooking) {
    die("Booking ID tidak ditemukan.");
}

// =============================================
// ================ Upload Bukti ================
// =============================================

$upload_dir = "../uploads/bukti/";

// Buat folder jika belum ada
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

$namaFile = null;

if (isset($_FILES['PbrBukti']) && $_FILES['PbrBukti']['error'] === UPLOAD_ERR_OK) {

    $ext = pathinfo($_FILES['PbrBukti']['name'], PATHINFO_EXTENSION);
    $namaFile = "bukti_" . $IDBooking . "_" . time() . "." . $ext;

    $pathSimpan = $upload_dir . $namaFile;

    move_uploaded_file($_FILES['PbrBukti']['tmp_name'], $pathSimpan);

} else {
    die("Upload bukti pembayaran gagal.");
}

// =============================================
// ============= Simpan ke Database =============
// =============================================

$sql = "INSERT INTO pembayaran 
        (IDBooking, PbrMetode, PbrKeterangan, PbrJumlah, PbrStatus, PbrConfirmed, PbrBukti)
        VALUES 
        (?, ?, ?, ?, ?, ?, ?)";

$stmt = $koneksi->prepare($sql);
$stmt->bind_param(
    "issdsis",
    $IDBooking,
    $PbrMetode,
    $PbrKeterangan,
    $PbrJumlah,
    $PbrStatus,
    $PbrConfirmed,
    $namaFile
);

if ($stmt->execute()) {

    // Update status booking → Pending
    $q = $koneksi->prepare("UPDATE booking SET BkgStatus = 'Pending' WHERE IDBooking = ?");
    $q->bind_param("i", $IDBooking);
    $q->execute();

    $_SESSION['success'] = "Pembayaran berhasil dikirim! Menunggu verifikasi admin.";
    header("Location: ../pages/sukses.php"); // Kamu bisa ganti ke halaman sukses
    exit;

} else {
    $_SESSION['error'] = "Gagal menyimpan pembayaran: " . $stmt->error;
    header("Location: pembayaran.php");
    exit;
}
