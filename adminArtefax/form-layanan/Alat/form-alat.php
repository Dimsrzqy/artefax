<?php
session_start();
require_once __DIR__ . "/../../../config/koneksi.php";
require_once __DIR__ . "/../../../class/alat.php";

$db = new Database();
$conn = $db->getConnection();

// Inisialisasi class
$alat = new Alat($conn);
/* ============== PAGINATION (SUDAH AMAN & TIDAK ERROR) ============== */
$limit  = 10;
$page   = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// Pastikan method ini ADA di class User
$totalAlat = $alat->TotalAlat();
$totalPages    = ceil($totalAlat/ $limit);

// Method getKaryawan dengan parameter $limit & $offset (WAJIB ADA!)
$alatList  = $alat->readAll($limit, $offset);
/* ================================================================== */

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

    function gtag() {
      dataLayer.push(arguments);
    }
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
  <link href="../../lib/fontawesome-free/css/all.min.css" rel="stylesheet">
  <link href="../../lib/ionicons/css/ionicons.min.css" rel="stylesheet">
  <link href="../../lib/typicons.font/typicons.css" rel="stylesheet">

  <!-- azia CSS -->
  <link rel="stylesheet" href="../../css/azia.css" />
  <link rel="stylesheet" href="../css/form-alat.css">

</head>

<body>
  <div class="az-header">
    <div class="container">
      <div class="az-header-left">
        <a href="../../template/index.html" class="az-logo"><span></span> Artefax</a>
        <a href="" id="azMenuShow" class="az-header-menu-icon d-lg-none"><span></span></a>
      </div>
      <!-- az-header-left -->
      <div class="az-header-menu">
        <div class="az-header-menu-header">
          <a href="index.html" class="az-logo"><span></span> Artefax</a>
          <a href="" class="close">×</a>
        </div>
        <!-- az-header-menu-header -->
        <ul class="nav">
          <li class="nav-item">
            <a href="../../template/index.html" class="nav-link"><i class="typcn typcn-chart-area-outline"></i> Dashboard</a>
          </li>
          <li class="nav-item">
            <a href="../../form-karyawan/form-user.php" class="nav-link"><i class="typcn typcn-group"></i>User</a>
          </li>
          <li class="nav-item">
            <a href="../../form-pembayaran/daftar_pembayaran.php" class="nav-link"><i class="typcn typcn-puzzle-outline"></i>Pembayaran</a>
          </li>
          <li class="nav-item active">
            <a href="../PaketJasa/form-paketjasa.php" class="nav-link"><i class="typcn typcn-puzzle-outline"></i>Layanan</a>
          </li>
          <li class="nav-item">
            <a href="../../form-laporan/LaporanKeuangan.php" class="nav-link"><i class="typcn typcn-group-outline"></i>Laporan</a>
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
            <a href="../PaketJasa/form-paketjasa.php" class="nav-link">Daftar Paket Jasa</a>
            <a href="../Alat/form-alat.php" class="nav-link active">Daftar Alat</a>
          </nav>
        </div><!-- component-item -->

      </div><!-- az-content-left -->
      <div class="az-content-body pd-lg-l-40 d-flex flex-column">
        <div class="az-content-breadcrumb">
          <span>Layanan</span>
          <span>Daftar Alat</span>
        </div>
        <h2 class="az-content-title">Daftar Alat</h2>

        <div class="d-flex justify-content-between align-items-center mg-b-20">
          <p class="mg-b-0">Kelola semua alat di sini.</p>
          <button onclick="openTambahPopup()" 
          style="padding: 10px 20px; background: #3366ff; color: white; border: none; border-radius: 6px; cursor: pointer;">
        Tambah Layanan
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
                    <?php if ($alatList && count($alatList) > 0): ?>
                        <table class="custom-table">
                            <thead>
                                <tr>
                                    <th width="5%">No</th>
                                    <th>Nama Alat</th>
                                    <th>Gambar</th>
                                    <th>Kategori</th>
                                    <th>Harga</th>
                                    <th>Stok</th>
                                    <th>Status</th>
                                    <th width="18%">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no = $offset + 1; foreach ($alatList as $p): ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                        <td>
                                            <strong><?= htmlspecialchars($p['AlatNama']) ?></strong><br>
                                            <small class="text-muted"><?= htmlspecialchars(substr($p['AlatDeskripsi'], 0, 60)) ?>...</small>
                                        </td>
                                        <td class="text-center">
                                          <?php if (!empty($p['AlatDirGbr'])): ?>
                                              <button type="button" class="btn btn-sm btn-info btn-detail-gambar"
                                                      data-img="<?= htmlspecialchars($p['AlatDirGbr']) ?>"
                                                      data-nama="<?= htmlspecialchars($p['AlatNama']) ?>">
                                                  <i class="fas fa-image"></i> Detail
                                              </button>
                                          <?php else: ?>
                                              <span class="text-muted">—</span>
                                          <?php endif; ?>
                                      </td>
                                        <td><?= htmlspecialchars($p['AlatKategori']) ?></td>
                                        <td>Rp <?= number_format($p['AlatHarga'], 0, ',', '.') ?></td>
                                        <td><?= number_format($p['AlatStok']) ?></td>
                                        <td>
                                            <span class="badge <?= $p['AlatStatus'] === 'Tersedia' ? 'badge-active' : 'badge-inactive' ?>">
                                                <?= htmlspecialchars($p['AlatStatus']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="tombol-aksi">
                                                <button class="btn btn-sm btn-warning" onclick='openEditPopup(<?= json_encode($p) ?>)'>
                                                    <i class="fas fa-edit"></i> Edit
                                                </button>
                                                <form action="hapus_alat.php" method="POST" style="display:inline;" onsubmit="return confirm('Yakin hapus layanan ini?')">
                                                    <input type="hidden" name="id" value="<?= $p['IDAlat'] ?>">
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
                                    <a class="page-link" href="?page=<?= $page+1 ?>">Berikutnya »</a>
                                </li>
                            </ul>
                        </nav>
                        <div class="text-center text-muted small">
                            Halaman <?= $page ?> dari <?= $totalPages ?> | Total <?= $totalAlat ?> Alat
                        </div>
                        <?php endif; ?>

                    <?php else: ?>
                        <div class="text-center py-5 bg-light rounded">
                            <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                            <p class="text-muted mb-3">Belum ada layanan terdaftar.</p>
                            <button onclick="openTambahPopup()" style="padding: 10px 20px; background: #3366ff; color: white; border: none; border-radius: 6px; cursor: pointer;">
                                Tambah Layanan
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div> 
        </div>
      </div>
    </div>
  </div>

  <!-- LIGHTBOX / POPUP GAMBAR -->
  <div id="gambarLightbox" class="lightbox-overlay" style="display:none;">
    <div class="lightbox-content">
      <span class="lightbox-close">&times;</span>
      <h5 id="lightboxJudul" class="mb-3"></h5>
      <img id="lightboxImg" src="" alt="Gambar Alat" style="max-width:100%; max-height:80vh; border-radius:8px;">
    </div>
  </div>

  <!-- Modal Tambah/Edit -->
  <div id="layananModal" class="modal" style="display:none;">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 id="modalTitle">Tambah Layanan</h5>
          <button type="button" class="close-btn" onclick="closeModal()">&times;</button>
        </div>
        <form id="formLayanan" action="tambah_alat.php" method="POST" enctype="multipart/form-data">
          <div class="modal-body modal-body-scroll">
            <input type="hidden" id="idAlat" name="IDAlat">
            <input type="hidden" id="gambarLama" name="gambarLama">

            <div class="form-group">
              <label>Nama Alat <span class="text-danger">*</span></label>
              <input type="text" name="AlatNama" class="form-control" required minlength="3" maxlength="100">
            </div> 
            <div class="form-group">
              <label>Gambar <span class="text-danger">*</span></label> 
 
              <div id="previewContainer" class="text-center mb-4" style="display:none;">
                <img id="previewImg" src="" alt="Preview" style="max-height:220px; border-radius:12px; box-shadow:0 6px 20px rgba(0,0,0,0.18);">
                <p class="mt-2 text-success"><small id="previewText">Preview gambar</small></p>
              </div>
 
              <div class="input-group">
                <input type="text" class="form-control" id="fileNameDisplay" placeholder="Belum ada file dipilih" readonly>
                <button type="button" class="btn btn-sm btn-danger" id="btnHapusGambar" style="display:none;" title="Hapus gambar">
                  <i class="fas fa-times" style="font-size:12px;"></i>
                </button>
                <label for="gambar_alat" class="btn btn-primary">
                  <i class="fas fa-camera me-1"></i> Browse
                </label>
              </div>

              <input type="file" name="gambar" id="gambar_alat" accept="image/*" style="display:none;">
              <small class="text-muted mt-2 d-block">Maksimal 5MB, format: JPG</small>
            </div>

            <div class="form-group">
              <label>Kategori <span class="text-danger">*</span></label>
              <select name="AlatKategori" class="form-control" required>
                <option value="">Pilih Kategori</option>
                <option value="Proyektor">Proyektor</option>
                <option value="Audio">Audio</option>
                <option value="Kamera">Kamera</option>
                <option value="Aksesoris">Aksesoris</option>
                <option value="Pencahayaan">Pencahayaan</option>
                <option value="Display">Display</option>
                <option value="Drone">Drone</option>
                <option value="Lainnya">Lainnya</option>
              </select>
            </div>

            <div class="form-group">
              <label>Deskripsi</label>
              <textarea name="AlatDeskripsi" class="form-control" rows="3" maxlength="500"></textarea>
            </div>

            <div class="form-group">
              <label>Harga (Rp) <span class="text-danger">*</span></label>
              <input type="number" name="AlatHarga" class="form-control" required min="0" step="1000">
            </div>

            <div class="form-group">
              <label>Stok <span class="text-danger">*</span></label>
              <input type="text" name="AlatStok" class="form-control" maxlength="50">
            </div>

            <div class="form-group">
              <label>Status <span class="text-danger">*</span></label>
              <select name="AlatStatus" class="form-control" required>
                <option value="Tersedia">Tersedia</option>
                <option value="Rusak">Rusak</option>
                <option value="Dipinjam">Dipinjam</option>
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

  <script>
    const modal = document.getElementById('layananModal');
    const form = document.getElementById('formLayanan');

        document.addEventListener('DOMContentLoaded', function() {
            modal.style.display = 'none'; 
            document.body.style.overflow = 'auto'; 
        });
        function openTambahPopup() {
        document.getElementById('modalTitle').textContent = 'Tambah Layanan';
        form.action = 'tambah_alat.php';
        form.reset();
        document.getElementById('idAlat').value = '';
        document.getElementById('gambarLama').value = '';
        document.getElementById('fileNameDisplay').value = '';
        document.getElementById('btnHapusGambar').style.display = 'none';
        document.getElementById('previewContainer').style.display = 'none';
        modal.style.display = 'flex'; 
    }

    function openEditPopup(data) {
      document.getElementById('modalTitle').textContent = 'Edit Layanan';
      form.action = 'edit_alat.php';
      document.getElementById('idAlat').value = data.IDAlat;
      form.AlatNama.value = data.AlatNama;
      form.AlatKategori.value = data.AlatKategori;
      form.AlatDeskripsi.value = data.AlatDeskripsi;
      form.AlatHarga.value = data.AlatHarga;
      form.AlatStok.value = data.AlatStok;
      form.AlatStatus.value = data.AlatStatus;

      const imgPath = data.AlatDirGbr;
      if (imgPath && imgPath.trim() !== '') {
        document.getElementById('gambarLama').value = imgPath;
        previewImg.src = '/artefax/Paket/img/produk/' + imgPath;
        previewContainer.style.display = 'block';
        document.getElementById('previewText').textContent = 'Preview Gambar';
        document.getElementById('fileNameDisplay').value = imgPath.split('/').pop();
        document.getElementById('btnHapusGambar').style.display = 'block';
      } else {
        resetGambarPreview();
      }
      modal.style.display = 'flex';
    }

    function closeModal() {
      modal.style.display = 'none';
      document.body.style.overflow = 'auto';
    }

    function resetGambarPreview() {
      document.getElementById('gambar_alat').value = '';
      document.getElementById('fileNameDisplay').value = 'Belum ada file dipilih';
      document.getElementById('btnHapusGambar').style.display = 'none';
      document.getElementById('previewContainer').style.display = 'none';
      document.getElementById('gambarLama').value = '';
    }

    // Preview Gambar Baru
    document.getElementById('gambar_alat').addEventListener('change', function() {
      const file = this.files[0];
      if (!file) {
        resetGambarPreview();
        return;
      }

      if (file.size > 5 * 1024 * 1024) {
        alert('Ukuran maksimal 5MB!');
        resetGambarPreview();
        return;
      }

      document.getElementById('fileNameDisplay').value = file.name;
      document.getElementById('btnHapusGambar').style.display = 'block';

      const reader = new FileReader();
      reader.onload = function(e) {
        document.getElementById('previewImg').src = e.target.result;
        document.getElementById('previewContainer').style.display = 'block';
        document.getElementById('previewText').textContent = 'Preview gambar';
      };
      reader.readAsDataURL(file);
    });

    document.getElementById('btnHapusGambar').addEventListener('click', resetGambarPreview);
    modal.addEventListener('click', e => {
      if (e.target === modal) closeModal();
    });

    // Tutup saat klik luar
    window.addEventListener('click', function(e) {
      if (e.target === modal) {
        closeModal();
      }
    });
    form.addEventListener('submit', function() {
      setTimeout(function() {
        closeModal();
      }, 100);
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

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const lightbox = document.getElementById('gambarLightbox');
      const lightboxImg = document.getElementById('lightboxImg');
      const lightboxJudul = document.getElementById('lightboxJudul');
      const closeBtn = document.querySelector('.lightbox-close');
      const basePath = '/artefax/Paket/img/produk/';

      // Buka lightbox saat tombol Detail diklik
      document.querySelectorAll('.btn-detail-gambar').forEach(btn => {
        btn.addEventListener('click', function() {
          const imgFile = this.getAttribute('data-img');
          const nama = this.getAttribute('data-nama');
          const fullPath = basePath + imgFile.trim();

          console.log('Mencoba load gambar:', fullPath);

          lightboxJudul.textContent = nama;
          lightboxImg.src = fullPath;
          lightbox.style.display = 'flex';

          lightboxImg.onerror = function() {
            lightboxImg.src = '';
            lightboxJudul.textContent = 'Gambar tidak ditemukan!';
            console.error('Gagal load:', fullPath);
          }
        });
      });


      closeBtn.onclick = () => {
        lightbox.style.display = 'none';
        lightboxImg.src = '';
      };


      lightbox.onclick = (e) => {
        if (e.target === lightbox) {
          lightbox.style.display = 'none';
          lightboxImg.src = '';
        }
      };

      // Tutup dengan tombol ESC
      document.onkeyup = (e) => {
        if (e.key === 'Escape' && lightbox.style.display === 'flex') {
          lightbox.style.display = 'none';
          lightboxImg.src = '';
        }
      };
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