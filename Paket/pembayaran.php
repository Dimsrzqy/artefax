<?php
session_start();
include __DIR__ . '/../config/koneksi.php';

// Validasi: Harus ada session user
if (!isset($_SESSION['user'])) {
    $_SESSION['error'] = "Silakan login terlebih dahulu.";
    header('Location: ../view/login.php');
    exit;
}

// Ambil user data
$userId = $_SESSION['user']['IDUser'] ?? $_SESSION['user']['id'] ?? null;
$userName = $_SESSION['user']['UserNama'] ?? $_SESSION['user']['nama'] ?? $_SESSION['user']['username'] ?? 'Pengunjung';

// Ambil ID Booking
$idBooking = $_GET['id'] ?? $_SESSION['current_booking_id'] ?? null;

if (!$idBooking || !is_numeric($idBooking)) {
    die("
        <div style='text-align:center; padding:50px; font-family:Arial;'>
            <h2>Booking Tidak Ditemukan</h2>
            <p>ID Booking tidak valid. Silakan ulangi proses checkout.</p>
            <a href='checkout.php' style='padding:10px 20px; background:#007bff; color:white; text-decoration:none; border-radius:5px;'>Kembali ke Checkout</a>
        </div>
    ");
}

// Simpan ke session
$_SESSION['current_booking_id'] = $idBooking;

$db = new Database();
$conn = $db->getConnection();

// Ambil data booking
$stmtBooking = $conn->prepare("SELECT * FROM booking WHERE IDBooking = ? AND IDUser = ?");
$stmtBooking->bind_param("ii", $idBooking, $userId);
$stmtBooking->execute();
$booking = $stmtBooking->get_result()->fetch_assoc();
$stmtBooking->close();

if (!$booking) {
    $_SESSION['error'] = "Booking tidak ditemukan atau bukan milik Anda.";
    header('Location: shop.php');
    exit;
}

// Cek status pembayaran
$pembayaran = null;
$stmtPembayaran = $conn->prepare("SELECT * FROM pembayaran WHERE IDBooking = ?");
$stmtPembayaran->bind_param("i", $idBooking);
$stmtPembayaran->execute();
$resPembayaran = $stmtPembayaran->get_result();
if ($resPembayaran->num_rows > 0) {
    $pembayaran = $resPembayaran->fetch_assoc();
}
$stmtPembayaran->close();

// PROSES UPLOAD BUKTI PEMBAYARAN
$uploadSuccess = false;
$uploadError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['bukti_tf'])) {
    $metode = $_POST['metode_pembayaran'] ?? '';
    $keterangan = $_POST['keterangan'] ?? ''; // DP atau LUNAS
    $file = $_FILES['bukti_tf'];
    
    if (empty($metode) || empty($keterangan)) {
        $uploadError = "Pilih metode pembayaran dan keterangan terlebih dahulu.";
    } elseif ($file['error'] !== UPLOAD_ERR_OK) {
        $uploadError = "Gagal mengupload file. Error code: " . $file['error'];
    } else {
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];
        $maxSize = 5 * 1024 * 1024; // 5MB
        
        if (!in_array($file['type'], $allowedTypes)) {
            $uploadError = "Format file tidak didukung. Gunakan JPG, PNG, atau PDF.";
        } elseif ($file['size'] > $maxSize) {
            $uploadError = "Ukuran file terlalu besar. Maksimal 5MB.";
        } else {
            $uploadDir = __DIR__ . '/../uploads/bukti_pembayaran/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $newFileName = 'bukti_' . $idBooking . '_' . time() . '.' . $ext;
            $uploadPath = $uploadDir . $newFileName;
            
            if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                // Hitung jumlah bayar
                $totalBooking = $booking['BkgTotalHarga'];
                $jumlahBayar = ($keterangan === 'DP') ? ($totalBooking * 0.5) : $totalBooking;

                try {
                    $conn->begin_transaction();

                    if ($pembayaran) {
                        // UPDATE data pembayaran yang sudah ada
                        $stmt = $conn->prepare("UPDATE pembayaran SET 
                            PbrMetode = ?, 
                            PbrKeterangan = ?, 
                            PbrJumlah = ?, 
                            PbrBukti = ?, 
                            PbrStatus = 'Pending', 
                            PbrConfirmed = 0, 
                            UpdatedAt = CURRENT_TIMESTAMP 
                            WHERE IDBooking = ?");
                        $stmt->bind_param("ssdsi", $metode, $keterangan, $jumlahBayar, $newFileName, $idBooking);
                    } else {
                        // INSERT data pembayaran baru
                        $stmt = $conn->prepare("INSERT INTO pembayaran 
                            (IDBooking, PbrMetode, PbrKeterangan, PbrJumlah, PbrBukti, PbrStatus, PbrConfirmed, CreatedAt) 
                            VALUES (?, ?, ?, ?, ?, 'Pending', 0, CURRENT_TIMESTAMP)");
                        $stmt->bind_param("issds", $idBooking, $metode, $keterangan, $jumlahBayar, $newFileName);
                    }

                    if ($stmt->execute()) {
                        $stmt->close();

                        // Update status booking tetap 'Pending' (menunggu konfirmasi admin)
                        $updateBooking = $conn->prepare("UPDATE booking SET BkgStatus = 'Pending', UpdatedAt = NOW() WHERE IDBooking = ?");
                        $updateBooking->bind_param("i", $idBooking);
                        $updateBooking->execute();
                        $updateBooking->close();

                        $conn->commit();

                        // ✅ BARU KOSONGKAN CART SETELAH UPLOAD BERHASIL
                        unset($_SESSION['cart']);
                        unset($_SESSION['checkout_success']);

                        $uploadSuccess = true;
                        $_SESSION['success_pembayaran'] = "Bukti pembayaran berhasil diupload! Menunggu verifikasi admin.";

                        // Reload data pembayaran
                        $stmtReload = $conn->prepare("SELECT * FROM pembayaran WHERE IDBooking = ?");
                        $stmtReload->bind_param("i", $idBooking);
                        $stmtReload->execute();
                        $pembayaran = $stmtReload->get_result()->fetch_assoc();
                        $stmtReload->close();

                    } else {
                        $conn->rollback();
                        $uploadError = "Gagal menyimpan ke database: " . $stmt->error;
                        $stmt->close();
                    }

                } catch (Exception $e) {
                    $conn->rollback();
                    $uploadError = "Error: " . $e->getMessage();
                }

            } else {
                $uploadError = "Gagal memindahkan file bukti transfer.";
            }
        }
    }
}

function rupiah($n) { 
    return 'Rp ' . number_format((float)$n, 0, ',', '.'); 
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Upload Bukti Pembayaran - Artefax</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600&family=Raleway:wght@600;800&display=swap" rel="stylesheet"> 
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css"/>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

<!-- Navbar -->
<div class="container-fluid fixed-top">
    <div class="container px-0">
        <nav class="navbar navbar-light bg-white navbar-expand-xl">
            <a href="../index.php" class="navbar-brand">
                <h1 class="text-primary display-6">Artefax</h1>
            </a>
            <button class="navbar-toggler py-2 px-3" type="button" data-bs-toggle="collapse" data-bs-target="#navbarCollapse">
                <span class="fa fa-bars text-primary"></span>
            </button>
            <div class="collapse navbar-collapse bg-white" id="navbarCollapse">
                <div class="navbar-nav mx-auto">
                    <a href="../index.php" class="nav-item nav-link">Home</a>
                    <a href="Services.php" class="nav-item nav-link">Services</a>
                    <a href="shop.php" class="nav-item nav-link">Shop</a>
                    <a href="checkout.php" class="nav-item nav-link">Checkout</a>
                    <a href="contact.php" class="nav-item nav-link">Contact</a>
                </div>
                <div class="d-flex m-3 me-0">
                    <a href="riwayat_booking.php" class="my-auto me-3">
                        <i class="fas fa-history fa-2x"></i>
                    </a>
                    <a href="#" class="my-auto"><i class="fas fa-user fa-2x"></i></a>
                </div>
            </div>
        </nav>
    </div>
</div>

<div class="container py-5" style="margin-top:100px;">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><i class="fas fa-upload me-2"></i> Upload Bukti Pembayaran</h4>
                </div>
                <div class="card-body p-4">
                    
                    <?php if ($uploadSuccess): ?>
                    <script>
                       Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            html: '<p>Bukti pembayaran telah berhasil diupload.</p><p class="text-warning"><strong>Status: Menunggu Konfirmasi Admin</strong></p><p>Anda akan menerima notifikasi setelah admin memverifikasi pembayaran Anda.</p>',
                            confirmButtonText: 'Lihat Riwayat Booking'
                        }).then(() => {
                            // LANGSUNG KE RIWAYAT BOOKING YANG SUDAH ADA DI FOLDER UTAMA
                            window.location.href = '../RiwayatBooking.php';
                        });
                    </script>
                    <?php endif; ?>

                    <?php if (!empty($uploadError)): ?>
                        <div class="alert alert-danger alert-dismissible">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <?= htmlspecialchars($uploadError) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Info Booking -->
                    <div class="alert alert-info mb-4">
                        <h5><i class="fas fa-info-circle me-2"></i> Informasi Pembayaran</h5>
                        <table class="table table-sm table-borderless mb-0">
                            <tr>
                                <td width="150"><strong>ID Booking:</strong></td>
                                <td>#<?= str_pad($idBooking, 6, '0', STR_PAD_LEFT) ?></td>
                            </tr>
                            <tr>
                                <td><strong>Nama:</strong></td>
                                <td><?= htmlspecialchars($userName) ?></td>
                            </tr>
                            <tr>
                                <td><strong>Total Pesanan:</strong></td>
                                <td class="text-primary fw-bold"><?= rupiah($booking['BkgTotalHarga']) ?></td>
                            </tr>
                            <tr>
                                <td><strong>Status Booking:</strong></td>
                                <td>
                                    <span class="badge bg-warning text-dark"><?= htmlspecialchars($booking['BkgStatus']) ?></span>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <!-- Rekening Tujuan -->
                    <div class="bg-light p-3 rounded mb-4">
                        <h5><i class="fas fa-university me-2"></i> Rekening Tujuan Transfer</h5>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="border p-3 rounded bg-white">
                                    <strong class="text-primary">Bank BCA</strong><br>
                                    <h4 class="my-2">1234567890</h4>
                                    <small>a.n. Artefax Indonesia</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="border p-3 rounded bg-white">
                                    <strong class="text-primary">Bank BRI</strong><br>
                                    <h4 class="my-2">0987654321</h4>
                                    <small>a.n. Artefax Indonesia</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Status Pembayaran -->
                    <?php if ($pembayaran): ?>
                        <?php if ($pembayaran['PbrStatus'] === 'Pending'): ?>
                            <div class="alert alert-warning">
                                <h5><i class="fas fa-clock me-2"></i> Status: Menunggu Konfirmasi Admin</h5>
                                <p class="mb-2">Bukti pembayaran Anda sudah diupload dan sedang dalam proses verifikasi oleh admin.</p>
                                <hr>
                                <p class="mb-1"><strong>Detail Pembayaran:</strong></p>
                                <ul class="mb-0">
                                    <li>Metode: <?= htmlspecialchars($pembayaran['PbrMetode']) ?></li>
                                    <li>Jenis: <?= htmlspecialchars($pembayaran['PbrKeterangan']) ?></li>
                                    <li>Jumlah: <?= rupiah($pembayaran['PbrJumlah']) ?></li>
                                    <li>File: <?= htmlspecialchars($pembayaran['PbrBukti']) ?></li>
                                    <li>Waktu Upload: <?= date('d M Y H:i', strtotime($pembayaran['CreatedAt'])) ?> WIB</li>
                                </ul>
                            </div>
                            <div class="text-center mt-3">
                                <a href="../RiwayatBooking.php" class="btn btn-primary btn-lg">
                                    <i class="fas fa-history me-2"></i> Lihat Riwayat Booking
                                </a>
                            </div>
                        <?php elseif ($pembayaran['PbrStatus'] === 'Lunas' || $pembayaran['PbrStatus'] === 'Lunas DP'): ?>
                            <div class="alert alert-success">
                                <h5><i class="fas fa-check-circle me-2"></i> Pembayaran Terverifikasi!</h5>
                                <p>Pembayaran Anda sudah diverifikasi oleh admin.</p>
                                <p class="mb-0"><strong>Status:</strong> <?= htmlspecialchars($pembayaran['PbrStatus']) ?></p>
                            </div>
                            <div class="text-center mt-3">
                                <a href="riwayat_booking.php" class="btn btn-success btn-lg">
                                    <i class="fas fa-check me-2"></i> Lihat Detail Booking
                                </a>
                            </div>
                        <?php elseif ($pembayaran['PbrStatus'] === 'Gagal'): ?>
                            <div class="alert alert-danger">
                                <h5><i class="fas fa-times-circle me-2"></i> Pembayaran Ditolak</h5>
                                <p>Bukti pembayaran Anda ditolak oleh admin. Silakan upload ulang bukti pembayaran yang valid.</p>
                            </div>
                            <?php $showForm = true; ?>
                        <?php endif; ?>
                    <?php else: ?>
                        <?php $showForm = true; ?>
                    <?php endif; ?>

                    <!-- Form Upload (hanya tampil jika belum upload atau ditolak) -->
                    <?php if (isset($showForm) && $showForm): ?>
                        <form method="POST" enctype="multipart/form-data" id="formUploadBukti">
                            <div class="mb-3">
                                <label class="form-label">Metode Pembayaran <span class="text-danger">*</span></label>
                                <select name="metode_pembayaran" class="form-select" required>
                                    <option value="">-- Pilih Bank Tujuan --</option>
                                    <option value="BRI">Transfer BRI</option>
                                    <option value="BCA">Transfer BCA</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Jenis Pembayaran <span class="text-danger">*</span></label>
                                <select name="keterangan" class="form-select" id="keteranganSelect" required>
                                    <option value="">-- Pilih Jenis Pembayaran --</option>
                                    <option value="DP">DP 50% (Sisa dibayar saat acara)</option>
                                    <option value="LUNAS">Lunas</option>
                                </select>
                            </div>

                            <div class="alert alert-info" id="infoJumlahBayar">
                                <strong>Jumlah yang harus dibayar:</strong> <span id="displayJumlah" class="fs-5 text-danger">-</span>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Upload Bukti Transfer <span class="text-danger">*</span></label>
                                <input type="file" name="bukti_tf" class="form-control" accept="image/jpeg,image/jpg,image/png,application/pdf" required>
                                <small class="text-muted">Format: JPG, PNG, atau PDF. Maksimal 5MB.</small>
                            </div>

                            <div class="alert alert-warning">
                                <strong><i class="fas fa-info-circle me-2"></i>Penting:</strong>
                                <ul class="mb-0">
                                    <li>Pastikan bukti transfer jelas dan terbaca</li>
                                    <li>Nama pengirim harus sesuai dengan nama akun Anda</li>
                                    <li>Pembayaran akan diverifikasi dalam 1x24 jam</li>
                                    <li>Status booking akan berubah menjadi "Diterima" setelah verifikasi berhasil</li>
                                </ul>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-upload me-2"></i> Kirim Bukti Pembayaran
                                </button>
                                <a href="shop.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left me-2"></i> Kembali ke Shop
                                </a>
                            </div>
                        </form>

                        <script>
                        // Update jumlah bayar dinamis
                        const totalBooking = <?= $booking['BkgTotalHarga'] ?>;
                        document.getElementById('keteranganSelect').addEventListener('change', function() {
                            const keterangan = this.value;
                            const displayEl = document.getElementById('displayJumlah');
                            
                            if (keterangan === 'DP') {
                                const dpAmount = totalBooking * 0.5;
                                displayEl.textContent = 'Rp ' + dpAmount.toLocaleString('id-ID');
                            } else if (keterangan === 'LUNAS') {
                                displayEl.textContent = 'Rp ' + totalBooking.toLocaleString('id-ID');
                            } else {
                                displayEl.textContent = '-';
                            }
                        });
                        </script>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>