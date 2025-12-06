<?php
session_start();

// --- START: VERIFIKASI DAN ADAPTASI SESI KRITIS ---
if (isset($_SESSION['user']) && is_array($_SESSION['user'])) {
    $_SESSION['IDUser'] = $_SESSION['user']['IDUser'] ?? null;
    $_SESSION['UserNama'] = $_SESSION['user']['UserNama'] ?? 'Guest User';
    $_SESSION['UserRole'] = $_SESSION['user']['UserRole'] ?? 'Unknown Role';
}

// VERIFIKASI LOGIN
if (!isset($_SESSION['IDUser']) || empty($_SESSION['IDUser'])) {
    header("Location: ../../../view/login.php"); 
    exit;
}
// --- END: VERIFIKASI DAN ADAPTASI SESI KRITIS ---

require_once __DIR__ . "/../../../config/koneksi.php";
require_once __DIR__ . "/../../../class/pembayaran.php";
require_once __DIR__ . "/../../../class/users.php";

// --- DATA USER LOGIN ---
$loggedInUser = [
    'UserNama' => $_SESSION['UserNama'] ?? 'Guest User', 
    'UserRole' => $_SESSION['UserRole'] ?? 'Unknown Role', 
];
$defaultProfileImage = '../../img/faces/face1.jpg';

$db = new Database();
$conn = $db->getConnection();

$pembayaran = new Pembayaran($conn);
$user = new User($conn);

$ClearPayments = $pembayaran->readLunasDP();
$detailPembayaran = $pembayaran->readJoinFull();

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
    <title>Pelunasan Pembayaran - Admin ArtefaxID</title>

    <link href="../../lib/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="../../lib/ionicons/css/ionicons.min.css" rel="stylesheet">
    <link href="../../lib/typicons.font/typicons.css" rel="stylesheet">
    <link href="../../css/azia.css" rel="stylesheet">
    
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

        /* --- CARD PAYMENT --- */
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
        .card-header {
            background: #1DA1F2;
            color: white;
            padding: 12px 16px;
            font-weight: 600;
            font-size: 15px;
        }
        .card-body {
            padding: 16px;
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
            border-radius: 4px;
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

        /* --- BUTTON ACTION --- */
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
            cursor: pointer;
        }
        .btn-detail {
            background: #17a2b8;
        }
        .btn-setuju {
            background: #28a745;
        }
        .btn-tolak {
            background: #dc3545;
        }

        /* --- ALERT --- */
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

        /* --- EMPTY STATE --- */
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

        /* --- MODAL --- */
        #modalPelunasan {
            pointer-events: none;
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            z-index: 9999;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        .modal-dialog {
            background: white;
            border-radius: 12px;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            animation: fadeIn 0.3s ease;
            pointer-events: auto;
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
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: scale(0.9);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }
    </style>
</head>

<body class="az-body">
    <div class="az-header">
        <div class="container">
            <div class="az-header-left">
                <a href="../../template/index.html" class="az-logo"><span></span> Artefax</a>
                <a href="" id="azMenuShow" class="az-header-menu-icon d-lg-none"><span></span></a>
            </div>
            <div class="az-header-menu">
                <div class="az-header-menu-header">
                    <a href="index.html" class="az-logo"><span></span> Artefax</a>
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
                        <a href="../../form-pembayaran/daftar_pembayaran.php" class="nav-link"><i class="typcn typcn-puzzle-outline"></i>Pembayaran</a>
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
                                <div class="az-img-user"><img src="../../img/faces/face2.jpg" alt=""></div>
                                <div class="media-body">
                                    <p>Congratulate <strong>Socrates Itumay</strong> for work anniversaries</p>
                                    <span>Mar 15 12:32pm</span>
                                </div>
                            </div>
                            <div class="media new">
                                <div class="az-img-user online"><img src="../../img/faces/face3.jpg" alt=""></div>
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
                    <a href="" class="az-img-user"><img src="<?= $defaultProfileImage ?>" alt=""></a>
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
            <div class="az-content-left az-content-left-components d-lg-block d-none">
                <div class="component-item">
                    <label>Pembayaran</label>
                    <nav class="nav flex-column">
                        <a href="../daftar_pembayaran.php" class="nav-link">Daftar Pembayaran</a>
                        <a href="../pembayaran/konfirmasi_pembayaran.php" class="nav-link">Konfirmasi Pembayaran</a>
                    </nav>
                    <label>Pelunasan DP</label>
                    <nav class="nav flex-column">
                        <a href="../dp/pelunasan_pembayaran.php" class="nav-link active">Pelunasan Pembayaran</a>
                    </nav>
                    <label>Refund</label>
                    <nav class="nav flex-column">
                        <a href="../refund/pengajuan_refund.php" class="nav-link">Pengajuan Refund</a>
                    </nav>
                </div>
            </div>

            <div class="az-content-body pd-lg-l-40 d-flex flex-column">
                <div class="az-content-breadcrumb">
                    <span>Pembayaran</span>
                    <span>Pelunasan Pembayaran</span>
                </div>
                <h2 class="az-content-title">Pelunasan Pembayaran</h2>
                <p class="mg-b-20">Verifikasi pelunasan pembayaran DP dari pelanggan.</p>

                <?php if ($success): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <div class="row">
                    <?php if ($ClearPayments && count($ClearPayments) > 0): ?>
                        <?php foreach ($ClearPayments as $index => $p): 
                            $pf = $detailPembayaran[$index] ?? $p;
                            $customer = $user->getUserByID($p['IDUser']);
                            $namaCustomer = $customer['UserNama'] ?? 'Unknown';
                            $tglKirim = date('d M Y, H:i', strtotime($p['CreatedAt'])) . ' WIB';
                            $tglBooking = date('d M Y', strtotime($p['BkgTglMulai'] ?? $p['CreatedAt']));
                        ?>
                            <div class="col-md-6 col-lg-4 mb-4" data-id="<?= $p['IDPembayaran'] ?>">
                                <div class="card-payment shadow-sm h-100">
                                    <div class="card-header">
                                        BOOK#<?= str_pad($p['IDBooking'], 6, '0', STR_PAD_LEFT) ?>
                                    </div>
                                    <div class="card-body">
                                        <div class="info-row">
                                            <span class="info-label">Nama Pelanggan</span>
                                            <span class="info-value"><?= htmlspecialchars($namaCustomer) ?></span>
                                        </div>
                                        <div class="info-row">
                                            <span class="info-label">Tanggal Booking</span>
                                            <span class="info-value"><?= $tglBooking ?></span>
                                        </div>
                                        <div class="info-row">
                                            <span class="info-label">Total Tagihan</span>
                                            <span class="info-value">Rp <?= number_format($p['BkgTotalHarga'], 0, ',', '.') ?></span>
                                        </div>
                                        <div class="info-row">
                                            <span class="info-label">Jumlah Transfer</span>
                                            <span class="info-value">Rp <?= number_format($p['PbrJumlah'], 0, ',', '.') ?></span>
                                        </div>
                                        <div class="info-row">
                                            <span class="info-label">Bank Tujuan</span>
                                            <span class="info-value"><?= htmlspecialchars($p['PbrMetode']) ?></span>
                                        </div>
                                        <div class="info-row">
                                            <span class="info-label">Waktu Kirim</span>
                                            <span class="info-value"><?= $tglKirim ?></span>
                                        </div>
                                        <div class="info-row">
                                            <span class="info-label">Status</span>
                                            <span class="info-value"><span class="badge-pending">Menunggu Konfirmasi</span></span>
                                        </div>
                                    </div>
                                    <div class="card-footer">
                                        <button class="btn-action btn-detail" 
                                                onclick='openDetailPopup(<?= json_encode($pf, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                                            <i class="fas fa-eye"></i> Detail
                                        </button>
                                        <div>
                                            <button class="btn-action btn-setuju" onclick="konfirmasiAksi(<?= $p['IDPembayaran'] ?>, 'setuju')">
                                                <i class="fas fa-check"></i> Setuju
                                            </button>
                                            <button class="btn-action btn-tolak" onclick="konfirmasiAksi(<?= $p['IDPembayaran'] ?>, 'tolak')">
                                                <i class="fas fa-times"></i> Tolak
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="col-12">
                            <div class="empty-state">
                                <i class="fas fa-check-circle"></i>
                                <h5>Tidak Ada Pelunasan Menunggu</h5>
                                <p>Semua pelunasan telah dikonfirmasi.</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div id="modalPelunasan" class="modal-overlay">
        <div class="modal-dialog">
            <div class="modal-header" id="modalHeader">
                <h5 id="modalTitle">Konfirmasi</h5>
                <button type="button" class="close-btn" onclick="closeModal()">×</button>
            </div>
            <div class="modal-body">
                <p id="modalMessage"></p>
            </div>
            <div class="modal-footer">
                <button type="button" id="btnKonfirmasi" class="btn btn-primary">Ya, Lanjutkan</button>
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Batal</button>
            </div>
        </div>
    </div>

    <?php require_once __DIR__ . "/../detail_pembayaran.php"; ?>

    <script src="../../lib/jquery/jquery.min.js"></script>
    <script src="../../lib/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../js/azia.js"></script>
    
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
        });

        const modalOverlay = document.getElementById('modalPelunasan');
        let currentId = null;

        function konfirmasiAksi(id, aksi) {
            currentId = id;

            const header = document.getElementById('modalHeader');
            const title = document.getElementById('modalTitle');
            const message = document.getElementById('modalMessage');
            const btnKonfirmasi = document.getElementById('btnKonfirmasi');

            if (aksi === 'setuju') {
                header.className = 'modal-header setuju';
                title.textContent = 'Setujui Pelunasan';
                message.innerHTML = 'Setujui pelunasan ini?<br>Status pembayaran menjadi <strong>Lunas</strong>.';
                btnKonfirmasi.textContent = 'Ya, Setujui';
            } else {
                header.className = 'modal-header tolak';
                title.textContent = 'Tolak Pelunasan';
                message.innerHTML = 'Tolak pelunasan ini?<br>Status pembayaran menjadi <strong>Gagal</strong>.';
                btnKonfirmasi.textContent = 'Ya, Tolak';
            }

            btnKonfirmasi.onclick = () => prosesPelunasan(aksi);
            modalOverlay.style.display = 'flex';
        }

        function prosesPelunasan(aksi) {
            if (!currentId) return;
            window.location.href = `proses_pelunasan.php?id=${currentId}&aksi=${aksi}`;
        }

        function closeModal() {
            modalOverlay.style.display = 'none';
            currentId = null;
        }

        modalOverlay.addEventListener('click', function(e) {
            if (e.target === modalOverlay) {
                closeModal();
            }
        });
    </script>
</body>
</html>