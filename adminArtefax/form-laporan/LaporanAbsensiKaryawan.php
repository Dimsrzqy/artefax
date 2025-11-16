<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Google Analytics -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=UA-90680653-2"></script>
    <script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());
    gtag('config', 'UA-90680653-2');
    </script>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="Responsive Bootstrap 4 Dashboard Template">
    <meta name="author" content="BootstrapDash">

    <title>Absensi Karyawan</title>

    <!-- vendor css -->
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

    <!-- azia CSS -->
    <link rel="stylesheet" href="../css/azia.css">

    <!-- CSS eksternal -->
    <link rel="stylesheet" href="css/absensi-karyawan.css">
</head>
<body>

<!-- HEADER -->
<div class="az-header">
    <div class="container">
        <div class="az-header-left">
            <a href="../index.html" class="az-logo"><span></span> Artefax</a>
            <a href="" id="azMenuShow" class="az-header-menu-icon d-lg-none"><span></span></a>
        </div>
        <div class="az-header-menu">
            <ul class="nav">
                <li class="nav-item">
                    <a href="../index.html" class="nav-link"><i class="typcn typcn-chart-area-outline"></i> Dashboard</a>
                </li>
                <li class="nav-item">
                    <a href="../form-karyawan/form-user.php" class="nav-link"><i class="typcn typcn-group"></i>User</a>
                </li>
                <li class="nav-item">
                    <a href="../form-layanan/form-layanan.php" class="nav-link"><i class="typcn typcn-puzzle-outline"></i>Layanan</a>
                </li>
                <li class="nav-item active">
                    <a href="LaporanAbsensiKaryawan.php" class="nav-link"><i class="typcn typcn-group-outline"></i>Laporan</a>
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
                <!-- container -->
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
</div>

<!-- CONTENT -->
<div class="az-content pd-y-20 pd-lg-y-30 pd-xl-y-40">
  <div class="container">
    <div class="az-content-left az-content-left-components">
      <div class="component-item">
        <label>Laporan</label>
        <nav class="nav flex-column">
          <a href="LaporanAbsensiKaryawan.php" class="nav-link active">Absensi</a>
        </nav>
      </div>
    </div>

    <div class="az-content-body pd-lg-l-40 d-flex flex-column">
      <div class="az-content-breadcrumb">
        <span>Laporan</span>
        <span>Absensi</span>
      </div>
      <h2 class="az-content-title">Daftar Absensi Karyawan</h2>

      <div class="col-lg-12 mg-t-20" style="max-width: 100%;">
        <?php
        require_once __DIR__ . "/../../config/koneksi.php";
        require_once __DIR__ . "/../../class/Absensi.php";

        $db = new Database();
        $conn = $db->getConnection();

        if (!$conn) {
            echo "<div class='alert alert-danger text-center'>Koneksi database gagal.</div>";
        } else {
            $absensi = new Absensi($conn);
            $result = $absensi->tampilSemua();

            if ($result === false) {
                echo "<div class='alert alert-warning text-center'>Gagal mengambil data.</div>";
            } elseif ($result->num_rows === 0) {
                echo "<div class='no-data'>
                        <i class='typcn typcn-document-text'></i>
                        <p><strong>Belum ada data absensi.</strong></p>
                        <small>Data akan muncul setelah karyawan presensi.</small>
                      </div>";
            } else {
                echo "<table class='custom-table'>
                        <thead>
                            <tr>
                                <th>Nama Karyawan</th>
                                <th>Waktu</th>
                                <th>Lokasi</th>
                                <th>Foto</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>";

                while ($row = $result->fetch_assoc()) {
                    $status = strtolower($row['PsnStatus'] ?? 'alpha');
                    $statusClass = 'status-' . $status;

                    $nama = htmlspecialchars($row['UserNama'] ?? 'Tidak Diketahui');
                    $waktu = !empty($row['PsnWaktu']) ? date('d/m/Y H:i', strtotime($row['PsnWaktu'])) : '-';
                    $lokasi = htmlspecialchars($row['PsnLokasi'] ?? '-');
                    $statusText = htmlspecialchars($row['PsnStatus'] ?? 'Alpha');

                    // Ambil foto sesuai database
                    if (!empty($row['PsnFoto'])) {
                        // Jika data panjang, berarti BLOB (base64)
                        if (strlen($row['PsnFoto']) > 200) {
                            $fotoData = base64_encode($row['PsnFoto']);
                            $fotoPath = "data:image/jpeg;base64,$fotoData";
                        } else {
                            // Jika hanya nama file, ambil dari folder uploads
                            $fotoFile = htmlspecialchars($row['PsnFoto']);
                            $fotoPath = "../../uploads/$fotoFile";
                        }
                    } else {
                        $fotoPath = "../../img/no-photo.png";
                    }

                    echo "<tr>
                            <td>$nama</td>
                            <td>$waktu</td>
                            <td>$lokasi</td>
                            <td>
                                <button class='btn btn-sm lihat-foto' 
                                        data-foto='$fotoPath' 
                                        style='background-color:#5d5dfb; color:white; border:none; padding:6px 12px; border-radius:6px;'>
                                    <i class='fas fa-camera'></i> Lihat
                                </button>
                            </td>
                            <td class='$statusClass'>$statusText</td>
                          </tr>";
                }

                echo "</tbody></table>";
            }
        }
        ?>
      </div>
    </div>
  </div>
</div>


<!-- Modal Foto -->
<div class="modal fade" id="fotoModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-body text-center" style="padding:20px;">
        <img id="modalFoto" src="" alt="Foto Absensi" 
             style="max-width:80%; max-height:80vh; border-radius:10px; box-shadow:0 0 10px rgba(0,0,0,0.3);">
      </div>
    </div>
  </div>
</div>


<!-- JS -->
<script src="../lib/jquery/jquery.min.js"></script>
<script src="../lib/popper.js/popper.min.js"></script>
<script src="../lib/bootstrap/js/bootstrap.min.js"></script>

<script>
$(document).ready(function(){
  // Saat tombol "Lihat" diklik
  $(document).on('click', '.lihat-foto', function(e){
    e.preventDefault();

    const fotoPath = $(this).data('foto');
    if (fotoPath && fotoPath.trim() !== "") {
      $('#modalFoto').attr('src', fotoPath);
      $('#fotoModal').modal('show');
    } else {
      alert('Foto tidak tersedia.');
    }
  });

  // Kosongkan src setelah modal ditutup
  $('#fotoModal').on('hidden.bs.modal', function(){
    $('#modalFoto').attr('src', '');
  });
});
</script>

</body>
</html>
