<?php
session_start();
require_once '../../config/koneksi.php';
require_once '../../class/Absensi.php';

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
$conn = null;
try {
    $conn = $db->getConnection();
} catch (Exception $e) {
    // Tangani pengecualian jika koneksi gagal sebelum die()
}

// ✅ PERBAIKAN: Cek koneksi yang lebih aman
if ($conn === null) {
    // Jika koneksi gagal, hentikan skrip dan tampilkan pesan
    die("<script>alert('Gagal terhubung ke database!');</script>");
}

// === AMBIL DATA ABSENSI PER KARYAWAN ===
$absensi = new Absensi($conn);
$riwayatAbsensi = [];

$query = "
    SELECT 
        p.IDPresensi, 
        p.PsnWaktu, 
        p.PsnLokasi, 
        p.PsnFoto, 
        p.PsnStatus,
        e.EventNama
    FROM presensi p
    LEFT JOIN event e ON e.IDEvent = p.IDEvent
    WHERE p.IDUser = ?
    ORDER BY p.PsnWaktu DESC
";

$stmt = $conn->prepare($query);
if ($stmt) {
    $stmt->bind_param("i", $idKaryawan);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $row['Tanggal'] = date('d M Y', strtotime($row['PsnWaktu']));
        $row['Jam'] = date('H:i', strtotime($row['PsnWaktu']));
        $riwayatAbsensi[] = $row;
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Laporan Absensi - <?= htmlspecialchars($namaKaryawan) ?> | Artefax</title>

    <link href="../lib/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="../css/azia.css" rel="stylesheet">
    <link href="../lib/bootstrap/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background: #f6f8fb;
            font-family: "Poppins", sans-serif;
            color: #1a1a1a;
        }

        /* ✅ PERBAIKAN: Fixed Header */
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
        .az-header-right {
            display: flex;
            align-items: center;
            gap: 10px; /* Jarak antar elemen di header kanan */
        }
        .az-logo {
            font-size: 20px;
            font-weight: 700;
            color: #4b4be5;
            text-decoration: none;
        }
        
        /* Gaya tambahan untuk ikon profil */
        .az-profile-icon {
            font-size: 28px;
            color: #4b4be5 !important; 
            transition: color 0.2s;
        }
        .az-profile-icon:hover {
            color: #3a3ad1;
        }
        
        /* Style untuk tombol logout di navbar */
        .btn-logout-nav {
            padding: 5px 10px;
            font-size: 13px;
            display: flex;
            align-items: center;
        }
        .btn-logout-nav i {
            margin-right: 5px;
        }


        /* Navbar aktif */
        .az-header-menu {
             display: flex;
        }
        .az-header-menu .nav-link {
            position: relative;
            color: #1a1a1a;
            font-weight: 500;
            padding: 0 15px;
        }

        .az-header-menu .nav-link::after {
            content: "";
            position: absolute;
            bottom: -6px;
            left: 50%;
            width: 0;
            height: 3px;
            background: #4b4be5;
            transition: all 0.3s;
            transform: translateX(-50%);
        }

        .az-header-menu .nav-item.active .nav-link::after, /* Menggunakan .active */
        .az-header-menu .nav-link:hover::after {
            width: 70%;
        }

        /* ✅ PERBAIKAN: Menambahkan margin-top yang cukup agar konten tidak tertutup header */
        .az-content {
            margin-top: 60px; 
            padding-top: 25px; 
        }

        .az-content-body {
            display: flex;
            gap: 30px;
        }
        
        /* ✅ PERBAIKAN: Sticky Sidebar (Desktop Only) */
        .az-content-left {
            position: sticky;
            top: 85px; /* Jarak dari atas (Tinggi header + sedikit padding) */
            align-self: flex-start; /* Penting untuk sticky */
            
            background: #fff;
            border: 1px solid #e4e6eb;
            border-radius: 10px;
            padding: 20px;
            flex: 0 0 220px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
            /* Reset lebar di mobile di media query */
        }

        .az-content-left label {
            font-weight: 600;
            font-size: 13px;
            color: #333;
            border-bottom: 2px solid #007bff;
            padding-bottom: 5px;
            display: block;
            margin-bottom: 15px;
        }

        .az-content-left .nav-link {
            display: block;
            padding: 8px 0;
            color: #444;
            font-size: 14px;
        }

        .az-content-left .nav-link:hover,
        .az-content-left .nav-link.active {
            color: #007bff;
            font-weight: 600;
        }


        /* TABEL */
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

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
            border-radius: 10px;
            overflow: hidden;
        }
        /* ... (Styling tabel dan badge tidak diubah) ... */
        
        /* CSS untuk Mobile (Hamburger) */
        .az-menu-toggle {
            display: none; /* Sembunyikan di desktop */
            font-size: 24px;
            cursor: pointer;
            color: #4b4be5;
            padding: 0 10px;
        }
        
        /* Mobile Menu Style */
        .az-mobile-menu {
            position: absolute;
            top: 100%;
            right: 0;
            background-color: #fff;
            border: 1px solid #e4e6eb;
            box-shadow: 0 5px 10px rgba(0, 0, 0, 0.1);
            z-index: 999;
            padding: 10px;
            min-width: 150px;
            display: none; /* Default hidden */
            flex-direction: column;
        }

        @media (max-width: 991px) {
            .az-content-body {
                flex-direction: column;
            }

            /* ✅ PERBAIKAN: Sidebar di Mobile menjadi full width dan tidak sticky */
            .az-content-left {
                width: 100%;
                position: static;
                margin-bottom: 20px;
            }
            
            /* Sembunyikan menu navigasi default di mobile */
            .az-header-menu {
                display: none; 
            }
            
            /* Tampilkan hamburger menu */
            .az-menu-toggle {
                display: block;
                order: 3; 
                margin-left: 10px; 
            }
        }
    </style>
</head>

<body>

    <div class="az-header">
        <div class="container">
            <div class="az-header-left">
                <a href="index.php" class="az-logo">artefax</a>
            </div>
            
            <div class="az-header-menu" id="desktopMenu">
                <ul class="nav">
                    <li class="nav-item"><a href="index.php" class="nav-link">Penugasan</a></li>
                    <li class="nav-item"><a href="InputAbsenKaryawan.php" class="nav-link">Absensi</a></li>
                    <li class="nav-item active"><a href="LaporanPenugasan.php" class="nav-link">Laporan</a></li>
                </ul>
            </div>
            
            <div class="az-header-right">
                <a href="../../view/profil.php" title="Profil Saya" class="d-flex align-items-center">
                    <i class="fas fa-user-circle az-profile-icon"></i>
                </a>
                
                <a href="../../logout.php" class="btn btn-sm btn-danger btn-logout-nav">
                    <i class="fas fa-sign-out-alt"></i> Keluar
                </a>

                <i class="fas fa-bars az-menu-toggle" id="azMenuToggle"></i>
            </div>
        </div>
        
        <div class="az-mobile-menu" id="mobileMenu">
            <ul class="nav flex-column">
                <li class="nav-item"><a href="index.php" class="nav-link">Penugasan</a></li>
                <li class="nav-item"><a href="InputAbsenKaryawan.php" class="nav-link">Absensi</a></li>
                <li class="nav-item"><a href="LaporanPenugasan.php" class="nav-link">Laporan</a></li>
            </ul>
        </div>
    </div>

    <div class="az-content pd-y-20 pd-lg-y-30 pd-xl-y-40">
        <div class="container">
            <div class="az-content-body">

                <div class="az-content-left">
                    <label>Menu Karyawan</label>
                    <nav class="nav flex-column">
                        <a href="LaporanPenugasan.php" class="nav-link">Laporan Penugasan Selesai</a>
                        <a href="LaporanAbsensi.php" class="nav-link active">Laporan Absensi</a>
                    </nav>
                </div>

                <div class="table-wrapper">
                    <h2><i class="fas fa-user-check"></i> Laporan Absensi</h2>

                    <?php if (empty($riwayatAbsensi)): ?>
                        <div class="text-center text-secondary py-5">
                            <i class="fas fa-calendar-times fa-3x mb-2"></i>
                            <p>Belum ada data absensi.</p>
                        </div>
                    <?php else: ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Event</th>
                                    <th>Tanggal</th>
                                    <th>Jam</th>
                                    <th>Lokasi</th>
                                    <th>Foto</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($riwayatAbsensi as $a): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($a['EventNama'] ?? '—') ?></td>
                                        <td><?= htmlspecialchars($a['Tanggal']) ?></td>
                                        <td><?= htmlspecialchars($a['Jam']) ?></td>
                                        <td><?= htmlspecialchars($a['PsnLokasi'] ?? '-') ?></td>
                                        <td>
                                            <?php if (!empty($a['PsnFoto'])): ?>
                                                <button class="btn-foto" data-toggle="modal" data-target="#fotoModal" data-foto="<?= htmlspecialchars($a['PsnFoto']) ?>">
                                                    <i class="fas fa-camera"></i> Lihat
                                                </button>
                                            <?php else: ?>
                                                <span class="text-muted">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php
                                            $status = strtolower($a['PsnStatus']);
                                            $cls = $status === 'hadir' ? 'badge-hadir'
                                                : ($status === 'izin' ? 'badge-izin'
                                                : ($status === 'sakit' ? 'badge-sakit' : 'badge-tidak'));
                                            ?>
                                            <span class="badge-status <?= $cls ?>"><?= ucfirst($status) ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="fotoModal" tabindex="-1" role="dialog" aria-labelledby="fotoModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-body text-center">
                    <img id="modalFoto" src="" alt="Foto Absensi" class="modal-img">
                </div>
            </div>
        </div>
    </div>

    <script src="../lib/jquery/jquery.min.js"></script>
    <script src="../lib/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            // Fungsionalitas Modal Foto
            $('#fotoModal').on('show.bs.modal', function(event) {
                var button = $(event.relatedTarget);
                var fotoPath = button.data('foto');
                var modal = $(this);
                // Pastikan path ke folder uploads sudah benar relatif terhadap LaporanAbsensi.php
                modal.find('#modalFoto').attr('src', '../../uploads/' + fotoPath);
            });
            
            // JAVASCRIPT UNTUK HAMBURGER MENU:
            document.getElementById('azMenuToggle').addEventListener('click', function() {
                const mobileMenu = document.getElementById('mobileMenu');
                // Toggle display flex/none
                if (mobileMenu.style.display === 'flex') {
                    mobileMenu.style.display = 'none';
                } else {
                    mobileMenu.style.display = 'flex';
                }
            });

            // Hapus style inline saat resize ke desktop
            window.addEventListener('resize', function() {
                if (window.innerWidth > 991) {
                    document.getElementById('desktopMenu').style.cssText = '';
                    document.getElementById('mobileMenu').style.display = 'none'; // Sembunyikan mobile menu di desktop
                }
            });
        });
    </script>

</body>

</html>