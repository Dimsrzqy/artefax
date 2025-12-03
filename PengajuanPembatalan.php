<?php
// File: PengajuanPembatalan.php - Final Code (Mengubah PbrJumlah saat Refund > 0)

session_start();
date_default_timezone_set('Asia/Jakarta');

if (!isset($_SESSION['user']) || strtolower($_SESSION['user']['UserRole'] ?? '') !== 'customer') {
    header("Location: view/login.php"); 
    exit;
}

// PERBAIKAN PATH DI BARIS INI: Menggunakan path relatif yang lebih aman dan konsisten.
// Diasumsikan file config berada di root proyek (Artefax/config/koneksi.php)
require_once __DIR__ . '/config/koneksi.php'; 

$db = new Database();
$conn = $db->getConnection();
if (!$conn) die("<div class='alert alert-danger'>Koneksi database gagal.</div>");

$idBooking = (int)($_GET['id'] ?? 0);
$idUser = $_SESSION['user']['IDUser'];

if ($idBooking === 0) {
    die("<div class='alert alert-warning'>ID Booking tidak valid.</div>");
}

// 1. Ambil Detail Booking & Pembayaran
$stmt = $conn->prepare("
    SELECT 
        b.BkgTglMulai, 
        b.BkgTotalHarga, 
        p.IDPembayaran,
        p.PbrJumlah /* Ambil PbrJumlah asli */
    FROM booking b
    LEFT JOIN pembayaran p ON b.IDBooking = p.IDBooking 
    WHERE b.IDBooking = ? AND b.IDUser = ? 
      AND b.BkgStatus IN ('Diterima', 'Pending') 
");
$stmt->bind_param("ii", $idBooking, $idUser);
$stmt->execute();
$result = $stmt->get_result();
$bookingData = $result->fetch_assoc();
$stmt->close();

if (!$bookingData) {
    die("<div class='alert alert-danger'>Booking tidak ditemukan, bukan milik Anda, atau statusnya sudah selesai/dibatalkan.</div>");
}

$tanggalMulai = new DateTime($bookingData['BkgTglMulai']);
// Gunakan PbrJumlah dari tabel pembayaran sebagai total harga yang akan dihitung refund-nya
$totalHargaAwal = $bookingData['PbrJumlah']; 
$idPembayaran = $bookingData['IDPembayaran'];
$today = new DateTime();
$interval = $today->diff($tanggalMulai);
$daysDifference = (int)$interval->format('%r%a');

// 2. Hitung Persentase Refund
$refundPercentage = 0;
$cancellationAllowed = false;

if ($daysDifference >= 5) {
    $refundPercentage = 100;
    $cancellationAllowed = true;
    $infoText = "Pengajuan H-". $daysDifference . ". Anda berhak atas pengembalian 100% dari total harga.";
} elseif ($daysDifference >= 3) {
    $refundPercentage = 50;
    $cancellationAllowed = true;
    $infoText = "Pengajuan H-". $daysDifference . ". Anda berhak atas pengembalian 50% dari total harga.";
} elseif ($daysDifference >= 1) {
    $refundPercentage = 0;
    $cancellationAllowed = true;
    $infoText = "Pengajuan H-". $daysDifference . ". Pembatalan diizinkan, tetapi tidak ada pengembalian dana (0%).";
} else {
    $refundPercentage = 0;
    $cancellationAllowed = false;
    $infoText = "Pembatalan tidak diizinkan. Tanggal event sudah terlalu dekat atau sudah lewat.";
}

$refundAmount = $totalHargaAwal * ($refundPercentage / 100);
$pendapatanBersihBaru = $totalHargaAwal - $refundAmount;

// 3. Proses Pengajuan Refund
$success = $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_cancellation'])) {
    if (!$cancellationAllowed) {
        $error = "Pembatalan tidak dapat diproses karena tanggal event sudah terlalu dekat.";
    } elseif (empty($_POST['alasan'])) {
        $error = "Alasan pembatalan wajib diisi.";
    } elseif (empty($_FILES['bukti']['name'])) {
        $error = "Bukti pembatalan wajib diunggah.";
    } elseif (!$idPembayaran) {
        $error = "Data pembayaran untuk booking ini tidak ditemukan. Pengajuan gagal.";
    } else {
        $alasan = $conn->real_escape_string($_POST['alasan']);
        $refundJumlah = $refundAmount;

        // TENTUKAN PATH UPLOAD YANG BENAR & CHECK DIREKTORI
        $targetDir = __DIR__ . "/../../uploads/refund_bukti/";
        
        if (!is_dir($targetDir)) {
             if (!mkdir($targetDir, 0777, true)) {
                 $error = "Gagal membuat folder upload. Mohon periksa izin direktori.";
                 $uploadOk = 0;
             }
        }

        if (empty($error)) {
            $fileName = $idBooking . '_' . time() . '_' . basename($_FILES["bukti"]["name"]);
            $targetFile = $targetDir . $fileName;
            $uploadOk = 1;
            $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));

            if ($_FILES["bukti"]["size"] > 5000000) { 
                $error = "Ukuran file terlalu besar.";
                $uploadOk = 0;
            } elseif (!in_array($imageFileType, ['jpg', 'png', 'jpeg', 'pdf'])) {
                $error = "Hanya file JPG, JPEG, PNG, & PDF yang diizinkan.";
                $uploadOk = 0;
            }

            if ($uploadOk == 1 && move_uploaded_file($_FILES["bukti"]["tmp_name"], $targetFile)) {
                
                // 3a. Masukkan data ke tabel refund
                $queryInsert = "INSERT INTO refund (RefundJumlah, RefundWaktu, RefundAlasan, RefundStatus, IDUser, IDBooking, IDPembayaran, RefundBukti) 
                                VALUES (?, NOW(), ?, 'Pending', ?, ?, ?, ?)";
                
                $stmtInsert = $conn->prepare($queryInsert);
                $stmtInsert->bind_param("dsiiii", $refundJumlah, $alasan, $idUser, $idBooking, $idPembayaran, $fileName);
                
                if ($stmtInsert->execute()) {
                    
                    // 3b. UPDATE PENDAPATAN BERSIH di tabel 'pembayaran' (Sesuai permintaan Anda)
                    if ($refundAmount > 0) {
                        // **PERHATIAN: INI MENGUBAH DATA TRANSAKSI ASLI.**
                        $conn->query("UPDATE pembayaran SET PbrJumlah = $pendapatanBersihBaru WHERE IDPembayaran = $idPembayaran");
                        
                        // Opsional: Update BkgTotalHarga di tabel booking juga (agar laporan booking konsisten)
                        $conn->query("UPDATE booking SET BkgTotalHarga = $pendapatanBersihBaru WHERE IDBooking = $idBooking");
                    }

                    // 3c. Update status booking menjadi 'Pending'
                    if ($conn->query("UPDATE booking SET BkgStatus = 'Pending' WHERE IDBooking = $idBooking")) {
                        $_SESSION['success_message'] = "Pengajuan Pembatalan Booking #{$idBooking} berhasil diajukan. Status dan Nominal Pembayaran diubah.";
                    } else {
                        $_SESSION['success_message'] = "Pengajuan Pembatalan Booking #{$idBooking} berhasil diajukan, namun gagal update status booking.";
                    }

                    header("Location: RiwayatBooking.php"); // Redirect kembali ke Riwayat Booking
                    exit;
                } else {
                    $error = "Gagal menyimpan data pengajuan refund ke database: " . $conn->error;
                }
                $stmtInsert->close();
            } elseif ($uploadOk == 1) {
                $error = "Gagal mengunggah file bukti.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Pengajuan Pembatalan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel-stylesheet>
    <style>
        :root { --primary-blue: #5c99ee; --soft-blue: #f4f7fc; }
        body { background: var(--soft-blue); font-family: 'Roboto', sans-serif; }
        .card-cancellation { border-radius: 15px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
        .header-cancellation { background-color: var(--primary-blue); color: white; border-radius: 15px 15px 0 0; padding: 20px; }
        .info-box { background-color: #ffe0b2; color: #e65100; padding: 15px; border-radius: 8px; margin-bottom: 20px; font-weight: 500; }
        .summary-box { border: 2px dashed #ccc; padding: 15px; border-radius: 8px; margin-top: 20px; }
        .text-main { color: var(--primary-blue); font-weight: 700; }
        /* Perbaikan untuk alert Bootstrap */
        .alert-success { background-color: #d1e7dd; color: #0f5132; border-color: #badbcc; }
        .alert-danger { background-color: #f8d7da; color: #842029; border-color: #f5c6cb; }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card card-cancellation">
                    <div class="header-cancellation">
                        <h3 class="mb-0"><i class="bi bi-x-octagon-fill me-2"></i> Pengajuan Pembatalan Booking</h3>
                        <p class="mb-0 mt-2">Booking ID: **<?= $idBooking ?>** | Tanggal Mulai: **<?= $tanggalMulai->format('d F Y H:i') ?>**</p>
                    </div>
                    <div class="card-body p-4">
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success"><i class="bi bi-check-circle-fill"></i> **Sukses!** <?= $success ?></div>
                        <?php endif; ?>
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><i class="bi bi-exclamation-triangle-fill"></i> **Gagal!** <?= $error ?></div>
                        <?php endif; ?>

                        <div class="info-box bg-warning-subtle text-warning-emphasis">
                            <i class="bi bi-info-circle-fill me-2"></i> <?= $infoText ?>
                        </div>

                        <?php if ($cancellationAllowed): ?>
                        <form method="POST" enctype="multipart/form-data">
                            
                            <div class="summary-box">
                                <p class="text-muted mb-1">Total Harga Awal: <span class="float-end text-main">Rp<?= number_format($totalHargaAwal, 0, ',', '.') ?></span></p>
                                <p class="text-muted mb-1">Persentase Pengembalian: <span class="float-end text-main"><?= $refundPercentage ?>%</span></p>
                                <hr>
                                <h5 class="mb-0">Jumlah Refund Diajukan: <span class="float-end text-success">Rp<?= number_format($refundAmount, 0, ',', '.') ?></span></h5>
                                <?php if ($refundAmount > 0): ?>
                                    <p class="mt-2 small text-danger">⚠️ PERINGATAN: Jika disetujui, nominal pembayaran akan di-*update* di database menjadi **Rp<?= number_format($pendapatanBersihBaru, 0, ',', '.') ?>** agar laporan keuangan mencerminkan pendapatan bersih.</p>
                                <?php endif; ?>
                            </div>

                            <div class="mt-4">
                                <label for="alasan" class="form-label text-main">Alasan Pembatalan (Wajib)</label>
                                <textarea name="alasan" id="alasan" rows="4" class="form-control" required placeholder="Jelaskan alasan Anda mengajukan pembatalan secara detail."></textarea>
                            </div>

                            <div class="mt-3">
                                <label for="bukti" class="form-label text-main">Upload Bukti Pembatalan (Wajib)</label>
                                <input type="file" name="bukti" id="bukti" class="form-control" accept=".jpg,.jpeg,.png,.pdf" required>
                                <small class="text-muted">Max 5MB (JPG, PNG, atau PDF)</small>
                            </div>

                            <div class="d-flex justify-content-between mt-4">
                                <a href="RiwayatBooking.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Kembali</a>
                                <button type="submit" name="submit_cancellation" class="btn btn-danger"><i class="bi bi-send-fill"></i> Ajukan Pembatalan</button>
                            </div>
                        </form>
                        <?php else: ?>
                        <div class="text-center py-4">
                            <h4 class="text-danger">Pembatalan Tidak Dapat Diproses.</h4>
                            <p class="text-muted">Silakan hubungi admin jika Anda memiliki pertanyaan.</p>
                            <a href="RiwayatBooking.php" class="btn btn-secondary mt-3"><i class="bi bi-arrow-left"></i> Kembali</a>
                        </div>
                        <?php endif; ?>

                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>