<?php
session_start();
require_once __DIR__ . '/../config/koneksi.php';
$db = new Database();
$conn = $db->getConnection();
if (!$conn) die("Koneksi database gagal.");

// TANGGAL HARI INI (real-time device/server)
$today = date('Y-m-d');

// Filter tanggal
$filter_date = $_GET['date'] ?? $today;
if ($filter_date < $today) {
    $filter_date = $today;
}

// --- 1. FILTER DATA ---
$q = trim($_GET['q'] ?? '');
$type = $_GET['type'] ?? '';
$kategori = $_GET['kategori'] ?? '';
$sort = $_GET['sort'] ?? 'newest';

function esc($conn, $v) { return $conn->real_escape_string($v); }

// =================== HITUNG TOTAL BOOKING PAKET JASA YANG OVERLAP DENGAN TANGGAL DIPILIH ===================
$paketBookingCount = 0;
$stmtGlobal = $conn->prepare("
    SELECT COUNT(*)
    FROM booking_detail bd
    INNER JOIN booking b ON bd.IDBooking = b.IDBooking
    WHERE bd.BkgDetailJenis = 'Paket Jasa'
      AND b.BkgStatus IN ('Diterima', 'Selesai')
      AND ? BETWEEN DATE(b.BkgTglMulai) AND DATE(b.BkgTglSelesai)
");
$stmtGlobal->bind_param("s", $filter_date);
$stmtGlobal->execute();
$stmtGlobal->bind_result($paketBookingCount);
$stmtGlobal->fetch();
$stmtGlobal->close();

$isPaketFullToday = ($paketBookingCount >= 2); // Maksimal 2 paket jasa aktif di periode yang overlap
// ==================================================================================================

// --- 2. QUERY CONDITIONS ---
$whereAlat = ["AlatStatus != 'Nonaktif'"];
$wherePaket = ["PaketStatus != 'Nonaktif'"];

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

$whereAlatStr = count($whereAlat) ? "WHERE " . implode(' AND ', $whereAlat) : "";
$wherePaketStr = count($wherePaket) ? "WHERE " . implode(' AND ', $wherePaket) : "";

// --- 3. QUERY UTAMA ---
$sql = "
    SELECT
        IDAlat AS id, 'alat' AS tipe, AlatNama AS name, AlatKategori AS kategori,
        AlatDeskripsi AS description, AlatHarga AS price, AlatDirGbr AS image,
        AlatStatus AS status_admin, AlatStok AS stock_db, CreatedAt as created_at,
        (
            SELECT COUNT(*)
            FROM booking_detail bd
            JOIN booking b ON bd.IDBooking = b.IDBooking
            WHERE bd.IDAlat = alat.IDAlat
              AND b.BkgStatus IN ('Diterima','Selesai')
              AND ? BETWEEN DATE(b.BkgTglMulai) AND DATE(b.BkgTglSelesai)
        ) AS booked_count
    FROM alat $whereAlatStr
    UNION ALL
    SELECT
        IDPaket AS id, 'paket' AS tipe, PaketNama AS name, PaketKategori AS kategori,
        PaketDeskripsi AS description, PaketHarga AS price, PaketDirGbr AS image,
        PaketStatus AS status_admin, 999 AS stock_db, CreatedAt as created_at,
        0 AS booked_count
    FROM paketjasa $wherePaketStr
";

if ($sort === 'price_asc') $sql .= " ORDER BY price ASC";
elseif ($sort === 'price_desc') $sql .= " ORDER BY price DESC";
elseif ($sort === 'name') $sql .= " ORDER BY name ASC";
else $sql .= " ORDER BY created_at DESC, id DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $filter_date);
$stmt->execute();
$res = $stmt->get_result();
$products = [];
while ($row = $res->fetch_assoc()) {
    $products[] = $row;
}
$stmt->close();

// SIDEBAR COUNTS
$alatSub = []; $paketSub = [];
$res = $conn->query("SELECT AlatKategori, COUNT(*) AS cnt FROM alat WHERE AlatStatus != 'Nonaktif' GROUP BY AlatKategori");
if($res) while ($r = $res->fetch_assoc()) $alatSub[$r['AlatKategori']] = (int)$r['cnt'];
$res = $conn->query("SELECT PaketKategori, COUNT(*) AS cnt FROM paketjasa WHERE PaketStatus != 'Nonaktif' GROUP BY PaketKategori");
if($res) while ($r = $res->fetch_assoc()) $paketSub[$r['PaketKategori']] = (int)$r['cnt'];

function rupiah($n) { return 'Rp ' . number_format((float)$n, 0, ',', '.'); }
$cart_count = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Shop - Artefax</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="../assets/img/logo Artefax1.png" rel="icon" />
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&family=Raleway:wght@600;800&display=swap" rel="stylesheet">
    <link href="https://use.fontawesome.com/releases/v5.15.4/css/all.css" rel="stylesheet"/>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css?v=<?= time() ?>" rel="stylesheet">
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
                    <a href="shop.php" class="nav-item nav-link active">Shop</a>
                </div>
                <div class="nav-icon-wrapper m-3 me-0">
                    <a href="#" class="nav-icon-btn" data-bs-toggle="modal" data-bs-target="#cartModal">
                        <i class="fa fa-shopping-bag"></i>
                        <span id="jmlKeranjang" class="cart-badge"><?= $cart_count ?></span>
                    </a>
                    <a href="../View/profil.php" class="nav-icon-btn"><i class="fas fa-user"></i></a>
                </div>
            </div>
        </div>
    </nav>
</div>

<div class="container-fluid page-header mb-5">
    <div class="container text-center">
        <h1 class="display-5 text-white fw-bold mb-2">Shop & Booking</h1>
        <p class="text-white-50 mb-0">Cek ketersediaan alat dan paket jasa kami</p>
    </div>
</div>

<div class="container py-5">
    <div class="row g-4 mb-5">
        <div class="col-12">
            <div class="bg-white p-4 rounded shadow-sm border border-light">
                <form method="GET" action="shop.php" class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label fw-bold text-muted small">Cari Produk</label>
                        <input type="text" name="q" class="form-control bg-light border-0" placeholder="Kamera, Dokumentasi..." value="<?= htmlspecialchars($q) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold text-info small">Tanggal Sewa:</label>
                        <input type="date" id="bookingDate" name="date" class="form-control border-info fw-bold text-info" 
                               value="<?= htmlspecialchars($filter_date) ?>" 
                               min="<?= $today ?>" required onchange="this.form.submit()">
                    </div>
                    <input type="hidden" name="type" value="<?= htmlspecialchars($type) ?>">
                    <div class="col-md-3">
                        <label class="form-label fw-bold text-muted small">Urutkan</label>
                        <select name="sort" class="form-select bg-light border-0" onchange="this.form.submit()">
                            <option value="newest" <?= $sort==='newest'?'selected':'' ?>>Terbaru</option>
                            <option value="price_asc" <?= $sort==='price_asc'?'selected':'' ?>>Harga Terendah</option>
                            <option value="price_desc" <?= $sort==='price_desc'?'selected':'' ?>>Harga Tertinggi</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-info w-100 fw-bold rounded-pill shadow-sm text-white">Cek</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-3">
            <div class="mb-4">
                <h4 class="mb-3 text-info fw-bold">Kategori</h4>
                <div class="list-group shadow-sm border-0 rounded-3 overflow-hidden">
                    <a href="shop.php?date=<?= $filter_date ?>" class="list-group-item list-group-item-action <?= $type===''?'active':'' ?>">Semua</a>
                    <a href="shop.php?type=alat&date=<?= $filter_date ?>" class="list-group-item list-group-item-action <?= $type==='alat'?'active':'' ?>">Alat Only</a>
                    <a href="shop.php?type=paket&date=<?= $filter_date ?>" class="list-group-item list-group-item-action <?= $type==='paket'?'active':'' ?>">Paket Jasa Only</a>
                </div>
            </div>
            
            <div class="d-none d-lg-block">
                <?php if ($type === '' || $type === 'alat'): ?>
                <div class="mb-3">
                    <h6 class="fw-bold text-muted">Kategori Alat</h6>
                    <ul class="list-unstyled ps-2 small">
                        <?php foreach($alatSub as $k => $c): ?>
                            <li class="mb-2"><a href="shop.php?type=alat&kategori=<?= urlencode($k) ?>&date=<?= $filter_date ?>" class="text-decoration-none text-dark d-flex justify-content-between align-items-center"><span><i class="fa fa-angle-right me-2 text-muted"></i><?= $k ?></span> <span class="badge bg-light text-info rounded-pill border border-info"><?= $c ?></span></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
                <?php if ($type === '' || $type === 'paket'): ?>
                <div class="mb-3">
                    <h6 class="fw-bold text-muted">Kategori Paket</h6>
                    <ul class="list-unstyled ps-2 small">
                        <?php foreach($paketSub as $k => $c): ?>
                            <li class="mb-2"><a href="shop.php?type=paket&kategori=<?= urlencode($k) ?>&date=<?= $filter_date ?>" class="text-decoration-none text-dark d-flex justify-content-between align-items-center"><span><i class="fa fa-angle-right me-2 text-muted"></i><?= $k ?></span> <span class="badge bg-light text-info rounded-pill border border-info"><?= $c ?></span></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-lg-9">
            <div class="alert alert-info py-2 mb-4 d-flex align-items-center bg-white shadow-sm border-0 rounded-3 border-start border-5 border-info">
                <i class="fa fa-info-circle me-3 text-info fa-2x"></i>
                <div>
                    <small class="text-muted d-block">Status Ketersediaan untuk tanggal:</small>
                    <strong class="text-info fs-5"><?= date('d F Y', strtotime($filter_date)) ?></strong>
                </div>
            </div>

            <div class="row g-4">
                <?php if (empty($products)): ?>
                    <div class="col-12 text-center py-5">
                        <i class="fa fa-search fa-3x text-muted mb-3"></i>
                        <h5>Tidak ada produk ditemukan.</h5>
                    </div>
                <?php else: ?>
                    <?php foreach ($products as $p):
                        $stokFisik = (int)$p['stock_db'];
                        $bookedCount = (int)$p['booked_count'];
                        $limitBookingHarian = 2;

                        $isStockEmpty = ($stokFisik <= 0);
                        $isDateFull = ($p['tipe'] === 'alat')
                                             ? ($bookedCount >= $limitBookingHarian)
                                             : $isPaketFullToday; // Pakai cek global overlap

                        $isUnavailable = ($isStockEmpty || $isDateFull);

                        $statusLabel = ""; $statusBadgeClass = "";
                        if ($isStockEmpty) {
                            $statusLabel = "HABIS";
                            $statusBadgeClass = "bg-secondary";
                        } elseif ($isDateFull) {
                            $statusLabel = "FULL";
                            $statusBadgeClass = "bg-secondary";
                        } elseif (strtolower($p['status_admin']) == 'bestseller') {
                            $statusLabel = "FAVORIT";
                            $statusBadgeClass = "badge-bestseller text-dark";
                        }
                        $imgUrl = (!empty($p['image']) && file_exists(__DIR__ . '/img/produk/' . $p['image']))
                                     ? 'img/produk/' . $p['image'] : 'img/noimage.png';
                    ?>
                    <div class="col-md-6 col-lg-6 col-xl-4">
                        <div class="product-card h-100 <?= $isUnavailable ? 'unavailable' : '' ?>">
                            <div class="product-image-wrapper">
                                <?php if ($statusLabel): ?>
                                    <div class="position-absolute top-0 start-0 p-2 z-index-2">
                                        <span class="badge <?= $statusBadgeClass ?> fw-bold shadow-sm">
                                            <?= $statusLabel == 'FAVORIT' ? 'FAVORIT' : $statusLabel ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                                <img src="<?= $imgUrl ?>" class="product-image" alt="<?= htmlspecialchars($p['name']) ?>">
                            </div>
                            <div class="p-3 d-flex flex-column flex-grow-1">
                                <div class="mb-2">
                                    <span class="chip-category tipe"><?= htmlspecialchars($p['tipe']) ?></span>
                                    <span class="chip-category"><?= htmlspecialchars($p['kategori']) ?></span>
                                </div>
                                
                                <h5 class="fw-bold text-dark mb-2" style="font-size: 1.1rem; height: 2.5em; overflow: hidden; line-height: 1.3;">
                                    <?= htmlspecialchars($p['name']) ?>
                                </h5>
                                
                                <div class="small mb-3">
                                    <?php if ($isUnavailable): ?>
                                        <span class="text-danger fw-bold">Tidak Tersedia</span>
                                    <?php else: ?>
                                        <span class="text-success fw-bold">Tersedia</span>
                                        <?php if ($p['tipe'] === 'paket'): ?>
                                            <span class="text-muted ms-1" style="font-size: 0.75rem;">
                                                (Slot tersisa: <?= 2 - $paketBookingCount ?>)
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted ms-1" style="font-size: 0.75rem;">
                                                (Slot: <?= $limitBookingHarian - $bookedCount ?>)
                                            </span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                                <div class="mt-auto pt-3 border-top d-flex justify-content-between align-items-center">
                                    <?php if ($isUnavailable): ?>
                                        <h5 class="price-disabled mb-0"><?= rupiah($p['price']) ?></h5>
                                        <button class="btn-disabled-fixed" disabled>
                                            <i class="fa fa-ban"></i> <?= $isStockEmpty ? 'Habis' : 'Penuh' ?>
                                        </button>
                                    <?php else: ?>
                                        <h5 class="product-price mb-0"><?= rupiah($p['price']) ?></h5>
                                        <button class="btn btn-outline-info fw-bold openDetailBtn"
                                            data-id="<?= $p['id'] ?>" data-tipe="<?= $p['tipe'] ?>" data-name="<?= htmlspecialchars($p['name']) ?>"
                                            data-price="<?= $p['price'] ?>" data-desc="<?= htmlspecialchars($p['description']) ?>"
                                            data-img="<?= $imgUrl ?>" data-date="<?= $filter_date ?>">
                                            Sewa
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

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
                        <div class="alert alert-light border d-flex align-items-center mb-3">
                            <i class="fa fa-calendar-alt fa-2x me-3 text-info"></i>
                            <div>
                                <small class="text-muted">Tanggal Booking:</small><br>
                                <strong id="modalDateDisplay" class="fs-5 text-dark"></strong>
                            </div>
                        </div>
                        <p id="modalDesc" class="text-muted mb-4"></p>
                        <input type="hidden" id="modalId">
                        <input type="hidden" id="modalType">
                        <input type="hidden" id="modalDateInput">
                        <div class="d-flex gap-3">
                            <input type="number" id="modalQty" class="form-control w-25 text-center fw-bold" value="1" min="1">
                            <button id="btnAddToCart" class="btn btn-info w-100 fw-bold text-white shadow-sm">Masuk Keranjang</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

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
        $('#modalPrice').text(new Intl.NumberFormat('id-ID', {style:'currency', currency:'IDR', minimumFractionDigits:0}).format(btn.data('price')));
        const rawDate = btn.data('date');
        if(rawDate) {
            $('#modalDateDisplay').text(new Date(rawDate).toLocaleDateString('id-ID'));
            $('#modalDateInput').val(rawDate);
        }
        $('#modalId').val(btn.data('id'));
        $('#modalType').val(btn.data('tipe'));
        new bootstrap.Modal(document.getElementById('productModal')).show();
    });

    $('#btnAddToCart').click(function(e){
        e.preventDefault();
        const btn = $(this);
        const data = {
            id: $('#modalId').val(),
            type: $('#modalType').val(),
            qty: $('#modalQty').val(),
            date: $('#modalDateInput').val(),
            name: $('#modalName').text(),
            price: $('#modalPrice').text().replace(/[^0-9]/g, '')
        };
        btn.prop('disabled', true).html('Loading...');
        $.ajax({
            url: 'root/cart_add.php',
            method: 'POST',
            data: data,
            dataType: 'json',
            success: function(r){
                if(r.status === 'success'){
                    $('#jmlKeranjang').text(r.cart_count);
                    btn.html('Berhasil!');
                    setTimeout(function(){
                        bootstrap.Modal.getInstance(document.getElementById('productModal')).hide();
                        btn.prop('disabled', false).html('Masuk Keranjang');
                    }, 800);
                } else {
                    alert(r.message);
                    btn.prop('disabled', false).html('Masuk Keranjang');
                }
            }
        });
    });
    
    $(document).on('click', 'a[href*="checkout.php"]', function(e) {
        e.preventDefault();
        var valDate = $('input[name="date"]').val() || new Date().toISOString().split('T')[0];
        window.location.href = 'checkout.php?date=' + valDate;
    });
});
</script>
</body>
</html>