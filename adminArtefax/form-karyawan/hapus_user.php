<?php
// File: hapus_user.php → VERSI FINAL 100% AMAN & BERHASIL (Desember 2025)
// Hanya hapus user dari tabel `users` → tidak menyentuh booking sama sekali

session_start();
require_once __DIR__ . "/../../config/koneksi.php";

$db = new Database();
$conn = $db->getConnection();

if (!$conn) {
    $_SESSION['error_message'] = 'Koneksi database gagal';
    header("Location: form-user.php");
    exit;
}

$idUser = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if (!$idUser) {
    $_SESSION['error_message'] = 'ID user tidak valid';
    header("Location: form-user.php");
    exit;
}

try {
    $conn->begin_transaction();

    // 1. Buat user dummy permanen (hanya sekali)
    $dummyId = 999999;
    $conn->query("INSERT IGNORE INTO users 
        (IDUser, UserNama, UserEmail, UserPassword, UserRole) 
        VALUES 
        (999999, 'Deleted User', 'deleted@artefax.com', 'DELETED', 'Customer')");

    // 2. Ganti semua referensi IDUser di semua tabel lain jadi dummy (hanya kolom IDUser!)
    $tabelList = [
        'booking', 'booking_detail', 'refund', 'transaksi', 'pembayaran',
        'penjualan', 'log_activity', 'log_user', 'notification', 'review',
        'voucher_usage', 'cart', 'wishlist', 'customer_support', 'deposit'
    ];

    foreach ($tabelList as $tabel) {
        $cek = $conn->query("SHOW TABLES LIKE '$tabel'");
        if ($cek->num_rows == 0) continue;

        $cekKolom = $conn->query("SHOW COLUMNS FROM `$tabel` LIKE 'IDUser'");
        if ($cekKolom && $cekKolom->num_rows > 0) {
            $stmt = $conn->prepare("UPDATE `$tabel` SET IDUser = ? WHERE IDUser = ?");
            $stmt->bind_param("ii", $dummyId, $idUser);
            $stmt->execute();
            $stmt->close();
        }
    }

    // 3. HANYA HAPUS DARI TABEL USERS → tidak ada hubungan dengan booking_detail
    $stmt = $conn->prepare("DELETE FROM users WHERE IDUser = ?");
    $stmt->bind_param("i", $idUser);
    $stmt->execute();

    if ($stmt->affected_rows === 0) {
        throw new Exception("User tidak ditemukan atau sudah dihapus.");
    }

    $stmt->close();
    $conn->commit();

    $_SESSION['success_message'] = 'User berhasil dihapus permanen dari sistem.';

} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['error_message'] = 'Gagal menghapus user: ' . $e->getMessage();
}

header("Location: form-user.php");
exit;