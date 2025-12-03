<?php
// ============================================
// root/cart_delete.php
// Hapus item dari cart
// ============================================

session_start();

header('Content-Type: application/json');

$index = $_POST['index'] ?? null;

if ($index === null) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Index tidak ditemukan'
    ]);
    exit;
}

// Convert index to array keys
$cart_keys = array_keys($_SESSION['cart'] ?? []);

if (!isset($cart_keys[$index])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Item tidak ditemukan'
    ]);
    exit;
}

$key = $cart_keys[$index];

// Delete item
if (isset($_SESSION['cart'][$key])) {
    unset($_SESSION['cart'][$key]);
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Item berhasil dihapus',
        'cart_count' => count($_SESSION['cart'])
    ]);
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Item tidak ditemukan'
    ]);
}
?>