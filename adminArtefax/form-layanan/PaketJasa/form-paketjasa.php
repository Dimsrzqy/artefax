<?php
session_start();

// ============== WHITELIST – FILE YANG BOLEH DIAKSES TANPA LOGIN ==============
$currentFile = basename($_SERVER['SCRIPT_NAME']);
$allowedWithoutLogin = ['login.php', 'logout.php', 'register.php', 'forgot_password.php'];

if (!in_array($currentFile, $allowedWithoutLogin)) {

    // ============== ANTI-LOOP LOGIN PROTECTION ==============
    if (!isset($_SESSION['login_attempts'])) {
        $_SESSION['login_attempts'] = 0;
    }
    if (isset($_SESSION['last_redirect_time'])) {
        if (time() - $_SESSION['last_redirect_time'] > 60) {
            $_SESSION['login_attempts'] = 0;
        }
    }

    require_once __DIR__ . "/../../../config/koneksi.php";
    require_once __DIR__ . "/../../../class/paketjasa.php";

    // === PERBAIKAN PALING PENTING: CEK SESSION YANG BENAR ===
    $isLoggedIn = false;
    $userData   = [];

    // Format session baru dari login.php kamu (di dalam $_SESSION['user'])
    if (isset($_SESSION['user']) && is_array($_SESSION['user']) && !empty($_SESSION['user']['IDUser'])) {
        $isLoggedIn = true;
        $userData   = $_SESSION['user'];
    }
    // Format lama (kalau ada halaman lain yang masih pakai cara lama)
    elseif (isset($_SESSION['UserID']) || isset($_SESSION['user_id']) || isset($_SESSION['id'])) {
        $isLoggedIn = true;
        $userData = [
            'IDUser'   => $_SESSION['UserID'] ?? $_SESSION['user_id'] ?? $_SESSION['id'],
            'UserNama' => $_SESSION['UserNama'] ?? $_SESSION['username'] ?? $_SESSION['user_name'] ?? 'User',
            'UserRole' => $_SESSION['UserRole'] ?? $_SESSION['role'] ?? $_SESSION['user_role'] ?? 'user'
        ];
    }

    // Jika tetap belum login → redirect
    if (!$isLoggedIn) {
        $_SESSION['login_attempts']++;
        $_SESSION['last_redirect_time'] = time();

        if ($_SESSION['login_attempts'] > 3) {
            die('
                <div style="font-family: Arial; padding: 50px; text-align: center;">
                    <h2 style="color: #dc3545;">Login Loop Detected</h2>
                    <p>Terjadi masalah dengan sistem login. Kemungkinan penyebab:</p>
                    <ul style="text-align: left; max-width: 600px; margin: 20px auto; line-height: 1.8;">
                        <li>Session tidak tersimpan dengan benar setelah login</li>
                        <li>Cookie browser diblokir atau disabled</li>
                    </ul>
                    <div style="margin-top: 30px;">
                        <a href="../../../View/login.php" style="background: #007bff; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px;">
                            Coba Login Lagi
                        </a>
                    </div>
                </div>
            ');
        }

        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        header("Location: ../../../View/login.php");
        exit();
    }

    // Reset counter karena sudah berhasil masuk
    $_SESSION['login_attempts'] = 0;

    // ============== DATA USER YANG DIPAKAI DI HALAMAN ==============
    $loggedInUser = [
        'UserNama' => $userData['UserNama']  ?? 'Admin User',
        'UserRole' => $userData['UserRole']  ?? 'Administrator'
    ];

    $defaultProfileImage = "../../img/faces/face1.jpg";

    $db = new Database();
    $conn = $db->getConnection();
    $paket = new PaketJasa($conn);

    /* ============== PAGINATION ============== */
    $limit = 10;
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $offset = ($page - 1) * $limit;

    $totalPaket = $paket->TotalLayanan();
    $totalPages = ceil($totalPaket / $limit);
    $paketList = $paket->readAll($limit, $offset);

    $success_message = $_SESSION['success_message'] ?? '';
    $error_message   = $_SESSION['error_message'] ?? '';
    unset($_SESSION['success_message'], $_SESSION['error_message']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <!-- ... head content tetap sama ... -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Admin ArtefaxID</title>
    
    <link href="../../lib/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="../../lib/ionicons/css/ionicons.min.css" rel="stylesheet">
    <link href="../../lib/typicons.font/typicons.css" rel="stylesheet">
    <link rel="stylesheet" href="../../css/azia.css" />
    <link rel="stylesheet" href="../css/form-paketjasa.css">

    <style>
        /* --- FIXED LAYOUT --- */
        .az-body {
            padding-top: 70px !important;
        }
        .az-header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1040;
            background-color: #fff;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .az-content-left {
            position: fixed;
            top: 70px;
            bottom: 0;
            z-index: 1020;
            overflow-y: auto;
            background-color: #fff;
            padding-top: 30px !important;
        }
        
        @media (min-width: 992px) {
            .az-content-body {
                padding-top: 0 !important;
                margin-left: 240px !important;
            }
        }
        @media (max-width: 991.98px) {
            .az-content-left {
                position: static;
                top: auto;
                bottom: auto;
                overflow-y: visible;
            }
            .az-content-body {
                margin-left: 0 !important;
            }
        }

        /* Badge status */
        .badge-active { background-color: #d4edda; color: #155724; }
        .badge-inactive { background-color: #f8d7da; color: #721c24; }
        
        /* Modal */
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1050;
            width: 100%;
            height: 100%;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: rgba(0, 0, 0, 0.5);
        }
        
        /* Lightbox */
        .lightbox-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 9999;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .lightbox-content {
            background: white;
            padding: 20px;
            border-radius: 8px;
            position: relative;
            max-width: 90%;
            max-height: 90%;
            text-align: center;
        }
        .lightbox-close {
            position: absolute;
            top: 10px;
            right: 25px;
            color: #fff;
            font-size: 35px;
            font-weight: bold;
            cursor: pointer;
        }
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
                    <a href="" class="close">&times;</a>
                </div>
                <ul class="nav">
                    <li class="nav-item">
                        <a href="../template/index.html" class="nav-link"><i class="typcn typcn-chart-area-outline"></i> Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a href="../../form-karyawan/form-karyawan.php" class="nav-link"><i class="typcn typcn-group"></i>User</a>
                    </li>
                    <li class="nav-item">
                        <a href="../../form-pembayaran/daftar_pembayaran.php" class="nav-link"><i class="typcn typcn-puzzle-outline"></i>Pembayaran</a>
                    </li>
                    <li class="nav-item active">
                        <a href="../form-layanan/form-layanan.php" class="nav-link"><i class="typcn typcn-puzzle-outline"></i>Layanan</a>
                    </li>
                    <li class="nav-item">
                        <a href="../../form-laporan/LaporanKeuangan.php" class="nav-link"><i class="typcn typcn-group-outline"></i>Laporan</a>
                    </li>
                </ul>
            </div>
            
            <div class="az-header-right">
                <a href="" class="az-header-search-link"><i class="fas fa-search"></i></a>
                <div class="az-header-message">
                    <a href="#"><i class="typcn typcn-messages"></i></a>
                </div>
                <div class="dropdown az-header-notification">
                    <a href="" class="new"><i class="typcn typcn-bell"></i></a>
                </div>
                
                <!-- DROPDOWN PROFIL - SUDAH DIPERBAIKI -->
                <div class="dropdown az-profile-menu">
                    <a href="../../View/profile.php" class="az-img-user">
                        <img src="<?= htmlspecialchars($defaultProfileImage) ?>" alt="">
                    </a>
                    <div class="dropdown-menu">
                        <div class="az-dropdown-header d-sm-none">
                            <a href="" class="az-header-arrow"><i class="icon ion-md-arrow-back"></i></a>
                        </div>
                        <div class="az-header-profile">
                            <div class="az-img-user">
                                <img src="<?= htmlspecialchars($defaultProfileImage) ?>" alt="">
                            </div>
                            <h6><?= htmlspecialchars($loggedInUser['UserNama']) ?></h6>
                            <span><?= htmlspecialchars($loggedInUser['UserRole']) ?></span>
                        </div>
                        <a href="../../View/profile.php" class="dropdown-item">
                            <i class="typcn typcn-user-outline"></i> My Profile
                        </a>
                        <a href="../../logout.php" class="dropdown-item">
                            <i class="typcn typcn-power-outline"></i> Sign Out
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Content tetap sama seperti kode asli -->
    <div class="az-content pd-y-20 pd-lg-y-30 pd-xl-y-40">
        <div class="container">
            <div class="az-content-left az-content-left-components d-lg-block d-none">
                <div class="component-item">
                    <label>Layanan</label>
                    <nav class="nav flex-column">
                        <a href="../PaketJasa/form-paketjasa.php" class="nav-link active">Daftar Paket Jasa</a>
                        <a href="../Alat/form-alat.php" class="nav-link">Daftar Alat</a>
                    </nav>
                </div>
            </div>
            
            <div class="az-content-body pd-lg-l-40 d-flex flex-column">
                <div class="az-content-breadcrumb">
                    <span>Layanan</span>
                    <span>Daftar Paket Jasa</span>
                </div>
                <h2 class="az-content-title">Daftar Paket Jasa</h2>

                <div class="d-flex justify-content-between align-items-center mg-b-20">
                    <p class="mg-b-0">Kelola semua paket jasa di sini.</p>
                    <button onclick="openTambahPopup()" 
                        style="padding: 10px 20px; background: #3366ff; color: white; border: none; border-radius: 6px; cursor: pointer;">
                        Tambah Layanan
                    </button>
                </div>

                <?php if ($success_message): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($success_message) ?></div>
                <?php endif; ?>
                <?php if ($error_message): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
                <?php endif; ?>

                <!-- Tabel dan pagination tetap sama -->
                <div class="table-container">
                    <?php if ($paketList && count($paketList) > 0): ?>
                        <table class="custom-table">
                            <thead>
                                <tr>
                                    <th width="5%">No</th>
                                    <th>Nama Paket</th>
                                    <th>Gambar</th>
                                    <th>Kategori</th>
                                    <th>Harga</th>
                                    <th>Durasi</th>
                                    <th>Status</th>
                                    <th width="18%">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no = $offset + 1; foreach ($paketList as $p): ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                        <td>
                                            <strong><?= htmlspecialchars($p['PaketNama']) ?></strong><br>
                                            <small class="text-muted"><?= htmlspecialchars(substr($p['PaketDeskripsi'], 0, 60)) ?>...</small>
                                        </td>
                                        <td class="text-center">
                                            <?php if (!empty($p['PaketDirGbr'])): ?>
                                                <button type="button" class="btn btn-sm btn-info btn-detail-gambar"
                                                        data-img="<?= htmlspecialchars($p['PaketDirGbr']) ?>"
                                                        data-nama="<?= htmlspecialchars($p['PaketNama']) ?>">
                                                    <i class="fas fa-image"></i> Detail
                                                </button>
                                            <?php else: ?>
                                                <span class="text-muted">—</span>
                                            <?php endif; ?>
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
                                                <form action="hapus_paketjasa.php" method="POST" style="display:inline;" onsubmit="return confirm('Yakin hapus layanan ini?')">
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
                        
                        <?php if ($totalPages > 1): ?>
                        <nav class="mt-4">
                            <ul class="pagination justify-content-center">
                                <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                                    <a class="page-link" href="?page=<?= $page-1 ?>">« Sebelumnya</a>
                                </li>
                                <?php
                                $start = max(1, $page - 2);
                                $end = min($totalPages, $page + 2);
                                
                                for ($i = $start; $i <= $end; $i++) {
                                    $active = ($i == $page) ? 'active' : '';
                                    echo "<li class='page-item $active'><a class='page-link' href='?page=$i'>$i</a></li>";
                                }
                                ?>
                                <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                                    <a class="page-link" href="?page=<?= $page+1 ?>">Berikutnya »</a>
                                </li>
                            </ul>
                        </nav>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="text-center py-5 bg-light rounded">
                            <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                            <p class="text-muted mb-3">Belum ada layanan terdaftar.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal dan Lightbox tetap sama -->
    <div id="gambarLightbox" class="lightbox-overlay" style="display:none;">
        <div class="lightbox-content">
            <span class="lightbox-close">&times;</span>
            <h5 id="lightboxJudul" class="mb-3"></h5>
            <img id="lightboxImg" src="" alt="Gambar Paket" style="max-width:100%; max-height:80vh; border-radius:8px;">
        </div>
    </div>

    <div id="layananModal" class="modal" style="display:none;">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 id="modalTitle">Tambah Layanan</h5>
                    <button type="button" class="close-btn" onclick="closeModal()">&times;</button>
                </div>
                <form id="formLayanan" action="tambah_paketjasa.php" method="POST" enctype="multipart/form-data">
                    <div class="modal-body modal-body-scroll">
                        <input type="hidden" id="idPaket" name="IDPaket">
                        <input type="hidden" id="gambarLama" name="gambarLama">

                        <div class="form-group">
                            <label>Nama Paket <span class="text-danger">*</span></label>
                            <input type="text" name="PaketNama" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Gambar <span class="text-danger">*</span></label>
                            <div id="previewContainer" style="display:none;">
                                <img id="previewImg" src="" alt="Preview" style="max-height:220px; border-radius:12px;">
                            </div>
                            <input type="file" name="gambar" id="gambar_paketjasa" accept="image/*">
                        </div>

                        <div class="form-group">
                            <label>Kategori <span class="text-danger">*</span></label>
                            <select name="PaketKategori" class="form-control" required>
                                <option value="">Pilih Kategori</option>
                                <option value="Graduation">Graduation</option>
                                <option value="Wedding">Wedding</option>
                                <option value="Prewedding">Prewedding</option>
                                <option value="Event">Event Organizer</option>
                                <option value="YearBook">YearBook</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Deskripsi</label>
                            <textarea name="PaketDeskripsi" class="form-control" rows="3"></textarea>
                        </div>

                        <div class="form-group">
                            <label>Harga (Rp) <span class="text-danger">*</span></label>
                            <input type="number" name="PaketHarga" class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label>Durasi <span class="text-danger">*</span></label>
                            <input type="text" name="PaketDurasi" class="form-control" required>
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
    </div>

    <script src="../../lib/jquery/jquery.min.js"></script>
    <script src="../../lib/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../js/azia.js"></script>
    
    <script>
        const modal = document.getElementById('layananModal');
        const form = document.getElementById('formLayanan');

        document.addEventListener('DOMContentLoaded', function() {
            modal.style.display = 'none';
            
            setTimeout(function() {
                $('.alert').fadeOut('slow');
            }, 5000);
            
            $('#azMenuShow').on('click', function(e) {
                e.preventDefault();
                $('.az-header-menu').toggleClass('show');
            });
            
            $('.az-header-menu .close').on('click', function(e) {
                e.preventDefault();
                $('.az-header-menu').removeClass('show');
            });
        });
        
        function openTambahPopup() {
            document.getElementById('modalTitle').textContent = 'Tambah Layanan';
            form.action = 'tambah_paketjasa.php';
            form.reset();
            modal.style.display = 'flex';
        }

        function openEditPopup(data) {
            document.getElementById('modalTitle').textContent = 'Edit Layanan';
            form.action = 'edit_paketjasa.php';
            document.getElementById('idPaket').value = data.IDPaket;
            form.PaketNama.value = data.PaketNama;
            form.PaketKategori.value = data.PaketKategori;
            form.PaketDeskripsi.value = data.PaketDeskripsi;
            form.PaketHarga.value = data.PaketHarga;
            form.PaketDurasi.value = data.PaketDurasi;
            form.PaketStatus.value = data.PaketStatus;
            
            if (data.PaketDirGbr) {
                document.getElementById('gambarLama').value = data.PaketDirGbr;
                document.getElementById('previewImg').src = '../../Paket/img/produk/' + data.PaketDirGbr;
                document.getElementById('previewContainer').style.display = 'block';
            }
            
            modal.style.display = 'flex';
        }

        function closeModal() {
            modal.style.display = 'none';
        }

        window.onclick = (e) => {
            if (e.target === modal) closeModal();
        };

        // Lightbox
        document.querySelectorAll('.btn-detail-gambar').forEach(btn => {
            btn.addEventListener('click', function() {
                const lightbox = document.getElementById('gambarLightbox');
                const imgPath = '../../Paket/img/produk/' + this.getAttribute('data-img');
                document.getElementById('lightboxImg').src = imgPath;
                document.getElementById('lightboxJudul').textContent = this.getAttribute('data-nama');
                lightbox.style.display = 'flex';
            });
        });

        document.querySelector('.lightbox-close').onclick = () => {
            document.getElementById('gambarLightbox').style.display = 'none';
        };
    </script>
</body>
</html>