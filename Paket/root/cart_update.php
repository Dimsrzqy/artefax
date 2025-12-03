<?php
session_start();

$index = $_POST['index'] ?? null;
$qty   = $_POST['qty']  ?? 1;

if ($index !== null && isset($_SESSION['cart'][$index])) {
    $_SESSION['cart'][$index]['qty'] = max(1, (int)$qty);
}

echo 'ok';