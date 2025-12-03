<?php
// shop.php - FINAL VERSION
session_start();
require_once __DIR__ . '/../config/koneksi.php';
include __DIR__ . "/components/cart_modal.php";
include __DIR__ . "/components/cart_script.php";

$db = new Database();
$conn = $db->getConnection();
if (!$conn) die("Koneksi database gagal.");

// AMBIL PARAMETER FILTER
$q = trim($_GET['q'] ?? '');
$type = $_GET['type'] ?? '';
$kategori = $_GET['kategori'] ?? '';
$sort = $_GET['sort'] ?? 'newest';
$featured = $_GET['featured'] ?? '';
$min_price = is_numeric($_GET['min_price'] ?? '') ? (float)$_GET['min_price'] : null;
$max_price = is_numeric($_GET['max_price'] ?? '') ? (float)$_GET['max_price'] : null;

function esc($conn, $v) { return $conn->real_escape_string($v); }

// BUILD WHERE CONDITIONS
$whereAlat = ["LOWER(AlatStatus) IN ('aktif','bestseller','tersedia')"];
$wherePaket = ["PaketStatus IN ('aktif','Bestseller','bestseller')"];

if ($q !== '') {
    $s = esc($conn, $q);
    $whereAlat[] = "(AlatNama LIKE '%$s%' OR AlatDeskripsi LIKE '%$s%')";
    $wherePaket[] = "(PaketNama LIKE '%$s%' OR PaketDeskripsi LIKE '%$s%')";
}

if ($type === 'alat') $wherePaket[] = "0";
elseif ($type === 'paket') $whereAlat[] = "0";

if ($kategori !== '') {
    $k = esc($conn, $kategori);
    $whereAlat[] = "AlatKategori = '$k'";
    $wherePaket[] = "PaketKategori = '$k'";
}

if ($min_price !== null) {
    $whereAlat[] = "AlatHarga >= " . (float)$min_price;
    $wherePaket[] = "PaketHarga >= " . (float)$min_price;
}
if ($max_price !== null) {
    $whereAlat[] = "AlatHarga <= " . (float)$max_price;
    $wherePaket[] = "PaketHarga <= " . (float)$max_price;
}

if ($featured === '1') {
    $whereAlat[] = "LOWER(AlatStatus) = 'bestseller'";
    $wherePaket[] = "LOWER(PaketStatus) = 'bestseller'";
}

$whereAlatStr = count($whereAlat) ? "WHERE " . implode(' AND ', $whereAlat) : "";
$wherePaketStr = count($wherePaket) ? "WHERE " . implode(' AND ', $wherePaket) : "";

// QUERY UNION
$sql = "
    SELECT IDAlat AS id, 'alat' AS tipe, AlatNama AS name, AlatKategori AS kategori,
           AlatDeskripsi AS description, AlatHarga AS price, AlatDirGbr AS image, AlatStatus AS status, CreatedAt as created_at
    FROM alat $whereAlatStr
    UNION ALL
    SELECT IDPaket AS id, 'paket' AS tipe, PaketNama AS name, PaketKategori AS kategori,
           PaketDeskripsi AS description, PaketHarga AS price, PaketDirGbr AS image, PaketStatus AS status, CreatedAt as created_at
    FROM paketjasa $wherePaketStr
";

if ($sort === 'price_asc') $sql .= " ORDER BY price ASC";
elseif ($sort === 'price_desc') $sql .= " ORDER BY price DESC";
elseif ($sort === 'name') $sql .= " ORDER BY name ASC";
else $sql .= " ORDER BY created_at DESC, id DESC";

$res = $conn->query($sql);
$products = [];
if ($res) {
    while ($row = $res->fetch_assoc()) $products[] = $row;
    $res->free();
}

// AMBIL KATEGORI
$alatSub = [];
$paketSub = [];
$res = $conn->query("SELECT AlatKategori, COUNT(*) AS cnt FROM alat WHERE AlatKategori IS NOT NULL AND AlatKategori <> '' GROUP BY AlatKategori");
if ($res) {
    while ($r = $res->fetch_assoc()) $alatSub[$r['AlatKategori']] = (int)$r['cnt'];
    $res->free();
}
$res = $conn->query("SELECT PaketKategori, COUNT(*) AS cnt FROM paketjasa WHERE PaketKategori IS NOT NULL AND PaketKategori <> '' GROUP BY PaketKategori");
if ($res) {
    while ($r = $res->fetch_assoc()) $paketSub[$r['PaketKategori']] = (int)$r['cnt'];
    $res->free();
}

function rupiah($n) { return 'Rp ' . number_format((float)$n, 0, ',', '.'); }
$cart_count = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Shop - Artefax</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600&family=Raleway:wght@600;800&display=swap" rel="stylesheet"> 
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css"/>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="lib/lightbox/css/lightbox.min.css" rel="stylesheet">
    <link href="lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body>

<!-- Spinner -->
<div id="spinner" class="show w-100 vh-100 bg-white position-fixed translate-middle top-50 start-50 d-flex align-items-center justify-content-center">
    <div class="spinner-grow text-primary" role="status"></div>
</div>

<!-- Navbar -->
<div class="container-fluid fixed-top">
    <div class="container px-0">
        <nav class="navbar navbar-light bg-white navbar-expand-xl">
            <a href="../index.php" class="navbar-brand"><h1 class="text-primary display-6">Artefax</h1></a>
            <button class="navbar-toggler py-2 px-3" type="button" data-bs-toggle="collapse" data-bs-target="#navbarCollapse">
                <span class="fa fa-bars text-primary"></span>
            </button>
            <div class="collapse navbar-collapse bg-white" id="navbarCollapse">
                <div class="navbar-nav mx-auto">
                    <a href="../index.php" class="nav-item nav-link">Home</a>
                    <a href="Services.php" class="nav-item nav-link">Services</a>
                    <a href="shop.php" class="nav-item nav-link active">Shop</a>
                    <a href="checkout.php" class="nav-item nav-link">Checkout</a>
                    <a href="contact.php" class="nav-item nav-link">Contact</a>
                </div>
                <div class="d-flex m-3 me-0">
                    <button class="btn-search btn border border-secondary btn-md-square rounded-circle bg-white me-4" data-bs-toggle="modal" data-bs-target="#searchModal">
                        <i class="fas fa-search text-primary"></i>
                    </button>
                    <a href="#" class="position-relative me-4 my-auto" data-bs-toggle="modal" data-bs-target="#cartModal">
                        <i class="fa fa-shopping-bag fa-2x"></i>
                        <span class="position-absolute bg-secondary rounded-circle d-flex align-items-center justify-content-center text-dark px-1" style="top: -5px; left: 15px; height: 20px; min-width: 20px;"><?= $cart_count ?></span>
                    </a>
                    <a href="#" class="my-auto"><i class="fas fa-user fa-2x"></i></a>
                </div>
            </div>
        </nav>
    </div>
</div>

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
                    <input type="text" name="q" class="form-control p-3" placeholder="Cari produk..." value="<?= htmlspecialchars($q) ?>">
                    <button class="input-group-text p-3" type="submit"><i class="fa fa-search"></i></button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Header -->
<div class="container-fluid page-header py-5" style="margin-top:90px;">
    <h1 class="text-center text-white display-6">Shop</h1>
    <ol class="breadcrumb justify-content-center mb-0">
        <li class="breadcrumb-item"><a href="../index.php">Home</a></li>
        <li class="breadcrumb-item active text-white">Shop</li>
    </ol>
</div>

<!-- Shop Content -->
<div class="container-fluid fruite py-5">
    <div class="container py-5">
        <div class="mb-4">
            <h1 class="mb-0">All Products</h1>
            <p class="text-muted">Menampilkan alat & paket jasa. Klik produk untuk lihat detail dan tambah ke keranjang.</p>
        </div>

        <!-- Search & Sort -->
        <div class="row g-4 mb-3">
            <div class="col-xl-3">
                <form method="GET" action="shop.php" class="input-group">
                    <input type="text" name="q" class="form-control p-3" placeholder="Search keywords..." value="<?= htmlspecialchars($q) ?>">
                    <input type="hidden" name="type" value="<?= htmlspecialchars($type) ?>">
                    <button class="input-group-text p-3"><i class="fa fa-search"></i></button>
                </form>
            </div>
            <div class="col-6"></div>
            <div class="col-xl-3">
                <div class="bg-light ps-3 py-3 rounded d-flex justify-content-between align-items-center">
                    <label for="sortSelect" class="mb-0 me-2">Sort:</label>
                    <form method="GET" action="shop.php" class="m-0 d-flex align-items-center">
                        <input type="hidden" name="q" value="<?= htmlspecialchars($q) ?>">
                        <select id="sortSelect" name="sort" class="form-select form-select-sm border-0 bg-light" onchange="this.form.submit();">
                            <option value="newest" <?= $sort==='newest' ? 'selected' : '' ?>>Newest</option>
                            <option value="price_asc" <?= $sort==='price_asc' ? 'selected' : '' ?>>Price: Low to High</option>
                            <option value="price_desc" <?= $sort==='price_desc' ? 'selected' : '' ?>>Price: High to Low</option>
                            <option value="name" <?= $sort==='name' ? 'selected' : '' ?>>Name</option>
                        </select>
                    </form>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- SIDEBAR -->
            <div class="col-lg-3">
                <div class="row g-4">
                    <div class="col-lg-12">
                        <h4>Categories</h4>
                        <div class="mb-2">
                            <a href="shop.php" class="btn btn-sm <?= $type==='' ? 'btn-primary' : 'btn-outline-primary' ?>">All</a>
                            <a href="shop.php?type=alat" class="btn btn-sm <?= $type==='alat' ? 'btn-primary' : 'btn-outline-primary' ?>">Alat</a>
                            <a href="shop.php?type=paket" class="btn btn-sm <?= $type==='paket' ? 'btn-primary' : 'btn-outline-primary' ?>">Paket Jasa</a>
                        </div>

                        <?php if ($type === '' || $type === 'alat'): ?>
                            <h6 class="mt-3">Alat</h6>
                            <ul class="list-unstyled fruite-categorie">
                                <?php if (!empty($alatSub)): foreach ($alatSub as $sub => $cnt): ?>
                                    <li class="mb-1 d-flex justify-content-between">
                                        <a href="shop.php?type=alat&kategori=<?= urlencode($sub) ?>"><i class="fas fa-camera me-2"></i><?= htmlspecialchars($sub) ?></a>
                                        <span>(<?= $cnt ?>)</span>
                                    </li>
                                <?php endforeach; else: ?>
                                    <li class="text-muted">Tidak ada subkategori.</li>
                                <?php endif; ?>
                            </ul>
                        <?php endif; ?>

                        <?php if ($type === '' || $type === 'paket'): ?>
                            <h6 class="mt-3">Paket Jasa</h6>
                            <ul class="list-unstyled fruite-categorie">
                                <?php if (!empty($paketSub)): foreach ($paketSub as $sub => $cnt): ?>
                                    <li class="mb-1 d-flex justify-content-between">
                                        <a href="shop.php?type=paket&kategori=<?= urlencode($sub) ?>"><i class="fas fa-briefcase me-2"></i><?= htmlspecialchars($sub) ?></a>
                                        <span>(<?= $cnt ?>)</span>
                                    </li>
                                <?php endforeach; else: ?>
                                    <li class="text-muted">Tidak ada subkategori.</li>
                                <?php endif; ?>
                            </ul>
                        <?php endif; ?>
                    </div>

                    <!-- Price Filter -->
                    <div class="col-lg-12">
                        <div class="mb-3">
                            <h4 class="mb-2">Price</h4>
                            <form method="GET" action="shop.php">
                                <input type="hidden" name="type" value="<?= htmlspecialchars($type) ?>">
                                <input type="hidden" name="q" value="<?= htmlspecialchars($q) ?>">
                                <div class="d-flex gap-2 mb-2">
                                    <input type="number" name="min_price" class="form-control" placeholder="Min" value="<?= htmlspecialchars($min_price ?? '') ?>">
                                    <input type="number" name="max_price" class="form-control" placeholder="Max" value="<?= htmlspecialchars($max_price ?? '') ?>">
                                </div>
                                <button class="btn btn-sm btn-outline-primary w-100" type="submit">Filter Price</button>
                            </form>
                        </div>
                    </div>

                    <!-- Status Filter -->
                    <div class="col-lg-12">
                        <div class="mb-3">
                            <h4>Status</h4>
                            <form method="GET" action="shop.php">
                                <input type="hidden" name="type" value="<?= htmlspecialchars($type) ?>">
                                <div class="mb-2">
                                    <input type="radio" id="f_all" name="featured" value="" <?= $featured !== '1' ? 'checked' : '' ?>>
                                    <label for="f_all"> Semua</label>
                                </div>
                                <div class="mb-2">
                                    <input type="radio" id="f_bs" name="featured" value="1" <?= $featured === '1' ? 'checked' : '' ?>>
                                    <label for="f_bs"> Bestsellers</label>
                                </div>
                                <button class="btn btn-sm btn-outline-primary mt-2" type="submit">Apply</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

<!-- PRODUCT GRID - MODERN DESIGN -->
<div class="col-lg-9">
    <div class="row g-4 justify-content-center">
        <?php if (empty($products)): ?>
            <div class="col-12 text-center py-5">
                <i class="fa fa-search fa-3x text-muted mb-3"></i>
                <h5>Tidak ada produk ditemukan.</h5>
                <p class="text-muted">Coba kata kunci lain atau filter berbeda</p>
            </div>
        <?php else: ?>
            <?php foreach ($products as $p): 
                $imgFile = $p['image'] ?? '';
                $placeholder = 'img/noimage.png';
                $imgUrl = $placeholder;
                
                if (!empty($imgFile)) {
                    $mainPath = 'img/produk/' . $imgFile;
                    $fullPath = __DIR__ . '/' . $mainPath;
                    if (file_exists($fullPath)) {
                        $imgUrl = $mainPath;
                    }
                }
            ?>
            
            <div class="col-md-6 col-lg-6 col-xl-4">
                <div class="product-card">
                    
                    <!-- Image Section -->
                    <div class="product-image-wrapper">
                        <img src="<?= $imgUrl ?>" 
                             class="product-image" 
                             alt="<?= htmlspecialchars($p['name']) ?>"
                             onerror="this.onerror=null; this.src='img/noimage.png';">
                        
                        <!-- Badges -->
                        <div class="product-badges">
                            <?php if (strtolower($p['status']) === 'bestseller'): ?>
                                <span class="badge badge-bestseller">
                                    <i class="fa fa-star"></i> Bestseller
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Quick View Button (Optional) -->
                        <div class="product-overlay">
                            <button class="btn btn-light btn-sm rounded-circle openDetailBtn"
                                    data-id="<?= htmlspecialchars($p['id']) ?>"
                                    data-tipe="<?= htmlspecialchars($p['tipe']) ?>"
                                    data-name="<?= htmlspecialchars($p['name']) ?>"
                                    data-kat="<?= htmlspecialchars($p['kategori']) ?>"
                                    data-desc="<?= htmlspecialchars($p['description']) ?>"
                                    data-price="<?= htmlspecialchars($p['price']) ?>"
                                    data-img="<?= $imgUrl ?>"
                                    title="Lihat Detail">
                                <i class="fa fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Content Section -->
                    <div class="product-content">
                        
                        <!-- Category Badge -->
                        <div class="product-category">
                            <span class="badge badge-category">
                                <?= htmlspecialchars($p['tipe']) ?>
                            </span>
                        </div>
                        
                        <!-- Product Name -->
                        <h5 class="product-name">
                            <?= htmlspecialchars($p['name']) ?>
                        </h5>
                        
                        <!-- Spacer -->
                        <div class="product-spacer"></div>
                        
                        <!-- Price & Button -->
                        <div class="product-footer">
                            <div class="product-price">
                                <?= rupiah($p['price']) ?>
                            </div>
                            <button class="btn btn-primary btn-sm openDetailBtn"
                                    data-id="<?= htmlspecialchars($p['id']) ?>"
                                    data-tipe="<?= htmlspecialchars($p['tipe']) ?>"
                                    data-name="<?= htmlspecialchars($p['name']) ?>"
                                    data-kat="<?= htmlspecialchars($p['kategori']) ?>"
                                    data-desc="<?= htmlspecialchars($p['description']) ?>"
                                    data-price="<?= htmlspecialchars($p['price']) ?>"
                                    data-img="<?= $imgUrl ?>">
                                Detail <i class="fa fa-arrow-right ms-1"></i>
                            </button>
                        </div>
                        
                    </div>
                </div>
            </div>
            
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>


<!-- MODAL DETAIL PRODUK -->
<div class="modal fade" id="productModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content p-3">
            <button type="button" class="btn-close position-absolute top-0 end-0 m-3" data-bs-dismiss="modal"></button>
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

<style>
    
/* ============================================
   PRODUCT CARD - FIXED LAYOUT
   Tambahkan CSS ini ke css/style.css
   ============================================ */


.product-card {
    background: #fff;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
    height: 100%;
    display: flex;
    flex-direction: column;
}

.product-card:hover {
    box-shadow: 0 8px 24px rgba(0,0,0,0.12);
    transform: translateY(-4px);
}

/* Image Section */
.product-image-wrapper {
    position: relative;
    overflow: hidden;
    height: 250px;
    background: #f8f9fa;
}

.product-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: center;
    transition: transform 0.4s ease;
}

.product-card:hover .product-image {
    transform: scale(1.08);
}

/* Badges */
.product-badges {
    position: absolute;
    top: 12px;
    left: 12px;
    z-index: 2;
}

.badge-bestseller {
    background: linear-gradient(135deg, #ffd700, #ffed4e);
    color: #000;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    box-shadow: 0 2px 8px rgba(255,215,0,0.3);
}

/* Overlay Button */
.product-overlay {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    opacity: 0;
    transition: opacity 0.3s ease;
}

.product-card:hover .product-overlay {
    opacity: 1;
}

.product-overlay .btn {
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

/* Content Section */
.product-content {
    padding: 20px;
    display: flex;
    flex-direction: column;
    flex-grow: 1;
}

.product-category {
    margin-bottom: 8px;
}

.badge-category {
    background-color: #e9ecef;
    color: #495057;
    padding: 4px 10px;
    border-radius: 4px;
    font-size: 0.7rem;
    font-weight: 500;
    text-transform: uppercase;
}

/* Product Name - Max 2 lines */
.product-name {
    font-size: 1rem;
    font-weight: 600;
    color: #212529;
    margin: 0 0 12px 0;
    height: 3rem;
    overflow: hidden;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    line-height: 1.5rem;
}

/* Spacer to push footer down */
.product-spacer {
    flex-grow: 1;
}

/* Footer */
.product-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    margin-top: auto;
    padding-top: 12px;
    border-top: 1px solid #e9ecef;
}

.product-price {
    font-size: 1.25rem;
    font-weight: 700;
    color: #81c408;
}

.product-footer .btn {
    padding: 8px 16px;
    font-size: 0.875rem;
    border-radius: 6px;
    font-weight: 500;
    white-space: nowrap;
}

/* Responsive */
@media (max-width: 1199px) {
    .product-image-wrapper {
        height: 220px;
    }
}

@media (max-width: 767px) {
    .product-image-wrapper {
        height: 200px;
    }
    
    .product-name {
        font-size: 0.95rem;
        height: 2.8rem;
    }
    
    .product-price {
        font-size: 1.1rem;
    }
    
    .product-footer .btn {
        font-size: 0.8rem;
        padding: 6px 12px;
    }
}
</style>

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

<!-- Scripts -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="lib/easing/easing.min.js"></script>
<script src="lib/waypoints/waypoints.min.js"></script>
<script src="lib/lightbox/js/lightbox.min.js"></script>
<script src="lib/owlcarousel/owl.carousel.min.js"></script>

<script>
// Popup Detail
$(document).on('click', '.openDetailBtn', function(){
    const btn = $(this);
    $('#modalImg').attr('src', btn.data('img'));
    $('#modalName').text(btn.data('name'));
    $('#modalCat').text(btn.data('kat'));
    $('#modalDesc').text(btn.data('desc'));
    const price = btn.data('price');
    $('#modalPrice').text(new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(price));
    $('#modalQty').val(1);
    $('#btnAddToCart').data('id', btn.data('id'));
    $('#btnAddToCart').data('type', btn.data('tipe'));
    $('#btnAddToCart').data('name', btn.data('name'));
    $('#btnAddToCart').data('price', btn.data('price'));
    $('#productModal').modal('show');
});

// Add to Cart
$('#btnAddToCart').click(function(){
    const id = $(this).data('id');
    const type = $(this).data('type');
    const name = $(this).data('name');
    const price = $(this).data('price');
    const qty = parseInt($('#modalQty').val()) || 1;

    // Efek tombol langsung berubah biar user tahu diklik
    const btn = $(this);
    const originalText = btn.html();
    btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin me-2"></i>Menambahkan...');

    fetch('root/cart_add.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `id=${id}&type=${type}&name=${encodeURIComponent(name)}&price=${price}&qty=${qty}`
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            // Update angka keranjang langsung
            const cartBadge = $('.position-absolute.bg-secondary');
            cartBadge.text(data.cart_count);
            
            // Efek angka naik (opsional, keren)
            cartBadge.addClass('animate__animated animate__bounceIn');
            setTimeout(() => cartBadge.removeClass('animate__animated animate__bounceIn'), 600);

            // Tutup modal
            $('#productModal').modal('hide');

            // Optional: kasih feedback halus tanpa alert
            // Bisa ditambahin toast kecil nanti kalau mau
        } else {
            alert('Gagal menambah ke keranjang: ' + (data.message || 'Coba lagi'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Gagal terhubung ke server!');
    })
    .finally(() => {
        // Kembalikan tombol
        btn.prop('disabled', false).html(originalText);
    });
});
</script>

<script src="js/main.js"></script>
</body>
</html>