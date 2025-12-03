<?php
// ============================================
// components/get_cart.php
// Return cart data sebagai JSON
// ============================================

session_start();

header('Content-Type: application/json');

$cart = $_SESSION['cart'] ?? [];
$cart_array = [];
$grand_total = 0;

// Convert associative array to indexed array
foreach ($cart as $key => $item) {
    $subtotal = $item['qty'] * $item['price'];
    $grand_total += $subtotal;
    
    $cart_array[] = [
        'key' => $key,
        'id' => $item['id'],
        'type' => $item['type'],
        'name' => $item['name'],
        'price' => $item['price'],
        'qty' => $item['qty'],
        'subtotal' => $subtotal
    ];
}

echo json_encode([
    'status' => 'success',
    'cart' => $cart_array,
    'cart_count' => count($cart),
    'grand_total' => $grand_total
]);
?>