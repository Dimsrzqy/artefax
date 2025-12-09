<?php
session_start();
require_once __DIR__ . '/../config/koneksi.php';

$db = new Database(); 
$conn = $db->getConnection();
$cart_count = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;

// === HITUNG TOTAL BOOKING PAKET JASA DI TANGGAL HARI INI (GLOBAL) ===
$today = date('Y-m-d');
$paketBookingCount = 0;
$stmtGlobal = $conn->prepare("
    SELECT COUNT(*) 
    FROM booking_detail bd
    INNER JOIN booking b ON bd.IDBooking = b.IDBooking
    WHERE bd.BkgDetailJenis = 'Paket Jasa'
      AND b.BkgStatus IN ('Diterima', 'Selesai')
      AND ? BETWEEN DATE(b.BkgTglMulai) AND DATE(b.BkgTglSelesai)
");
$stmtGlobal->bind_param("s", $today);
$stmtGlobal->execute();
$stmtGlobal->bind_result($paketBookingCount);
$stmtGlobal->fetch();
$stmtGlobal->close();

$isPaketFullToday = ($paketBookingCount >= 2); // GLOBAL: maksimal 2 booking paket per hari

// === Query Produk Bestseller ===
$q_best = "SELECT * FROM (
    (SELECT a.IDAlat AS id, 'alat' AS tipe, a.AlatNama AS name, a.AlatKategori AS kat, a.AlatDeskripsi AS des, a.AlatHarga AS prc, a.AlatDirGbr AS img, a.AlatStatus AS st FROM alat a WHERE AlatStatus!='Nonaktif')
    UNION ALL 
    (SELECT p.IDPaket AS id, 'paket' AS tipe, p.PaketNama AS name, p.PaketKategori AS kat, p.PaketDeskripsi AS des, p.PaketHarga AS prc, p.PaketDirGbr AS img, p.PaketStatus AS st FROM paketjasa p WHERE PaketStatus!='Nonaktif')
) AS gab 
ORDER BY (CASE WHEN LOWER(st)='bestseller' THEN 0 ELSE 1 END), id DESC 
LIMIT 8";
$res_best = $conn->query($q_best);

// === Query Produk Terbaru ===
$q_new = "SELECT IDAlat AS id, 'alat' AS tipe, AlatNama AS name, AlatKategori AS kat, AlatDeskripsi AS des, AlatHarga AS prc, AlatDirGbr AS img, AlatStatus AS st FROM alat WHERE AlatStatus!='Nonaktif' 
          UNION ALL 
          SELECT IDPaket AS id, 'paket' AS tipe, PaketNama AS name, PaketKategori AS kat, PaketDeskripsi AS des, PaketHarga AS prc, PaketDirGbr AS img, PaketStatus AS st FROM paketjasa WHERE PaketStatus!='Nonaktif' 
          ORDER BY id DESC LIMIT 8";
$res_new = $conn->query($q_new);

function rupiah($n){ return 'Rp ' . number_format((float)$n,0,',','.'); }

// === Fungsi Render Card dengan Logika Ketersediaan ===
function renderCard($p, $isFavorite = false, $isPaketFullToday = false) { 
    global $today;
    
    $img = (!empty($p['img']) && file_exists(__DIR__ . '/img/produk/' . $p['img'])) 
           ? 'img/produk/' . $p['img'] : 'img/noimage.png';
    $status = $p['st'] ?? '';
    $isBest = $isFavorite || (strtolower($status) == 'bestseller');

    // Hitung booking untuk alat (jika tipe alat)
    $bookedCount = 0;
    if ($p['tipe'] === 'alat') {
        $stmt = $GLOBALS['conn']->prepare("
            SELECT COUNT(*) FROM booking_detail bd 
            JOIN booking b ON bd.IDBooking = b.IDBooking 
            WHERE bd.IDAlat = ? AND b.BkgStatus IN ('Diterima','Selesai')
              AND ? BETWEEN DATE(b.BkgTglMulai) AND DATE(b.BkgTglSelesai)
        ");
        $stmt->bind_param("is", $p['id'], $today);
        $stmt->execute();
        $stmt->bind_result($bookedCount);
        $stmt->fetch();
        $stmt->close();
    }

    $limitBookingHarian = 2;
    $isStockEmpty = ($p['tipe'] === 'alat' && (int)$p['prc'] <= 0); // contoh stok habis
    $isDateFull   = ($p['tipe'] === 'alat') 
                    ? ($bookedCount >= $limitBookingHarian) 
                    : $isPaketFullToday;

    $isUnavailable = $isStockEmpty || $isDateFull;
    ?>
    <div class="col-md-6 col-lg-4 col-xl-3">
        <div class="product-card h-100 <?= $isUnavailable ? 'unavailable' : '' ?>">
            <div class="product-image-wrapper">
                <?php if ($isBest): ?>
                    <div class="position-absolute top-0 start-0 p-2 z-index-2">
                        <span class="badge-fav">FAVORIT</span>
                    </div>
                <?php endif; ?>
                <?php if ($isUnavailable): ?>
                    <div class="position-absolute top-50 start-50 translate-middle">
                        <span class="badge bg-danger fs-6 px-3 py-2">FULL</span>
                    </div>
                <?php endif; ?>
                <img src="<?= $img ?>" class="product-image" alt="<?= htmlspecialchars($p['name']) ?>">
            </div>
            
            <div class="p-3 d-flex flex-column flex-grow-1">
                <div class="mb-2">
                    <span class="chip-category tipe"><?= htmlspecialchars($p['tipe']) ?></span>
                    <span class="chip-category"><?= htmlspecialchars($p['kat']) ?></span>
                </div>

                <h5 class="fw-bold text-dark mb-2" style="font-size: 1.1rem; height: 2.5em; overflow: hidden; line-height: 1.3;">
                    <?= htmlspecialchars($p['name']) ?>
                </h5>

                <?php if ($isUnavailable): ?>
                    <div class="small mb-3 text-danger fw-bold">Tidak Tersedia Hari Ini</div>
                <?php else: ?>
                    <div class="small mb-3 text-success fw-bold">Tersedia</div>
                <?php endif; ?>

                <div class="mt-auto pt-3 d-flex justify-content-between align-items-center border-top">
                    <?php if ($isUnavailable): ?>
                        <h5 class="price-disabled mb-0"><?= rupiah($p['prc']) ?></h5>
                        <button class="btn-disabled-fixed" disabled>
                            Penuh
                        </button>
                    <?php else: ?>
                        <h5 class="product-price mb-0"><?= rupiah($p['prc']) ?></h5>
                        <button class="btn-action openDetailBtn"
                            data-id="<?= $p['id'] ?>" 
                            data-tipe="<?= $p['tipe'] ?>" 
                            data-name="<?= htmlspecialchars($p['name']) ?>"
                            data-price="<?= $p['prc'] ?>" 
                            data-desc="<?= htmlspecialchars($p['des']) ?>" 
                            data-img="<?= $img ?>"> 
                            Sewa
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php 
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Artefax - Home</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="../assets/img/logo Artefax1.png" rel="icon" />
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&family=Raleway:wght@600;800&display=swap" rel="stylesheet"> 
    <link href="https://use.fontawesome.com/releases/v5.15.4/css/all.css" rel="stylesheet"/>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css?v=<?= time() ?>" rel="stylesheet">
</head>
<body>
    <!-- NAVBAR SAMA -->
    <div class="container-fluid fixed-top px-0">
        <nav class="navbar navbar-light bg-white navbar-expand-xl shadow-sm">
            <div class="container">
                <a href="../index.php" class="navbar-brand">
                    <img src="../assets/img/logo Artefax.png" alt="Artefax" style="max-height: 55px;">
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarCollapse">
                    <span class="fa fa-bars text-primary"></span>
                </button>
                <div class="collapse navbar-collapse" id="#navbarCollapse">
                    <div class="navbar-nav mx-auto">
                        <a href="Services.php" class="nav-item nav-link active">Home</a>
                        <a href="shop.php" class="nav-item nav-link">Shop</a>
                    </div>
                    <div class="nav-icon-wrapper m-3 me-0">
                        <a href="#" class="nav-icon-btn" data-bs-toggle="modal" data-bs-target="#cartModal">
                            <i class="fa fa-shopping-bag"></i>
                            <span class="cart-badge"><?= $cart_count ?></span>
                        </a> 
                        <a href="../View/profil.php" class="nav-icon-btn"><i class="fas fa-user"></i></a>
                    </div>
                </div>
            </div>
        </nav>
    </div>

    <!-- HERO SECTION SAMA -->
    <div class="container-fluid hero-header mb-5">
        <div class="container">
            <div class="row g-5 align-items-center">
                <div class="col-md-12 col-lg-7 text-center text-lg-start">
                    <div class="d-inline-block mb-3">
                        <h4 class="typewriter">Selamat Datang di Artefax.id</h4>
                    </div>
                    <h1 class="hero-title text-white">Solusi Sewa Alat & Jasa Dokumentasi</h1>
                    <div class="search-box-hero position-relative mx-auto ms-lg-0 w-100" style="max-width: 600px;">
                        <form action="shop.php" method="GET" class="d-flex w-100">
                            <input class="form-control border-0 w-100 py-3 px-4 rounded-pill shadow-none bg-transparent" 
                                   type="text" name="q" placeholder="Cari kamera, lensa, atau paket..." required>
                            <button type="submit" class="btn btn-primary border-0 py-3 px-4 position-absolute rounded-pill text-white fw-bold shadow-sm" style="top: 5px; right: 5px;">
                                Cari
                            </button>
                        </form>
                    </div>
                </div>
                <div class="col-md-12 col-lg-5">
                    <div id="carouselId" class="carousel slide carousel-fade carousel-hero-container" data-bs-ride="carousel" data-bs-interval="3000">
                        <div class="carousel-inner">
                            <div class="carousel-item active">
                                <img src="img/alat.jpg" class="d-block w-100" alt="Alat">
                                <div class="carousel-caption d-none d-md-block bg-dark bg-opacity-75 rounded p-2 mb-3"><h5 class="text-white m-0">Alat Multimedia</h5></div>
                            </div>
                            <div class="carousel-item">
                                <img src="img/jasa.jpg" class="d-block w-100" alt="Jasa">
                                <div class="carousel-caption d-none d-md-block bg-dark bg-opacity-75 rounded p-2 mb-3"><h5 class="text-white m-0">Jasa Dokumentasi</h5></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- PRODUK FAVORIT -->
    <?php if ($res_best && $res_best->num_rows > 0): ?>
    <div class="container py-5">
        <div class="text-center mx-auto mb-5" style="max-width: 700px;">
            <h2 class="display-5 text-primary fw-bold">Produk Paling Banyak Dicari</h2>
            <p class="text-muted">Produk favorit pelanggan kami.</p>
        </div>
        <div class="row g-4">
            <?php while($row = $res_best->fetch_assoc()) renderCard($row, true, $isPaketFullToday); ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- PRODUK TERBARU -->
    <div class="container py-5">
        <div class="text-center mx-auto mb-5" style="max-width: 700px;">
            <h2 class="display-5 text-primary fw-bold">Koleksi Terbaru</h2>
        </div>
        <div class="row g-4">
            <?php while($row = $res_new->fetch_assoc()) renderCard($row, false, $isPaketFullToday); ?>
        </div>
        <div class="text-center mt-5">
            <a href="shop.php" class="btn btn-primary py-3 px-5 shadow-lg">Lihat Semua Produk</a>
        </div>
    </div>

    <!-- FOOTER SAMA -->
    
<div class="container-fluid bg-dark text-white-50 footer pt-5 mt-5">
    <div class="container py-5">
        <div class="pb-4 mb-4" style="border-bottom: 1px solid rgba(226, 175, 24, 0.5);">
            <div class="row g-4">
                <div class="col-lg-3">
                    <a href="#">
                        <h1 class="text-info mb-0">ARTEFAX.ID</h1>
                        <p class="text-secondary mb-0">Penyewaan Paket Jasa Dan Alat Multimedia</p>
                    </a>
                </div>
                <div class="col-lg-3">
                    <div class="d-flex justify-content-end pt-3">
                        <a class="btn btn-outline-secondary me-2 btn-md-square rounded-circle" href="https://www.instagram.com/artefax_id?igsh=YWJ2amlvajRiNHh0" target="_blank"><i class="fab fa-instagram"></i></a>
                        <a class="btn btn-outline-secondary me-2 btn-md-square rounded-circle" href="https://www.tiktok.com/@artefax.id?_r=1&_t=ZS-91w6hQ7SJym" target="_blank"><i class="fab fa-tiktok"></i></a>
                        <a class="btn btn-outline-secondary me-2 btn-md-square rounded-circle" href="https://youtube.com/@artefaxmedia-xn6zm?si=2HWgVISPqwb-zoVg" target="_blank"><i class="fab fa-youtube"></i></a>
                        <a class="btn btn-outline-secondary btn-md-square rounded-circle" href="https://wa.me/6289653521667" target="_blank"><i class="fab fa-whatsapp"></i></a>
                    </div>
                </div>
            </div>
        </div>
        <div class="row g-5">
            <div class="col-lg-3 col-md-6">
                <div class="footer-item">
                    <h4 class="text-light mb-3">Tentang Kami</h4>
                    <p class="mb-4">Artefax Media menyediakan layanan sewa alat multimedia terlengkap dan jasa dokumentasi profesional untuk menunjang kesuksesan acara Anda.</p>
                    <a href="Services.php" class="btn border-secondary py-2 px-4 rounded-pill text-info">Lihat Layanan</a>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="d-flex flex-column text-start footer-item">
                    <h4 class="text-light mb-3">Menu Cepat</h4>
                    <a class="btn-link text-info" href="../index.php">Landing Page</a>
                    <a class="btn-link text-info" href="shop.php">Shop</a>
                    <a class="btn-link text-info" href="Services.php">Home</a>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="d-flex flex-column text-start footer-item">
                    <h4 class="text-light mb-3">Akun Saya</h4>
                    <a class="btn-link text-info" href="../View/profil.php">Profil</a>
                    <a class="btn-link text-info" href="cart.php">Keranjang</a>
                    <a class="btn-link text-info" href="../RiwayatBooking.php">Riwayat Booking</a>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="footer-item">
                    <h4 class="text-light mb-3">Kontak</h4>
                    <p>Alamat: Jember, Jawa Timur</p>
                    <p>Email: artefaxm@gmail.com</p>
                    <p>WhatsApp: +62 896-5352-1667</p>
                    <p class="mt-3 mb-0">Pembayaran: Transfer Bank (BCA/BRI)</p>
                    <img src="img/pembayaran.png" class="img-fluid" alt="Metode Pembayaran" style="margin-top: 10px;">
                </div>
            </div>
        </div>
    </div>
</div>

    <!-- MODAL & CART -->
    <div class="modal fade" id="productModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header border-0 bg-light">
                    <h5 class="modal-title fw-bold">Detail Produk</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <img id="modalImg" src="" class="img-fluid rounded shadow w-100" style="height:350px; object-fit:cover;">
                        </div>
                        <div class="col-md-6">
                            <h3 id="modalName" class="fw-bold mb-2"></h3>
                            <h4 class="fw-bold mb-4 text-warning" id="modalPrice"></h4>
                            <p id="modalDesc" class="text-muted mb-4"></p>
                            <div class="bg-light p-3 rounded border mb-4">
                                <label class="fw-bold small mb-2 text-primary">Tanggal Sewa:</label>
                                <input type="date" id="modalDateInput" class="form-control fw-bold text-center border-primary" value="<?= $today ?>">
                            </div>
                            <input type="hidden" id="modalId">
                            <input type="hidden" id="modalType">
                            <div class="d-flex gap-3">
                                <input type="number" id="modalQty" class="form-control w-25 text-center fw-bold" value="1" min="1">
                                <button id="btnAddToCart" class="btn btn-primary w-100 fw-bold text-white shadow-sm">Masuk Keranjang</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if (file_exists(__DIR__ . "/components/cart_modal.php")) include __DIR__ . "/components/cart_modal.php"; ?>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    $(document).ready(function() {
        $(document).on('click', '.openDetailBtn', function(e){
            e.preventDefault(); 
            const btn = $(this);
            $('#modalImg').attr('src', btn.data('img')); 
            $('#modalName').text(btn.data('name')); 
            $('#modalDesc').text(btn.data('desc')); 
            $('#modalPrice').text(new Intl.NumberFormat('id-ID',{style:'currency',currency:'IDR',minimumFractionDigits:0}).format(btn.data('price')));
            $('#modalId').val(btn.data('id')); 
            $('#modalType').val(btn.data('tipe'));
            $('#modalDateInput').val('<?= $today ?>');
            new bootstrap.Modal(document.getElementById('productModal')).show();
        });

        $('#btnAddToCart').click(function(e){
            e.preventDefault(); 
            const btn = $(this);
            const d = $('#modalDateInput').val();
            if(!d){ alert('Pilih tanggal sewa!'); return; }
            btn.prop('disabled',true).html('Loading...');
            $.ajax({
                url: 'root/cart_add.php',
                method: 'POST',
                data: {
                    id: $('#modalId').val(),
                    type: $('#modalType').val(),
                    qty: $('#modalQty').val(),
                    date: d,
                    name: $('#modalName').text(),
                    price: $('#modalPrice').text().replace(/[^0-9]/g,'')
                },
                dataType: 'json',
                success: function(r){
                    if(r.status==='success'){
                        $('.cart-badge').text(r.cart_count);
                        btn.html('Berhasil!');
                        setTimeout(() => {
                            bootstrap.Modal.getInstance(document.getElementById('productModal')).hide();
                            btn.prop('disabled',false).html('Masuk Keranjang');
                        }, 800);
                    } else {
                        alert(r.message || 'Gagal menambah ke keranjang');
                        btn.prop('disabled',false).html('Masuk Keranjang');
                    }
                },
                error: function(){
                    alert('Gagal terhubung ke server');
                    btn.prop('disabled',false).html('Masuk Keranjang');
                }
            });
        });
    });
    </script>
</body>
</html>