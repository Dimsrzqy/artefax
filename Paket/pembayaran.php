<?php
// ============================================
// pembayaran.php
// Form upload bukti pembayaran
// ============================================

session_start();

// CEK APAKAH ADA DATA BOOKING DARI SESSION
$IDBooking = $_SESSION['checkout_booking_id'] ?? null;
$total = $_SESSION['checkout_total'] ?? 0;
$paymentType = $_SESSION['checkout_payment'] ?? 'DP';
$userName = $_SESSION['checkout_name'] ?? ($_SESSION['user']['Nama'] ?? 'Pengunjung');

// Jika tidak ada booking ID, redirect ke shop
if (!$IDBooking) {
    $_SESSION['error'] = "Tidak ada data booking. Silakan checkout terlebih dahulu.";
    header("Location: shop.php");
    exit;
}

// Hitung jumlah yang harus dibayar
if ($paymentType === 'DP') {
    $jumlahBayar = $total * 0.5;
    $keterangan = "DP";
} else {
    $jumlahBayar = $total;
    $keterangan = "LUNAS";
}

// Format rupiah
function rupiah($n){ 
    return 'Rp ' . number_format((float)$n, 0, ',', '.'); 
}

// Flash messages
$error = $_SESSION['error'] ?? null;
unset($_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Upload Bukti Pembayaran - Artefax</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600&family=Raleway:wght@600;800&display=swap" rel="stylesheet"> 

    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css"/>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">

    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>

<!-- Spinner Start -->
<div id="spinner" class="show w-100 vh-100 bg-white position-fixed translate-middle top-50 start-50 d-flex align-items-center justify-content-center">
    <div class="spinner-grow text-primary" role="status"></div>
</div>

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

<?php if($error): ?>
<script>
document.addEventListener('DOMContentLoaded', ()=> {
  Swal.fire({
    icon:'error',
    title:'Gagal',
    text: <?= json_encode($error) ?>
  });
});
</script>
<?php endif; ?>

<!-- Main Content -->
<div class="container py-5" style="margin-top:120px;">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><i class="fas fa-upload"></i> Upload Bukti Pembayaran</h4>
                </div>
                
                <div class="card-body">
                    
                    <!-- Info Booking -->
                    <div class="alert alert-info">
                        <h5><i class="fas fa-info-circle"></i> Informasi Pembayaran</h5>
                        <table class="table table-sm table-borderless mb-0">
                            <tr>
                                <td width="150"><strong>ID Booking:</strong></td>
                                <td>#<?= $IDBooking ?></td>
                            </tr>
                            <tr>
                                <td><strong>Nama:</strong></td>
                                <td><?= htmlspecialchars($userName) ?></td>
                            </tr>
                            <tr>
                                <td><strong>Total Pesanan:</strong></td>
                                <td><?= rupiah($total) ?></td>
                            </tr>
                            <tr>
                                <td><strong>Jenis Pembayaran:</strong></td>
                                <td><span class="badge bg-warning text-dark"><?= $paymentType ?></span></td>
                            </tr>
                            <tr>
                                <td><strong>Jumlah yang Harus Dibayar:</strong></td>
                                <td class="text-danger"><strong style="font-size:1.3rem"><?= rupiah($jumlahBayar) ?></strong></td>
                            </tr>
                        </table>
                    </div>

                    <!-- Informasi Rekening -->
                    <div class="alert alert-success">
                        <h5><i class="fas fa-university"></i> Rekening Tujuan Transfer</h5>
                        <div class="row">
                            <div class="col-md-6 mb-2">
                                <strong>BCA:</strong> 1234567890<br>
                                <small class="text-muted">a.n. Artefax</small>
                            </div>
                            <div class="col-md-6 mb-2">
                                <strong>BRI:</strong> 0987654321<br>
                                <small class="text-muted">a.n. Artefax</small>
                            </div>
                            <div class="col-md-6 mb-2">
                                <strong>GOPAY:</strong> 081234567890
                            </div>
                            <div class="col-md-6 mb-2">
                                <strong>DANA:</strong> 081234567890
                            </div>
                        </div>
                    </div>

                    <!-- Form Upload Bukti -->
                    <form action="proses_bayar.php" method="POST" enctype="multipart/form-data" id="formBukti">
                        
                        <input type="hidden" name="IDBooking" value="<?= $IDBooking ?>">
                        <input type="hidden" name="PbrKeterangan" value="<?= $keterangan ?>">
                        <input type="hidden" name="PbrJumlah" value="<?= $jumlahBayar ?>">

                        <!-- Pilih Metode Pembayaran -->
                        <div class="mb-3">
                            <label class="form-label">Metode Pembayaran <span class="text-danger">*</span></label>
                            <select name="PbrMetode" class="form-select" required>
                                <option value="">-- Pilih Metode --</option>
                                <option value="BCA">BCA</option>
                                <option value="BRI">BRI</option>
                                <option value="GOPAY">GOPAY</option>
                                <option value="DANA">DANA</option>
                            </select>
                        </div>

                        <!-- Upload Bukti Transfer -->
                        <div class="mb-3">
                            <label class="form-label">Upload Bukti Transfer <span class="text-danger">*</span></label>
                            <input type="file" name="PbrBukti" class="form-control" accept="image/*" required id="fileInput">
                            <small class="form-text text-muted">
                                Format: JPG, PNG, atau GIF. Maksimal 5MB.
                            </small>
                        </div>

                        <!-- Preview Image -->
                        <div class="mb-3" id="previewContainer" style="display:none;">
                            <label class="form-label">Preview:</label>
                            <div>
                                <img id="previewImage" src="" style="max-width:100%; max-height:300px; border:1px solid #ddd; padding:5px; border-radius:5px;">
                            </div>
                        </div>

                        <!-- Catatan -->
                        <div class="alert alert-warning">
                            <small>
                                <strong>Catatan:</strong><br>
                                - Pastikan bukti transfer jelas dan terbaca<br>
                                - Pembayaran akan diverifikasi oleh admin dalam 1x24 jam<br>
                                - Setelah verifikasi, status booking akan diupdate
                            </small>
                        </div>

                        <!-- Tombol Submit -->
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-paper-plane"></i> Kirim Bukti Pembayaran
                            </button>
                            <a href="shop.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left"></i> Kembali ke Shop
                            </a>
                        </div>

                    </form>

                </div>
            </div>

        </div>
    </div>
</div>
<!-- Footer Start -->
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
                        <div class="col-lg-6">
                            <div class="position-relative mx-auto">
                                <input class="form-control border-0 w-100 py-3 px-4 rounded-pill" type="number" placeholder="Your Email">
                                <button type="submit" class="btn btn-primary border-0 border-secondary py-3 px-4 position-absolute rounded-pill text-white" style="top: 0; right: 0;">Subscribe Now</button>
                            </div>
                        </div>
                        <div class="col-lg-3">
                            <div class="d-flex justify-content-end pt-3">
                                <a class="btn  btn-outline-secondary me-2 btn-md-square rounded-circle" href=""><i class="fab fa-twitter"></i></a>
                                <a class="btn btn-outline-secondary me-2 btn-md-square rounded-circle" href=""><i class="fab fa-facebook-f"></i></a>
                                <a class="btn btn-outline-secondary me-2 btn-md-square rounded-circle" href=""><i class="fab fa-youtube"></i></a>
                                <a class="btn btn-outline-secondary btn-md-square rounded-circle" href=""><i class="fab fa-linkedin-in"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row g-5">
                    <div class="col-lg-3 col-md-6">
                        <div class="footer-item">
                            <h4 class="text-light mb-3">Why People Like us!</h4>
                            <p class="mb-4">typesetting, remaining essentially unchanged. It was 
                                popularised in the 1960s with the like Aldus PageMaker including of Lorem Ipsum.</p>
                            <a href="" class="btn border-secondary py-2 px-4 rounded-pill text-primary">Read More</a>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="d-flex flex-column text-start footer-item">
                            <h4 class="text-light mb-3">Shop Info</h4>
                            <a class="btn-link" href="">About Us</a>
                            <a class="btn-link" href="">Contact Us</a>
                            <a class="btn-link" href="">Privacy Policy</a>
                            <a class="btn-link" href="">Terms & Condition</a>
                            <a class="btn-link" href="">Return Policy</a>
                            <a class="btn-link" href="">FAQs & Help</a>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="d-flex flex-column text-start footer-item">
                            <h4 class="text-light mb-3">Account</h4>
                            <a class="btn-link" href="">My Account</a>
                            <a class="btn-link" href="">Shop details</a>
                            <a class="btn-link" href="">Shopping Cart</a>
                            <a class="btn-link" href="">Wishlist</a>
                            <a class="btn-link" href="">Order History</a>
                            <a class="btn-link" href="">International Orders</a>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="footer-item">
                            <h4 class="text-light mb-3">Contact</h4>
                            <p>Address: 1429 Netus Rd, NY 48247</p>
                            <p>Email: Example@gmail.com</p>
                            <p>Phone: +0123 4567 8910</p>
                            <p>Payment Accepted</p>
                            <img src="img/payment.png" class="img-fluid" alt="">
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Footer End -->

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
// Preview image sebelum upload
document.getElementById('fileInput').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        // Validasi ukuran
        if (file.size > 5 * 1024 * 1024) {
            Swal.fire({
                icon: 'error',
                title: 'File Terlalu Besar',
                text: 'Maksimal ukuran file adalah 5MB'
            });
            this.value = '';
            return;
        }
        
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('previewImage').src = e.target.result;
            document.getElementById('previewContainer').style.display = 'block';
        }
        reader.readAsDataURL(file);
    }
});

// Spinner
window.addEventListener('load', function() {
    document.getElementById('spinner').classList.remove('show');
});
</script>

</body>
</html>