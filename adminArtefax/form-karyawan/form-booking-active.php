<?php
session_start();
require_once __DIR__ . "/../../config/koneksi.php";
require_once __DIR__ . "/../../class/Booking.php";

$db      = new Database();
$conn    = $db->getConnection();
$booking = new Booking($conn);

/* ============== PAGINATION ============== */
$limit  = 10;
$page   = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// NOTE: Kita tetap menggunakan getTotalBooking yang mengambil total booking status 'Diterima' 
// karena filter jenis layanan akan dilakukan setelah data diambil.
$totalBooking = $booking->getTotalBooking('Diterima');
$totalPages   = ceil($totalBooking / $limit);
$rawList      = $booking->getBookingList($limit, $offset, 'Diterima');

/* Group biar 1 booking = 1 baris DAN HANYA MENGAMBIL ITEM PAKET JASA */
$grouped = [];
$bookingIdsToKeep = [];

foreach ($rawList as $row) {
  $id = $row['IDBooking'];

  // Inisialisasi booking
  if (!isset($grouped[$id])) {
    $grouped[$id] = $row;
    $grouped[$id]['items'] = [];
  }

  if (!empty($row['BkgDetailJenis'])) {
    $nama = '-';

    // >>> FILTER HANYA UNTUK PAKET JASA <<<
    if ($row['BkgDetailJenis'] === 'Paket Jasa' && !empty($row['PaketNama'])) {
      $nama = $row['PaketNama'];

      // Tandai booking ini untuk disimpan (jika minimal ada 1 Paket Jasa)
      $bookingIdsToKeep[$id] = true;

      // Tambahkan item ke list item booking ini
      $grouped[$id]['items'][] = [
        'jenis' => $row['BkgDetailJenis'],
        'nama'  => $nama
      ];
    }
    // Abaikan jika BkgDetailJenis === 'Alat'
  }
}

// Hapus booking dari $grouped yang hanya berisi item Alat atau tidak punya item sama sekali
$filteredGrouped = array_intersect_key($grouped, $bookingIdsToKeep);

// Hitung ulang total baris yang ditampilkan (opsional, untuk display yang akurat)
$displayedTotalRows = count($filteredGrouped);


/* Feedback */
$success = $_SESSION['success_message'] ?? '';
$error   = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>

<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Booking Diterima - Artefax</title>
  <link href="../lib/fontawesome-free/css/all.min.css" rel="stylesheet">
  <link href="../lib/ionicons/css/ionicons.min.css" rel="stylesheet">
  <link href="../lib/typicons.font/typicons.css" rel="stylesheet">
  <link rel="stylesheet" href="../css/azia.css">

  <style>
    .badge-paket {
      background: #28a745;
      color: #fff;
      padding: 6px 12px;
      margin: 3px 3px 3px 0;
      font-size: 12px;
      border-radius: 50px;
      display: inline-block;
      font-weight: 500;
    }

    .badge-alat {
      /* Warna badge Alat dihapus karena Alat tidak ditampilkan, tapi CSS tetap disimpan agar konsisten */
      background: #ffc107;
      color: #212529;
      padding: 6px 12px;
      margin: 3px 3px 3px 0;
      font-size: 12px;
      border-radius: 50px;
      display: inline-block;
      font-weight: 500;
    }

    .table {
      background: #fff;
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
      margin-bottom: 0;
    }

    /* HEADER TABEL WARNA BIRU #3366ff */
    .table thead th {
      background: #3366ff !important;
      color: #ffffff !important;
      font-weight: 600;
      text-transform: uppercase;
      font-size: 13px;
      letter-spacing: 0.8px;
      border: none;
      padding: 16px 20px;
    }

    .table tbody td {
      padding: 18px 20px;
      vertical-align: middle;
      border-top: 1px solid #eef2f7;
      font-size: 14px;
      color: #2d3748;
    }

    .table tbody tr:hover {
      background: #f8faff;
      transition: all 0.2s ease;
    }

    .table tbody tr:last-child td {
      border-bottom: none;
    }

    .table-responsive {
      border-radius: 12px;
      overflow: hidden;
    }

    .az-content-title {
      font-weight: 700;
      color: #1a202c;
      margin-bottom: 25px;
    }

    .alert {
      border-radius: 10px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    .text-durasi {
      font-size: 13px;
      color: #4a5568;
      font-weight: 500;
    }
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
          <a href="form-booking-active.php" class="nav-link active">Booking Paket</a>
          <a href="form-penugasan.php" class="nav-link">Penugasan</a>
        </div>
      </div>

      <div class="az-content-body pd-lg-l-40 d-flex flex-column">
        <div class="az-content-breadcrumb">
          <span>Data</span>
          <span>Booking Paket</span>
        </div>
        <h2 class="az-content-title">Daftar Booking Paket Aktif</h2>

        <?php if ($success): ?>
          <div class="alert alert-success alert-dismissible fade show">
            <?= htmlspecialchars($success) ?>
            <button type="button" class="close" data-dismiss="alert">x</button>
          </div>
        <?php endif; ?>
        <?php if ($error): ?>
          <div class="alert alert-danger alert-dismissible fade show">
            <?= htmlspecialchars($error) ?>
            <button type="button" class="close" data-dismiss="alert">x</button>
          </div>
        <?php endif; ?>

        <?php if (count($filteredGrouped) > 0): ?>
          <small class="text-muted mb-3">Menampilkan **<?= count($filteredGrouped) ?>** dari **<?= $totalBooking ?>** total booking yang memiliki **Paket Jasa**.</small>
        <?php endif; ?>

        <div class="table-responsive">
          <?php if (!empty($filteredGrouped)): ?>
            <table class="table table-hover mg-b-0">
              <thead class="thead-light">
                <tr>
                  <th>No</th>
                  <th>Pelanggan</th>
                  <th>Paket Jasa</th>
                  <th>Mulai</th>
                  <th>Selesai</th>
                  <th>Durasi</th>
                  <th>Total</th>
                </tr>
              </thead>
              <tbody>
                <?php $no = $offset + 1;
                foreach ($filteredGrouped as $b): ?>
                  <?php
                  // Hitung durasi dari Mulai → Selesai
                  $mulai   = new DateTime($b['BkgTglMulai']);
                  $selesai = new DateTime($b['BkgTglSelesai']);
                  $interval = $mulai->diff($selesai);

                  $hari = $interval->d;
                  $jam  = $interval->h + ($interval->days * 24); // Total jam akurat

                  if ($hari > 0) {
                    $durasi = "$hari hari" . ($interval->h > 0 ? ", " . $interval->h . " jam" : "");
                  } else {
                    $durasi = "$jam jam";
                  }
                  if ($jam == 0 && $hari == 0) $durasi = "< 1 jam";
                  ?>
                  <tr>
                    <td><?= $no++ ?></td>
                    <td><?= htmlspecialchars($b['UserNama'] ?? 'Guest') ?></td>
                    <td>
                      <?php if (empty($b['items'])): ?>
                        <span class="text-muted">Tidak ada Paket Jasa</span>
                        <?php else: foreach ($b['items'] as $item): ?>
                          <span class="badge-paket"><?= htmlspecialchars($item['nama']) ?></span>
                      <?php endforeach;
                      endif; ?>
                    </td>
                    <td><?= date('d/m/Y H:i', strtotime($b['BkgTglMulai'])) ?></td>
                    <td><?= date('d/m/Y H:i', strtotime($b['BkgTglSelesai'])) ?></td>
                    <td class="text-durasi"><strong><?= $durasi ?></strong></td>
                    <td><strong>Rp <?= number_format($b['BkgTotalHarga'], 0, ',', '.') ?></strong></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>

            <?php if ($totalPages > 1): ?>
              <nav class="mt-4">
                <ul class="pagination justify-content-center">
                  <?php
                  // Link Previous
                  $prev_page = $page - 1;
                  $prev_class = ($page <= 1) ? 'disabled' : '';
                  ?>
                  <li class="page-item <?= $prev_class ?>">
                    <a class="page-link" href="?page=<?= $prev_page ?>" aria-label="Previous">
                      <span aria-hidden="true">&laquo;</span>
                    </a>
                  </li>

                  <?php for ($i = 1; $i <= $totalPages; $i++):
                    $active = ($i == $page) ? "active" : "";
                  ?>
                    <li class="page-item <?= $active ?>">
                      <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                    </li>
                  <?php endfor; ?>

                  <?php
                  // Link Next
                  $next_page = $page + 1;
                  $next_class = ($page >= $totalPages) ? 'disabled' : '';
                  ?>
                  <li class="page-item <?= $next_class ?>">
                    <a class="page-link" href="?page=<?= $next_page ?>" aria-label="Next">
                      <span aria-hidden="true">&raquo;</span>
                    </a>
                  </li>
                </ul>
              </nav>
              <div class="text-center text-muted small mt-2">
                Halaman **<?= $page ?>** dari **<?= $totalPages ?>** | Total **<?= $totalBooking ?>** booking (semua jenis)
              </div>
            <?php endif; ?>
          <?php else: ?>
            <div class="text-center py-5">
              <h5 class="text-muted">Belum ada booking Paket Jasa dengan status **Diterima**.</h5>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <script src="../lib/jquery/jquery.min.js"></script>
  <script src="../lib/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script>
    setTimeout(() => $('.alert').fadeOut('slow'), 5000);
  </script>
</body>

</html>