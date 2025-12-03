<?php
// ============================================
// root/cart_update.php
// Update quantity item di cart
// ============================================

session_start();

header('Content-Type: application/json');

$index = $_POST['index'] ?? null;
$qty = (int)($_POST['qty'] ?? 1);

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

// Update quantity
if (isset($_SESSION['cart'][$key])) {
    $_SESSION['cart'][$key]['qty'] = max(1, $qty); // Minimal 1
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Quantity berhasil diupdate',
        'cart_count' => count($_SESSION['cart'])
    ]);
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Item tidak ditemukan'
    ]);
}
?>