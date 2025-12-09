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
    header("Location: ../../view/login.php"); 
    exit;
}
// --- END: VERIFIKASI DAN ADAPTASI SESI KRITIS ---

require_once __DIR__ . "/../../config/koneksi.php";
require_once __DIR__ . "/../../class/pembayaran.php";

$loggedInUser = [
    'UserNama' => $_SESSION['UserNama'] ?? 'Guest User', 
    'UserRole' => $_SESSION['UserRole'] ?? 'Unknown Role', 
];
$defaultProfileImage = '../img/faces/artefax.jpg';

$db = new Database();
$conn = $db->getConnection();
$pembayaran = new Pembayaran($conn);

/* ============== PAGINATION ============== */
$limit  = 10;
$page   = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;

$totalBooking = $pembayaran->TotalBooking();
$totalPages    = ceil($totalBooking / $limit);

$daftarPembayaran = $pembayaran->readJoin($limit, $offset);
$detailPembayaran = $pembayaran->readJoinFull($limit, $offset);

$success_message = $_SESSION['success_message'] ?? '';
$error_message = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Daftar Pembayaran - Artefax</title>

  <link href="../lib/fontawesome-free/css/all.min.css" rel="stylesheet">
  <link href="../lib/ionicons/css/ionicons.min.css" rel="stylesheet">
  <link href="../lib/typicons.font/typicons.css" rel="stylesheet">
  <link rel="stylesheet" href="../css/azia.css" />

  <style>
    /* --- FIXED LAYOUT --- */
    .az-body { padding-top: 70px !important; }
    .az-header { position: fixed; top:0; left:0; right:0; z-index:1040; background:#fff; box-shadow:0 2px 4px rgba(0,0,0,.1); }
    .az-content-left { position:fixed; top:70px; bottom:0; overflow-y:auto; background:#fff; padding-top:30px !important; }
    .az-content-left .component-item { padding-top:10px; }
    .az-content-left .component-item label { margin-top:15px; margin-bottom:10px; display:block; }
    .az-content-left .component-item label:first-child { margin-top:0; }
    
    @media (min-width:992px) { .az-content-body { padding-top:0 !important; margin-left:240px !important; } }
    @media (max-width:991.98px) { .az-content-left { position:static; } .az-content-body { margin-left:0 !important; } .az-body { padding-top:70px !important; } }

    /* --- TABLE STYLE --- */
    .custom-table { width:100%; border-collapse:separate; border-spacing:0; background:white; margin-top:20px; border-radius:12px; overflow:hidden; box-shadow:0 4px 15px rgba(0,0,0,.08); font-size:14px; }
    .custom-table th { background:#3366ff; color:white; font-weight:600; text-transform:uppercase; font-size:13px; padding:16px 12px; text-align:center; }
    .custom-table td { padding:14px 12px; border-bottom:1px solid #eef2f7; }
    .custom-table tbody tr:hover { background:#f8faff; transition:all .2s; }
    .text-truncate-multiline { max-height:3.6em; overflow:hidden; display:-webkit-box; -webkit-line-clamp:3; -webkit-box-orient:vertical; line-height:1.2em; }

    .tombol-aksi { display:flex; gap:8px; justify-content:center; flex-wrap:nowrap; }
    .btn-action-square { padding:0.375rem 0.75rem !important; border:none !important; border-radius:0.25rem !important; font-size:0.875rem; display:inline-flex; align-items:center; gap:0.35rem; min-width:80px; height:40px; justify-content:center; }
    .btn-detail-custom { background:#17a2b8 !important; color:white !important; }
    .btn-hapus-custom { background:#dc3545 !important; color:white !important; }

    .badge { padding:5px 10px; border-radius:12px; font-size:12px; font-weight:600; }
    .badge-menunggu { background:#fff3cd; color:#856404; }
    .badge-berhasil { background:#d4edda; color:#155724; }
    .badge-gagal { background:#f8d7da; color:#721c24; }

    .table-container { overflow-x:auto; border-radius:10px; }

    .alert { padding:12px 16px; border-radius:6px; margin-bottom:20px; }
    .alert-success { background:#d4edda; color:#155724; border:1px solid #c3e6cb; }
    .alert-danger { background:#f8d7da; color:#721c24; border:1px solid #f5c6cb; }

    /* POPUP CANTIK DARI detail_pembayaran.php */
    .popup-overlay {display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.8);z-index:9999;justify-content:center;align-items:center;}
    .popup-content {background:#fff;width:90%;max-width:900px;max-height:92vh;border-radius:16px;overflow:hidden;box-shadow:0 20px 70px rgba(0,0,0,0.4);position:relative;animation:fadeIn 0.3s ease-out;}
    .popup-header {background:linear-gradient(135deg,#4361ee,#3f37c9);color:white;padding:18px 25px;font-size:1.5em;font-weight:bold;cursor:grab;user-select:none;position:relative;}
    .popup-header:active{cursor:grabbing;}
    .close-popup{position:absolute;right:20px;top:50%;transform:translateY(-50%);font-size:34px;cursor:pointer;opacity:0.9;}
    .close-popup:hover{opacity:1;}
    .popup-body{padding:25px;max-height:70vh;overflow-y:auto;}
    .section{margin-bottom:25px;padding:20px;background:#f8f9fa;border-radius:12px;border-left:6px solid #4361ee;}
    .section h3{margin:0 0 18px 0;color:#2d3436;font-size:1.35em;display:flex;align-items:center;gap:10px;}
    table.info-table{width:100%;border-collapse:collapse;font-size:1.02em;}
    table.info-table td{padding:11px 0;border-bottom:1px dashed #ddd;}
    table.info-table td:first-child{width:38%;font-weight:600;color:#444;}
    .badge-lunas{background:#d4edda;color:#155724;}
    .badge-dp{background:#fff3cd;color:#856404;}
    .badge-pending{background:#fff3cd;color:#b8860b;}
    .item-list{margin:15px 0;padding-left:5px;}
    .item-list li{padding:8px 0;color:#2d3436;font-size:1.05em;}
    .btn-bukti{background:#4361ee;color:white;border:none;padding:11px 20px;border-radius:8px;cursor:pointer;font-weight:600;transition:all .3s;}
    .btn-bukti:hover{background:#3f37c9;transform:translateY(-2px);box-shadow:0 5px 15px rgba(67,97,238,0.4);}
    @keyframes fadeIn{from{opacity:0;transform:scale(0.9);}to{opacity:1;transform:scale(1);}}

    #buktiLightbox{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.94);align-items:center;justify-content:center;z-index:9999;padding:20px;}
    .lightbox-overlay{position:relative;max-width:95vw;max-height:95vh;border-radius:16px;overflow:hidden;box-shadow:0 25px 70px rgba(0,0,0,0.7);}
    .lightbox-image{max-width:95vw;max-height:85vh;object-fit:contain;border-radius:12px;background:#000;}
    .lightbox-close{position:absolute;top:-14px;right:-14px;background:#ff3b30;color:white;width:44px;height:44px;border-radius:50%;font-size:28px;font-weight:bold;display:flex;align-items:center;justify-content:center;cursor:pointer;box-shadow:0 4px 20px rgba(0,0,0,0.6);transition:all .2s;}
    .lightbox-close:hover{transform:scale(1.1);background:#ff453a;}
    .lightbox-caption{color:#aaa;text-align:center;margin-top:12px;font-size:14px;}
    .bukti-container{text-align:center;margin:12px 0;}
    .bukti-thumbnail{max-width:260px;max-height:320px;object-fit:cover;border-radius:12px;border:4px solid #fff;box-shadow:0 8px 25px rgba(0,0,0,0.25);cursor:zoom-in;transition:transform .3s;}
    .bukti-thumbnail:hover{transform:scale(1.06);}
  </style>
</head>

<body class="az-body">
  <div class="az-header">
    <div class="container">
      <div class="az-header-left">
        <a href="../template/index.html" class="az-logo"><span></span> Artefax</a>
        <a href="" id="azMenuShow" class="az-header-menu-icon d-lg-none"><span></span></a>
      </div>
      <div class="az-header-menu">
        <div class="az-header-menu-header">
          <a href="index.html" class="az-logo"><span></span> Artefax</a>
          <a href="" class="close">×</a>
        </div>
        <ul class="nav">
          <li class="nav-item"><a href="../index.html" class="nav-link"><i class="typcn typcn-chart-area-outline"></i> Dashboard</a></li>
          <li class="nav-item"><a href="../form-karyawan/form-user.php" class="nav-link"><i class="typcn typcn-group"></i>User</a></li>
          <li class="nav-item active"><a href="../form-pembayaran/daftar_pembayaran.php" class="nav-link"><i class="fas fa-money-bill-alt" style="margin-right:8px;"></i> Pembayaran</a></li>
          <li class="nav-item"><a href="../form-layanan/PaketJasa/form-paketjasa.php" class="nav-link"><i class="typcn typcn-puzzle-outline"></i>Layanan</a></li>
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
            <a href="../../View/profile.php" class="dropdown-item"><i class="typcn typcn-user-outline"></i> My Profile</a>
            <a href="../../logout.php" class="dropdown-item"><i class="typcn typcn-power-outline"></i> Sign Out</a>
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
            <a href="../form-pembayaran/daftar_pembayaran.php" class="nav-link active">Daftar Pembayaran</a>
            <a href="../form-pembayaran/pembayaran/konfirmasi_pembayaran.php" class="nav-link">Konfirmasi Pembayaran</a>
          </nav>
          <label>Pelunasan DP</label>
          <nav class="nav flex-column">
            <a href="../form-pembayaran/dp/pelunasan_pembayaran.php" class="nav-link">Pelunasan Pembayaran</a>
          </nav>
          <label>Refund</label>
          <nav class="nav flex-column">
            <a href="../form-pembayaran/refund/pengajuan_refund.php" class="nav-link">Pengajuan Refund</a>
          </nav>
        </div>
      </div>

      <div class="az-content-body pd-lg-l-40 d-flex flex-column">
        <div class="az-content-breadcrumb">
          <span>Pembayaran</span>
          <span>Daftar Pembayaran</span>
        </div>
        <h2 class="az-content-title">Daftar Pembayaran</h2>

        <div class="d-flex justify-content-between align-items-center mg-b-20">
          <p class="mg-b-0">Daftar keseluruhan transaksi pemesanan.</p>
        </div>

        <?php if ($success_message): ?>
          <div class="alert alert-success"><?= htmlspecialchars($success_message) ?></div>
        <?php endif; ?>
        <?php if ($error_message): ?>
          <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
        <?php endif; ?>

        <div class="table-container">
          <?php if ($daftarPembayaran && count($daftarPembayaran) > 0): ?>
            <table class="custom-table">
              <thead>
                <tr>
                  <th width="5%">No</th>
                  <th>Nama Pelanggan</th>
                  <th>Jenis</th>
                  <th>Pesanan</th> 
                  <th>Jumlah Pembayaran</th>
                  <th>Metode</th>
                  <th>Status</th>
                  <th>Waktu</th>
                  <th width="15%">Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php $no = $offset + 1; foreach ($daftarPembayaran as $index => $p): 
                  $pf = $detailPembayaran[$index] ?? $p;
                ?>
                  <tr>
                    <td><?= $no++ ?></td>
                    <td><?= htmlspecialchars($p['UserNama'] ?? '') ?></td>
                    <td>
                      <?php 
                      $jenis = $p['JenisBooking'] ?? '-';
                      echo $jenis == 'Paket Jasa,Alat' ? 'Paket & Alat' : htmlspecialchars($jenis);
                      ?>
                    </td>
                    <td>
                      <div class="text-truncate-multiline">
                        <?php 
                        $pesanan = $p['DaftarPesanan'] ?? '-';
                        echo $pesanan !== '' ? htmlspecialchars($pesanan) : '-';
                        ?>
                      </div>
                    </td>
                    <td>Rp <?= number_format($p['PbrJumlah'], 0, ',', '.') ?></td>
                    <td><?= htmlspecialchars($p['PbrMetode']) ?></td>
                    <td>
                      <span class="badge badge-<?= strtolower($p['PbrStatus']) ?>">
                        <?= htmlspecialchars($p['PbrStatus']) ?>
                      </span>
                    </td>
                    <td><?= date('d/m/Y H:i', strtotime($p['CreatedAt'])) ?></td>
                    <td>
                      <div class="tombol-aksi">
                        <button class="btn btn-sm btn-action-square btn-detail-custom" onclick='openDetailPopup(<?= json_encode($pf, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                          <i class="fas fa-eye"></i> Detail
                        </button>
                        <form action="hapus_pembayaran.php" method="POST" style="display:inline;" onsubmit="return confirm('Yakin hapus pembayaran ini?')">
                          <input type="hidden" name="id" value="<?= $p['IDPembayaran'] ?>">
                          <button type="submit" class="btn btn-sm btn-action-square btn-hapus-custom ms-1">
                            <i class="fas fa-trash"></i> Hapus
                          </button>
                        </form>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>

            <?php if ($totalPages > 1): ?>
              <nav class="mt-4">
                <ul class="pagination justify-content-center">
                  <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= $page-1 ?>">Previous</a>
                  </li>
                  <?php
                  $start = max(1, $page - 2);
                  $end   = min($totalPages, $page + 2);
                  if ($start > 1) {
                    echo '<li class="page-item"><a class="page-link" href="?page=1">1</a></li>';
                    if ($start > 2) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                  }
                  for ($i = $start; $i <= $end; $i++) {
                    $active = ($i == $page) ? 'active' : '';
                    echo "<li class='page-item $active'><a class='page-link' href='?page=$i'>$i</a></li>";
                  }
                  if ($end < $totalPages) {
                    if ($end < $totalPages - 1) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                    echo "<li class='page-item'><a class='page-link' href='?page=$totalPages'>$totalPages</a></li>";
                  }
                  ?>
                  <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= $page+1 ?>">Next</a>
                  </li>
                </ul>
              </nav>
              <div class="text-center text-muted small">
                Halaman <?= $page ?> dari <?= $totalPages ?> | Total <?= $totalBooking ?> Pembayaran
              </div>
            <?php endif; ?>
          <?php else: ?>
            <div class="text-center py-5 bg-light rounded">
              <i class="fas fa-money-check-alt fa-3x text-muted mb-3"></i>
              <p class="text-muted mb-3">Belum ada data pembayaran.</p>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- POPUP CANTIK + LENGKAP DARI detail_pembayaran.php (SUDAH DIGABUNG) -->
  <div class="popup-overlay" id="detailPopupPembayaran">
    <div class="popup-content" id="draggablePopup">
        <div class="popup-header" id="dragHandle">
            <span id="popupTitle">Detail Pembayaran</span>
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

  <!-- Lightbox -->
  <div id="buktiLightbox"></div>

  <script src="../lib/jquery/jquery.min.js"></script>
  <script src="../lib/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="../js/azia.js"></script>
  
  <script>
    $(document).ready(function() {
      $('#azMenuShow').on('click', function(e) {
        e.preventDefault();
        $('.az-header-menu').toggleClass('show');
        $(this).toggleClass('open');
      });
      
      $('.az-header-menu .close').on('click', function(e) {
        e.preventDefault();
        $('.az-header-menu').removeClass('show');
        $('#azMenuShow').removeClass('open');
      });

      setTimeout(function() {
        $('.alert').fadeOut('slow');
      }, 5000);
    });

    // DRAGGABLE
    let isDragging = false, startX, startY, offsetX = 0, offsetY = 0;
    const popupEl = document.getElementById('draggablePopup');
    const headerEl = document.getElementById('dragHandle');

    headerEl.addEventListener('mousedown', e => {
        if (e.target.classList.contains('close-popup')) return;
        isDragging = true;
        startX = e.clientX - offsetX;
        startY = e.clientY - offsetY;
        headerEl.style.cursor = 'grabbing';
    });
    document.addEventListener('mousemove', e => {
        if (!isDragging) return;
        e.preventDefault();
        offsetX = e.clientX - startX;
        offsetY = e.clientY - startY;
        popupEl.style.transform = `translate(${offsetX}px, ${offsetY}px)`;
    });
    document.addEventListener('mouseup', () => {
        isDragging = false;
        headerEl.style.cursor = 'grab';
    });

    // FUNGSI UTAMA - PAKAI YANG CANTIK & TIDAK LOOP
    function openDetailPopup(data) {
        popupEl.style.transform = 'translate(0,0)';
        offsetX = offsetY = 0;

        const status = (data.PbrStatus || 'Pending').trim();
        const badge = status === 'Lunas' ? 'badge-lunas' : 
                      status === 'Pending' ? 'badge-pending' : 'badge-dp';

        const items = Array.isArray(data.DaftarPesanan) && data.DaftarPesanan.length > 0
            ? data.DaftarPesanan.map(i => `<li>${i}</li>`).join('')
            : '<li style="color:#888"><em>Tidak ada item dipesan</em></li>';

        const tglMulai = data.BkgTglMulai ? new Date(data.BkgTglMulai).toLocaleDateString('id-ID') : '-';
        const tglSelesai = data.BkgTglSelesai ? new Date(data.BkgTglSelesai).toLocaleDateString('id-ID') : '-';
        const waktu = new Date(data.CreatedAt).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' });

        // BUKTI PEMBAYARAN - PATH PASTI BENAR
        let bukti = '<em style="color:#888">Tidak ada bukti pembayaran</em>';
        if (data.PbrBukti && data.PbrBukti.trim() !== '') {
            let filename = data.PbrBukti.replace(/^(\.+[\/\\])+/g, '');
            let imgUrl = '../../uploads/bukti_pembayaran/' + filename;

            bukti = `
                <div style="text-align:center;margin:30px 0;padding:20px;background:#f8f9fa;border-radius:12px;border:3px dashed #4361ee;">
                    <p style="margin:0 0 15px 0;color:#4361ee;font-weight:600;font-size:17px;">Bukti Transfer dari Pelanggan</p>
                    <img src="${imgUrl}?v=${Date.now()}" 
                         style="max-width:100%;max-height:550px;border-radius:12px;box-shadow:0 10px 30px rgba(0,0,0,0.2);cursor:zoom-in;"
                         onclick="window.open('${imgUrl}','_blank')"
                         onerror="this.src='https://via.placeholder.com/600x400/eee/999?text=Bukti+Tidak+Ditemukan';this.style.opacity=0.6;"
                         alt="Bukti Pembayaran">
                    <br><br>
                    <button class="btn-bukti" onclick="window.open('${imgUrl}','_blank')">
                        Buka di Tab Baru
                    </button>
                </div>`;
        }

        const html = `
            <div class="section">
                <h3>Informasi Pembayaran & Status</h3>
                <table class="info-table">
                    <tr><td>Total Jumlah</td><td><strong>Rp ${Number(data.PbrJumlah || 0).toLocaleString('id-ID')}</strong></td></tr>
                    <tr><td>Status</td><td><span class="badge ${badge}">${status}</span></td></tr>
                    <tr><td>Metode</td><td>${data.PbrMetode || '-'}</td></tr>
                    <tr><td>Keterangan</td><td>${data.PbrKeterangan || '-'}</td></tr>
                    <tr><td>Waktu Transaksi</td><td>${waktu}</td></tr>
                    <tr><td>Jaminan</td><td>${data.BkgJaminan || '-'}</td></tr>
                </table>
                ${bukti}
            </div>

            <div class="section">
                <h3>Informasi Pelanggan & Lokasi</h3>
                <table class="info-table">
                    <tr><td>Nama Pelanggan</td><td>${data.UserNama || '-'}</td></tr>
                    <tr><td>Alamat Penggunaan</td><td>${data.BkgAlamat || '-'}</td></tr>
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

        document.getElementById('popupTitle').textContent = `Detail Booking #${data.IDBooking || '—'}`;
        document.getElementById('popupBody').innerHTML = html;
        document.getElementById('detailPopupPembayaran').style.display = 'flex';
    }

    function closeDetailPopup() {
        document.getElementById('detailPopupPembayaran').style.display = 'none';
    }

    document.getElementById('detailPopupPembayaran').addEventListener('click', e => {
        if (e.target.id === 'detailPopupPembayaran') closeDetailPopup();
    });
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') closeDetailPopup();
    });
  </script>
</body>
</html>