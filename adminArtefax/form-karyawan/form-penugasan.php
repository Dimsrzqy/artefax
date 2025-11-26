<?php
session_start();
require_once __DIR__ . "/../../config/koneksi.php";
require_once __DIR__ . "/../../class/Users.php";
require_once __DIR__ . "/../../class/EventAssignment.php";

$db = new Database();
$conn = $db->getConnection();
if (!$conn) die("<p style='color:red;'>Koneksi database gagal.</p>");

$user        = new User($conn);
$eventAssign = new EventAssignment($conn);

$eventAssign->updateStatusOtomatis();

/* ============== BOOKING YANG BELUM PERNAH DIBUATKAN EVENT ============== */
$stmt_booking = $conn->prepare("
    SELECT 
        b.IDBooking,
        b.BkgAlamat,
        b.BkgTglMulai,
        b.BkgTotalHarga,
        b.CreatedAt,
        u.UserNama AS CustomerNama,
        COALESCE((
            SELECT GROUP_CONCAT(DISTINCT p.PaketNama SEPARATOR ' + ')
            FROM booking_detail bd
            LEFT JOIN paketjasa p ON bd.IDPaket = p.IDPaket
            WHERE bd.IDBooking = b.IDBooking 
              AND bd.BkgDetailJenis = 'Paket Jasa'
        ), 'Paket Jasa Custom') AS NamaPaket
    FROM booking b
    LEFT JOIN users u ON b.IDUser = u.IDUser
    WHERE b.BkgStatus = 'Diterima'
      AND EXISTS (
          SELECT 1 
          FROM booking_detail bd 
          WHERE bd.IDBooking = b.IDBooking 
            AND bd.BkgDetailJenis = 'Paket Jasa'
      )
      AND NOT EXISTS (
          SELECT 1 
          FROM event e 
          WHERE e.IDBooking = b.IDBooking
      )
    ORDER BY b.CreatedAt DESC
");

if (!$stmt_booking) {
    die("Query prepare gagal: " . $conn->error);
}
$stmt_booking->execute();
$bookings = $stmt_booking->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_booking->close();

/* ============== SEMUA KARYAWAN ============== */
$stmt_karyawan = $conn->prepare("SELECT IDUser, UserNama FROM users WHERE UserRole = 'Karyawan' ORDER BY UserNama");
$stmt_karyawan->execute();
$karyawans = $stmt_karyawan->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_karyawan->close();

/* ==========================================================
   =============== PROSES BUAT EVENT (PAKAI CLASS) =============
   ========================================================== */
$success_message = $error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['buat_event'])) {

    $idBooking    = (int)($_POST['id_booking'] ?? 0);
    $eventNama    = trim($_POST['event_nama'] ?? '');
    $eventLokasi  = trim($_POST['event_lokasi'] ?? '');
    $eventTanggal = $_POST['event_tanggal'] ?? '';
    $eventMulai   = $_POST['event_mulai'] ?? '';
    $eventDurasi  = (int)($_POST['event_durasi'] ?? 0);
    $karyawanIds  = $_POST['karyawan'] ?? [];

    if ($idBooking <= 0) {
        $error_message = "Booking tidak valid.";
    } elseif (empty($eventNama) || empty($eventLokasi) || empty($eventTanggal) || empty($eventMulai) || $eventDurasi < 1) {
        $error_message = "Lengkapi semua field event.";
    } elseif (empty($karyawanIds) || !is_array($karyawanIds)) {
        $error_message = "Pilih minimal 1 karyawan.";
    } else {
        $result = $eventAssign->createEvent(
            $idBooking,
            $eventNama,
            $eventLokasi,
            $eventTanggal,
            $eventMulai,
            $eventDurasi,
            $karyawanIds
        );

        if ($result !== false) {
            $_SESSION['success_message'] = "Event berhasil dibuat! (ID Event: $result)";
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        } else {
            $error_message = "Gagal membuat event. Silakan coba lagi.";
        }
    }
}

$success_message = $_SESSION['success_message'] ?? "";
unset($_SESSION['success_message']);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Penugasan Event - Artefax</title>
    <link href="../lib/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="../lib/ionicons/css/ionicons.min.css" rel="stylesheet">
    <link href="../lib/typicons.font/typicons.css" rel="stylesheet">
    <link href="../lib/select2/css/select2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/azia.css">
    <style>
        .select2-container { width: 100% !important; }
        .modal { 
            display:none; 
            position:fixed; top:0; left:0; width:100%; height:100%; 
            background:rgba(0,0,0,.6); z-index:9999; 
            justify-content:center; align-items:center; 
        }
        .modal .modal-dialog { max-width: 700px; }
        .alert { margin:15px 0; padding:12px; border-radius:5px; }
        .alert-success { background:#d4edda; color:#155724; border:1px solid #c3e6cb; }
        .alert-danger { background:#f8d7da; color:#721c24; border:1px solid #f5c6cb; }

        /* TABEL 100% SAMA DENGAN BOOKING AKTIF */
        .table {
            background:#fff;
            border-radius:12px;
            overflow:hidden;
            margin-bottom:0;
        }
        .table thead th {
            background:#3366ff !important;
            color:#ffffff !important;
            font-weight:600;
            text-transform:uppercase;
            font-size:13px;
            letter-spacing:0.5px;
            border:none;
            padding:15px 20px;
        }
        .table tbody td {
            padding:18px 20px;
            vertical-align:middle;
            border-top:1px solid #eef2f7;
            font-size:14px;
        }
        .table tbody tr:hover {
            background:#f1f5f9;
            transition:all 0.2s ease;
        }
        .table tbody tr:last-child td {
            border-bottom:none;
        }
        .table-responsive {
            border-radius:12px;
            overflow:hidden;
        }
    </style>
</head>
<body class="az-body">
<!-- HEADER & SIDEBAR TETAP SAMA -->
<div class="az-header">
      <div class="container">
        <div class="az-header-left">
          <a href="../template/index.html" class="az-logo"><span></span> Artefax</a>
          <a href="" id="azMenuShow" class="az-header-menu-icon d-lg-none"><span></span></a>
        </div>
        <!-- az-header-left -->
        <div class="az-header-menu">
          <div class="az-header-menu-header">
            <a href="index.html" class="az-logo"><span></span> Artefax</a>
            <a href="" class="close">×</a>
          </div>
          <!-- az-header-menu-header -->
          <ul class="nav">
            <li class="nav-item">
              <a href="../template/index.html" class="nav-link"><i class="typcn typcn-chart-area-outline"></i> Dashboard</a>
            </li>
           <li class="nav-item active">
              <a href="../form-karyawan/form-karyawan.php" class="nav-link"><i class="typcn typcn-group"></i>User</a>
            </li>
            <li class="nav-item">
              <a href="../form-pembayaran/daftar_pembayaran.php" class="nav-link"><i class="typcn typcn-puzzle-outline"></i>Pembayaran</a>
            </li>
            <li class="nav-item">
              <a href="../form-layanan/form-layanan.php" class="nav-link"><i class="typcn typcn-puzzle-outline"></i>Layanan</a>
            </li>
           <li class="nav-item">
             <a href="../form-laporan/LaporanAbsensiKaryawan.php" class="nav-link"><i class="typcn typcn-group-outline"></i>Laporan</a>
           </li>
            <li class="nav-item">
              <a href="" class="nav-link with-sub"><i class="typcn typcn-book"></i> Components</a>
              <div class="az-menu-sub">
                <div class="container">
                  <div>
                    <nav class="nav">
                      <a href="../template/elem-buttons.html" class="nav-link">Buttons</a>
                      <a href="../template/elem-dropdown.html" class="nav-link">Dropdown</a>
                      <a href="../template/elem-icons.html" class="nav-link">Icons</a>
                      <a href="../template/table-basic.html" class="nav-link">Table</a>
                    </nav>
                  </div>
                </div>
              </div>
            </li>
          </ul>
        </div><!-- az-header-menu -->
        <div class="az-header-right">
          <a href="https://www.bootstrapdash.com/demo/azia-free/docs/documentation.html" target="_blank" class="az-header-search-link"><i class="far fa-file-alt"></i></a>
          <a href="" class="az-header-search-link"><i class="fas fa-search"></i></a>
          <div class="az-header-message">
            <a href="#"><i class="typcn typcn-messages"></i></a>
          </div><!-- az-header-message -->
          <div class="dropdown az-header-notification">
            <a href="" class="new"><i class="typcn typcn-bell"></i></a>
            <div class="dropdown-menu">
              <div class="az-dropdown-header mg-b-20 d-sm-none">
                <a href="" class="az-header-arrow"><i class="icon ion-md-arrow-back"></i></a>
              </div>
              <h6 class="az-notification-title">Notifications</h6>
              <p class="az-notification-text">You have 2 unread notification</p>
              <div class="az-notification-list">
                <div class="media new">
                  <div class="az-img-user"><img src="../img/faces/face2.jpg" alt=""></div>
                  <div class="media-body">
                    <p>Congratulate <strong>Socrates Itumay</strong> for work anniversaries</p>
                    <span>Mar 15 12:32pm</span>
                  </div><!-- media-body -->
                </div><!-- media -->
                <div class="media new">
                  <div class="az-img-user online"><img src="../img/faces/face3.jpg" alt=""></div>
                  <div class="media-body">
                    <p><strong>Joyce Chua</strong> just created a new blog post</p>
                    <span>Mar 13 04:16am</span>
                  </div><!-- media-body -->
                </div><!-- media -->
                <div class="media">
                  <div class="az-img-user"><img src="../img/faces/face4.jpg" alt=""></div>
                  <div class="media-body">
                    <p><strong>Althea Cabardo</strong> just created a new blog post</p>
                    <span>Mar 13 02:56am</span>
                  </div><!-- media-body -->
                </div><!-- media -->
                <div class="media">
                  <div class="az-img-user"><img src="../img/faces/face5.jpg" alt=""></div>
                  <div class="media-body">
                    <p><strong>Adrian Monino</strong> added new comment on your photo</p>
                    <span>Mar 12 10:40pm</span>
                  </div><!-- media-body -->
                </div><!-- media -->
              </div><!-- az-notification-list -->
              <div class="dropdown-footer"><a href="">View All Notifications</a></div>
            </div><!-- dropdown-menu -->
          </div><!-- az-header-notification -->
          <div class="dropdown az-profile-menu">
            <a href="" class="az-img-user"><img src="../img/faces/face1.jpg" alt=""></a>
            <div class="dropdown-menu">
              <div class="az-dropdown-header d-sm-none">
                <a href="" class="az-header-arrow"><i class="icon ion-md-arrow-back"></i></a>
              </div>
              <div class="az-header-profile">
                <div class="az-img-user">
                  <img src="../img/faces/face1.jpg" alt="">
                </div><!-- az-img-user -->
                <h6>Aziana Pechon</h6>
                <span>Premium Member</span>
              </div><!-- az-header-profile -->

              <a href="" class="dropdown-item"><i class="typcn typcn-user-outline"></i> My Profile</a>
              <a href="" class="dropdown-item"><i class="typcn typcn-edit"></i> Edit Profile</a>
              <a href="" class="dropdown-item"><i class="typcn typcn-time"></i> Activity Logs</a>
              <a href="" class="dropdown-item"><i class="typcn typcn-cog-outline"></i> Account Settings</a>
              <a href="page-signin.html" class="dropdown-item"><i class="typcn typcn-power-outline"></i> Sign Out</a>
            </div><!-- dropdown-menu -->
          </div>
        </div><!-- az-header-right -->
      </div><!-- container -->
    </div><!-- az-header -->

    <!-- Content -->
    <div class="az-content pd-y-20 pd-lg-y-30 pd-xl-y-40">
        <div class="container">
            <div class="az-content-left az-content-left-components d-lg-block d-none">
                <div class="component-item">
                    <label>Menu User</label>
                    <nav class="nav flex-column">
                        <a href="form-user.php" class="nav-link">Daftar User</a>
                    </nav>
                    <label>Menu Karyawan</label>
                    <a href="form-karyawan.php" class="nav-link">Daftar Karyawan</a>
                    <a href="form-booking-active.php" class="nav-link">Booking Paket</a>
                    <a href="form-penugasan.php" class="nav-link active">Penugasan</a>
                </div>
            </div>

            <div class="az-content-body pd-lg-l-40 d-flex flex-column">
                <div class="az-content-breadcrumb">
                    <span>Data</span>
                    <span>Karyawan</span>
                </div>
                  <h2 class="az-content-title">Penugasan Event</h2>

            <?php if ($success_message): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success_message) ?></div>
            <?php endif; ?>
            <?php if ($error_message): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
            <?php endif; ?>

            <div class="table-responsive">
                <?php if ($bookings): ?>
                    <table class="table table-bordered table-hover">
                        <thead class="thead-light">
                            <tr>
                                <th>No</th>
                                <th>Customer</th>
                                <th>Paket</th>
                                <th>Tanggal</th>
                                <th>Alamat</th>
                                <th>Harga</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bookings as $i => $b): ?>
                                <tr>
                                    <td><?= $i + 1 ?></td>
                                    <td><?= htmlspecialchars($b['CustomerNama']) ?></td>
                                    <td><?= htmlspecialchars($b['NamaPaket'] ?? 'Custom') ?></td>
                                    <td><?= date('d/m/Y', strtotime($b['BkgTglMulai'])) ?></td>
                                    <td><?= htmlspecialchars($b['BkgAlamat']) ?></td>
                                    <td>Rp <?= number_format($b['BkgTotalHarga'], 0, ',', '.') ?></td>
                                    <td>
                                        <button class="btn btn-primary btn-sm" 
                                            onclick="openPopup(
                                                <?= $b['IDBooking'] ?>,
                                                '<?= addslashes(htmlspecialchars($b['NamaPaket'] ?? 'Event Jasa')) ?>',
                                                '<?= addslashes(htmlspecialchars($b['BkgAlamat'])) ?>',
                                                '<?= date('Y-m-d', strtotime($b['BkgTglMulai'])) ?>'
                                            )">
                                            Tugaskan
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="text-center text-muted py-5">Tidak ada booking yang perlu ditugaskan saat ini.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- MODAL — Nama Event & Lokasi jadi readonly -->
<div id="popupForm" class="modal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content p-4 bg-white rounded shadow">
            <h4 class="mb-4 text-primary">Buat Event Baru</h4>
            <form id="formEvent" method="POST">
                <input type="hidden" name="buat_event" value="1">
                <input type="hidden" name="id_booking" id="id_booking">

                <div class="row">
                    <div class="col-md-12 mb-3">
                        <label>Nama Event</label>
                        <input type="text" name="event_nama" id="event_nama" class="form-control" readonly>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12 mb-3">
                        <label>Lokasi</label>
                        <input type="text" name="event_lokasi" id="event_lokasi" class="form-control" readonly>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4  mb-3">
                        <label>Tanggal Event</label>
                        <input type="date" name="event_tanggal" id="event_tanggal" class="form-control" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label>Jam Mulai</label>
                        <input type="time" name="event_mulai" id="event_mulai" class="form-control" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label>Durasi (jam)</label>
                        <input type="number" name="event_durasi" id="event_durasi" class="form-control" min="1" max="24" value="8" required>
                    </div>
                </div>

                <div class="mb-4">
                    <label>Pilih Karyawan <small class="text-muted">(bisa lebih dari satu)</small></label>
                    <select name="karyawan[]" id="selectKaryawan" multiple class="form-control" style="height: 180px;" required>
                        <?php foreach ($karyawans as $k): ?>
                            <option value="<?= $k['IDUser'] ?>"><?= htmlspecialchars($k['UserNama']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="text-right">
                    <button type="button" class="btn btn-secondary mr-2" onclick="closePopup()">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Event</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="../lib/jquery/jquery.min.js"></script>
<script src="../lib/select2/js/select2.min.js"></script>
<script>
function openPopup(id, namaPaket, alamat, tgl) {
    $('#formEvent')[0].reset();
    $('#id_booking').val(id);
    $('#event_nama').val(namaPaket);
    $('#event_lokasi').val(alamat);
    $('#event_tanggal').val(tgl);

    // AMBIL JAM SEKARANG DARI DEVICE (LOCAL TIME) — DIBULATKAN KE 5 MENIT TERDEKAT
    const now = new Date();
    const minutes = now.getMinutes();
    const remainder = minutes % 5;
    const roundedMinutes = remainder < 3 ? minutes - remainder : minutes + (5 - remainder);

    if (roundedMinutes >= 60) {
        now.setHours(now.getHours() + 1);
        now.setMinutes(0);
    } else {
        now.setMinutes(roundedMinutes);
    }
    now.setSeconds(0);
    now.setMilliseconds(0);

    const jam   = String(now.getHours()).padStart(2, '0');
    const menit = String(now.getMinutes()).padStart(2, '0');
    $('#event_mulai').val(`${jam}:${menit}`);

    $('#event_durasi').val('8');

    $("#popupForm").fadeIn(200);

    setTimeout(() => {
        $("#selectKaryawan").select2({
            dropdownParent: $('#popupForm'),
            placeholder: "Pilih karyawan...",
            width: '100%'
        });
    }, 150);
}

function closePopup() {
    $("#popupForm").fadeOut(200);
    if ($('#selectKaryawan').data('select2')) {
        $('#selectKaryawan').select2('destroy');
    }
}

$(document).on('click', function(e) {
    if ($(e.target).is('#popupForm')) closePopup();
});
</script>

</body>
</html>