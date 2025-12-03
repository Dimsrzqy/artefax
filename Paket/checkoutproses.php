<?php
// ============================================
// checkoutproses.php 
// ============================================

session_start();
ob_start();

require_once __DIR__ . '/../config/koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_SESSION['cart'])) {
    $_SESSION['error_checkout'] = "Akses tidak valid atau keranjang kosong.";
    header('Location: checkout.php');
    exit;
}

$userId = $_SESSION['user']['IDUser'] ?? $_SESSION['user']['id'] ?? null;
if (!$userId) {
    header('Location: ../view/login.php');
    exit;
}

// Ambil data form
$alamat      = trim($_POST['alamat'] ?? '');
$jaminan     = trim($_POST['jaminan'] ?? '');
$phone       = trim($_POST['phone'] ?? '');
$tgl_mulai   = $_POST['tgl_mulai'] ?? '';
$tgl_selesai = $_POST['tgl_selesai'] ?? '';

// Validasi wajib
if (empty($tgl_mulai) || empty($tgl_selesai) || empty($phone)) {
    $_SESSION['error_checkout'] = "Data tanggal dan nomor HP wajib diisi.";
    header('Location: checkout.php');
    exit;
}
if (strtotime($tgl_selesai) <= strtotime($tgl_mulai)) {
    $_SESSION['error_checkout'] = "Tanggal selesai harus lebih besar dari tanggal mulai.";
    header('Location: checkout.php');
    exit;
}

// Hitung total + deteksi jenis produk
$cart = $_SESSION['cart'];
$total = 0;
$hasAlat = $hasPaket = false;

foreach ($cart as $item) {
    $qty   = (int)($item['quantity'] ?? $item['qty'] ?? 1);
    $price = (float)($item['price'] ?? $item['harga'] ?? 0);
    $total += $price * $qty;

    $jenis = strtolower(trim($item['jenis'] ?? $item['tipe'] ?? $item['type'] ?? ''));
    if ($jenis === 'alat') $hasAlat = true;
    if ($jenis === 'paket' || $jenis === 'jasa') $hasPaket = true;
}

// Validasi alamat & jaminan
if ($hasPaket && empty($alamat)) {
    $_SESSION['error_checkout'] = "Alamat lokasi acara wajib diisi untuk paket/jasa.";
    header('Location: checkout.php');
    exit;
}
if ($hasAlat && !$hasPaket && empty($jaminan)) {
    $_SESSION['error_checkout'] = "Jaminan wajib dipilih untuk sewa alat saja.";
    header('Location: checkout.php');
    exit;
}

$db = new Database();
$conn = $db->getConnection();

try {
    $conn->begin_transaction();

    // INSERT BOOKING – sesuai struktur tabel kamu
    $sqlBooking = $jaminan 
        ? "INSERT INTO booking (IDUser, BkgAlamat, BkgTglMulai, BkgTglSelesai, BkgTotalHarga, BkgJaminan, BkgStatus, CreatedAt, UpdatedAt) 
           VALUES (?, ?, ?, ?, ?, ?, 'Pending', NOW(), NOW())"
        : "INSERT INTO booking (IDUser, BkgAlamat, BkgTglMulai, BkgTglSelesai, BkgTotalHarga, BkgStatus, CreatedAt, UpdatedAt) 
           VALUES (?, ?, ?, ?, ?, 'Pending', NOW(), NOW())";

    $stmt = $conn->prepare($sqlBooking);
    if ($jaminan) {
        $stmt->bind_param("isssds", $userId, $alamat, $tgl_mulai, $tgl_selesai, $total, $jaminan);
    } else {
        $stmt->bind_param("isssd", $userId, $alamat, $tgl_mulai, $tgl_selesai, $total);
    }
    $stmt->execute();
    $bookingId = $conn->insert_id;
    $stmt->close();

    // INSERT DETAIL – INI YANG PALING PENTING: PAKAI ENUM YANG BENAR!
    $stmtDetail = $conn->prepare("INSERT INTO booking_detail 
        (IDBooking, IDPaket, IDAlat, BkgDetailJenis) VALUES (?, ?, ?, ?)");

    foreach ($cart as $item) {
        $idItem = (int)($item['id'] ?? 0);
        $jenis  = strtolower(trim($item['jenis'] ?? $item['tipe'] ?? $item['type'] ?? ''));

        if ($jenis === 'paket' || $jenis === 'jasa') {
            $idPaket = $idItem;
            $idAlat  = null;
            $jenisDB  = 'Paket Jasa';        // SESUAI ENUM DI DATABASE KAMU
        } else {
            $idPaket = null;
            $idAlat  = $idItem;
            $jenisDB  = 'Alat';              // SESUAI ENUM DI DATABASE KAMU
        }

        $stmtDetail->bind_param("iiis", $bookingId, $idPaket, $idAlat, $jenisDB);
        $stmtDetail->execute();
    }
    $stmtDetail->close();

    $conn->commit();

    // Simpan info untuk halaman pembayaran
    $_SESSION['current_booking_id'] = $bookingId;
    $_SESSION['checkout_success'] = true;

    // JANGAN HAPUS CART DI SINI (biar tetap ada sampai upload bukti)
    
    ob_end_flush();
    header("Location: pembayaran.php?id=$bookingId");
    exit;

} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['error_checkout'] = "Gagal memproses checkout: " . $e->getMessage();
    ob_end_flush();
    header('Location: checkout.php');
    exit;
}
?>