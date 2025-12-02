<?php
// fetch_booking_detail.php → VERSI FINAL: WARNA BIRU SESUAI MAIN.CSS (#5c99ee)
session_start();
date_default_timezone_set('Asia/Jakarta');

if (!isset($_SESSION['user']) || !isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("<div class='alert alert-danger text-center p-4'>Akses ditolak.</div>");
}

$idBooking = (int)$_GET['id'];
$role      = $_SESSION['user']['UserRole'] ?? '';
$idUser    = $_SESSION['user']['IDUser'] ?? 0;

require_once __DIR__ . '/config/koneksi.php';
$database = new Database();
$conn     = $database->getConnection();
if (!$conn) die("Database error");

// Ambil data booking
$query = "SELECT b.*, u.UserNama, u.UserNoHP
          FROM booking b
          JOIN users u ON b.IDUser = u.IDUser
          WHERE b.IDBooking = ?";
if (strtolower($role) === 'customer') {
    $query .= " AND b.IDUser = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $idBooking, $idUser);
} else {
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $idBooking);
}
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();
if (!$booking) die("<div class='text-center py-5 text-muted'>Booking tidak ditemukan.</div>");

// Ambil item
$itemsQuery = "SELECT bd.BkgDetailJenis,
                 COALESCE(pj.PaketNama, a.AlatNama, 'Item Tidak Diketahui') AS nama_item
               FROM booking_detail bd
               LEFT JOIN paketjasa pj ON bd.IDPaket = pj.IDPaket
               LEFT JOIN alat a ON bd.IDAlat = a.IDAlat
               WHERE bd.IDBooking = ?";
$stmtItems = $conn->prepare($itemsQuery);
$stmtItems->bind_param("i", $idBooking);
$stmtItems->execute();
$itemsResult = $stmtItems->get_result();

$items = [];
while ($row = $itemsResult->fetch_assoc()) $items[] = $row;

// Format
$tglMulai   = date('d F Y H:i', strtotime($booking['BkgTglMulai']));
$totalHarga = number_format((float)$booking['BkgTotalHarga'], 0, ',', '.');
$statusClass = $booking['BkgStatus'] === 'Diterima' ? 'bg-success' :
               ($booking['BkgStatus'] === 'Selesai' ? 'bg-primary' : 'bg-danger');
?>

<div class="container py-4">
    <div class="card shadow-lg border-0 rounded-4 overflow-hidden">
        <!-- Header BIRU #5c99ee -->
        <div class="card-header text-white text-center py-4" 
             style="background: linear-gradient(135deg, #5c99ee, #4c89de);">
            <h4 class="mb-0 fw-bold">Booking #<?= $booking['IDBooking'] ?></h4>
            <small class="opacity-90">Detail Pesanan Anda</small>
        </div>

        <div class="card-body p-4">

            <!-- Tanggal + Status -->
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-4 gap-3">
                <div class="bg-light rounded-4 p-4 text-center text-md-start flex-fill shadow-sm">
                    <small class="text-muted d-block mb-1">
                        Tanggal & Waktu Mulai
                    </small>
                    <h5 class="fw-bold text-primary mb-0"><?= $tglMulai ?> WIB</h5>
                </div>
                <span class="badge <?= $statusClass ?> fs-5 px-4 py-3 rounded-pill fw-bold">
                    <?= $booking['BkgStatus'] ?>
                </span>
            </div>

            <!-- Item yang Dipesan -->
            <h5 class="fw-bold text-primary mb-3">
                Item yang Dipesan
            </h5>
            <div class="list-group list-group-flush mb-4">
                <?php foreach ($items as $item): ?>
                <div class="list-group-item bg-light rounded-3 mb-2 p-3 border-0 shadow-sm">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1 fw-bold"><?= htmlspecialchars($item['nama_item']) ?></h6>
                            <small class="text-muted">
                                <?= $item['BkgDetailJenis'] === 'Paket Jasa' ? 'Paket Jasa' : 'Sewa Alat' ?>
                            </small>
                        </div>
                        <span class="badge bg-primary rounded-pill">
                            <?= $item['BkgDetailJenis'] === 'Paket Jasa' ? 'Paket' : 'Alat' ?>
                        </span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- TOTAL HARGA — FULL BIRU #5c99ee + TEKS PUTIH SUPER JELAS -->
            <div class="p-5 rounded-4 text-center text-white shadow-lg"
                 style="background: linear-gradient(135deg, #5c99ee, #4c89de);">
                <div class="small opacity-90 mb-2">Total Pembayaran</div>
                <h1 class="display-4 fw-bold mb-0">Rp<?= $totalHarga ?></h1>
            </div>

        </div>
    </div>
</div>