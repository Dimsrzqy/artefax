<?php
// File: RiwayatBooking.php → FINAL CODE (Dengan Modal AJAX)
session_start();
date_default_timezone_set('Asia/Jakarta');

if (!isset($_SESSION['user'])) {
    header("Location: view/login.php");
    exit;
}

require_once __DIR__ . '/config/koneksi.php';
// Diasumsikan class User berada di __DIR__ . '/class/User.php'
// require_once __DIR__ . '/class/Booking.php'; 

$database = new Database();
$conn = $database->getConnection();
if (!$conn) die("Database error");

$role   = $_SESSION['user']['UserRole'] ?? '';
$idUser = $_SESSION['user']['IDUser'] ?? 0;

$statusFilter = ['Diterima', 'Selesai', 'Batal'];
$bookings = [];

// Query untuk Riwayat Booking (Menggunakan u.UserNoHP sesuai class User)
$statuses = implode("','", array_map([$conn, 'real_escape_string'], $statusFilter));
$query = "SELECT 
            b.IDBooking,
            b.BkgTglMulai,
            b.BkgTotalHarga,
            b.BkgStatus,
            u.UserNama,
            u.UserNoHP, -- Mengambil UserNoHP untuk tampilan detail di RiwayatBooking (opsional)
            COALESCE(pj.PaketNama, a.AlatNama, 'Item Tidak Diketahui') AS ItemNama
          FROM booking b
          LEFT JOIN users u ON b.IDUser = u.IDUser
          LEFT JOIN booking_detail bd ON b.IDBooking = bd.IDBooking
          LEFT JOIN paketjasa pj ON bd.IDPaket = pj.IDPaket
          LEFT JOIN alat a ON bd.IDAlat = a.IDAlat
          WHERE b.BkgStatus IN ('$statuses')";

if (strtolower($role) === 'customer') {
    $query .= " AND b.IDUser = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $idUser);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    // Untuk Admin/Staff, tampilkan semua
    $query .= " ORDER BY b.BkgTglMulai DESC";
    $result = $conn->query($query);
}

while ($row = $result->fetch_assoc()) {
    $bookings[] = $row;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Booking - Artefax</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Questrial&family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    
    <style>
        /* --- SKEMA WARNA DEVING/ARTEFAX --- */
        :root { 
            --primary-blue: #5c99ee; 
            --soft-blue: #f4f7fc; 
            --dark-text: #344761; 
            --light-text: #535d6b; 
            
            --background-color: var(--soft-blue);
            --default-color: var(--light-text);
            --heading-color: var(--dark-text);
            --accent-color: var(--primary-blue);
            --surface-color: #ffffff;
            --contrast-color: #ffffff;

            /* Warna Status */
            --status-diterima-bg: #4caf50;
            --status-selesai-bg: var(--accent-color);
            --status-batal-bg: #dc3545;
        }
        
        body { 
            background: var(--background-color); 
            font-family: 'Roboto', sans-serif;
            color: var(--default-color);
        }
        
        /* NAVBAR */
        .navbar { background-color: var(--accent-color) !important; }

        /* --- CARD STYLING ELEGANT --- */
        .card-horizontal {
            border: 1px solid color-mix(in srgb, var(--default-color), transparent 90%); 
            border-radius: 12px;
            overflow: hidden;
            background: var(--surface-color); 
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08); 
            transition: all 0.4s ease;
            margin-bottom: 2rem; 
        }
        .card-horizontal:hover {
            transform: translateY(-5px); 
            box-shadow: 0 15px 35px color-mix(in srgb, var(--accent-color), transparent 70%);
            border-color: color-mix(in srgb, var(--accent-color), transparent 50%);
            cursor: pointer;
        }
        
        /* Card Header */
        .card-header-h {
            background: transparent;
            color: var(--heading-color); 
            padding: 1.2rem 1.5rem 0.8rem 1.5rem;
            border-bottom: 2px solid color-mix(in srgb, var(--accent-color), transparent 80%);
        }
        .card-header-h small { color: var(--default-color); }
        
        /* Harga */
        .price-gede {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--accent-color); 
            line-height: 1.2;
            margin-bottom: 0.5rem !important;
        }
        
        /* Status Badges */
        .status-badge {
            font-weight: 600; border-radius: 5px; padding: 0.5em 1.2em;
            color: var(--contrast-color) !important; text-transform: uppercase; font-size: 0.85rem;
        }
        .status-diterima { background-color: var(--status-diterima-bg); }
        .status-selesai   { background-color: var(--status-selesai-bg); }
        .status-batal     { background-color: var(--status-batal-bg); }

        /* Detail Area (Kanan) */
        .detail-area {
            background-color: var(--soft-blue); 
            border-left: 1px solid color-mix(in srgb, var(--default-color), transparent 90%);
            border-radius: 0 12px 12px 0;
        }
        
        /* Tombol Detail */
        .btn-detail {
            background-color: var(--accent-color); color: var(--contrast-color); border: none;
            padding: 12px 30px; border-radius: 8px; font-weight: 500; transition: all 0.3s ease;
        }
        .btn-detail:hover {
            background-color: color-mix(in srgb, var(--accent-color), black 10%);
            transform: translateY(-2px);
        }

        /* --- STYLING KHUSUS MODAL --- */
        .modal-content {
            border-radius: 15px; border: none; box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        .highlight-info {
            background-color: var(--soft-blue); border-radius: 8px; padding: 15px;
            margin-bottom: 15px; border-left: 4px solid var(--accent-color);
        }
        .highlight-info .info-label {
            font-size: 0.75rem; color: var(--default-color); text-transform: uppercase; font-weight: 500;
        }
        .highlight-info .info-value {
            font-size: 1rem; color: var(--heading-color); font-weight: 700;
        }
        .total-price-box {
            background: linear-gradient(135deg, var(--accent-color), #764ba2); 
            color: var(--contrast-color); border-radius: 10px; padding: 20px; text-align: center;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        .total-price-box .total-label { font-size: 0.9rem; opacity: 0.9; }
        .total-price-box .total-amount { font-size: 2.5rem; font-weight: 800; }
        .table thead th {
            border-bottom: 2px solid var(--accent-color); color: var(--heading-color); font-weight: 600;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark shadow">
    <div class="container">
        <a class="navbar-brand fw-bold" href="index.php" style="font-family: 'Questrial', sans-serif;">Artefax</a>
        <div class="d-flex align-items-center gap-3">
            <span class="text-white small">Hi, **<?= htmlspecialchars($_SESSION['user']['nama'] ?? $_SESSION['user']['UserNama'] ?? 'User') ?>**</span>
            <a href="logout.php" class="btn btn-outline-light btn-sm">
                <i class="bi bi-box-arrow-right"></i> Logout
            </a>
        </div>
    </div>
</nav>

<div class="container py-5">
    <h2 class="text-center mb-5 fw-bolder" style="color: var(--heading-color); font-family: 'Questrial', sans-serif;">
        <i class="bi bi-clock-history me-2"></i> Riwayat Booking
    </h2>

    <?php if (empty($bookings)): ?>
        <div class="text-center py-5" style="background: var(--surface-color); border-radius: 12px; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);">
            <i class="bi bi-receipt display-1 text-muted mb-4"></i>
            <h4 class="text-muted">Riwayat booking Anda masih kosong.</h4>
            <a href="index.php" class="btn btn-lg mt-3 btn-detail">
                Booking Sekarang
            </a>
        </div>
    <?php else: ?>
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <?php foreach ($bookings as $b):
                    $harga = number_format((float)$b['BkgTotalHarga'], 0, ',', '.');
                    $statusClass = $b['BkgStatus'] === 'Diterima' ? 'status-diterima' :
                                   ($b['BkgStatus'] === 'Selesai' ? 'status-selesai' : 'status-batal');
                    
                    $statusIcon = $b['BkgStatus'] === 'Diterima' ? 'bi-check-circle-fill' :
                                  ($b['BkgStatus'] === 'Selesai' ? 'bi-archive-fill' : 'bi-x-circle-fill');
                ?>
                    <div class="card card-horizontal">
                        <div class="row g-0">
                            <div class="col-md-8">
                                <div class="card-header-h d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong class="fs-5">Booking #<?= $b['IDBooking'] ?></strong><br>
                                        <small>
                                            <i class="bi bi-calendar-check me-1"></i> <?= date('d F Y', strtotime($b['BkgTglMulai'])) ?> 
                                            <i class="bi bi-clock ms-2 me-1"></i> <?= date('H:i', strtotime($b['BkgTglMulai'])) ?> WIB
                                        </small>
                                    </div>
                                    <span class="badge status-badge <?= $statusClass ?>">
                                        <i class="bi <?= $statusIcon ?> me-1"></i> <?= $b['BkgStatus'] ?>
                                    </span>
                                </div>
                                <div class="card-body py-4">
                                    <p class="text-muted small mb-1">Item/Layanan Dipesan</p>
                                    <h4 class="card-title mb-3">
                                        <?= htmlspecialchars($b['ItemNama']) ?>
                                    </h4>
                                    <?php if (strtolower($role) !== 'customer'): ?>
                                    <p class="text-muted mb-0 small">
                                        <i class="bi bi-person me-1"></i> **Penyewa:** <?= htmlspecialchars($b['UserNama']) ?>
                                    </p>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="col-md-4 detail-area d-flex flex-column justify-content-center align-items-center p-4">
                                <div class="text-center">
                                    <p class="text-muted small mb-1">Total Pembayaran</p>
                                    <div class="price-gede mb-3">Rp<?= $harga ?></div>
                                    
                                    <a href="#" class="btn btn-detail" 
                                        onclick="showBookingDetail(<?= $b['IDBooking'] ?>); return false;">
                                        <i class="bi bi-search me-1"></i> Lihat Detail
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="text-center mt-5">
        <a href="index.php" class="btn btn-outline-secondary px-5 py-2">
            <i class="bi bi-arrow-left"></i> Kembali ke Beranda
        </a>
    </div>
</div>

<div class="modal fade" id="bookingDetailModal" tabindex="-1" aria-labelledby="bookingDetailModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="bookingDetailModalLabel" style="color: var(--heading-color); font-family: 'Questrial', sans-serif;">Detail Booking #<span id="modalBookingId"></span></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div id="modalContentPlaceholder">
            <div class="text-center py-5">
                <div class="spinner-border" role="status" style="width: 3rem; height: 3rem; color: var(--accent-color);">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2 text-muted">Memuat detail...</p>
            </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Fungsi JavaScript/AJAX untuk memuat detail booking
    function showBookingDetail(id) {
        // 1. Tampilkan ID di header modal
        document.getElementById('modalBookingId').textContent = id;
        
        // 2. Tampilkan Loading Spinner
        const modalBody = document.getElementById('modalContentPlaceholder');
        modalBody.innerHTML = `
            <div class="text-center py-5">
                <div class="spinner-border" role="status" style="width: 3rem; height: 3rem; color: var(--accent-color);">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2 text-muted">Memuat detail...</p>
            </div>`;
        
        // 3. Tampilkan Modal
        const detailModal = new bootstrap.Modal(document.getElementById('bookingDetailModal'));
        detailModal.show();

        // 4. Lakukan Panggilan AJAX ke file terpisah
        fetch(`fetch_booking_detail.php?id=${id}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Gagal mengambil data. Status: ' + response.status);
                }
                return response.text();
            })
            .then(data => {
                // 5. Isi konten modal dengan data yang berhasil diambil
                modalBody.innerHTML = data;
            })
            .catch(error => {
                // 6. Tangani error
                console.error("Error fetching booking detail:", error);
                modalBody.innerHTML = `
                    <div class="alert alert-danger text-center" role="alert">
                        <strong>Gagal memuat detail!</strong><br>${error.message}. <br>Pastikan file <code>fetch_booking_detail.php</code> sudah ada dan nama kolom database Anda sudah sesuai (Gunakan <code>UserNoHP</code>).
                    </div>`;
            });
    }
</script>
</body>
</html>