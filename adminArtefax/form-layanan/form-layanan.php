<?php
session_start();
require_once __DIR__ . "/../../config/koneksi.php";
require_once __DIR__ . "/../../class/paketjasa.php";

$db = new Database();
$conn = $db->getConnection();

// Inisialisasi class
$paket = new PaketJasa($conn);
$paketList = $paket->readAll(); // Nanti akan tampilkan data

// Feedback
$success_message = $_SESSION['success_message'] ?? '';
$error_message = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <!-- Global site tag (gtag.js) - Google Analytics -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=UA-90680653-2"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());

      gtag('config', 'UA-90680653-2');
    </script>

    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- Twitter -->
    <!-- <meta name="twitter:site" content="@bootstrapdash">
    <meta name="twitter:creator" content="@bootstrapdash">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Azia">
    <meta name="twitter:description" content="Responsive Bootstrap 4 Dashboard Template">
    <meta name="twitter:image" content="https://www.bootstrapdash.com/azia/img/azia-social.png"> -->

    <!-- Facebook -->
    <!-- <meta property="og:url" content="https://www.bootstrapdash.com/azia">
    <meta property="og:title" content="Azia">
    <meta property="og:description" content="Responsive Bootstrap 4 Dashboard Template">

    <meta property="og:image" content="https://www.bootstrapdash.com/azia/img/azia-social.png">
    <meta property="og:image:secure_url" content="https://www.bootstrapdash.com/azia/img/azia-social.png">
    <meta property="og:image:type" content="image/png">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="600"> -->

    <!-- Meta -->
    <meta name="description" content="Responsive Bootstrap 4 Dashboard Template">
    <meta name="author" content="BootstrapDash">

    <title>Admin ArtefaxID</title>

    <!-- vendor css -->
    <link href="../lib/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="../lib/ionicons/css/ionicons.min.css" rel="stylesheet">
    <link href="../lib/typicons.font/typicons.css" rel="stylesheet">

    <!-- azia CSS -->
    <link rel="stylesheet" href="../css/azia.css" />
    <!-- Custom Table & Modal Style -->
    <style>
        .custom-table {
        width: 100%;
        border-collapse: collapse;
        background: white;
        margin-top: 20px;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
        font-size: 14px;
    }

        .tombol-aksi {
        display: flex;
        gap: 8px;
        justify-content: center;
    }
    .custom-table th,
    .custom-table td {
        border: 1px solid #ddd;
        padding: 12px 15px;
        text-align: left;
    }

    .custom-table th {
        background-color: #3366ff;
        color: white;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 13px;
    }

    .custom-table tbody tr:nth-child(even) {
        background-color: #f8f9fa;
    }

    .custom-table tbody tr:hover {
        background-color: #fff3cd;
    }

    .badge-active { background: #d4edda; color: #155724; padding: 4px 8px; border-radius: 4px; font-size: 12px; }
    .badge-inactive { background: #f8d7da; color: #721c24; padding: 4px 8px; border-radius: 4px; font-size: 12px; }

    .table-container {
        overflow-x: auto;
        border-radius: 10px;
    }

    .alert {
        padding: 12px 16px;
        border-radius: 6px;
        margin-bottom: 20px;
    }
    
    .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    .alert-danger { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

        /* Modal */
        .modal {
        display: none;
        position: fixed;
        top: 0; left: 0;
        width: 100%; height: 100%;
        background-color: rgba(0, 0, 0, 0.6);
        z-index: 9999 !important;
        justify-content: center;
        align-items: center;
        padding: 20px;
        overflow: auto;
    }

    .modal-dialog {
        background: white;
        border-radius: 12px;
        width: 100%;
        max-width: 560px;
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
        position: relative;
        animation: fadeIn 0.3s ease;
        pointer-events: auto !important;
    }

    .modal * {
        pointer-events: auto !important;
    }

    .modal input,
    .modal select,
    .modal textarea,
    .modal button {
        pointer-events: auto !important;
        user-select: auto !important;
        cursor: auto !important;
    }

    .modal input:focus,
    .modal select:focus,
    .modal textarea:focus {
        outline: 2px solid #3366ff;
    }

    .modal-header {
        padding: 15px 20px;
        border-bottom: 1px solid #eee;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .modal-header h5 {
        margin: 0;
        font-weight: 600;
        color: #333;
    }

    .modal-body {
        padding: 20px;
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
        font-size: 28px;
        cursor: pointer;
        color: #aaa;
        font-weight: bold;
    }

    .close-btn:hover {
        color: #333;
    }

    .form-control {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid #ccc;
        border-radius: 6px;
        font-size: 14px;
        transition: border 0.2s;
    }

    .form-control:focus {
        border-color: #3366ff;
        box-shadow: 0 0 0 3px rgba(51, 102, 255, 0.1);
    }

    .btn {
        padding: 8px 16px;
        border-radius: 6px;
        font-weight: 500;
        cursor: pointer;
    }

    .btn-primary {
        background-color: #3366ff;
        color: white;
        border: none;
    }

    .btn-secondary {
        background-color: #6c757d;
        color: white;
        border: none;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    </style>
  </head>
  <body>
    <div class="az-header">
      <div class="container">
        <div class="az-header-left">
          <a href="index.html" class="az-logo"><span></span> Artefax</a>
          <a href="" id="azMenuShow" class="az-header-menu-icon d-lg-none"><span></span></a>
        </div>
        <!-- az-header-left -->
        <div class="az-header-menu">
          <div class="az-header-menu-header">
            <a href="index.html" class="az-logo"><span></span> azia</a>
            <a href="" class="close">&times;</a>
          </div>
          <!-- az-header-menu-header -->
          <ul class="nav">
            <li class="nav-item">
              <a href="../template/index.html" class="nav-link"><i class="typcn typcn-chart-area-outline"></i> Dashboard</a>
            </li>
           <li class="nav-item">
              <a href="../form-karyawan/form-karyawan.php" class="nav-link"><i class="typcn typcn-group"></i>Karyawan</a>
            </li>
            <li class="nav-item active">
              <a href="../form-layanan/form-layanan.php" class="nav-link"><i class="typcn typcn-puzzle-outline"></i>Layanan</a>
            </li>
            <li class="nav-item">
              <a href="../form-karyawan/form-user.php" class="nav-link"><i class="typcn typcn-group-outline"></i>Users</a>
            </li>
            <li class="nav-item">
              <a href="../form-laporan/LaporanAbsensiKaryawan.php" class="nav-link"><i class="typcn typcn-group-outline"></i>Laporan</a>
            </li>
            <li class="nav-item">
              <a href="" class="nav-link with-sub"><i class="typcn typcn-book"></i> Components</a>
              <div class="az-menu-sub">
                <div class="container">
                  <div>
                    <nav class="nav">
                      <a href="../template/elem-buttons.html" class="nav-link">Buttons</a>
                      <a href="../template/elem-dropdown.html" class="nav-link">Dropdown</a>
                      <a href="../template/elem-icons.html" class="nav-link">Icons</a>
                      <a href="../template/table-basic.html" class="nav-link">Table</a>
                    </nav>
                  </div>
                </div>
                <!-- container -->
              </div>
            </li>
          </ul>
        </div><!-- az-header-menu -->
        <div class="az-header-right">
          <a href="https://www.bootstrapdash.com/demo/azia-free/docs/documentation.html" target="_blank" class="az-header-search-link"><i class="far fa-file-alt"></i></a>
          <a href="" class="az-header-search-link"><i class="fas fa-search"></i></a>
          <div class="az-header-message">
            <a href="#"><i class="typcn typcn-messages"></i></a>
          </div><!-- az-header-message -->
          <div class="dropdown az-header-notification">
            <a href="" class="new"><i class="typcn typcn-bell"></i></a>
            <div class="dropdown-menu">
              <div class="az-dropdown-header mg-b-20 d-sm-none">
                <a href="" class="az-header-arrow"><i class="icon ion-md-arrow-back"></i></a>
              </div>
              <h6 class="az-notification-title">Notifications</h6>
              <p class="az-notification-text">You have 2 unread notification</p>
              <div class="az-notification-list">
                <div class="media new">
                  <div class="az-img-user"><img src="../img/faces/face2.jpg" alt=""></div>
                  <div class="media-body">
                    <p>Congratulate <strong>Socrates Itumay</strong> for work anniversaries</p>
                    <span>Mar 15 12:32pm</span>
                  </div><!-- media-body -->
                </div><!-- media -->
                <div class="media new">
                  <div class="az-img-user online"><img src="../img/faces/face3.jpg" alt=""></div>
                  <div class="media-body">
                    <p><strong>Joyce Chua</strong> just created a new blog post</p>
                    <span>Mar 13 04:16am</span>
                  </div><!-- media-body -->
                </div><!-- media -->
                <div class="media">
                  <div class="az-img-user"><img src="../img/faces/face4.jpg" alt=""></div>
                  <div class="media-body">
                    <p><strong>Althea Cabardo</strong> just created a new blog post</p>
                    <span>Mar 13 02:56am</span>
                  </div><!-- media-body -->
                </div><!-- media -->
                <div class="media">
                  <div class="az-img-user"><img src="../img/faces/face5.jpg" alt=""></div>
                  <div class="media-body">
                    <p><strong>Adrian Monino</strong> added new comment on your photo</p>
                    <span>Mar 12 10:40pm</span>
                  </div><!-- media-body -->
                </div><!-- media -->
              </div><!-- az-notification-list -->
              <div class="dropdown-footer"><a href="">View All Notifications</a></div>
            </div><!-- dropdown-menu -->
          </div><!-- az-header-notification -->
          <div class="dropdown az-profile-menu">
            <a href="" class="az-img-user"><img src="../img/faces/face1.jpg" alt=""></a>
            <div class="dropdown-menu">
              <div class="az-dropdown-header d-sm-none">
                <a href="" class="az-header-arrow"><i class="icon ion-md-arrow-back"></i></a>
              </div>
              <div class="az-header-profile">
                <div class="az-img-user">
                  <img src="../img/faces/face1.jpg" alt="">
                </div><!-- az-img-user -->
                <h6>Aziana Pechon</h6>
                <span>Premium Member</span>
              </div><!-- az-header-profile -->

              <a href="" class="dropdown-item"><i class="typcn typcn-user-outline"></i> My Profile</a>
              <a href="" class="dropdown-item"><i class="typcn typcn-edit"></i> Edit Profile</a>
              <a href="" class="dropdown-item"><i class="typcn typcn-time"></i> Activity Logs</a>
              <a href="" class="dropdown-item"><i class="typcn typcn-cog-outline"></i> Account Settings</a>
              <a href="page-signin.html" class="dropdown-item"><i class="typcn typcn-power-outline"></i> Sign Out</a>
            </div><!-- dropdown-menu -->
          </div>
        </div><!-- az-header-right -->
      </div><!-- container -->
    </div><!-- az-header -->

    <div class="az-content pd-y-20 pd-lg-y-30 pd-xl-y-40">
      <div class="container">
        <div class="az-content-left az-content-left-components">
          <div class="component-item">
        
            <label>Layanan</label>
            <nav class="nav flex-column">
              <a href="../form-layanan/daftarlayanan.html" class="nav-link active">Daftar Layanan</a> 
            </nav>
        </div><!-- component-item -->

        </div><!-- az-content-left -->
        <div class="az-content-body pd-lg-l-40 d-flex flex-column">
          <div class="az-content-breadcrumb">
            <span>Layanan</span>
            <span>Daftar Layanan</span>
          </div>
          <h2 class="az-content-title">Daftar Layanan</h2>

          <div class="d-flex justify-content-between align-items-center mg-b-20">
                    <p class="mg-b-0">Kelola semua paket layanan di sini.</p>
                    <button class="btn btn-primary" onclick="openTambahPopup()">
                        <i class="fas fa-plus"></i> Tambah Layanan
                    </button>
                </div>

                <!-- Feedback -->
                <?php if ($success_message): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($success_message) ?></div>
                <?php endif; ?>
                <?php if ($error_message): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
                <?php endif; ?>

                <!-- Tabel Layanan -->
                <div class="table-container">
                    <?php if ($paketList && count($paketList) > 0): ?>
                        <table class="custom-table">
                            <thead>
                                <tr>
                                    <th width="5%">No</th>
                                    <th>Layanan</th>
                                    <th>Kategori</th>
                                    <th>Harga</th>
                                    <th>Durasi</th>
                                    <th>Status</th>
                                    <th width="18%">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no = 1; foreach ($paketList as $p): ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                        <td>
                                            <strong><?= htmlspecialchars($p['PaketNama']) ?></strong><br>
                                            <small class="text-muted"><?= htmlspecialchars(substr($p['PaketDeskripsi'], 0, 60)) ?>...</small>
                                        </td>
                                        <td><?= htmlspecialchars($p['PaketKategori']) ?></td>
                                        <td>Rp <?= number_format($p['PaketHarga'], 0, ',', '.') ?></td>
                                        <td><?= htmlspecialchars($p['PaketDurasi']) ?></td>
                                        <td>
                                            <span class="badge <?= $p['PaketStatus'] === 'Aktif' ? 'badge-active' : 'badge-inactive' ?>">
                                                <?= htmlspecialchars($p['PaketStatus']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="tombol-aksi">
                                                <button class="btn btn-sm btn-warning" onclick='openEditPopup(<?= json_encode($p) ?>)'>
                                                    <i class="fas fa-edit"></i> Edit
                                                </button>
                                                <form action="hapus_layanan.php" method="POST" style="display:inline;" onsubmit="return confirm('Yakin hapus layanan ini?')">
                                                    <input type="hidden" name="id" value="<?= $p['IDPaket'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger">
                                                        <i class="fas fa-trash"></i> Hapus
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="text-center py-5 bg-light rounded">
                            <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                            <p class="text-muted mb-3">Belum ada layanan terdaftar.</p>
                            <button class="btn btn-primary" onclick="openTambahPopup()">
                                <i class="fas fa-plus"></i> Tambah Layanan
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Tambah/Edit -->
    <div id="layananModal" class="modal">
        <div class="modal-dialog">
            <div class="modal-header">
                <h5 id="modalTitle">Tambah Layanan</h5>
                <button type="button" class="close-btn" onclick="closeModal()">&times;</button>
            </div>
            <form id="formLayanan" action="tambah_layanan.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" id="idPaket" name="IDPaket">

                    <div class="form-group">
                        <label>Nama Layanan <span class="text-danger">*</span></label>
                        <input type="text" name="PaketNama" class="form-control" required minlength="3" maxlength="100">
                    </div>

                    <div class="form-group">
                        <label>Kategori <span class="text-danger">*</span></label>
                        <select name="PaketKategori" class="form-control" required>
                            <option value="">Pilih Kategori</option>
                            <option value="Graduation">Graduation</option>
                            <option value="Wedding">Wedding</option>
                            <option value="Event Organizer">Event Organizer</option>
                            <option value="Lainnya">Lainnya</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Deskripsi</label>
                        <textarea name="PaketDeskripsi" class="form-control" rows="3" maxlength="500"></textarea>
                    </div>

                    <div class="form-group">
                        <label>Harga (Rp) <span class="text-danger">*</span></label>
                        <input type="number" name="PaketHarga" class="form-control" required min="0" step="1000">
                    </div>

                    <div class="form-group">
                        <label>Durasi <span class="text-danger">*</span></label>
                        <input type="text" name="PaketDurasi" class="form-control" required placeholder="Contoh: 3 Hari" maxlength="50">
                    </div>

                    <div class="form-group">
                        <label>Status <span class="text-danger">*</span></label>
                        <select name="PaketStatus" class="form-control" required>
                            <option value="Aktif">Aktif</option>
                            <option value="Nonaktif">Nonaktif</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const modal = document.getElementById('layananModal');
        const form = document.getElementById('formLayanan');

        function openTambahPopup() {
        document.getElementById('modalTitle').textContent = 'Tambah Layanan';
        form.action = 'tambah_layanan.php';
        form.reset();
        document.getElementById('idPaket').value = '';
        modal.style.display = 'flex';
        setTimeout(() => form.PaketNama.focus(), 100);
    }

        function openEditPopup(data) {
        document.getElementById('modalTitle').textContent = 'Edit Layanan';
        form.action = 'edit_layanan.php';
        document.getElementById('idPaket').value = data.IDPaket;
        form.PaketNama.value = data.PaketNama;
        form.PaketKategori.value = data.PaketKategori;
        form.PaketDeskripsi.value = data.PaketDeskripsi;
        form.PaketHarga.value = data.PaketHarga;
        form.PaketDurasi.value = data.PaketDurasi;
        form.PaketStatus.value = data.PaketStatus;
        modal.style.display = 'flex';
        setTimeout(() => form.PaketNama.focus(), 100);
    }

        function closeModal() {
        modal.style.display = 'none';
        form.reset();
    }

    // Tutup saat klik luar
    window.addEventListener('click', function(e) {
        if (e.target === modal) {
            closeModal();
        }
    });

    // Pastikan input bisa diketik
    document.addEventListener('DOMContentLoaded', function() {
        const inputs = modal.querySelectorAll('input, select, textarea, button');
        inputs.forEach(input => {
            input.style.pointerEvents = 'auto';
            input.style.userSelect = 'auto';
            input.disabled = false;
        });
    });

    </script>

        </div><!-- az-content-body -->
      </div><!-- container -->
    </div><!-- az-content -->


    <script src="../lib/jquery/jquery.min.js"></script>
    <script src="../lib/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../lib/ionicons/ionicons.js"></script>
    <script src="../js/azia.js"></script>
    <script src="../js/chart.chartjs.js"></script>
    <script src="../js/jquery.cookie.js" type="text/javascript"></script>
  </body>
</html>
