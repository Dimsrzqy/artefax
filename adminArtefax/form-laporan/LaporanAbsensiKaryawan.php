<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Laporan Absensi Karyawan | Artefax</title>

    <!-- Vendor CSS -->
    <link href="../lib/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="../lib/ionicons/css/ionicons.min.css" rel="stylesheet">
    <link href="../lib/typicons.font/typicons.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/azia.css">
    <link rel="stylesheet" href="css/absensi-karyawan.css">
</head>
<body>
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
                    <a href="LaporanAbsensiKaryawan.php" class="nav-link active">Absensi</a>
                    <a href="LaporanPenugasan.php" class="nav-link">Penugasan</a>
                </nav>
            </div>
        </div>

        <div class="az-content-body pd-lg-l-40 d-flex flex-column">
            <div class="az-content-breadcrumb">
                <span>Laporan</span>
                <span>Absensi</span>
            </div>
            <h2 class="az-content-title">Daftar Absensi Karyawan</h2>

            <div class="col-lg-12 mg-t-20" style="max-width: 100%; margin-top: 5px !important;">
                <?php
                require_once __DIR__ . "/../../config/koneksi.php";
                require_once __DIR__ . "/../../class/Absensi.php";

                $db = new Database();
                $conn = $db->getConnection();

                // PAGINASI
                $limit = 10;
                $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
                $offset = ($page - 1) * $limit;

                if (!$conn) {
                    echo "<div class='alert alert-danger text-center'>Koneksi database gagal.</div>";
                } else {
                    $absensi = new Absensi($conn);

                    // Total data
                    $totalResult = $absensi->tampilSemua();
                    $totalRows = $totalResult ? $totalResult->num_rows : 0;
                    $totalPages = ceil($totalRows / $limit);

                    // Data untuk halaman ini
                    $sql = "SELECT p.IDPresensi, p.PsnWaktu, p.PsnLokasi, p.PsnFoto, p.PsnStatus, u.UserNama
                            FROM presensi p
                            LEFT JOIN users u ON p.IDUser = u.IDUser
                            ORDER BY p.PsnWaktu DESC
                            LIMIT ? OFFSET ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("ii", $limit, $offset);
                    $stmt->execute();
                    $result = $stmt->get_result();

                    if ($result->num_rows === 0) {
                        echo "<div class='text-center py-5'>
                                <i class='typcn typcn-document-text' style='font-size:5rem;color:#ddd;'></i>
                                <h5 class='mt-3'>Belum ada data absensi</h5>
                              </div>";
                    } else {
                        // TOMBOL EXPORT KE EXCEL (.XLSX) DENGAN IKON EXCEL PREMIUM
                      echo "<div class='export-container d-flex justify-content-end align-items-center'
                                  style='margin-bottom:18px !important; margin-top:-8px;'>

                              <form action='export_absensi_excel.php' method='post' target='exportFrame'>

                                  <button type='submit'
                                      style='background:#0f8f4f;
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
                                            transition:0.25s ease;'>
                                      
                                      <i class='fas fa-file-excel' style='font-size:17px;'></i>
                                      Export Excel
                                  </button>

                              </form>
                          </div>";


                        echo "<table class='custom-table' style='margin-top: 2px;'>
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Nama Karyawan</th>
                                        <th>Tanggal</th>
                                        <th>Jam</th>
                                        <th>Lokasi</th>
                                        <th>Foto</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>";

                        $no = $offset + 1;
                        while ($row = $result->fetch_assoc()) {
                            $nama = htmlspecialchars($row['UserNama'] ?? 'Tidak Diketahui');
                            $waktuRaw = $row['PsnWaktu'];
                            $tanggal = $waktuRaw ? (new DateTime($waktuRaw))->format('d/m/Y') : '-';
                            $jam = $waktuRaw ? (new DateTime($waktuRaw))->format('H:i') : '-';
                            $lokasi = htmlspecialchars($row['PsnLokasi'] ?? '-');
                            $status = strtolower($row['PsnStatus'] ?? 'alpha');
                            $statusText = ucfirst($status);
                            $statusClass = 'status-' . $status;

                            // Foto
                            if (!empty($row['PsnFoto'])) {
                                $fotoPath = strlen($row['PsnFoto']) > 200 
                                    ? "data:image/jpeg;base64," . base64_encode($row['PsnFoto'])
                                    : "../../uploads/" . htmlspecialchars($row['PsnFoto']);
                            } else {
                                $fotoPath = "../../img/no-photo.png";
                            }

                            echo "<tr>
                                    <td>$no</td>
                                    <td><strong>$nama</strong></td>
                                    <td>$tanggal</td>
                                    <td><strong>$jam</strong></td>
                                    <td>$lokasi</td>
                                    <td>
                                        <button class='btn btn-sm lihat-foto' data-foto='$fotoPath'
                                                style='background:#5d5dfb;color:white;border:none;padding:7px 14px;border-radius:8px;'>
                                            Lihat
                                        </button>
                                    </td>
                                    <td class='$statusClass'>$statusText</td>
                                  </tr>";
                            $no++;
                        }
                        echo "</tbody></table>";

                        // PAGINASI
                        if ($totalPages > 1) {
                            echo "<nav class='mt-4'><ul class='pagination justify-content-center'>";
                            for ($i = 1; $i <= $totalPages; $i++) {
                                $active = ($i == $page) ? "active" : "";
                                echo "<li class='page-item $active'><a class='page-link' href='?page=$i'>$i</a></li>";
                            }
                            echo "</ul></nav>";
                        }
                    }
                    $stmt->close();
                }
                ?>
            </div>
        </div>
    </div>
</div>

<!-- Hidden iframe untuk export (anti error header) -->
<iframe name="exportFrame" style="display:none;"></iframe>

<!-- Modal Foto -->
<div class="modal fade" id="fotoModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-body text-center p-4">
                <img id="modalFoto" src="" alt="Foto Absensi" style="max-width:100%; max-height:80vh; border-radius:12px; box-shadow:0 15px 35px rgba(0,0,0,0.3);">
            </div>
        </div>
    </div>
</div>

<!-- JS -->
<script src="../lib/jquery/jquery.min.js"></script>
<script src="../lib/popper.js/popper.min.js"></script>
<script src="../lib/bootstrap/js/bootstrap.min.js"></script>

<script>
$(document).on('click', '.lihat-foto', function(){
    $('#modalFoto').attr('src', $(this).data('foto'));
    $('#fotoModal').modal('show');
});
$('#fotoModal').on('hidden.bs.modal', function(){
    $('#modalFoto').attr('src', '');
});
</script>

</body>
</html>