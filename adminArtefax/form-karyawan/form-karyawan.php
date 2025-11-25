<?php
session_start();
require_once __DIR__ . "/../../config/koneksi.php";
require_once __DIR__ . "/../../class/Users.php";

// Initialize database connection
$db = new Database();
$conn = $db->getConnection();

if (!$conn) {
    die("<p style='color:red;'>Koneksi database gagal.</p>");
}

$user = new User($conn);

/* ============== PAGINATION (SUDAH AMAN & TIDAK ERROR) ============== */
$limit  = 10;
$page   = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// Pastikan method ini ADA di class User
$totalKaryawan = $user->getTotalKaryawan();           
$totalPages    = ceil($totalKaryawan / $limit);

// Method getKaryawan dengan parameter $limit & $offset (WAJIB ADA!)
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
    <!-- Global site tag (gtag.js) - Google Analytics -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=UA-90680653-2"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag() { dataLayer.push(arguments); }
        gtag('js', new Date());
        gtag('config', 'UA-90680653-2');
    </script>

    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- Meta -->
    <meta name="description" content="Dashboard Manajemen Karyawan - Artefax">
    <meta name="author" content="BootstrapDash">

    <title>Form Karyawan - Artefax</title>

    <!-- Vendor CSS -->
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

    <!-- Azia Core CSS -->
    <link rel="stylesheet" href="../css/azia.css">

    <!-- Custom CSS (External) -->
    <link rel="stylesheet" href="css/form-karyawan.css">

    <!-- Perbaikan jarak agar lebih rapat & rapi -->
    <style>
        .az-content-title { margin-bottom: 10px !important; }
        .d-flex.justify-content-end { margin-bottom: 15px !important; }
        .table-responsive { margin-top: 0 !important; }
        .pagination { margin: 20px 0 10px !important; }
        .text-center.text-muted.small { margin-bottom: 0; }
    </style>
</head>
<body class="az-body">
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
                  <h2 class="az-content-title">Daftar Karyawan</h2>
                <div class="d-flex justify-content-end align-items-center mg-b-20">
                    <button class="btn btn-primary btn-with-icon" onclick="openTambahPopup()">
                        <i class="fas fa-plus"></i> Tambah Karyawan
                    </button>
                </div>

                <!-- Feedback Messages -->
                <?php if ($success_message): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($success_message); ?>
                        <button type="button" class="close" data-dismiss="alert">×</button>
                    </div>
                <?php endif; ?>
                <?php if ($error_message): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($error_message); ?>
                        <button type="button" class="close" data-dismiss="alert">×</button>
                    </div>
                <?php endif; ?>

                <!-- Tabel Karyawan -->
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
                                <?php $no = $offset + 1; foreach ($karyawanList as $karyawan): ?>
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

                        <!-- PAGINATION HANYA MUNCUL KALAU DATA > 10 -->
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

    <!-- Modal Tambah/Edit Karyawan -->
    <div id="popupForm" class="modal">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 id="popupTitle" class="modal-title">Tambah Karyawan</h5>
                    <button type="button" class="close" onclick="closePopup()">×</button>
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

    <!-- Scripts -->
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

            window.openTambahPopup = openTambahPopup;
            window.closePopup = closePopup;
        });
    </script>
</body>
</html>