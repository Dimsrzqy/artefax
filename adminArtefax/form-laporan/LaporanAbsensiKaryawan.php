<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Laporan Absensi Karyawan | Artefax</title>

    <link href="../lib/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="../lib/ionicons/css/ionicons.min.css" rel="stylesheet">
    <link href="../lib/typicons.font/typicons.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/azia.css">

    <style>
        /* ============================================= */
        /* absensi-karyawan.css – UI Modern & Clean */
        /* ============================================= */

        /* Header Export + Total (di atas tabel) */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 28px;
        }

        /* Container untuk Filter dan Export */
        .filter-export-container {
            margin-bottom: 20px;
        }

        /* PERBAIKAN: Hapus tampilan card/kotak pada filter */
        .filter-form {
            display: flex;
            gap: 15px;
            align-items: flex-end;
            flex-wrap: wrap;
            padding: 0;
            /* Hapus padding */
            background: none;
            /* Hapus background */
            border-radius: 0;
            /* Hapus border-radius */
            border: none;
            /* Hapus border */
            box-shadow: none;
            /* Hapus shadow */
            justify-content: flex-start;
        }

        .filter-form .form-group {
            margin-bottom: 0;
        }

        .filter-form .btn-export-excel {
            background: #0f8f4f;
            color: white !important;
            border: none;
            padding: 10px 22px;
            border-radius: 8px;
            font-size: 14.2px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.15);
            transition: all 0.25s ease;
            text-decoration: none;
            height: 40px;
        }

        .filter-form .btn-export-excel:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(30, 126, 52, 0.4);
            background: #0e6b36;
        }

        /* Style tombol filter */
        .filter-form .btn-primary {
            padding: 10px 20px;
            border-radius: 8px;
            height: 40px;
        }

        /* Menyesuaikan input tanggal agar terlihat lebih rapi */
        .filter-form input[type="date"] {
            padding: 8px 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            height: 40px;
            font-size: 14px;
        }

        .export-container {
            display: none;
        }

        /* PERBAIKAN: Samakan Tampilan Tabel dengan Laporan Keuangan */
        .custom-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            /* Perubahan: Hilangkan jarak antar baris */
            font-size: .93rem;
            background: #fff;
            /* Perubahan: Gunakan shadow yang lebih umum */
            box-shadow: 0 .125rem .25rem rgba(0, 0, 0, .075);
            border-radius: 8px;
            overflow: hidden;
            /* Penting untuk border-radius */
            margin-top: 10px;
        }

        .custom-table thead th {
            background: #3366ff;
            color: #ffffff;
            /* Padding dan font disesuaikan agar mirip Keuangan */
            padding: 14px 12px;
            text-align: center;
            font-weight: 600;
            font-size: .8rem;
            border: none;
            text-transform: uppercase;
            letter-spacing: .5px;
            vertical-align: middle;
        }

        .custom-table tbody tr {
            background: #ffffff;
            box-shadow: none;
            /* Hilangkan shadow per baris */
            border-bottom: 1px solid #eef2f7;
            /* Tambahkan border bawah per baris */
            transition: background-color .2s;
        }

        .custom-table tbody tr:last-child {
            border-bottom: none;
        }

        .custom-table tbody tr:hover {
            transform: none;
            /* Hilangkan efek hover translasi */
            box-shadow: none;
            background: #f8f9ff !important;
            /* Warna hover Keuangan */
        }

        .custom-table td {
            padding: 14px 12px;
            /* Disesuaikan dengan Keuangan */
            vertical-align: middle;
            border: none;
            font-size: 14.5px;
        }

        /* Status Badge */
        .status-hadir {
            color: #0a8f1f;
            font-weight: 700;
        }

        .status-izin {
            color: #ff9100;
            font-weight: 700;
        }

        .status-sakit {
            color: #9c27b0;
            font-weight: 700;
        }

        .status-alpha {
            color: #d00000;
            font-weight: 700;
        }

        .status-telat {
            color: #ff5722;
            font-weight: 700;
        }

        /* Tombol Lihat Foto */
        .lihat-foto {
            background: #5d5dfb;
            color: white;
            border: none;
            padding: 9px 18px;
            border-radius: 10px;
            font-weight: 500;
            font-size: 14px;
            transition: all 0.25s ease;
            cursor: pointer;
        }

        .lihat-foto:hover {
            background: #4a4ae8;
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(93, 93, 251, 0.4);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .filter-form {
                flex-direction: column;
                align-items: stretch;
            }

            .filter-form .form-group,
            .filter-form button,
            .filter-form a {
                width: 100%;
            }

            .custom-table thead {
                display: none;
            }

            .custom-table tbody tr {
                display: block;
                margin-bottom: 15px;
                border-radius: 12px;
                padding: 10px;
                box-shadow: 0 3px 12px rgba(0, 0, 0, 0.07);
                /* Tambahkan shadow untuk mode mobile */
                border: none;
            }

            .custom-table td {
                display: block;
                text-align: right;
                padding: 8px 0;
                position: relative;
            }

            .custom-table td::before {
                content: attr(data-label);
                position: absolute;
                left: 15px;
                font-weight: bold;
                color: #555;
            }
        }
    </style>
</head>

<body>
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
                    <li class="nav-item">
                        <a href="../form-karyawan/form-user.php" class="nav-link"><i class="typcn typcn-group"></i>User</a>
                    </li>
                    <li class="nav-item">
                        <a href="../form-pembayaran/daftar_pembayaran.php" class="nav-link"><i class="typcn typcn-puzzle-outline"></i>Pembayaran</a>
                    </li>
                    <li class="nav-item">
                        <a href="../form-layanan/PaketJasa/form-paketjasa.php" class="nav-link"><i class="typcn typcn-puzzle-outline"></i>Layanan</a>
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
                        </div>
                        <a href="" class="dropdown-item"><i class="typcn typcn-user-outline"></i> My Profile</a>
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
            <div class="az-content-left az-content-left-components">
                <div class="component-item">
                    <label>Laporan</label>
                    <nav class="nav flex-column">
                        <a href="LaporanKeuangan.php" class="nav-link">Laporan Keuangan</a>
                        <a href="LaporanBooking.php" class="nav-link">Laporan Booking</a>
                        <a href="LaporanAbsensiKaryawan.php" class="nav-link active">Laporan Absensi</a>
                        <a href="LaporanPenugasan.php" class="nav-link">Laporan Penugasan</a>
                    </nav>
                </div>
            </div>

            <div class="az-content-body pd-lg-l-40 d-flex flex-column">
                <div class="az-content-breadcrumb">
                    <span>Laporan</span>
                    <span>Absensi</span>
                </div>
                <h2 class="az-content-title">Daftar Absensi Karyawan</h2>

                <div class="filter-export-container">
                    <form id="filterForm" method="GET" action="LaporanAbsensiKaryawan.php" class="filter-form">
                        <?php
                        $start_date_val = isset($_GET['start_date']) ? $_GET['start_date'] : '';
                        $end_date_val = isset($_GET['end_date']) ? $_GET['end_date'] : '';
                        ?>
                        <div class="form-group">
                            <label for="start_date">Dari Tanggal:</label>
                            <input type="date" id="start_date" name="start_date" class="form-control" value="<?php echo $start_date_val; ?>">
                        </div>
                        <div class="form-group">
                            <label for="end_date">Sampai Tanggal:</label>
                            <input type="date" id="end_date" name="end_date" class="form-control" value="<?php echo $end_date_val; ?>">
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="typcn typcn-filter"></i> Filter
                        </button>

                        <button type="button" id="exportButton" class="btn-export-excel">
                            <i class='fas fa-file-excel'></i>
                            Export Excel
                        </button>

                        <?php if (!empty($start_date_val) || !empty($end_date_val)): ?>
                            <a href="LaporanAbsensiKaryawan.php" class="btn btn-secondary" style="height: 40px; padding: 10px 20px; border-radius: 8px;">
                                <i class="typcn typcn-refresh"></i> Reset
                            </a>
                        <?php endif; ?>
                    </form>
                </div>
                <div class="col-lg-12 mg-t-20" style="max-width: 100%; margin-top: 5px !important;">
                    <?php
                    require_once __DIR__ . "/../../config/koneksi.php";
                    require_once __DIR__ . "/../../class/Absensi.php";

                    $db = new Database();
                    $conn = $db->getConnection();

                    // Ambil filter dari URL
                    $start_date = isset($_GET['start_date']) && $_GET['start_date'] !== '' ? $_GET['start_date'] . ' 00:00:00' : null;
                    $end_date = isset($_GET['end_date']) && $_GET['end_date'] !== '' ? $_GET['end_date'] . ' 23:59:59' : null;

                    // Query Dasar
                    $base_sql = "SELECT p.IDPresensi, p.PsnWaktu, p.PsnLokasi, p.PsnFoto, p.PsnStatus, u.UserNama
                             FROM presensi p
                             LEFT JOIN users u ON p.IDUser = u.IDUser";

                    // Tambahkan WHERE clause jika ada filter tanggal
                    $where_clauses = [];
                    if ($start_date) {
                        $where_clauses[] = "p.PsnWaktu >= ?";
                    }
                    if ($end_date) {
                        $where_clauses[] = "p.PsnWaktu <= ?";
                    }

                    $where_sql = count($where_clauses) > 0 ? " WHERE " . implode(" AND ", $where_clauses) : "";

                    // Parameter untuk binding
                    $params = [];
                    $types = '';
                    if ($start_date) {
                        $params[] = $start_date;
                        $types .= 's';
                    }
                    if ($end_date) {
                        $params[] = $end_date;
                        $types .= 's';
                    }


                    // PAGINASI
                    $limit = 10;
                    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
                    $offset = ($page - 1) * $limit;

                    if (!$conn) {
                        echo "<div class='alert alert-danger text-center'>Koneksi database gagal.</div>";
                    } else {
                        $absensi = new Absensi($conn);

                        // 1. Hitung Total data (untuk paginasi)
                        $total_sql = "SELECT COUNT(*) as total FROM presensi p" . $where_sql;
                        $total_stmt = $conn->prepare($total_sql);
                        if ($types) {
                            $total_stmt->bind_param($types, ...$params);
                        }
                        $total_stmt->execute();
                        $totalResult = $total_stmt->get_result()->fetch_assoc();
                        $totalRows = $totalResult['total'];
                        $totalPages = ceil($totalRows / $limit);
                        $total_stmt->close();

                        // 2. Data untuk halaman ini
                        $sql = $base_sql . $where_sql . " ORDER BY p.PsnWaktu DESC LIMIT ? OFFSET ?";
                        $stmt = $conn->prepare($sql);

                        // Gabungkan parameter filter dan paginasi
                        $types .= 'ii';
                        $params[] = $limit;
                        $params[] = $offset;

                        // Lakukan binding
                        if ($types) {
                            $stmt->bind_param($types, ...$params);
                        }
                        $stmt->execute();
                        $result = $stmt->get_result();


                        if ($result->num_rows === 0 && $totalRows == 0) {
                            echo "<div class='text-center py-5'>
                                    <i class='typcn typcn-document-text' style='font-size:5rem;color:#ddd;'></i>
                                    <h5 class='mt-3'>Belum ada data absensi</h5>
                                  </div>";
                        } else if ($result->num_rows === 0 && $totalRows > 0) {
                            echo "<div class='text-center py-5'>
                                    <h5 class='mt-3'>Tidak ada data absensi yang ditemukan untuk rentang tanggal tersebut.</h5>
                                  </div>";
                        } else {
                            echo "<table class='custom-table'>
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
                                        <td data-label='No'>$no</td>
                                        <td data-label='Nama Karyawan'><strong>$nama</strong></td>
                                        <td data-label='Tanggal'>$tanggal</td>
                                        <td data-label='Jam'><strong>$jam</strong></td>
                                        <td data-label='Lokasi'>$lokasi</td>
                                        <td data-label='Foto'>
                                            <button class='btn btn-sm lihat-foto' data-foto='$fotoPath'
                                                        style='background:#5d5dfb;color:white;border:none;padding:7px 14px;border-radius:8px;'>
                                                Lihat
                                            </button>
                                        </td>
                                        <td data-label='Status' class='$statusClass'>$statusText</td>
                                    </tr>";
                                $no++;
                            }
                            echo "</tbody></table>";

                            // PAGINASI
                            if ($totalPages > 1) {
                                echo "<nav class='mt-4'><ul class='pagination justify-content-center'>";

                                // Siapkan query string untuk paginasi (mempertahankan filter tanggal)
                                $pagination_query = '';
                                if (isset($_GET['start_date'])) $pagination_query .= '&start_date=' . urlencode($_GET['start_date']);
                                if (isset($_GET['end_date'])) $pagination_query .= '&end_date=' . urlencode($_GET['end_date']);

                                for ($i = 1; $i <= $totalPages; $i++) {
                                    $active = ($i == $page) ? "active" : "";
                                    echo "<li class='page-item $active'><a class='page-link' href='?page=$i$pagination_query'>$i</a></li>";
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

    <iframe name="exportFrame" style="display:none;"></iframe>

    <div class="modal fade" id="fotoModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-body text-center p-4">
                    <img id="modalFoto" src="" alt="Foto Absensi" style="max-width:100%; max-height:80vh; border-radius:12px; box-shadow:0 15px 35px rgba(0,0,0,0.3);">
                </div>
            </div>
        </div>
    </div>

    <script src="../lib/jquery/jquery.min.js"></script>
    <script src="../lib/popper.js/popper.min.js"></script>
    <script src="../lib/bootstrap/js/bootstrap.min.js"></script>

    <script>
        $(document).on('click', '.lihat-foto', function() {
            $('#modalFoto').attr('src', $(this).data('foto'));
            $('#fotoModal').modal('show');
        });
        $('#fotoModal').on('hidden.bs.modal', function() {
            $('#modalFoto').attr('src', '');
        });

        // Logika JavaScript untuk tombol Export Excel
        document.getElementById('exportButton').addEventListener('click', function() {
            const startDate = document.getElementById('start_date').value;
            const endDate = document.getElementById('end_date').value;

            // Buat URL export dengan filter tanggal yang saat ini dipilih
            let exportUrl = 'export_absensi_excel.php?';
            if (startDate) {
                exportUrl += 'start_date=' + encodeURIComponent(startDate) + '&';
            }
            if (endDate) {
                exportUrl += 'end_date=' + encodeURIComponent(endDate);
            }

            // Arahkan ke URL export menggunakan iframe tersembunyi
            window.open(exportUrl, 'exportFrame');
        });
    </script>

</body>

</html>