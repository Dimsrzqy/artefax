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

/* ============== BOOKING BELUM JADI EVENT ============== */
$stmt = $conn->prepare("
    SELECT b.*, 
           u.UserNama AS CustomerNama,
           p.PaketNama AS NamaPaket
    FROM booking b
    LEFT JOIN users u ON b.IDUser = u.IDUser
    LEFT JOIN paketjasa p ON b.IDPaket = p.IDPaket
    WHERE b.BkgStatus = 'Diterima'
      AND b.BkgJenis = 'Jasa'
      AND b.IDBooking NOT IN (SELECT IDBooking FROM event)
    ORDER BY b.CreatedAt DESC
");



$stmt->execute();
$bookings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

/* ============== SEMUA KARYAWAN ============== */
$stmt = $conn->prepare("SELECT IDUser, UserNama FROM users WHERE UserRole = 'Karyawan' ORDER BY UserNama");
$stmt->execute();
$karyawans = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

/* ==========================================================
   =============== PROSES BUAT EVENT (ROBUST) ================
   ========================================================== */
$success_message = $error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['buat_event'])) {

    // sanitize & map
    $idBooking    = isset($_POST['id_booking']) ? (int)$_POST['id_booking'] : 0;
    $eventNama    = isset($_POST['event_nama']) ? trim($_POST['event_nama']) : '';
    $eventLokasi  = isset($_POST['event_lokasi']) ? trim($_POST['event_lokasi']) : '';
    $eventTanggal = isset($_POST['event_tanggal']) ? $_POST['event_tanggal'] : '';
    $eventMulai   = isset($_POST['event_mulai']) ? $_POST['event_mulai'] : '';
    $eventDurasi  = isset($_POST['event_durasi']) ? (int)$_POST['event_durasi'] : 0;
    $karyawanIds  = isset($_POST['karyawan']) && is_array($_POST['karyawan']) ? array_map('intval', $_POST['karyawan']) : [];

    // Basic validation
    if ($idBooking <= 0) {
        $error_message = "Booking tidak valid.";
    } elseif ($eventNama === "" || $eventLokasi === "" || $eventTanggal === "" || $eventMulai === "" || $eventDurasi < 1) {
        $error_message = "Lengkapi semua field event (nama, lokasi, tanggal, mulai, durasi).";
    } elseif (empty($karyawanIds)) {
        $error_message = "Pilih minimal 1 karyawan.";
    } else {
        // lanjutkan validasi DB dan insert dalam transaction
        try {
            // 1) cek booking ada & status diterima
            $stmt = $conn->prepare("SELECT IDBooking, BkgStatus FROM booking WHERE IDBooking = ? LIMIT 1");
            $stmt->bind_param("i", $idBooking);
            $stmt->execute();
            $bk = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$bk) {
                throw new Exception("Booking tidak ditemukan.");
            }
            if (strtolower(trim($bk['BkgStatus'])) !== 'diterima') {
                throw new Exception("Booking belum berstatus 'Diterima'.");
            }

            // 2) cek apakah booking sudah dipakai (double-check)
            $stmt = $conn->prepare("SELECT IDEvent FROM event WHERE IDBooking = ? LIMIT 1");
            $stmt->bind_param("i", $idBooking);
            $stmt->execute();
            $exists = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if ($exists) {
                throw new Exception("Booking ini sudah dibuat event sebelumnya (IDEvent: " . $exists['IDEvent'] . ").");
            }

            // 3) validasi semua karyawan ada
            // buat query dinamika dengan IN(...)
            $placeholders = implode(',', array_fill(0, count($karyawanIds), '?'));
            $types = str_repeat('i', count($karyawanIds));
            $sql = "SELECT IDUser FROM users WHERE UserRole = 'Karyawan' AND IDUser IN ($placeholders)";
            $stmt = $conn->prepare($sql);
            // bind params dynamically
            $bind_names = [];
            foreach ($karyawanIds as $k => $val) {
                $bind_names[] = &$karyawanIds[$k];
            }
            if ($bind_names) {
                // mysqli_stmt::bind_param requires string types first, then references
                array_unshift($bind_names, $types);
                call_user_func_array([$stmt, 'bind_param'], $bind_names);
            }
            $stmt->execute();
            $res = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            if (count($res) !== count($karyawanIds)) {
                throw new Exception("Salah satu atau beberapa karyawan tidak ditemukan atau bukan role Karyawan.");
            }

            // 4) mulai transaction dan insert event + event_karyawan
            $conn->autocommit(false);
            $conn->begin_transaction();

            // hitung waktu selesai
            $startDt = new DateTime($eventTanggal . ' ' . $eventMulai);
            $endDt   = clone $startDt;
            $endDt->modify("+{$eventDurasi} hours");
            // format time mysql TIME
            $timeMulai = $startDt->format('H:i:s');
            $timeSelesai = $endDt->format('H:i:s');

            // Insert event
            $sqlInsertEvent = "INSERT INTO event
                (EventNama, EventLokasi, IDBooking, EventTanggal, EventDurasi, EventMulai, EventSelesai, EventStatus, CreatedAt)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'Menunggu', NOW())";
            $stmt = $conn->prepare($sqlInsertEvent);
            if (!$stmt) throw new Exception("Prepare insert event gagal: " . $conn->error);

            // bind types: s s i s i s s  => "ssisiss"
            $types_event = "ssisiss";
            $stmt->bind_param(
                $types_event,
                $eventNama,
                $eventLokasi,
                $idBooking,
                $eventTanggal,
                $eventDurasi,
                $timeMulai,
                $timeSelesai
            );

            if (!$stmt->execute()) {
                $err = $stmt->error;
                $stmt->close();
                throw new Exception("Insert event gagal: " . $err);
            }
            $idEvent = $conn->insert_id;
            $stmt->close();

            // Insert event_karyawan
            $stmt = $conn->prepare("INSERT INTO event_karyawan (IDEvent, IDKaryawan) VALUES (?, ?)");
            if (!$stmt) throw new Exception("Prepare insert event_karyawan gagal: " . $conn->error);
            foreach ($karyawanIds as $idK) {
                $idK = (int)$idK;
                $stmt->bind_param("ii", $idEvent, $idK);
                if (!$stmt->execute()) {
                    $err = $stmt->error;
                    $stmt->close();
                    throw new Exception("Insert event_karyawan gagal untuk karyawan $idK: " . $err);
                }
            }
            $stmt->close();

            // commit
            $conn->commit();
            $conn->autocommit(true);

            $_SESSION['success_message'] = "Event berhasil dibuat dan karyawan ditugaskan (Event ID: $idEvent).";
            // redirect supaya menghindari resubmit form
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;

        } catch (Exception $e) {
            // rollback dan tampilkan pesan
            if ($conn->connect_errno === 0) {
                $conn->rollback();
                $conn->autocommit(true);
            }
            $error_message = "Gagal membuat event: " . $e->getMessage();
            // juga tulis ke error_log agar bisa dicek di server
            error_log("[form-penugasan] " . $e->getMessage());
        }
    }
}

$success_message = $_SESSION['success_message'] ?? "";
unset($_SESSION['success_message']);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Penugasan Event - Artefax</title>


    <link href="../lib/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="../lib/ionicons/css/ionicons.min.css" rel="stylesheet">
    <link href="../lib/typicons.font/typicons.css" rel="stylesheet">
    <link href="../lib/spectrum-colorpicker/spectrum.css" rel="stylesheet">
    <link href="../lib/select2/css/select2.min.css" rel="stylesheet">
    <link href="../lib/ion-rangeslider/css/ion.rangeSlider.css" rel="stylesheet">
    <link href="../lib/ion-rangeslider/css/ion.rangeSlider.skinFlat.css" rel="stylesheet">
    <link href="../lib/amazeui-datetimepicker/css/amazeui.datetimepicker.css" rel="stylesheet">
    <link href="../lib/jquery-simple-datetimepicker/jquery.simple-dtpicker.css" rel="stylesheet">
    <link href="../lib/pickerjs/picker.min.css" rel="stylesheet">


    <link href="../lib/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="../lib/select2/css/select2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/azia.css">
    <link rel="stylesheet" href="css/form-karyawan.css">
    

    <style>
        .select2-container { width: 100% !important; }
        .modal { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,.4); }
        /* ==================== STYLING TABEL PENUGASAN ==================== */
    .table-responsive {
        margin-top: 20px;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    }

    .table {
        margin-bottom: 0;
        background-color: #fff;
    }

    .table thead th {
        background-color: #2852d4;        /* warna header gelap elegan */
        color: #ffffff;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.85rem;
        letter-spacing: 0.5px;
        border: none;
        padding: 14px 12px;
        text-align: center;
        vertical-align: middle;
    }

    .table tbody td {
        padding: 14px 12px;
        vertical-align: middle;
        border-color: #e2e8f0;
        font-size: 0.95rem;
    }

    .table tbody tr {
        transition: all 0.2s ease;
    }

    .table tbody tr:hover {
        background-color: #f8fafc;
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.05);
    }

    .table tbody tr:nth-child(even) {
        background-color: #f9fbfc;
    }

    /* Tombol Tugaskan lebih menarik */
    .btn-sm {
        padding: 6px 14px;
        font-size: 0.85rem;
        border-radius: 6px;
        font-weight: 500;
    }

    /* Responsif pada layar kecil */
    @media (max-width: 768px) {
        .table thead {
            display: none;
        }
        .table, .table tbody, .table tr, .table td {
            display: block;
            width: 100%;
        }
        .table tr {
            margin-bottom: 15px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .table td {
            text-align: right;
            padding-left: 50%;
            position: relative;
        }
        .table td::before {
            content: attr(data-label);
            position: absolute;
            left: 12px;
            width: 45%;
            font-weight: 600;
            text-align: left;
            color: #4a5568;
        }
        .table td:last-child {
            text-align: center;
        }
    }
    </style>
</head>
<body class="az-body">

<!-- HEADER — TIDAK DIRUBAH -- kept exactly as original by you -->
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
                    <a href="form-penugasan.php" class="nav-link active">Penugasan</a>
                </div>
            </div>
<div class="az-content-body pd-lg-l-40 d-flex flex-column">
    <div class="container">

        <div class="az-content-body">

            <h2 class="az-content-title">Penugasan Event</h2>

            <?php if ($success_message): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success_message) ?></div>
            <?php endif; ?>
            <?php if ($error_message): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
            <?php endif; ?>

            <div class="table-responsive">
                <?php if ($bookings): ?>
                    <table class="table table-bordered">
                        <thead>
                        <tr>
                            <th>No</th>
                            <th>Customer</th>
                            <th>Jenis</th>
                            <th>Tanggal</th>
                            <th>Alamat</th>
                            <th>Harga</th>
                            <th>Aksi</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($bookings as $i => $b): ?>
                            <tr>
                                <td><?= $i+1 ?></td>
                                <td><?= htmlspecialchars($b['CustomerNama']) ?></td>
                                <td><?= htmlspecialchars($b['BkgJenis']) ?></td>
                                <td><?= date('d/m/Y', strtotime($b['BkgTglMulai'])) ?></td>
                                <td><?= htmlspecialchars($b['BkgAlamat']) ?></td>
                                <td>Rp <?= number_format($b['BkgTotalHarga']) ?></td>
                                <td>
                                    <button class="btn btn-primary btn-sm"
                                            onclick="openPopup(
                                                <?= $b['IDBooking'] ?>, 
                                                '<?= addslashes($b['NamaPaket']) ?>',
                                                '<?= addslashes($b['BkgAlamat']) ?>',
                                                '<?= $b['BkgTglMulai'] ?>'
                                            )">
                                        Tugaskan
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="text-center text-muted">Tidak ada booking siap dijadwalkan.</p>
                <?php endif; ?>
            </div>

        </div>
    </div>
</div>


<!-- ======================== MODAL TAMBAH EVENT ======================== -->
<div id="popupForm" class="modal" style="display:none;">
    <div class="modal-dialog modal-lg">
        <div class="modal-content p-3">

            <h5>Buat Event</h5>

            <form id="formEvent" method="POST">
                <input type="hidden" name="buat_event" value="1">
                <input type="hidden" name="id_booking" id="id_booking">

                <div class="form-group">
                    <label>Nama Event</label>
                    <input type="text" name="event_nama" id="event_nama" class="form-control" required readonly>
                </div>

                <div class="form-group">
                    <label>Lokasi</label>
                    <input type="text" name="event_lokasi" id="event_lokasi" class="form-control" required>
                </div>

                <div class="row">
                    <div class="col">
                        <label>Tanggal</label>
                        <input type="date" name="event_tanggal" id="event_tanggal" class="form-control" required>
                    </div>
                    <div class="col">
                        <label>Mulai</label>
                        <input type="time" name="event_mulai" id="event_mulai" class="form-control" required>
                    </div>
                    <div class="col">
                        <label>Durasi (jam)</label>
                        <input type="number" name="event_durasi" id="event_durasi" class="form-control" min="1" max="24" value="8" required>
                    </div>
                </div>

                <div class="form-group mt-3">
                    <label>Pilih Karyawan</label>
                    <select name="karyawan[]" id="selectKaryawan" multiple class="form-control" required>
                        <?php foreach ($karyawans as $k): ?>
                            <option value="<?= $k['IDUser'] ?>"><?= htmlspecialchars($k['UserNama']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="d-flex justify-content-end">
                    <button type="button" class="btn btn-secondary mr-2" onclick="closePopup()">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>

            </form>

        </div>
    </div>
</div>


<script src="../lib/jquery/jquery.min.js"></script>
<script src="../lib/select2/js/select2.min.js"></script>

<script>
function openPopup(id, jenis, alamat, tgl) {

    $('#formEvent')[0].reset();

    // === SET VALUE ===
    $('#id_booking').val(id);
    $('#event_nama').val(jenis);   // Nama event otomatis = BkgJenis
    $('#event_lokasi').val(alamat);
    $('#event_tanggal').val(tgl.split(" ")[0]);

    // === TAMPILKAN POPUP ===
    $("#popupForm").show();

    // === REINIT SELECT2 ===
    setTimeout(() => {
        $("#selectKaryawan").select2({
            dropdownParent: $('#popupForm')
        });
    }, 200);
}


function closePopup() {
    $("#popupForm").hide();
    if ($('#selectKaryawan').data('select2')) {
        $('#selectKaryawan').select2('destroy');
    }
}
</script>

</body>
</html>
