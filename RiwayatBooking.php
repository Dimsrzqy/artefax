<?php
// File: RiwayatBooking.php → FINAL CODE (Bersih dari NBSP dan Status Otomatis + Modal Logout Minimalis)
session_start();
date_default_timezone_set('Asia/Jakarta');

// Pastikan class Booking tersedia (asumsi: terletak di './class/Booking.php')
require_once __DIR__ . '/config/koneksi.php';
require_once __DIR__ . '/class/Booking.php'; 

if (!isset($_SESSION['user'])) {
    header("Location: view/login.php");
    exit;
}

$database = new Database();
$conn = $database->getConnection();
if (!$conn) die("Database error");

// Inisiasi class Booking. Ini akan memicu updateStatusSelesaiOtomatis()
$bookingCls = new Booking($conn);

$role = $_SESSION['user']['UserRole'] ?? '';
$idUser = $_SESSION['user']['IDUser'] ?? 0;

// Daftar semua status yang relevan untuk riwayat
$statusFilter = ['Pending', 'Menunggu Konfirmasi', 'Diterima', 'Selesai', 'Batal'];
$bookings = [];

// Menggunakan implode dan real_escape_string untuk daftar status SQL
$statuses = implode("','", array_map([$conn, 'real_escape_string'], $statusFilter));
$result = false;

if (strtolower($role) === 'customer') {
    $query = "SELECT 
                b.IDBooking,
                b.BkgTglMulai,
                b.BkgTotalHarga,
                b.BkgStatus,
                u.UserNama,
                u.UserNoHP,
                COALESCE(GROUP_CONCAT(DISTINCT COALESCE(pj.PaketNama, a.AlatNama) SEPARATOR ' + '), 'Item Tidak Diketahui') AS ItemNama
              FROM booking b
              LEFT JOIN users u ON b.IDUser = u.IDUser
              LEFT JOIN booking_detail bd ON b.IDBooking = bd.IDBooking
              LEFT JOIN paketjasa pj ON bd.IDPaket = pj.IDPaket
              LEFT JOIN alat a ON bd.IDAlat = a.IDAlat
              WHERE b.BkgStatus IN ('$statuses') AND b.IDUser = ?
              GROUP BY b.IDBooking
              ORDER BY b.BkgTglMulai DESC";
              
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $idUser);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
    
} else {
    $query = "SELECT 
                b.IDBooking,
                b.BkgTglMulai,
                b.BkgTotalHarga,
                b.BkgStatus,
                u.UserNama,
                u.UserNoHP,
                COALESCE(GROUP_CONCAT(DISTINCT COALESCE(pj.PaketNama, a.AlatNama) SEPARATOR ' + '), 'Item Tidak Diketahui') AS ItemNama
              FROM booking b
              LEFT JOIN users u ON b.IDUser = u.IDUser
              LEFT JOIN booking_detail bd ON b.IDBooking = bd.IDBooking
              LEFT JOIN paketjasa pj ON bd.IDPaket = pj.IDPaket
              LEFT JOIN alat a ON bd.IDAlat = a.IDAlat
              WHERE b.BkgStatus IN ('$statuses')
              GROUP BY b.IDBooking, b.BkgTglMulai, b.BkgTotalHarga, b.BkgStatus, u.UserNama, u.UserNoHP
              ORDER BY b.BkgTglMulai DESC";
              
    $result = $conn->query($query);
}

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $bookings[] = $row;
    }
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
            --status-diterima-bg: #4caf50;
            --status-selesai-bg: var(--accent-color);
            --status-batal-bg: #dc3545;
            --status-pending-bg: #ffc107;
            --status-menunggu-konfirmasi-bg: #ffc107;
        }
        
        body { 
            background: var(--background-color); 
            font-family: 'Roboto', sans-serif;
            color: var(--default-color);
            padding-top: 70px; 
        }
        
        .navbar { 
            background-color: var(--accent-color) !important; 
        }

        /* HANYA INI YANG DITAMBAHKAN – TOMBOL PROFIL */
        .btn-profile {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: rgba(255,255,255,0.15);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.3rem;
            text-decoration: none;
            transition: background 0.3s ease;
        }
        .btn-profile:hover {
            background: rgba(255,255,255,0.3);
        }
        /* SEMUA STYLE LAIN TETAP 100% SAMA */
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
        .card-header-h {
            background: transparent;
            color: var(--heading-color); 
            padding: 1.2rem 1.5rem 0.8rem 1.5rem;
            border-bottom: 2px solid color-mix(in srgb, var(--accent-color), transparent 80%);
        }
        .card-header-h small { color: var(--default-color); }
        .price-gede {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--accent-color); 
            line-height: 1.2;
            margin-bottom: 0.5rem !important;
        }
        .status-badge {
            font-weight: 600; border-radius: 5px; padding: 0.5em 1.2em;
            color: var(--contrast-color) !important; text-transform: uppercase; font-size: 0.85rem;
        }
        .status-diterima { background-color: var(--status-diterima-bg); }
        .status-selesai   { background-color: var(--status-selesai-bg); }
        .status-batal     { background-color: var(--status-batal-bg); }
        .status-pending,
        .status-menunggu-konfirmasi { 
            background-color: var(--status-pending-bg); 
            color: #212529 !important; 
        }
        .detail-area {
            background-color: var(--soft-blue); 
            border-left: 1px solid color-mix(in srgb, var(--default-color), transparent 90%);
            border-radius: 0 12px 12px 0;
        }
        .btn-action {
            padding: 12px 30px; border-radius: 8px; font-weight: 500; transition: all 0.3s ease;
        }
        .btn-detail {
            background-color: var(--accent-color); color: var(--contrast-color); border: none;
        }
        .btn-detail:hover {
            background-color: color-mix(in srgb, var(--accent-color), black 10%);
            transform: translateY(-2px);
        }
        .btn-cancel {
            background-color: var(--status-batal-bg); color: var(--contrast-color); border: none;
            padding: 8px 15px; font-size: 0.85rem; margin-top: 10px;
        }
        .btn-cancel:hover {
             background-color: color-mix(in srgb, var(--status-batal-bg), black 10%);
             transform: none;
        }
        
        /* 🛑 STYLING MODAL LOGOUT MINIMALIS BARU */
        .modal-header-minimal {
            border-bottom: none;
            padding-bottom: 0;
        }
        .modal-title-minimal {
            font-weight: 600;
            color: var(--heading-color);
        }
        .modal-body-minimal {
            padding-top: 0;
            padding-bottom: 2rem;
            text-align: center;
        }
        .modal-icon-minimal {
            color: #6c757d; /* Warna abu-abu netral */
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        .modal-footer-minimal {
            border-top: none;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark shadow fixed-top">
    <div class="container">
        <a class="navbar-brand fw-bold" href="index.php" style="font-family: 'Questrial', sans-serif;">Artefax</a>
        <div class="d-flex align-items-center gap-3">
            <a href="View/profil.php" class="btn-profile" title="Profil Saya">
                <i class="bi bi-person-circle"></i>
            </a>

            <span class="text-white small">Hi, **<?= htmlspecialchars($_SESSION['user']['nama'] ?? $_SESSION['user']['UserNama'] ?? 'User') ?>**</span>
            
            <a href="javascript:void(0);" onclick="showLogoutModal();" class="btn btn-outline-light btn-sm">
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
            <a href="index.php" class="btn btn-lg mt-3 btn-detail btn-action">
                Booking Sekarang
            </a>
        </div>
    <?php else: ?>
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <?php foreach ($bookings as $b):
                    $harga = number_format((float)$b['BkgTotalHarga'], 0, ',', '.');
                    
                    $isPendingOrWaiting = in_array($b['BkgStatus'], ['Pending', 'Menunggu Konfirmasi']);

                    $statusClass = $b['BkgStatus'] === 'Diterima' ? 'status-diterima' :
                                   ($b['BkgStatus'] === 'Selesai' ? 'status-selesai' : 
                                    ($isPendingOrWaiting ? 'status-pending' : 'status-batal'));
                                    
                    $statusIcon = $b['BkgStatus'] === 'Diterima' ? 'bi-check-circle-fill' :
                                  ($b['BkgStatus'] === 'Selesai' ? 'bi-archive-fill' : 
                                   ($isPendingOrWaiting ? 'bi-hourglass-split' : 'bi-x-circle-fill'));
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
                                    
                                    <a href="#" class="btn btn-detail btn-action" 
                                        onclick="showBookingDetail(<?= $b['IDBooking'] ?>); return false;">
                                        <i class="bi bi-search me-1"></i> Lihat Detail
                                    </a>

                                    <?php 
                                        if ($b['BkgStatus'] === 'Diterima' || $b['BkgStatus'] === 'Menunggu Konfirmasi'): 
                                    ?>
                                    <button class="btn btn-cancel" 
                                            onclick="requestCancellation(<?= $b['IDBooking'] ?>); return false;">
                                        <i class="bi bi-x-circle-fill me-1"></i> Ajukan Pembatalan
                                    </button>
                                    <?php endif; ?>
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
                <h5 class="modal-title" id="bookingDetailModalLabel" style="color: var(--heading-color); font-family: 'Questrial', sans-serif;">Detail Booking</h5>
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

<div class="modal fade" id="logoutConfirmModal" tabindex="-1" aria-labelledby="logoutConfirmModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header modal-header-minimal">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body modal-body-minimal">
                <i class="bi bi-box-arrow-right modal-icon-minimal"></i>
                <h5 class="modal-title-minimal mb-2" id="logoutConfirmModalLabel">Konfirmasi Logout</h5>
                <p class="text-muted mb-0 small">Apakah Anda yakin ingin mengakhiri sesi?</p>
            </div>
            <div class="modal-footer modal-footer-minimal justify-content-center pt-0 pb-3">
                <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Batal</button>
                <a id="confirmLogoutButton" href="logout.php" class="btn btn-danger">Ya, Keluar</a>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // FUNGSI: Menampilkan modal konfirmasi logout
    function showLogoutModal() {
        const logoutModal = new bootstrap.Modal(document.getElementById('logoutConfirmModal'));
        logoutModal.show();
    }

    function showBookingDetail(id) {
        const modalBody = document.getElementById('modalContentPlaceholder');
        const modalTitle = document.getElementById('bookingDetailModalLabel');
        
        modalTitle.textContent = 'Detail Booking #' + id; 

        modalBody.innerHTML = `
            <div class="text-center py-5">
                <div class="spinner-border" role="status" style="width: 3rem; height: 3rem; color: var(--accent-color);">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2 text-muted">Memuat detail...</p>
            </div>`;
        
        const detailModal = new bootstrap.Modal(document.getElementById('bookingDetailModal'));
        detailModal.show();

        fetch(`fetch_booking_detail.php?id=${id}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Gagal mengambil data. Status: ' + response.status);
                }
                return response.text();
            })
            .then(data => {
                modalBody.innerHTML = data;
            })
            .catch(error => {
                console.error("Error fetching booking detail:", error);
                modalBody.innerHTML = `
                    <div class="alert alert-danger text-center" role="alert">
                        <strong>Gagal memuat detail!</strong><br>${error.message}.
                    </div>`;
            });
    }

    function requestCancellation(id) {
        window.location.href = `PengajuanPembatalan.php?id=${id}`;
    }
</script>
</body>
</html>