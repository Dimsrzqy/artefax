<?php
session_start();

// Verifikasi login
if (!isset($_SESSION['IDUser']) || empty($_SESSION['IDUser'])) {
    header("Location: ../../view/login.php");
    exit;
}

require_once __DIR__ . "/../../config/koneksi.php";
require_once __DIR__ . "/../../class/pembayaran.php";

$db = new Database();
$conn = $db->getConnection(); // Ini MySQLi

// Ambil ID dan halaman
$id   = $_GET['id'] ?? 0;
$page = $_GET['page'] ?? 1;

if (!$id || !is_numeric($id)) {
    $_SESSION['error_message'] = "ID pembayaran tidak valid!";
    header("Location: daftar_pembayaran.php?page=" . (int)$page);
    exit;
}

try {
    // Cek apakah data ada + ambil bukti sekaligus (MySQLi)
    $stmt = $conn->prepare("SELECT PbrBukti FROM pembayaran WHERE IDPembayaran = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $_SESSION['error_message'] = "Data pembayaran tidak ditemukan!";
        $stmt->close();
        header("Location: daftar_pembayaran.php?page=" . (int)$page);
        exit;
    }

    $row = $result->fetch_assoc();
    $bukti = $row['PbrBukti'] ?? '';
    $stmt->close();

    // --- Hapus file bukti jika ada ---
    if (!empty($bukti)) {
        $filename = basename($bukti); // Ambil nama file saja

        $possiblePaths = [
            __DIR__ . "/../../uploads/" . $filename,
            __DIR__ . "/../../uploads/pembayaran/" . $filename,
            $_SERVER['DOCUMENT_ROOT'] . "/artefax/uploads/" . $filename,
        ];

        foreach ($possiblePaths as $path) {
            if (file_exists($path) && is_file($path)) {
                @unlink($path);
                break;
            }
        }
    }

    // --- Hapus dari database (MySQLi) ---
    $deleteStmt = $conn->prepare("DELETE FROM pembayaran WHERE IDPembayaran = ?");
    $deleteStmt->bind_param("i", $id);
    
    if ($deleteStmt->execute()) {
        $_SESSION['success_message'] = "Pembayaran berhasil dihapus secara permanen!";
    } else {
        $_SESSION['error_message'] = "Gagal menghapus data dari database.";
    }
    $deleteStmt->close();

} catch (Exception $e) {
    $_SESSION['error_message'] = "Terjadi kesalahan: " . $e->getMessage();
}

// Kembali ke halaman yang benar
header("Location: daftar_pembayaran.php?page=" . (int)$page);
exit;
?>