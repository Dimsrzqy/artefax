<?php
session_start();
// --- START: VERIFIKASI DAN ADAPTASI SESI KRITIS ---
// CRITICAL FIX 1: Adaptasi dari kunci sesi 'user' ke kunci top-level yang diharapkan template
// Ini mengatasi loop login dan Guest User
if (isset($_SESSION['user']) && is_array($_SESSION['user'])) {
    // Menyalin data dari array nested 'user' ke top-level keys
    $_SESSION['IDUser'] = $_SESSION['user']['IDUser'] ?? null;
    $_SESSION['UserNama'] = $_SESSION['user']['UserNama'] ?? 'Guest User';
    $_SESSION['UserRole'] = $_SESSION['user']['UserRole'] ?? 'Unknown Role';
}

// CRITICAL FIX 2: VERIFIKASI LOGIN
if (!isset($_SESSION['IDUser']) || empty($_SESSION['IDUser'])) {
    // Sesuaikan path ke halaman login Anda jika berbeda
    header("Location: ../../view/login.php"); 
    exit;
}
// --- END: VERIFIKASI DAN ADAPTASI SESI KRITIS ---

require_once __DIR__ . "/../../config/koneksi.php";
require_once __DIR__ . "/../../class/users.php";
require_once __DIR__ . "/../../class/EventAssignment.php";

$db = new Database();
$conn = $db->getConnection();
if (!$conn) die("<p style='color:red;'>Koneksi database gagal.</p>");

$user        = new User($conn);
$eventAssign = new EventAssignment($conn);

// Otomatis ubah status event yang sudah selesai
$eventAssign->updateStatusOtomatis();

// --- START: DATA USER LOGIN (Ambil dari $_SESSION yang sudah diadaptasi) ---
$loggedInUser = [
    'UserNama' => $_SESSION['UserNama'] ?? 'Guest User', 
    'UserRole' => $_SESSION['UserRole'] ?? 'Unknown Role', 
];
$defaultProfileImage = '../img/faces/face1.jpg'; 
// --- END: DATA USER LOGIN ---

/* ============== BOOKING YANG BELUM PERNAH DIBUATKAN EVENT ============== */
// Query ini memastikan: Status 'Diterima', Ada 'Paket Jasa', DAN BELUM ada entri di tabel 'event'
$stmt_booking = $conn->prepare("
    SELECT 
        b.IDBooking,
        b.BkgAlamat,
        b.BkgTglMulai,
        b.BkgTglSelesai, /* Ambil tgl selesai untuk perhitungan durasi di tampilan */
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
    $idBooking     = (int)($_POST['id_booking'] ?? 0);
    $eventNama     = trim($_POST['event_nama'] ?? '');
    $eventLokasi   = trim($_POST['event_lokasi'] ?? '');
    $eventTanggal  = $_POST['event_tanggal'] ?? '';
    $eventMulai    = $_POST['event_mulai'] ?? '';
    $eventDurasi   = (int)($_POST['event_durasi'] ?? 0);
    $karyawanIds   = $_POST['karyawan'] ?? [];

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

$success_message = $_SESSION['success_message'] ?? $success_message;
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
        /* --- START: Perbaikan untuk Fixed Layout --- */
        .az-body {
            padding-top: 70px !important; 
        }
        .az-header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1040;
            background-color: #fff;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .az-content-left {
            position: fixed;
            top: 70px; 
            bottom: 0;
            z-index: 1020;
            overflow-y: auto;
            background-color: #fff;
        }
        .az-content-left .component-item label {
            margin-top: 40px;
            margin-bottom: 10px;
            display: block; /* ← DITAMBAHKAN INI */
        }
        
        @media (min-width: 992px) {
            .az-content-body {
                padding-top: 0 !important;
                margin-left: 240px !important; 
            }
        }
        @media (max-width: 991.98px) {
            .az-content-left {
                position: static;
                top: auto;
                bottom: auto;
                overflow-y: visible;
            }
            .az-content-body {
                margin-left: 0 !important;
            }
            .az-body {
                padding-top: 0 !important; 
            }
        }
        /* --- END: Perbaikan untuk Fixed Layout --- */


        /* CSS DISAMAKAN DENGAN BOOKING ACTIVE */
        .badge-paket{background:#28a745;color:#fff;padding:6px 12px;margin:3px 3px 3px 0;font-size:12px;border-radius:50px;display:inline-block;font-weight:500;white-space: nowrap;}
        .table{background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 15px rgba(0,0,0,0.08);margin-bottom:0;}
        .table thead th{background:#3366ff !important;color:#fff !important;font-weight:600;text-transform:uppercase;font-size:13px;letter-spacing:0.8px;border:none;padding:16px 20px;}
        .table tbody td{padding:18px 20px;vertical-align:middle;border-top:1px solid #eef2f7;font-size:14px;color:#2d3748;}
        .table tbody tr:hover{background:#f8faff;transition:all .2s;}
        .az-content-title{font-weight:700;color:#1a202c;margin-bottom:25px;}
        .alert{border-radius:10px;box-shadow:0 2px 10px rgba(0,0,0,0.1); padding: 15px;}
        .text-durasi{font-size:13px;color:#4a5568;font-weight:500;}
        .btn-assign{padding: 6px 14px; font-size: 13px;}
        
        /* MODAL DAN SELECT2 */
        .select2-container { width: 100% !important; margin-top: 5px; }
        .select2-container .select2-selection--multiple { min-height: 40px !important; border-color: #ced4da !important; }
        .modal { 
            display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; 
            background: rgba(0,0,0,.6); z-index: 9999; justify-content: center; align-items: center; 
            overflow: auto; 
        }
        .modal-dialog { max-width: 750px; margin: 1.75rem auto; }
        .modal-content { border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        .form-control[readonly] { background-color: #f8f9fa; border-style: dashed; }
    </style>
</head>
<body class="az-body">
    <div class="az-header">
        <div class="container">
            <div class="az-header-left">
                <a href="../template/index.html" class="az-logo"><span></span> Artefax</a>
                <a href="" id="azMenuShow" class="az-header-menu-icon d-lg-none"><span></span></a>
            </div>
            <div class="az-header-menu">
                <div class="az-header-menu-header">
                    <a href="index.html" class="az-logo"><span></span> Artefax</a>
                    <a href="" class="close">&times;</a>
                </div>
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
                        <a href="../form-laporan/LaporanKeuangan.php" class="nav-link"><i class="typcn typcn-group-outline"></i>Laporan</a>
                    </li>
                </ul>
            </div>
            <div class="az-header-right">
                <a href="https://www.bootstrapdash.com/demo/azia-free/docs/documentation.html" target="_blank" class="az-header-search-link"><i class="far fa-file-alt"></i></a>
                <a href="" class="az-header-search-link"><i class="fas fa-search"></i></a>
                <div class="az-header-message">
                    <a href="#"><i class="typcn typcn-messages"></i></a>
                </div>
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
                                </div>
                            </div>
                            <div class="media new">
                                <div class="az-img-user online"><img src="../img/faces/face3.jpg" alt=""></div>
                                <div class="media-body">
                                    <p><strong>Joyce Chua</strong> just created a new blog post</p>
                                    <span>Mar 13 04:16am</span>
                                </div>
                            </div>
                        </div>
                        <div class="dropdown-footer"><a href="">View All Notifications</a></div>
                    </div>
                </div>
                <div class="dropdown az-profile-menu">
                    <a href="../../View/profile.php" class="az-img-user"><img src="<?= $defaultProfileImage ?>" alt=""></a>
                    <div class="dropdown-menu">
                        <div class="az-dropdown-header d-sm-none">
                            <a href="" class="az-header-arrow"><i class="icon ion-md-arrow-back"></i></a>
                        </div>
                        <div class="az-header-profile">
                            <div class="az-img-user">
                                <img src="<?= $defaultProfileImage ?>" alt="">
                            </div>
                            <h6><?= htmlspecialchars($loggedInUser['UserNama']) ?></h6>
                            <span><?= htmlspecialchars($loggedInUser['UserRole']) ?></span>
                        </div>
                        <a href="../../View/profile.php" class="dropdown-item"><i class="typcn typcn-user-outline"></i> My Profile</a>
                        <a href="../../logout.php" class="dropdown-item"><i class="typcn typcn-power-outline"></i> Sign Out</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
            </div>
        </div>
    </div>
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
                    <span>Penugasan</span>
                </div>
                <h2 class="az-content-title"><i class="fas fa-calendar-alt"></i> Penugasan Event Baru</h2>
                <p class="mb-4 text-muted">Daftar booking yang sudah Diterima dan memiliki Paket Jasa, namun **belum memiliki jadwal event**.</p>
                
                <?php if ($success_message): ?>
                    <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($success_message) ?></div>
                <?php endif; ?>
                <?php if ($error_message): ?>
                    <div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error_message) ?></div>
                <?php endif; ?>

                <div class="table-responsive">
                    <?php if ($bookings): ?>
                        <table class="table table-hover mg-b-0">
                            <thead class="thead-light">
                                <tr>
                                    <th>No</th><th>Customer</th><th>Paket Jasa</th><th>Mulai</th><th>Durasi Estimasi</th><th>Total Harga</th><th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($bookings as $i => $b): ?>
                                    <?php
                                    // Hitung durasi estimasi (untuk tampilan saja)
                                    $mulai     = new DateTime($b['BkgTglMulai']);
                                    $selesai   = new DateTime($b['BkgTglSelesai'] ?? $b['BkgTglMulai']);
                                    
                                    if ($mulai >= $selesai) {
                                        $durasi = "N/A";
                                    } else {
                                        $diffInSeconds = $selesai->getTimestamp() - $mulai->getTimestamp();
                                        $days = floor($diffInSeconds / (60 * 60 * 24));
                                        $hours = floor(($diffInSeconds % (60 * 60 * 24)) / (60 * 60));
                                        
                                        $durasiParts = [];
                                        if ($days > 0) {
                                            $durasiParts[] = "$days hari";
                                        }
                                        if ($hours > 0) {
                                            $durasiParts[] = "$hours jam";
                                        }
                                        
                                        $durasi = count($durasiParts) > 0 ? implode(', ', $durasiParts) : "Beberapa menit";
                                    }
                                    ?>
                                    <tr>
                                        <td><?= $i + 1 ?></td>
                                        <td><strong><?= htmlspecialchars($b['CustomerNama']) ?></strong></td>
                                        <td>
                                            <?php 
                                            $pakets = explode(' + ', $b['NamaPaket'] ?? '');
                                            foreach ($pakets as $p):
                                                if (trim($p)): ?>
                                                    <span class="badge-paket"><?= htmlspecialchars(trim($p)) ?></span>
                                                <?php endif;
                                            endforeach; ?>
                                        </td>
                                        <td><?= date('d/m/Y H:i', strtotime($b['BkgTglMulai'])) ?></td>
                                        <td class="text-durasi"><strong><?= $durasi ?></strong></td>
                                        <td><strong>Rp <?= number_format($b['BkgTotalHarga'], 0, ',', '.') ?></strong></td>
                                        <td>
                                            <button class="btn btn-primary btn-sm btn-assign"
                                                onclick="openPopup(<?= $b['IDBooking'] ?>, 
                                                                    '<?= addslashes(htmlspecialchars($b['NamaPaket'] ?? 'Event Jasa')) ?>', 
                                                                    '<?= addslashes(htmlspecialchars($b['BkgAlamat'])) ?>', 
                                                                    '<?= date('Y-m-d', strtotime($b['BkgTglMulai'])) ?>')">
                                                <i class="fas fa-user-tag"></i> Tugaskan
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="text-center py-5 border rounded-lg bg-light mt-3">
                            <i class="fas fa-info-circle text-secondary mb-3" style="font-size: 30px;"></i>
                            <p class="text-muted mb-0">Semua booking yang perlu ditugaskan sudah memiliki event saat ini.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div id="popupForm" class="modal" onclick="if(event.target.id === 'popupForm') closePopup()">
        <div class="modal-dialog">
            <div class="modal-content p-4 bg-white">
                <h4 class="mb-4 text-primary"><i class="fas fa-calendar-plus"></i> Detail Event & Penugasan</h4>
                <form id="formEvent" method="POST">
                    <input type="hidden" name="buat_event" value="1">
                    <input type="hidden" name="id_booking" id="id_booking">

                    <div class="row mb-3">
                        <div class="col-md-12 mb-3">
                            <label class="font-weight-bold">Nama Event (Berdasarkan Paket)</label>
                            <input type="text" name="event_nama" id="event_nama" class="form-control" readonly>
                        </div>
                        <div class="col-md-12">
                            <label class="font-weight-bold">Lokasi Event</label>
                            <input type="text" name="event_lokasi" id="event_lokasi" class="form-control" readonly>
                        </div>
                    </div>

                    <div class="row mb-3 border-top pt-3">
                        <div class="col-md-4 mb-3">
                            <label class="font-weight-bold">Tanggal Event</label>
                            <input type="date" name="event_tanggal" id="event_tanggal" class="form-control" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="font-weight-bold">Jam Mulai</label>
                            <input type="time" name="event_mulai" id="event_mulai" class="form-control" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="font-weight-bold">Durasi (jam)</label>
                            <input type="number" name="event_durasi" id="event_durasi" class="form-control" min="1" max="24" value="8" required>
                        </div>
                    </div>

                    <div class="mb-4 border-top pt-3">
                        <label class="font-weight-bold">Pilih Karyawan <small class="text-muted">(Pilih minimal satu)</small></label>
                        <select name="karyawan[]" id="selectKaryawan" multiple class="form-control" required></select>
                        <small class="text-danger mt-2 d-block" id="infoBentrok" style="display:none;"><i class="fas fa-exclamation-circle"></i></small>
                    </div>

                    <div class="text-right border-top pt-3">
                        <button type="button" class="btn btn-secondary mr-2" onclick="closePopup()"><i class="fas fa-times"></i> Batal</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan Event</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

<script src="../lib/jquery/jquery.min.js"></script>
<script src="../lib/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../lib/select2/js/select2.min.js"></script>
<script src="../js/azia.js"></script>
<script>
    // Data Karyawan dari PHP (semua karyawan di sistem)
    const semuaKaryawan = [
        <?php foreach ($karyawans as $k): ?>
            {id: "<?= trim($k['IDUser']) ?>", nama: "<?= addslashes(htmlspecialchars($k['UserNama'])) ?>"},
        <?php endforeach; ?>
    ];

    console.log('%c═══════════════════════════════════', 'color: blue; font-weight: bold');
    console.log('%c SEMUA KARYAWAN DI SISTEM', 'color: blue; font-weight: bold');
    console.log('%c═══════════════════════════════════', 'color: blue; font-weight: bold');
    console.table(semuaKaryawan);

    let select2Instance = null;

    $(document).ready(function() {
        $('.az-header .dropdown-menu').appendTo('.az-header-right .dropdown.az-profile-menu');
        
        $('#azMenuShow').on('click', function(e) {
            e.preventDefault();
            $('.az-header-menu').toggleClass('show');
            $(this).toggleClass('open');
        });
        
        $('.az-header-menu .close').on('click', function(e) {
            e.preventDefault();
            $('.az-header-menu').removeClass('show');
            $('#azMenuShow').removeClass('open');
        });

        setTimeout(() => $('.alert').fadeOut('slow'), 5000);

        $('#selectKaryawan').select2({
            dropdownParent: $('#popupForm'),
            placeholder: "Pilih karyawan...",
            width: '100%'
        });
        select2Instance = $('#selectKaryawan');
    });

    function openPopup(id, namaPaket, alamat, tgl) {
        console.log('%c═══════════════════════════════════', 'color: green; font-weight: bold');
        console.log('%c OPENING PENUGASAN FORM', 'color: green; font-weight: bold');
        console.log('%c═══════════════════════════════════', 'color: green; font-weight: bold');
        
        if (select2Instance && select2Instance.hasClass('select2-hidden-accessible')) {
            select2Instance.select2('destroy');
        }

        $('#formEvent')[0].reset();
        $('#id_booking').val(id);
        $('#event_nama').val(namaPaket);
        $('#event_lokasi').val(alamat);
        $('#event_tanggal').val(tgl);
        $('#infoBentrok').hide();

        const now = new Date();
        now.setMinutes(Math.round(now.getMinutes() / 5) * 5);
        now.setSeconds(0);
        $('#event_mulai').val(now.toTimeString().slice(0,5));
        $('#event_durasi').val('8');
        
        $("#popupForm").fadeIn(200);

        select2Instance = $('#selectKaryawan').select2({
            dropdownParent: $('#popupForm'),
            placeholder: "Pilih karyawan...",
            width: '100%'
        });
        
        select2Instance.val(null).trigger('change');
        
        // LANGSUNG load karyawan tersedia saat popup dibuka
        loadKaryawanTersedia();
    }

    function loadKaryawanTersedia() {
        console.log('%c═══════════════════════════════════', 'color: orange; font-weight: bold');
        console.log('%c LOADING KARYAWAN TERSEDIA', 'color: orange; font-weight: bold');
        console.log('%c Filter: TIDAK di event Menunggu/Berjalan', 'color: orange; font-weight: bold');
        console.log('%c═══════════════════════════════════', 'color: orange; font-weight: bold');

        if (select2Instance && select2Instance.hasClass('select2-hidden-accessible')) {
            select2Instance.select2('destroy');
        }

        console.log('🌐 Mengirim request ke server...');
        
        $.ajax({
            url: 'get_karyawan_tersedia.php',
            method: 'GET',
            dataType: 'json',
            cache: false,
            success: function(response) {
                console.log('%c═══════════════════════════════════', 'color: purple; font-weight: bold');
                console.log('%c SERVER RESPONSE', 'color: purple; font-weight: bold');
                console.log('%c═══════════════════════════════════', 'color: purple; font-weight: bold');
                console.log('Raw response:', response);
                
                if (response.error) {
                    console.error('❌ Server error:', response.error);
                    $('#infoBentrok').html('<i class="fas fa-exclamation-circle"></i> ' + response.error).show();
                    buildKaryawanList([]);
                    return;
                }
                
                if (!Array.isArray(response)) {
                    console.error('❌ Response bukan array!');
                    buildKaryawanList([]);
                    return;
                }
                
                console.log('✅ Karyawan tersedia dari server:');
                console.table(response);
                
                buildKaryawanList(response);
            },
            error: function(xhr, status, error) {
                console.log('%c═══════════════════════════════════', 'color: red; font-weight: bold');
                console.log('%c AJAX ERROR', 'color: red; font-weight: bold');
                console.log('%c═══════════════════════════════════', 'color: red; font-weight: bold');
                console.error('Status:', status);
                console.error('Error:', error);
                console.error('Response:', xhr.responseText);
                
                buildKaryawanList([]);
                $('#infoBentrok').html('<i class="fas fa-exclamation-circle"></i> Gagal memuat data. Cek console atau error log.').show();
            }
        });
    }

    function buildKaryawanList(availableEmployees) {
        console.log('%c═══════════════════════════════════', 'color: teal; font-weight: bold');
        console.log('%c BUILDING DROPDOWN', 'color: teal; font-weight: bold');
        console.log('%c═══════════════════════════════════', 'color: teal; font-weight: bold');
        
        const $select = $('#selectKaryawan');
        $select.empty();

        const availableOptions = [];
        const availableIds = [];

        // Server mengirim HANYA karyawan yang TERSEDIA
        availableEmployees.forEach(emp => {
            const empId = String(emp.id).trim();
            const empNama = emp.nama;
            
            console.log(`✅ TERSEDIA - ID: "${empId}" - Nama: ${empNama}`);
            
            const option = new Option(empNama, empId, false, false);
            availableOptions.push(option);
            availableIds.push(empId);
        });

        // Log karyawan yang SIBUK (untuk debugging)
        const availableIdSet = new Set(availableIds.map(id => String(id).trim()));
        const busyEmployees = [];
        
        semuaKaryawan.forEach(k => {
            const kId = String(k.id).trim();
            if (!availableIdSet.has(kId)) {
                console.log(`❌ SIBUK - ID: "${kId}" - Nama: ${k.nama} (Event Menunggu/Berjalan)`);
                busyEmployees.push(k.nama);
            }
        });

        console.log('%c═══════════════════════════════════', 'color: blue; font-weight: bold');
        console.log(`%c📊 HASIL: ${availableOptions.length} tersedia, ${busyEmployees.length} sibuk`, 'color: blue; font-weight: bold');
        if (busyEmployees.length > 0) {
            console.log('%cKaryawan Sibuk:', 'color: red; font-weight: bold', busyEmployees.join(', '));
        }
        console.log('%c═══════════════════════════════════', 'color: blue; font-weight: bold');

        // Tambahkan ke dropdown
        $select.append(availableOptions);

        if (availableOptions.length === 0) {
            $('#infoBentrok').html('<i class="fas fa-exclamation-circle"></i> Semua karyawan sedang bertugas (Event Menunggu/Berjalan)!').show();
            $select.prop('disabled', true);
            console.warn('⚠️  SEMUA KARYAWAN SIBUK!');
        } else {
            $('#infoBentrok').hide();
            $select.prop('disabled', false);
        }

        // Reinitialize Select2
        select2Instance = $select.select2({
            dropdownParent: $('#popupForm'),
            placeholder: "Pilih karyawan...",
            width: '100%'
        });
    }

    function closePopup() {
        console.log('Closing popup');
        $("#popupForm").fadeOut(200);
        if (select2Instance) {
            select2Instance.val(null).trigger('change');
        }
        $('#infoBentrok').hide();
    }

    // TIDAK PERLU event listener untuk tanggal/jam karena filter berdasarkan status saja
    // Jika Anda ingin reload saat ganti tanggal/jam, bisa tetap panggil loadKaryawanTersedia()
</script>
</body>
</html>