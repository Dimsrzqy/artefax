<?php
session_start();
require_once __DIR__ . "/../../config/koneksi.php";
require_once __DIR__ . "/../../class/Pembayaran.php";
require_once __DIR__ . "/../../class/Booking.php";
require_once __DIR__ . "/../../class/User.php";

if (!isset($_GET['id'])) {
    header("Location: konfirmasi_pembayaran.php");
    exit;
}

$db = new Database();
$pembayaran = new Pembayaran($db->getConnection());
$bookingCls = new Booking($db->getConnection());
$user = new User($db->getConnection());

$data = $pembayaran->find($_GET['id']);
if (!$data) {
    $_SESSION['error'] = "Data tidak ditemukan.";
    header("Location: konfirmasi_pembayaran.php");
    exit;
}

$bookingData = $bookingCls->getById($data['IDBooking']);
$customer = $user->getById($bookingData['IDUser']);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Detail Pembayaran #<?= $data['IDPembayaran'] ?></title>
    <link href="../lib/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="../css/azia.css" rel="stylesheet">
    <style>
        .detail-section { margin-bottom: 30px; padding: 20px; background: #f8f9fa; border-radius: 10px; }
        .bukti-img { max-width: 100%; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); cursor: zoom-in; }
        .bukti-img:hover { opacity: 0.9; }
    </style>
</head>
<body class="az-body">
    <?php include '../includes/header.php'; ?>
    <div class="az-content pd-y-20">
        <div class="container">
            <div class="az-content-body">
                <a href="konfirmasi_pembayaran.php" class="btn btn-secondary mb-3">
                    <i class="fas fa-arrow-left"></i> Kembali
                </a>
                <h3>Detail Pembayaran</h3>

                <!-- Bukti Pembayaran -->
                <div class="detail-section">
                    <h5><i class="fas fa-image"></i> Bukti Transfer</h5>
                    <?php if ($data['PbrBukti'] && file_exists("../../uploads/bukti/" . $data['PbrBukti'])): ?>
                        <img src="../../uploads/bukti/<?= $data['PbrBukti'] ?>" class="bukti-img" onclick="window.open(this.src)">
                    <?php else: ?>
                        <p class="text-muted">Tidak ada bukti transfer.</p>
                    <?php endif; ?>
                </div>

                <!-- Info Booking -->
                <div class="detail-section">
                    <h5><i class="fas fa-book"></i> Informasi Booking</h5>
                    <p><strong>Nama:</strong> <?= htmlspecialchars($customer['UserNama']) ?></p>
                    <p><strong>Email:</strong> <?= htmlspecialchars($customer['UserEmail']) ?></p>
                    <p><strong>Jenis:</strong> <?= htmlspecialchars($bookingData['BkgJenis']) ?></p>
                    <p><strong>Paket:</strong> #<?= $bookingData['IDPaket'] ?></p>
                    <p><strong>Tanggal:</strong> <?= date('d M Y', strtotime($bookingData['BkgTglMulai'])) ?> - <?= date('d M Y', strtotime($bookingData['BkgTglSelesai'])) ?></p>
                    <p><strong>Total:</strong> Rp <?= number_format($bookingData['BkgTotalHarga'], 0, ',', '.') ?></p>
                </div>

                <!-- Info Pembayaran -->
                <div class="detail-section">
                    <h5><i class="fas fa-money-check-alt"></i> Rincian Pembayaran</h5>
                    <p><strong>Jumlah Transfer:</strong> Rp <?= number_format($data['PbrJumlah'], 0, ',', '.') ?></p>
                    <p><strong>Metode:</strong> <?= htmlspecialchars($data['PbrMetode']) ?></p>
                    <p><strong>Waktu Kirim:</strong> <?= date('d M Y H:i', strtotime($data['CreatedAt'])) ?> WIB</p>
                    <p><strong>Status:</strong> <span class="badge badge-<?= strtolower($data['PbrStatus']) ?>"><?= $data['PbrStatus'] ?></span></p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>