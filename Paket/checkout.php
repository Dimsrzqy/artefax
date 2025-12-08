<?php
session_start();
include __DIR__ . '/../config/koneksi.php';

// =======================================================
// 1. LOGIKA PENANGKAPAN TANGGAL DARI SHOP
// =======================================================
$autoDateValue = "";

if (isset($_GET['date']) && !empty($_GET['date'])) {
   $autoDateValue = date('Y-m-d\TH:i', strtotime($_GET['date'] . ' 09:00'));
}

// === CEK LOGIN & AMBIL DATA USER ===
if (!isset($_SESSION['user']) || !is_array($_SESSION['user'])) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header('Location: ../view/login.php');
    exit;
}

$userIdSession = $_SESSION['user']['IDUser'] ?? $_SESSION['user']['id'] ?? null;

if (!$userIdSession) {
    unset($_SESSION['user']);
    header('Location: ../view/login.php');
    exit;
}

$db = new Database();
$conn = $db->getConnection();

// === AMBIL DATA USER TERBARU ===
$stmtUser = $conn->prepare("SELECT UserNama, UserEmail, UserNoHP FROM users WHERE IDUser = ?");
$stmtUser->bind_param("i", $userIdSession);
$stmtUser->execute();
$resUser = $stmtUser->get_result();
$userData = $resUser->fetch_assoc();
$stmtUser->close();

$userId    = $userIdSession;
$userName  = $userData['UserNama'] ?? $_SESSION['user']['UserNama'] ?? 'Pelanggan';
$userEmail = $userData['UserEmail'] ?? $_SESSION['user']['UserEmail'] ?? '';
$userPhone = $userData['UserNoHP'] ?? ''; 

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
            $conn->query("UPDATE alat SET AlatStatus = 'Dipesan' WHERE IDAlat = $itemId");
        } elseif (in_array($jenis, ['paket', 'jasa'])) {
            $conn->query("UPDATE paketjasa SET PaketStatus = 'Nonaktif' WHERE IDPaket = $itemId");
        }
    }
}

$cart = $_SESSION['cart'] ?? [];
$cart_count = count($cart);

// =======================================================
// DETEKSI JENIS PRODUK
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

$needLokasi  = $hasPaket;             
$needJaminan = $hasAlat && !$hasPaket; 

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
    <title>Checkout - Artefax</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="../assets/img/logo Artefax1.png" rel="icon" />
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600&family=Raleway:wght@600;800&display=swap" rel="stylesheet"> 
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css"/>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css?v=<?= time() ?>" rel="stylesheet">
    
    <!-- CSS INTERNAL: NAVBAR FIXED TOP 100% -->
    <style>
        /* Navbar benar-benar fixed */
        .navbar-fixed-custom {
            position: fixed !important;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1030;
            background-color: #fff !important;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        /* Konten tidak tertutup navbar */
        body {
            padding-top: 90px !important;
        }
        .checkout-title {
            color: #ffffff !important;
        }

        @media (max-width: 991.98px) {
            body {
                padding-top: 80px !important;
            }
        }

        /* Spinner tetap di atas semua */
        #spinner {
            position: fixed !important;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(255,255,255,0.98);
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .page-header {
            margin-top: 2rem;
        }
    </style>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

<!-- SPINNER -->
<div id="spinner" class="show">
    <div class="spinner-grow text-primary" style="width: 3rem; height: 3rem;" role="status"></div>
</div>

<!-- NAVBAR DENGAN KELAS FIXED MANUAL -->
<div class="navbar-fixed-custom">
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

<!-- HEADER -->
<div class="container-fluid page-header mb-5">
    <div class="container text-center">
        <h1 class="display-5 fw-bold mb-2 checkout-title">Checkout</h1>
        <p class="text-white-50 mb-0">Lengkapi data pemesanan Anda</p>
    </div>
</div>

<!-- NOTIFIKASI SWEETALERT -->
<?php if($success): ?>
<script>
document.addEventListener('DOMContentLoaded', ()=> {
  Swal.fire({ icon:'success', title:'Berhasil', text: <?= json_encode($success) ?>, timer:3000, showConfirmButton:false });
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
  let list = <?= json_encode($blockedProducts) ?>.join('<br>');
  Swal.fire({
    icon:'warning',
    title:'Produk Tidak Tersedia',
    html: 'Produk berikut telah mencapai batas booking hari ini dan dihapus dari keranjang:<br><br><strong>' + list + '</strong><br><br>Silakan pilih tanggal lain atau produk lain.',
    confirmButtonText: 'OK'
  });
});
</script>
<?php endif; ?>

<!-- KONTEN UTAMA -->
<div class="container py-5">
  
  <?php if (empty($cart)): ?>
    <div class="alert alert-warning text-center py-5">
      <i class="fa fa-shopping-cart fa-3x mb-3 text-muted"></i><br>
      <h4>Keranjang Belanja Kosong</h4>
      <a href="shop.php" class="btn btn-primary mt-3">Belanja Sekarang</a>
    </div>
  <?php else: ?>

  <form id="checkoutForm" action="checkoutproses.php<?= isset($_GET['date']) ? '?date='.$_GET['date'] : '' ?>" method="POST">
    <input type="hidden" name="user_id" value="<?= htmlspecialchars($userId) ?>">

    <div class="row g-5">
      <div class="col-lg-7">
        <h4 class="mb-3">Detail Penyewa</h4>
        
        <div class="mb-3">
          <label class="form-label fw-bold">Nama Lengkap</label>
          <input type="text" class="form-control bg-light" name="username" value="<?= htmlspecialchars($userName) ?>" readonly>
          <small class="text-muted">Sesuai akun login</small>
        </div>

        <div class="mb-3">
          <label class="form-label fw-bold">
            Alamat Lokasi Acara
            <?php if (!$needLokasi): ?>
            <span class="badge bg-secondary ms-2">Tidak Perlu</span>
            <?php else: ?>
            <span class="text-danger">*</span>
            <?php endif; ?>
          </label>
          <textarea id="alamatField" name="alamat" class="form-control" rows="3"
            <?php if ($needLokasi): ?>
                required placeholder="Masukkan alamat lengkap lokasi acara"
            <?php else: ?>
                disabled placeholder="(Tidak diperlukan hanya untuk penyewaan jasa saja)"
            <?php endif; ?>
          ></textarea>
        </div>

        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label fw-bold">
              Jaminan
              <?php if ($needJaminan): ?>
              <span class="text-danger">*</span>
              <?php else: ?>
              <span class="badge bg-secondary ms-2">Tidak Perlu</span>
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
            <label class="form-label fw-bold">Nomor HP <span class="text-danger">*</span></label>
            <input type="tel" name="phone" class="form-control" placeholder="08xxxxxxxxxx" value="<?= htmlspecialchars($userPhone) ?>" required>
          </div>
        </div>

       <div class="row g-3 mt-3">
          <div class="col-md-6">
            <label class="form-label fw-bold text-primary">Tanggal Mulai <span class="text-danger">*</span></label>
            <input type="datetime-local" 
                  id="inputTglMulai" 
                  name="tgl_mulai" 
                  class="form-control border-primary" 
                  value="<?= htmlspecialchars($autoDateValue) ?>" 
                  required>
            
            <?php if (!empty($autoDateValue)): ?>
                <small class="text-muted"><i class="fas fa-check-circle text-success"></i> Sesuai pilihan di Shop.</small>
            <?php endif; ?>
          </div>

          <div class="col-md-6">
            <label class="form-label fw-bold text-primary">Tanggal Selesai <span class="text-danger">*</span></label>
            <input type="datetime-local" id="inputTglSelesai" name="tgl_selesai" class="form-control border-primary" required>
          </div>
        </div>
        
        <div class="mt-4 p-3 bg-light rounded border">
          <label class="form-label fw-bold mb-2">Metode Pembayaran</label>
          <div class="d-flex gap-4">
              <div class="form-check">
                <input class="form-check-input paymentOpt" type="radio" name="payment" id="pay_dp" value="dp" checked>
                <label class="form-check-label fw-bold" for="pay_dp">DP 50% <span class="text-muted fw-normal">(Sisanya saat acara)</span></label>
              </div>
              <div class="form-check">
                <input class="form-check-input paymentOpt" type="radio" name="payment" id="pay_full" value="lunas">
                <label class="form-check-label fw-bold" for="pay_full">Lunas (Full Payment)</label>
              </div>
          </div>
        </div>

        <div class="mt-4">
          <label class="form-label">Catatan Tambahan (opsional)</label>
          <textarea name="deskripsi" class="form-control" rows="2" placeholder="Request khusus..."></textarea>
        </div>
      </div>

      <div class="col-lg-5">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-3">
                <h5 class="mb-0 fw-bold">Ringkasan Pesanan</h5>
            </div>
            <div class="card-body">
              <div class="table-responsive">
                <table class="table table-sm table-borderless">
                  <thead>
                    <tr class="text-muted border-bottom">
                        <th>Produk</th>
                        <th class="text-center">Qty</th>
                        <th class="text-end">Subtotal</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach($cart as $it):
                      $qty = (int)($it['quantity'] ?? $it['qty'] ?? 1);
                      $price = (float)($it['price'] ?? 0);
                      $line = $price * $qty;
                    ?>
                    <tr>
                      <td class="fw-bold"><?= htmlspecialchars($it['name']) ?></td>
                      <td class="text-center"><?= $qty ?></td>
                      <td class="text-end subtotal-item" data-base-price="<?= $line ?>">
                          <?= rupiah($line) ?>
                      </td>
                    </tr>
                    <?php endforeach; ?>
                    
                    <tr class="border-top mt-3">
                      <td colspan="2" class="text-end pt-3">Total Estimasi:</td>
                      <td class="text-end pt-3">
                          <strong id="totalFull" class="fs-5" data-base-total="<?= $total ?>"><?= rupiah($total) ?></strong>
                          <br><small id="infoDurasi" class="text-muted" style="font-size: 0.75em;"></small>
                      </td>
                    </tr>
                    <tr id="dpRow" style="background-color: #fff3cd;">
                      <td colspan="2" class="text-end fw-bold p-3" style="color: #856404;"> Bayar Sekarang (DP 50%):</td>
                      <td class="text-end p-3">
                          <strong class="fs-4" id="totalDp" style="color: #d39e00;"> <?= rupiah($total * 0.5) ?></strong>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              
              <div class="d-grid gap-2 mt-4">
                  <button type="button" id="btnKonfirmasi" class="btn btn-primary py-3 fw-bold">Konfirmasi Checkout</button>
                  <a href="shop.php" class="btn btn-outline-secondary">Kembali ke Shop</a>
              </div>
            </div>
        </div>
      </div>
    </div>
  </form>

  <?php endif; ?>
</div>

<!-- FOOTER -->
<div class="container-fluid bg-dark text-white-50 footer pt-5 mt-5">
    <div class="container py-5">
        <div class="pb-4 mb-4" style="border-bottom: 1px solid rgba(226, 175, 24, 0.5);">
            <div class="row g-4">
                <div class="col-lg-3">
                    <a href="#">
                        <h1 class="text-primary mb-0">ARTEFAX.ID</h1>
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
                    <a href="Services.php" class="btn border-secondary py-2 px-4 rounded-pill text-primary">Lihat Layanan</a>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="d-flex flex-column text-start footer-item">
                    <h4 class="text-light mb-3">Menu Cepat</h4>
                    <a class="btn-link" href="../index.php">Landing Page</a>
                    <a class="btn-link" href="shop.php">Shop</a>
                    <a class="btn-link" href="Services.php">Home</a>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="d-flex flex-column text-start footer-item">
                    <h4 class="text-light mb-3">Akun Saya</h4>
                    <a class="btn-link" href="../View/profil.php">Profil</a>
                    <a class="btn-link" href="cart.php">Contact</a>
                    <a class="btn-link" href="../RiwayatBooking.php">Riwayat Booking</a>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/main.js"></script>

<script>
// Hilangkan spinner setelah load
window.addEventListener('load', () => {
    document.getElementById('spinner').style.display = 'none';
});

// Format Rupiah
const formatter = new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0
});

document.addEventListener("DOMContentLoaded", function() {
    const tglMulaiInput = document.getElementById("inputTglMulai");
    const tglSelesaiInput = document.getElementById("inputTglSelesai");
    
    function updateMinDate() {
        if (tglMulaiInput.value) {
            tglSelesaiInput.min = tglMulaiInput.value;
            if (tglSelesaiInput.value && tglSelesaiInput.value < tglMulaiInput.value) {
                tglSelesaiInput.value = ""; 
            }
        }
    }
    updateMinDate();
    tglMulaiInput.addEventListener("change", updateMinDate);

    function updatePrices() {
        const startVal = tglMulaiInput.value;
        const endVal = tglSelesaiInput.value;
        
        let multiplier = 1;
        let infoText = "";

        if (startVal && endVal) {
            const startDate = new Date(startVal);
            const endDate = new Date(endVal);
            const diffTime = endDate - startDate;
            const diffHours = diffTime / (1000 * 60 * 60);
            multiplier = Math.ceil(diffHours / 24);
            if (multiplier < 1) multiplier = 1;

            if (multiplier > 1) {
                infoText = "(Durasi " + multiplier + " Hari: Harga x" + multiplier + ")";
                $('#infoDurasi').text(infoText).addClass('text-danger').removeClass('text-muted');
            } else {
                $('#infoDurasi').text("(Harga Normal 1 Hari)").removeClass('text-danger').addClass('text-muted');
            }
        }

        $('.subtotal-item').each(function() {
            const basePrice = parseFloat($(this).data('base-price'));
            const newPrice = basePrice * multiplier;
            $(this).text(formatter.format(newPrice));
        });

        const totalElem = $('#totalFull');
        const baseTotal = parseFloat(totalElem.data('base-total'));
        const newTotal = baseTotal * multiplier;
        totalElem.text(formatter.format(newTotal));

        const newDp = newTotal * 0.5;
        $('#totalDp').text(formatter.format(newDp));
    }

    $('#inputTglMulai, #inputTglSelesai').on('change', updatePrices);

    function updateDpVisibility(){
        const payment = $('input[name=payment]:checked').val();
        if(payment === 'dp'){ $('#dpRow').show(); } else { $('#dpRow').hide(); }
    }
    updateDpVisibility();
    $('input[name=payment]').on('change', updateDpVisibility);
});

// SweetAlert Konfirmasi Checkout
document.getElementById("btnKonfirmasi").addEventListener("click", function () {
    if(!document.getElementById("inputTglMulai").value || !document.getElementById("inputTglSelesai").value){
        Swal.fire('Error', 'Mohon lengkapi tanggal sewa.', 'error');
        return;
    }

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