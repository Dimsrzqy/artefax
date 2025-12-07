<?php
require_once __DIR__ . "/../../config/koneksi.php";
require_once __DIR__ . "/../../class/EventAssignment.php";
session_start();
if (isset($_SESSION['user']) && is_array($_SESSION['user'])) {
    $_SESSION['IDUser'] = $_SESSION['user']['IDUser'] ?? null;
    $_SESSION['UserNama'] = $_SESSION['user']['UserNama'] ?? 'Guest User';
    $_SESSION['UserRole'] = $_SESSION['user']['UserRole'] ?? 'Unknown Role';
}
if (!isset($_SESSION['IDUser']) || empty($_SESSION['IDUser'])) {
   header("Location: ../../View/login.php"); 
    exit;
}
$loggedInUser = [
    'UserNama' => $_SESSION['UserNama'] ?? 'Admin',
    'UserRole' => $_SESSION['UserRole'] ?? 'Administrator',
];
$defaultProfileImage = '../img/faces/artefax.jpg';
$db = new Database();
$conn = $db->getConnection();
$event = new EventAssignment($conn);
$event->updateStatusOtomatis();
$start_date = $_GET['start_date'] ?? null;
$end_date = $_GET['end_date'] ?? null;
$where_clauses = [];
$params_filter = []; // Parameter hanya untuk filter (tipe string)
$types_filter = '';
if (!empty($start_date)) {
    $where_clauses[] = "e.EventTanggal >= ?";
    $params_filter[] = $start_date;
    $types_filter .= 's';
}
if (!empty($end_date)) {
    $where_clauses[] = "e.EventTanggal <= ?";
    $params_filter[] = $end_date;
    $types_filter .= 's';
}
$where_sql = count($where_clauses) > 0 ? " WHERE " . implode(" AND ", $where_clauses) : "";
$limit = 10;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;
$total_sql = "SELECT COUNT(*) AS total FROM event e" . $where_sql;
$total_stmt = $conn->prepare($total_sql);
if ($types_filter) {
    $total_stmt->bind_param($types_filter, ...$params_filter);
}
$total_stmt->execute();
$totalRows = $total_stmt->get_result()->fetch_assoc()['total'];
$totalPages = ceil($totalRows / $limit);
$total_stmt->close();
$sql = "
    SELECT e.*, 
        GROUP_CONCAT(u.UserNama ORDER BY u.UserNama ASC SEPARATOR ', ') AS Karyawan
    FROM event e
    LEFT JOIN event_karyawan ek ON e.IDEvent = ek.IDEvent
    LEFT JOIN users u ON ek.IDKaryawan = u.IDUser
    " . $where_sql . "
    GROUP BY e.IDEvent
    ORDER BY e.EventTanggal DESC, e.EventMulai DESC
    LIMIT ? OFFSET ?
";
$stmt = $conn->prepare($sql);
$params_final = array_merge($params_filter, [$limit, $offset]);
$types_final = $types_filter . 'ii'; // Tambahkan 'ii' untuk LIMIT dan OFFSET
if ($types_final) {
    $stmt->bind_param($types_final, ...$params_final);
}
$stmt->execute();
$result = $stmt->get_result();
$displayStartDate = $_GET['start_date'] ?? '';
$displayEndDate = $_GET['end_date'] ?? '';
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
        .filter-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            align-items: flex-end;
            margin-bottom: 25px;
        }
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
        .filter-actions .form-control {
            width: 170px;
            height: 40px;
            padding: 8px 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
        }
        .filter-actions label {
            display: block;
            margin-bottom: 5px;
            font-size: .85rem;
            font-weight: 600;
        }
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
        .btn-primary {
            background: #3366ff;
            color: #fff
        }
        .btn-primary:hover {
            background: #2952cc;
            transform: translateY(-1px);
            box-shadow: 0 4px 10px rgba(51, 102, 255, .3)
        }
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
        .status-menunggu {
            color: #0d6efd; /* Blue */
            font-weight: 700;
        }
        .status-berjalan {
            color: #ffc107; /* Yellow/Orange */
            font-weight: 700;
        }
        .status-selesai {
            color: #0a8f1f; /* Green */
            font-weight: 700;
        }
        .pagination {
            margin-bottom: 1rem;
        }
        .pagination .page-link {
            color: #3366ff;
            border: 1px solid #dee2e6;
            padding: 0.5rem 0.75rem;
            margin: 0 2px;
            border-radius: 4px;
            transition: all 0.2s;
        }
        .pagination .page-link:hover {
            background-color: #e9ecef;
            border-color: #3366ff;
            color: #2952cc;
        }
        .pagination .page-item.active .page-link {
            background-color: #3366ff;
            border-color: #3366ff;
            color: white;
            font-weight: 600;
        }
        .pagination .page-item.disabled .page-link {
            color: #6c757d;
            pointer-events: none;
            background-color: #fff;
            border-color: #dee2e6;
            cursor: not-allowed;
        }
        .sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0,0,0,0);
            white-space: nowrap;
            border: 0;
        }
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
        .az-header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1040;
            background-color: #fff;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
        }
        body {
            padding-top: 70px !important;
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
                margin-left: 240px !important;
            }
        }
        @media (max-width: 991.98px) {
            .az-content-left.az-content-left-components {
                position: static;
                top: auto;
                bottom: auto;
                overflow-y: visible;
                border-right: none;
            }
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
                    <li class="nav-item">
                        <a href="../form-karyawan/form-user.php" class="nav-link"><i class="typcn typcn-group"></i>User</a>
                    </li>
                    <li class="nav-item">
                        <a href="../form-pembayaran/daftar_pembayaran.php" class="nav-link">
                            <i class="fas fa-money-bill-alt" style="margin-right: 8px;"></i> Pembayaran
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="../form-layanan/PaketJasa/form-paketjasa.php" class="nav-link"><i class="typcn typcn-puzzle-outline"></i>Layanan</a>
                    </li>
                    <li class="nav-item active">
                        <a href="../form-laporan/LaporanKeuangan.php" class="nav-link">
                            <i class="fas fa-file-alt" style="margin-right: 8px;"></i> Laporan
                        </a>
                    </li>
                </ul>
            </div>
            <div class="az-header-right">
                <div class="dropdown az-profile-menu">
                    <a href="#" class="az-img-user dropdown-toggle" data-toggle="dropdown"><img src="<?= $defaultProfileImage ?>" alt=""></a>
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
                <h2 class="az-content-title"><i class="fas fa-tasks"></i> Daftar Penugasan Karyawan</h2>
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
                                    <i class="fas fa-file-excel"></i> Export CSV
                                </a>
                            </div>
                            <?php if ($displayStartDate || $displayEndDate) : ?>
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
                        if ($totalRows === 0) {
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
                                $waktu = substr($row['EventMulai'], 0, 5) . " - " . substr($row['EventSelesai'], 0, 5);
                                $status = strtolower($row['EventStatus']);
                                echo "
                            <tr>
                                <td data-label='No'>$no</td>
                                <td data-label='Nama Event'><strong>" . htmlspecialchars($row['EventNama']) . "</strong></td>
                                <td data-label='Lokasi'>" . htmlspecialchars($row['EventLokasi']) . "</td>
                                <td data-label='Tanggal'>$tanggal</td>
                                <td data-label='Waktu'>$waktu</td>
                                <td data-label='Karyawan'>" . htmlspecialchars($row['Karyawan']) . "</td>
                                <td data-label='Status' class='status-{$status}'>
                                    " . htmlspecialchars($row['EventStatus']) . "
                                </td>
                            </tr>";
                                $no++;
                            }
                        }
                        ?>
                    </tbody>
                </table>
                <?php 
                if ($totalPages > 1) {
                    // Build query string untuk filter
                    $pagination_query = [];
                    if (!empty($_GET['start_date'])) {
                        $pagination_query[] = 'start_date=' . urlencode($_GET['start_date']);
                    }
                    if (!empty($_GET['end_date'])) {
                        $pagination_query[] = 'end_date=' . urlencode($_GET['end_date']);
                    }
                    $query_string = !empty($pagination_query) ? '&' . implode('&', $pagination_query) : '';
                    
                    // Tentukan range halaman yang ditampilkan
                    $range = 2; // Jumlah halaman sebelum dan sesudah halaman aktif
                    $start_page = max(1, $page - $range);
                    $end_page = min($totalPages, $page + $range);
                ?>
                    <nav class="mt-4" aria-label="Navigasi Halaman">
                        <ul class="pagination justify-content-center">
                            <!-- Tombol Previous -->
                            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                <a class="page-link" href="<?= $page > 1 ? '?page=' . ($page - 1) . $query_string : '#' ?>" 
                                   <?= $page <= 1 ? 'tabindex="-1" aria-disabled="true"' : '' ?>>
                                    <i class="typcn typcn-chevron-left"></i> Previous
                                </a>
                            </li>

                            <!-- Halaman Pertama -->
                            <?php if ($start_page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=1<?= $query_string ?>">1</a>
                                </li>
                                <?php if ($start_page > 2): ?>
                                    <li class="page-item disabled">
                                        <span class="page-link">...</span>
                                    </li>
                                <?php endif; ?>
                            <?php endif; ?>

                            <!-- Halaman Tengah -->
                            <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                                <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                    <a class="page-link" href="?page=<?= $i ?><?= $query_string ?>">
                                        <?= $i ?>
                                        <?php if ($i == $page): ?>
                                            <span class="sr-only">(current)</span>
                                        <?php endif; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>

                            <!-- Halaman Terakhir -->
                            <?php if ($end_page < $totalPages): ?>
                                <?php if ($end_page < $totalPages - 1): ?>
                                    <li class="page-item disabled">
                                        <span class="page-link">...</span>
                                    </li>
                                <?php endif; ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?= $totalPages ?><?= $query_string ?>"><?= $totalPages ?></a>
                                </li>
                            <?php endif; ?>

                            <!-- Tombol Next -->
                            <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                                <a class="page-link" href="<?= $page < $totalPages ? '?page=' . ($page + 1) . $query_string : '#' ?>"
                                   <?= $page >= $totalPages ? 'tabindex="-1" aria-disabled="true"' : '' ?>>
                                    Next <i class="typcn typcn-chevron-right"></i>
                                </a>
                            </li>
                        </ul>
                        <!-- Info Halaman -->
                        <div class="text-center mt-2">
                            <small class="text-muted">
                                Halaman <?= $page ?> dari <?= $totalPages ?> 
                                (Total <?= $totalRows ?> event)
                            </small>
                        </div>
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
<script src="../js/azia.js"></script>
<script>
    $(document).ready(function() {
        $('#azMenuShow').on('click', function(e) {
            e.preventDefault();
            $('.az-header-menu').toggleClass('show');
        });
        $('.az-header-menu .close').on('click', function(e) {
            e.preventDefault();
            $('.az-header-menu').removeClass('show');
        });
        var $dropdown = $('.az-profile-menu');
        var $toggle = $dropdown.find('.dropdown-toggle');
        var $menu = $dropdown.find('.dropdown-menu');
        if ($toggle.length) {
            $toggle.attr('data-toggle', 'dropdown');
        }
        if ($menu.length && !$menu.parent().is($dropdown)) {
            $menu.appendTo($dropdown);
        }
        $dropdown.dropdown(); 
    });
</script>
</html>