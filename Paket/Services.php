<?php
// ===============================
// 🔹 1. KONEKSI DAN SESI AWAL
// ===============================
include __DIR__ . '/../config/koneksi.php';
session_start();

// 🔹 SweetAlert jika checkout berhasil
if (isset($_SESSION['success_checkout'])) {
    echo "
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                title: 'Berhasil!',
                text: '" . $_SESSION['success_checkout'] . "',
                icon: 'success',
                timer: 2500,
                showConfirmButton: false
            });
        });
    </script>
    ";
    unset($_SESSION['success_checkout']);
}

// 🔹 Membuat koneksi database
$db = new Database();
$conn = $db->getConnection();

if (!$conn) {
    die("Koneksi database gagal.");
}

// ===============================
// 🔹 2. AMBIL DATA DARI DATABASE
// ===============================
$alat = [];
$paket = [];

try {
    // 🔸 Ambil data dari tabel alat
    $resultAlat = $conn->query("SELECT * FROM alat ORDER BY IDAlat DESC");
    if ($resultAlat && $resultAlat->num_rows > 0) {
        while ($row = $resultAlat->fetch_assoc()) {
            $alat[] = $row;
        }
    }

    // 🔸 Ambil data dari tabel paket jasa
    $resultPaket = $conn->query("SELECT * FROM paketjasa ORDER BY IDPaket DESC");
    if ($resultPaket && $resultPaket->num_rows > 0) {
        while ($row = $resultPaket->fetch_assoc()) {
            $paket[] = $row;
        }
    }
} catch (Exception $e) {
    die("Error mengambil data: " . $e->getMessage());
}

// ===============================
// 🔹 3. KELOMPOKKAN BERDASARKAN KATEGORI (opsional)
// ===============================
$alatByKategori = [];
foreach ($alat as $a) {
    $kat = strtolower(trim($a['AlatKategori'] ?? 'lainnya'));
    $alatByKategori[$kat][] = $a;
}

$paketByKategori = [];
foreach ($paket as $p) {
    $kat = strtolower(trim($p['PaketKategori'] ?? 'lainnya'));
    $paketByKategori[$kat][] = $p;
}
?>

<!-- =============================== -->
<!-- 🔹 4. BAGIAN HEAD HTML          -->
<!-- =============================== -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Layanan - Artefax</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">

    <!-- Google Fonts & Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600&family=Raleway:wght@600;800&display=swap" rel="stylesheet"> 
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css"/>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Library CSS -->
    <link href="lib/lightbox/css/lightbox.min.css" rel="stylesheet">
    <link href="lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">

    <!-- Bootstrap & Custom -->
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>

<body>
    <!-- =============================== -->
    <!-- 🔹 5. NAVBAR DARI TEMPLATE     -->
    <!-- =============================== -->
   <!-- 🔸 Navbar Start -->
<div class="container-fluid fixed-top">
    <!-- 🔹 Top Bar (bagian atas navbar) -->
    <div class="container topbar bg-primary d-none d-lg-block">
        <div class="d-flex justify-content-between">
            <div class="top-info ps-2">
                <small class="me-3">
                    <i class="fas fa-map-marker-alt me-2 text-secondary"></i> 
                    <a href="#" class="text-white">123 Street, New York</a>
                </small>
                <small class="me-3">
                    <i class="fas fa-envelope me-2 text-secondary"></i>
                    <a href="#" class="text-white">Email@Example.com</a>
                </small>
            </div>

            <div class="top-link pe-2">
                <a href="#" class="text-white"><small class="text-white mx-2">Privacy Policy</small> /</a>
                <a href="#" class="text-white"><small class="text-white mx-2">Terms of Use</small> /</a>
                <a href="#" class="text-white"><small class="text-white ms-2">Sales and Refunds</small></a>
            </div>
        </div>
    </div>

    <!-- 🔹 Navbar Utama -->
    <div class="container px-0">
        <nav class="navbar navbar-light bg-white navbar-expand-xl">
            
            <!-- 🔸 Logo Brand -->
            <a href="../index.php" class="navbar-brand">
                <h1 class="text-primary display-6">Artefax</h1>
            </a>

            <!-- 🔸 Tombol Toggle (untuk tampilan mobile) -->
            <button class="navbar-toggler py-2 px-3" type="button" data-bs-toggle="collapse" data-bs-target="#navbarCollapse">
                <span class="fa fa-bars text-primary"></span>
            </button>

            <!-- 🔸 Daftar Menu -->
            <div class="collapse navbar-collapse bg-white" id="navbarCollapse">
                <div class="navbar-nav mx-auto">

                    <!-- ✅ Home diarahkan ke index utama folder Artefax -->
                    <a href="../index.php" class="nav-item nav-link">Home</a>

                    <!-- ✅ Services diarahkan ke halaman dalam folder Paket -->
                    <a href="index.php" class="nav-item nav-link active">Services</a>

                    <!-- ✅ Shop dan Detail -->
                    <a href="shop.php" class="nav-item nav-link">Shop</a>
                    <a href="shop-detail.php" class="nav-item nav-link">Shop Detail</a>

                    <!-- 🔸 Dropdown Menu -->
                    <div class="nav-item dropdown">
                        <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">Pages</a>
                        <div class="dropdown-menu m-0 bg-secondary rounded-0">
                            <a href="cart.php" class="dropdown-item">Cart</a>
                            <a href="checkout.php" class="dropdown-item">Checkout</a>
                            <a href="404.php" class="dropdown-item">404 Page</a>
                        </div>
                    </div>

                    <!-- 🔸 Contact -->
                    <a href="contact.php" class="nav-item nav-link">Contact</a>
                </div>

                <!-- 🔸 Bagian Kanan Navbar (Search, Cart, Account) -->
                <div class="d-flex m-3 me-0">

                    <!-- Tombol Pencarian -->
                    <button class="btn-search btn border border-secondary btn-md-square rounded-circle bg-white me-4" 
                            data-bs-toggle="modal" data-bs-target="#searchModal">
                        <i class="fas fa-search text-primary"></i>
                    </button>

                    <!-- Keranjang -->
                    <a href="#" class="position-relative me-4 my-auto">
                        <i class="fa fa-shopping-bag fa-2x"></i>
                        <span class="position-absolute bg-secondary rounded-circle d-flex align-items-center justify-content-center text-dark px-1"
                              style="top: -5px; left: 15px; height: 20px; min-width: 20px;">3</span>
                    </a>

                    <!-- Akun -->
                    <a href="#" class="my-auto">
                        <i class="fas fa-user fa-2x"></i>
                    </a>
                </div>
            </div>
        </nav>
    </div>
</div>
<!-- Navbar End -->


    
    <!-- Modal Search Start -->
    <div class="modal fade" id="searchModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen">
            <div class="modal-content rounded-0">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Search by keyword</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body d-flex align-items-center">
                    <div class="input-group w-75 mx-auto d-flex">
                        <input type="search" class="form-control p-3" id="searchInput" placeholder="Search products..." aria-describedby="search-icon-1">
                        <span id="search-icon-1" class="input-group-text p-3" onclick="searchProducts()"><i class="fa fa-search"></i></span>
                    </div>
                    <div id="searchResults" class="w-100 position-absolute bg-white rounded shadow-sm" style="top: 100%; display: none; max-height: 300px; overflow-y: auto;">
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Modal Search End -->

    <!-- Hero Start -->
    <div class="container-fluid py-5 mb-5 hero-header">
        <div class="container py-5">
            <div class="row g-5 align-items-center">
                <div class="col-md-12 col-lg-7">
                    <h4 class="mb-3 text-secondary">100% Organic Foods</h4>
                    <h1 class="mb-5 display-3 text-primary">Organic Veggies & Fruits Foods</h1>
                    <div class="position-relative mx-auto">
                        <input class="form-control border-2 border-secondary w-75 py-3 px-4 rounded-pill" type="number" placeholder="Search">
                        <button type="submit" class="btn btn-primary border-2 border-secondary py-3 px-4 position-absolute rounded-pill text-white h-100" style="top: 0; right: 25%;">Submit Now</button>
                    </div>
                </div>
                <div class="col-md-12 col-lg-5">
                    <div id="carouselId" class="carousel slide position-relative" data-bs-ride="carousel">
                        <div class="carousel-inner" role="listbox">
                            <div class="carousel-item active rounded">
                                <img src="img/hero-img-1.png" class="img-fluid w-100 h-100 bg-secondary rounded" alt="First slide">
                                <a href="#" class="btn px-4 py-2 text-white rounded">Fruites</a>
                            </div>
                            <div class="carousel-item rounded">
                                <img src="img/hero-img-2.jpg" class="img-fluid w-100 h-100 rounded" alt="Second slide">
                                <a href="#" class="btn px-4 py-2 text-white rounded">Vesitables</a>
                            </div>
                        </div>
                        <button class="carousel-control-prev" type="button" data-bs-target="#carouselId" data-bs-slide="prev">
                            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Previous</span>
                        </button>
                        <button class="carousel-control-next" type="button" data-bs-target="#carouselId" data-bs-slide="next">
                            <span class="carousel-control-next-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Next</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Hero End -->

    <!-- =============================== -->
    <!-- 🔹 6. MODAL SEARCH BAR          -->
    <!-- =============================== -->
    <div class="modal fade" id="searchModal" tabindex="-1">
        <div class="modal-dialog modal-fullscreen">
            <div class="modal-content rounded-0">
                <div class="modal-header">
                    <h5 class="modal-title">Search by keyword</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body d-flex align-items-center">
                    <div class="input-group w-75 mx-auto d-flex">
                        <input type="search" class="form-control p-3" id="searchInput" placeholder="Cari produk...">
                        <span id="search-icon-1" class="input-group-text p-3"><i class="fa fa-search"></i></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- =============================== -->
    <!-- 🔹 7. BAGIAN PRODUK (ALAT & PAKET) -->
    <!-- =============================== -->
    <div class="container py-5">
        <div class="text-center mb-5">
            <h2 class="display-5 text-primary fw-bold">Daftar Produk</h2>
            <p class="text-muted">Temukan alat dan paket jasa terbaik dari Artefax</p>
        </div>

        <div class="row g-4">
            <!-- 🔸 Menampilkan semua alat -->
            <?php foreach ($alat as $a): ?>
                <div class="col-lg-3 col-md-6">
                    <div class="product-item border rounded p-3">
                        <img src="<?= htmlspecialchars($a['AlatDirGbr']) ?>" class="img-fluid mb-3" alt="">
                        <h5><?= htmlspecialchars($a['AlatNama']) ?></h5>
                        <p><?= htmlspecialchars($a['AlatDeskripsi']) ?></p>
                        <p class="text-primary fw-bold">Rp <?= number_format($a['AlatHarga'], 0, ',', '.') ?></p>
                        <a href="#" class="btn btn-outline-primary w-100">Sewa</a>
                    </div>
                </div>
            <?php endforeach; ?>

            <!-- 🔸 Menampilkan semua paket jasa -->
            <?php foreach ($paket as $p): ?>
                <div class="col-lg-3 col-md-6">
                    <div class="product-item border rounded p-3">
                        <img src="<?= htmlspecialchars($p['PaketDirGbr']) ?>" class="img-fluid mb-3" alt="">
                        <h5><?= htmlspecialchars($p['PaketNama']) ?></h5>
                        <p><?= htmlspecialchars($p['PaketDeskripsi']) ?></p>
                        <p class="text-primary fw-bold">Rp <?= number_format($p['PaketHarga'], 0, ',', '.') ?></p>
                        <a href="#" class="btn btn-outline-primary w-100">Pesan</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- =============================== -->
    <!-- 🔹 8. FOOTER TEMPLATE           -->
    <!-- =============================== -->
    <div class="container-fluid bg-dark text-white-50 footer pt-5 mt-5">
<div class="container py-5">
        <div class="pb-4 mb-4" style="border-bottom: 1px solid rgba(226, 175, 24, 0.5) ;">
            <div class="row g-4">
                <div class="col-lg-3">
                    <a href="#">
                        <h1 class="text-primary mb-0">Fruitables</h1>
                        <p class="text-secondary mb-0">Fresh products</p>
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
    <!-- =============================== -->
    <!-- 🔹 9. SCRIPT BOOSTRAP & JS      -->
    <!-- =============================== -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="lib/easing/easing.min.js"></script>
    <script src="lib/lightbox/js/lightbox.min.js"></script>
    <script src="lib/owlcarousel/owl.carousel.min.js"></script>
    <script src="js/main.js"></script>
</body>
</html>
