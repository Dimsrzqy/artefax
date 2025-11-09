<?php
session_start();

// === CEK LOGIN ===
if (!isset($_SESSION['user']) || $_SESSION['user']['UserRole'] !== 'Karyawan') {
    header('Location: ../../View/login.php');
    exit();
}

$userData = $_SESSION['user'];
$idKaryawan = $userData['IDUser'];
$namaKaryawan = $userData['UserNama'];

// === KONEKSI DB ===
require_once '../../config/koneksi.php';
require_once '../../class/EventAssignment.php';

$db = new Database();
$conn = $db->getConnection();

if ($conn === null) {
    die("Database gagal terkoneksi!");
}

$assignment = new EventAssignment($conn);
$allAssignments = $assignment->getAssignmentsByKaryawan($idKaryawan);
$stats = $assignment->getStats($idKaryawan);

// === FILTER: HILANGKAN EVENT YANG SUDAH LEWAT (KECUALI SELESAI) ===
$now = new DateTime(); // Waktu saat ini
$assignments = [];

foreach ($allAssignments as $t) {
    $selesai = !empty($t['EventSelesai']) ? new DateTime($t['EventSelesai']) : null;
    $status = strtolower($t['EventStatus']);

    // Tampilkan jika:
    // 1. Status = Selesai (riwayat tetap tampil)
    // 2. Atau EventSelesai belum lewat
    if ($status === 'selesai' || ($selesai && $selesai >= $now)) {
        $assignments[] = $t;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Penugasan - <?= htmlspecialchars($namaKaryawan) ?> | Artefax</title>

    <!-- Font Awesome -->
    <link href="../lib/fontawesome-free/css/all.min.css" rel="stylesheet">
    
    <!-- Azia Core CSS -->
    <link href="../lib/ionicons/css/ionicons.min.css" rel="stylesheet">
    <link href="../lib/typicons.font/typicons.css" rel="stylesheet">
    <link href="../css/azia.css" rel="stylesheet">
    
    <!-- CSS Karyawan -->
    <link href="../css/karyawan.css" rel="stylesheet">

    <style>
        .badge-today { 
            background: #ff6b6b; color: white; font-size: 0.65em; padding: 3px 7px; border-radius: 4px; margin-left: 6px; 
        }
        .badge-tomorrow { 
            background: #4ecdc4; color: white; font-size: 0.65em; padding: 3px 7px; border-radius: 4px; margin-left: 6px; 
        }
        .badge-solid { 
            padding: 6px 12px; border-radius: 8px; font-weight: 600; font-size: 0.85em; 
        }
        .status-menunggu { background: #ffc107; color: #212529; }
        .status-berjalan { background: #17a2b8; color: white; }
        .status-selesai { background: #28a745; color: white; }
        .durasi { font-family: 'Courier New', monospace; font-weight: 600; }
        .empty-state { text-align: center; padding: 50px 20px; color: #6c757d; }
        .empty-state i { font-size: 60px; color: #dee2e6; margin-bottom: 16px; }
        .table-kotak th { background: #f8f9fa; font-weight: 600; }
        .table-kotak td { vertical-align: middle; }
        .text-muted { color: #6c757d !important; font-style: italic; }
        @media (max-width: 768px) {
            .card-stat h6 { font-size: 0.9rem; }
            .card-stat h3 { font-size: 1.5rem; }
        }
    </style>
</head>
<body class="az-body">

    <!-- HEADER -->
    <div class="az-header">
        <div class="container">
            <div class="az-header-left">
                <a href="index.php" class="az-logo"><span></span> artefax</a>
            </div>
            <div class="az-header-menu">
                <ul class="nav">
                    <li class="nav-item active"><a href="index.php" class="nav-link">Penugasan</a></li>
                    <li class="nav-item"><a href="../form-karyawan.php" class="nav-link">Absensi</a></li>
                </ul>
            </div>
            <div class="az-header-right">
                <div class="dropdown az-profile-menu">
                    <a href="" class="az-img-user"><img src="../img/faces/face1.jpg" alt=""></a>
                    <div class="dropdown-menu">
                        <div class="az-header-profile">
                            <div class="az-img-user"><img src="../img/faces/face1.jpg" alt=""></div>
                            <h6><?= htmlspecialchars($namaKaryawan) ?></h6>
                            <span>Karyawan</span>
                        </div>
                        <a href="../../View/logout.php" class="dropdown-item"><i class="typcn typcn-power"></i> Keluar</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- CONTENT -->
    <div class="az-content pd-y-20 pd-lg-y-30 pd-xl-y-40">
        <div class="container">
            <div class="az-content-body">
                <h2 class="az-content-title">Penugasan Event</h2>
                <p>Halo <strong><?= htmlspecialchars($namaKaryawan) ?></strong>, berikut daftar penugasan aktif Anda:</p>

                <!-- STATISTIK -->
                <div class="row row-sm mg-b-30">
                    <div class="col-3">
                        <div class="card card-stat bg-warning text-dark">
                            <h6>Menunggu</h6>
                            <h3><?= $stats['menunggu'] ?></h3>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="card card-stat bg-info">
                            <h6>Berjalan</h6>
                            <h3><?= $stats['berjalan'] ?></h3>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="card card-stat bg-success">
                            <h6>Selesai</h6>
                            <h3><?= $stats['selesai'] ?></h3>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="card card-stat bg-primary">
                            <h6>Total</h6>
                            <h3><?= $stats['total'] ?></h3>
                        </div>
                    </div>
                </div>

                <!-- TABEL PENUGASAN -->
                <div class="card">
                    <?php if (empty($assignments)): ?>
                        <div class="empty-state">
                            <i class="fas fa-calendar-check"></i>
                            <p class="tx-20 mg-b-5">Tidak ada penugasan aktif saat ini.</p>
                            <small class="text-muted">Event yang sudah lewat otomatis disembunyikan.</small>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-kotak table-hover">
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
                                    <?php 
                                    $today    = new DateTime();
                                    $today->setTime(0, 0, 0);
                                    $tomorrow = clone $today;
                                    $tomorrow->modify('+1 day');

                                    foreach ($assignments as $t): 
                                        $mulai   = !empty($t['EventMulai'])   ? new DateTime($t['EventMulai'])   : null;
                                        $selesai = !empty($t['EventSelesai']) ? new DateTime($t['EventSelesai']) : null;

                                        // Badge: Hari ini / Besok (berdasarkan EventMulai)
                                        $badgeMulai = '';
                                        if ($mulai) {
                                            $mulaiDate = clone $mulai;
                                            $mulaiDate->setTime(0, 0, 0);
                                            if ($mulaiDate == $today) {
                                                $badgeMulai = '<span class="badge-today">Hari ini</span>';
                                            } elseif ($mulaiDate == $tomorrow) {
                                                $badgeMulai = '<span class="badge-tomorrow">Besok</span>';
                                            }
                                        }

                                        // Durasi
                                        $durasi = '—';
                                        if ($mulai && $selesai) {
                                            $interval = $mulai->diff($selesai);
                                            $jam = $interval->h;
                                            $menit = $interval->i;
                                            $durasi = $jam . ' jam';
                                            if ($menit > 0) $durasi .= ' ' . $menit . ' menit';
                                        }

                                        // Status
                                        $status = strtolower($t['EventStatus']);
                                        $icon = match($status) {
                                            'menunggu' => 'hourglass-half',
                                            'berjalan' => 'running',
                                            default    => 'check-circle'
                                        };
                                    ?>
                                    <tr>
                                        <td data-label="Event">
                                            <strong><?= htmlspecialchars($t['EventNama']) ?></strong>
                                        </td>
                                        <td data-label="Lokasi">
                                            <small><?= htmlspecialchars($t['EventLokasi'] ?? '—') ?></small>
                                        </td>
                                        <td data-label="Customer">
                                            <?= htmlspecialchars($t['CustomerNama'] ?? 'N/A') ?>
                                        </td>
                                        <td data-label="Mulai">
                                            <?= $mulai ? $mulai->format('d M Y H:i') : '-' ?>
                                            <?= $badgeMulai ?>
                                        </td>
                                        <td data-label="Selesai">
                                            <?= $selesai ? $selesai->format('d M Y H:i') : '-' ?>
                                        </td>
                                        <td data-label="Durasi" class="durasi">
                                            <?= $durasi ?>
                                        </td>
                                        <td data-label="Status">
                                            <span class="badge-solid status-<?= $status ?>">
                                                <?= ucfirst($t['EventStatus']) ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Info Kecil -->
                <div class="mg-t-20">
                    <small class="text-muted">
                        Event yang sudah lewat (kecuali status <strong>Selesai</strong>) otomatis disembunyikan.
                    </small>
                </div>

            </div><!-- az-content-body -->
        </div><!-- container -->
    </div><!-- az-content -->

    <!-- Scripts -->
    <script src="../lib/jquery/jquery.min.js"></script>
    <script src="../lib/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../js/azia.js"></script>
</body>
</html>