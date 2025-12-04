<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
header('Content-Type: application/json; charset=utf-8');

// Cek apakah user sudah login
if (!isset($_SESSION['user']['UserEmail'])) {
    echo json_encode([
        'success' => false, 
        'message' => 'Sesi login habis, silakan login kembali'
    ]);
    exit;
}

// Koneksi database - SESUAIKAN DENGAN CLASS DATABASE
require_once __DIR__ . '/../config/koneksi.php';

$database = new Database();
$connection = $database->getConnection();

if (!$connection) {
    echo json_encode([
        'success' => false, 
        'message' => 'Koneksi database gagal'
    ]);
    exit;
}

// Ambil email lama dari session
$oldEmail = $_SESSION['user']['UserEmail'];

// Ambil data dari form
$nama = trim($_POST['userNama'] ?? '');
$email = trim($_POST['userEmail'] ?? '');
$noHp = trim($_POST['userNoHp'] ?? '');
$alamat = trim($_POST['userAddress'] ?? '');

// ==========================================
// VALIDASI INPUT
// ==========================================
if (empty($nama)) {
    echo json_encode([
        'success' => false, 
        'message' => 'Nama tidak boleh kosong'
    ]);
    exit;
}

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        'success' => false, 
        'message' => 'Format email tidak valid'
    ]);
    exit;
}

// ==========================================
// CEK EMAIL SUDAH DIPAKAI USER LAIN?
// ==========================================
if ($email !== $oldEmail) {
    $checkEmail = $connection->prepare("SELECT UserEmail FROM users WHERE UserEmail = ?");
    $checkEmail->bind_param("s", $email);
    $checkEmail->execute();
    $resultCheck = $checkEmail->get_result();
    
    if ($resultCheck->num_rows > 0) {
        echo json_encode([
            'success' => false, 
            'message' => 'Email sudah digunakan oleh user lain'
        ]);
        exit;
    }
}

// ==========================================
// PROSES UPLOAD FOTO PROFILE
// ==========================================
$photoFileName = null;
$uploadDir = __DIR__ . '/../uploads/profile/';

// Buat folder jika belum ada
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Cek apakah ada file foto yang diupload
if (!empty($_FILES['userPhoto']['name']) && $_FILES['userPhoto']['error'] === UPLOAD_ERR_OK) {
    
    $fileSize = $_FILES['userPhoto']['size'];
    $fileTmpName = $_FILES['userPhoto']['tmp_name'];
    $fileType = $_FILES['userPhoto']['type'];
    $fileExtension = strtolower(pathinfo($_FILES['userPhoto']['name'], PATHINFO_EXTENSION));
    
    // Validasi tipe file
    $allowedExtensions = ['jpg', 'jpeg', 'png'];
    if (!in_array($fileExtension, $allowedExtensions)) {
        echo json_encode([
            'success' => false, 
            'message' => 'Format file tidak valid! Gunakan JPG, JPEG, atau PNG'
        ]);
        exit;
    }
    
    // Validasi ukuran file (max 2MB)
    if ($fileSize > 2 * 1024 * 1024) {
        echo json_encode([
            'success' => false, 
            'message' => 'Ukuran file terlalu besar! Maksimal 2MB'
        ]);
        exit;
    }
    
    // Hapus foto lama jika ada
    $getOldPhoto = $connection->prepare("SELECT UserPhoto FROM users WHERE UserEmail = ?");
    $getOldPhoto->bind_param("s", $oldEmail);
    $getOldPhoto->execute();
    $oldPhotoData = $getOldPhoto->get_result()->fetch_assoc();
    
    if (!empty($oldPhotoData['UserPhoto'])) {
        $oldPhotoPath = $uploadDir . $oldPhotoData['UserPhoto'];
        if (file_exists($oldPhotoPath)) {
            unlink($oldPhotoPath);
        }
    }
    
    // Generate nama file baru yang unik
    $photoFileName = 'profile_' . time() . '_' . rand(1000, 9999) . '.' . $fileExtension;
    $targetPath = $uploadDir . $photoFileName;
    
    // Upload file
    if (!move_uploaded_file($fileTmpName, $targetPath)) {
        echo json_encode([
            'success' => false, 
            'message' => 'Gagal mengupload foto'
        ]);
        exit;
    }
}

// ==========================================
// UPDATE DATABASE
// ==========================================
try {
    if ($photoFileName) {
        // Update dengan foto baru
        $sql = "UPDATE users SET 
                UserNama = ?, 
                UserEmail = ?, 
                UserNoHp = ?, 
                UserAlamat = ?, 
                UserPhoto = ? 
                WHERE UserEmail = ?";
        $stmt = $connection->prepare($sql);
        $stmt->bind_param("ssssss", $nama, $email, $noHp, $alamat, $photoFileName, $oldEmail);
    } else {
        // Update tanpa mengubah foto
        $sql = "UPDATE users SET 
                UserNama = ?, 
                UserEmail = ?, 
                UserNoHp = ?, 
                UserAlamat = ? 
                WHERE UserEmail = ?";
        $stmt = $connection->prepare($sql);
        $stmt->bind_param("sssss", $nama, $email, $noHp, $alamat, $oldEmail);
    }
    
    if ($stmt->execute()) {
        // Update session dengan data baru
        $_SESSION['user']['UserEmail'] = $email;
        $_SESSION['user']['UserNama'] = $nama;
        
        echo json_encode([
            'success' => true,
            'message' => 'Profile berhasil diperbarui!',
            'userName' => $nama
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Gagal menyimpan data: ' . $stmt->error
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>