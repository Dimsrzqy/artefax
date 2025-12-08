<?php
// ============================================
// checkoutproses.php (FINAL VERSION - FIX ENUM NULL)
// ============================================

session_start();
ob_start();

require_once __DIR__ . '/../config/koneksi.php';

// Cek akses & session cart
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_SESSION['cart'])) {
    $_SESSION['error_checkout'] = "Akses tidak valid atau keranjang kosong.";
    header('Location: checkout.php');
    exit;
}

// Cek login user
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
$deskripsi   = trim($_POST['deskripsi'] ?? '');

// [PENTING] Normalisasi Pembayaran (DP/Lunas)
$raw_payment = $_POST['payment'] ?? 'dp'; 
if (strtolower($raw_payment) === 'dp') {
    $opsi_bayar = 'DP';
} else {
    $opsi_bayar = 'Lunas';
}

// 1. VALIDASI WAJIB
if (empty($tgl_mulai) || empty($tgl_selesai) || empty($phone)) {
    $_SESSION['error_checkout'] = "Data tanggal dan nomor HP wajib diisi.";
    header('Location: checkout.php');
    exit;
}

// 2. VALIDASI TANGGAL
if (strtotime($tgl_selesai) <= strtotime($tgl_mulai)) {
    $_SESSION['error_checkout'] = "Tanggal pengembalian tidak boleh lebih awal dari tanggal mulai penyewaan.";
    $redirectParams = (!empty($tgl_mulai)) ? "?date=" . date('Y-m-d', strtotime($tgl_mulai)) : "";
    header("Location: checkout.php" . $redirectParams);
    exit;
}

// Hitung total awal & Cek Tipe Produk
$cart = $_SESSION['cart'];
$total = 0;
$hasAlat = $hasPaket = false;

foreach ($cart as $item) {
    $qty   = (int)($item['quantity'] ?? $item['qty'] ?? 1);
    $price = (float)($item['price'] ?? $item['harga'] ?? 0);
    $total += $price * $qty;

    $jenis = strtolower(trim($item['jenis'] ?? $item['tipe'] ?? $item['type'] ?? ''));
    if (strpos($jenis, 'alat') !== false) $hasAlat = true;
    if (strpos($jenis, 'paket') !== false || strpos($jenis, 'jasa') !== false) $hasPaket = true;
}

// Cek kebutuhan data berdasarkan jenis produk
$needLokasi  = $hasPaket;             
$needJaminan = $hasAlat && !$hasPaket; 

// 3. LOGIKA KENAIKAN HARGA
$durasi_jam = 0;
if (!empty($tgl_mulai) && !empty($tgl_selesai)) {
    $ts_mulai   = strtotime($tgl_mulai);
    $ts_selesai = strtotime($tgl_selesai);
    
    // Hitung selisih jam
    $durasi_jam = ($ts_selesai - $ts_mulai) / 3600; 

    // Hitung jumlah hari (pembulatan ke atas per 24 jam)
    $jumlah_hari = ceil($durasi_jam / 24);
    if ($jumlah_hari < 1) $jumlah_hari = 1;

    // Kalikan total dengan jumlah hari
    $total = $total * $jumlah_hari;
}

// Validasi Kelengkapan Data Sesuai Jenis
if ($needLokasi && empty($alamat)) {
    $_SESSION['error_checkout'] = "Alamat lokasi acara wajib diisi untuk paket/jasa.";
    header('Location: checkout.php');
    exit;
}
if ($needJaminan && empty($jaminan)) {
    $_SESSION['error_checkout'] = "Jaminan wajib dipilih untuk sewa alat saja.";
    header('Location: checkout.php');
    exit;
}

$db = new Database();
$conn = $db->getConnection();

try {
    $conn->begin_transaction();

    // ============================================================
    // PERBAIKAN PENTING: MENANGANI NULL UNTUK ENUM
    // ============================================================
    
    // Jika butuh lokasi, pakai isinya. Jika tidak, kirim NULL.
    $alamatFinal = $needLokasi ? $alamat : NULL;
    
    // Jika butuh jaminan, pakai isinya. Jika tidak, kirim NULL (Bukan '').
    // Karena ENUM hanya terima 'KTP','SIM','STNK' atau NULL.
    $jaminanFinal = $needJaminan ? $jaminan : NULL;

    // INSERT BOOKING
    $sqlBooking = "INSERT INTO booking (IDUser, BkgAlamat, BkgTglMulai, BkgTglSelesai, BkgTotalHarga, BkgJaminan, BkgStatus, CreatedAt, UpdatedAt) 
                   VALUES (?, ?, ?, ?, ?, ?, 'Pending', NOW(), NOW())";

    $stmt = $conn->prepare($sqlBooking);
    
    // Bind Param: s = string, d = double, i = integer
    // Urutan: IDUser(i), Alamat(s), TglMulai(s), TglSelesai(s), Total(d), Jaminan(s)
    $stmt->bind_param("isssds", $userId, $alamatFinal, $tgl_mulai, $tgl_selesai, $total, $jaminanFinal);
    
    $stmt->execute();
    $bookingId = $conn->insert_id;
    $stmt->close();

    // INSERT DETAIL BOOKING
    $stmtDetail = $conn->prepare("INSERT INTO booking_detail 
        (IDBooking, IDPaket, IDAlat, BkgDetailJenis) VALUES (?, ?, ?, ?)");

    foreach ($cart as $item) {
        $idItem = (int)($item['id'] ?? 0);
        $jenisRaw  = strtolower(trim($item['jenis'] ?? $item['tipe'] ?? $item['type'] ?? ''));

        if (strpos($jenisRaw, 'paket') !== false || strpos($jenisRaw, 'jasa') !== false) {
            $idPaket = $idItem;
            $idAlat  = null;
            $jenisDB  = 'Paket Jasa'; 
        } else {
            $idPaket = null;
            $idAlat  = $idItem;
            $jenisDB  = 'Alat';
        }

        $stmtDetail->bind_param("iiis", $bookingId, $idPaket, $idAlat, $jenisDB);
        $stmtDetail->execute();
    }
    $stmtDetail->close();

    $conn->commit();

    $_SESSION['current_booking_id'] = $bookingId;
    $_SESSION['checkout_success'] = true;
    $_SESSION['checkout_payment'] = $opsi_bayar; 
    
    ob_end_flush();
    header("Location: pembayaran.php?id=$bookingId");
    exit;

} catch (Exception $e) {
    $conn->rollback();
    // Tampilkan error biar ketahuan kalau ada masalah database
    echo "<h1>Terjadi Error Database!</h1>";
    echo "<p>Pesan Error: " . $e->getMessage() . "</p>";
    die();
}
?>