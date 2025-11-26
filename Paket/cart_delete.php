<?php
session_start();

$index = $_POST['index'] ?? null;

if ($index !== null && isset($_SESSION['cart'][$index])) {
    unset($_SESSION['cart'][$index]);
    $_SESSION['cart'] = array_values($_SESSION['cart']); // reset index
}

echo 'ok';
