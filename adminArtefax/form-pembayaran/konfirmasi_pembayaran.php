<?php
session_start();
require_once __DIR__ . "/../../config/koneksi.php";
require_once __DIR__ . "/../../class/pembayaran.php";
require_once __DIR__ . "/../../class/users.php";

$db = new Database();
$conn = $db->getConnection();

$pembayaran = new Pembayaran($conn);
$user = new User($conn);

$pendingPayments = $pembayaran->readPending();

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
    <title>Konfirmasi Pembayaran - Admin ArtefaxID</title>

    <!-- CSS -->
    <link href="../lib/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="../css/azia.css" rel="stylesheet">
    <style>
        .card-payment {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            overflow: hidden;
            transition: transform 0.2s;
            margin-bottom: 20px;
        }
        .card-payment:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        .card-header {
            background: #3366ff;
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
            background: #fff3cd; color: #856404; padding: 3px 8px; border-radius: 4px; font-size: 11px;
        }
        .card-footer {
            padding: 12px 16px;
            background: #f8f9fa;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top: 1px solid #eee;
        }

        /* Tombol Aksi Seragam */
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
        .btn-detail   { background: #17a2b8; }
        .btn-setuju   { background: #28a745; }
        .btn-tolak    { background: #dc3545; }

        .alert { padding: 12px; border-radius: 6px; margin-bottom: 20px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-danger { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

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

        /* Modal Konfirmasi - Fixed & Lebih Jelas */
        #modalKonfirmasi {
            display: none;
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(0,0,0,0.7);
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
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            animation: fadeIn 0.3s ease;
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
        .modal-header.setuju { background: #28a745; }
        .modal-header.tolak { background: #dc3545; }
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
        .close-btn:hover { opacity: 0.8; }

        @keyframes fadeIn {
            from { opacity: 0; transform: scale(0.9); }
            to { opacity: 1; transform: scale(1); }
        }
    </style>
</head>
<body class="az-body">

    <!-- Header & Sidebar (tetap sama) -->
    <div class="az-header"> ... </div>

    <div class="az-content pd-y-20 pd-lg-y-30 pd-xl-y-40">
        <div class="container">
            <div class="az-content-left az-content-left-components">
                <div class="component-item">
                    <label>Pembayaran</label>
                    <nav class="nav flex-column">
                        <a href="../form-pembayaran/daftar_pembayaran.php" class="nav-link">Daftar Pembayaran</a> 
                        <a href="../form-pembayaran/konfirmasi_pembayaran.php" class="nav-link active">Konfirmasi Pembayaran</a> 
                    </nav>
                </div>
            </div>

            <div class="az-content-body pd-lg-l-40 d-flex flex-column">
                <div class="az-content-breadcrumb">
                    <span>Pembayaran</span>
                    <span>Konfirmasi Pembayaran</span>
                </div>
                <h2 class="az-content-title">Konfirmasi Pembayaran</h2>
                <p class="mg-b-20">Verifikasi bukti pembayaran dari pelanggan.</p>

                <!-- Feedback -->
                <?php if ($success): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <!-- Daftar Kartu -->
                <div class="row">
                    <?php if ($pendingPayments && count($pendingPayments) > 0): ?>
                        <?php foreach ($pendingPayments as $p): ?>
                            <?php
                            $customer = $user->getUserByID($p['IDUser']);
                            $namaCustomer = $customer['UserNama'] ?? 'Unknown';
                            $tglKirim = date('d M Y, H:i', strtotime($p['CreatedAt'])) . ' WIB';
                            $tglBooking = date('d M Y', strtotime($p['BkgTglMulai'] ?? $p['CreatedAt']));
                            ?>
                            <div class="col-md-6 col-lg-4">
                                <div class="card-payment">
                                    <div class="card-header">
                                        BOOK#<?= str_pad($p['IDBooking'], 6, '0', STR_PAD_LEFT) ?>
                                    </div>
                                    <div class="card-body">
                                        <div class="info-row">
                                            <span class="info-label">Nama Pemesan</span>
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
                                            <span class="info-value">BCA a/n PT Artefax</span>
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
                                        <a href="detail_pembayaran.php?id=<?= $p['IDPembayaran'] ?>" class="btn-action btn-detail">
                                            <i class="fas fa-eye"></i> Detail
                                        </a>
                                        <div>
                                            <button class="btn-action btn-setuju" onclick="konfirmasiAksi(<?= $p['IDPembayaran'] ?>, 'Sukses')">
                                                <i class="fas fa-check"></i> Setuju
                                            </button>
                                            <button class="btn-action btn-tolak" onclick="konfirmasiAksi(<?= $p['IDPembayaran'] ?>, 'Gagal')">
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
                                <h5>Tidak Ada Pembayaran Menunggu</h5>
                                <p>Semua pembayaran telah dikonfirmasi.</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Konfirmasi -->
    <div id="modalKonfirmasi">
        <div class="modal-dialog">
            <div class="modal-header" id="modalHeader">
                <h5 id="modalTitle">Konfirmasi</h5>
                <button class="close-btn" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body">
                <p id="modalMessage"></p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal()">Batal</button>
                <button id="btnKonfirmasi" class="btn btn-primary">Ya, Lanjutkan</button>
            </div>
        </div>
    </div>

    <script>
        let currentId, currentStatus;

        function konfirmasiAksi(id, status) {
            currentId = id;
            currentStatus = status;

            const isSetuju = status === 'Sukses';
            const header = document.getElementById('modalHeader');
            const title = document.getElementById('modalTitle');
            const message = document.getElementById('modalMessage');

            if (isSetuju) {
                header.className = 'modal-header setuju';
                title.textContent = 'Setujui Pembayaran';
                message.innerHTML = 'Setujui pembayaran ini? Status akan menjadi <strong>Sukses</strong>.';
            } else {
                header.className = 'modal-header tolak';
                title.textContent = 'Tolak Pembayaran';
                message.innerHTML = 'Tolak pembayaran ini? Status akan menjadi <strong>Gagal</strong>.';
            }

            document.getElementById('modalKonfirmasi').style.display = 'flex';
        }

        document.getElementById('btnKonfirmasi').onclick = function() {
            window.location = `proses_konfirmasi.php?id=${currentId}&status=${currentStatus}`;
        };

        function closeModal() {
            document.getElementById('modalKonfirmasi').style.display = 'none';
        }

        // Tutup saat klik luar
        window.addEventListener('click', function(e) {
            const modal = document.getElementById('modalKonfirmasi');
            if (e.target === modal) closeModal();
        });
    </script>

    <!-- Scripts -->
    <script src="../lib/jquery/jquery.min.js"></script>
    < script src="../lib/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../js/azia.js"></script>
</body>
</html>