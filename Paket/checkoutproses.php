<?php
session_start();
include 'config/koneksi.php';

// pastikan ada keranjang
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    header("Location: cart.php");
    exit;
}

$cart_items = $_SESSION['cart'];
$subtotal = 0;

foreach($cart_items as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}

// simpan order (contoh minimal)
try {
    $sql = "INSERT INTO orders (total_amount) VALUES (:total_amount)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['total_amount' => $subtotal]);

    // bersihkan cart
    unset($_SESSION['cart']);

    $_SESSION['success_checkout'] = "Pesanan kamu berhasil! Tunggu konfirmasi admin ya 😊";

    header("Location: checkout.php");
    exit;
} catch (PDOException $e) {
    $_SESSION['error_checkout'] = "Checkout gagal, coba lagi!";
    header("Location: checkout.php");
    exit;
    // nanti tambahkan proses simpan database disini

$_SESSION['success_checkout'] = "Pesanan berhasil! Mohon tunggu konfirmasi admin.";

header("Location: checkout.php");
exit;
}
