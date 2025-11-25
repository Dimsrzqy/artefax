<?php
require_once __DIR__ . "/../../config/koneksi.php";
require_once __DIR__ . "/../../class/EventAssignment.php";

$db = new Database();
$conn = $db->getConnection();
$event = new EventAssignment($conn);

// PAGINASI
$limit = 10;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// Ambil total event
$totalQuery = $conn->query("SELECT COUNT(*) AS total FROM event");
$totalRows = $totalQuery->fetch_assoc()['total'];
$totalPages = ceil($totalRows / $limit);

// Ambil data event + karyawan
$sql = "
    SELECT e.*, 
        GROUP_CONCAT(u.UserNama SEPARATOR ', ') AS Karyawan
    FROM event e
    LEFT JOIN event_karyawan ek ON e.IDEvent = ek.IDEvent
    LEFT JOIN users u ON ek.IDKaryawan = u.IDUser
    GROUP BY e.IDEvent
    ORDER BY e.EventTanggal DESC, e.EventMulai DESC
    LIMIT $limit OFFSET $offset
";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Laporan Penugasan Event | Artefax</title>

    <link href="../lib/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="../lib/ionicons/css/ionicons.min.css" rel="stylesheet">
    <link href="../lib/typicons.font/typicons.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/azia.css">
    <link rel="stylesheet" href="css/absensi-karyawan.css">
    <link rel="stylesheet" href="css/absensi-karyawan.css"> 
</head>

<body>

<!-- HEADER TEMPLATE MU TETAP, TIDAK DIUBAH -->
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
           <li class="nav-item">
              <a href="../form-karyawan/form-karyawan.php" class="nav-link"><i class="typcn typcn-group"></i>User</a>
            </li>
            <li class="nav-item">
              <a href="../form-pembayaran/daftar_pembayaran.php" class="nav-link"><i class="typcn typcn-puzzle-outline"></i>Pembayaran</a>
            </li>
            <li class="nav-item">
              <a href="../form-layanan/form-layanan.php" class="nav-link"><i class="typcn typcn-puzzle-outline"></i>Layanan</a>
            </li>
           <li class="nav-item active">
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
<!-- CONTENT -->
<div class="az-content pd-y-20 pd-lg-y-30 pd-xl-y-40">
    <div class="container">
        <div class="az-content-left az-content-left-components">
            <div class="component-item">
                <label>Laporan</label>
                <nav class="nav flex-column">
                    <a href="LaporanAbsensiKaryawan.php" class="nav-link">Absensi</a>
                    <a href="LaporanPenugasan.php" class="nav-link active">Penugasan</a>
                </nav>
            </div>
        </div>

        <div class="az-content-body pd-lg-l-40 d-flex flex-column">
            <div class="az-content-breadcrumb">
                <span>Laporan</span>
                <span>Absensi</span>
            </div>
            <h2 class="az-content-title">Daftar Absensi Karyawan</h2>

        <!-- TOMBOL EXPORT (Elegan, kanan, simpel) -->
        <div class="d-flex justify-content-end" style="margin-bottom:18px; margin-top:-8px;">
            <form action="export_penugasan_excel.php" method="post" target="exportFrame">
                <button type="submit"
                    style="
                        background:#0f8f4f;
                        color:white;
                        border:none;
                        padding:10px 22px;
                        border-radius:10px;
                        font-size:14.2px;
                        font-weight:600;
                        display:flex;
                        align-items:center;
                        gap:8px;
                        box-shadow:0 4px 14px rgba(0,0,0,0.15);
                        transition:.25s ease;
                    ">
                    <i class="fas fa-file-excel" style="font-size:16px;"></i>
                    Export Excel
                </button>
            </form>
        </div>

        <!-- TABEL -->
        <table class="custom-table" style="margin-top: 2px;">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama Event</th>
                    <th>Lokasi</th>
                    <th>Tanggal</th>
                    <th>Waktu</th>
                    <th>Karyawan</th>
                    <th>Status</th>
                </tr>
            </thead>

            <tbody>
                <?php
                if ($result->num_rows === 0) {
                    echo "<tr><td colspan='7' class='text-center py-5'>Belum ada data event</td></tr>";
                } else {
                    $no = $offset + 1;
                    while ($row = $result->fetch_assoc()) {
                        $tanggal = (new DateTime($row['EventTanggal']))->format('d/m/Y');
                        $waktu   = $row['EventMulai'] . " - " . $row['EventSelesai'];

                        echo "
                        <tr>
                            <td>$no</td>
                            <td><strong>{$row['EventNama']}</strong></td>
                            <td>{$row['EventLokasi']}</td>
                            <td>$tanggal</td>
                            <td>$waktu</td>
                            <td>{$row['Karyawan']}</td>
                            <td class='status-" . strtolower($row['EventStatus']) . "'>
                                {$row['EventStatus']}
                            </td>
                        </tr>";
                        $no++;
                    }
                }
                ?>
            </tbody>
        </table>

        <!-- PAGINASI -->
        <?php if ($totalPages > 1) { ?>
            <nav class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php for ($i = 1; $i <= $totalPages; $i++) { ?>
                        <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                        </li>
                    <?php } ?>
                </ul>
            </nav>
        <?php } ?>

        <!-- IFRAME EXPORT -->
        <iframe name="exportFrame" style="display:none;"></iframe>

    </div>
</div>

</body>
</html>
