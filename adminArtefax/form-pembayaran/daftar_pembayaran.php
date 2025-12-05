<?php
session_start();

// --- START: VERIFIKASI DAN ADAPTASI SESI KRITIS ---
if (isset($_SESSION['user']) && is_array($_SESSION['user'])) {
    $_SESSION['IDUser'] = $_SESSION['user']['IDUser'] ?? null;
    $_SESSION['UserNama'] = $_SESSION['user']['UserNama'] ?? 'Guest User';
    $_SESSION['UserRole'] = $_SESSION['user']['UserRole'] ?? 'Unknown Role';
}

// VERIFIKASI LOGIN
if (!isset($_SESSION['IDUser']) || empty($_SESSION['IDUser'])) {
    header("Location: ../../view/login.php"); 
    exit;
}
// --- END: VERIFIKASI DAN ADAPTASI SESI KRITIS ---

require_once __DIR__ . "/../../config/koneksi.php";
require_once __DIR__ . "/../../class/pembayaran.php";
require_once __DIR__ . "/detail_pembayaran.php";

// --- DATA USER LOGIN ---
$loggedInUser = [
    'UserNama' => $_SESSION['UserNama'] ?? 'Guest User', 
    'UserRole' => $_SESSION['UserRole'] ?? 'Unknown Role', 
];
$defaultProfileImage = '../img/faces/face1.jpg';

$db = new Database();
$conn = $db->getConnection();

// Inisialisasi class
$pembayaran = new Pembayaran($conn);

/* ============== PAGINATION ============== */
$limit  = 10;
$page   = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;

$totalBooking = $pembayaran->TotalBooking();
$totalPages    = ceil($totalBooking / $limit);

$daftarPembayaran = $pembayaran->readJoin($limit, $offset);
$detailPembayaran = $pembayaran->readJoinFull($limit, $offset);

// Feedback
$success_message = $_SESSION['success_message'] ?? '';
$error_message = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Daftar Pembayaran - Artefax</title>

  <link href="../lib/fontawesome-free/css/all.min.css" rel="stylesheet">
  <link href="../lib/ionicons/css/ionicons.min.css" rel="stylesheet">
  <link href="../lib/typicons.font/typicons.css" rel="stylesheet">
  <link rel="stylesheet" href="../css/azia.css" />

  <style>
    /* --- FIXED LAYOUT --- */
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
      padding-top: 30px !important;
    }
    .az-content-left .component-item {
      padding-top: 10px;
    }
    .az-content-left .component-item label {
      margin-top: 15px;
      margin-bottom: 10px;
      display: block;
    }
    .az-content-left .component-item label:first-child {
      margin-top: 0;
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
        padding-top: 70px !important;
      }
    }

    /* --- TABLE STYLE --- */
    .custom-table {
      width: 100%;
      border-collapse: separate;
      border-spacing: 0;
      background: white;
      margin-top: 20px;
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
      font-size: 14px;
    }
    .custom-table th {
      background-color: #3366ff;
      color: white;
      font-weight: 600;
      text-transform: uppercase;
      font-size: 13px;
      padding: 16px 12px;
      text-align: center;
      border: none;
    }
    .custom-table td {
      padding: 14px 12px;
      text-align: left;
      border-bottom: 1px solid #eef2f7;
    }
    .custom-table tbody tr:hover {
      background-color: #f8faff;
      transition: all 0.2s;
    }

    .tombol-aksi {
      display: flex;
      gap: 8px;
      justify-content: center;
    }
    
    .badge {
      padding: 5px 10px;
      border-radius: 12px;
      font-size: 12px;
      font-weight: 600;
    }
    .badge-menunggu {
      background-color: #fff3cd;
      color: #856404;
    }
    .badge-berhasil {
      background-color: #d4edda;
      color: #155724;
    }
    .badge-gagal {
      background-color: #f8d7da;
      color: #721c24;
    }

    .table-container {
      overflow-x: auto;
      border-radius: 10px;
    }

    .alert {
      padding: 12px 16px;
      border-radius: 6px;
      margin-bottom: 20px;
    }
    .alert-success {
      background: #d4edda;
      color: #155724;
      border: 1px solid #c3e6cb;
    }
    .alert-danger {
      background: #f8d7da;
      color: #721c24;
      border: 1px solid #f5c6cb;
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
          <a href="" class="close">&times;</a>
        </div>
        <ul class="nav">
          <li class="nav-item">
            <a href="../index.html" class="nav-link"><i class="typcn typcn-chart-area-outline"></i> Dashboard</a>
          </li>
          <li class="nav-item">
            <a href="../form-karyawan/form-user.php" class="nav-link"><i class="typcn typcn-group"></i>User</a>
          </li>
          <li class="nav-item active">
            <a href="../form-pembayaran/daftar_pembayaran.php" class="nav-link"><i class="typcn typcn-puzzle-outline"></i>Pembayaran</a>
          </li>
          <li class="nav-item">
            <a href="../form-layanan/PaketJasa/form-paketjasa.php" class="nav-link"><i class="typcn typcn-puzzle-outline"></i>Layanan</a>
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
          <a href="" class="az-img-user"><img src="<?= $defaultProfileImage ?>" alt=""></a>
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

  <div class="az-content pd-y-20 pd-lg-y-30 pd-xl-y-40">
    <div class="container">
      <div class="az-content-left az-content-left-components d-lg-block d-none">
        <div class="component-item">
          <label>Pembayaran</label>
          <nav class="nav flex-column">
            <a href="../form-pembayaran/daftar_pembayaran.php" class="nav-link active">Daftar Pembayaran</a>
            <a href="../form-pembayaran/pembayaran/konfirmasi_pembayaran.php" class="nav-link">Konfirmasi Pembayaran</a>
          </nav>
          <label>Pelunasan DP</label>
          <nav class="nav flex-column">
            <a href="../form-pembayaran/dp/pelunasan_pembayaran.php" class="nav-link">Pelunasan Pembayaran</a>
          </nav>
          <label>Refund</label>
          <nav class="nav flex-column">
            <a href="../form-pembayaran/refund/pengajuan_refund.php" class="nav-link">Pengajuan Refund</a>
          </nav>
        </div>
      </div>

      <div class="az-content-body pd-lg-l-40 d-flex flex-column">
        <div class="az-content-breadcrumb">
          <span>Pembayaran</span>
          <span>Daftar Pembayaran</span>
        </div>
        <h2 class="az-content-title">Daftar Pembayaran</h2>

        <div class="d-flex justify-content-between align-items-center mg-b-20">
          <p class="mg-b-0">Daftar keseluruhan transaksi pemesanan.</p>
        </div>

        <?php if ($success_message): ?>
          <div class="alert alert-success"><?= htmlspecialchars($success_message) ?></div>
        <?php endif; ?>
        <?php if ($error_message): ?>
          <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
        <?php endif; ?>

        <div class="table-container">
          <?php if ($daftarPembayaran && count($daftarPembayaran) > 0): ?>
            <table class="custom-table">
              <thead>
                <tr>
                  <th width="5%">No</th>
                  <th>Nama Pelanggan</th>
                  <th>Jenis</th>
                  <th>Pesanan</th> 
                  <th>Jumlah Pembayaran</th>
                  <th>Metode</th>
                  <th>Status</th>
                  <th>Waktu</th>
                  <th width="15%">Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php $no = $offset + 1; foreach ($daftarPembayaran as $index => $p): 
                  $pf = $detailPembayaran[$index] ?? $p;
                ?>
                  <tr>
                    <td><?= $no++ ?></td>
                    <td><?= htmlspecialchars($p['UserNama'] ?? '') ?></td>
                    <td>
                      <?php 
                      $jenis = $p['JenisBooking'] ?? '-';
                      echo $jenis == 'Paket Jasa,Alat' ? 'Paket & Alat' : htmlspecialchars($jenis);
                      ?>
                    </td>
                    <td>
                      <?php 
                      $pesanan = $p['DaftarPesanan'] ?? '-';
                      echo $pesanan !== '' ? htmlspecialchars($pesanan) : '-';
                      ?>
                    </td>
                    <td>Rp <?= number_format($p['PbrJumlah'], 0, ',', '.') ?></td>
                    <td><?= htmlspecialchars($p['PbrMetode']) ?></td>
                    <td>
                      <span class="badge badge-<?= strtolower($p['PbrStatus']) ?>">
                        <?= htmlspecialchars($p['PbrStatus']) ?>
                      </span>
                    </td>
                    <td><?= date('d/m/Y H:i', strtotime($p['CreatedAt'])) ?></td>
                    <td>
                      <div class="tombol-aksi">
                        <button class="btn btn-sm btn-info" onclick='openDetailPopup(<?= json_encode($pf, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                          <i class="fas fa-eye"></i> Detail
                        </button>
                        <form action="hapus_pembayaran.php" method="POST" style="display:inline;" onsubmit="return confirm('Yakin hapus pembayaran ini?')">
                          <input type="hidden" name="id" value="<?= $p['IDPembayaran'] ?>">
                          <button type="submit" class="btn btn-sm btn-danger">
                            <i class="fas fa-trash"></i> Hapus
                          </button>
                        </form>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>

            <?php if ($totalPages > 1): ?>
              <nav class="mt-4">
                <ul class="pagination justify-content-center">
                  <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= $page-1 ?>">« Sebelumnya</a>
                  </li>

                  <?php
                  $start = max(1, $page - 2);
                  $end   = min($totalPages, $page + 2);

                  if ($start > 1) {
                    echo '<li class="page-item"><a class="page-link" href="?page=1">1</a></li>';
                    if ($start > 2) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                  }

                  for ($i = $start; $i <= $end; $i++) {
                    $active = ($i == $page) ? 'active' : '';
                    echo "<li class='page-item $active'><a class='page-link' href='?page=$i'>$i</a></li>";
                  }

                  if ($end < $totalPages) {
                    if ($end < $totalPages - 1) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                    echo "<li class='page-item'><a class='page-link' href='?page=$totalPages'>$totalPages</a></li>";
                  }
                  ?>

                  <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= $page+1 ?>">Berikutnya »</a>
                  </li>
                </ul>
              </nav>
              <div class="text-center text-muted small">
                Halaman <?= $page ?> dari <?= $totalPages ?> | Total <?= $totalBooking ?> Pembayaran
              </div>
            <?php endif; ?>
          <?php else: ?>
            <div class="text-center py-5 bg-light rounded">
              <i class="fas fa-money-check-alt fa-3x text-muted mb-3"></i>
              <p class="text-muted mb-3">Belum ada data pembayaran.</p>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <script src="../lib/jquery/jquery.min.js"></script>
  <script src="../lib/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="../js/azia.js"></script>
  
  <script>
    $(document).ready(function() {
      // Menu toggle handlers
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

      // Auto fadeout alert
      setTimeout(function() {
        $('.alert').fadeOut('slow');
      }, 5000);
    });

    function openDetailPopup(data) {
      // Implementasi detail popup
      console.log('Detail:', data);
      alert('Detail pembayaran:\n' + JSON.stringify(data, null, 2));
    }
  </script>
</body>
</html>