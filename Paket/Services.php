<?php
// ===============================
// 🔹 1. KONEKSI DAN SESI AWAL
// ===============================
session_start();
require_once __DIR__ . '/../config/koneksi.php';

// ⬇️ TAMBAHKAN CART COMPONENTS
include __DIR__ . "/components/cart_modal.php";
include __DIR__ . "/components/cart_script.php";

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

// Hitung cart
$cart_count = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;

// ===============================
// 🔹 2. AMBIL DATA DARI DATABASE
// ===============================

// 🔸 Ambil 8 produk terbaru (gabungan alat & paket)
$sql_latest = "
    SELECT IDAlat AS id, 'alat' AS tipe, AlatNama AS name, AlatKategori AS kategori,
           AlatDeskripsi AS description, AlatHarga AS price, AlatDirGbr AS image, 
           AlatStatus AS status, CreatedAt as created_at
    FROM alat
    WHERE LOWER(AlatStatus) IN ('aktif','bestseller','tersedia')
    UNION ALL
    SELECT IDPaket AS id, 'paket' AS tipe, PaketNama AS name, PaketKategori AS kategori,
           PaketDeskripsi AS description, PaketHarga AS price, PaketDirGbr AS image, 
           PaketStatus AS status, CreatedAt as created_at
    FROM paketjasa
    WHERE PaketStatus IN ('aktif','Bestseller','bestseller')
    ORDER BY created_at DESC
    LIMIT 8
";

$result_latest = $conn->query($sql_latest);
$latest_products = [];
if ($result_latest) {
    while ($row = $result_latest->fetch_assoc()) {
        $latest_products[] = $row;
    }
}

// 🔸 Ambil produk BESTSELLER (favorit)
$sql_bestseller = "
    SELECT IDAlat AS id, 'alat' AS tipe, AlatNama AS name, AlatKategori AS kategori,
           AlatDeskripsi AS description, AlatHarga AS price, AlatDirGbr AS image, 
           AlatStatus AS status
    FROM alat
    WHERE LOWER(AlatStatus) = 'bestseller'
    UNION ALL
    SELECT IDPaket AS id, 'paket' AS tipe, PaketNama AS name, PaketKategori AS kategori,
           PaketDeskripsi AS description, PaketHarga AS price, PaketDirGbr AS image, 
           PaketStatus AS status
    FROM paketjasa
    WHERE LOWER(PaketStatus) = 'bestseller'
    LIMIT 8
";

$result_bestseller = $conn->query($sql_bestseller);
$bestseller_products = [];
if ($result_bestseller) {
    while ($row = $result_bestseller->fetch_assoc()) {
        $bestseller_products[] = $row;
    }
}

// Format Rupiah
function rupiah($n) {
    return 'Rp ' . number_format((float)$n, 0, ',', '.');
}
?>
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
    <!-- Spinner Start -->
    <div id="spinner" class="show w-100 vh-100 bg-white position-fixed translate-middle top-50 start-50 d-flex align-items-center justify-content-center">
        <div class="spinner-grow text-primary" role="status"></div>
    </div>
    <!-- Spinner End -->

    <!-- =============================== -->
    <!-- 🔹 NAVBAR -->
    <!-- =============================== -->
    <div class="container-fluid fixed-top">
        <div class="container px-0">
            <nav class="navbar navbar-light bg-white navbar-expand-xl">
                
                <!-- Logo Brand -->
                <a href="../index.php" class="navbar-brand">
                    <h1 class="text-primary display-6">Artefax</h1>
                </a>

                <!-- Toggle Button -->
                <button class="navbar-toggler py-2 px-3" type="button" data-bs-toggle="collapse" data-bs-target="#navbarCollapse">
                    <span class="fa fa-bars text-primary"></span>
                </button>

                <!-- Menu Items -->
                <div class="collapse navbar-collapse bg-white" id="navbarCollapse">
                    <div class="navbar-nav mx-auto">
                        <a href="../index.php" class="nav-item nav-link">Home</a>
                        <a href="Services.php" class="nav-item nav-link active">Services</a>
                        <a href="shop.php" class="nav-item nav-link">Shop</a>
                        <a href="checkout.php" class="nav-item nav-link">Checkout</a>
                        <a href="contact.php" class="nav-item nav-link">Contact</a>
                    </div>

                    <!-- Right Side Icons -->
                    <div class="d-flex m-3 me-0">
                        <!-- Search Button -->
                        <button class="btn-search btn border border-secondary btn-md-square rounded-circle bg-white me-4" 
                                data-bs-toggle="modal" data-bs-target="#searchModal">
                            <i class="fas fa-search text-primary"></i>
                        </button>

                        <!-- Cart Icon dengan Counter -->
                        <a href="#" class="position-relative me-4 my-auto" data-bs-toggle="modal" data-bs-target="#cartModal">
                            <i class="fa fa-shopping-bag fa-2x"></i>
                            <span class="position-absolute bg-secondary rounded-circle d-flex align-items-center justify-content-center text-dark px-1"
                                  style="top: -5px; left: 15px; height: 20px; min-width: 20px;">
                                <?= $cart_count ?>
                            </span>
                        </a>

                        <!-- Account Icon -->
                        <a href="#" class="my-auto">
                            <i class="fas fa-user fa-2x"></i>
                        </a>
                    </div>
                </div>
            </nav>
        </div>
    </div>
    <!-- Navbar End -->

    <!-- Modal Search -->
    <div class="modal fade" id="searchModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen">
            <div class="modal-content rounded-0">
                <div class="modal-header">
                    <h5 class="modal-title">Search by keyword</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body d-flex align-items-center">
                    <form method="GET" action="shop.php" class="input-group w-75 mx-auto d-flex">
                        <input type="text" name="q" class="form-control p-3" placeholder="Cari produk...">
                        <button class="input-group-text p-3" type="submit"><i class="fa fa-search"></i></button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Hero Start -->
    <div class="container-fluid py-5 mb-5 hero-header">
        <div class="container py-5">
            <div class="row g-5 align-items-center">
                <div class="col-md-12 col-lg-7">
                    <h4 class="mb-3 text-secondary">ARTEFAX.ID</h4>
                    <h1 class="mb-5 display-3 text-primary">Pemesanan Alat & Paket Jasa</h1>
                    
                    <!-- Form Pencarian yang Berfungsi -->
                    <form method="GET" action="shop.php" class="position-relative mx-auto">
                        <input class="form-control border-2 border-secondary w-75 py-3 px-4 rounded-pill" 
                               type="text" 
                               name="q" 
                               placeholder="Cari produk..." 
                               required>
                        <button type="submit" class="btn btn-primary border-2 border-secondary py-3 px-4 position-absolute rounded-pill text-white h-100" style="top: 0; right: 25%;">
                            <i class="fa fa-search me-2"></i> Cari
                        </button>
                    </form>
                </div>
                <div class="col-md-12 col-lg-5">
                    <div id="carouselId" class="carousel slide position-relative" data-bs-ride="carousel">
                        <div class="carousel-inner" role="listbox">
                            <div class="carousel-item active rounded">
                                <img src="img/alat.jpg" class="img-fluid w-100 h-100 bg-secondary rounded" alt="Alat">
                                <a href="shop.php?type=alat" class="btn px-4 py-2 text-white rounded">Alat</a>
                            </div>
                            <div class="carousel-item rounded">
                                <img src="img/jasa.jpg" class="img-fluid w-100 h-100 rounded" alt="Jasa">
                                <a href="shop.php?type=paket" class="btn px-4 py-2 text-white rounded">Paket Jasa</a>
                            </div>
                        </div>
                        <button class="carousel-control-prev" type="button" data-bs-target="#carouselId" data-bs-slide="prev">
                            <span class="carousel-control-prev-icon"></span>
                        </button>
                        <button class="carousel-control-next" type="button" data-bs-target="#carouselId" data-bs-slide="next">
                            <span class="carousel-control-next-icon"></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Hero End -->

    <!-- =============================== -->
    <!-- 🔹 SECTION: LAYANAN FAVORIT (BESTSELLER) -->
    <!-- =============================== -->
    <?php if (!empty($bestseller_products)): ?>
    <div class="container-fluid py-5">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-5 text-primary fw-bold">⭐ Layanan Favorit</h2>
                <p class="text-muted">Produk paling diminati oleh pelanggan kami</p>
            </div>

            <div class="row g-4 justify-content-center">
                <?php foreach ($bestseller_products as $p): 
                    $imgFile = $p['image'] ?? '';
                    $placeholder = 'img/noimage.png';
                    $imgUrl = $placeholder;
                    
                    if (!empty($imgFile)) {
                        $mainPath = 'img/produk/' . $imgFile;
                        if (file_exists(__DIR__ . '/' . $mainPath)) {
                            $imgUrl = $mainPath;
                        }
                    }
                ?>
                <div class="col-md-6 col-lg-6 col-xl-3">
                    <div class="rounded position-relative fruite-item">
                        <div class="fruite-img">
                            <img src="<?= $imgUrl ?>" class="img-fluid w-100 rounded-top" alt="<?= htmlspecialchars($p['name']) ?>" style="height:220px;object-fit:cover" onerror="this.onerror=null; this.src='img/noimage.png';">
                        </div>
                        <div class="position-absolute" style="top:10px;left:10px;">
                            <span class="badge bg-warning text-dark">⭐ Bestseller</span>
                        </div>
                        <div class="p-4 border border-secondary border-top-0 rounded-bottom">
                            <h4 class="mb-3"><?= htmlspecialchars($p['name']) ?></h4>
                            <div class="d-flex justify-content-between align-items-center">
                                <p class="text-dark fs-5 fw-bold mb-0"><?= rupiah($p['price']) ?></p>
                                <button class="btn border border-secondary rounded-pill px-3 text-primary openDetailBtn"
                                        data-id="<?= $p['id'] ?>"
                                        data-tipe="<?= $p['tipe'] ?>"
                                        data-name="<?= htmlspecialchars($p['name']) ?>"
                                        data-kat="<?= htmlspecialchars($p['kategori']) ?>"
                                        data-desc="<?= htmlspecialchars($p['description']) ?>"
                                        data-price="<?= $p['price'] ?>"
                                        data-img="<?= $imgUrl ?>">
                                    <i class="fa fa-info-circle me-2"></i> Detail
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- =============================== -->
    <!-- 🔹 SECTION: PRODUK TERBARU (8 ITEMS) -->
    <!-- =============================== -->
    <div class="container-fluid py-5">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-5 text-primary fw-bold">Produk Terbaru</h2>
                <p class="text-muted">Pilihan alat dan paket jasa terbaik untuk kebutuhan Anda</p>
            </div>

            <div class="row g-4 justify-content-center">
                <?php foreach ($latest_products as $p): 
                    $imgFile = $p['image'] ?? '';
                    $placeholder = 'img/noimage.png';
                    $imgUrl = $placeholder;
                    
                    if (!empty($imgFile)) {
                        $mainPath = 'img/produk/' . $imgFile;
                        if (file_exists(__DIR__ . '/' . $mainPath)) {
                            $imgUrl = $mainPath;
                        }
                    }
                ?>
                <div class="col-md-6 col-lg-6 col-xl-3">
                    <div class="rounded position-relative fruite-item">
                        <div class="fruite-img">
                            <img src="<?= $imgUrl ?>" class="img-fluid w-100 rounded-top" alt="<?= htmlspecialchars($p['name']) ?>" style="height:220px;object-fit:cover" onerror="this.onerror=null; this.src='img/noimage.png';">
                        </div>
                        <?php if (strtolower($p['status']) === 'bestseller'): ?>
                        <div class="position-absolute" style="top:10px;left:10px;">
                            <span class="badge bg-warning text-dark">Bestseller</span>
                        </div>
                        <?php endif; ?>
                        <div class="p-4 border border-secondary border-top-0 rounded-bottom">
                            <h4 class="mb-3"><?= htmlspecialchars($p['name']) ?></h4>
                            <div class="d-flex justify-content-between align-items-center">
                                <p class="text-dark fs-5 fw-bold mb-0"><?= rupiah($p['price']) ?></p>
                                <button class="btn border border-secondary rounded-pill px-3 text-primary openDetailBtn"
                                        data-id="<?= $p['id'] ?>"
                                        data-tipe="<?= $p['tipe'] ?>"
                                        data-name="<?= htmlspecialchars($p['name']) ?>"
                                        data-kat="<?= htmlspecialchars($p['kategori']) ?>"
                                        data-desc="<?= htmlspecialchars($p['description']) ?>"
                                        data-price="<?= $p['price'] ?>"
                                        data-img="<?= $imgUrl ?>">
                                    <i class="fa fa-info-circle me-2"></i> Detail
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Button Selengkapnya -->
            <div class="text-center mt-5">
                <a href="shop.php" class="btn btn-primary btn-lg rounded-pill px-5">
                    <i class="fa fa-arrow-right me-2"></i> Lihat Semua Produk
                </a>
            </div>
        </div>
    </div>

    <!-- MODAL DETAIL PRODUK -->
    <div class="modal fade" id="productModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content p-3">
                <!-- Tombol Close (X) -->
                <button type="button" class="btn-close position-absolute top-0 end-0 m-3" data-bs-dismiss="modal" aria-label="Close"></button>
                
                <div class="row g-3">
                    <div class="col-md-5">
                        <img id="modalImg" src="" class="img-fluid rounded" alt="">
                    </div>
                    <div class="col-md-7">
                        <h4 id="modalName"></h4>
                        <p><small id="modalCat" class="text-muted"></small></p>
                        <h5 class="text-primary" id="modalPrice"></h5>
                        <p id="modalDesc"></p>
                        
                        <div class="d-flex gap-2 align-items-center">
                            <label for="modalQty" class="mb-0">Jumlah:</label>
                            <input type="number" id="modalQty" class="form-control" value="1" min="1" style="width:100px;">
                            <button id="btnAddToCart" class="btn btn-primary flex-grow-1">
                                <i class="fa fa-shopping-cart me-2"></i>Tambah ke Keranjang
                            </button>
                        </div>
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

        <!-- Copyright Start -->
        <div class="container-fluid copyright bg-dark py-4">
            <div class="container">
                <div class="row">
                    <div class="col-md-6 text-center text-md-start mb-3 mb-md-0">
                        <span class="text-light"><a href="#"><i class="fas fa-copyright text-light me-2"></i>Your Site Name</a>, All right reserved.</span>
                    </div>
                    <div class="col-md-6 my-auto text-center text-md-end text-white">
                        Designed By <a class="border-bottom" href="https://htmlcodex.com">HTML Codex</a> Distributed By <a class="border-bottom" href="https://themewagon.com">ThemeWagon</a>
                    </div>
                </div>
            </div>
        </div>
        <!-- Copyright End -->

    <!-- JavaScript -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="lib/easing/easing.min.js"></script>
    <script src="lib/lightbox/js/lightbox.min.js"></script>
    <script src="lib/owlcarousel/owl.carousel.min.js"></script>

    <!-- Script untuk Popup & Add to Cart -->
    <script>
    // Buka modal detail produk
    $(document).on('click', '.openDetailBtn', function(){
        const btn = $(this);
        $('#modalImg').attr('src', btn.data('img'));
        $('#modalName').text(btn.data('name'));
        $('#modalCat').text(btn.data('kat'));
        $('#modalDesc').text(btn.data('desc'));
        const price = btn.data('price');
        $('#modalPrice').text(new Intl.NumberFormat('id-ID', { 
            style: 'currency', 
            currency: 'IDR', 
            maximumFractionDigits: 0 
        }).format(price));
        $('#modalQty').val(1);
        $('#btnAddToCart').data('id', btn.data('id'));
        $('#btnAddToCart').data('type', btn.data('tipe'));
        $('#btnAddToCart').data('name', btn.data('name'));
        $('#btnAddToCart').data('price', btn.data('price'));
        $('#productModal').modal('show');
    });

    // Tambah ke keranjang
    $('#btnAddToCart').click(function(){
        const id = $(this).data('id');
        const type = $(this).data('type');
        const name = $(this).data('name');
        const price = $(this).data('price');
        const qty = parseInt($('#modalQty').val()) || 1;

        fetch('root/cart_add.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `id=${id}&type=${type}&name=${encodeURIComponent(name)}&price=${price}&qty=${qty}`
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                if (data.cart_count) {
                    $('.position-absolute.bg-secondary').text(data.cart_count);
                }
                alert('✅ Produk berhasil ditambahkan ke keranjang!');
                $('#productModal').modal('hide');
            } else {
                alert('❌ Gagal: ' + (data.message || 'Terjadi kesalahan'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('❌ Gagal menghubungi server!');
        });
    });
    </script>

    <script src="js/main.js"></script>
</body>
</html>