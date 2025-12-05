<?php
session_start();
require_once __DIR__ . "/../../config/koneksi.php";
require_once __DIR__ . "/../../class/pembayaran.php";

$db = new Database();
$conn = $db->getConnection();

$pembayaran = new Pembayaran($conn);

/* ============== FILTER TANGGAL & STATUS BARU ============== */
$startDate = $_GET['start_date'] ?? null;
$endDate = $_GET['end_date'] ?? null;
// Status yang difilter: Lunas, Pending, Lunas DP, dan Gagal
$statusFilter = ['Lunas', 'Pending', 'Lunas DP', 'Gagal']; 

try {
    // Note: Modify('+1 day') pada endDate sudah benar untuk mencakup seluruh hari di tanggal yang dipilih.
    if ($startDate) $startDate = (new DateTime($startDate))->format('Y-m-d');
    if ($endDate)  $endDate  = (new DateTime($endDate))->modify('+1 day')->format('Y-m-d');
} catch (Exception $e) {
    $startDate = $endDate = null;
    $_SESSION['error_message'] = "Format tanggal tidak valid.";
}

/* ============== EXPORT EXCEL ============== */
if (isset($_GET['export']) && $_GET['export'] == 'excel') {
    $dataToExport = $pembayaran->readJoin(null, null, $startDate, $endDate, $statusFilter);

    $getJenis = fn($j) => $j == 'Paket Jasa,Alat' ? 'Paket & Alat' : ($j ?? '-');

    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="Laporan_Keuangan_ArtefaxID_' . date('Ymd_His') . '.xls"');

    $out = fopen('php://output', 'w');
    fputcsv($out, ['No', 'Nama Pelanggan', 'Jenis', 'Pesanan', 'Harga Awal', 'Jumlah Refund', 'PENDAPATAN BERSIH', 'Metode', 'Status Pembayaran', 'Waktu', 'ID Pembayaran', 'ID Booking'], "\t");

    $no = 1;
    foreach ($dataToExport as $p) {
        $refundJumlah = $p['RefundJumlah'] ?? 0;
        $pendapatanBersih = $p['PbrJumlah']; 
        $hargaAwal = $pendapatanBersih + $refundJumlah;
        
        $dataRow = [
            $no++,
            $p['UserNama'],
            $getJenis($p['JenisBooking']),
            $p['DaftarPesanan'] ?? '-',
            $hargaAwal, 
            $refundJumlah, 
            $pendapatanBersih, 
            $p['PbrMetode'],
            $p['PbrStatus'], 
            date('d/m/Y H:i', strtotime($p['CreatedAt'])),
            $p['IDPembayaran'],
            $p['IDBooking'],
        ];
        fputcsv($out, $dataRow, "\t");
    }
    fclose($out);
    exit();
}

/* ============== PAGINATION ============== */
$limit = 10;
$page  = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;

$totalBooking = $pembayaran->TotalBooking($startDate, $endDate, $statusFilter);
$totalPages  = ceil($totalBooking / $limit);

$daftarPembayaran = $pembayaran->readJoin($limit, $offset, $startDate, $endDate, $statusFilter);

// --- HITUNG TOTAL PENDAPATAN BERSIH UNTUK SEMUA DATA (TANPA PAGINASI) ---
$grandTotalPendapatan = 0;
$allDataForTotal = $pembayaran->readJoin(null, null, $startDate, $endDate, $statusFilter); 

foreach ($allDataForTotal as $p) {
    $pendapatanBersih = $p['PbrJumlah']; 
    $grandTotalPendapatan += $pendapatanBersih;
}
// -----------------------------------------------------------------------


$success_message = $_SESSION['success_message'] ?? '';
$error_message  = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);

$displayStartDate = $_GET['start_date'] ?? '';
$displayEndDate  = $_GET['end_date'] ?? '';

// Ambil data sesi untuk header
$loggedInUser = [
    'UserNama' => $_SESSION['UserNama'] ?? 'Guest User', 
    'UserRole' => $_SESSION['UserRole'] ?? 'Unknown Role', 
];
$defaultProfileImage = '../img/faces/face1.jpg';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Laporan Keuangan - ArtefaxID</title>
    <link href="../lib/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="../lib/ionicons/css/ionicons.min.css" rel="stylesheet">
    <link href="../lib/typicons.font/typicons.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/azia.css">
    <style>
        /* ============================================= */
        /* CSS INTERNAL YANG DIRAPIKAN */
        /* ============================================= */
        
        /* FIX LAYOUT CSS */
        .az-body {
            padding-top: 70px !important; /* Ruang untuk fixed header */
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
            top: 70px; /* Di bawah header */
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
        /* Responsive Layout untuk body content */
        @media (min-width: 992px) {
            .az-content-body {
                padding-top: 0 !important;
                margin-left: 240px !important; /* Memberi ruang untuk fixed sidebar */
            }
        }
        @media (max-width: 991.98px) {
            .az-content-left {
                position: static;
                width: 100%;
                top: auto;
                bottom: auto;
                overflow-y: visible;
                display: none; /* Sembunyikan sidebar di mobile, akan ditampilkan via JS jika perlu */
            }
        }
        /* END FIX LAYOUT CSS */

        /* Tabel & Badge */
        .custom-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            font-size: .93rem;
            background: #fff;
            box-shadow: 0 .125rem .25rem rgba(0, 0, 0, .075);
            border-radius: 8px;
            overflow: hidden
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

        .badge-sukses {
            background: #d4edda;
            color: #155724;
            padding: 6px 12px;
            border-radius: 6px;
            font-weight: 600;
            font-size: .8rem
        }
        
        .badge-pending {
            background: #ffc107;
            color: #383d41;
            padding: 6px 12px;
            border-radius: 6px;
            font-weight: 600;
            font-size: .8rem
        }
        
        .badge-gagal { 
            background: #dc3545;
            color: #fff;
            padding: 6px 12px;
            border-radius: 6px;
            font-weight: 600;
            font-size: .8rem
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
            margin-bottom: 25px;
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
        
        .total-box {
            background-color: #f1f5f9;
            padding: 15px 20px;
            border-radius: 8px;
            margin-top: 20px;
            border-left: 5px solid #3366ff;
            font-size: 1.1rem;
        }
        .total-box strong {
            font-size: 1.4rem;
            color: #0f8f4f;
        }
        
        .pagination .page-link {
            min-width: 40px;
            text-align: center;
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
                    <div class="dropdown-menu"> 
                        </div>
                </div>
                
                <div class="dropdown az-profile-menu">
                    <a href="#" class="az-img-user" id="dropdownMenuProfile" data-toggle="dropdown" aria-expanded="false">
                        <img src="<?= $defaultProfileImage ?>" alt="">
                    </a>
                    <div class="dropdown-menu">
                        <div class="az-dropdown-header mg-b-20 d-sm-none">
                            <a href="" class="az-header-arrow"><i class="icon ion-md-arrow-back"></i></a>
                        </div>
                        <div class="az-header-profile">
                            <div class="az-img-user">
                                <img src="<?= $defaultProfileImage ?>" alt="">
                            </div>
                            <h6><?= htmlspecialchars($loggedInUser['UserNama']) ?></h6>
                            <span><?= htmlspecialchars($loggedInUser['UserRole']) ?></span>
                        </div>
                        <a href="profile.php" class="dropdown-item"><i class="typcn typcn-user-outline"></i> My Profile</a>
                        <a href="edit-profile.php" class="dropdown-item"><i class="typcn typcn-edit"></i> Edit Profile</a>
                        <a href="activity-logs.php" class="dropdown-item"><i class="typcn typcn-time"></i> Activity Logs</a>
                        <a href="account-settings.php" class="dropdown-item"><i class="typcn typcn-cog-outline"></i> Account Settings</a>
                        <a href="../logout.php" class="dropdown-item"><i class="typcn typcn-power-outline"></i> Sign Out</a>
                    </div>
                </div>
                </div>
        </div>
    </div>

    <div class="az-content pd-y-20 pd-lg-y-30 pd-xl-y-40">
        <div class="container">
            <div class="az-content-left az-content-left-components d-lg-block d-none">
                <div class="component-item">
                    <label>Laporan</label>
                    <nav class="nav flex-column">
                        <a href="LaporanKeuangan.php" class="nav-link active">Laporan Keuangan</a>
                        <a href="LaporanBooking.php" class="nav-link">Laporan Booking</a>
                        <a href="LaporanAbsensiKaryawan.php" class="nav-link">Laporan Absensi</a>
                        <a href="LaporanPenugasan.php" class="nav-link">Laporan Penugasan</a>
                    </nav>
                </div>
            </div>

            <div class="az-content-body pd-lg-l-40 d-flex flex-column">
                <div class="az-content-breadcrumb">
                    <span>Data</span>
                    <span>Keuangan</span>
                </div>
                <h2 class="az-content-title">Laporan Keuangan</h2>

                <div class="mg-t-20">
                    <form method="GET">
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
                                <a href="?<?= $link ?>" class="btn btn-success">
                                    <i class="fas fa-file-excel"></i> Export Excel
                                </a>
                            </div>

                            <?php if ($displayStartDate || $displayEndDate): ?>
                                <div>
                                    <a href="LaporanKeuangan.php" class="btn btn-secondary">
                                        <i class="typcn typcn-refresh"></i> Reset
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
                <?php if ($success_message): ?><div class="alert alert-success"><?= $success_message ?></div><?php endif; ?>
                <?php if ($error_message): ?><div class="alert alert-danger"><?= $error_message ?></div><?php endif; ?>

                <div class="table-responsive">
                    <?php if ($daftarPembayaran): ?>
                        
                        <div class="total-box mb-3">
                            Total Pendapatan Bersih (Periode Filter): 
                            <strong style="color: <?= $grandTotalPendapatan < 0 ? '#dc3545' : '#0f8f4f' ?>;">
                                Rp <?= number_format($grandTotalPendapatan, 0, ',', '.') ?>
                            </strong>
                        </div>
                        
                        <table class="table custom-table">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama Pelanggan</th>
                                    <th>Jenis</th>
                                    <th>Pesanan</th>
                                    <th>Pendapatan Bersih</th> <th>Metode</th>
                                    <th>Status</th>
                                    <th>Waktu</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no = $offset + 1;
                                foreach ($daftarPembayaran as $p): 
                                    // Hitung Pendapatan Bersih
                                    $refundJumlah = $p['RefundJumlah'] ?? 0;
                                    $pendapatanBersih = $p['PbrJumlah']; // PbrJumlah sudah di-update menjadi pendapatan bersih di DB
                                    $hargaAwalBooking = ($pendapatanBersih + $refundJumlah); // Hitung mundur harga awal

                                    // Tentukan badge status
                                    $statusBadgeClass = 'badge-sukses';
                                    $statusText = $p['PbrStatus'] ?? 'Lunas';
                                    
                                    if (isset($p['BkgStatus']) && $p['BkgStatus'] == 'Batal') {
                                        $statusBadgeClass = 'badge-gagal';
                                        $statusText = 'Batal';
                                    } elseif (isset($p['PbrStatus'])) {
                                        if ($p['PbrStatus'] == 'Pending' || $p['PbrStatus'] == 'Lunas DP') {
                                            $statusBadgeClass = 'badge-pending';
                                            $statusText = $p['PbrStatus'];
                                        } elseif ($p['PbrStatus'] == 'Gagal') {
                                            $statusBadgeClass = 'badge-gagal';
                                            $statusText = 'Gagal';
                                        } else {
                                            $statusBadgeClass = 'badge-sukses';
                                            $statusText = 'Lunas';
                                        }
                                    }
                                ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                        <td><?= htmlspecialchars($p['UserNama']) ?></td>
                                        <td><?= $p['JenisBooking'] == 'Paket Jasa,Alat' ? 'Paket & Alat' : htmlspecialchars($p['JenisBooking'] ?? '-') ?></td>
                                        <td><?= htmlspecialchars($p['DaftarPesanan'] ?? '-') ?></td>
                                        <td><strong style="color: <?= $refundJumlah > 0 ? '#dc3545' : '#0f8f4f' ?>;">Rp <?= number_format($pendapatanBersih, 0, ',', '.') ?></strong></td>
                                        <td><?= htmlspecialchars($p['PbrMetode']) ?></td>
                                        <td><span class="<?= $statusBadgeClass ?>"><?= htmlspecialchars($statusText) ?></span></td>
                                        <td><?= date('d/m/Y H:i', strtotime($p['CreatedAt'])) ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-info" onclick='openDetail(<?= json_encode($p, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>, <?= $hargaAwalBooking ?>)'>Detail</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                        <div class="text-center text-muted small mt-3">
                            Halaman <?= $page ?> dari <?= $totalPages ?> | Total <?= $totalBooking ?> transaksi Lunas/Pending/Gagal
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
                            <p class="text-muted">Tidak ada transaksi **Lunas**, **Pending**, atau **Gagal** pada periode ini.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div id="detailModal" class="modal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.6);z-index:9999;justify-content:center;align-items:center;">
        <div style="background:white;border-radius:12px;max-width:500px;width:90%;padding:20px;position:relative;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:15px;">
                <h5>Detail Pembayaran</h5>
                <button onclick="document.getElementById('detailModal').style.display='none'" style="background:none;border:none;font-size:24px;cursor:pointer;">×</button>
            </div>
            <div id="detailContent"></div>
        </div>
    </div>

    <script src="../lib/jquery/jquery.min.js"></script>
    <script src="../lib/popper.js/popper.min.js"></script>
    <script src="../lib/bootstrap/js/bootstrap.min.js"></script>

    <script>
        function openDetail(data, hargaAwalBooking) {
            const jenis = data.JenisBooking === 'Paket Jasa,Alat' ? 'Paket & Alat' : (data.JenisBooking || '-');

            // Nilai dari data
            const hargaBayarSaatIni = data.PbrJumlah;
            const refundJumlah = data.RefundJumlah || 0;
            const pendapatanBersih = hargaBayarSaatIni; 
            const totalAwal = hargaAwalBooking || (pendapatanBersih + refundJumlah);


            // Helper untuk format rupiah
            const formatRupiah = (angka) => {
                if (typeof angka !== 'number' && typeof angka !== 'string') return '-';
                const number = parseFloat(angka);
                if (isNaN(number)) return '-';
                return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(number).replace('IDR', 'Rp').trim();
            }

            // Tentukan badge status untuk modal
            let statusBadgeClass = 'badge-sukses';
            let statusText = data.PbrStatus || 'Lunas';
            if (data.BkgStatus == 'Batal' || data.PbrStatus == 'Gagal') {
                statusBadgeClass = 'badge-gagal';
                statusText = data.BkgStatus == 'Batal' ? 'Batal' : 'Gagal';
            } else if (data.PbrStatus == 'Pending' || data.PbrStatus == 'Lunas DP') {
                statusBadgeClass = 'badge-pending';
                statusText = data.PbrStatus;
            }
            
            let detailHtml = `
            <div style="margin-bottom:8px;"><strong>ID Pembayaran:</strong> #${String(data.IDPembayaran).padStart(5,'0')}</div>
            <div style="margin-bottom:8px;"><strong>Nama Pelanggan:</strong> ${data.UserNama}</div>
            <div style="margin-bottom:8px;"><strong>Jenis Layanan:</strong> ${jenis}</div>
            <div style="margin-bottom:8px;"><strong>Status Pembayaran:</strong> <span class="${statusBadgeClass}">${statusText}</span></div>
            <div style="margin-bottom:8px;"><strong>Metode:</strong> ${data.PbrMetode}</div>
            <div style="margin-bottom:15px;"><strong>Waktu:</strong> ${new Date(data.CreatedAt).toLocaleString('id-ID')}</div>
            
            <hr style="border-top: 1px dashed #ccc;">`;
            
            if (refundJumlah > 0) {
                 // Kasus Pembatalan/Refund
                 detailHtml += `
                 <div style="font-size: 14px; margin-bottom: 5px;">
                     <strong>1. Jumlah Pembayaran Awal:</strong> <span style="float:right;">${formatRupiah(totalAwal)}</span>
                 </div>
                 <div style="font-size: 14px; margin-bottom: 5px; color: #dc3545;">
                     <strong>2. Potongan Refund (Diajukan):</strong> <span style="float:right;">- ${formatRupiah(refundJumlah)}</span>
                 </div>
                 <hr style="margin-top: 5px; margin-bottom: 5px;">
                 <div style="font-size: 16px; font-weight: bold; color: #0f8f4f;">
                     <strong>3. PENDAPATAN BERSIH:</strong> <span style="float:right;">${formatRupiah(pendapatanBersih)}</span>
                 </div>
                 <div style="font-size: 12px; color: #6c757d; margin-top: 10px;">
                     *Nominal PbrJumlah di database telah di-update menjadi pendapatan bersih.
                 </div>`;
            } else {
                 // Kasus Normal (Tidak Ada Refund)
                 detailHtml += `
                 <div style="font-size: 14px; margin-bottom: 5px;">
                     <strong>Total Tagihan:</strong> <span style="float:right;">${formatRupiah(totalAwal)}</span>
                 </div>
                 <div style="font-size: 16px; font-weight: bold; color: #0f8f4f; margin-top: 10px;">
                     <strong>PENDAPATAN BERSIH:</strong> <span style="float:right;">${formatRupiah(pendapatanBersih)}</span>
                 </div>`;
            }


            document.getElementById('detailContent').innerHTML = detailHtml;
            document.getElementById('detailModal').style.display = 'flex';
        }

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
        });
    </script>
</body>

</html>