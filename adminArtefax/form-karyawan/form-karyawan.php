<?php
session_start();

// --- START: VERIFIKASI DAN ADAPTASI SESI KRITIS ---
// Adaptasi dari kunci sesi 'user' ke kunci top-level yang diharapkan template
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
require_once __DIR__ . "/../../class/users.php";

// Initialize database connection
$db = new Database();
$conn = $db->getConnection();

if (!$conn) {
  die("<p style='color:red;'>Koneksi database gagal.</p>");
}

$user = new User($conn);

// --- START: DATA USER LOGIN (Ambil dari $_SESSION yang sudah diadaptasi) ---
$loggedInUser = [
    'UserNama' => $_SESSION['UserNama'] ?? 'Guest User', 
    'UserRole' => $_SESSION['UserRole'] ?? 'Unknown Role', 
];
$defaultProfileImage = '../img/faces/artefax.jpg'; 
// --- END: DATA USER LOGIN ---

/* ============== PAGINATION (SUDAH AMAN & TIDAK ERROR) ============== */
$limit  = 10;
$page   = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;

$totalKaryawan = $user->getTotalKaryawan();
$totalPages    = ceil($totalKaryawan / $limit);
$karyawanList  = $user->getKaryawan($limit, $offset);
/* ================================================================== */

// Handle feedback messages
$success_message = $_SESSION['success_message'] ?? '';
$error_message   = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <meta name="description" content="Dashboard Manajemen Karyawan - Artefax">
  <title>Form Karyawan - Artefax</title>

  <!-- Vendor CSS -->
  <link href="../lib/fontawesome-free/css/all.min.css" rel="stylesheet">
  <link href="../lib/ionicons/css/ionicons.min.css" rel="stylesheet">
  <link href="../lib/typicons.font/typicons.css" rel="stylesheet">
  <link href="../lib/select2/css/select2.min.css" rel="stylesheet">

  <!-- Azia Core CSS -->
  <link rel="stylesheet" href="../css/azia.css">

  <!-- Custom CSS -->
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
      padding-top: 30px !important; /* Tambah space dari navbar */
    }
    .az-content-left .component-item {
      padding-top: 10px;
    }
    .az-content-left .component-item label {
      margin-top: 15px;
      margin-bottom: 10px;
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

    /* --- CUSTOM TABLE --- */
    .custom-table {
      width: 100%;
      border-collapse: separate;
      border-spacing: 0;
      background: white;
      margin-top: 20px;
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    }
    .custom-table th {
      background-color: #3366ff;
      color: white;
      font-weight: 600;
      padding: 14px 16px;
      text-align: center;
      border-bottom: 2px solid #2852d4;
    }
    .custom-table td {
      padding: 12px 16px;
      text-align: center;
      border-bottom: 1px solid #eee;
      color: #333;
    }
    .custom-table tr:nth-child(even) {
      background-color: #f8f9fc;
    }
    .custom-table tr:hover {
      background-color: #e3f2fd !important;
      transition: background-color 0.2s ease;
    }

    /* --- MODAL --- */
    .modal {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0, 0, 0, 0.5);
      z-index: 1050;
      align-items: center;
      justify-content: center;
      padding: 15px;
    }
    .modal-dialog {
      max-width: 500px;
      width: 100%;
      animation: modalFadeIn 0.3s ease-out;
    }
    .modal-content {
      background: white;
      border-radius: 12px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
    }
    @keyframes modalFadeIn {
      from { opacity: 0; transform: translateY(-20px); }
      to { opacity: 1; transform: translateY(0); }
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
                        <a href="../template/index.html" class="nav-link"><i class="typcn typcn-chart-area-outline"></i> Dashboard</a>
                    </li>
                    <li class="nav-item active">
                        <a href="../form-karyawan/form-karyawan.php" class="nav-link"><i class="typcn typcn-group"></i>User</a>
                    </li>
                    <li class="nav-item">
                        <a href="../form-pembayaran/daftar_pembayaran.php" class="nav-link">
                            <i class="fas fa-money-bill-alt" style="margin-right: 8px;"></i> Pembayaran
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="../form-layanan/PaketJasa/form-paketjasa.php" class="nav-link"><i class="typcn typcn-puzzle-outline"></i>Layanan</a>
                    </li>
                    <li class="nav-item">
                        <a href="../form-laporan/LaporanKeuangan.php" class="nav-link">
                            <i class="fas fa-file-alt" style="margin-right: 8px;"></i> Laporan
                        </a>
                    </li>
                </ul>
            </div>
            <div class="az-header-right">
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

  <div class="az-content pd-y-20 pd-lg-y-30 pd-xl-y-40">
    <div class="container">
      <div class="az-content-left az-content-left-components d-lg-block d-none">
        <div class="component-item">
          <label>Menu User</label>
          <nav class="nav flex-column">
            <a href="form-user.php" class="nav-link">Daftar User</a>
          </nav>
          <label>Menu Karyawan</label>
          <a href="form-karyawan.php" class="nav-link active">Daftar Karyawan</a>
          <a href="form-booking-active.php" class="nav-link">Booking Paket</a>
          <a href="form-penugasan.php" class="nav-link">Penugasan</a>
        </div>
      </div>

      <div class="az-content-body pd-lg-l-40 d-flex flex-column">
        <div class="az-content-breadcrumb">
          <span>Data</span>
          <span>Karyawan</span>
        </div>
        <h2 class="az-content-title"><i class="fas fa-users"></i> Daftar Karyawan</h2>
        <div class="d-flex justify-content-end align-items-center mg-b-20">
          <button class="btn btn-primary btn-with-icon" onclick="openTambahPopup()">
            <i class="fas fa-plus"></i> Tambah Karyawan
          </button>
        </div>

        <?php if ($success_message): ?>
          <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($success_message); ?>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
          </div>
        <?php endif; ?>
        <?php if ($error_message): ?>
          <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($error_message); ?>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
          </div>
        <?php endif; ?>

        <div class="table-responsive">
          <?php if ($karyawanList && count($karyawanList) > 0): ?>
            <table class="custom-table">
              <thead>
                <tr>
                  <th>No</th>
                  <th>Nama Karyawan</th>
                  <th>Email</th>
                  <th>No HP</th>
                  <th>Alamat</th>
                  <th>Role</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php $no = $offset + 1;
                foreach ($karyawanList as $karyawan): ?>
                  <tr>
                    <td><?php echo $no++; ?></td>
                    <td><?php echo htmlspecialchars($karyawan['UserNama']); ?></td>
                    <td><?php echo htmlspecialchars($karyawan['UserEmail']); ?></td>
                    <td><?php echo htmlspecialchars($karyawan['UserNoHP']); ?></td>
                    <td><?php echo htmlspecialchars($karyawan['UserAlamat']); ?></td>
                    <td><span class="badge badge-info"><?php echo htmlspecialchars($karyawan['UserRole']); ?></span></td>
                    <td>
                      <form action="hapus_karyawan.php" method="POST" style="display:inline;" onsubmit="return confirm('Yakin ingin menghapus karyawan ini?');">
                        <input type="hidden" name="id" value="<?php echo htmlspecialchars($karyawan['IDUser']); ?>">
                        <button type="submit" class="btn btn-sm btn-danger">
                          <i class="fas fa-trash"></i>
                        </button>
                      </form>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>

            <?php if ($totalPages > 1): ?>
              <nav class="mt-4">
                <ul class="pagination justify-content-center">
                  <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= $page - 1 ?>">&laquo; Sebelumnya</a>
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
                    <a class="page-link" href="?page=<?= $page + 1 ?>">Berikutnya &raquo;</a>
                  </li>
                </ul>
              </nav>
              <div class="text-center text-muted small">
                Halaman <?= $page ?> dari <?= $totalPages ?> | Total <?= $totalKaryawan ?> karyawan
              </div>
            <?php endif; ?>

          <?php else: ?>
            <div class="text-center py-5">
              <p class="text-muted">Belum ada karyawan terdaftar.</p>
              <button class="btn btn-primary" onclick="openTambahPopup()">Tambah Karyawan Pertama</button>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <div id="popupForm" class="modal">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 id="popupTitle" class="modal-title">Tambah Karyawan</h5>
          <button type="button" class="close" onclick="closePopup()">&times;</button>
        </div>
        <div class="modal-body">
          <form id="formKaryawan" action="tambah_karyawan.php" method="POST">
            <input type="hidden" id="idUser" name="IDUser">

            <div class="form-group">
              <label for="namaUser">Nama Karyawan <span class="text-danger">*</span></label>
              <input type="text" id="namaUser" name="NamaUser" class="form-control" required
                pattern="[A-Za-z\s]{2,50}" title="Hanya huruf dan spasi, 2-50 karakter">
            </div>

            <div class="form-group">
              <label for="emailUser">Email <span class="text-danger">*</span></label>
              <input type="email" id="emailUser" name="Email" class="form-control" required>
            </div>

            <div class="form-group">
              <label for="password">Password <span class="text-danger">*</span></label>
              <input type="password" id="password" name="Password" class="form-control" required minlength="6">
              <small class="text-muted">Minimal 6 karakter</small>
            </div>

            <div class="form-group">
              <label for="noHP">No HP <span class="text-danger">*</span></label>
              <input type="text" id="noHP" name="NoHP" class="form-control" required
                pattern="[0-9]{10,15}" title="10-15 digit angka">
            </div>

            <div class="form-group">
              <label for="alamat">Alamat <span class="text-danger">*</span></label>
              <textarea id="alamat" name="Alamat" class="form-control" rows="3" required minlength="5" maxlength="200"></textarea>
            </div>

            <input type="hidden" name="Role" value="Karyawan">
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" onclick="closePopup()">Batal</button>
          <button type="submit" form="formKaryawan" class="btn btn-primary" id="submitBtn">
            <i class="fas fa-save"></i> Simpan
          </button>
        </div>
      </div>
    </div>
  </div>

  <script src="../lib/jquery/jquery.min.js"></script>
  <script src="../lib/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="../js/azia.js"></script>

  <script>
    $(document).ready(function() {
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

      setTimeout(function() {
        $('.alert').fadeOut('slow');
      }, 5000);
    });

    function openTambahPopup() {
      document.getElementById('popupTitle').textContent = 'Tambah Karyawan';
      document.getElementById('formKaryawan').reset();
      document.getElementById('idUser').value = '';
      document.getElementById('popupForm').style.display = 'flex';
      document.getElementById('namaUser').focus();
    }

    function closePopup() {
      document.getElementById('popupForm').style.display = 'none';
      document.getElementById('formKaryawan').reset();
    }

    window.onclick = function(event) {
      const modal = document.getElementById('popupForm');
      if (event.target === modal) {
        closePopup();
      }
    };
  </script>
</body>
</html>