<?php
// shop.php (letakkan di folder Paket/)
// Menampilkan produk gabungan dari tabel `alat` dan `paketjasa`
// Menggunakan koneksi mysqli (class Database di ../config/koneksi.php)

session_start();
include __DIR__ . '/../config/koneksi.php'; // sesuaikan jika lokasi koneksi berbeda

// ---------------------------
// KONEKSI
// ---------------------------
$db = new Database();
$conn = $db->getConnection();
if (!$conn) {
    die("Koneksi database gagal.");
}

// ---------------------------
// AMBIL PARAMETER FILTER / SORT / SEARCH
// ---------------------------
$q         = trim($_GET['q'] ?? '');           // kata kunci pencarian
$type      = $_GET['type'] ?? '';              // 'alat'|'paket'|'' (semua)
$kategori  = $_GET['kategori'] ?? '';          // subkategori
$sort      = $_GET['sort'] ?? 'newest';        // newest | price_asc | price_desc | name
$featured  = $_GET['featured'] ?? '';          // '1' jika hanya featured (bestseller)
$min_price = is_numeric($_GET['min_price'] ?? '') ? (float)$_GET['min_price'] : null;
$max_price = is_numeric($_GET['max_price'] ?? '') ? (float)$_GET['max_price'] : null;

// helper escape
function esc($conn, $v) {
    return $conn->real_escape_string($v);
}

// ---------------------------
// Siapkan bagian WHERE untuk UNION query
// Kita buat kondisi untuk masing-masing tabel, lalu gabungkan
// ---------------------------
$whereAlat = [];
$wherePaket = [];

// Hanya tampilkan yang status aktif/tersedia (jika kamu punya aturan)
// jika ingin tampil semua, comment baris berikut:
$whereAlat[] = "LOWER(AlatStatus) IN ('aktif','bestseller','tersedia')"; // fleksibel
$wherePaket[] = "PaketStatus IN ('aktif','aktif','Bestseller','bestseller','Bestseller')";

// pencarian
if ($q !== '') {
    $s = esc($conn, $q);
    $whereAlat[] = "(AlatNama LIKE '%$s%' OR AlatDeskripsi LIKE '%$s%')";
    $wherePaket[] = "(PaketNama LIKE '%$s%' OR PaketDeskripsi LIKE '%$s%')";
}

// filter tipe (alat / paket)
if ($type === 'alat') {
    // kosongkan kondisi paket supaya tidak diambil (hanya alat)
    $wherePaket[] = "0"; // always false
} elseif ($type === 'paket') {
    $whereAlat[] = "0"; // always false
}

// filter kategori (subkategori)
if ($kategori !== '') {
    $k = esc($conn, $kategori);
    $whereAlat[] = "AlatKategori = '$k'";
    $wherePaket[] = "PaketKategori = '$k'";
}

// filter harga
if ($min_price !== null) {
    $whereAlat[] = "AlatHarga >= " . (float)$min_price;
    $wherePaket[] = "PaketHarga >= " . (float)$min_price;
}
if ($max_price !== null) {
    $whereAlat[] = "AlatHarga <= " . (float)$max_price;
    $wherePaket[] = "PaketHarga <= " . (float)$max_price;
}

// featured (bestseller)
if ($featured === '1') {
    $whereAlat[] = "LOWER(AlatStatus) = 'bestseller'";
    $wherePaket[] = "LOWER(PaketStatus) = 'bestseller'";
}

// gabungkan where menjadi string
$whereAlatStr = count($whereAlat) ? "WHERE " . implode(' AND ', $whereAlat) : "";
$wherePaketStr = count($wherePaket) ? "WHERE " . implode(' AND ', $wherePaket) : "";

// ---------------------------
// QUERY UNION (gabungkan hasil dari dua tabel)
// Field diseragamkan: id, tipe, name, kategori, description, price, image, status, created_at
// ---------------------------
$sql = "
    SELECT IDAlat AS id, 'alat' AS tipe, AlatNama AS name, AlatKategori AS kategori,
           AlatDeskripsi AS description, AlatHarga AS price, AlatDirGbr AS image, AlatStatus AS status, CreatedAt as created_at
    FROM alat
    $whereAlatStr
    UNION ALL
    SELECT IDPaket AS id, 'paket' AS tipe, PaketNama AS name, PaketKategori AS kategori,
           PaketDeskripsi AS description, PaketHarga AS price, PaketDirGbr AS image, PaketStatus AS status, CreatedAt as created_at
    FROM paketjasa
    $wherePaketStr
";

// Sorting: karena UNION ALL gabungan, tambahkan ORDER BY di akhir SQL
if ($sort === 'price_asc') {
    $sql .= " ORDER BY price ASC";
} elseif ($sort === 'price_desc') {
    $sql .= " ORDER BY price DESC";
} elseif ($sort === 'name') {
    $sql .= " ORDER BY name ASC";
} else {
    // newest: urutkan berdasarkan created_at (jika ada), fallback id desc
    $sql .= " ORDER BY created_at DESC, id DESC";
}

// eksekusi query
$res = $conn->query($sql);
$products = [];
if ($res) {
    while ($row = $res->fetch_assoc()) {
        // pastikan tipe dan field ada
        $products[] = $row;
    }
    $res->free();
}

// ---------------------------
// Ambil daftar subkategori dinamis untuk sidebar (Alat + Paket)
// ---------------------------
$alatSub = [];
$paketSub = [];

// Alat kategori
$res = $conn->query("SELECT AlatKategori, COUNT(*) AS cnt FROM alat WHERE AlatKategori IS NOT NULL AND AlatKategori <> '' GROUP BY AlatKategori");
if ($res) {
    while ($r = $res->fetch_assoc()) $alatSub[$r['AlatKategori']] = (int)$r['cnt'];
    $res->free();
}
// Paket kategori
$res = $conn->query("SELECT PaketKategori, COUNT(*) AS cnt FROM paketjasa WHERE PaketKategori IS NOT NULL AND PaketKategori <> '' GROUP BY PaketKategori");
if ($res) {
    while ($r = $res->fetch_assoc()) $paketSub[$r['PaketKategori']] = (int)$r['cnt'];
    $res->free();
}

// ---------------------------
// Path gambar (relatif dari file ini)
// kita gunakan ../img/produk/ karena shop.php berada di folder Paket/
// ---------------------------
$imgBaseUrl = '../img/produk/';     // digunakan di tag <img src="...">
$imgBasePath = __DIR__ . '/../img/produk/'; // untuk cek file_exists
$placeholder = '../img/noimage.png'; // siapkan placeholder

// ---------------------------
// Fungsi format Rupiah (Indonesia)
// ---------------------------
function rupiah($n) {
    return 'Rp ' . number_format((float)$n, 0, ',', '.');
}
// cart
$cart = $_SESSION['cart'] ?? [];

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
        <div id="spinner" class="show w-100 vh-100 bg-white position-fixed translate-middle top-50 start-50  d-flex align-items-center justify-content-center">
            <div class="spinner-grow text-primary" role="status"></div>
        </div>
        <!-- Spinner End -->

 <!-- =============================== -->
    <!-- 🔹 5. NAVBAR DARI TEMPLATE     -->
    <!-- =============================== -->
   <!-- 🔸 Navbar Start -->
<div class="container-fluid fixed-top">
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
                    <a href="Services.php" class="nav-item nav-link ">Services</a>

                    <!-- ✅ Shop -->
                    <a href="shop.php" class="nav-item nav-link active">Shop</a>

                    <!-- ✅ checkout  -->
                          <a href="checkout.php" class="nav-item nav-link">Checkout</a>
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
                  <a href="#" class="position-relative me-4 my-auto" data-bs-toggle="modal" data-bs-target="#cartModal">
                  <i class="fa fa-shopping-bag fa-2x"></i>
                  <span class="position-absolute bg-secondary rounded-circle d-flex align-items-center justify-content-center text-dark px-1" style="top: -5px; left: 15px; height: 20px; min-width: 20px;" id="cart-count">
                        3
                  </span>
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

<!-- ========== Modal Search Start ========== -->
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
<!-- ========== Modal Search End ========== -->

<!-- Modal keranjang -->
<?php include 'cart_modal.php'; ?>

<!-- Script AJAX untuk update qty & hapus item -->
<script>
document.querySelectorAll('.qtyUpdate').forEach(el => {
    el.addEventListener('change', () => {
        let index = el.dataset.index;
        let qty = el.value;

        fetch('cart_update.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'index=' + index + '&qty=' + qty
        }).then(() => location.reload());
    });
});

document.querySelectorAll('.deleteItem').forEach(btn => {
    btn.addEventListener('click', () => {
        let index = btn.dataset.index;
        fetch('cart_delete.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'index=' + index
        }).then(() => location.reload());
    });
});
</script>

</body>
</html>


<!-- ========== Header / Breadcrumb ========== -->
<div class="container-fluid page-header py-5" style="margin-top:90px;">
  <h1 class="text-center text-white display-6">Shop</h1>
  <ol class="breadcrumb justify-content-center mb-0">
    <li class="breadcrumb-item"><a href="../index.php">Home</a></li>
    <li class="breadcrumb-item active text-white">Shop</li>
  </ol>
</div>

<!-- ========== Shop Content ========== -->
<div class="container-fluid fruite py-5">
  <div class="container py-5">
    <div class="mb-4">
      <h1 class="mb-0">All Products</h1>
      <p class="text-muted">Menampilkan alat & paket jasa. Klik produk untuk lihat detail dan tambah ke keranjang.</p>
    </div>

    <!-- Kontrol atas: search inline + sort -->
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
          <form id="sortForm" method="GET" action="shop.php" class="m-0 d-flex align-items-center">
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

          <!-- Price filter -->
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

          <!-- Featured -->
          <div class="col-lg-12">
            <div class="mb-3">
              <h4>Status</h4>
              <form method="GET" action="shop.php">
                <input type="hidden" name="type" value="<?= htmlspecialchars($type) ?>">
                <div class="mb-2">
                  <input type="radio" id="f_all" name="featured" value="" <?= $featured!== '1' ? 'checked' : '' ?>>
                  <label for="f_all"> Semua</label>
                </div>
                <div class="mb-2">
                  <input type="radio" id="f_bs" name="featured" value="1" <?= $featured=== '1' ? 'checked' : '' ?>>
                  <label for="f_bs"> Bestsellers</label>
                </div>
                <button class="btn btn-sm btn-outline-primary mt-2" type="submit">Apply</button>
              </form>
            </div>
          </div>

          <!-- Featured products preview (ambil 3 teratas dari query yang sudah kamu punya) -->
          <div class="col-lg-12">
            <h4 class="mb-3">Featured (Preview)</h4>
            <!-- contoh sederhana: kita ambil 3 produk bestseller -->
            <?php
            $fres = $conn->query("SELECT IDAlat AS id, AlatNama AS name, AlatHarga AS price, AlatDirGbr AS image, 'alat' AS tipe FROM alat WHERE LOWER(AlatStatus)='bestseller' LIMIT 3");
            while ($fr = $fres->fetch_assoc()):
              $fpImg = file_exists($imgBasePath . ($fr['image'] ?? '')) ? $imgBaseUrl . $fr['image'] : $placeholder;
            ?>
              <div class="d-flex align-items-center mb-3">
                <div class="rounded me-3" style="width:72px;height:72px;overflow:hidden">
                  <img src="<?= $fpImg ?>" class="img-fluid" style="width:100%;height:100%;object-fit:cover">
                </div>
                <div>
                  <div class="fw-bold mb-1"><?= htmlspecialchars($fr['name']) ?></div>
                  <div class="text-primary"><?= rupiah($fr['price']) ?></div>
                </div>
              </div>
            <?php endwhile; if ($fres) $fres->free(); ?>
          </div>
        </div>
      </div>
<!-- PRODUCT GRID -->
    <!-- PRODUCT GRID -->
<div class="col-lg-9">
  <div class="row g-4 justify-content-center">
    <?php if (empty($products)): ?>
      <div class="col-12 text-center py-5"><h5>Tidak ada produk ditemukan.</h5></div>
    <?php else: ?>

      <?php 
        // SETTING PATH GAMBAR FIX
        $imgBaseUrl  = "../img/paket/"; // URL gambar untuk <img>
        $imgBasePath = __DIR__ . "/../img/paket/"; // Lokasi folder untuk file_exists
        $placeholder = "../img/noimage.png"; // fallback
      ?>

      <?php foreach ($products as $p): ?>

        <?php
          // Nama kolom gambar HARUS bernama 'gambar'
          $imgFile = $p['gambar'] ?? ''; 
          $imgPath = $imgBasePath . $imgFile;
          $imgUrl  = (!empty($imgFile) && file_exists($imgPath)) 
                      ? $imgBaseUrl . $imgFile 
                      : $placeholder;
        ?>

        <div class="col-md-6 col-lg-6 col-xl-4">
          <div class="rounded position-relative fruite-item">
            <div class="fruite-img">
              <img src="<?= $imgUrl ?>" class="img-fluid w-100 rounded-top"
                   alt="<?= htmlspecialchars($p['name']) ?>"
                   style="height:220px;object-fit:cover">
            </div>

            <?php if (strtolower($p['status']) === 'bestseller'): ?>
              <div class="position-absolute" style="top:10px;left:10px;">
                <span class="badge bg-warning text-dark">Bestseller</span>
              </div>
            <?php endif; ?>

            <div class="p-4 border border-secondary border-top-0 rounded-bottom">
              <h4><?= htmlspecialchars($p['name']) ?></h4>
              <p class="small text-muted mb-2">
                <?= htmlspecialchars(mb_strimwidth($p['description'], 0, 80, '...')) ?>
              </p>

              <div class="d-flex justify-content-between flex-lg-wrap align-items-center">
                <p class="text-dark fs-5 fw-bold mb-0"><?= rupiah($p['price']) ?></p>

                <button class="btn border border-secondary rounded-pill px-3 text-primary openDetailBtn"
                        data-id="<?= htmlspecialchars($p['id']) ?>"
                        data-tipe="<?= htmlspecialchars($p['tipe']) ?>"
                        data-name="<?= htmlspecialchars($p['name']) ?>"
                        data-kat="<?= htmlspecialchars($p['kategori']) ?>"
                        data-desc="<?= htmlspecialchars($p['description']) ?>"
                        data-price="<?= htmlspecialchars($p['price']) ?>"
                        data-img="<?= $imgUrl ?>">
                  <i class="fa fa-info-circle me-2 text-primary"></i> Detail
                </button>

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
</div>

<!-- MODAL DETAIL PRODUK (popup) -->
<div class="modal fade" id="productModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content p-3">
      <div class="row g-3">
        <div class="col-md-5">
          <img id="modalImg" src="" class="img-fluid rounded" alt="">
        </div>
        <div class="col-md-7">
          <h4 id="modalName"></h4>
          <p><small id="modalCat" class="text-muted"></small></p>
          <h5 class="text-primary" id="modalPrice"></h5>
          <p id="modalDesc"></p>

          <!-- Pilihan durasi jika tipe paket punya durasi (jika ada) -->
          <div id="modalExtra" class="mb-2"></div>

          <!-- Jumlah & Tombol Tambah ke Keranjang -->
          <div class="d-flex gap-2">
            <input type="number" id="modalQty" class="form-control" value="1" min="1" style="width:120px;">
            <button id="btnAddToCart" class="btn btn-primary">Tambah ke Keranjang</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
 <!--Keranjang -->
<div class="modal fade" id="cartModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title">Keranjang Belanja</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">

        <?php if (empty($cart)): ?>
            <p class="text-center text-muted">Keranjang masih kosong.</p>
        <?php else: ?>

        <table class="table table-striped">
          <thead>
            <tr>
              <th>Produk</th>
              <th>Harga</th>
              <th>Qty</th>
              <th>Total</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
          <?php 
            $grand = 0; 
            foreach ($cart as $idx => $c):
              $subtotal = $c['qty'] * $c['price'];
              $grand += $subtotal;
          ?>
            <tr>
              <td><?= htmlspecialchars($c['name']) ?></td>
              <td>Rp <?= number_format($c['price'],0,',','.') ?></td>

              <td width="100">
                <input type="number" min="1" class="form-control qtyUpdate" 
                       data-index="<?= $idx ?>" value="<?= $c['qty'] ?>">
              </td>

              <td>Rp <?= number_format($subtotal,0,',','.') ?></td>

              <td>
                <button class="btn btn-danger btn-sm deleteItem" data-index="<?= $idx ?>">
                    <i class="fa fa-trash"></i>
                </button>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>

        <h5 class="text-end">Total: <strong>Rp <?= number_format($grand,0,',','.') ?></strong></h5>

        <?php endif; ?>

      </div>

      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
        <?php if (!empty($cart)): ?>
            <a href="checkout.php" class="btn btn-success">Checkout</a>
        <?php endif; ?>
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
        <!-- Footer End -->

        <!-- Copyright Start -->
        <div class="container-fluid copyright bg-dark py-4">
            <div class="container">
                <div class="row">
                    <div class="col-md-6 text-center text-md-start mb-3 mb-md-0">
                        <span class="text-light"><a href="#"><i class="fas fa-copyright text-light me-2"></i>Your Site Name</a>, All right reserved.</span>
                    </div>
                    <div class="col-md-6 my-auto text-center text-md-end text-white">
                        <!--/*** This template is free as long as you keep the below author’s credit link/attribution link/backlink. ***/-->
                        <!--/*** If you'd like to use the template without the below author’s credit link/attribution link/backlink, ***/-->
                        <!--/*** you can purchase the Credit Removal License from "https://htmlcodex.com/credit-removal". ***/-->
                        Designed By <a class="border-bottom" href="https://htmlcodex.com">HTML Codex</a> Distributed By <a class="border-bottom" href="https://themewagon.com">ThemeWagon</a>
                    </div>
                </div>
            </div>
        </div>
        <!-- Copyright End -->



        <!-- Back to Top -->
        <a href="#" class="btn btn-primary border-3 border-primary rounded-circle back-to-top"><i class="fa fa-arrow-up"></i></a>   

        
    <!-- JavaScript Libraries -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="lib/easing/easing.min.js"></script>
    <script src="lib/waypoints/waypoints.min.js"></script>
    <script src="lib/lightbox/js/lightbox.min.js"></script>
    <script src="lib/owlcarousel/owl.carousel.min.js"></script>
 <script>
// buka modal product dan isi data
$(document).on('click', '.openDetailBtn', function(){
    const btn = $(this);
    $('#modalImg').attr('src', btn.data('img'));
    $('#modalName').text(btn.data('name'));
    $('#modalCat').text(btn.data('kat'));
    $('#modalDesc').text(btn.data('desc'));
    $('#modalPrice').text(new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(btn.data('price')));
    $('#modalQty').val(1);
    // simpan data pada tombol add
    $('#btnAddToCart').data('id', btn.data('id'));
    $('#btnAddToCart').data('tipe', btn.data('tipe'));
    $('#productModal').modal('show');
});

// Tambah ke keranjang (AJAX ke cart_add.php)
$('#btnAddToCart').click(function(){
    const id = $(this).data('id');
    const tipe = $(this).data('tipe');
    const qty = parseInt($('#modalQty').val()) || 1;

    $.post('cart_add.php', { id: id, tipe: tipe, qty: qty }, function(resp){
        try {
            const j = JSON.parse(resp);
            if (j.success) {
                // update counter keranjang di navbar
                $('#cartCount').text(j.cart_count);
                // tampil notifikasi sederhana
                alert('Produk berhasil ditambahkan ke keranjang.');
                $('#productModal').modal('hide');
            } else {
                alert('Gagal menambahkan ke keranjang: ' + (j.message || 'unknown'));
            }
        } catch(e) {
            alert('Response error: ' + resp);
        }
    });
});
</script>
    <!-- Template Javascript -->
    <script src="js/main.js"></script>
   
    </body>

</html>