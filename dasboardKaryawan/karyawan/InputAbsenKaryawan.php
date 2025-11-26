<?php
session_start();
require_once '../../config/koneksi.php'; 
require_once '../../class/Absensi.php';

$db = new Database();
$conn = $db->getConnection();
if (!$conn) die("<script>alert('Gagal terhubung ke database!');</script>");

if (!isset($_SESSION['user']) || $_SESSION['user']['UserRole'] !== 'Karyawan') {
    header('Location: ../../View/login.php');
    exit();
}

$userData     = $_SESSION['user'];
$namaKaryawan = $userData['UserNama'];
$idKaryawan   = $userData['IDUser'];

// === Cari event aktif ===
$sqlEvent = "
    SELECT e.IDEvent, e.EventNama 
    FROM event e
    JOIN event_karyawan ek ON e.IDEvent = ek.IDEvent
    WHERE ek.IDKaryawan = ? AND e.EventStatus = 'Berjalan'
    ORDER BY e.EventTanggal DESC, e.EventMulai DESC
    LIMIT 1
";
$stmt = $conn->prepare($sqlEvent);
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
    $latitude   = $_POST['latitude'] ?? '';
    $longitude  = $_POST['longitude'] ?? '';
    $fotoData   = $_POST['foto'] ?? '';
    $clientTime = $_POST['client_time'] ?? '';

    if (empty($fotoData)) {
        $errorMsg = "Ambil foto dulu ya!";
    } else {
        // Simpan foto
        if (preg_match('/^data:image\/(\w+);base64,/', $fotoData, $type)) {
            $data = substr($fotoData, strpos($fotoData, ',') + 1);
            $type = strtolower($type[1]);
            $data = base64_decode($data);
            if (!is_dir('../../uploads')) mkdir('../../uploads', 0777, true);
            $fileName = 'absensi_'.$idKaryawan.'_'.time().'.'.$type;
            file_put_contents('../../uploads/'.$fileName, $data);
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

    <!-- SweetAlert2 harus di-load DULU -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        .no-event-notif, .sudah-absen-notif {
            text-align: center;
            padding: 50px 30px;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            border: 2px solid #e2e8f0;
            margin: 30px 0;
            color: #4a5568;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .no-event-notif i {font-size:70px;color:#a0aec0;margin-bottom:20px;}
        .no-event-notif h3, .sudah-absen-notif h3 {color:#2d3748;font-size:24px;margin-bottom:12px;}
        .sudah-absen-notif {background:rgba(16,185,129,0.15);border:2px solid #10b981;color:#065f46;}
        .sudah-absen-notif h3 {color:#10b981;}
        .sudah-absen-notif i {font-size:70px;color:#10b981;margin:20px 0;}

        /* PERBAIKAN MIRROR - Hanya tambahan ini */
        #kamera, #preview {
            transform: scaleX(-1);
            display: block;
        }
    </style>
</head>
<body class="az-body">

    <!-- HEADER -->
    <div class="az-header">
        <div class="container">
            <div class="az-header-left">
                <a href="index.php" class="az-logo"><span>artefax</span></a>
            </div>
            <div class="az-header-menu">
                <ul class="nav">
                    <li class="nav-item"><a href="index.php" class="nav-link">Penugasan</a></li>
                    <li class="nav-item active"><a href="InputAbsenKaryawan.php" class="nav-link">Absensi</a></li>
                    <li class="nav-item"><a href="LaporanPenugasan.php" class="nav-link">Laporan</a></li>
                </ul>
            </div>
            <div class="az-header-right">
                <div class="dropdown az-profile-menu">
                    <a href="" class="az-img-user"><img src="../img/faces/face1.jpg" alt=""></a>
                    <div class="dropdown-menu">
                        <div class="az-header-profile">
                            <div class="az-img-user"><img src="../img/faces/face1.jpg" alt=""></div>
                            <h6><?= htmlspecialchars($namaKaryawan) ?></h6>
                            <span>Karyawan</span>
                        </div>
                        <a href="../../logout.php" class="dropdown-item">Keluar</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- CONTENT -->
    <div class="az-content">
        <div class="az-content-body d-flex justify-content-center align-items-center min-vh-100">
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

    <!-- JS harus di-load SETELAH SweetAlert2 -->
    <script src="../lib/jquery/jquery.min.js"></script>
    <script src="../lib/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../js/azia.js"></script>

    <?php if ($adaEventAktif && !$sudahAbsen): ?>
    <script>
        // Update waktu lokal tiap detik
        function updateClientTime() {
            const now = new Date();
            const time = now.getFullYear() + '-' +
                String(now.getMonth()+1).padStart(2,'0') + '-' +
                String(now.getDate()).padStart(2,'0') + ' ' +
                String(now.getHours()).padStart(2,'0') + ':' +
                String(now.getMinutes()).padStart(2,'0') + ':' +
                String(now.getSeconds()).padStart(2,'0');
            document.getElementById('client_time').value = time;
        }
        setInterval(updateClientTime, 1000);
        updateClientTime();

        // Kamera
        navigator.mediaDevices.getUserMedia({ video: { facingMode: "user" } })
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

            ctx.clearRect(0, 0, canvas.width, canvas.height);

            // Karena video sudah di-mirror oleh CSS, kita gambar ULANG dengan flip lagi
            // agar hasilnya menjadi NORMAL (tidak terbalik)
            ctx.scale(-1, 1);
            ctx.drawImage(video, -canvas.width, 0, canvas.width, canvas.height);

            // Reset transform supaya toDataURL tidak error
            ctx.setTransform(1, 0, 0, 1, 0, 0);

            document.getElementById('foto').value = canvas.toDataURL('image/jpeg', 0.8);
        };
    </script>
    <?php endif; ?>

    <!-- Notifikasi hasil absensi -->
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

</body>
</html>