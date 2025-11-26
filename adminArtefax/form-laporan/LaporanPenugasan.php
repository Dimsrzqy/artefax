<?php
require_once __DIR__ . "/../../config/koneksi.php";
require_once __DIR__ . "/../../class/EventAssignment.php";

$db = new Database();
$conn = $db->getConnection();
$event = new EventAssignment($conn);

/* ============== FILTER RENTANG TANGGAL ============== */
$start_date = $_GET['start_date'] ?? null;
$end_date = $_GET['end_date'] ?? null;

// Tentukan klausa WHERE berdasarkan filter
$where_clauses = [];
$params = [];
$types = '';

if (!empty($start_date)) {
    $where_clauses[] = "e.EventTanggal >= ?";
    $params[] = $start_date;
    $types .= 's';
}
if (!empty($end_date)) {
    $where_clauses[] = "e.EventTanggal <= ?";
    $params[] = $end_date;
    $types .= 's';
}

$where_sql = count($where_clauses) > 0 ? " WHERE " . implode(" AND ", $where_clauses) : "";

/* ============== PAGINASI ============== */
$limit = 10;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// 1. Hitung Total Event (dengan Filter)
$total_sql = "SELECT COUNT(*) AS total FROM event e" . $where_sql;
$total_stmt = $conn->prepare($total_sql);

if ($types) {
    $total_stmt->bind_param($types, ...$params);
}
$total_stmt->execute();
$totalRows = $total_stmt->get_result()->fetch_assoc()['total'];
$totalPages = ceil($totalRows / $limit);
$total_stmt->close();

// 2. Ambil data event + karyawan (dengan Filter dan Paginasi)
$sql = "
    SELECT e.*, 
        GROUP_CONCAT(u.UserNama SEPARATOR ', ') AS Karyawan
    FROM event e
    LEFT JOIN event_karyawan ek ON e.IDEvent = ek.IDEvent
    LEFT JOIN users u ON ek.IDKaryawan = u.IDUser
    " . $where_sql . "
    GROUP BY e.IDEvent
    ORDER BY e.EventTanggal DESC, e.EventMulai DESC
    LIMIT ? OFFSET ?
";

$stmt = $conn->prepare($sql);

// Gabungkan parameter filter dan paginasi
$types .= 'ii';
$params[] = $limit;
$params[] = $offset;

if ($types) {
    // Perlu memisahkan jenis dan parameter untuk bind_param
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Nilai yang ditampilkan di input filter
$displayStartDate = $_GET['start_date'] ?? '';
$displayEndDate  = $_GET['end_date'] ?? '';
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

    <style>
        /* ============================================= */
        /* CSS INTERNAL: Filter & Tabel Konsisten */
        /* ============================================= */

        /* Kontrol Layout Filter */
        .filter-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            align-items: flex-end;
            margin-bottom: 25px;
        }

        /* Hilangkan tampilan card/kotak pada filter */
        .filter-form {
            display: flex;
            gap: 15px;
            align-items: flex-end;
            flex-wrap: wrap;
            padding: 0;
            background: none;
            border-radius: 0;
            border: none;
            box-shadow: none;
            justify-content: flex-start;
        }

        .filter-form .form-group {
            margin-bottom: 0;
        }

        /* Kontrol lebar input tanggal */
        .filter-actions .form-control {
            width: 170px;
            height: 40px;
            padding: 8px 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
        }

        /* Label input tanggal */
        .filter-actions label {
            display: block;
            margin-bottom: 5px;
            font-size: .85rem;
            font-weight: 600;
        }

        /* Button style dasar */
        .btn {
            padding: 10px 20px;
            border-radius: 6px;
            font-weight: 500;
            font-size: .9rem;
            transition: all .2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: none;
            cursor: pointer;
            text-decoration: none;
            height: 40px;
        }

        /* Primary/Filter Button */
        .btn-primary {
            background: #3366ff;
            color: #fff
        }

        .btn-primary:hover {
            background: #2952cc;
            transform: translateY(-1px);
            box-shadow: 0 4px 10px rgba(51, 102, 255, .3)
        }

        /* Success/Export Button */
        .btn-success {
            background: #0f8f4f;
            color: #fff;
            font-weight: 600;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.15);
        }

        .btn-success:hover {
            background: #0e6b36;
            transform: translateY(-1px);
            box-shadow: 0 4px 10px rgba(15, 143, 79, .4)
        }

        /* Secondary/Reset Button */
        .btn-secondary {
            background: #6c757d;
            color: #fff;
            font-weight: 500;
        }

        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-1px);
            box-shadow: 0 4px 10px rgba(108, 117, 125, .4);
        }

        /* TABEL KONSISTEN DENGAN PEMBAYARAN/ABSENSI */
        .custom-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            font-size: .93rem;
            background: #fff;
            box-shadow: 0 .125rem .25rem rgba(0, 0, 0, .075);
            border-radius: 8px;
            overflow: hidden;
        }

        .custom-table thead th {
            background: #3366ff;
            color: #fff;
            font-weight: 600;
            text-transform: uppercase;
            font-size: .8rem;
            letter-spacing: .5px;
            padding: 14px 12px;
            text-align: center;
            vertical-align: middle;
            border: none;
            /* Tambahkan ini agar border-radius berfungsi */
        }

        .custom-table tbody td {
            padding: 14px 12px;
            vertical-align: middle;
            border-bottom: 1px solid #eef2f7;
        }

        .custom-table tbody tr:hover {
            background-color: #f8f9ff;
            transition: background-color .2s;
        }

        .custom-table tbody tr:last-child td {
            border-bottom: none;
        }

        /* Status Badge */
        .status-baru,
        .status-new {
            color: #0d6efd;
            font-weight: 700;
        }

        .status-proses,
        .status-progress {
            color: #ffc107;
            font-weight: 700;
        }

        .status-selesai,
        .status-completed {
            color: #0a8f1f;
            font-weight: 700;
        }

        /* Responsive kecil */
        @media (max-width: 768px) {

            .filter-form,
            .filter-actions {
                flex-direction: column;
                align-items: stretch;
            }

            .filter-actions>div,
            .filter-actions .btn {
                width: 100%;
            }

            .filter-actions .form-control {
                width: 100% !important;
            }

            .custom-table thead {
                display: none;
            }

            .custom-table tbody tr {
                display: block;
                margin-bottom: 15px;
                border-radius: 8px;
                padding: 10px;
                box-shadow: 0 3px 12px rgba(0, 0, 0, 0.07);
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
                        <a href="../form-layanan/form-layanan.php" class="nav-link"><i class="typcn typcn-puzzle-outline"></i>Layanan</a>
                    </li>
                    <li class="nav-item active">
                        <a href="../form-laporan/LaporanPenugasan.php" class="nav-link"><i class="typcn typcn-group-outline"></i>Laporan</a>
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
                    </div>
                </div>
                <div class="dropdown az-profile-menu">
                    <a href="" class="az-img-user"><img src="../img/faces/face1.jpg" alt=""></a>
                    <div class="dropdown-menu">
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
                        <a href="LaporanAbsensiKaryawan.php" class="nav-link">Laporan Absensi</a>
                        <a href="LaporanPenugasan.php" class="nav-link active">Laporan Penugasan</a>
                    </nav>
                </div>
            </div>

            <div class="az-content-body pd-lg-l-40 d-flex flex-column">
                <div class="az-content-breadcrumb">
                    <span>Laporan</span>
                    <span>Penugasan</span>
                </div>
                <h2 class="az-content-title">Daftar Penugasan Karyawan</h2>

                <div class="mg-t-20">
                    <form method="GET" class="filter-form">
                        <div class="filter-actions">
                            <div>
                                <label class="form-label" for="start_date">Dari Tanggal</label>
                                <input type="date" id="start_date" name="start_date" class="form-control" value="<?= htmlspecialchars($displayStartDate) ?>">
                            </div>
                            <div>
                                <label class="form-label" for="end_date">Sampai Tanggal</label>
                                <input type="date" id="end_date" name="end_date" class="form-control" value="<?= htmlspecialchars($displayEndDate) ?>">
                            </div>

                            <div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="typcn typcn-filter"></i> Filter
                                </button>
                            </div>

                            <div>
                                <?php
                                $exportParams = $_GET;
                                $exportParams['export'] = 'excel';
                                unset($exportParams['page']);
                                $link = http_build_query($exportParams);
                                ?>
                                <a href="export_penugasan_excel.php?<?= $link ?>" class="btn btn-success">
                                    <i class="fas fa-file-excel"></i> Export Excel
                                </a>
                            </div>

                            <?php if ($displayStartDate || $displayEndDate): ?>
                                <div>
                                    <a href="LaporanPenugasan.php" class="btn btn-secondary">
                                        <i class="typcn typcn-refresh"></i> Reset
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
                <table class="custom-table">
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
                            $colSpan = 7;
                            if (!empty($displayStartDate) || !empty($displayEndDate)) {
                                echo "<tr><td colspan='{$colSpan}' class='text-center py-5'>Tidak ada penugasan pada rentang tanggal tersebut.</td></tr>";
                            } else {
                                echo "<tr><td colspan='{$colSpan}' class='text-center py-5'>Belum ada data event.</td></tr>";
                            }
                        } else {
                            $no = $offset + 1;
                            while ($row = $result->fetch_assoc()) {
                                $tanggal = (new DateTime($row['EventTanggal']))->format('d/m/Y');
                                $waktu  = $row['EventMulai'] . " - " . $row['EventSelesai'];
                                $status = strtolower($row['EventStatus']);

                                echo "
                            <tr>
                                <td data-label='No'>$no</td>
                                <td data-label='Nama Event'><strong>{$row['EventNama']}</strong></td>
                                <td data-label='Lokasi'>{$row['EventLokasi']}</td>
                                <td data-label='Tanggal'>$tanggal</td>
                                <td data-label='Waktu'>$waktu</td>
                                <td data-label='Karyawan'>{$row['Karyawan']}</td>
                                <td data-label='Status' class='status-{$status}'>
                                    {$row['EventStatus']}
                                </td>
                            </tr>";
                                $no++;
                            }
                        }
                        ?>
                    </tbody>
                </table>

                <?php if ($totalPages > 1) {
                    $pagination_query = '';
                    if (isset($_GET['start_date'])) $pagination_query .= '&start_date=' . urlencode($_GET['start_date']);
                    if (isset($_GET['end_date'])) $pagination_query .= '&end_date=' . urlencode($_GET['end_date']);

                ?>
                    <nav class="mt-4">
                        <ul class="pagination justify-content-center">
                            <?php for ($i = 1; $i <= $totalPages; $i++) { ?>
                                <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                    <a class="page-link" href="?page=<?= $i ?><?= $pagination_query ?>"><?= $i ?></a>
                                </li>
                            <?php } ?>
                        </ul>
                    </nav>
                <?php } ?>

                <iframe name="exportFrame" style="display:none;"></iframe>

            </div>
        </div>

    </div>

</body>
<script src="../lib/jquery/jquery.min.js"></script>
<script src="../lib/popper.js/popper.min.js"></script>
<script src="../lib/bootstrap/js/bootstrap.min.js"></script>

</html>