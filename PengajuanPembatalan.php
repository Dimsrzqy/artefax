<?php
// File: PengajuanPembatalan.php - Final Code (Hanya update PbrJumlah di tabel pembayaran, BkgTotalHarga di booking TIDAK diubah)

session_start();
date_default_timezone_set('Asia/Jakarta');

if (!isset($_SESSION['user']) || strtolower($_SESSION['user']['UserRole'] ?? '') !== 'customer') {
    header("Location: view/login.php"); 
    exit;
}

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
        b.BkgStatus,
        p.IDPembayaran,
        p.PbrJumlah
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
$totalHargaAwal = $bookingData['PbrJumlah']; 
$idPembayaran = $bookingData['IDPembayaran'];
$currentStatus = $bookingData['BkgStatus'];
$today = new DateTime('today');

$interval = $today->diff($tanggalMulai);
$daysLeft = $interval->days;

if ($tanggalMulai < $today) {
    $daysLeft = 0;
}

$refundPercentage = 0;
$cancellationAllowed = false;
$infoText = "";

if ($daysLeft >= 5) {
    $refundPercentage = 100;
    $cancellationAllowed = true;
    $infoText = "Pengajuan lebih dari 5 hari sebelum acara. Anda berhak atas pengembalian 100% dari total harga.";
} elseif ($daysLeft >= 3) {
    $refundPercentage = 50;
    $cancellationAllowed = true;
    $infoText = "Pengajuan 3–4 hari sebelum acara. Anda berhak atas pengembalian 50% dari total harga.";
} elseif ($daysLeft >= 1) {
    $refundPercentage = 0;
    $cancellationAllowed = true;
    $infoText = "Pengajuan 1–2 hari sebelum acara. Pembatalan diizinkan, tetapi tidak ada pengembalian dana (0%).";
} else {
    $refundPercentage = 0;
    $cancellationAllowed = false;
    $infoText = "Pembatalan tidak diizinkan. Acara kurang dari 1 hari lagi atau sudah lewat.";
}

$statusColorClass = $currentStatus === 'Pending' ? 'text-warning' : 'text-success';

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

        $targetDir = __DIR__ . "/uploads/refund_bukti/";
        if (!is_dir($targetDir)) {
             if (!mkdir($targetDir, 0777, true)) {
                 $error = "Gagal membuat folder upload. Mohon periksa izin direktori.";
             }
        }

        if (empty($error)) {
            $imageFileType = strtolower(pathinfo($_FILES["bukti"]["name"], PATHINFO_EXTENSION));
            $uniqueId = uniqid(date('YmdHis'));
            $fileName = "refund_BKG{$idBooking}_{$uniqueId}.{$imageFileType}"; 
            $targetFile = $targetDir . $fileName;
            $uploadOk = 1;

            if ($_FILES["bukti"]["size"] > 5000000) { 
                $error = "Ukuran file terlalu besar.";
                $uploadOk = 0;
            } elseif (!in_array($imageFileType, ['jpg', 'png', 'jpeg', 'pdf'])) {
                $error = "Hanya file JPG, JPEG, PNG, & PDF yang diizinkan.";
                $uploadOk = 0;
            }

            if ($uploadOk == 1 && move_uploaded_file($_FILES["bukti"]["tmp_name"], $targetFile)) {
                
                $queryInsert = "INSERT INTO refund (RefundJumlah, RefundWaktu, RefundAlasan, RefundStatus, IDUser, IDBooking, IDPembayaran, RefundBukti) 
                                 VALUES (?, NOW(), ?, 'Pending', ?, ?, ?, ?)";
                
                $stmtInsert = $conn->prepare($queryInsert);
                $stmtInsert->bind_param("dsiiis", $refundJumlah, $alasan, $idUser, $idBooking, $idPembayaran, $fileName);
                
                if ($stmtInsert->execute()) {
                    // HANYA UPDATE TABEL pembayaran (BkgTotalHarga di booking TIDAK DIUBAH)
                    if ($refundAmount > 0) {
                        $conn->query("UPDATE pembayaran SET PbrJumlah = $pendapatanBersihBaru WHERE IDPembayaran = $idPembayaran");
                        // Dihapus baris ini: UPDATE booking SET BkgTotalHarga = ...
                    }

                    // Update status booking tetap jadi 'Pending'
                    if ($conn->query("UPDATE booking SET BkgStatus = 'Pending' WHERE IDBooking = $idBooking")) {
                        $_SESSION['success_message'] = "Pengajuan Pembatalan Booking #{$idBooking} berhasil diajukan.";
                    } else {
                        $_SESSION['success_message'] = "Pengajuan berhasil, namun gagal update status booking.";
                    }

                    header("Location: RiwayatBooking.php");
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root { --primary-blue: #5c99ee; --soft-blue: #f4f7fc; }
        body { background: var(--soft-blue); font-family: 'Roboto', sans-serif; }
        .card-cancellation { border-radius: 15px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
        .header-cancellation { background-color: var(--primary-blue); color: white; border-radius: 15px 15px 0 0; padding: 20px; }
        .info-box { background-color: #ffe0b2; color: #e65100; padding: 15px; border-radius: 8px; margin-bottom: 20px; font-weight: 500; }
        .summary-box { border: 2px dashed #ccc; padding: 15px; border-radius: 8px; margin-top: 20px; }
        .text-main { color: var(--primary-blue); font-weight: 700; }
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
                        <h3 class="mb-0">Pengajuan Pembatalan Booking</h3>
                        <p class="mb-0 mt-2">
                            Booking ID: **<?= $idBooking ?>** | Tanggal Mulai: **<?= $tanggalMulai->format('d F Y H:i') ?>** | Status: <strong class="<?= $statusColorClass ?>"><?= htmlspecialchars($currentStatus) ?></strong> 
                        </p>
                    </div>
                    <div class="card-body p-4">
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success">Sukses! <?= $success ?></div>
                        <?php endif; ?>
                        <?php if ($error): ?>
                            <div class="alert alert-danger">Gagal! <?= $error ?></div>
                        <?php endif; ?>

                        <div class="info-box bg-warning-subtle text-warning-emphasis">
                            <i class="bi bi-info-circle-fill me-2"></i> <?= $infoText ?>
                            <hr class="my-2">
                            <small class="d-block mt-2">Waktu pengajuan saat ini: <span id="local-time" class="fw-bold">...</span></small>
                        </div>

                        <?php if ($cancellationAllowed): ?>
                        <form method="POST" enctype="multipart/form-data">
                            
                            <div class="summary-box">
                                <p class="text-muted mb-1">Total Harga Awal: <span class="float-end text-main">Rp<?= number_format($totalHargaAwal, 0, ',', '.') ?></span></p>
                                <p class="text-muted mb-1">Persentase Pengembalian: <span class="float-end text-main"><?= $refundPercentage ?>%</span></p>
                                <hr>
                                <h5 class="mb-0">Jumlah Refund Diajukan: <span class="float-end text-success">Rp<?= number_format($refundAmount, 0, ',', '.') ?></span></h5>
                                <?php if ($refundAmount > 0): ?>
                                    <p class="mt-2 small text-danger">PERINGATAN: Jika disetujui, nominal yang diterima sejumlah Rp<?= number_format($pendapatanBersihBaru, 0, ',', '.') ?>**</p>
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
                                <a href="RiwayatBooking.php" class="btn btn-outline-secondary">Kembali</a>
                                <button type="submit" name="submit_cancellation" class="btn btn-danger">Ajukan Pembatalan</button>
                            </div>
                        </form>
                        <?php else: ?>
                        <div class="text-center py-4">
                            <h4 class="text-danger">Pembatalan Tidak Dapat Diproses.</h4>
                            <p class="text-muted">Silakan hubungi admin jika Anda memiliki pertanyaan.</p>
                            <a href="RiwayatBooking.php" class="btn btn-secondary mt-3">Kembali</a>
                        </div>
                        <?php endif; ?>

                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function updateLocalTime() {
            const now = new Date(); 
            const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false, timeZoneName: 'short' };
            document.getElementById('local-time').textContent = now.toLocaleDateString('id-ID', options);
        }
        updateLocalTime();
        setInterval(updateLocalTime, 1000); 
    </script>
</body>
</html>