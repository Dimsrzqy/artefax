<?php
// File: update_profil.php - FINAL CODE 
session_start();
header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 1); // Tampilkan error untuk debugging

// Cek autentikasi
if (!isset($_SESSION['user']['IDUser'])) {
    echo json_encode(['success' => false, 'message' => 'Sesi login habis, silakan login kembali']);
    exit;
}

// Ambil IDUser dan data lama dari session
$idUser = $_SESSION['user']['IDUser'];
$oldEmail = $_SESSION['user']['UserEmail'] ?? '';
$oldPhoto = $_SESSION['user']['UserPhoto'] ?? null;

// Koneksi database
require_once __DIR__ . '/../config/koneksi.php';

$database = new Database();
$connection = $database->getConnection();

if (!$connection) {
    echo json_encode(['success' => false, 'message' => 'Koneksi database gagal']);
    exit;
}

// Ambil data dari POST
$nama = trim($_POST['userNama'] ?? '');
$email = trim($_POST['userEmail'] ?? '');
$noHp = trim($_POST['userNoHp'] ?? '');
$alamat = trim($_POST['userAddress'] ?? '');

// ==========================================
// VALIDASI INPUT
// ==========================================
if (empty($nama) || empty($email) || empty($noHp)) {
    echo json_encode(['success' => false, 'message' => 'Nama, Email, dan Nomor HP tidak boleh kosong']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Format email tidak valid']);
    exit;
}

// ==========================================
// CEK EMAIL SUDAH DIPAKAI USER LAIN?
// ==========================================
if ($email !== $oldEmail) {
    $checkEmail = $connection->prepare("SELECT IDUser FROM users WHERE UserEmail = ? AND IDUser != ?");
    $checkEmail->bind_param("si", $email, $idUser);
    $checkEmail->execute();
    $resultCheck = $checkEmail->get_result();
    
    if ($resultCheck->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Email sudah digunakan oleh user lain']);
        exit;
    }
}

// ==========================================
// PROSES UPLOAD FOTO PROFILE
// ==========================================
$photoFileName = $oldPhoto; 
$uploadDir = __DIR__ . '/../uploads/profile/'; 

// Pastikan folder ada dan memiliki izin tulis
if (!is_dir($uploadDir)) {
    @mkdir($uploadDir, 0755, true); 
}

if (isset($_FILES['userPhoto']) && $_FILES['userPhoto']['error'] === UPLOAD_ERR_OK) {
    
    $fileSize = $_FILES['userPhoto']['size'];
    $fileTmpName = $_FILES['userPhoto']['tmp_name'];
    $fileExtension = strtolower(pathinfo($_FILES['userPhoto']['name'], PATHINFO_EXTENSION));
    
    $allowedExtensions = ['jpg', 'jpeg', 'png'];
    if (!in_array($fileExtension, $allowedExtensions)) {
        echo json_encode(['success' => false, 'message' => 'Format file tidak valid! Gunakan JPG, JPEG, atau PNG']);
        exit;
    }
    
    if ($fileSize > 2 * 1024 * 1024) {
        echo json_encode(['success' => false, 'message' => 'Ukuran file terlalu besar! Maksimal 2MB']);
        exit;
    }
    
    // Hapus foto lama sebelum upload yang baru
    if (!empty($oldPhoto)) {
        $oldPhotoPath = $uploadDir . $oldPhoto;
        if (file_exists($oldPhotoPath)) {
            // KRUSIAL: Log status penghapusan
            $unlink_status = @unlink($oldPhotoPath); 
            if (!$unlink_status) {
                 error_log("FOTO DEBUG: GAGAL unlink foto lama: " . $oldPhotoPath . ". Check file permission!");
            }
        }
    }
    
    // Generate nama file baru
    $photoFileName = 'profile_' . $idUser . '_' . time() . '.' . $fileExtension;
    $targetPath = $uploadDir . $photoFileName;
    
    // Upload file
    if (!move_uploaded_file($fileTmpName, $targetPath)) {
        error_log("FOTO DEBUG: GAGAL move_uploaded_file ke: " . $targetPath . ". Check directory permission!");
        echo json_encode(['success' => false, 'message' => 'Gagal mengupload foto. Periksa izin direktori.']);
        exit;
    }
}

// ==========================================
// UPDATE DATABASE (Menggunakan IDUser)
// ==========================================
try {
    if ($photoFileName !== $oldPhoto) {
        // Jika ada update foto
        $sql = "UPDATE users SET 
                UserNama = ?, UserEmail = ?, UserNoHp = ?, UserAlamat = ?, UserPhoto = ? 
                WHERE IDUser = ?";
        $stmt = $connection->prepare($sql);
        $stmt->bind_param("sssssi", $nama, $email, $noHp, $alamat, $photoFileName, $idUser);
    } else {
        // Jika tidak ada update foto
        $sql = "UPDATE users SET 
                UserNama = ?, UserEmail = ?, UserNoHp = ?, UserAlamat = ? 
                WHERE IDUser = ?";
        $stmt = $connection->prepare($sql);
        $stmt->bind_param("ssssi", $nama, $email, $noHp, $alamat, $idUser);
    }
    
    if ($stmt->execute()) {
        // Update session dengan data baru yang lengkap
        $_SESSION['user']['UserNama'] = $nama;
        $_SESSION['user']['UserEmail'] = $email;
        $_SESSION['user']['UserNoHp'] = $noHp;
        $_SESSION['user']['UserAlamat'] = $alamat;
        $_SESSION['user']['UserPhoto'] = $photoFileName; 
        
        echo json_encode([
            'success' => true,
            'message' => 'Profile berhasil diperbarui!',
            'userName' => htmlspecialchars($nama),
            'userNoHp' => htmlspecialchars($noHp) 
        ]);
    } else {
        error_log("SQL Error: " . $stmt->error);
        echo json_encode(['success' => false, 'message' => 'Gagal menyimpan data ke database: ' . $stmt->error]);
    }
    
} catch (Exception $e) {
    error_log("General Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>