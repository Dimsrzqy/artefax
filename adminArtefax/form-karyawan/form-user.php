<?php
session_start();
date_default_timezone_set('Asia/Jakarta');

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
require_once __DIR__ . "/../../class/Users.php";

// Aktifkan error reporting MySQLi
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Initialize database
$db = new Database();
$conn = $db->getConnection();

if (!$conn) {
    die("<p style='color:red;'>Koneksi database gagal.</p>");
}

$user = new User($conn);

// --- START: DATA USER LOGIN ---
$defaultProfileImage = '../img/faces/face1.jpg'; 
$loggedInUser = [
    'UserNama' => $_SESSION['UserNama'] ?? 'Guest User', 
    'UserRole' => $_SESSION['UserRole'] ?? 'Unknown Role', 
];
// --- END: DATA USER LOGIN ---

// --- PAGINATION SETUP ---
$limit = 10;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// Hitung total data TANPA ADMIN
try {
    $total_result = $conn->query("
        SELECT COUNT(*) as total 
        FROM users 
        WHERE UserRole != 'Admin'
    ");
    $total_row = $total_result->fetch_assoc();
    $total_data = $total_row['total'] ?? 0;
    $total_pages = $total_data > 0 ? ceil($total_data / $limit) : 1;
} catch (Exception $e) {
    die("Error hitung total: " . $e->getMessage());
}

// Ambil data tanpa Admin
$sql = "
    SELECT IDUser, UserNama, UserEmail, UserNoHP, UserAlamat, UserRole 
    FROM users 
    WHERE UserRole != 'Admin'
    ORDER BY IDUser ASC 
    LIMIT ? OFFSET ?
";

try {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    $userList = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} catch (Exception $e) {
    echo "<pre>Error Query: " . $e->getMessage() . "</pre>";
    die();
}

// Handle feedback
$success_message = $_SESSION['success_message'] ?? '';
$error_message = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="Dashboard Manajemen User - Artefax">
    <title>Daftar User - Artefax</title>

    <link href="../lib/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="../lib/ionicons/css/ionicons.min.css" rel="stylesheet">
    <link href="../lib/typicons.font/typicons.css" rel="stylesheet">
    <link href="../css/azia.css" rel="stylesheet">

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
            padding-top: 30px !important; /* Space dari navbar */
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

        /* --- BADGES --- */
        .badge {
            padding: 5px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-Admin {
            background-color: #dc3545;
            color: white;
        }
        .badge-Karyawan {
            background-color: #3366ff;
            color: white;
        }
        .badge-Customer {
            background-color: #28a745;
            color: white;
        }

        /* --- PAGINATION --- */
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 20px;
        }
        .page-item {
            margin: 0 2px;
        }
        .page-link {
            padding: 0.5rem 0.75rem;
            color: #3366ff;
            background-color: #fff;
            border: 1px solid #dee2e6;
            text-decoration: none;
            border-radius: 0.35rem;
            font-size: 14px;
            min-width: 40px;
            text-align: center;
        }
        .page-link:hover {
            color: #fff;
            background-color: #3366ff;
            border-color: #3366ff;
        }
        .page-item.active .page-link {
            background-color: #3366ff;
            color: #fff;
        }

        /* --- ALERTS --- */
        .alert {
            padding: 12px 20px;
            margin: 15px 0;
            border-radius: 6px;
            font-size: 14px;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
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
                        <a href="../form-pembayaran/daftar_pembayaran.php" class="nav-link"><i class="typcn typcn-puzzle-outline"></i>Pembayaran</a>
                    </li>
                    <li class="nav-item">
                        <a href="../form-layanan/form-layanan.php" class="nav-link"><i class="typcn typcn-puzzle-outline"></i>Layanan</a>
                    </li>
                    <li class="nav-item">
                        <a href="../form-laporan/LaporanKeuangan.php" class="nav-link"><i class="typcn typcn-group-outline"></i>Laporan</a>
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
                        </div>
                        <div class="dropdown-footer"><a href="">View All Notifications</a></div>
                    </div>
                </div>
                <div class="dropdown az-profile-menu">
                    <a href="profile.php" class="az-img-user"><img src="<?= $defaultProfileImage ?>" alt=""></a>
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
                    <label>Menu User</label>
                    <nav class="nav flex-column">
                        <a href="form-user.php" class="nav-link active">Daftar User</a>
                    </nav>
                    <label>Menu Karyawan</label>
                    <a href="form-karyawan.php" class="nav-link">Daftar Karyawan</a>
                    <a href="form-booking-active.php" class="nav-link">Booking Paket</a>
                    <a href="form-penugasan.php" class="nav-link">Penugasan</a>
                </div>
            </div>

            <div class="az-content-body pd-lg-l-40 d-flex flex-column">
                <div class="az-content-breadcrumb">
                    <span>Data</span>
                    <span>User</span>
                </div>

                <h2 class="az-content-title">Daftar User</h2>

                <?php if ($success_message): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($success_message) ?></div>
                <?php endif; ?>

                <?php if ($error_message): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
                <?php endif; ?>

                <div class="table-responsive">
                    <?php if ($userList && count($userList) > 0): ?>
                        <table class="custom-table">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama</th>
                                    <th>Email</th>
                                    <th>No HP</th>
                                    <th>Alamat</th>
                                    <th>Role</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $no = $offset + 1;
                                foreach ($userList as $userItem):
                                    $role = $userItem['UserRole'];
                                    $badgeClass = match ($role) {
                                        'Admin' => 'badge-Admin',
                                        'Karyawan' => 'badge-Karyawan',
                                        'Customer' => 'badge-Customer',
                                        default => 'badge-secondary'
                                    };
                                ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                        <td><?= htmlspecialchars($userItem['UserNama']) ?></td>
                                        <td><?= htmlspecialchars($userItem['UserEmail']) ?></td>
                                        <td><?= htmlspecialchars($userItem['UserNoHP']) ?></td>
                                        <td><?= htmlspecialchars($userItem['UserAlamat']) ?></td>
                                        <td><span class="badge <?= $badgeClass ?>"><?= $role ?></span></td>
                                        <td>
                                            <form action="hapus_karyawan.php" method="POST" style="display:inline;"
                                                onsubmit="return confirm('Yakin hapus <?= htmlspecialchars($userItem['UserNama']) ?>?');">
                                                <input type="hidden" name="id" value="<?= $userItem['IDUser'] ?>">
                                                <button type="submit" class="btn btn-sm btn-danger">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                        <?php if ($total_pages > 1): ?>
                            <nav aria-label="Page navigation">
                                <ul class="pagination">
                                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                        <a class="page-link" href="?page=<?= $page - 1 ?>">Previous</a>
                                    </li>

                                    <?php 
                                    $start = max(1, $page - 2);
                                    $end   = min($total_pages, $page + 2);

                                    if ($start > 1) {
                                        echo '<li class="page-item"><a class="page-link" href="?page=1">1</a></li>';
                                        if ($start > 2) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                    }

                                    for ($i = $start; $i <= $end; $i++): ?>
                                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                            <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                                        </li>
                                    <?php endfor; 

                                    if ($end < $total_pages) {
                                        if ($end < $total_pages - 1) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                        echo '<li class="page-item"><a class="page-link" href="?page='.$total_pages.'">'.$total_pages.'</a></li>';
                                    }
                                    ?>

                                    <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                                        <a class="page-link" href="?page=<?= $page + 1 ?>">Next</a>
                                    </li>
                                </ul>
                            </nav>

                            <p class="text-center text-muted small">
                                Menampilkan <?= count($userList) ?> dari <?= $total_data ?> user
                            </p>
                        <?php endif; ?>

                    <?php else: ?>
                        <div class="text-center py-5">
                            <p class="text-muted">Belum ada user terdaftar.</p>
                            <a href="tambah-user.php" class="btn btn-primary">+ Tambah User</a>
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
            // Fix dropdown menu in fixed header
            $('.az-header .dropdown-menu').appendTo('.az-header-right .dropdown.az-profile-menu');
            
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
    </script>
</body>
</html>