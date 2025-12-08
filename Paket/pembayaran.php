<?php
session_start();
include __DIR__ . '/../config/koneksi.php';

// Validasi Login
if (!isset($_SESSION['user'])) {
    $_SESSION['error'] = "Silakan login terlebih dahulu.";
    header('Location: ../view/login.php');
    exit;
}

// Ambil Data User & Booking
$userId = $_SESSION['user']['IDUser'] ?? $_SESSION['user']['id'] ?? null;
$userName = $_SESSION['user']['UserNama'] ?? $_SESSION['user']['nama'] ?? 'Pengunjung';
$idBooking = $_GET['id'] ?? $_SESSION['current_booking_id'] ?? null;

if (!$idBooking || !is_numeric($idBooking)) {
    echo "<script>alert('ID Booking tidak valid'); window.location='shop.php';</script>";
    exit;
}
$_SESSION['current_booking_id'] = $idBooking;

$db = new Database();
$conn = $db->getConnection();

// Cek Booking
$stmtBooking = $conn->prepare("SELECT * FROM booking WHERE IDBooking = ? AND IDUser = ?");
$stmtBooking->bind_param("ii", $idBooking, $userId);
$stmtBooking->execute();
$booking = $stmtBooking->get_result()->fetch_assoc();
$stmtBooking->close();

if (!$booking) {
    echo "<script>alert('Booking tidak ditemukan'); window.location='shop.php';</script>";
    exit;
}

$jenisBayarOtomatis = $_SESSION['checkout_payment'] ?? 'DP';

// Cek Pembayaran Existing
$pembayaran = null;
$stmtCek = $conn->prepare("SELECT * FROM pembayaran WHERE IDBooking = ?");
$stmtCek->bind_param("i", $idBooking);
$stmtCek->execute();
$resCek = $stmtCek->get_result();
if($resCek->num_rows > 0) $pembayaran = $resCek->fetch_assoc();
$stmtCek->close();

// Logic Upload
$uploadSuccess = false; $uploadError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['bukti_tf'])) {
    $metode = $_POST['metode_pembayaran'] ?? '';
    $keterangan = $_POST['keterangan'] ?? ''; 
    $file = $_FILES['bukti_tf'];

    if(empty($metode)){
        $uploadError = "Pilih Bank Tujuan Transfer.";
    } elseif ($file['error'] !== UPLOAD_ERR_OK) {
        $uploadError = "Gagal upload file.";
    } else {
        $allowed = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];
        if(!in_array($file['type'], $allowed)) {
            $uploadError = "Format file harus JPG, PNG, atau PDF.";
        } else {
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $newFileName = 'bukti_' . $idBooking . '_' . time() . '.' . $ext;
            $target = __DIR__ . '/../uploads/bukti_pembayaran/' . $newFileName;
            
            if (!is_dir(dirname($target))) mkdir(dirname($target), 0755, true);

            if(move_uploaded_file($file['tmp_name'], $target)){
                $jumlah = ($keterangan === 'DP') ? ($booking['BkgTotalHarga'] * 0.5) : $booking['BkgTotalHarga'];
                
                $conn->begin_transaction();
                try {
                    if($pembayaran) {
                        $sql = "UPDATE pembayaran SET PbrMetode=?, PbrKeterangan=?, PbrJumlah=?, PbrBukti=?, PbrStatus='Pending', UpdatedAt=NOW() WHERE IDBooking=?";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("ssdsi", $metode, $keterangan, $jumlah, $newFileName, $idBooking);
                    } else {
                        $sql = "INSERT INTO pembayaran (IDBooking, PbrMetode, PbrKeterangan, PbrJumlah, PbrBukti, PbrStatus, CreatedAt) VALUES (?, ?, ?, ?, ?, 'Pending', NOW())";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("issds", $idBooking, $metode, $keterangan, $jumlah, $newFileName);
                    }
                    $stmt->execute();
                    
                    $conn->query("UPDATE booking SET BkgStatus='Pending', UpdatedAt=NOW() WHERE IDBooking=$idBooking");
                    $conn->commit();
                    
                    $uploadSuccess = true;
                    unset($_SESSION['cart']);
                    
                    $stmtRefresh = $conn->prepare("SELECT * FROM pembayaran WHERE IDBooking = ?");
                    $stmtRefresh->bind_param("i", $idBooking);
                    $stmtRefresh->execute();
                    $pembayaran = $stmtRefresh->get_result()->fetch_assoc();

                } catch(Exception $e) {
                    $conn->rollback();
                    $uploadError = "Database Error: " . $e->getMessage();
                }
            } else {
                $uploadError = "Gagal memindahkan file ke folder uploads.";
            }
        }
    }
}

function rupiah($n) { return 'Rp ' . number_format((float)$n, 0, ',', '.'); }
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Pembayaran - Artefax</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="../assets/img/logo Artefax1.png" rel="icon" />
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600&family=Raleway:wght@600;800&display=swap" rel="stylesheet"> 
    <link href="https://use.fontawesome.com/releases/v5.15.4/css/all.css" rel="stylesheet"/>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css?v=<?= time() ?>" rel="stylesheet"> 
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .header-rincian-text { color: #ffb400 !important; font-weight: 800; }
        .info-table td { padding: 8px 5px; font-size: 1rem; color: #333; }
        .info-table td:first-child { font-weight: 600; color: #555; }
        .note-verification {
            font-size: 0.85rem;
            background-color: #fff3cd;
            color: #856404;
            padding: 10px;
            border-radius: 8px;
            border: 1px solid #ffeeba;
            margin-top: 15px;
        }
    </style>
</head>
<body>

<div class="container-fluid fixed-top px-0">
    <nav class="navbar navbar-light bg-white navbar-expand-xl shadow-sm">
        <div class="container">
            <a href="../index.php" class="navbar-brand">
                <img src="../assets/img/logo Artefax.png" alt="Artefax" style="max-height: 55px;">
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarCollapse">
                <span class="fa fa-bars text-primary"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarCollapse">
                <div class="navbar-nav mx-auto">
                    <a href="Services.php" class="nav-item nav-link">Home</a>
                    <a href="shop.php" class="nav-item nav-link">Shop</a>
                </div>
                
                <div class="nav-icon-wrapper m-3 me-0">
                    <a href="../RiwayatBooking.php" class="nav-icon-btn" title="Riwayat Booking">
                        <i class="fas fa-history"></i>
                    </a> 
                    <a href="../View/profil.php" class="nav-icon-btn" title="Profil">
                        <i class="fas fa-user"></i>
                    </a>
                </div>
            </div>
        </div>
    </nav>
</div>

<div class="container-fluid page-header mb-5">
    <div class="container text-center">
        <h1 class="display-5 text-white fw-bold mb-2">Konfirmasi Pembayaran</h1>
        <p class="text-white-50 mb-0">Langkah terakhir sebelum pesanan diproses</p>
    </div>
</div>

<div class="container pembayaran-container pb-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="pembayaran-card">
                <div class="pembayaran-card-header">
                    <h4 class="mb-0 header-rincian-text"><i class="fas fa-wallet me-2"></i> Rincian Tagihan</h4>
                </div>
                <div class="card-body p-4">
                    
                    <?php if ($uploadSuccess): ?>
                    <script>
                       Swal.fire({
                            icon: 'success',
                            title: 'Pembayaran Berhasil Dikirim!',
                            html: 'Terima kasih. Bukti pembayaran Anda telah kami terima.<br>Mohon tunggu proses <b>verifikasi oleh admin</b>.',
                            confirmButtonColor: '#008ac0',
                            confirmButtonText: 'Lihat Status Booking'
                        }).then(() => {
                            window.location.href = '../RiwayatBooking.php';
                        });
                    </script>
                    <?php endif; ?>

                    <?php if ($uploadError): ?>
                        <div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i> <?= htmlspecialchars($uploadError) ?></div>
                    <?php endif; ?>

                    <div class="info-box-blue mb-4">
                        <table class="table table-borderless table-sm mb-0 info-table">
                            <tr>
                                <td width="40%">ID Booking</td>
                                <td>: <strong>#<?= str_pad($idBooking, 6, '0', STR_PAD_LEFT) ?></strong></td>
                            </tr>
                            <tr>
                                <td>Atas Nama</td>
                                <td>: <strong><?= htmlspecialchars($userName) ?></strong></td>
                            </tr>
                            <tr>
                                <td>Total Transaksi</td>
                                <td class="text-danger fw-bold fs-5">: <?= rupiah($booking['BkgTotalHarga']) ?></td>
                            </tr>
                        </table>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <div class="bank-card">
                                <i class="fas fa-university fa-3x text-primary mb-3"></i>
                                <h5 class="fw-bold text-dark">BANK BCA</h5>
                                <h3 class="mb-1 text-primary fw-bold">1234 567 890</h3>
                                <small class="text-muted">a.n. Artefax Indonesia</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="bank-card">
                                <i class="fas fa-university fa-3x text-primary mb-3"></i>
                                <h5 class="fw-bold text-dark">BANK BRI</h5>
                                <h3 class="mb-1 text-primary fw-bold">0987 654 321</h3>
                                <small class="text-muted">a.n. Artefax Indonesia</small>
                            </div>
                        </div>
                    </div>

                    <?php if (!$pembayaran || $pembayaran['PbrStatus'] === 'Gagal'): ?>
                    <form method="POST" enctype="multipart/form-data">
                        <h5 class="fw-bold mb-3 border-bottom pb-2">Form Upload Bukti</h5>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Transfer Ke Bank</label>
                                <select name="metode_pembayaran" class="form-select bg-light" required>
                                    <option value="">-- Pilih --</option>
                                    <option value="BCA">BCA</option>
                                    <option value="BRI">BRI</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Jenis Pembayaran</label>
                                <input type="text" class="form-control bg-light" value="<?= $jenisBayarOtomatis === 'DP' ? 'DP 50%' : 'LUNAS (Full)' ?>" readonly>
                                <input type="hidden" name="keterangan" id="keteranganInput" value="<?= $jenisBayarOtomatis ?>">
                            </div>
                        </div>

                        <div class="total-box-green d-flex justify-content-between align-items-center">
                            <span class="fw-bold fs-6 text-dark">Nominal Transfer:</span>
                            <span class="total-nominal-red" id="displayJumlah">...</span>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold">Foto Bukti Transfer <span class="text-danger">*</span></label>
                            <input type="file" name="bukti_tf" class="form-control" required>
                            <small class="text-muted">Format: JPG, PNG, PDF (Max 5MB)</small>
                        </div>

                        <button type="submit" class="btn-kirim-pembayaran">
                            <i class="fas fa-paper-plane me-2"></i> KIRIM KONFIRMASI
                        </button>

                        <div class="note-verification text-center">
                            <i class="fas fa-clock me-1"></i> <strong>Info:</strong> Proses verifikasi pembayaran membutuhkan waktu maksimal <strong>1x24 Jam</strong> kerja.
                        </div>

                    </form>
                    <script>
                        document.addEventListener("DOMContentLoaded", function() {
                            const total = <?= (float)$booking['BkgTotalHarga'] ?>;
                            const jenis = document.getElementById('keteranganInput').value;
                            const bayar = (jenis === 'DP') ? total * 0.5 : total;
                            document.getElementById('displayJumlah').textContent = 'Rp ' + bayar.toLocaleString('id-ID');
                        });
                    </script>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-check-circle fa-4x text-warning mb-3"></i>
                            <h4 class="fw-bold">Pembayaran Sedang Diproses</h4>
                            <p class="text-muted">Status: <span class="badge bg-warning text-dark"><?= htmlspecialchars($pembayaran['PbrStatus']) ?></span></p>
                            <div class="note-verification mt-3">
                                <i class="fas fa-info-circle"></i> Mohon tunggu admin memverifikasi bukti pembayaran Anda (Max 1x24 Jam).
                            </div>
                            <div class="d-flex justify-content-center gap-2 mt-4">
                                <a href="../RiwayatBooking.php" class="btn btn-primary">Lihat Riwayat</a>
                                <a href="shop.php" class="btn btn-outline-secondary">Kembali Belanja</a>
                            </div>
                        </div>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid bg-dark text-white-50 footer pt-5 mt-5">
    <div class="container py-5">
        <div class="row g-5">
            <div class="col-lg-3 col-md-6">
                <h3 class="text-primary mb-4">ARTEFAX.ID</h3>
                <p class="mb-2">Jasa dokumentasi & sewa alat multimedia terpercaya.</p>
            </div>
            <div class="col-lg-3 col-md-6">
                <h4 class="text-light mb-4">Menu Cepat</h4>
                <a class="btn btn-link" href="Services.php">Home</a>
                <a class="btn btn-link" href="shop.php">Shop</a>
            </div>
            <div class="col-lg-3 col-md-6">
                <h4 class="text-light mb-4">Kontak</h4>
                <p class="mb-2">Jember, Jawa Timur</p>
                <p class="mb-2">WA: 0896-5352-1667</p>
            </div>
            <div class="col-lg-3 col-md-6">
                <h4 class="text-light mb-4">Sosial Media</h4>
                <div class="d-flex pt-2">
                    <a class="btn btn-social" href="#"><i class="fab fa-instagram"></i></a>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>