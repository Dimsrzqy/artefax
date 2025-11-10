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
if ($conn === null) die("Database gagal terkoneksi!");

$assignment = new EventAssignment($conn);
$allAssignments = $assignment->getAssignmentsByKaryawan($idKaryawan);
$stats = $assignment->getStats($idKaryawan);

// === FILTER: HILANGKAN EVENT YANG SUDAH LEWAT (kecuali status selesai) ===
$now = new DateTime();
$assignments = [];

foreach ($allAssignments as $t) {
    $tanggal = !empty($t['EventTanggal']) ? new DateTime($t['EventTanggal']) : null;
    $waktuSelesai = !empty($t['WaktuSelesai']) && $t['WaktuSelesai'] !== '—' ? $t['WaktuSelesai'] : null;
    $status = $t['EventStatusClean'] ?? trim(strtolower($t['EventStatus']));

    // Buat DateTime lengkap untuk cek "lewat"
    $selesaiFull = null;
    if ($tanggal && $waktuSelesai) {
        $selesaiFull = clone $tanggal;
        $time = DateTime::createFromFormat('H:i:s', $waktuSelesai);
        if (!$time) $time = DateTime::createFromFormat('H:i', $waktuSelesai);
        if ($time) $selesaiFull->setTime($time->format('H'), $time->format('i'), 0);
    }

    // Logika penampilan event:
    if ($status === 'selesai' || !$selesaiFull || $selesaiFull >= $now) {
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

    <link href="../lib/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="../lib/ionicons/css/ionicons.min.css" rel="stylesheet">
    <link href="../lib/typicons.font/typicons.css" rel="stylesheet">
    <link href="../css/azia.css" rel="stylesheet">
    <link href="../css/karyawan.css" rel="stylesheet">
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
                    <div class="col-3"><div class="card card-stat bg-menunggu"><h6>Menunggu</h6><h3><?= $stats['menunggu'] ?></h3></div></div>
                    <div class="col-3"><div class="card card-stat bg-berjalan"><h6>Berjalan</h6><h3><?= $stats['berjalan'] ?></h3></div></div>
                    <div class="col-3"><div class="card card-stat bg-selesai"><h6>Selesai</h6><h3><?= $stats['selesai'] ?></h3></div></div>
                    <div class="col-3"><div class="card card-stat bg-total"><h6>Total</h6><h3><?= $stats['total'] ?></h3></div></div>
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
                        <table class="table-kotak">
                            <thead>
                                <tr>
                                    <th><i class="fas fa-tag"></i> Event</th>
                                    <th><i class="fas fa-map-marker-alt"></i> Lokasi</th>
                                    <th><i class="fas fa-user-tie"></i> Customer</th>
                                    <th><i class="fas fa-calendar-alt"></i> Mulai</th>
                                    <th><i class="fas fa-calendar-check"></i> Selesai</th>
                                    <th><i class="fas fa-clock"></i> Durasi</th>
                                    <th><i class="fas fa-info-circle"></i> Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $today = new DateTime(); $today->setTime(0,0,0);
                                $tomorrow = clone $today; $tomorrow->modify('+1 day');

                                foreach ($assignments as $t):
                                    $tanggal = !empty($t['EventTanggal']) ? new DateTime($t['EventTanggal']) : null;
                                    $status = $t['EventStatusClean'];
                                    $badgeMulai = '';

                                    if ($tanggal) {
                                        if ($tanggal == $today) $badgeMulai = '<span class="badge-today">Hari ini</span>';
                                        elseif ($tanggal == $tomorrow) $badgeMulai = '<span class="badge-tomorrow">Besok</span>';
                                    }
                                ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($t['EventNama']) ?></strong></td>
                                    <td><small><?= htmlspecialchars($t['EventLokasi'] ?? '—') ?></small></td>
                                    <td><?= htmlspecialchars($t['CustomerNama'] ?? 'N/A') ?></td>

                                    <td>
                                        <?= $tanggal ? $tanggal->format('d M Y') : '-' ?><br>
                                        <strong><?= $t['WaktuMulai'] ?></strong>
                                        <?= $badgeMulai ?>
                                    </td>

                                    <td>
                                        <?= $tanggal ? $tanggal->format('d M Y') : '-' ?><br>
                                        <strong><?= $t['WaktuSelesai'] ?></strong>
                                    </td>

                                    <td class="durasi"><?= $t['EventDurasiFormatted'] ?></td>

                                    <td>
                                        <span class="badge-kotak status-<?= $status ?>">
                                            <?= ucfirst($t['EventStatus']) ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="../lib/jquery/jquery.min.js"></script>
    <script src="../lib/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../js/azia.js"></script>
</body>
</html>
