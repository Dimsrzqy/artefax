<?php
session_start();
require_once '../../config/koneksi.php';
require_once '../../class/absensi.php';

// === CEK LOGIN ===
if (!isset($_SESSION['user']) || $_SESSION['user']['UserRole'] !== 'Karyawan') {
    header('Location: ../../View/login.php');
    exit();
}

$userData     = $_SESSION['user'];
$namaKaryawan = $userData['UserNama'];
$idKaryawan   = $userData['IDUser'];

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

// === Cari event aktif ===
// Baris 25 ada di sini:
$sqlEvent = "
    SELECT e.IDEvent, e.EventNama 
    FROM event e
    JOIN event_karyawan ek ON e.IDEvent = ek.IDEvent
    WHERE ek.IDKaryawan = ? AND e.EventStatus = 'Berjalan'
    ORDER BY e.EventTanggal DESC, e.EventMulai DESC
    LIMIT 1
";
$stmt = $conn->prepare($sqlEvent); // ERROR terjadi jika $conn adalah NULL
$stmt->bind_param("i", $idKaryawan);
$stmt->execute();
$result = $stmt->get_result();
$eventAktif = $result->fetch_assoc();
$stmt->close();

// Variabel default
$namaEvent    = '';
$idEventAktif = 0;
$sudahAbsen   = false;
$adaEventAktif = ($eventAktif !== null);
$errorMsg = ''; // Pastikan variabel errorMsg diinisialisasi

if ($adaEventAktif) {
    $idEventAktif = $eventAktif['IDEvent'];
    $namaEvent    = $eventAktif['EventNama'];

    $cekAbsen = $conn->prepare("SELECT 1 FROM presensi WHERE IDUser = ? AND IDEvent = ?");
    $cekAbsen->bind_param("ii", $idKaryawan, $idEventAktif);
    $cekAbsen->execute();
    $cekAbsen->store_result();
    $sudahAbsen = $cekAbsen->num_rows > 0;
    $cekAbsen->close();
}

// === Proses absensi ===
$absenBerhasil = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $adaEventAktif && !$sudahAbsen) {
    $latitude    = $_POST['latitude'] ?? '';
    $longitude   = $_POST['longitude'] ?? '';
    $fotoData    = $_POST['foto'] ?? '';
    $clientTime  = $_POST['client_time'] ?? '';

    if (empty($fotoData)) {
        $errorMsg = "Ambil foto dulu ya!";
    } else {
        // Simpan foto
        if (preg_match('/^data:image\/(\w+);base64,/', $fotoData, $type)) {
            $data = substr($fotoData, strpos($fotoData, ',') + 1);
            $type = strtolower($type[1]);
            $data = base64_decode($data);
            if (!is_dir('../../uploads')) mkdir('../../uploads', 0777, true);
            $fileName = 'absensi_' . $idKaryawan . '_' . time() . '.' . $type;
            file_put_contents('../../uploads/' . $fileName, $data);
        } else {
            $fileName = '';
        }

        $lokasiString = (!empty($latitude) && !empty($longitude))
            ? "Lat: {$latitude}, Lon: {$longitude}"
            : "Tidak terdeteksi";

        $absensi = new Absensi($conn);
        $absensi->IDUser    = $idKaryawan;
        $absensi->IDEvent   = $idEventAktif;
        $absensi->PsnWaktu  = ($clientTime && preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $clientTime)) ? $clientTime : date('Y-m-d H:i:s');
        $absensi->PsnLokasi = $lokasiString;
        $absensi->PsnFoto   = $fileName;
        $absensi->PsnStatus = 'Hadir';

        if ($absensi->tambah()) {
            $absenBerhasil = true;
        } else {
            $errorMsg = "Gagal menyimpan absensi.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Absensi - <?= htmlspecialchars($namaKaryawan) ?> | Artefax</title>

    <link href="../lib/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="../css/azia.css" rel="stylesheet">
    <link href="../css/karyawan.css" rel="stylesheet">
    <link href="../css/absensi.css" rel="stylesheet">
    <link href="../lib/bootstrap/css/bootstrap.min.css" rel="stylesheet">

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        /* ✅ PERBAIKAN: Background Biru Penuh */
        .az-body {
            /* Warna dan gradien yang konsisten dengan template Azia/Login */
            background: linear-gradient(135deg, #5c99ee 0%, #4c89de 100%);
            height: 100vh; /* Memastikan body mengisi seluruh viewport */
            margin: 0;
            overflow-y: auto; /* Mengizinkan scroll jika konten melebihi 100vh */
        }
        
        /* ✅ PERBAIKAN: Fixed Header */
        .az-header {
            position: fixed; 
            top: 0;
            width: 100%;
            z-index: 1030;
            background-color: #ffffff; 
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            padding: 10px 0; /* Sesuaikan padding */
        }
        /* Memberikan padding pada body agar konten tidak tertutup header */
        .az-body {
            padding-top: 60px; /* Nilai ini disesuaikan dengan tinggi header Azia */
        }
        /* Mengatur kontainer konten agar terpusat di latar belakang biru */
        .az-content {
            padding-top: 20px;
            padding-bottom: 20px;
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
            /* margin-left akan diatur di az-header-right */
            display: flex;
            align-items: center;
        }
        .btn-logout-nav i {
            margin-right: 5px; /* Jarak antara ikon dan teks */
        }
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
        .az-mobile-menu .nav-item {
            padding: 5px 10px;
            border-bottom: 1px solid #eee;
        }
        .az-mobile-menu .nav-item:last-child {
            border-bottom: none;
        }
        
        /* Pengaturan Layout Kanan Header */
        .az-header-right {
            display: flex;
            align-items: center;
            /* ✅ PERBAIKAN: Menambahkan margin di antara ikon profil dan tombol logout */
            gap: 10px; /* Jarak yang lebih baik antar elemen di header kanan */
        }

        /* Responsive Navbar */
        @media (max-width: 991px) {
            .az-header-menu {
                display: none; /* Sembunyikan menu desktop di mobile */
            }
            .az-menu-toggle {
                display: block;
                order: 3; 
                margin-left: 10px; 
            }
        }
        
        /* Notif dan Kamera CSS (TIDAK BERUBAH) */
        .no-event-notif, .sudah-absen-notif {
            text-align: center;
            padding: 50px 30px;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            border: 2px solid #e2e8f0;
            margin: 30px auto; 
            max-width: 500px; 
            color: #4a5568;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        .no-event-notif i {
            font-size: 70px;
            color: #a0aec0;
            margin-bottom: 20px;
        }
        .no-event-notif h3, .sudah-absen-notif h3 {
            color: #2d3748;
            font-size: 24px;
            margin-bottom: 12px;
        }
        .sudah-absen-notif {
            background: rgba(16, 185, 129, 0.15);
            border: 2px solid #10b981;
            color: #065f46;
        }
        .sudah-absen-notif h3 {
            color: #10b981;
        }
        .sudah-absen-notif i {
            font-size: 70px;
            color: #10b981;
            margin: 20px 0;
        }
        #kamera {
            transform: scaleX(-1);
            display: block;
        }
        #preview {
            transform: none; 
            display: block;
        }
        .az-content-body.d-flex {
            min-height: calc(100vh - 60px); 
        }
    </style>
</head>

<body class="az-body">

    <div class="az-header">
        <div class="container">
            <div class="az-header-left">
                <a href="index.php" class="az-logo"><span>artefax</span></a>
            </div>
            
            <div class="az-header-menu" id="desktopMenu">
                <ul class="nav">
                    <li class="nav-item"><a href="index.php" class="nav-link">Penugasan</a></li>
                    <li class="nav-item active"><a href="InputAbsenKaryawan.php" class="nav-link">Absensi</a></li>
                </ul>
            </div>
            
            <div class="az-header-right">
                <a href="../../view/profile.php" title="Profil Saya" class="d-flex align-items-center">
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
            </ul>
        </div>
    </div>

    <div class="az-content">
        <div class="az-content-body d-flex justify-content-center align-items-center">
            <div class="absensi-card">

                <?php if (!$adaEventAktif): ?>
                    <div class="no-event-notif">
                        <i class="fas fa-calendar-times"></i>
                        <h3>Tidak Ada Event yang Sedang Berlangsung</h3>
                        <p>Saat ini Anda belum ditugaskan pada event apapun yang berstatus <strong>Berjalan</strong>.</p>
                        <p>Silakan hubungi admin jika ada kendala.</p>
                    </div>

                <?php elseif ($sudahAbsen): ?>
                    <div class="sudah-absen-notif">
                        <i class="fas fa-check-circle"></i>
                        <h3>Absensi Sudah Tercatat</h3>
                        <p>Terima kasih <strong><?= htmlspecialchars($namaKaryawan) ?></strong>,<br>
                            Anda sudah melakukan absensi untuk event:</p>
                        <h4 style="margin:15px 0;color:#10b981;font-weight:700;"><?= htmlspecialchars($namaEvent) ?></h4>
                        <p>Selamat bekerja!</p>
                    </div>

                <?php else: ?>
                    <h2>Absensi Event: <span><?= htmlspecialchars($namaEvent) ?></span></h2>

                    <button id="ambilLokasi" class="btn btn-primary">Deteksi Lokasi</button>
                    <p id="lokasi">Belum terdeteksi...</p>

                    <div class="camera-wrapper">
                        <div class="camera-box">
                            <video id="kamera" autoplay playsinline></video>
                            <button id="ambilFoto" class="btn btn-success">Ambil Foto</button>
                        </div>
                        <div class="preview-box">
                            <canvas id="preview"></canvas>
                            <small>Preview hasil foto</small>
                        </div>
                    </div>

                    <form id="formAbsensi" method="POST">
                        <input type="hidden" name="latitude" id="latitude">
                        <input type="hidden" name="longitude" id="longitude">
                        <input type="hidden" name="foto" id="foto">
                        <input type="hidden" name="client_time" id="client_time">
                        <button type="submit" class="btn btn-info mt-3">Kirim Absensi</button>
                    </form>
                <?php endif; ?>

            </div>
        </div>
    </div>

    <script src="../lib/jquery/jquery.min.js"></script>
    <script src="../lib/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../js/azia.js"></script>

    <?php if ($adaEventAktif && !$sudahAbsen): ?>
        <script>
            // Update waktu lokal tiap detik
            function updateClientTime() {
                const now = new Date();
                const time = now.getFullYear() + '-' +
                    String(now.getMonth() + 1).padStart(2, '0') + '-' +
                    String(now.getDate()).padStart(2, '0') + ' ' +
                    String(now.getHours()).padStart(2, '0') + ':' +
                    String(now.getMinutes()).padStart(2, '0') + ':' +
                    String(now.getSeconds()).padStart(2, '0');
                document.getElementById('client_time').value = time;
            }
            setInterval(updateClientTime, 1000);
            updateClientTime();

            // Kamera
            navigator.mediaDevices.getUserMedia({
                    video: {
                        facingMode: "user"
                    }
                })
                .then(stream => document.getElementById('kamera').srcObject = stream)
                .catch(err => Swal.fire("Error", "Kamera tidak dapat diakses: " + err.message, "error"));

            // Lokasi
            document.getElementById('ambilLokasi').onclick = e => {
                e.preventDefault();
                if (!navigator.geolocation) return Swal.fire("Error", "Geolocation tidak didukung", "error");
                navigator.geolocation.getCurrentPosition(pos => {
                    const lat = pos.coords.latitude.toFixed(6);
                    const lon = pos.coords.longitude.toFixed(6);
                    document.getElementById('latitude').value = lat;
                    document.getElementById('longitude').value = lon;
                    document.getElementById('lokasi').textContent = `Latitude: ${lat}, Longitude: ${lon}`;
                    document.getElementById('lokasi').classList.add("detected");
                }, () => Swal.fire("Error", "Gagal mendeteksi lokasi", "warning"));
            };

            // FOTO - PERBAIKAN UTAMA: hasil foto TIDAK mirror
            document.getElementById('ambilFoto').onclick = e => {
                e.preventDefault();
                const video = document.getElementById('kamera');
                const canvas = document.getElementById('preview');
                const ctx = canvas.getContext('2d');

                canvas.width = video.videoWidth || 640;
                canvas.height = video.videoHeight || 480;

                // 1. Reset/Clear canvas
                ctx.clearRect(0, 0, canvas.width, canvas.height);

                // 2. Set transform untuk membalik gambar secara horizontal
                // Karena video LIVE sudah di-flip (transform: scaleX(-1)) di CSS,
                // kita perlu mem-flip-nya LAGI di canvas agar hasilnya normal.
                ctx.translate(canvas.width, 0); // Pindahkan origin ke kanan
                ctx.scale(-1, 1); // Flip horizontal

                // 3. Gambar video (kini gambar akan terlihat normal/tidak mirror)
                ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

                // 4. Reset transform agar toDataURL tidak menyertakan transformasi
                ctx.setTransform(1, 0, 0, 1, 0, 0);

                document.getElementById('foto').value = canvas.toDataURL('image/jpeg', 0.8);
            };
        </script>
    <?php endif; ?>

    <?php if (!empty($absenBerhasil)): ?>
        <script>
            Swal.fire({
                icon: 'success',
                title: 'Absensi Berhasil!',
                text: 'Terima kasih, absensi Anda untuk event <?= addslashes($namaEvent) ?> telah tercatat.',
                timer: 3000,
                showConfirmButton: false
            }).then(() => location.reload());
        </script>
    <?php elseif (!empty($errorMsg)): ?>
        <script>
            Swal.fire('Gagal', '<?= addslashes($errorMsg) ?>', 'error');
        </script>
    <?php endif; ?>
    
    <script>
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
                document.querySelector('.az-header-menu').style.cssText = '';
                document.getElementById('mobileMenu').style.display = 'none'; // Sembunyikan mobile menu di desktop
            }
        });
    </script>

</body>

</html>