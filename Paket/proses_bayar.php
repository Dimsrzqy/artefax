
<?php
// ============================================
// proses_pembayaran.php - FIXED
// Fungsi: Simpan booking setelah checkout
// ============================================

session_start();
require_once __DIR__ . '/../config/koneksi.php';

// Cek method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Akses ditolak.");
}

// Ambil data dari form
$userId = $_POST['user_id'] ?? null;
$name = $_POST['name'] ?? '';
$alamat = $_POST['alamat'] ?? '';
$jaminan = $_POST['jaminan'] ?? '';
$phone = $_POST['phone'] ?? '';
$tgl_mulai = $_POST['tgl_mulai'] ?? '';
$tgl_selesai = $_POST['tgl_selesai'] ?? '';
$payment = $_POST['payment'] ?? 'DP';
$deskripsi = $_POST['deskripsi'] ?? '';

// Validasi user ID
if (!$userId) {
    $_SESSION['error_checkout'] = "User tidak ditemukan. Silakan login terlebih dahulu.";
    header("Location: checkout.php");
    exit;
}

// Validasi cart
$cart = $_SESSION['cart'] ?? [];
if (empty($cart)) {
    $_SESSION['error_checkout'] = "Keranjang kosong.";
    header("Location: shop.php");
    exit;
}

// Hitung total dan deteksi jenis produk
$total = 0.0;
$hasAlat = false;
$hasPaket = false;

foreach ($cart as $item) {
    $qty = (int)($item['quantity'] ?? $item['qty'] ?? 1);
    $price = (float)($item['price'] ?? 0);
    $total += $price * $qty;
    
    $jenis = strtolower(trim($item['jenis'] ?? $item['tipe'] ?? $item['type'] ?? ''));
    
    if ($jenis === 'alat') {
        $hasAlat = true;
    }
    
    if ($jenis === 'paket' || $jenis === 'jasa') {
        $hasPaket = true;
    }
}

// Logika validasi
$needJaminan = ($hasAlat === true && $hasPaket === false);
$needLokasi = $hasPaket;

// Validasi jaminan
if ($needJaminan && empty($jaminan)) {
    $_SESSION['error_checkout'] = "Jaminan wajib diisi untuk penyewaan alat.";
    header("Location: checkout.php");
    exit;
}

// Validasi lokasi
if ($needLokasi && empty($alamat)) {
    $_SESSION['error_checkout'] = "Alamat lokasi acara wajib diisi untuk paket/jasa.";
    header("Location: checkout.php");
    exit;
}

// =============================================
// SIMPAN KE DATABASE: BOOKING
// =============================================

$db = new Database();
$conn = $db->getConnection();

if (!$conn) {
    die("Koneksi database gagal.");
}

$BkgStatus = "Pending";
$CreatedAt = date('Y-m-d H:i:s');
$UpdatedAt = $CreatedAt;

// ✅ PERBAIKAN: Gunakan string kosong '' jika tidak perlu, bukan NULL
// Karena kolom BkgAlamat dan BkgJaminan di database tidak boleh NULL
$alamatFinal = $needLokasi ? $alamat : '';
$jaminanFinal = $needJaminan ? $jaminan : '';

// Insert booking dengan BkgTotalHarga
$sql = "INSERT INTO booking 
        (IDUser, BkgAlamat, BkgTglMulai, BkgTglSelesai, BkgStatus, BkgJaminan, BkgTotalHarga, CreatedAt, UpdatedAt) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Error prepare statement: " . $conn->error);
}

$stmt->bind_param(
    "isssssdss",
    $userId,
    $alamatFinal,    // ✅ String kosong '' jika tidak perlu
    $tgl_mulai,
    $tgl_selesai,
    $BkgStatus,
    $jaminanFinal,   // ✅ String kosong '' jika tidak perlu
    $total,
    $CreatedAt,
    $UpdatedAt
);

if (!$stmt->execute()) {
    $_SESSION['error_checkout'] = "Gagal menyimpan booking: " . $stmt->error;
    header("Location: checkout.php");
    exit;
}

$IDBooking = $conn->insert_id;
$stmt->close();

// =============================================
// SIMPAN DETAIL BOOKING
// =============================================

foreach ($cart as $item) {
    $itemId = $item['id'];
    $itemType = strtolower($item['jenis'] ?? $item['tipe'] ?? $item['type'] ?? '');
    
    $IDPaket = ($itemType === 'paket' || $itemType === 'jasa') ? $itemId : null;
    $IDAlat = ($itemType === 'alat') ? $itemId : null;
    
    $sql2 = "INSERT INTO booking_detail (IDBooking, IDPaket, IDAlat) 
             VALUES (?, ?, ?)";
    
    $stmt2 = $conn->prepare($sql2);
    $stmt2->bind_param("iii", $IDBooking, $IDPaket, $IDAlat);
    $stmt2->execute();
    $stmt2->close();
}

// =============================================
// SIMPAN DATA KE SESSION UNTUK PEMBAYARAN
// =============================================

$_SESSION['checkout_booking_id'] = $IDBooking;
$_SESSION['checkout_total'] = $total;
$_SESSION['checkout_payment'] = strtoupper($payment); 
$_SESSION['checkout_name'] = $name;

// Kosongkan cart
unset($_SESSION['cart']);

// Redirect ke halaman pembayaran
header("Location: pembayaran.php");
exit;
?>