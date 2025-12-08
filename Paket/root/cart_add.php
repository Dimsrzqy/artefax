<?php
session_start();

// Pastikan header JSON ada di paling atas sebelum output apapun (opsional tapi good practice)
header('Content-Type: application/json');

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// 1. Terima parameter dari POST
$id = $_POST['id'] ?? null;
$type = $_POST['type'] ?? $_POST['tipe'] ?? null;  // Support kedua format
$name = $_POST['name'] ?? '';
$price = $_POST['price'] ?? 0;
$qty = (int)($_POST['qty'] ?? 1);
$date = $_POST['date'] ?? date('Y-m-d'); // <--- BARU: Ambil tanggal booking

// Validasi
if (!$id || !$type) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Data tidak lengkap'
    ]);
    exit;
}

// 2. Buat key unik untuk cart (Gabungan Tipe + ID + TANGGAL)
// Supaya booking alat yang sama di TANGGAL BEDA dianggap item baru
$cart_key = $type . '_' . $id . '_' . $date;

// 3. Tambah atau update cart
if (isset($_SESSION['cart'][$cart_key])) {
    // Jika item sama DAN tanggal sama, tambahkan qty
    $_SESSION['cart'][$cart_key]['qty'] += $qty;
} else {
    // Jika item baru atau tanggal beda, buat baru
    $_SESSION['cart'][$cart_key] = [
        'id' => $id,
        'type' => $type,
        'name' => $name,
        'price' => $price,
        'qty' => $qty,
        'date' => $date // <--- BARU: Simpan tanggalnya
    ];
}

// 4. Return JSON response dengan jumlah cart terbaru
echo json_encode([
    'status' => 'success',
    'message' => 'Berhasil ditambahkan',
    'cart_count' => count($_SESSION['cart']) // Ini yang dipakai JS untuk update navbar
]);
?>