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
$defaultProfileImage = '../../img/faces/artefax.jpg';

$db = new Database();
$conn = $db->getConnection();

$pembayaran = new Pembayaran($conn);
$user = new User($conn);

$ClearPayments = $pembayaran->readLunasDP();        // Menunggu pelunasan DP
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
        /* --- LAYOUT & CARD (sama seperti sebelumnya) --- */
        .az-body { padding-top: 70px !important; }
        .az-header { position: fixed; top:0; left:0; right:0; z-index:1040; background:#fff; box-shadow:0 2px 4px rgba(0,0,0,.1); }
        .az-content-left { position:fixed; top:70px; bottom:0; overflow-y:auto; background:#fff; padding-top:30px !important; }
        @media (min-width:992px) { .az-content-body { padding-top:0 !important; margin-left:240px !important; } }
        @media (max-width:991.98px) { .az-content-left { position:static; } .az-content-body { margin-left:0 !important; } }

        .card-payment {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            transition: transform 0.2s;
            margin-bottom: 20px;
        }
        .card-payment:hover { transform: translateY(-5px); box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15); }
        .card-header { background: #3366ff; color: white; padding: 12px 16px; font-weight: 600; font-size: 15px; }
        .card-body { padding: 16px; }
        .info-row { display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 14px; }
        .info-label { font-weight: 600; color: #555; width: 45%; }
        .info-value { color: #333; text-align: right; width: 55%; }
        .badge-pending { background: #fff3cd; color: #856404; padding: 3px 8px; border-radius: 4px; font-size: 11px; }
        .card-footer { padding: 12px 16px; background: #f8f9fa; display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #eee; }

        .btn-action { padding: 6px 12px; font-size: 13px; border-radius: 6px; font-weight: 500; color: white; border: none; display: inline-flex; align-items: center; gap: 5px; cursor: pointer; }
        .btn-detail { background: #17a2b8; }
        .btn-setuju { background: #28a745; }
        .btn-tolak { background: #dc3545; }

        .alert { padding:12px; border-radius:6px; margin-bottom:20px; }
        .alert-success { background:#d4edda; color:#155724; border:1px solid #c3e6cb; }
        .alert-danger { background:#f8d7da; color:#721c24; border:1px solid #f5c6cb; }

        .empty-state { text-align: center; padding: 60px 20px; color: #666; }
        .empty-state i { font-size: 48px; color:#ccc; margin-bottom:16px; }

        /* --- MODAL KONFIRMASI --- */
        #modalPelunasan {
            pointer-events: none; display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.7); z-index: 9999; justify-content: center; align-items: center; padding: 20px;
        }
        .modal-dialog {
            background: white; border-radius: 12px; width: 100%; max-width: 420px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3); animation: fadeIn 0.3s ease; pointer-events: auto;
        }
        .modal-header { padding: 15px 20px; color: white; font-weight: 600; border-radius: 12px 12px 0 0; display: flex; justify-content: space-between; align-items: center; }
        .modal-header.setuju { background: #28a745; }
        .modal-header.tolak { background: #dc3545; }
        .modal-body { padding:20px; text-align:center; font-size:15px; }
        .modal-footer { padding:15px 20px; border-top:1px solid #eee; display:flex; justify-content:flex-end; gap:10px; }
        .close-btn { background:none; border:none; font-size:24px; cursor:pointer; color:white; font-weight:bold; }
        .close-btn:hover { opacity:0.8; }
        @keyframes fadeIn { from {opacity:0;transform:scale(0.9);} to {opacity:1;transform:scale(1);} }

        /* --- DETAIL POPUP --- */
        .popup-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.85); z-index: 9998; justify-content: center; align-items: center; }
        .popup-content { background: #fff; width: 90%; max-width: 960px; max-height: 92vh; border-radius: 16px; overflow: hidden; box-shadow: 0 20px 70px rgba(0,0,0,0.5); position: relative; }
        .popup-header { background: linear-gradient(135deg, #4361ee, #3f37c9); color: white; padding: 18px 25px; font-size: 1.5em; font-weight: bold; cursor: grab; user-select: none; }
        .popup-header:active { cursor: grabbing; }
        .close-popup { position: absolute; right: 20px; top: 50%; transform: translateY(-50%); font-size: 34px; cursor: pointer; opacity: 0.9; }
        .close-popup:hover { opacity: 1; }
        .popup-body { padding: 25px; max-height: 72vh; overflow-y: auto; }
        .section { margin-bottom: 25px; padding: 20px; background: #f8f9fa; border-radius: 12px; border-left: 6px solid #4361ee; }
        .section h3 { margin: 0 0 18px 0; color: #2d3436; font-size: 1.35em; display: flex; align-items: center; gap: 10px; }
        table.info-table { width: 100%; border-collapse: collapse; font-size: 1.02em; }
        table.info-table td { padding: 11px 0; border-bottom: 1px dashed #ddd; }
        table.info-table td:first-child { width: 38%; font-weight: 600; color: #444; }
        .badge { padding: 6px 16px; border-radius: 50px; font-weight: bold; font-size: 0.9em; }
        .badge-lunas { background: #d4edda; color: #155724; }
        .badge-dp { background: #fff3cd; color: #856404; }
        .badge-pending { background: #fff3cd; color: #b8860b; }
        .item-list li { padding: 8px 0; color: #2d3436; font-size: 1.05em; }
        .btn-bukti { background: #4361ee; color: white; border: none; padding: 11px 20px; border-radius: 8px; cursor: pointer; font-weight: 600; transition: all 0.3s; }
        .btn-bukti:hover { background: #3f37c9; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(67,97,238,0.4); }
        .bukti-container { text-align: center; margin: 20px 0; }
        .bukti-thumbnail { max-width: 280px; max-height: 360px; object-fit: cover; border-radius: 12px; border: 4px solid #fff; box-shadow: 0 8px 30px rgba(0,0,0,0.3); cursor: zoom-in; transition: transform 0.3s; }
        .bukti-thumbnail:hover { transform: scale(1.07); }

        /* --- LIGHTBOX --- */
        #buktiLightbox { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.96); z-index: 99999; align-items: center; justify-content: center; padding: 20px; }
        .lightbox-overlay { position: relative; max-width: 95vw; max-height: 95vh; }
        .lightbox-image { max-width: 95vw; max-height: 92vh; object-fit: contain; border-radius: 12px; box-shadow: 0 0 50px rgba(0,0,0,0.8); }
        .lightbox-close { position: absolute; top: -16px; right: -16px; background: #ff3b30; color: white; width: 50px; height: 50px; border-radius: 50%; font-size: 32px; font-weight: bold; display: flex; align-items: center; justify-content: center; cursor: pointer; box-shadow: 0 4px 20px rgba(0,0,0,0.7); transition: all .2s; }
        .lightbox-close:hover { background:#ff453a; transform:scale(1.1); }
        .lightbox-caption { color:#ccc; text-align:center; margin-top:12px; font-size:14px; }
    </style>
</head>

<body class="az-body">
    <!-- HEADER & MENU (sama seperti sebelumnya) -->
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
                    <li class="nav-item"><a href="../../index.html" class="nav-link"><i class="typcn typcn-chart-area-outline"></i> Dashboard</a></li>
                    <li class="nav-item"><a href="../../form-karyawan/form-user.php" class="nav-link"><i class="typcn typcn-group"></i>User</a></li>
                    <li class="nav-item active"><a href="../form-pembayaran/daftar_pembayaran.php" class="nav-link"><i class="fas fa-money-bill-alt" style="margin-right:8px;"></i> Pembayaran</a></li>
                    <li class="nav-item"><a href="../../form-layanan/PaketJasa/form-paketjasa.php" class="nav-link"><i class="typcn typcn-puzzle-outline"></i>Layanan</a></li>
                    <li class="nav-item"><a href="../form-laporan/LaporanKeuangan.php" class="nav-link"><i class="fas fa-file-alt" style="margin-right:8px;"></i> Laporan</a></li>
                </ul>
            </div>
            <div class="az-header-right">
                <div class="dropdown az-profile-menu">
                    <a href="" class="az-img-user"><img src="<?= $defaultProfileImage ?>" alt=""></a>
                    <div class="dropdown-menu">
                        <div class="az-dropdown-header d-sm-none"><a href="" class="az-header-arrow"><i class="icon ion-md-arrow-back"></i></a></div>
                        <div class="az-header-profile">
                            <div class="az-img-user"><img src="<?= $defaultProfileImage ?>" alt=""></div>
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
                                            <span class="info-value"><span class="badge-pending">Menunggu Pelunasan</span></span>
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

    <!-- MODAL KONFIRMASI PELUNASAN -->
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

    <!-- DETAIL POPUP + LIGHTBOX (SEMUA DI DALAM 1 FILE) -->
    <div class="popup-overlay" id="detailPopupPembayaran">
        <div class="popup-content" id="draggablePopup">
            <div class="popup-header" id="dragHandle">
                <span id="popupTitle">Detail Pelunasan</span>
                <span class="close-popup" onclick="closeDetailPopup()">×</span>
            </div>
            <div class="popup-body" id="popupBody">
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="buktiLightbox"></div>

    <script src="../../lib/jquery/jquery.min.js"></script>
    <script src="../../lib/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../js/azia.js"></script>
    
    <script>
        $(document).ready(function() {
            $('#azMenuShow').on('click', e => { e.preventDefault(); $('.az-header-menu').toggleClass('show'); $(this).toggleClass('open'); });
            $('.az-header-menu .close').on('click', e => { e.preventDefault(); $('.az-header-menu').removeClass('show'); $('#azMenuShow').removeClass('open'); });
            setTimeout(() => $('.alert').fadeOut('slow'), 5000);
        });

        // DRAGGABLE
        let isDragging = false, startX, startY, offsetX = 0, offsetY = 0;
        const popupEl = document.getElementById('draggablePopup');
        const headerEl = document.getElementById('dragHandle');
        headerEl.addEventListener('mousedown', e => {
            if (e.target.classList.contains('close-popup')) return;
            isDragging = true; startX = e.clientX - offsetX; startY = e.clientY - offsetY; headerEl.style.cursor = 'grabbing';
        });
        document.addEventListener('mousemove', e => {
            if (!isDragging) return; e.preventDefault();
            offsetX = e.clientX - startX; offsetY = e.clientY - startY;
            popupEl.style.transform = `translate(${offsetX}px, ${offsetY}px)`;
        });
        document.addEventListener('mouseup', () => { isDragging = false; headerEl.style.cursor = 'grab'; });

        // MODAL PELUNASAN
        const modalOverlay = document.getElementById('modalPelunasan');
        let currentId = null;
        function konfirmasiAksi(id, aksi) {
            currentId = id;
            const header = document.getElementById('modalHeader');
            const title = document.getElementById('modalTitle');
            const message = document.getElementById('modalMessage');
            const btn = document.getElementById('btnKonfirmasi');

            if (aksi === 'setuju') {
                header.className = 'modal-header setuju';
                title.textContent = 'Setujui Pelunasan';
                message.innerHTML = 'Setujui pelunasan ini?<br>Status menjadi <strong>Lunas</strong>.';
                btn.textContent = 'Ya, Setujui';
            } else {
                header.className = 'modal-header tolak';
                title.textContent = 'Tolak Pelunasan';
                message.innerHTML = 'Tolak pelunasan ini?<br>Status menjadi <strong>Gagal</strong>.';
                btn.textContent = 'Ya, Tolak';
            }
            btn.onclick = () => window.location.href = `proses_pelunasan.php?id=${currentId}&aksi=${aksi}`;
            modalOverlay.style.display = 'flex';
        }
        function closeModal() { modalOverlay.style.display = 'none'; currentId = null; }
        modalOverlay.addEventListener('click', e => { if (e.target === modalOverlay) closeModal(); });

        // DETAIL POPUP
        function openDetailPopup(data) {
            popupEl.style.transform = 'translate(0,0)'; offsetX = offsetY = 0;

            const status = (data.PbrStatus || 'Pending').trim();
            const badge = status === 'Lunas' ? 'badge-lunas' : status === 'DP' ? 'badge-dp' : 'badge-pending';

            const items = Array.isArray(data.DaftarPesanan) && data.DaftarPesanan.length > 0
                ? data.DaftarPesanan.map(i => `<li>${i}</li>`).join('')
                : '<li style="color:#888"><em>Tidak ada item dipesan</em></li>';

            const tglMulai = data.BkgTglMulai ? new Date(data.BkgTglMulai).toLocaleDateString('id-ID') : '-';
            const tglSelesai = data.BkgTglSelesai ? new Date(data.BkgTglSelesai).toLocaleDateString('id-ID') : '-';
            const waktu = new Date(data.CreatedAt).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' });

            // BUKTI PELUNASAN
            let buktiHtml = '<em style="color:#888">Tidak ada bukti pelunasan</em>';
            if (data.PbrBukti && data.PbrBukti.trim() !== '') {
                const cleanFile = data.PbrBukti.trim().replace(/^(\.+[\/\\])+/g, '');
                const imgSrc = '../../../uploads/bukti_pembayaran/' + cleanFile;

                buktiHtml = `
                    <div class="bukti-container">
                        <img src="${imgSrc}" class="bukti-thumbnail" alt="Bukti Pelunasan"
                             onerror="this.src='https://via.placeholder.com/600x400/eee/999?text=Bukti+Tidak+Ditemukan';this.style.border='4px solid #fcc';"
                             onclick="openLightbox('${imgSrc}')">
                        <br><br>
                        <button class="btn-bukti" onclick="openLightbox('${imgSrc}')">
                            Lihat Ukuran Penuh
                        </button>
                    </div>`;
            }

            const html = `
                <div class="section">
                    <h3>Informasi Pelunasan</h3>
                    <table class="info-table">
                        <tr><td>Total Transfer</td><td><strong>Rp ${Number(data.PbrJumlah||0).toLocaleString('id-ID')}</strong></td></tr>
                        <tr><td>Status</td><td><span class="badge ${badge}">${status}</span></td></tr>
                        <tr><td>Metode</td><td>${data.PbrMetode || '-'}</td></tr>
                        <tr><td>Keterangan</td><td>${data.PbrKeterangan || '-'}</td></tr>
                        <tr><td>Waktu Upload</td><td>${waktu}</td></tr>
                        <tr><td>Bukti Pelunasan</td><td>${buktiHtml}</td></tr>
                    </table>
                </div>
                <div class="section">
                    <h3>Pelanggan & Lokasi</h3>
                    <table class="info-table">
                        <tr><td>Nama</td><td>${data.UserNama || '-'}</td></tr>
                        <tr><td>Alamat</td><td>${data.BkgAlamat || '-'}</td></tr>
                        <tr><td>Tanggal Mulai</td><td>${tglMulai}</td></tr>
                        <tr><td>Tanggal Selesai</td><td>${tglSelesai}</td></tr>
                    </table>
                </div>
                <div class="section">
                    <h3>Daftar Pesanan</h3>
                    <p><strong>Jenis Booking:</strong> ${data.JenisBooking || '-'}</p>
                    <ul class="item-list">${items}</ul>
                </div>
            `;

            document.getElementById('popupTitle').textContent = `Detail Pelunasan BOOK#${String(data.IDBooking||'').padStart(6,'0')}`;
            document.getElementById('popupBody').innerHTML = html;
            document.getElementById('detailPopupPembayaran').style.display = 'flex';
        }

        // LIGHTBOX
        function openLightbox(src) {
            let lb = document.getElementById('buktiLightbox');
            if (!lb.innerHTML) {
                lb.innerHTML = `<div class="lightbox-overlay">
                    <span class="lightbox-close" onclick="closeLightbox()">×</span>
                    <img class="lightbox-image" src="" alt="Bukti Pelunasan">
                    <div class="lightbox-caption">Klik luar atau tekan ESC untuk menutup</div>
                </div>`;
                lb.addEventListener('click', e => { if (e.target === lb || e.target.classList.contains('lightbox-overlay')) closeLightbox(); });
            }
            lb.querySelector('.lightbox-image').src = src;
            lb.style.display = 'flex';
        }
        function closeLightbox() { document.getElementById('buktiLightbox').style.display = 'none'; }
        function closeDetailPopup() { document.getElementById('detailPopupPembayaran').style.display = 'none'; }
        document.getElementById('detailPopupPembayaran').addEventListener('click', e => { if (e.target.id === 'detailPopupPembayaran') closeDetailPopup(); });
        document.addEventListener('keydown', e => { if (e.key === 'Escape') { closeDetailPopup(); closeLightbox(); closeModal(); } });
    </script>
</body>
</html>