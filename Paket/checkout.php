<?php
// checkout.php - FIXED VERSION
session_start();
include __DIR__ . '/../config/koneksi.php';

// ✅ TAMBAHKAN CART COMPONENTS
include __DIR__ . "/components/cart_modal.php";
include __DIR__ . "/components/cart_script.php";

// Ambil user dari session
$userId = $_SESSION['user']['IDUser'] ?? $_SESSION['IDUser'] ?? null;
$userName = $_SESSION['user']['Nama'] ?? $_SESSION['nama'] ?? ($_SESSION['username'] ?? '');

// Ambil cart dari session
$cart = $_SESSION['cart'] ?? [];

// ✅ Hitung jumlah item di cart untuk navbar
$cart_count = count($cart);

// ===== PERBAIKAN LOGIKA DETEKSI PRODUK =====
$total = 0.0;
$hasAlat = false;
$hasPaket = false;

foreach ($cart as $it) {
    // Quantity
    $qty = isset($it['quantity']) ? (int)$it['quantity'] : 
           (isset($it['qty']) ? (int)$it['qty'] : 1);

    // Price
    $price = isset($it['price']) ? (float)$it['price'] : 
             (isset($it['harga']) ? (float)$it['harga'] : 0);

    $total += $price * $qty;

    // ✅ PERBAIKAN: Deteksi jenis item (support berbagai format)
    $jenis = strtolower(trim($it['jenis'] ?? $it['tipe'] ?? $it['type'] ?? ''));

    if ($jenis === 'alat') {
        $hasAlat = true;
    }
    
    // Paket ATAU jasa dianggap sama (keduanya butuh lokasi, tidak butuh jaminan)
    if ($jenis === 'paket' || $jenis === 'jasa') {
        $hasPaket = true;
    }
}

// ===== LOGIKA FINAL (SESUAI REQUIREMENT) =====

// JAMINAN AKTIF → HANYA jika cart berisi HANYA alat (tidak ada paket/jasa)
$needJaminan = ($hasAlat === true && $hasPaket === false);

// LOKASI AKTIF → Jika ada paket/jasa (dengan atau tanpa alat)
$needLokasi = $hasPaket;

// ===== END LOGIKA =====

// Format rupiah
function rupiah($n){ 
    return 'Rp ' . number_format((float)$n, 0, ',', '.'); 
}

// Read flash messages
$success = $_SESSION['success_checkout'] ?? null;
$error = $_SESSION['error_checkout'] ?? null;
unset($_SESSION['success_checkout'], $_SESSION['error_checkout']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Checkout - Artefax</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">

    <!-- Google Web Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600&family=Raleway:wght@600;800&display=swap" rel="stylesheet"> 

    <!-- Icon Font Stylesheet -->
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css"/>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Libraries Stylesheet -->
    <link href="lib/lightbox/css/lightbox.min.css" rel="stylesheet">
    <link href="lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">

    <!-- Customized Bootstrap Stylesheet -->
    <link href="css/bootstrap.min.css" rel="stylesheet">

    <!-- Template Stylesheet -->
    <link href="css/style.css" rel="stylesheet">

    <!-- jQuery & SweetAlert -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>

    <!-- Spinner Start -->
    <div id="spinner" class="show w-100 vh-100 bg-white position-fixed translate-middle top-50 start-50 d-flex align-items-center justify-content-center">
        <div class="spinner-grow text-primary" role="status"></div>
    </div>

    <!-- Navbar (sama seperti sebelumnya) -->
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
                        <a href="checkout.php" class="nav-item nav-link active">Checkout</a>
                        <a href="contact.php" class="nav-item nav-link">Contact</a>
                    </div>
                    <div class="d-flex m-3 me-0">
                        <button class="btn-search btn border border-secondary btn-md-square rounded-circle bg-white me-4" 
                                data-bs-toggle="modal" data-bs-target="#searchModal">
                            <i class="fas fa-search text-primary"></i>
                        </button>
                        <a href="#" class="position-relative me-4 my-auto" data-bs-toggle="modal" data-bs-target="#cartModal">
                            <i class="fa fa-shopping-bag fa-2x"></i>
                            <span class="position-absolute bg-secondary rounded-circle d-flex align-items-center justify-content-center text-dark px-1"
                                  style="top: -5px; left: 15px; height: 20px; min-width: 20px;">
                                <?= $cart_count ?>
                            </span>
                        </a>
                        <a href="#" class="my-auto">
                            <i class="fas fa-user fa-2x"></i>
                        </a>
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
                        <input type="text" name="q" class="form-control p-3" placeholder="Cari produk...">
                        <button class="input-group-text p-3" type="submit"><i class="fa fa-search"></i></button>
                    </form>
                </div>
            </div>
        </div>
    </div>

<?php if($success): ?>
<script>
document.addEventListener('DOMContentLoaded', ()=> {
  Swal.fire({
    icon:'success',
    title:'Berhasil',
    text: <?= json_encode($success) ?>,
    timer:2000,
    showConfirmButton:false
  });
});
</script>
<?php endif; ?>

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

<!-- ============================================
     CONTAINER FORM CHECKOUT
     ============================================ -->
<div class="container py-5" style="margin-top:120px;">
  <h2>Checkout</h2>
  <p>Halo <strong><?= htmlspecialchars($userName ?: 'Pengunjung') ?></strong>. Lengkapi data pemesanan.</p>

  <form id="checkoutForm" action="proses_pembayaran.php" method="POST">
    <input type="hidden" name="user_id" value="<?= htmlspecialchars($userId) ?>">

    <div class="row">
      <!-- ============================================
           BAGIAN KIRI — FORM DATA PEMESAN
           ============================================ -->
      <div class="col-lg-7">

        <!-- Nama user -->
        <div class="mb-3">
          <label class="form-label">Nama (sesuai akun)</label>
          <input type="text" class="form-control" name="name"
          value="<?= htmlspecialchars($userName) ?>" readonly>
        </div>

        <!-- ✅ PERBAIKAN: LOKASI -->
        <div class="mb-3">
          <label class="form-label">
            Alamat Lokasi Acara
            <?php if (!$needLokasi): ?>
            <small class="text-muted">(Dinonaktifkan - hanya sewa alat tanpa jasa/paket)</small>
            <?php else: ?>
            <small class="text-danger">*Wajib diisi</small>
            <?php endif; ?>
          </label>

          <textarea id="alamatField" name="alamat"
            class="form-control" rows="3"
            <?php if ($needLokasi): ?>
                required
                placeholder="Masukkan alamat lengkap lokasi acara"
            <?php else: ?>
                disabled
                placeholder="(Tidak diperlukan untuk sewa alat saja)"
            <?php endif; ?>
          ></textarea>
        </div>

        <!-- ✅ PERBAIKAN: JAMINAN + NOMOR HP -->
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">
              Jaminan
              <?php if ($needJaminan): ?>
              <small class="text-danger">*Wajib untuk sewa alat</small>
              <?php else: ?>
              <small class="text-muted">(Tidak diperlukan - ada paket/jasa)</small>
              <?php endif; ?>
            </label>
            
            <select id="jaminanField" name="jaminan" class="form-select" 
                    <?= $needJaminan ? 'required' : 'disabled' ?>>
              <?php if ($needJaminan): ?>
                <option value="">-- Pilih Jaminan --</option>
                <option value="KTP">KTP</option>
                <option value="SIM">SIM</option>
                <option value="STNK">STNK</option>
              <?php else: ?>
                <option value="" selected>Tidak diperlukan</option>
              <?php endif; ?>
            </select>
          </div>

          <div class="col-md-6">
            <label class="form-label">Nomor HP <small class="text-danger">*</small></label>
            <input type="tel" name="phone" class="form-control"
            placeholder="08xxxxxxxxxx" required>
          </div>
        </div>

        <!-- TANGGAL MULAI / SELESAI -->
        <div class="row g-3 mt-3">
          <div class="col-md-6">
            <label class="form-label">Tanggal Mulai Acara <small class="text-danger">*</small></label>
            <input type="datetime-local" name="tgl_mulai" class="form-control" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Tanggal Selesai Acara <small class="text-danger">*</small></label>
            <input type="datetime-local" name="tgl_selesai" class="form-control" required>
          </div>
        </div>

        <!-- PEMBAYARAN -->
        <div class="mt-3">
          <label class="form-label">Pembayaran</label>
          <div class="form-check">
            <input class="form-check-input paymentOpt" type="radio"
            name="payment" id="pay_dp" value="dp" checked>
            <label class="form-check-label" for="pay_dp">
              DP 50% (Pelunasan saat acara)
            </label>
          </div>
          <div class="form-check">
            <input class="form-check-input paymentOpt" type="radio"
            name="payment" id="pay_full" value="lunas">
            <label class="form-check-label" for="pay_full">
              Lunas
            </label>
          </div>
          <small id="dpNote" class="form-text text-muted mt-1">
            Jika memilih DP, sistem akan menghitung 50% dari total.
          </small>
        </div>

        <!-- CATATAN -->
        <div class="mt-4">
          <label class="form-label">Catatan / Deskripsi (opsional)</label>
          <textarea name="deskripsi" class="form-control" rows="4"></textarea>
        </div>

      </div>

      <!-- ============================================
           BAGIAN KANAN — RINGKASAN PESANAN
           ============================================ -->
      <div class="col-lg-5">
        <h5>Ringkasan Pesanan</h5>

        <div class="table-responsive">
          <table class="table table-sm">
            <thead>
              <tr><th>Produk</th><th>Qty</th><th class="text-end">Subtotal</th></tr>
            </thead>
            <tbody>
              <?php if(empty($cart)): ?>
                <tr><td colspan="3">Keranjang kosong.</td></tr>
              <?php else: ?>
              <?php foreach($cart as $it):
                $qty   = (int)($it['quantity'] ?? $it['qty'] ?? 1);
                $price = (float)($it['price'] ?? 0);
                $line  = $price * $qty;
              ?>
              <tr>
                <td style="max-width:180px;">
                  <div class="d-flex align-items-center">
                    <?php 
                    $imgFile = $it['image'] ?? '';
                    $imgUrl = 'img/noimage.png';
                    if (!empty($imgFile)) {
                        $mainPath = 'img/produk/' . $imgFile;
                        if (file_exists(__DIR__ . '/' . $mainPath)) {
                            $imgUrl = $mainPath;
                        }
                    }
                    ?>
                    <img src="<?= $imgUrl ?>" style="width:56px;height:56px;object-fit:cover;margin-right:8px" onerror="this.src='img/noimage.png'">
                    <div>
                      <div><?= htmlspecialchars($it['name']) ?></div>
                      <div class="text-muted" style="font-size:0.8rem">
                        <?= htmlspecialchars($it['jenis'] ?? ($it['tipe'] ?? $it['type'] ?? '')) ?>
                      </div>
                    </div>
                  </div>
                </td>
                <td><?= $qty ?></td>
                <td class="text-end"><?= rupiah($line) ?></td>
              </tr>
              <?php endforeach; ?>
              <?php endif; ?>

              <tr>
                <td colspan="2" class="text-end"><strong>Total:</strong></td>
                <td class="text-end">
                    <strong id="totalFull"><?= rupiah($total) ?></strong>
                </td>
              </tr>
              <tr id="dpRow" style="display:none;">
                <td colspan="2" class="text-end"><strong>DP 50%:</strong></td>
                <td class="text-end">
                    <strong id="totalDp"><?= rupiah($total * 0.5) ?></strong>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="mt-3">
          <button type="button" id="btnKonfirmasi" class="btn btn-primary w-100">
              Konfirmasi Checkout
          </button>
          <a href="shop.php" class="btn btn-outline-secondary w-100 mt-2">
            Kembali ke Shop
          </a>
        </div>

      </div>
    </div>
  </form>
</div>

<!-- Footer (sama seperti sebelumnya) -->
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
    </div>
</div>

<div class="container-fluid copyright bg-dark py-4">
    <div class="container">
        <div class="row">
            <div class="col-md-6 text-center text-md-start mb-3 mb-md-0">
                <span class="text-light"><a href="#"><i class="fas fa-copyright text-light me-2"></i>Artefax</a>, All right reserved.</span>
            </div>
        </div>
    </div>
</div>

<a href="#" class="btn btn-primary border-3 border-primary rounded-circle back-to-top"><i class="fa fa-arrow-up"></i></a>   

<!-- JavaScript Libraries -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="lib/easing/easing.min.js"></script>
<script src="lib/waypoints/waypoints.min.js"></script>
<script src="lib/lightbox/js/lightbox.min.js"></script>
<script src="lib/owlcarousel/owl.carousel.min.js"></script>
<script src="js/main.js"></script>

<!-- ✅ PERBAIKAN SCRIPT JAVASCRIPT -->
<script>
$(function(){

  // Update visibility DP row
  function updateDpVisibility(){
    const payment = $('input[name=payment]:checked').val();
    if(payment === 'dp'){
      $('#dpRow').show();
    } else {
      $('#dpRow').hide();
    }
  }

  updateDpVisibility();
  $('input[name=payment]').on('change', updateDpVisibility);

  // ✅ PERBAIKAN: Logika dari PHP
  var needJaminan = <?= $needJaminan ? 'true' : 'false' ?>;
  var needLokasi = <?= $needLokasi ? 'true' : 'false' ?>;

  // Apply logika jaminan
  if (!needJaminan) {
    $('#jaminanField')
      .prop('disabled', true)
      .prop('required', false);
  } else {
    $('#jaminanField')
      .prop('disabled', false)
      .prop('required', true);
  }

  // Apply logika lokasi
  if (!needLokasi) {
    $('#alamatField')
      .prop('disabled', true)
      .prop('required', false)
      .attr('placeholder', '(Tidak diperlukan untuk sewa alat saja)');
  } else {
    $('#alamatField')
      .prop('disabled', false)
      .prop('required', true)
      .attr('placeholder', 'Masukkan alamat lengkap lokasi acara');
  }

});
</script>

<!-- Syarat dan ketentuan -->
<script>
document.getElementById("btnKonfirmasi").addEventListener("click", function () {
    Swal.fire({
        title: 'Syarat & Ketentuan',
        html: `
        <div style="text-align:left; max-height:350px; overflow-y:auto; padding-right:10px;">
            <h4><b>1. Ketentuan Penyewaan Alat</b></h4>
            <ul>
                <li>Penyewa wajib memberikan data yang benar.</li>
                <li>Pembayaran dilakukan di awal sesuai ketentuan.</li>
                <li>Penyewa bertanggung jawab penuh atas alat selama masa sewa.</li>
                <li>Kerusakan ringan ditanggung penyewa, sedangkan kerusakan berat atau kehilangan harus diganti sesuai harga alat.</li>
                <li>Alat harus dikembalikan tepat waktu. Keterlambatan akan dikenakan denda.</li>
                <li>Dilarang meminjamkan alat kepada pihak lain tanpa izin dari penyedia.</li>
            </ul>
            <h4><b>2. Ketentuan Penyewaan Jasa</b></h4>
            <ul>
                <li>Penyewa wajib menjelaskan detail kebutuhan jasa dengan jelas.</li>
                <li>Pembayaran jasa dilakukan di awal (DP/lunas).</li>
                <li>Jasa yang sudah dikerjakan tidak dapat dibatalkan atau dikembalikan dananya.</li>
                <li>Permintaan revisi besar di luar perjanjian awal akan dikenakan biaya tambahan.</li>
            </ul>
            <h4><b>3. Ketentuan Pembatalan Booking</b></h4>
            <ul>
                <li>Pembatalan maksimal H-2 sebelum acara untuk refund 100%.</li>
                <li>Pembatalan H-1 atau hari H → uang tidak dikembalikan (non-refund).</li>
                <li>Tidak hadir atau tidak mengambil barang dianggap batal tanpa refund.</li>
                <li>Proses refund membutuhkan 1–3 hari kerja.</li>
            </ul>
            <h4><b>4. Ketentuan Tambahan</b></h4>
            <ul>
                <li>Dengan melanjutkan checkout, penyewa dianggap setuju dengan seluruh ketentuan di atas.</li>
                <li>Penyedia berhak menolak pesanan tertentu apabila dianggap tidak sesuai atau berisiko.</li>
                <li>Syarat & ketentuan dapat berubah sewaktu-waktu tanpa pemberitahuan.</li>
            </ul>
            <hr>
            <div style="margin-top:10px;">
                <input type="checkbox" id="agreeCheckbox">
                <label for="agreeCheckbox">Saya telah membaca dan menyetujui Syarat & Ketentuan.</label>
            </div>
        </div>
        `,
        width: 700,
        showCancelButton: true,
        confirmButtonText: 'Lanjutkan Pembayaran',
        cancelButtonText: 'Batal',
        allowOutsideClick: false,
        preConfirm: () => {
            if (!document.getElementById('agreeCheckbox').checked) {
                Swal.showValidationMessage('Anda harus menyetujui syarat & ketentuan.');
                return false;
            }
            return true;
        }
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById("checkoutForm").submit();
        }
    });
});
</script>

</body>
</html>