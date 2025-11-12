<?php
session_start();
require_once '../../config/koneksi.php'; 
require_once '../../class/Absensi.php';

// === Koneksi database ===
$db = new Database();
$conn = $db->getConnection();
if (!$conn) {
    die("<script>alert('Gagal terhubung ke database!');</script>");
}

// === Cek login ===
if (!isset($_SESSION['user']) || $_SESSION['user']['UserRole'] !== 'Karyawan') {
    header('Location: ../../View/login.php');
    exit();
}

$userData = $_SESSION['user'];
$namaKaryawan = $userData['UserNama'];
$idKaryawan = $userData['IDUser'];

// === Ambil event aktif ===
$sqlEvent = "SELECT IDEvent, EventNama 
             FROM event 
             WHERE IDKaryawan = ? 
             AND EventStatus = 'Berjalan' 
             ORDER BY EventTanggal DESC LIMIT 1";
$stmt = $conn->prepare($sqlEvent);
$stmt->bind_param("i", $idKaryawan);
$stmt->execute();
$result = $stmt->get_result();
$eventAktif = $result->fetch_assoc();
$stmt->close();

if (!$eventAktif) {
    echo "<script>alert('Tidak ada event aktif untuk Anda saat ini.'); window.location='index.php';</script>";
    exit();
}

$idEventAktif = $eventAktif['IDEvent'];
$namaEvent = $eventAktif['EventNama'];

// === Proses absensi ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $latitude = $_POST['latitude'] ?? '';
    $longitude = $_POST['longitude'] ?? '';
    $fotoData = $_POST['foto'] ?? '';

    // === Cek apakah sudah absen ===
    $sqlCek = "SELECT 1 FROM presensi WHERE IDUser = ? AND IDEvent = ?";
    $stmtCek = $conn->prepare($sqlCek);
    $stmtCek->bind_param("ii", $idKaryawan, $idEventAktif);
    $stmtCek->execute();
    $stmtCek->store_result();

    if ($stmtCek->num_rows > 0) {
        echo "<!DOCTYPE html>
        <html lang='id'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1'>
            <title>Sudah Absen</title>
            <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
        </head>
        <body>
        <script>
            Swal.fire({
                icon: 'info',
                title: 'Anda Sudah Absen!',
                text: 'Anda telah melakukan absensi pada event ini sebelumnya.',
                confirmButtonColor: '#5d5fef'
            }).then(() => {
                window.location='InputAbsenKaryawan.php';
            });
        </script>
        </body>
        </html>";
        exit();
    }
    $stmtCek->close();

    if (empty($fotoData)) {
        echo "<script>alert('Silakan ambil foto terlebih dahulu sebelum absen.'); window.history.back();</script>";
        exit();
    }

    if (preg_match('/^data:image\/(\w+);base64,/', $fotoData, $type)) {
        $data = substr($fotoData, strpos($fotoData, ',') + 1);
        $type = strtolower($type[1]);
        $data = base64_decode($data);

        if (!is_dir('../../uploads')) {
            mkdir('../../uploads', 0777, true);
        }

        $fileName = 'absensi_' . $idKaryawan . '_' . time() . '.' . $type;
        $filePath = '../../uploads/' . $fileName;
        file_put_contents($filePath, $data);
    }

    $lokasiString = (!empty($latitude) && !empty($longitude))
        ? "Lat: {$latitude}, Lon: {$longitude}"
        : "Tidak terdeteksi";

    $absensi = new Absensi($conn);
    $absensi->IDUser = $idKaryawan;
    $absensi->IDEvent = $idEventAktif;
    $absensi->PsnWaktu = date('Y-m-d H:i:s');
    $absensi->PsnLokasi = $lokasiString;
    $absensi->PsnFoto = $fileName;
    $absensi->PsnStatus = 'Hadir';

    if ($absensi->tambah()) {
        echo "<script>alert('Absensi berhasil untuk event: {$namaEvent}!'); window.location='InputAbsenKaryawan.php';</script>";
        exit();
    } else {
        echo "<script>alert('Gagal menyimpan absensi ke database.');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Absensi - <?= htmlspecialchars($namaKaryawan) ?> | Artefax</title>

    <!-- Eksternal CSS -->
    <link rel="stylesheet" href="../css/absensi.css">

    <!-- Library -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

    <div class="az-header">
        <div class="container">
            <div class="az-header-left">
                <a href="index.php" class="az-logo"><span></span> artefax</a>
            </div>
            <div class="az-header-menu">
                <ul class="nav">
                    <li class="nav-item"><a href="index.php" class="nav-link">Penugasan</a></li>
                    <li class="nav-item"><a href="InputAbsenKaryawan.php" class="nav-link">Absensi</a></li>
                    <li class="nav-item active"><a href="LaporanPenugasan.php" class="nav-link">Laporan</a></li>
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
                        <a href="../../logout.php" class="dropdown-item"><i class="typcn typcn-power"></i> Keluar</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

<main class="container-main">
    <section class="absensi-card">
        <h2>Absensi Event: <span><?= htmlspecialchars($namaEvent) ?></span></h2>

        <button id="ambilLokasi" class="btn btn-primary">📍 Deteksi Lokasi</button>
        <p id="lokasi">Belum terdeteksi...</p>

        <div class="camera-wrapper">
            <div class="camera-box">
                <video id="kamera" autoplay playsinline></video>
                <button id="ambilFoto" class="btn btn-success">📸 Ambil Foto</button>
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
            <button type="submit" class="btn btn-info">✅ Kirim Absensi</button>
        </form>
    </section>
</main>

<script>
const video = document.getElementById('kamera');
const canvas = document.getElementById('preview');
const lokasiTeks = document.getElementById('lokasi');

// Aktifkan kamera
navigator.mediaDevices.getUserMedia({ video: true })
    .then(stream => { video.srcObject = stream; })
    .catch(err => Swal.fire("Error", "Kamera tidak dapat diakses: " + err, "error"));

// Ambil lokasi
document.getElementById('ambilLokasi').onclick = (e) => {
    e.preventDefault();
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(pos => {
            const lat = pos.coords.latitude.toFixed(6);
            const lon = pos.coords.longitude.toFixed(6);
            document.getElementById('latitude').value = lat;
            document.getElementById('longitude').value = lon;
            lokasiTeks.textContent = `Latitude: ${lat}, Longitude: ${lon}`;
            lokasiTeks.classList.add("detected");
        }, () => Swal.fire("Error", "Tidak dapat mendeteksi lokasi.", "warning"));
    }
};

// Ambil foto
document.getElementById('ambilFoto').onclick = (e) => {
    e.preventDefault();
    const ctx = canvas.getContext('2d');
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
    document.getElementById('foto').value = canvas.toDataURL('image/png');
};
</script>
</body>
</html>
