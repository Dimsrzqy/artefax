<?php
// File: View/Karyawan/index.php atau dasboardKaryawan/index.php
// PERBAIKAN AGAR SESUAI DENGAN SISTEM LOGIN YANG ADA

// ==========================================
// KONFIGURASI SESSION UNTUK HOSTING
// ==========================================

// Cek apakah session sudah dimulai
if (session_status() === PHP_SESSION_NONE) {
    // Set session path ke folder yang writable di hosting
    $session_path = ini_get('session.save_path');
    if (empty($session_path) || !is_writable($session_path)) {
        $local_session_path = dirname(__FILE__) . '/../../tmp/sessions';
        if (!file_exists($local_session_path)) {
            @mkdir($local_session_path, 0700, true);
        }
        if (is_writable($local_session_path)) {
            session_save_path($local_session_path);
        }
    }
    
    // Konfigurasi session yang kompatibel dengan hosting
    ini_set('session.cookie_path', '/');
    ini_set('session.use_cookies', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_httponly', 1);
    ini_set('session.gc_maxlifetime', 86400);
    ini_set('session.cookie_lifetime', 0);
    
    session_start();
}

// Set timezone sesuai dengan login.php
date_default_timezone_set('Asia/Jakarta');

// ==========================================
// VALIDASI SESSION SESUAI FORMAT LOGIN.PHP
// ==========================================

// Fungsi untuk validasi session
function isValidKaryawanSession() {
    // Cek apakah session user ada
    if (!isset($_SESSION['user'])) {
        return false;
    }
    
    // Cek apakah session user adalah array
    if (!is_array($_SESSION['user'])) {
        return false;
    }
    
    // Cek field yang diperlukan sesuai dengan login.php
    $requiredFields = ['IDUser', 'UserNama', 'UserEmail', 'UserRole'];
    foreach ($requiredFields as $field) {
        if (!isset($_SESSION['user'][$field])) {
            return false;
        }
    }
    
    // Cek role - SESUAI DENGAN LOGIN.PHP (lowercase 'karyawan')
    $role = strtolower(trim($_SESSION['user']['UserRole']));
    if ($role !== 'karyawan') {
        return false;
    }
    
    return true;
}

// Validasi session dan redirect jika tidak valid
if (!isValidKaryawanSession()) {
    // Bersihkan session
    $_SESSION = array();
    
    // Hapus cookie session
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    
    // Destroy session
    session_destroy();
    
    // Redirect ke login - SESUAI STRUKTUR FILE ANDA
    header('Location: ../../View/login.php');
    exit();
}

// ==========================================
// AMBIL DATA USER DENGAN AMAN
// ==========================================

$userData = $_SESSION['user'];
$idKaryawan = (int)$userData['IDUser'];
$namaKaryawan = htmlspecialchars($userData['UserNama']);
$emailKaryawan = htmlspecialchars($userData['UserEmail']);

// ==========================================
// KONEKSI DATABASE
// ==========================================

require_once '../../config/koneksi.php';
require_once '../../class/EventAssignment.php';

$db = new Database();
$conn = $db->getConnection();

if ($conn === null) {
    die("<div style='padding:20px;background:#f8d7da;color:#721c24;border:1px solid #f5c6cb;border-radius:5px;margin:20px;'>
        <h3>Error Koneksi Database</h3>
        <p>Tidak dapat terhubung ke database. Silakan hubungi administrator.</p>
        </div>");
}

$assignment = new EventAssignment($conn);
$assignments = $assignment->getAssignmentsByKaryawan($idKaryawan);
$stats = $assignment->getStats($idKaryawan);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Penugasan - <?= $namaKaryawan ?> | Artefax</title>
    
    <link href="../lib/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="../css/azia.css" rel="stylesheet">
    <link href="../lib/bootstrap/css/bootstrap.min.css" rel="stylesheet"> 
    
    <style>
        /* Fixed Navbar */
        .az-header {
            position: fixed; 
            top: 0;
            width: 100%;
            z-index: 1030;
            background-color: #ffffff; 
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            padding: 10px 0;
        }
        .az-body {
            padding-top: 60px;
        }
        
        /* Ikon Profil & Logout Button Style */
        .az-header .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .az-header-right {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .az-profile-icon {
            font-size: 28px;
            color: #4b4be5 !important; 
            transition: color 0.2s;
        }
        .az-profile-icon:hover {
            color: #3a3ad1;
        }
        .btn-logout-nav {
            padding: 5px 10px;
            font-size: 13px;
            display: flex;
            align-items: center;
        }
        .btn-logout-nav i {
            margin-right: 5px;
        }
        
        /* Hamburger & Mobile Menu Style */
        .az-menu-toggle {
            display: none; 
            font-size: 24px;
            cursor: pointer;
            color: #4b4be5;
            padding: 0 10px;
        }
        .az-mobile-menu {
            position: absolute;
            top: 100%;
            right: 0;
            background-color: #fff;
            border: 1px solid #e4e6eb;
            box-shadow: 0 5px 10px rgba(0, 0, 0, 0.1);
            z-index: 999;
            padding: 10px;
            min-width: 150px;
            display: none;
            flex-direction: column;
        }
        .az-mobile-menu .nav-item {
            padding: 5px 10px;
            border-bottom: 1px solid #eee;
        }
        .az-mobile-menu .nav-item:last-child {
            border-bottom: none;
        }
        @media (max-width: 991px) {
            .az-header-menu {
                display: none;
            }
            .az-menu-toggle {
                display: block;
                order: 3; 
                margin-left: 10px; 
            }
        }
        
        /* Style Link Aktif */
        .az-header-menu .nav-link {
            position: relative;
            color: #1a1a1a;
            font-weight: 500;
            padding: 0 15px;
        }
        .az-header-menu .nav-link::after {
            content: "";
            position: absolute;
            bottom: -6px;
            left: 50%;
            width: 0;
            height: 3px;
            background: #4b4be5;
            transition: all 0.3s;
            transform: translateX(-50%);
        }
        .az-header-menu .nav-item.active::after,
        .az-header-menu .nav-link:hover::after {
            width: 70%;
        }

        /* CARD & STATISTIK */
        .card {
          border: 1px solid #e0e6ed;
          border-radius: 8px;
          box-shadow: none !important; 
          overflow: hidden;
          background: #fff;
        }
        .card-body {
          padding: 20px;
        }

        .card-stat {
          background: #fff;
          border: 1px solid #e0e6ed;
          border-radius: 6px;
          padding: 16px;
          text-align: center;
          box-shadow: none !important; 
        }
        .card-stat h6 {
          margin: 0;
          font-size: 13px;
          font-weight: 600;
          text-transform: uppercase;
          letter-spacing: 0.5px;
          color: #555;
        }
        .card-stat h3 {
          margin: 8px 0 0;
          font-size: 28px;
          font-weight: 700;
        }

        /* Warna stat */
        .bg-menunggu {
          background: #fff3cd;
          border-left: 4px solid #ffc107;
        }
        .bg-berjalan {
          background: #d1ecf1;
          border-left: 4px solid #17a2b8;
        }
        .bg-selesai {
          background: #d4edda;
          border-left: 4px solid #28a745;
        }
        .bg-total {
          background: #d6d8ff;
          border-left: 4px solid #007bff;
        }

        /* TABEL KOTAK */
        .table-kotak {
          width: 100%;
          border-collapse: separate;
          border-spacing: 0;
          font-size: 14.5px;
          background: #fff;
          border: 2px solid #007bff;
          border-radius: 10px;
          overflow: hidden;
          box-shadow: none !important; 
        }

        .table-kotak thead th {
          background: #007bff !important;
          color: #fff !important;
          font-weight: 700;
          padding: 16px 12px;
          text-align: center;
          text-transform: uppercase;
          font-size: 13.5px;
          letter-spacing: 0.8px;
          border: none;
          border-bottom: 3px solid #0056b3;
        }
        .table-kotak thead th i {
          margin-right: 8px;
          font-size: 16px;
          opacity: 0.95;
        }

        .table-kotak tbody td {
          padding: 16px 12px;
          text-align: center;
          vertical-align: middle;
          border-bottom: 1px solid #dee2e6;
          border-left: 1px solid #dee2e6;
          background: #fff;
          transition: background 0.2s ease, font-weight 0.2s ease;
        }
        .table-kotak tbody tr td:first-child {
          border-left: none;
        }
        .table-kotak tbody tr:nth-child(odd) td {
          background: #f8f9fa;
        }
        .table-kotak tbody tr:hover td {
          background: #e3f2fd !important;
          font-weight: 600;
        }
        .table-kotak tbody tr:last-child td {
          border-bottom: none;
        }

        /* BADGE STATUS */
        .badge-kotak {
          padding: 8px 18px;
          border-radius: 6px;
          font-weight: 700;
          font-size: 12.5px;
          text-transform: uppercase;
          min-width: 94px;
          display: inline-block;
          box-shadow: none !important;
          border: 1.5px solid transparent;
        }

        .status-menunggu {
          background: #fff3cd;
          color: #856404;
          border-color: #ffeaa7;
        }
        .status-berjalan {
          background: #d1ecf1;
          color: #0c5460;
          border-color: #74b9ff;
        }
        .status-selesai {
          background: #d4edda;
          color: #155724;
          border-color: #28a745;
        }

        /* Durasi */
        .durasi i {
          color: #007bff;
          margin-right: 6px;
          font-size: 16px;
        }

        /* EMPTY STATE */
        .empty-state {
          text-align: center;
          padding: 70px 20px;
          color: #6c757d;
          background: #f8f9fa;
          border-radius: 10px;
          margin: 20px 0;
          border: 2px dashed #dee2e6;
        }
        .empty-state i {
          font-size: 4.5rem;
          margin-bottom: 18px;
          opacity: 0.5;
          color: #adb5bd;
        }

        /* WAKTU MULAI & SELESAI */
        td[data-label="Mulai"] strong,
        td[data-label="Selesai"] strong {
          font-size: 1.1em;
          color: #007bff;
          font-weight: 700;
        }
        td[data-label="Mulai"] br,
        td[data-label="Selesai"] br {
          display: block;
          margin: 2px 0;
        }

        /* RESPONSIF (MOBILE) */
        @media (max-width: 768px) {
          .table-kotak {
            border: none;
            border-radius: 0;
            box-shadow: none !important;
          }
          .table-kotak thead {
            display: none;
          }
          .table-kotak tbody tr {
            display: block;
            margin-bottom: 22px;
            border: 2px solid #007bff;
            border-radius: 14px;
            overflow: hidden;
            background: #fff;
            box-shadow: none !important;
          }
          .table-kotak tbody td {
            display: block;
            text-align: right;
            padding: 15px 20px;
            border-bottom: 1px dashed #dee2e6;
            border-left: none;
          }
          .table-kotak tbody td::before {
            content: attr(data-label);
            float: left;
            font-weight: 700;
            color: #007bff;
            text-transform: uppercase;
            font-size: 13.5px;
            letter-spacing: 0.7px;
          }
          .table-kotak tbody td:last-child {
            border-bottom: none;
            background: #f5fbff;
          }
          .badge-kotak {
            margin-top: 10px;
            float: right;
          }
          .row-sm.mg-b-30 > div {
            margin-bottom: 15px;
            flex: 0 0 50%;
            max-width: 50%;
          }
          .row-sm.mg-b-30 {
            margin-left: -7.5px;
            margin-right: -7.5px;
          }
          .row-sm.mg-b-30 > div {
            padding-left: 7.5px;
            padding-right: 7.5px;
          }
        }
        .az-content {
            padding-top: 25px; 
            padding-bottom: 25px;
        }
    </style>
</head>

<body class="az-body">
    <div class="az-header">
        <div class="container">
            <div class="az-header-left">
                <a href="index.php" class="az-logo"><span></span> artefax</a>
            </div>
            
            <div class="az-header-menu" id="desktopMenu">
                <ul class="nav">
                    <li class="nav-item active"><a href="index.php" class="nav-link">Penugasan</a></li>
                    <li class="nav-item"><a href="InputAbsenKaryawan.php" class="nav-link">Absensi</a></li>
                </ul>
            </div>
            
            <div class="az-header-right">
                <a href="../../View/profilekaryawan.php" title="Profil Saya" class="d-flex align-items-center">
                    <i class="fas fa-user-circle az-profile-icon"></i>
                </a>
                
                <a href="../../logout.php" class="btn btn-sm btn-danger btn-logout-nav" onclick="return confirm('Yakin ingin logout?')">
                    <i class="fas fa-sign-out-alt"></i> Keluar
                </a>
                
                <i class="fas fa-bars az-menu-toggle" id="azMenuToggle"></i>
            </div>
        </div>
        
        <div class="az-mobile-menu" id="mobileMenu">
            <ul class="nav flex-column">
                <li class="nav-item"><a href="index.php" class="nav-link">Penugasan</a></li>
                <li class="nav-item"><a href="InputAbsenKaryawan.php" class="nav-link">Absensi</a></li>
            </ul>
        </div>
        
    </div>

    <div class="az-content pd-y-20 pd-lg-y-30 pd-xl-y-40">
        <div class="container">
            <div class="az-content-body">
                <h2 class="az-content-title">Penugasan Event</h2>
                <p>Halo <strong><?= $namaKaryawan ?></strong>, berikut daftar penugasan aktif Anda:</p>

                <div class="row row-sm mg-b-30">
                    <div class="col-6 col-md-3">
                        <div class="card card-stat bg-menunggu">
                            <h6>Menunggu</h6>
                            <h3><?= $stats['menunggu'] ?></h3>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card card-stat bg-berjalan">
                            <h6>Berjalan</h6>
                            <h3><?= $stats['berjalan'] ?></h3>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card card-stat bg-selesai">
                            <h6>Selesai</h6>
                            <h3><?= $stats['selesai'] ?></h3>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card card-stat bg-total">
                            <h6>Total</h6>
                            <h3><?= $stats['total'] ?></h3>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <?php if (empty($assignments)): ?>
                        <div class="empty-state">
                            <i class="fas fa-calendar-check"></i>
                            <p class="tx-20 mg-b-5">Tidak ada penugasan aktif saat ini.</p>
                            <small class="text-muted">Event yang sudah selesai otomatis disembunyikan.</small>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table-kotak">
                                <thead>
                                    <tr>
                                        <th>Event</th>
                                        <th>Lokasi</th>
                                        <th>Customer</th>
                                        <th>Mulai</th>
                                        <th>Selesai</th>
                                        <th>Durasi</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($assignments as $t): ?>
                                        <?php
                                        $dateObj = new DateTime($t['EventTanggal']);
                                        $tanggalFormatted = $dateObj->format('d M Y');

                                        $durasiJam = (int)$t['EventDurasi'];
                                        if ($durasiJam >= 24) {
                                            $hari = floor($durasiJam / 24);
                                            $jam  = $durasiJam % 24;
                                            $durasiTxt = $hari . ' hari' . ($jam > 0 ? ' ' . $jam . ' jam' : '');
                                        } else {
                                            $durasiTxt = $durasiJam . ' jam';
                                        }

                                        $status = strtolower(trim($t['EventStatus']));
                                        $statusClean = ($status === 'menunggu') ? 'menunggu' : (($status === 'berjalan') ? 'berjalan' : 'selesai');
                                        ?>
                                        <tr>
                                            <td data-label="Event"><strong><?= htmlspecialchars($t['EventNama']) ?></strong></td>
                                            <td data-label="Lokasi"><?= htmlspecialchars($t['EventLokasi'] ?? '—') ?></td>
                                            <td data-label="Customer"><?= htmlspecialchars($t['CustomerNama'] ?? '—') ?></td>
                                            <td data-label="Mulai"><?= $tanggalFormatted ?><br><strong><?= substr($t['EventMulai'], 0, 5) ?></strong></td>
                                            <td data-label="Selesai"><?= $tanggalFormatted ?><br><strong><?= substr($t['EventSelesai'], 0, 5) ?></strong></td>
                                            <td data-label="Durasi"><?= $durasiTxt ?></td>
                                            <td data-label="Status"><span class="badge-kotak status-<?= $statusClean ?>"><?= ucfirst($t['EventStatus']) ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
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
        document.getElementById('azMenuToggle').addEventListener('click', function() {
            const mobileMenu = document.getElementById('mobileMenu');
            if (mobileMenu.style.display === 'flex') {
                mobileMenu.style.display = 'none';
            } else {
                mobileMenu.style.display = 'flex';
            }
        });

        window.addEventListener('resize', function() {
            if (window.innerWidth > 991) {
                document.querySelector('.az-header-menu').style.cssText = '';
                document.getElementById('mobileMenu').style.display = 'none';
            }
        });
    </script>
</body>

</html>