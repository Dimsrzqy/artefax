<?php
session_start();
require_once __DIR__ . "/../../../config/koneksi.php";
require_once __DIR__ . "/../../../class/pembayaran.php";
require_once __DIR__ . "/../../../class/users.php"; 

// --- START: VERIFIKASI SESI KRITIS ---
if (isset($_SESSION['user']) && is_array($_SESSION['user'])) {
    $_SESSION['IDUser'] = $_SESSION['user']['IDUser'] ?? null;
    $_SESSION['UserNama'] = $_SESSION['user']['UserNama'] ?? 'Guest User';
    $_SESSION['UserRole'] = $_SESSION['user']['UserRole'] ?? 'Unknown Role';
}

if (!isset($_SESSION['IDUser']) || empty($_SESSION['IDUser'])) {
    // Path relatif dari /adminArtefax/form-pembayaran/refund/pengajuan_refund.php ke /adminArtefax/view/login.php
    header("Location: ../../../view/login.php"); 
    exit;
}

// Data User untuk Header
$loggedInUser = [
    'UserNama' => $_SESSION['UserNama'] ?? 'Admin',
    'UserRole' => $_SESSION['UserRole'] ?? 'Administrator',
];
$defaultProfileImage = '../../img/faces/face1.jpg';
// --- END: VERIFIKASI SESI KRITIS ---


$db = new Database();
$conn = $db->getConnection();

$pembayaran = new Pembayaran($conn);
$user = new User($conn);

// Ambil data user yang sedang login
$currentUser = null;
if (isset($_SESSION['IDUser'])) {
    $currentUser = $loggedInUser; 
}


// Asumsi: Anda memiliki metode readPendingRefund yang mengambil data refund
$pendingRefunds = $pembayaran->readPendingRefund();

// Feedback
$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pengajuan Refund - Admin ArtefaxID</title>

    <link href="../../lib/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="../../lib/ionicons/css/ionicons.min.css" rel="stylesheet">
    <link href="../../lib/typicons.font/typicons.css" rel="stylesheet">
    <link href="../../css/azia.css" rel="stylesheet">
    <style>
        /* Fixed Header */
        .az-header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        /* Fixed Sidebar */
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
        /* Content Body Adjustment */
        .az-content {
            margin-top: 70px; /* Sesuaikan dengan tinggi header */
        }

        .az-content-body {
            margin-left: 0;
        }

        @media (min-width: 992px) {
            .az-content-body {
                margin-left: 250px; /* Sesuaikan dengan lebar sidebar */
            }
        }

        /* Profile Dropdown Enhancement */
        .az-header-profile {
            padding: 15px 20px;
            border-bottom: 1px solid #e9ecef;
            text-align: center;
        }

        .az-header-profile .az-img-user {
            width: 60px;
            height: 60px;
            margin: 0 auto 10px;
        }

        .az-header-profile .az-img-user img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
            border: 3px solid #f0f0f0;
        }

        .az-header-profile h6 {
            margin: 8px 0 4px;
            font-size: 16px;
            font-weight: 600;
            color: #1c273c;
        }

        .az-header-profile span {
            font-size: 13px;
            color: #97a3b9;
        }

        .card-payment {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            transition: transform 0.2s;
            margin-bottom: 20px;
        }

        .card-payment:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .card-refund .card-header {
            background: linear-gradient(135deg, #ffc107, #ffb300) !important;
            color: #000 !important;
            font-weight: 600;
            font-size: 1.1em;
        }

        .card-body {
            padding: 16px;
        }

        .status-refund-badge {
            white-space: nowrap !important;
            display: inline-block !important;
            max-width: 100%;
            overflow: hidden;
            text-overflow: ellipsis;
            font-size: 0.85rem;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .info-label {
            font-weight: 600;
            color: #555;
            width: 45%;
        }

        .info-value {
            color: #333;
            text-align: right;
            width: 55%;
        }

        .badge-pending {
            background: #fff3cd;
            color: #856404;
            padding: 3px 8px;
            border-radius: 2px;
            font-size: 11px;
        }

        .card-footer {
            padding: 12px 16px;
            background: #f8f9fa;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top: 1px solid #eee;
        }

        .btn-action {
            padding: 6px 12px;
            font-size: 13px;
            border-radius: 6px;
            font-weight: 500;
            color: white;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            text-decoration: none;
        }

        .btn-detail {
            background: #17a2b8;
        }

        .btn-setuju-refund {
            min-width: 130px;
            background: #28a745;
        }

        .btn-tolak {
            background: #dc3545;
        }

        .alert {
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }

        .empty-state i {
            font-size: 48px;
            color: #ccc;
            margin-bottom: 16px;
        }

        .modal-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background-color: rgba(0,0,0,0.6);
            z-index: 9999;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background: white;
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            animation: muncul 0.3s ease-out;
        }

        @keyframes muncul {
            from { opacity: 0; transform: translateY(-50px) scale(0.9); }
            to   { opacity: 1; transform: translateY(0) scale(1); }
        }

        .modal-header {
            padding: 15px 20px;
            color: white;
            font-weight: 600;
            border-radius: 12px 12px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header.setuju {
            background: #28a745;
        }

        .modal-header.tolak {
            background: #dc3545;
        }

        .modal-body {
            padding: 20px;
            text-align: center;
            font-size: 15px;
        }

        .modal-footer {
            padding: 15px 20px;
            border-top: 1px solid #eee;
            text-align: right;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .close-btn {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: white;
            font-weight: bold;
        }

        .close-btn:hover {
            opacity: 0.8;
        }

        /* Smooth Scrollbar untuk Sidebar */
        .az-content-left::-webkit-scrollbar {
            width: 6px;
        }

        .az-content-left::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        .az-content-left::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 3px;
        }

        .az-content-left::-webkit-scrollbar-thumb:hover {
            background: #555;
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
                    <a href="index.html" class="az-logo"><span></span> azia</a>
                    <a href="" class="close">&times;</a>
                </div>
                <ul class="nav">
                    <li class="nav-item">
                        <a href="../../index.html" class="nav-link"><i class="typcn typcn-chart-area-outline"></i> Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a href="../../form-karyawan/form-user.php" class="nav-link"><i class="typcn typcn-group"></i>User</a>
                    </li>
                    <li class="nav-item active">
                        <a href="../daftar_pembayaran.php" class="nav-link"><i class="typcn typcn-puzzle-outline"></i>Pembayaran</a>
                    </li>
                    <li class="nav-item">
                        <a href="../../form-layanan/PaketJasa/form-paketjasa.php" class="nav-link"><i class="typcn typcn-puzzle-outline"></i>Layanan</a>
                    </li>
                    <li class="nav-item">
                        <a href="../../form-laporan/LaporanKeuangan.php" class="nav-link"><i class="typcn typcn-group-outline"></i>Laporan</a>
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
                        <a href="../../../View/profile.php" class="dropdown-item"><i class="typcn typcn-user-outline"></i> My Profile</a>
                        <a href="../../../logout.php" class="dropdown-item"><i class="typcn typcn-power-outline"></i> Sign Out</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="az-content pd-y-20 pd-lg-y-30 pd-xl-y-40">
        <div class="container">
            <div class="az-content-left az-content-left-components">
                <div class="component-item">
                    <label>Pembayaran</label>
                    <nav class="nav flex-column">
                        <a href="../daftar_pembayaran.php" class="nav-link">Daftar Pembayaran</a>
                        <a href="../pembayaran/konfirmasi_pembayaran.php" class="nav-link">Konfirmasi Pembayaran</a>
                    </nav>
                    <label>Pelunasan DP</label>
                    <nav class="nav flex-column">
                        <a href="../dp/pelunasan_pembayaran.php" class="nav-link">Pelunasan Pembayaran</a>
                    </nav>
                    <label>Refund</label>
                    <nav class="nav flex-column">
                        <a href="../refund/pengajuan_refund.php" class="nav-link active">Pengajuan Refund</a>
                    </nav>
                </div>
            </div>

            <div class="az-content-body pd-lg-l-40 d-flex flex-column">
                <div class="az-content-breadcrumb">
                    <span>Pembayaran</span>
                    <span>Pengajuan Refund</span>
                </div>
                <h2 class="az-content-title">Pengajuan Refund</h2>
                <p class="mg-b-20">Verifikasi dan proses pengajuan refund dari pelanggan.</p>

                <?php if ($success): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <div class="row">
                    <?php if ($pendingRefunds && count($pendingRefunds) > 0): ?>
                        <?php foreach ($pendingRefunds as $r): 
                            // Asumsi $user->getUserByID mengembalikan array user data
                            $customer = $user->getUserByID($r['IDUser']);
                            $namaCustomer = $customer['UserNama'] ?? 'Unknown';
                            $tglBooking = date('d M Y', strtotime($r['BkgTglMulai']));
                            $tglPengajuan = date('d M Y, H:i', strtotime($r['RefundWaktu'])) . ' WIB';
                            $rf = $r; // Menggunakan variabel yang sama untuk modal
                        ?>
                            <div class="col-md-6 col-lg-4 mb-4" data-id="<?= $r['IDRefund'] ?>">
                                <div class="card-refund shadow-sm h-100 border-warning">
                                    <div class="card-header bg-warning text-dark fw-bold">
                                        REFUND#<?= str_pad($r['IDRefund'], 6, '0', STR_PAD_LEFT) ?>
                                    </div>
                                    <div class="card-body">
                                        <div class="info-row">
                                            <span class="info-label">Nama Pelanggan</span>
                                            <span class="info-value"><?= htmlspecialchars($namaCustomer) ?></span>
                                        </div>
                                        <div class="info-row">
                                            <span class="info-label">Booking ID</span>
                                            <span class="info-value">BOOK#<?= str_pad($r['IDBooking'], 6, '0', STR_PAD_LEFT) ?></span>
                                        </div>
                                        <div class="info-row">
                                            <span class="info-label">Tanggal Booking</span>
                                            <span class="info-value"><?= $tglBooking ?></span>
                                        </div>
                                        <div class="info-row">
                                            <span class="info-label">Jumlah Dibayar</span>
                                            <span class="info-value">Rp <?= number_format($r['JumlahBayar'] ?? 0, 0, ',', '.') ?></span>
                                        </div>
                                        <div class="info-row">
                                            <span class="info-label">Jumlah Refund</span>
                                            <span class="info-value text-danger fw-bold">Rp <?= number_format($r['RefundJumlah'], 0, ',', '.') ?></span>
                                        </div>
                                        <div class="info-row">
                                            <span class="info-label">Alasan Refund</span>
                                            <span class="info-value small text-muted"><?= htmlspecialchars($r['RefundAlasan']) ?></span>
                                        </div>
                                        <div class="info-row">
                                            <span class="info-label">Diajukan pada</span>
                                            <span class="info-value"><?= $tglPengajuan ?></span>
                                        </div>
                                        <div class="info-row">
                                            <span class="info-label">Status</span>
                                            <span class="info-value">
                                                <span class="badge bg-warning text-dark status-refund-badge">Pending</span>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="card-footer d-flex justify-content-between align-items-center">
                                        <button class="btn-action btn-detail" 
                                                    onclick='openRefundPopup(<?= json_encode($rf, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                                            <i class="fas fa-eye"></i> Detail
                                        </button>
                                        <button type="button" class="btn btn-success btn-sm btn-setuju-refund" onclick="konfirmasiRefund(<?= $r['IDRefund'] ?>)">
                                            Setujui Refund
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="col-12">
                            <div class="empty-state text-center py-5">
                                <i class="fas fa-check-double fa-3x text-success mb-3"></i>
                                <h5>Tidak Ada Pengajuan Refund</h5>
                                <p>Semua pengajuan refund telah diproses atau belum ada yang mengajukan.</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <div id="modalRefund" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.7); z-index:9999; justify-content:center; align-items:center;">
                    <div style="background:white; width:90%; max-width:480px; margin:100px auto; border-radius:12px; box-shadow:0 10px 30px rgba(0,0,0,0.4); overflow:hidden;">
                        <div style="background:#28a745; color:white; padding:15px 20px; font-weight:bold; display:flex; justify-content:space-between; align-items:center;">
                            <span>Konfirmasi Refund</span>
                            <span style="cursor:pointer; font-size:24px;" onclick="tutupModalKu()">×</span>
                        </div>
                        <div style="padding:25px; text-align:center;">
                            <p style="margin:0 0 15px 0; font-size:16px;">Apakah Anda yakin ingin <strong>menyetujui pengajuan refund</strong> ini?</p>
                            <small style="color:#666;">
                                • Status Refund → <b>Diterima</b><br>
                                • Booking → <b>Batal</b><br>
                                • Pembayaran → <b>Batal</b>
                            </small>
                        </div>
                        <div style="padding:15px 20px; text-align:right; background:#f8f9fa;">
                            <button type="button" onclick="tutupModalKu()" style="margin-right:10px; padding:8px 16px; border:none; background:#6c757d; color:white; border-radius:6px; cursor:pointer;">Batal</button>
                            <button type="button" id="btnSetujuRefund" style="padding:8px 20px; border:none; background:#28a745; color:white; border-radius:6px; cursor:pointer;">Ya, Setujui Refund</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let idYangDipilih = 0;

        function konfirmasiRefund(id) {
            idYangDipilih = id;
            document.getElementById('modalRefund').style.display = 'flex';
        }

        function tutupModalKu() {
            document.getElementById('modalRefund').style.display = 'none';
            idYangDipilih = 0;
        }

        document.getElementById('btnSetujuRefund').onclick = function() {
            if (idYangDipilih > 0) {
                location.href = 'proses_refund.php?id=' + idYangDipilih + '&aksi=setuju';
            }
        };

        document.getElementById('modalRefund').addEventListener('click', function(e) {
            if (e.target === this) tutupModalKu();
        });

        // Function untuk menampilkan detail refund (modal terpisah, karena ini hanya modal konfirmasi)
        function openRefundPopup(data) {
            alert("Detail Refund:\nID: " + data.IDRefund + "\nAlasan: " + data.RefundAlasan);
            // Anda bisa mengganti ini dengan modal detail yang lebih kompleks jika diperlukan.
        }
    </script>

    <script src="../../lib/jquery/jquery.min.js"></script>
    <script src="../../lib/popper.js/popper.min.js"></script> 
    <script src="../../lib/bootstrap/js/bootstrap.min.js"></script> 
    <script src="../../js/azia.js"></script>

    <script>
        $(document).ready(function() {
            // Toggle menu mobile
            $('#azMenuShow').on('click', function(e) {
                e.preventDefault();
                $('.az-header-menu').toggleClass('show');
            });
            $('.az-header-menu .close').on('click', function(e) {
                e.preventDefault();
                $('.az-header-menu').removeClass('show');
            });

            // FIX DROPDOWN PROFILE (FINAL AGGRESSIVE FIX)
            var $dropdownContainer = $('.az-profile-menu');
            var $dropdownMenu = $dropdownContainer.find('.dropdown-menu');

            // 1. Memaksa elemen menu untuk berada di dalam kontainer profil (mengatasi bug Azia)
            if ($dropdownMenu.length && !$dropdownMenu.parent().is($dropdownContainer)) {
                $dropdownMenu.appendTo($dropdownContainer);
            }
            
            // 2. Menginisialisasi Ulang dan Menangani Klik Manual (Event Delegation)
            // Hapus semua inisialisasi Bootstrap yang mungkin gagal sebelumnya, dan tangani secara manual
            
            // Menghapus data-toggle untuk mencegah inisialisasi ganda yang gagal
            $dropdownContainer.find('.dropdown-toggle').removeAttr('data-toggle');

            // Mengganti fungsionalitas Bootstrap dengan JQuery Toggle Class
            $dropdownContainer.on('click', '.dropdown-toggle', function(e) {
                e.preventDefault();
                e.stopPropagation();
                // Menutup dropdown lain jika ada
                $('.az-profile-menu .dropdown-menu').not($(this).siblings('.dropdown-menu')).removeClass('show');

                // Toggle kelas 'show'
                $(this).siblings('.dropdown-menu').toggleClass('show');
            });

            // Menyembunyikan menu saat mengklik di luar
            $(document).on('click', function (e) {
                if (!$dropdownContainer.is(e.target) && $dropdownContainer.has(e.target).length === 0) {
                    $dropdownMenu.removeClass('show');
                }
            });
        });
    </script>
</body>

</html>