<?php
session_start();
require_once '../../config/koneksi.php';
require_once '../../class/EventAssignment.php';

// === CEK LOGIN ===
if (!isset($_SESSION['user']) || $_SESSION['user']['UserRole'] !== 'Karyawan') {
    header('Location: ../../View/login.php');
    exit();
}

$userData = $_SESSION['user'];
$idKaryawan = $userData['IDUser'];
$namaKaryawan = $userData['UserNama'];

// === KONEKSI DB ===
$db = new Database();
$conn = $db->getConnection();
if (!$conn) die("Database gagal terkoneksi!");

// === AMBIL DATA ===
$assignment = new EventAssignment($conn);

// 1. Event BELUM SELESAI → pakai fungsi class

// 2. Event SUDAH SELESAI → QUERY LANGSUNG (BIAR TIDAK BENTROK)
$finished = [];
$query_selesai = "
    SELECT 
        e.IDEvent, e.EventNama, e.EventLokasi, e.IDBooking, e.IDKaryawan,
        e.EventTanggal, e.EventDurasi, e.EventMulai, e.EventSelesai,
        e.EventStatus, e.CreatedAt, e.UpdatedAt,
        u.UserNama AS CustomerNama
    FROM event e
    LEFT JOIN booking b ON b.IDBooking = e.IDBooking
    LEFT JOIN users u ON u.IDUser = b.IDUser
    WHERE e.IDKaryawan = ?
      AND TRIM(LOWER(e.EventStatus)) = 'selesai'
    ORDER BY e.EventTanggal DESC, e.EventMulai ASC
";

$stmt = $conn->prepare($query_selesai);
if ($stmt) {
    $stmt->bind_param("i", $idKaryawan);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $row['TanggalFormatted'] = $row['EventTanggal'] ? date('d M Y', strtotime($row['EventTanggal'])) : '—';
        $row['WaktuMulai'] = $row['EventMulai'] ? date('H:i', strtotime($row['EventMulai'])) : '—';
        $row['WaktuSelesai'] = $row['EventSelesai'] ? date('H:i', strtotime($row['EventSelesai'])) : '—';
        $row['EventDurasiFormatted'] = ((int) $row['EventDurasi']) . " jam";
        $finished[] = $row;
    }
    $stmt->close();
} else {
    error_log("Prepare failed (query selesai): " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Laporan Penugasan - <?= htmlspecialchars($namaKaryawan) ?> | Artefax</title>

  <link href="../lib/fontawesome-free/css/all.min.css" rel="stylesheet">
  <link href="../css/azia.css" rel="stylesheet">
  <style>
    body {
      background: #f6f8fb;
      font-family: "Poppins", sans-serif;
      color: #1a1a1a;
    }

    /* ================= HEADER FIX ================= */
    .az-header {
      background: #fff;
      border-bottom: 1px solid #e4e6eb;
      padding: 10px 0;
      position: fixed;
      width: 100%;
      top: 0;
      left: 0;
      z-index: 1000;
      box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
    }

    .az-header .container {
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .az-logo {
      font-size: 20px;
      font-weight: 700;
      color: #4b4be5;
      text-decoration: none;
    }

    /* ================= NAVBAR ================= */
/* ================= NAVBAR ACTIVE FIX ================= */
.az-header-menu .nav-link {
  position: relative;
  color: #1a1a1a;
  font-weight: 500;
  font-size: 15px;
  padding: 0; /* hilangkan padding bawah agar rapat */
  margin-bottom: 4px; /* beri sedikit ruang agar teks tidak menempel garis */
  transition: color 0.3s ease;
}

.az-header-menu .nav-link:hover {
  color: #4b4be5;
}

/* Garis aktif di bawah teks */
.az-header-menu .nav-link::after {
  content: "";
  position: absolute;
  bottom: -2px; /* rapat ke teks */
  left: 50%;
  width: 0%;
  height: 2px;
  background-color: #4b4be5;
  transition: all 0.25s ease-in-out;
  transform: translateX(-50%);
  border-radius: 2px;
}

.az-header-menu .nav-item.active::after,
.az-header-menu .nav-link:hover::after {
  width: 100%;
}


    .az-header-menu .nav-item.active::after,
    .az-header-menu .nav-link:hover::after {
      width: 100%;
    }

    .az-header-right img {
      width: 35px;
      height: 35px;
      border-radius: 50%;
    }

    /* ================= CONTENT AREA ================= */
    .az-content {
      margin-top: 85px; /* beri ruang di bawah navbar fixed */
    }

    .az-content-body {
      display: flex;
      gap: 30px;
    }

    /* ================= SIDEBAR ================= */
    .az-content-left {
      background: #fff;
      border: 1px solid #e4e6eb;
      border-radius: 10px;
      padding: 20px;
      flex: 0 0 220px;
      height: fit-content;
      box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
    }

    .az-content-left label {
      font-weight: 600;
      font-size: 13px;
      color: #333;
      display: block;
      margin-bottom: 10px;
      border-bottom: 2px solid #007bff;
      padding-bottom: 5px;
    }

    .az-content-left .nav-link {
      display: block;
      color: #444;
      font-size: 14px;
      margin: 8px 0;
      transition: all 0.3s;
    }

    .az-content-left .nav-link:hover,
    .az-content-left .nav-link.active {
      color: #007bff;
      font-weight: 600;
      padding-left: 4px;
    }

    /* ================= TABLE WRAPPER ================= */
    .table-wrapper {
      background: #fff;
      border: 1px solid #dee2e6;
      border-radius: 10px;
      box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
      padding: 25px;
      flex: 1;
    }

    .table-wrapper h2 {
      font-size: 1.4rem;
      font-weight: 600;
      color: #007bff;
      margin-bottom: 20px;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    /* ================= TABLE ================= */
    table {
      width: 100%;
      border-collapse: collapse;
      font-size: 14px;
      border-radius: 10px;
      overflow: hidden;
    }

    thead {
      background: #007bff;
      color: #fff;
      text-transform: uppercase;
      font-size: 13px;
      letter-spacing: 0.5px;
    }

    th, td {
      text-align: center;
      padding: 10px;
      border: 1px solid #dee2e6;
    }

    tbody tr:nth-child(odd) {
      background: #f8f9fa;
    }

    tbody tr:hover {
      background: #eaf3ff;
      transition: 0.3s;
    }

    /* ================= BADGES ================= */
    .badge-status {
      padding: 5px 12px;
      border-radius: 15px;
      font-weight: 600;
      font-size: 12px;
      text-transform: uppercase;
    }
    .badge-selesai { background: #d4edda; color: #155724; border: 1px solid #28a745; }
    .badge-menunggu { background: #fff3cd; color: #856404; border: 1px solid #ffc107; }
    .badge-berjalan { background: #d1ecf1; color: #0c5460; border: 1px solid #17a2b8; }

    /* ================= EMPTY STATE ================= */
    .empty-state {
      text-align: center;
      padding: 60px 10px;
      color: #6c757d;
    }

    .empty-state i {
      font-size: 3rem;
      margin-bottom: 10px;
      color: #adb5bd;
    }

    /* ================= RESPONSIVE ================= */
    @media (max-width: 991px) {
      .az-content-body {
        flex-direction: column;
      }
      .az-content-left {
        width: 100%;
      }
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
      <img src="../img/faces/face1.jpg" alt="user">
    </div>
  </div>
</div>

<!-- CONTENT -->
<div class="az-content pd-y-20 pd-lg-y-30 pd-xl-y-40">
  <div class="container">
    <div class="az-content-body">
      <!-- SIDEBAR -->
      <div class="az-content-left az-content-left-components">
        <label>Menu Karyawan</label>
        <nav class="nav flex-column">
          <a href="LaporanPenugasan.php" class="nav-link active">Laporan Penugasan</a>
          <a href="LaporanAbsensi.php" class="nav-link">Laporan Absensi</a>
        </nav>
      </div>

      <!-- TABEL LAPORAN -->
      <div class="table-wrapper">
        <h2><i class="fas fa-clipboard-list"></i> Laporan Penugasan</h2>
        <?php if (empty($finished)): ?>
          <div class="empty-state">
            <i class="fas fa-clipboard-check"></i>
            <p>Belum ada penugasan yang selesai.</p>
          </div>
        <?php else: ?>
          <table>
            <thead>
              <tr>
                <th>Event</th>
                <th>Lokasi</th>
                <th>Customer</th>
                <th>Tanggal</th>
                <th>Mulai</th>
                <th>Selesai</th>
                <th>Durasi</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($finished as $t): ?>
              <tr>
                <td><strong><?= htmlspecialchars($t['EventNama']) ?></strong></td>
                <td><?= htmlspecialchars($t['EventLokasi'] ?? '—') ?></td>
                <td><?= htmlspecialchars($t['CustomerNama'] ?? '—') ?></td>
                <td><?= htmlspecialchars($t['TanggalFormatted']) ?></td>
                <td><?= htmlspecialchars($t['WaktuMulai']) ?></td>
                <td><?= htmlspecialchars($t['WaktuSelesai']) ?></td>
                <td><?= htmlspecialchars($t['EventDurasiFormatted']) ?></td>
                <td><span class="badge-status badge-selesai">Selesai</span></td>
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


