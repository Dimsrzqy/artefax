<?php
session_start();
require_once '../../config/koneksi.php';

// === CEK LOGIN ===
if (!isset($_SESSION['user']) || $_SESSION['user']['UserRole'] !== 'Karyawan') {
    header('Location: ../../View/login.php');
    exit();
}

$userData     = $_SESSION['user'];
$idKaryawan   = $userData['IDUser'];
$namaKaryawan = $userData['UserNama'];

// === KONEKSI DB ===
$db   = new Database();
$conn = $db->getConnection();
if (!$conn) die("Database gagal terkoneksi!");

// === AMBIL DATA EVENT YANG SUDAH SELESAI SAJA ===
$finished = [];
$query = "
    SELECT 
        e.IDEvent,
        e.EventNama,
        e.EventLokasi,
        e.EventTanggal,
        e.EventMulai,
        e.EventSelesai,
        e.EventDurasi,
        u.UserNama AS CustomerNama
    FROM event e
    LEFT JOIN booking b ON b.IDBooking = e.IDBooking
    LEFT JOIN users u ON u.IDUser = b.IDUser
    INNER JOIN event_karyawan ek ON ek.IDEvent = e.IDEvent
    WHERE ek.IDKaryawan = ? 
      AND e.EventStatus = 'Selesai'
    ORDER BY e.EventTanggal DESC, e.EventMulai ASC
";

$stmt = $conn->prepare($query);
if ($stmt) {
    $stmt->bind_param("i", $idKaryawan);
    $stmt->execute();
    $res = $stmt->get_result();

    while ($row = $res->fetch_assoc()) {
        $row['TanggalFormatted']     = date('d M Y', strtotime($row['EventTanggal']));
        $row['WaktuMulai']           = substr($row['EventMulai'], 0, 5);
        $row['WaktuSelesai']         = substr($row['EventSelesai'], 0, 5);
        $row['EventDurasiFormatted'] = $row['EventDurasi'] . ' jam';
        $finished[] = $row;
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Laporan Penugasan Selesai - <?= htmlspecialchars($namaKaryawan) ?> | Artefax</title>

  <link href="../lib/fontawesome-free/css/all.min.css" rel="stylesheet">
  <link href="../css/azia.css" rel="stylesheet">
  <style>
    body { background: #f6f8fb; font-family: "Poppins", sans-serif; }
    .az-header { 
      background: #fff; border-bottom: 1px solid #e4e6eb; padding: 10px 0; 
      position: fixed; width: 100%; top: 0; left: 0; z-index: 1000; 
      box-shadow: 0 2px 6px rgba(0,0,0,0.05);
    }
    .az-header .container { display: flex; justify-content: space-between; align-items: center; }
    .az-logo { font-size: 20px; font-weight: 700; color: #4b4be5; text-decoration: none; }

    /* Navbar aktif */
    .az-header-menu .nav-link { position: relative; color: #1a1a1a; font-weight: 500; padding: 0 15px; }
    .az-header-menu .nav-link::after {
      content: ""; position: absolute; bottom: -6px; left: 50%; width: 0; height: 3px;
      background: #4b4be5; transition: all 0.3s; transform: translateX(-50%);
    }
    .az-header-menu .nav-item.active .nav-link::after,
    .az-header-menu .nav-link:hover::after { width: 70%; }

    .az-content { margin-top: 85px; }
    .az-content-body { display: flex; gap: 30px; }
    
    /* Sidebar */
    .az-content-left {
      background: #fff; border: 1px solid #e4e6eb; border-radius: 10px; padding: 20px;
      flex: 0 0 220px; box-shadow: 0 3px 10px rgba(0,0,0,0.05);
    }
    .az-content-left label { 
      font-weight: 600; font-size: 13px; color: #333; 
      border-bottom: 2px solid #007bff; padding-bottom: 5px; 
      display: block; margin-bottom: 15px; 
    }
    .az-content-left .nav-link { display: block; padding: 8px 0; color: #444; font-size: 14px; }
    .az-content-left .nav-link:hover, .az-content-left .nav-link.active { color: #007bff; font-weight: 600; }

    /* Main Content */
    .table-wrapper {
      background: #fff; border: 1px solid #dee2e6; border-radius: 10px; padding: 25px;
      flex: 1; box-shadow: 0 3px 10px rgba(0,0,0,0.05);
    }
    .table-wrapper h2 { 
      font-size: 1.5rem; color: #007bff; margin-bottom: 10px; 
      display: flex; align-items: center; gap: 10px; 
    }

    /* Table */
    table { width: 100%; border-collapse: collapse; font-size: 14px; margin-top: 15px; }
    thead { background: #007bff; color: #fff; text-transform: uppercase; font-size: 13px; }
    th, td { padding: 12px; text-align: center; border: 1px solid #dee2e6; }
    tbody tr:nth-child(odd) { background: #f8fff9; }
    tbody tr:hover { background: #e8f7ec; }

    .empty-state { text-align: center; padding: 80px 20px; color: #6c757d; }
    .empty-state i { font-size: 4.5rem; color: #c3e6cb; margin-bottom: 15px; }

    @media (max-width: 991px) {
      .az-content-body { flex-direction: column; }
      .az-content-left { width: 100%; }
    }
  </style>
</head>
<body class="az-body">

<!-- HEADER -->
<div class="az-header">
  <div class="container">
    <div class="az-header-left">
      <a href="index.php" class="az-logo">artefax</a>
    </div>
    <div class="az-header-menu">
      <ul class="nav">
        <li class="nav-item"><a href="index.php" class="nav-link">Penugasan</a></li>
        <li class="nav-item"><a href="InputAbsenKaryawan.php" class="nav-link">Absensi</a></li>
        <li class="nav-item active"><a href="LaporanPenugasan.php" class="nav-link">Laporan</a></li>
      </ul>
    </div>
    <div class="az-header-right">
      <img src="../img/faces/face1.jpg" alt="user" style="width:35px;height:35px;border-radius:50%;">
    </div>
  </div>
</div>

<!-- CONTENT -->
<div class="az-content pd-y-20 pd-lg-y-30 pd-xl-y-40">
  <div class="container">
    <div class="az-content-body">

      <!-- SIDEBAR -->
      <div class="az-content-left">
        <label>Menu Karyawan</label>
        <nav class="nav flex-column">
          <a href="LaporanPenugasan.php" class="nav-link active">Laporan Penugasan Selesai</a>
          <a href="LaporanAbsensi.php" class="nav-link">Laporan Absensi</a>
        </nav>
      </div>

      <!-- MAIN CONTENT -->
      <div class="table-wrapper">
        <h2><i class="fas fa-check-circle"></i> Laporan Penugasan Selesai</h2>
        <small class="text-muted d-block mb-4">
          Halo, <strong><?= htmlspecialchars($namaKaryawan) ?></strong> — Berikut daftar event yang sudah kamu selesaikan
        </small>

        <?php if (empty($finished)): ?>
          <div class="empty-state">
            <i class="fas fa-clipboard-check"></i>
            <h4>Belum ada penugasan yang selesai</h4>
            <p>Tetap semangat ya! Event pertama kamu pasti segera datang.</p>
          </div>
        <?php else: ?>
          <table>
            <thead>
              <tr>
                <th>No</th>
                <th>Event</th>
                <th>Lokasi</th>
                <th>Customer</th>
                <th>Tanggal</th>
                <th>Waktu</th>
                <th>Durasi</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($finished as $i => $f): ?>
              <tr>
                <td><?= $i + 1 ?></td>
                <td><strong><?= htmlspecialchars($f['EventNama']) ?></strong></td>
                <td><?= htmlspecialchars($f['EventLokasi'] ?? '—') ?></td>
                <td><?= htmlspecialchars($f['CustomerNama'] ?? '—') ?></td>
                <td><?= $f['TanggalFormatted'] ?></td>
                <td><?= $f['WaktuMulai'] ?> - <?= $f['WaktuSelesai'] ?></td>
                <td><?= $f['EventDurasiFormatted'] ?></td>
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