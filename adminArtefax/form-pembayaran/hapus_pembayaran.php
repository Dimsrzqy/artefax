<?php
session_start();

// Debug sementara (hapus kalau sudah jalan)
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['IDUser']) || empty($_SESSION['IDUser'])) {
    header("Location: ../../view/login.php");
    exit;
}

require_once __DIR__ . "/../../config/koneksi.php";

$db = new Database();
$conn = $db->getConnection();

$id   = $_POST['id'] ?? 0;
$page = $_POST['page'] ?? $_GET['page'] ?? 1;

if (!$id || !is_numeric($id)) {
    $_SESSION['error_message'] = "ID tidak valid!";
    header("Location: daftar_pembayaran.php?page=" . (int)$page);
    exit;
}

try {
    $conn->autocommit(FALSE); // Mulai transaksi

    // 1. Hapus dulu semua refund yang terkait
    $delRefund = $conn->prepare("DELETE FROM refund WHERE IDPembayaran = ?");
    $delRefund->bind_param("i", $id);
    $delRefund->execute();
    $delRefund->close();

    // 2. Hapus bukti transfer (file)
    $stmt = $conn->prepare("SELECT PbrBukti FROM pembayaran WHERE IDPembayaran = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $bukti = $row['PbrBukti'] ?? '';

        if (!empty($bukti)) {
            $clean = preg_replace('#^(\.+[\/\\\\])+#', '', $bukti);
            $filePath = __DIR__ . "/../../uploads/bukti_pembayaran/" . $clean;
            if (file_exists($filePath)) @unlink($filePath);
        }

        // 3. Baru hapus data pembayaran
        $delete = $conn->prepare("DELETE FROM pembayaran WHERE IDPembayaran = ?");
        $delete->bind_param("i", $id);
        if ($delete->execute()) {
            $conn->commit();
            $_SESSION['success_message'] = "Pembayaran berhasil dihapus!";
        } else {
            throw new Exception("Gagal hapus pembayaran dari database");
        }
        $delete->close();
    } else {
        throw new Exception("Data pembayaran tidak ditemukan");
    }
    $stmt->close();

} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['error_message'] = "Gagal hapus: " . $e->getMessage();
}

$conn->autocommit(TRUE);
header("Location: daftar_pembayaran.php?page=" . (int)$page);
exit;
?>