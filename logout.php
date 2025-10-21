<?php
session_start();
session_unset();   // Hapus semua session
session_destroy(); // Hancurkan session
header("index.php"); // Redirect ke halaman login
exit;
?>
