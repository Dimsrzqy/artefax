<?php
// Ganti nama file ini menjadi LaporanBooking.php
require_once __DIR__ . "/../../config/koneksi.php";
require_once __DIR__ . "/../../class/Booking.php"; // Menggunakan Class Booking

$db = new Database();
// Menggunakan koneksi mysqli
$conn = $db->getConnection();
// Class Booking akan otomatis menjalankan update status Selesai
$bookingCls = new Booking($conn); 

// Status yang akan ditampilkan: Selesai DAN Batal
$statusFilterArr = ['Selesai', 'Batal'];
$statusFilterSql = "'" . implode("','", array_map([$conn, 'real_escape_string'], $statusFilterArr)) . "'";

/* ============== FILTER TANGGAL ============== */
$startDate = $_GET['start_date'] ?? null;
$endDate = $_GET['end_date'] ?? null;

// Konfigurasi Filter untuk Query SQL
$queryStartDate = null;
$queryEndDate = null;
$displayStartDate = $startDate;
$displayEndDate = $endDate;

try {
    if (!empty($startDate)) {
        // Mulai hari itu
        $queryStartDate = (new DateTime($startDate))->format('Y-m-d');
    }
    if (!empty($endDate)) {
        // Sampai akhir hari itu
        $queryEndDate = (new DateTime($endDate))->format('Y-m-d');
    }
} catch (Exception $e) {
    // Abaikan format tanggal yang salah
}

/* ============== PAGINASI DAN DATA ============== */
$limit = 10;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;


// --- Dapatkan Total Data ---
$totalRows = 0;

$totalSql = "SELECT COUNT(*) as total FROM booking WHERE BkgStatus IN ($statusFilterSql)";
$totalParams = [];
$totalTypes = '';

if ($queryStartDate && $queryEndDate) {
    $totalSql .= " AND BkgTglSelesai >= ? AND BkgTglSelesai <= ?";
    $totalParams[] = $queryStartDate;
    $totalParams[] = $queryEndDate;
    $totalTypes .= 'ss';
}

$totalQuery = $conn->prepare($totalSql);

if ($totalQuery) {
    // Solusi untuk Warning bind_param (menggunakan referensi)
    if (!empty($totalParams)) {
        $totalBindParams = array_merge([$totalTypes], $totalParams);
        $totalRefs = [];
        foreach ($totalBindParams as $key => $value) {
            $totalRefs[$key] = &$totalBindParams[$key];
        }
        call_user_func_array([$totalQuery, 'bind_param'], $totalRefs);
    }
    

    if ($totalQuery->execute()) {
        $result = $totalQuery->get_result();
        $totalRows = $result->fetch_assoc()['total'];
    }
    $totalQuery->close();
}


$totalPages = ceil($totalRows / $limit);

if ($page > $totalPages && $totalPages > 0) {
    $page = $totalPages;
}
$offset = ($page - 1) * $limit;
if ($totalRows == 0) $offset = 0;


// --- Dapatkan Daftar Booking ---
$dataBooking = [];

$sql = "SELECT 
            b.IDBooking, b.BkgTglMulai, b.BkgTglSelesai, b.BkgTotalHarga, b.BkgStatus,
            u.UserNama,
            bd.BkgDetailJenis, pj.PaketNama, a.AlatNama
        FROM booking b
        LEFT JOIN users u ON b.IDUser = u.IDUser
        LEFT JOIN booking_detail bd ON b.IDBooking = bd.IDBooking
        LEFT JOIN paketjasa pj ON bd.IDPaket = pj.IDPaket
        LEFT JOIN alat a ON bd.IDAlat = a.IDAlat
        WHERE b.BkgStatus IN ($statusFilterSql) "; // Menggunakan operator IN

$params = [];
$types = '';

if ($queryStartDate && $queryEndDate) {
    $sql .= " AND b.BkgTglSelesai >= ? AND b.BkgTglSelesai <= ?";
    $params[] = $queryStartDate;
    $params[] = $queryEndDate;
    $types .= 'ss';
}

$sql .= " ORDER BY b.BkgTglSelesai DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= 'ii';

$stmt = $conn->prepare($sql);
if ($stmt) {
    $bindParams = array_merge([$types], $params);
    $refs = [];
    foreach ($bindParams as $key => $value) {
        $refs[$key] = &$bindParams[$key];
    }
    // Perbaikan binding
    if (!empty($params) || $types === 'ii') { // Bind harus selalu dilakukan jika ada LIMIT/OFFSET
        call_user_func_array([$stmt, 'bind_param'], $refs);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $dataBooking[] = $row;
    }
    $stmt->close();
}

// Fungsi untuk format tanggal
function format_tanggal($dateString)
{
    if (empty($dateString) || $dateString === '0000-00-00' || strpos($dateString, '0000') !== false) {
        return '—';
    }
    return date('d/m/Y', strtotime($dateString));
}

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Laporan Booking | Artefax</title>

    <link href="../lib/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="../lib/ionicons/css/ionicons.min.css" rel="stylesheet">
    <link href="../lib/typicons.font/typicons.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/azia.css">

    <style>
        /* CSS yang sudah ada... */
        .custom-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            font-size: .93rem;
            background: #fff;
            box-shadow: 0 .125rem .25rem rgba(0, 0, 0, .075);
            border-radius: 8px;
            overflow: hidden;
            margin-top: 15px;
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
            vertical-align: middle
        }

        .custom-table tbody td {
            padding: 14px 12px;
            vertical-align: middle;
            border-bottom: 1px solid #eef2f7
        }

        .custom-table tbody tr:hover {
            background-color: #f8f9ff;
            transition: background-color .2s
        }

        .custom-table tbody tr:last-child td {
            border-bottom: none
        }

        /* Badge Status */
        .badge-sukses,
        .status-selesai {
            background: #d4edda;
            color: #155724;
            padding: 6px 12px;
            border-radius: 6px;
            font-weight: 600;
            font-size: .8rem;
            display: inline-block;
        }
        /* Tambahan CSS untuk status Batal */
        .status-batal { 
            background: #f8d7da;
            color: #721c24;
            padding: 6px 12px;
            border-radius: 6px;
            font-weight: 600;
            font-size: .8rem;
            display: inline-block;
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

        .btn-info {
            background: #17a2b8;
            color: #fff;
            padding: 6px 12px;
            font-size: .85rem
        }

        .btn-info:hover {
            background: #138496
        }

        /* Kontrol Layout Filter */
        .filter-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            align-items: flex-end;
            margin-bottom: 5px;
        }

        .filter-wrapper {
            margin-top: 10px;
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

        /* Responsive kecil */
        @media (max-width: 576px) {
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
        }
        .pagination .page-link {
            min-width: 40px;
            text-align: center;
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
                    <li class="nav-item"><a href="../template/index.html" class="nav-link"><i class="typcn typcn-chart-area-outline"></i> Dashboard</a></li>
                    <li class="nav-item"><a href="../form-karyawan/form-user.php" class="nav-link"><i class="typcn typcn-group"></i>User</a></li>
                    <li class="nav-item"><a href="../form-pembayaran/daftar_pembayaran.php" class="nav-link"><i class="typcn typcn-puzzle-outline"></i>Pembayaran</a></li>
                    <li class="nav-item"><a href="../form-layanan/PaketJasa/form-paketjasa.php" class="nav-link"><i class="typcn typcn-puzzle-outline"></i>Layanan</a></li>
                    <li class="nav-item active"><a href="LaporanKeuangan.php" class="nav-link"><i class="typcn typcn-group-outline"></i>Laporan</a></li>
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
                <div class="az-header-message"><a href="#"><i class="typcn typcn-messages"></i></a></div>
                <div class="dropdown az-header-notification">
                    <a href="" class="new"><i class="typcn typcn-bell"></i></a>
                    <div class="dropdown-menu"> </div>
                </div>
                <div class="dropdown az-profile-menu">
                    <a href="" class="az-img-user"><img src="../img/faces/face1.jpg" alt=""></a>
                    <div class="dropdown-menu"> </div>
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
                        <a href="LaporanBooking.php" class="nav-link active">Laporan Booking</a>
                        <a href="LaporanAbsensiKaryawan.php" class="nav-link">Laporan Absensi</a>
                        <a href="LaporanPenugasan.php" class="nav-link">Laporan Penugasan</a>
                    </nav>
                </div>
            </div>

            <div class="az-content-body pd-lg-l-40 d-flex flex-column">
                <div class="az-content-breadcrumb">
                    <span>Laporan</span>
                    <span>Booking</span>
                </div>
                <h2 class="az-content-title">Daftar Booking Selesai & Dibatalkan</h2>

                <div class="filter-wrapper">
                    <form method="GET">
                        <div class="filter-actions">
                            <div>
                                <label class="form-label" for="start_date">Dari Tanggal Selesai</label>
                                <input type="date" id="start_date" name="start_date" class="form-control" value="<?= htmlspecialchars($displayStartDate) ?>">
                            </div>
                            <div>
                                <label class="form-label" for="end_date">Sampai Tanggal Selesai</label>
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
                                <a href="export_booking_excel.php?<?= $link ?>" class="btn btn-success">
                                    <i class="fas fa-file-excel"></i> Export Excel
                                </a>
                            </div>

                            <?php if ($displayStartDate || $displayEndDate): ?>
                                <div>
                                    <a href="LaporanBooking.php" class="btn btn-secondary">
                                        <i class="typcn typcn-refresh"></i> Reset
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>

                <small class="text-muted d-block" style="margin-bottom: 5px;">
                    Menampilkan booking dengan status **Selesai** atau **Batal**
                    <?php if ($displayStartDate && $displayEndDate): ?>
                        dari **<?= format_tanggal($displayStartDate) ?>** sampai **<?= format_tanggal($displayEndDate) ?>**
                    <?php endif; ?>

                </small>

                <div class="table-responsive">
                    <?php if (!empty($dataBooking)): ?>
                        <table class="table custom-table">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama Pelanggan</th>
                                    <th>Jenis</th>
                                    <th>Detail Layanan</th>
                                    <th>Tgl Mulai</th>
                                    <th>Tgl Selesai</th>
                                    <th>Total Harga</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no = $offset + 1;
                                foreach ($dataBooking as $d):
                                    // Tentukan nama paket/alat
                                    $detailLayanan = '';
                                    if (!empty($d['PaketNama'])) {
                                        $detailLayanan = htmlspecialchars($d['PaketNama']);
                                    } elseif (!empty($d['AlatNama'])) {
                                        $detailLayanan = htmlspecialchars($d['AlatNama']);
                                    } else {
                                        $detailLayanan = '—';
                                    }

                                    // Ambil Jenis dari kolom yang benar (BkgDetailJenis)
                                    $jenisBooking = htmlspecialchars($d['BkgDetailJenis'] ?? '—');
                                    
                                    // Tentukan class badge
                                    $statusClass = (strtolower($d['BkgStatus']) === 'batal') ? 'status-batal' : 'status-selesai';
                                ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                        <td><?= htmlspecialchars($d['UserNama'] ?? '—') ?></td>
                                        <td><?= $jenisBooking ?></td>
                                        <td><?= $detailLayanan ?></td>
                                        <td><?= format_tanggal($d['BkgTglMulai']) ?></td>
                                        <td><?= format_tanggal($d['BkgTglSelesai']) ?></td>
                                        <td>Rp <?= number_format($d['BkgTotalHarga'], 0, ',', '.') ?></td>
                                        <td><span class="<?= $statusClass ?>"><?= htmlspecialchars($d['BkgStatus']) ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                        <div class="text-center text-muted small mt-3">
                            Halaman **<?= $page ?>** dari **<?= $totalPages ?>** | Total **<?= $totalRows ?>** transaksi Selesai/Batal
                        </div>

                        <?php if ($totalPages > 1): ?>
                            <nav class="mt-4">
                                <ul class="pagination justify-content-center">
                                    <?php
                                    $pagination_query = '';
                                    if (isset($_GET['start_date'])) $pagination_query .= '&start_date=' . urlencode($_GET['start_date']);
                                    if (isset($_GET['end_date'])) $pagination_query .= '&end_date=' . urlencode($_GET['end_date']);

                                    $prev_page = $page - 1;
                                    $prev_class = ($page <= 1) ? 'disabled' : '';
                                    ?>
                                    <li class="page-item <?= $prev_class ?>">
                                        <a class="page-link" href="?page=<?= $prev_page ?><?= $pagination_query ?>" aria-label="Previous">
                                            <span aria-hidden="true">Sebelumnya</span>
                                        </a>
                                    </li>

                                    <?php
                                    $start_loop = max(1, $page - 2);
                                    $end_loop = min($totalPages, $page + 2);

                                    if ($start_loop > 1) {
                                        echo '<li class="page-item"><a class="page-link" href="?page=1' . $pagination_query . '">1</a></li>';
                                        if ($start_loop > 2) {
                                            echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                        }
                                    }

                                    for ($i = $start_loop; $i <= $end_loop; $i++):
                                        $active = ($i == $page) ? "active" : "";
                                    ?>
                                        <li class="page-item <?= $active ?>">
                                            <a class="page-link" href="?page=<?= $i ?><?= $pagination_query ?>"><?= $i ?></a>
                                        </li>
                                    <?php endfor;

                                    if ($end_loop < $totalPages) {
                                        if ($end_loop < $totalPages - 1) {
                                            echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                        }
                                        echo '<li class="page-item"><a class="page-link" href="?page=' . $totalPages . $pagination_query . '">' . $totalPages . '</a></li>';
                                    }

                                    $next_page = $page + 1;
                                    $next_class = ($page >= $totalPages) ? 'disabled' : '';
                                    ?>
                                    <li class="page-item <?= $next_class ?>">
                                        <a class="page-link" href="?page=<?= $next_page ?><?= $pagination_query ?>" aria-label="Next">
                                            <span aria-hidden="true">Selanjutnya</span>
                                        </a>
                                    </li>
                                </ul>
                            </nav>
                        <?php endif; ?>

                    <?php else: ?>
                        <div class="text-center py-5">
                            <p class="text-muted">Tidak ada transaksi **Selesai** atau **Batal** pada periode ini.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <iframe name="exportFrame" style="display:none;"></iframe>

    <script src="../lib/jquery/jquery.min.js"></script>
    <script src="../lib/popper.js/popper.min.js"></script>
    <script src="../lib/bootstrap/js/bootstrap.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const exportButton = document.querySelector('a.btn-success');
            if (exportButton) {
                exportButton.addEventListener('click', function(e) {
                    // Biarkan browser mengunduh file secara default
                });
            }
        });
    </script>

</body>

</html>