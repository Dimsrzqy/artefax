<?php
// ============================================
// FILE 4: konfirmasi_pembayaran.php
// Fungsi: Halaman konfirmasi setelah upload bukti
// ============================================

session_start();
require_once __DIR__ . '/../config/koneksi.php';

$bookingId = $_SESSION['last_booking_id'] ?? null;
$success = $_SESSION['success_payment'] ?? null;
unset($_SESSION['success_payment']);

// Jika tidak ada booking ID, redirect
if (!$bookingId) {
    header("Location: shop.php");
    exit;
}

// Ambil data booking dari database
$db = new Database();
$conn = $db->getConnection();

$sql = "SELECT b.*, u.Nama as NamaUser 
        FROM booking b 
        JOIN user u ON b.IDUser = u.IDUser 
        WHERE b.IDBooking = ?";
        
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $bookingId);
$stmt->execute();
$result = $stmt->get_result();
$booking = $result->fetch_assoc();
$stmt->close();

if (!$booking) {
    header("Location: shop.php");
    exit;
}

function rupiah($n){ 
    return 'Rp ' . number_format((float)$n, 0, ',', '.'); 
}

// Format tanggal Indonesia
function tglIndo($datetime) {
    $bulan = array(
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    );
    $pecah = explode(' ', $datetime);
    $tanggal = explode('-', $pecah[0]);
    $waktu = $pecah[1] ?? '';
    
    return $tanggal[2] . ' ' . $bulan[(int)$tanggal[1]] . ' ' . $tanggal[0] . ' ' . $waktu;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Konfirmasi Pembayaran - Artefax</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600&family=Raleway:wght@600;800&display=swap" rel="stylesheet"> 

    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css"/>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">

    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        .success-animation {
            animation: scaleIn 0.5s ease-in-out;
        }
        
        @keyframes scaleIn {
            0% {
                transform: scale(0);
                opacity: 0;
            }
            50% {
                transform: scale(1.1);
            }
            100% {
                transform: scale(1);
                opacity: 1;
            }
        }
        
        .info-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .step-item {
            display: flex;
            align-items: start;
            margin-bottom: 15px;
        }
        
        .step-number {
            background: #28a745;
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 15px;
            flex-shrink: 0;
        }
    </style>
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
                    <a href="contact.php" class="nav-item nav-link">Contact</a>
                </div>
                <div class="d-flex m-3 me-0">
                    <a href="#" class="my-auto">
                        <i class="fas fa-user fa-2x"></i>
                    </a>
                </div>
            </div>
        </nav>
    </div>
</div>

<?php if($success): ?>
<script>
document.addEventListener('DOMContentLoaded', ()=> {
  Swal.fire({
    icon:'success',
    title:'Pembayaran Berhasil!',
    text: <?= json_encode($success) ?>,
    confirmButtonText: 'OK',
    confirmButtonColor: '#28a745'
  });
});
</script>
<?php endif; ?>

<!-- Main Content -->
<div class="container py-5" style="margin-top:120px;">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            
            <!-- Success Card -->
            <div class="card shadow border-0 success-animation">
                <div class="card-header text-center py-4" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                    <i class="fas fa-check-circle text-white" style="font-size: 4rem;"></i>
                    <h3 class="text-white mt-3 mb-0">Pembayaran Berhasil Dikirim!</h3>
                </div>
                
                <div class="card-body p-4">
                    
                    <!-- Info Pesanan -->
                    <div class="info-box">
                        <h5 class="mb-3"><i class="fas fa-receipt"></i> Informasi Pesanan</h5>
                        <table class="table table-sm table-borderless text-white mb-0">
                            <tr>
                                <td width="200"><strong>ID Booking:</strong></td>
                                <td><strong>#<?= $booking['IDBooking'] ?></strong></td>
                            </tr>
                            <tr>
                                <td><strong>Nama Pemesan:</strong></td>
                                <td><?= htmlspecialchars($booking['NamaUser']) ?></td>
                            </tr>
                            <tr>
                                <td><strong>Tanggal Acara:</strong></td>
                                <td>
                                    <?= tglIndo($booking['BkgTglMulai']) ?><br>
                                    <small>s/d</small><br>
                                    <?= tglIndo($booking['BkgTglSelesai']) ?>
                                </td>
                            </tr>
                            <?php if ($booking['BkgAlamat']): ?>
                            <tr>
                                <td><strong>Lokasi Acara:</strong></td>
                                <td><?= htmlspecialchars($booking['BkgAlamat']) ?></td>
                            </tr>
                            <?php endif; ?>
                            <tr>
                                <td><strong>Total Pembayaran:</strong></td>
                                <td><strong style="font-size: 1.3rem;"><?= rupiah($booking['BkgTotalHarga']) ?></strong></td>
                            </tr>
                            <tr>
                                <td><strong>Status:</strong></td>
                                <td>
                                    <span class="badge bg-warning text-dark">
                                        <i class="fas fa-clock"></i> Menunggu Verifikasi Admin
                                    </span>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <!-- Langkah Selanjutnya -->
                    <div class="alert alert-success">
                        <h5><i class="fas fa-check-double"></i> Langkah Selanjutnya:</h5>
                        
                        <div class="step-item">
                            <div class="step-number">1</div>
                            <div>
                                <strong>Bukti pembayaran telah diterima</strong><br>
                                <small>Tim kami telah menerima bukti transfer Anda</small>
                            </div>
                        </div>
                        
                        <div class="step-item">
                            <div class="step-number">2</div>
                            <div>
                                <strong>Verifikasi oleh admin dalam 1x24 jam</strong><br>
                                <small>Admin akan melakukan pengecekan dan verifikasi pembayaran</small>
                            </div>
                        </div>
                        
                        <div class="step-item">
                            <div class="step-number">3</div>
                            <div>
                                <strong>Notifikasi konfirmasi</strong><br>
                                <small>Anda akan dihubungi via WhatsApp/Email setelah verifikasi selesai</small>
                            </div>
                        </div>
                        
                        <div class="step-item">
                            <div class="step-number">4</div>
                            <div>
                                <strong>Status pesanan akan diupdate</strong><br>
                                <small>Cek status terbaru di menu Riwayat Pesanan</small>
                            </div>
                        </div>
                    </div>

                    <!-- Catatan Penting -->
                    <div class="alert alert-warning">
                        <h6><i class="fas fa-exclamation-triangle"></i> Catatan Penting:</h6>
                        <ul class="mb-0 ps-3">
                            <li>Simpan ID Booking Anda: <strong>#<?= $booking['IDBooking'] ?></strong></li>
                            <li>Jika ada pertanyaan, hubungi CS kami di <strong>WhatsApp: 081234567890</strong></li>
                            <li>Jangan lakukan pembayaran ulang sebelum konfirmasi dari admin</li>
                            <li>Screenshot halaman ini sebagai bukti transaksi</li>
                        </ul>
                    </div>

                    <!-- Contact Info -->
                    <div class="alert alert-info">
                        <h6><i class="fas fa-phone-alt"></i> Butuh Bantuan?</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <strong>WhatsApp:</strong> 081234567890
                            </div>
                            <div class="col-md-6">
                                <strong>Email:</strong> info@artefax.id
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="d-grid gap-2 mt-4">
                        <a href="shop.php" class="btn btn-primary btn-lg">
                            <i class="fas fa-shopping-bag"></i> Kembali ke Shop
                        </a>
                        <a href="Services.php" class="btn btn-success btn-lg">
                            <i class="fas fa-history"></i> Lihat Riwayat Pesanan
                        </a>
                        <button onclick="window.print()" class="btn btn-outline-secondary">
                            <i class="fas fa-print"></i> Cetak Halaman Ini
                        </button>
                    </div>

                </div>
            </div>

        </div>
    </div>
</div>

<!-- Footer -->
<div class="container-fluid bg-dark text-white-50 footer pt-5 mt-5">
    <div class="container py-5">
        <div class="pb-4 mb-4" style="border-bottom: 1px solid rgba(226, 175, 24, 0.5) ;">
            <div class="row g-4">
                <div class="col-lg-3">
                    <a href="#">
                        <h1 class="text-primary mb-0">ARTEFAX.ID</h1>
                        <p class="text-secondary mb-0">Paket Jasa & Sewa Alat</p>
                    </a>
                </div>
                <div class="col-lg-3">
                    <h4 class="text-light mb-3">Hubungi Kami</h4>
                    <p><i class="fas fa-phone-alt text-primary me-2"></i>+62 812-3456-7890</p>
                    <p><i class="fas fa-envelope text-primary me-2"></i>info@artefax.id</p>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid copyright bg-dark py-4">
    <div class="container">
        <div class="row">
            <div class="col-md-6 text-center text-md-start mb-3 mb-md-0">
                <span class="text-light">
                    <i class="fas fa-copyright text-light me-2"></i>Artefax, All right reserved.
                </span>
            </div>
        </div>
    </div>
</div>

<a href="#" class="btn btn-primary border-3 border-primary rounded-circle back-to-top"><i class="fa fa-arrow-up"></i></a>

<!-- JavaScript -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/main.js"></script>

<script>
// Auto scroll to top
window.scrollTo(0, 0);

// Hapus session booking setelah 5 detik (cleanup)
setTimeout(() => {
    <?php unset($_SESSION['last_booking_id']); ?>
}, 5000);
</script>

</body>
</html>