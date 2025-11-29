<?php
if (session_status() == PHP_SESSION_NONE) session_start();
$cart_count = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;
?>

<a href="#" data-bs-toggle="modal" data-bs-target="#cartModal" class="nav-link position-relative">
    <i class="fa fa-shopping-cart"></i>
    <span class="badge bg-danger position-absolute" style="top: -6px; right: -10px;">
        <?= $cart_count ?>
    </span>
</a>
