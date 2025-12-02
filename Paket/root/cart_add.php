<?php
session_start();

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Terima parameter dari POST
$id = $_POST['id'] ?? null;
$type = $_POST['type'] ?? $_POST['tipe'] ?? null;  // ⬅️ Support kedua format
$name = $_POST['name'] ?? '';
$price = $_POST['price'] ?? 0;
$qty = (int)($_POST['qty'] ?? 1);

// Validasi
if (!$id || !$type) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Data tidak lengkap'
    ]);
    exit;
}

// Buat key unik untuk cart
$cart_key = $type . '_' . $id;

// Tambah atau update cart
if (isset($_SESSION['cart'][$cart_key])) {
    $_SESSION['cart'][$cart_key]['qty'] += $qty;
} else {
    $_SESSION['cart'][$cart_key] = [
        'id' => $id,
        'type' => $type,
        'name' => $name,
        'price' => $price,
        'qty' => $qty
    ];
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode([
    'status' => 'success',
    'message' => 'Berhasil ditambahkan',
    'cart_count' => count($_SESSION['cart'])
]);