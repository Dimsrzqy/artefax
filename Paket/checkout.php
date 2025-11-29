<?php
// checkout.php
session_start();
include __DIR__ . '/../config/koneksi.php'; // sesuaikan path jika perlu

// Ambil user dari session (fallback jika struktur berbeda)
$userId = $_SESSION['user']['IDUser'] ?? $_SESSION['IDUser'] ?? null;
$userName = $_SESSION['user']['Nama'] ?? $_SESSION['nama'] ?? ($_SESSION['username'] ?? '');

// Ambil cart dari session (harus terisi oleh logic add-to-cart)
$cart = $_SESSION['cart'] ?? []; // setiap item: id, name, price, quantity, jenis('alat'|'paket'), image

// Hitung total dan deteksi apakah hanya alat / hanya paket / campuran
$total = 0.0;
$hasAlat = false;
$hasPaket = false;
foreach ($cart as $it) {
    $qty = (int)($it['quantity'] ?? $it['qty'] ?? 1);
    $price = (float)($it['price'] ?? $it['harga'] ?? 0);
    $total += $price * $qty;
    $jenis = strtolower($it['jenis'] ?? '');
    if ($jenis === 'alat' || ($it['tipe'] ?? '') === 'alat') $hasAlat = true;
    if ($jenis === 'paket' || ($it['tipe'] ?? '') === 'paket') $hasPaket = true;
}
$onlyAlat = $hasAlat && !$hasPaket;

// simple rupiah formatter
function rupiah($n){ return 'Rp ' . number_format((float)$n,0,',','.'); }

// Read flash messages
$success = $_SESSION['success_checkout'] ?? null;
$error = $_SESSION['error_checkout'] ?? null;
unset($_SESSION['success_checkout'], $_SESSION['error_checkout']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Fruitables - Vegetable Website Template</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="" name="keywords">
    <meta content="" name="description">

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
    <!<!-- =============================== -->
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
                    <a href="shop.php" class="nav-item nav-link ">Shop</a>

                    <!-- ✅ checkout  -->
                          <a href="checkout.php" class="nav-item nav-link active">Checkout</a>
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

<!-- ============================================
     NOTIFIKASI: SUCCESS
     ============================================ -->
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

<!-- ============================================
     NOTIFIKASI: ERROR
     ============================================ -->
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
<div class="container py-5" style="margin-top:80px;">
  <h2>Checkout</h2>

  <!-- Nama user -->
  <p>Halo <strong><?= htmlspecialchars($userName ?: 'Pengunjung') ?></strong>. Lengkapi data pemesanan.</p>

  
  <!-- FORM UTAMA -->
  <form id="checkoutForm" action="process_checkout.php" method="POST">

    <!-- Kirim user ID ke backend -->
    <input type="hidden" name="user_id" value="<?= htmlspecialchars($userId) ?>">

    <div class="row">

      <!-- ============================================
           BAGIAN KIRI — FORM DATA PEMESAN
           ============================================ -->
      <div class="col-lg-7">

        <!-- Nama user / readonly -->
        <div class="mb-3">
          <label class="form-label">Nama (sesuai akun)</label>
          <input type="text" class="form-control" name="name"
          value="<?= htmlspecialchars($userName) ?>" readonly>
        </div>

        <!-- ALAMAT ACARA — auto disable jika sewa alat -->
        <div class="mb-3">
          <label class="form-label">
            Alamat lokasi acara 
            <small class="text-muted">(dinonaktifkan bila hanya sewa alat)</small>
          </label>

          <!-- Jika hanya sewa alat → disabled -->
          <textarea id="alamatField" name="alamat"
            class="form-control" rows="3"
            <?= $onlyAlat ? 'disabled' : 'required' ?>
            placeholder="Alamat lokasi acara / penyewaan"></textarea>
        </div>

        <!-- JAMINAN + NOMOR HP -->
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Jaminan (opsi)</label>
            <select name="jaminan" class="form-select">
              <option value="">-- Pilih (opsional) --</option>
              <option value="KTP">KTP</option>
              <option value="SIM">SIM</option>
              <option value="STNK">STNK</option>
            </select>
            <small class="text-muted">Kolom opsional dan bisa dimatikan kapan saja.</small>
          </div>

          <div class="col-md-6">
            <label class="form-label">Nomor HP</label>
            <input type="tel" name="phone" class="form-control"
            placeholder="08xxxxxxxxxx" required>
          </div>
        </div>


        <!-- TANGGAL MULAI / SELESAI -->
        <div class="row g-3 mt-3">
          <div class="col-md-6">
            <label class="form-label">Tanggal Mulai acara</label>
            <input type="datetime-local" name="tgl_mulai" class="form-control" required>
          </div>

          <div class="col-md-6">
            <label class="form-label">Tanggal Selesai acara</label>
            <input type="datetime-local" name="tgl_selesai" class="form-control" required>
          </div>
        </div>


        <!-- PEMBAYARAN: DP atau Lunas -->
        <div class="mt-3">
          <label class="form-label">Pembayaran</label>

          <!-- Opsi DP -->
          <div class="form-check">
            <input class="form-check-input paymentOpt" type="radio"
            name="payment" id="pay_dp" value="dp"
            <?= (!isset($_POST['payment']) || $_POST['payment']==='dp') ? 'checked' : '' ?>>
            <label class="form-check-label" for="pay_dp">
              DP 50% (Pelunasan saat acara)
            </label>
          </div>

          <!-- Opsi Lunas -->
          <div class="form-check">
            <input class="form-check-input paymentOpt" type="radio"
            name="payment" id="pay_full" value="lunas"
            <?= (isset($_POST['payment']) && $_POST['payment']==='lunas') ? 'checked' : '' ?>>
            <label class="form-check-label" for="pay_full">
              Lunas
            </label>
          </div>

          <!-- Keterangan DP -->
          <small id="dpNote" class="form-text text-muted mt-1">
            Jika memilih DP, sistem akan menghitung 50% dari total.
          </small>
        </div>


        <!-- CATATAN -->
        <div class="mt-4">
          <label class="form-label">Catatan / Deskripsi (opsional)</label>
          <textarea name="deskripsi" class="form-control" rows="4"></textarea>
        </div>

      </div> <!-- end col kiri -->



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

              <!-- LOOP PRODUK DALAM CART -->
              <?php foreach($cart as $it):
                $qty   = (int)($it['quantity'] ?? $it['qty'] ?? 1);
                $price = (float)($it['price'] ?? 0);
                $line  = $price * $qty;
              ?>
              <tr>
                <td style="max-width:180px;">
                  <div class="d-flex align-items-center">

                    <!-- Gambar produk -->
                    <?php $img = htmlspecialchars($it['image'] ?? ''); ?>
                    <?php if($img): ?>
                      <img src="<?= $img ?>" style="width:56px;height:56px;object-fit:cover;margin-right:8px">
                    <?php endif; ?>

                    <!-- Nama + Jenis -->
                    <div>
                      <div><?= htmlspecialchars($it['name']) ?></div>
                      <div class="text-muted" style="font-size:0.8rem">
                        <?= htmlspecialchars($it['jenis'] ?? ($it['tipe'] ?? '')) ?>
                      </div>
                    </div>

                  </div>
                </td>

                <td><?= $qty ?></td>
                <td class="text-end"><?= rupiah($line) ?></td>
              </tr>
              <?php endforeach; ?>

              <?php endif; ?>

              <!-- TOTAL FULL -->
              <tr>
                <td colspan="2" class="text-end"><strong>Total:</strong></td>
                <td class="text-end">
                    <strong id="totalFull"><?= rupiah($total) ?></strong>
                </td>
              </tr>

              <!-- TOTAL DP (Hidden awalnya) -->
              <tr id="dpRow" style="display:none;">
                <td colspan="2" class="text-end"><strong>DP 50%:</strong></td>
                <td class="text-end">
                    <strong id="totalDp"><?= rupiah($total * 0.5) ?></strong>
                </td>
              </tr>

            </tbody>
          </table>
        </div>

        <!-- Tombol submit -->
        <div class="mt-3">
          <button type="button" id="btnKonfirmasi" class="btn btn-primary w-100">
              Konfirmasi Checkout
          </button>
          <a href="shop.php" class="btn btn-outline-secondary w-100 mt-2">
            Kembali ke Shop
          </a>
        </div>

      </div> <!-- end col kanan -->

    </div> <!-- end row -->
  </form>
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

<!-- Template Javascript -->
<script src="js/main.js"></script>

<!-- ============================================
     SCRIPT JAVASCRIPT
============================================ -->
<script>
$(function(){

  // -----------------------------
  // Tampilkan DP Row jika payment = DP
  // -----------------------------
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


  // -----------------------------
  // Jika hanya sewa alat → disable alamat
  // -----------------------------
  var onlyAlat = <?= $onlyAlat ? 'true' : 'false' ?>;

  if(onlyAlat){
    $('#alamatField')
      .prop('disabled', true)
      .attr('placeholder','(Dinonaktifkan karena hanya sewa alat)');
  } else {
    $('#alamatField').prop('disabled', false);
  }


  // -----------------------------
  // Saat submit, cart tidak dikirim,
  // karena process_checkout membaca dari session.
  // -----------------------------
  $('#checkoutForm').on('submit', function(){
    return true;
  });

});
</script>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<?php
if (isset($_SESSION['success_checkout'])) {
    $msg = $_SESSION['success_checkout'];
    echo "
    <script>
        Swal.fire({
            title: 'Berhasil!',
            text: '$msg',
            icon: 'success',
            confirmButtonText: 'OK'
        }).then(() => {
            window.location = 'index.php';
        });
    </script>";
    unset($_SESSION['success_checkout']);
}

if (isset($_SESSION['error_checkout'])) {
    $msg = $_SESSION['error_checkout'];
    echo "
    <script>
        Swal.fire({
            title: 'Gagal!',
            text: '$msg',
            icon: 'error',
            confirmButtonText: 'OK'
        });
    </script>";
    unset($_SESSION['error_checkout']);
}
?>
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
