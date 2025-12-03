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

// Otomatis ubah status event yang sudah selesai
$eventAssign->updateStatusOtomatis();

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
                    <a href="" class="close">×</a>
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
                            <div class="media">
                                <div class="az-img-user"><img src="../img/faces/face4.jpg" alt=""></div>
                                <div class="media-body">
                                    <p><strong>Althea Cabardo</strong> just created a new blog post</p>
                                    <span>Mar 13 02:56am</span>
                                </div>
                            </div>
                            <div class="media">
                                <div class="az-img-user"><img src="../img/faces/face5.jpg" alt=""></div>
                                <div class="media-body">
                                    <p><strong>Adrian Monino</strong> added new comment on your photo</p>
                                    <span>Mar 12 10:40pm</span>
                                </div>
                            </div>
                        </div>
                        <div class="dropdown-footer"><a href="">View All Notifications</a></div>
                    </div>
                </div>
                <div class="dropdown az-profile-menu">
                    <a href="" class="az-img-user"><img src="../img/faces/face1.jpg" alt=""></a>
                    <div class="dropdown-menu">
                        <div class="az-dropdown-header d-sm-none">
                            <a href="" class="az-header-arrow"><i class="icon ion-md-arrow-back"></i></a>
                        </div>
                        <div class="az-header-profile">
                            <div class="az-img-user">
                                <img src="../img/faces/face1.jpg" alt="">
                            </div>
                            <h6>Aziana Pechon</h6>
                            <span>Premium Member</span>
                        </div><a href="" class="dropdown-item"><i class="typcn typcn-user-outline"></i> My Profile</a>
                        <a href="" class="dropdown-item"><i class="typcn typcn-edit"></i> Edit Profile</a>
                        <a href="" class="dropdown-item"><i class="typcn typcn-time"></i> Activity Logs</a>
                        <a href="" class="dropdown-item"><i class="typcn typcn-cog-outline"></i> Account Settings</a>
                        <a href="page-signin.html" class="dropdown-item"><i class="typcn typcn-power-outline"></i> Sign Out</a>
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
                                    $mulai    = new DateTime($b['BkgTglMulai']);
                                    $selesai  = new DateTime($b['BkgTglSelesai'] ?? $b['BkgTglMulai']);
                                    
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
<script>
    // Data Karyawan dimuat dari PHP
    const semuaKaryawan = [
        <?php foreach ($karyawans as $k): ?>
            {id: <?= $k['IDUser'] ?>, nama: "<?= addslashes(htmlspecialchars($k['UserNama'])) ?>"},
        <?php endforeach; ?>
    ];

    let select2Instance = null;

    function openPopup(id, namaPaket, alamat, tgl) {
        if (select2Instance) {
            select2Instance.select2('destroy');
            select2Instance = null;
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

        $('#selectKaryawan').select2({
            dropdownParent: $('#popupForm'),
            placeholder: "Pilih karyawan...",
            width: '100%'
        });
        select2Instance = $('#selectKaryawan');

        updateKaryawanList();
    }

    function updateKaryawanList() {
        const tanggal = $('#event_tanggal').val();
        const jam     = $('#event_mulai').val();
        const durasi  = $('#event_durasi').val() || 8;

        if (!tanggal || !jam) {
            rebuildKaryawanList([]);
            return;
        }
        
        const selected = select2Instance ? select2Instance.val() : [];
        if (select2Instance) select2Instance.select2('destroy');

        // Panggil AJAX untuk mendapatkan ID karyawan yang sedang bentrok
        $.get('get_karyawan_tersedia.php', {
            tanggal: tanggal,
            jam_mulai: jam,
            durasi: durasi
        }, function(busyIds) {
            rebuildKaryawanList(busyIds || [], selected);
        }, 'json').fail(function() {
            rebuildKaryawanList([], selected);
            $('#infoBentrok').text('Gagal memuat data ketersediaan karyawan.').show();
        });
    }

    function rebuildKaryawanList(busyIds, previouslySelected = []) {
        const $select = $('#selectKaryawan');
        $select.empty();

        let tersedia = 0;
        let availableOptions = [];
        let availableIds = [];

        semuaKaryawan.forEach(k => {
            const isBusy = busyIds.includes(k.id);
            
            if (!isBusy) {
                // HANYA TAMBAHKAN JIKA TIDAK BENTROK
                const option = new Option(k.nama, k.id, false, false);
                availableOptions.push(option);
                availableIds.push(k.id.toString());
                tersedia++;
            }
        });

        $select.append(availableOptions);

        if (tersedia === 0 && semuaKaryawan.length > 0) {
            $('#infoBentrok').text('Semua karyawan sedang bertugas pada waktu ini!').show();
            // Non-aktifkan select jika tidak ada yang tersedia
            $select.prop('disabled', true); 
        } else {
            $('#infoBentrok').hide();
            $select.prop('disabled', false); 
        }

        select2Instance = $select.select2({
            dropdownParent: $('#popupForm'),
            placeholder: "Pilih karyawan...",
            width: '100%'
        });
        
        // Filter pilihan sebelumnya agar hanya ID yang tersedia yang dipilih
        const filteredSelected = previouslySelected.filter(id => availableIds.includes(id));
        select2Instance.val(filteredSelected).trigger('change');
    }

    function closePopup() {
        $("#popupForm").fadeOut(200);
        if (select2Instance) {
            select2Instance.select2('destroy');
            select2Instance = null;
        }
        $('#infoBentrok').hide();
    }

    // Event listener untuk update ketersediaan karyawan
    $(document).on('change', '#event_tanggal, #event_mulai, #event_durasi', updateKaryawanList);
    $(document).on('click', function(e) {
        if ($(e.target).is('#popupForm')) closePopup();
    });
</script>
</body>
</html>