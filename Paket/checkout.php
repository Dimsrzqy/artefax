<?php
session_start();
include __DIR__ . '/../config/koneksi.php';

// === CEK LOGIN & AMBIL DATA USER 
if (!isset($_SESSION['user']) || !is_array($_SESSION['user'])) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header('Location: ../view/login.php');
    exit;
}

$userId    = $_SESSION['user']['IDUser'] ?? $_SESSION['user']['id'] ?? null;
$userName  = $_SESSION['user']['UserNama'] ?? $_SESSION['user']['nama'] ?? $_SESSION['user']['username'] ?? 'Pengunjung';
$userEmail = $_SESSION['user']['UserEmail'] ?? $_SESSION['user']['email'] ?? '';
$userRole  = $_SESSION['user']['UserRole'] ?? $_SESSION['user']['role'] ?? 'customer';

if (!$userId) {
    unset($_SESSION['user']);
    header('Location: ../view/login.php');
    exit;
}

$db = new Database();
$conn = $db->getConnection();

// Ambil cart
$cart = $_SESSION['cart'] ?? [];
$cart_count = count($cart);

// =======================================================
// CEK LIMIT 2 BOOKING PER PRODUK PER HARI
// =======================================================
$today = date('Y-m-d');
$blockedProducts = [];

foreach ($cart as $key => $item) {
    $itemId = (int)($item['id'] ?? 0);
    $jenis  = strtolower($item['jenis'] ?? $item['tipe'] ?? $item['type'] ?? '');

    if ($itemId <= 0) continue;

    $sql = "SELECT COUNT(*) as total
            FROM booking b
            JOIN booking_detail d ON b.IDBooking = d.IDBooking
            WHERE DATE(b.BkgTglMulai) = ?
              AND b.BkgStatus NOT IN ('Dibatalkan', 'Ditolak', 'Selesai')
              AND (
                  (d.BkgDetailJenis = 'Jasa' AND d.IDPaket = ?) 
                  OR 
                  (d.BkgDetailJenis = 'Alat' AND d.IDAlat = ?)
              )";

    $stmt = $conn->prepare($sql);
    if (!$stmt) continue;

    $stmt->bind_param("sii", $today, $itemId, $itemId);
    $stmt->execute();
    $count = (int)$stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();

    if ($count >= 2) {
        $blockedProducts[] = $item['name'] ?? 'Produk';
        unset($_SESSION['cart'][$key]);

        if ($jenis === 'alat') {
            $conn->query("UPDATE alat SET AlatStatus = 'Tidak Tersedia' WHERE IDAlat = $itemId");
        } elseif (in_array($jenis, ['paket', 'jasa'])) {
            $conn->query("UPDATE paketjasa SET PaketStatus = 'Tidak Tersedia' WHERE IDPaket = $itemId");
        }
    }
}

$cart = $_SESSION['cart'] ?? [];
$cart_count = count($cart);

// =======================================================
// DETEKSI JENIS PRODUK & LOGIKA FORM (INI YANG BENAR!)
$total = 0;
$hasAlat = false;
$hasPaket = false;

foreach ($cart as $it) {
    $qty = (int)($it['quantity'] ?? $it['qty'] ?? 1);
    $price = (float)($it['price'] ?? $it['harga'] ?? 0);
    $total += $price * $qty;

    $jenis = strtolower(
        $it['jenis'] ?? $it['tipe'] ?? $it['type'] ?? $it['kategori'] ?? $it['category'] ?? ''
    );
    $jenis = trim(str_replace([' ', '_', '-'], '', $jenis));

    if (strpos($jenis, 'alat') !== false) $hasAlat = true;
    if (strpos($jenis, 'paket') !== false || strpos($jenis, 'jasa') !== false) $hasPaket = true;
}

// LOGIKA YANG BENAR SESUAI KEINGINAN KAMU:
$needLokasi  = $hasPaket;                    // Ada paket/jasa → wajib alamat
$needJaminan = $hasAlat && !$hasPaket;       // Hanya alat (tanpa paket) → wajib jaminan

function rupiah($n) {
    return 'Rp ' . number_format((float)$n, 0, ',', '.');
}

$success = $_SESSION['success_checkout'] ?? null;
$error   = $_SESSION['error_checkout'] ?? null;
unset($_SESSION['success_checkout'], $_SESSION['error_checkout']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Checkout - Artefax</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600&family=Raleway:wght@600;800&display=swap" rel="stylesheet"> 
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css"/>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="lib/lightbox/css/lightbox.min.css" rel="stylesheet">
    <link href="lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

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
                    <a href="Services.php" class="nav-item nav-link">Home</a>
                    <a href="shop.php" class="nav-item nav-link">Shop</a>
                </div>
                <div class="d-flex m-3 me-0">
                    <button class="btn-search btn border border-secondary btn-md-square rounded-circle bg-white me-4" data-bs-toggle="modal" data-bs-target="#searchModal">
                        <i class="fas fa-search text-primary"></i>
                    </button>
                    <a href="#" class="position-relative me-4 my-auto" data-bs-toggle="modal" data-bs-target="#cartModal">
                        <i class="fa fa-shopping-bag fa-2x"></i>
                        <span class="position-absolute bg-secondary rounded-circle d-flex align-items-center justify-content-center text-dark px-1" style="top: -5px; left: 15px; height: 20px; min-width: 20px;">
                            <?= $cart_count ?>
                        </span>
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
  Swal.fire({ icon:'success', title:'Berhasil', text: <?= json_encode($success) ?>, timer:2000, showConfirmButton:false });
});
</script>
<?php endif; ?>

<?php if($error): ?>
<script>
document.addEventListener('DOMContentLoaded', ()=> {
  Swal.fire({ icon:'error', title:'Gagal', text: <?= json_encode($error) ?> });
});
</script>
<?php endif; ?>

<?php if(!empty($blockedProducts)): ?>
<script>
document.addEventListener('DOMContentLoaded', ()=> {
  let productList = <?= json_encode(array_column($blockedProducts, 'name')) ?>.join('<br>');
  Swal.fire({
    icon:'warning',
    title:'Produk Tidak Tersedia',
    html: 'Produk berikut telah mencapai batas booking hari ini dan dihapus dari keranjang:<br><br><strong>' + productList + '</strong><br><br>Silakan pilih tanggal lain atau produk lain.',
    confirmButtonText: 'OK'
  });
});
</script>
<?php endif; ?>

<div class="container py-5" style="margin-top:120px;">
  <h2>Checkout</h2>
  <p>Halo <strong><?= htmlspecialchars($userName) ?></strong>. Lengkapi data pemesanan.</p>

  <?php if (empty($cart)): ?>
    <div class="alert alert-warning">
      Keranjang kosong. <a href="shop.php">Belanja sekarang</a>
    </div>
  <?php else: ?>

  <form id="checkoutForm" action="checkoutproses.php" method="POST">
    <input type="hidden" name="user_id" value="<?= htmlspecialchars($userId) ?>">

    <div class="row">
      <div class="col-lg-7">
        <div class="mb-3">
          <label class="form-label">Nama Lengkap</label>
          <input type="text" class="form-control" name="username" value="<?= htmlspecialchars($userName) ?>" readonly>
          <small class="text-muted">Sesuai dengan akun login Anda</small>
        </div>

        <div class="mb-3">
          <label class="form-label">
            Alamat Lokasi Acara
            <?php if (!$needLokasi): ?>
            <small class="text-muted">(Dinonaktifkan - hanya sewa alat tanpa jasa/paket)</small>
            <?php else: ?>
            <small class="text-danger">*Wajib diisi</small>
            <?php endif; ?>
          </label>
          <textarea id="alamatField" name="alamat" class="form-control" rows="3"
            <?php if ($needLokasi): ?>
                required placeholder="Masukkan alamat lengkap lokasi acara"
            <?php else: ?>
                disabled placeholder="(Tidak diperlukan untuk sewa alat saja)"
            <?php endif; ?>
          ></textarea>
        </div>

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
            <select id="jaminanField" name="jaminan" class="form-select" <?= $needJaminan ? 'required' : 'disabled' ?>>
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
            <input type="tel" name="phone" class="form-control" placeholder="08xxxxxxxxxx" required>
          </div>
        </div>

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

        <div class="mt-3">
          <label class="form-label">Pembayaran</label>
          <div class="form-check">
            <input class="form-check-input paymentOpt" type="radio" name="payment" id="pay_dp" value="dp" checked>
            <label class="form-check-label" for="pay_dp">DP 50% (Pelunasan saat acara)</label>
          </div>
          <div class="form-check">
            <input class="form-check-input paymentOpt" type="radio" name="payment" id="pay_full" value="lunas">
            <label class="form-check-label" for="pay_full">Lunas</label>
          </div>
        </div>

        <div class="mt-4">
          <label class="form-label">Catatan / Deskripsi (opsional)</label>
          <textarea name="deskripsi" class="form-control" rows="4"></textarea>
        </div>
      </div>

      <div class="col-lg-5">
        <h5>Ringkasan Pesanan</h5>
        <div class="table-responsive">
          <table class="table table-sm">
            <thead>
              <tr><th>Produk</th><th>Qty</th><th class="text-end">Subtotal</th></tr>
            </thead>
            <tbody>
              <?php foreach($cart as $it):
                $qty = (int)($it['quantity'] ?? $it['qty'] ?? 1);
                $price = (float)($it['price'] ?? 0);
                $line = $price * $qty;
              ?>
              <tr>
                <td><?= htmlspecialchars($it['name']) ?></td>
                <td><?= $qty ?></td>
                <td class="text-end"><?= rupiah($line) ?></td>
              </tr>
              <?php endforeach; ?>
              <tr>
                <td colspan="2" class="text-end"><strong>Total:</strong></td>
                <td class="text-end"><strong id="totalFull"><?= rupiah($total) ?></strong></td>
              </tr>
              <tr id="dpRow" style="display:none;">
                <td colspan="2" class="text-end"><strong>DP 50%:</strong></td>
                <td class="text-end"><strong id="totalDp"><?= rupiah($total * 0.5) ?></strong></td>
              </tr>
            </tbody>
          </table>
        </div>
        <div class="mt-3">
          <button type="button" id="btnKonfirmasi" class="btn btn-primary w-100">Konfirmasi Checkout</button>
          <a href="shop.php" class="btn btn-outline-secondary w-100 mt-2">Kembali ke Shop</a>
        </div>
      </div>
    </div>
  </form>

  <?php endif; ?>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/main.js"></script>

<script>
$(function(){
  function updateDpVisibility(){
    const payment = $('input[name=payment]:checked').val();
    if(payment === 'dp'){ $('#dpRow').show(); } else { $('#dpRow').hide(); }
  }
  updateDpVisibility();
  $('input[name=payment]').on('change', updateDpVisibility);
});

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