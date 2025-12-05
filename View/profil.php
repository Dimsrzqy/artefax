<?php
// File: profil.php → FINAL CODE LENGKAP & KONSISTEN (DENGAN MODAL LOGOUT)
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Cek session
if (!isset($_SESSION['user']['IDUser'])) { 
    header("Location: login.php");
    exit();
}

// Koneksi database - Sesuaikan path jika perlu
require_once __DIR__ . '/../config/koneksi.php';

$database = new Database();
$connection = $database->getConnection();

if (!$connection) {
    die("
    <div style='padding:20px;background:red;color:white;font-family:Arial;'>
        <h2>Koneksi Database</h2>
        <p>Koneksi database gagal! Silakan cek:</p>
    </div>
    ");
}

// Ambil IDUser dari session 
$idUser = $_SESSION['user']['IDUser'];

// Ambil data user berdasarkan IDUser yang login
try {
    $query = "SELECT IDUser, UserNama, UserEmail, UserNoHp, UserAlamat, UserPhoto FROM users WHERE IDUser = ?";
    $stmt = $connection->prepare($query);
    
    if (!$stmt) {
        die("<div style='padding:20px;background:red;color:white;'>Error preparing statement: " . $connection->error . "</div>");
    }
    
    $stmt->bind_param("i", $idUser);
    $stmt->execute();
    $result = $stmt->get_result();
    $userData = $result->fetch_assoc();
    
    if (!$userData) {
          session_destroy();
          header("Location: login.php?error=user_not_found");
          exit();
    }
    
    // Update session dengan data terbaru
    $_SESSION['user'] = array_merge($_SESSION['user'], $userData);

} catch (Exception $e) {
    die("<div style='padding:20px;background:red;color:white;'>Database error: " . $e->getMessage() . "</div>");
}

// Mask nomor telepon (tampilkan 4 digit terakhir)
function maskPhone($phone) {
    if (empty($phone)) return "****";
    $length = strlen($phone);
    if ($length <= 4) return $phone;
    return str_repeat("*", $length - 4) . substr($phone, -4);
}

// Tambahkan timestamp anti-cache pada URL foto
$currentPhotoName = htmlspecialchars($userData['UserPhoto']);
$photoPath = __DIR__ . "/../uploads/profile/" . $currentPhotoName;

$photoUrl = "../uploads/profile/" . $currentPhotoName;
if (!empty($currentPhotoName) && file_exists($photoPath)) {
    $photoUrl .= '?v=' . time(); 
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile User - ARTEFAX.ID</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"> 
    <link href="https://fonts.googleapis.com/css2?family=Questrial&family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    
    <style>
        :root { 
            --primary-blue: #5c99ee; 
            --soft-blue: #f4f7fc; 
            --dark-text: #344761; 
            --light-text: #535d6b; 
            --background-color: var(--soft-blue);
            --default-color: var(--light-text);
            --heading-color: var(--dark-text);
            --accent-color: var(--primary-blue);
            --surface-color: #ffffff;
            --contrast-color: #ffffff;
            --status-diterima-bg: #4caf50; 
            --status-batal-bg: #dc3545; 
            --status-pending-bg: #ffc107; 
            --warning-color: var(--status-pending-bg);
            --error-color: var(--status-batal-bg);
        }

        body {
            font-family: 'Roboto', sans-serif; 
            background: var(--background-color);
            min-height: 100vh;
            padding-top: 70px; 
        }

        /* NAVBAR */
        .navbar {
            background-color: var(--accent-color) !important; 
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15);
        }
        
        .btn-riwayat {
            background: color-mix(in srgb, var(--contrast-color), var(--accent-color) 20%);
            color: var(--heading-color);
            font-weight: 500;
            padding: 8px 15px; 
            border-radius: 8px;
            transition: all 0.3s;
            text-decoration: none;
        }

        .btn-riwayat:hover {
            background: var(--contrast-color);
        }
        
        .btn-logout-red {
            background-color: var(--error-color);
            color: white;
            border: 1px solid var(--error-color);
            transition: all 0.3s ease;
            padding: 8px 15px; 
            border-radius: 8px;
            text-decoration: none;
        }
        .btn-logout-red:hover {
            background-color: color-mix(in srgb, var(--error-color), black 10%);
            color: white;
            border: 1px solid color-mix(in srgb, var(--error-color), black 10%);
        }

        /* CARD */
        .container { max-width: 900px; }
        .profile-card {
            background: var(--surface-color);
            border-radius: 12px; 
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08); 
            overflow: hidden;
            border: 1px solid color-mix(in srgb, var(--default-color), transparent 90%);
            transition: all 0.4s ease; 
        }
        
        .profile-card:hover {
            transform: translateY(-5px); 
            box-shadow: 0 15px 35px color-mix(in srgb, var(--accent-color), transparent 70%);
            border-color: color-mix(in srgb, var(--accent-color), transparent 50%);
        }

        .profile-header {
            background: linear-gradient(135deg, var(--accent-color) 0%, color-mix(in srgb, var(--accent-color), black 10%) 100%);
            padding: 40px 30px 80px;
            position: relative;
            text-align: center;
        }

        .profile-photo-container { position: relative; display: inline-block; margin-bottom: 20px; }
        .profile-photo {
            width: 150px; height: 150px; border-radius: 50%; border: 5px solid white;
            object-fit: cover; background: var(--soft-blue); display: flex;
            align-items: center; justify-content: center; font-size: 60px;
            color: var(--accent-color); cursor: pointer; position: relative;
            overflow: hidden; box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .profile-photo img { width: 100%; height: 100%; object-fit: cover; }
        .photo-placeholder { font-size: 60px; }

        .photo-overlay {
            position: absolute; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.6); border-radius: 50%; display: none;
            align-items: center; justify-content: center; color: white;
            font-size: 14px; cursor: pointer; flex-direction: column;
            gap: 5px; transition: background 0.3s;
        }

        .profile-photo-container:hover .photo-overlay { display: flex; }
        .profile-name { color: white; font-size: 28px; font-weight: 700; margin-bottom: 5px; }
        .profile-email { color: rgba(255, 255, 255, 0.9); font-size: 16px; font-weight: 300; }

        .profile-body { padding: 40px 30px; margin-top: -40px; background: var(--surface-color); border-radius: 12px 12px 0 0; color: var(--default-color); }
        .form-section { margin-bottom: 30px; }

        .section-title {
            font-size: 22px; color: var(--heading-color); font-weight: 600;
            margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid var(--accent-color);
            display: flex; align-items: center; gap: 10px;
        }

        .form-grid {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px;
        }

        .form-group { display: flex; flex-direction: column; }
        .form-group.full-width { grid-column: 1 / -1; }

        label { font-size: 14px; font-weight: 500; color: var(--dark-text); margin-bottom: 8px; }

        input, textarea {
            padding: 12px 15px; border: 1px solid color-mix(in srgb, var(--default-color), transparent 80%);
            border-radius: 10px; font-size: 15px; transition: all 0.3s;
            font-family: inherit; color: var(--heading-color);
        }

        input:focus, textarea:focus {
            outline: none; border-color: var(--accent-color);
            box-shadow: 0 0 0 3px color-mix(in srgb, var(--accent-color), transparent 70%);
        }

        input[readonly] { background-color: var(--soft-blue); cursor: not-allowed; }
        textarea { resize: vertical; min-height: 100px; }

        /* PERBAIKAN UTAMA: BUTTON GROUP & MOBILE */
        .button-group {
            display: flex;
            gap: 20px;
            justify-content: flex-end;
            margin-top: 40px;
            flex-wrap: wrap;
        }

        /* Desktop: tetap horizontal */
        @media (min-width: 768px) {
            .button-group {
                flex-wrap: nowrap;
            }
        }

        /* Mobile: tombol vertikal & full width */
        @media (max-width: 767.98px) {
            .button-group {
                flex-direction: column;
                gap: 15px;
            }
            .button-group button,
            .button-group .btn-action {
                width: 100%;
                justify-content: center;
            }
        }

        button, .btn-action {
            padding: 12px 30px; border: none; border-radius: 8px; font-size: 16px;
            font-weight: 500; cursor: pointer; transition: all 0.3s ease;
            display: inline-flex; align-items: center; gap: 8px;
            text-decoration: none; justify-content: center; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
        }
        
        .btn-primary, .btn-secondary, .btn-warning { padding: 12px 30px !important; }
        .btn-primary { background: var(--accent-color); color: var(--contrast-color); }
        .btn-primary:hover { transform: translateY(-2px); background: color-mix(in srgb, var(--accent-color), black 10%); box-shadow: 0 8px 20px color-mix(in srgb, var(--accent-color), transparent 60%); }
        .btn-secondary { background: #E5E7EB; color: #374151; }
        .btn-secondary:hover { background: #D1D5DB; }
        .btn-warning { background: var(--warning-color); color: #212529; }
        .btn-warning:hover { transform: translateY(-2px); background: color-mix(in srgb, var(--warning-color), black 5%); box-shadow: 0 8px 20px color-mix(in srgb, var(--warning-color), transparent 60%); }

        .edit-mode-controls { display: none; }
        .edit-mode .edit-mode-controls { display: flex; }
        .view-mode-controls { display: flex; }
        .edit-mode .view-mode-controls { display: none; }
        .edit-mode input:not([readonly]) { background-color: var(--surface-color); border-color: var(--warning-color); }
        .edit-mode textarea:not([readonly]) { background-color: var(--surface-color); border-color: var(--warning-color); }
        input[type="file"] { display: none; }
        .info-text { font-size: 12px; color: var(--light-text); margin-top: 5px; display: flex; align-items: center; gap: 5px; }
        .phone-masked { font-family: 'Courier New', monospace; letter-spacing: 2px; }

        /* 🛑 PERBAIKAN UTAMA STYLING NAVBAR MOBILE */
        
        /* Wrapper yang menampung Logo, Hi User, dan Toggler */
        .navbar-brand-wrapper {
            display: flex;
            align-items: center;
            /* Memisahkan Logo (kiri) dan Mobile Controls (kanan) */
            justify-content: space-between; 
            width: 100%;
        }

        /* Wrapper Baru untuk menampung Hi User dan Toggler (di mode mobile) */
        .mobile-controls-wrapper {
            display: none; /* Default: Sembunyi di desktop */
            align-items: center;
            gap: 15px; /* Jarak antara Hi User dan Toggler (membuat Hi User di kiri toggler) */
        }

        /* Text Hi, User di mobile */
        .navbar-text-mobile {
            color: white !important;
            font-size: 0.875rem; /* small */
            font-weight: 500;
        }

        /* Text Hi, User untuk Desktop (Di dalam Collapse) */
        .navbar-text-desktop {
            display: inline !important; /* Default: Tampil di desktop */
        }
        
        /* 4. Aturan Khusus untuk MOBILE (Layar Lebar < 992px) */
        @media (max-width: 991.98px) {
            /* Tampilkan wrapper mobile controls */
            .mobile-controls-wrapper {
                display: flex; 
            }
            
            /* Sembunyikan Hi User Desktop */
            .navbar-text-desktop {
                display: none !important; 
            }
            
            /* Perataan elemen di dalam collapse (menu yang dibuka) untuk mobile */
            .navbar .d-flex.align-items-center.gap-3 {
                flex-direction: column;
                width: 100%;
                margin-top: 1rem;
                gap: 12px !important;
                align-items: stretch !important;
            }

            .navbar .btn-riwayat,
            .navbar .btn-logout-red {
                width: 100%;
                justify-content: center;
            }
        }

        /* 5. Aturan Khusus untuk DESKTOP (Layar Lebar >= 992px) */
        @media (min-width: 992px) {
             /* Sembunyikan mobile controls wrapper */
             .mobile-controls-wrapper {
                display: none;
            }
             .navbar-text-desktop {
                display: inline !important; /* Tampil di desktop */
            }
            /* Menghapus wrapper agar layout desktop tetap standar Bootstrap */
            .navbar-brand-wrapper {
                display: contents; 
            }
        }


        .toast {
            position: fixed; top: 20px; right: 20px; background: var(--status-diterima-bg); 
            color: white; padding: 15px 25px; border-radius: 10px; box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);
            display: none; align-items: center; gap: 10px; z-index: 1000;
            animation: slideIn 0.3s ease-out; font-weight: 500;
        }

        .toast.error { background: var(--error-color); }

        @keyframes slideIn {
            from { transform: translateX(400px); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }

        .loading {
            display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.3); justify-content: center; align-items: center; z-index: 9999;
        }

        .loading-spinner {
            width: 50px; height: 50px; border: 5px solid rgba(255, 255, 255, 0.5);
            border-top: 5px solid var(--accent-color); border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        
        /* STYLING MODAL LOGOUT MINIMALIS BARU */
        .modal-header-minimal {
            border-bottom: none;
            padding-bottom: 0;
        }
        .modal-title-minimal {
            font-weight: 600;
            color: var(--heading-color);
        }
        .modal-body-minimal {
            padding-top: 0;
            padding-bottom: 2rem;
            text-align: center;
        }
        .modal-icon-minimal {
            color: #6c757d; /* Warna abu-abu netral */
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        .modal-footer-minimal {
            border-top: none;
        }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark shadow fixed-top">
    <div class="container">
        <div class="navbar-brand-wrapper">
            <a class="navbar-brand fw-bold" href="../index.php" style="font-family: 'Questrial', sans-serif;">Artefax</a>
            
            <div class="mobile-controls-wrapper">
                <span class="navbar-text-mobile">Hi, <strong><?= htmlspecialchars($_SESSION['user']['nama'] ?? $_SESSION['user']['UserNama'] ?? 'User') ?></strong></span>
                
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
            </div>
        </div>

        <div class="collapse navbar-collapse" id="navbarNav">
            <div class="ms-auto d-flex align-items-center gap-3">
                <span class="text-white small navbar-text-desktop">Hi, <strong><?= htmlspecialchars($_SESSION['user']['nama'] ?? $_SESSION['user']['UserNama'] ?? 'User') ?></strong></span>
                
                <a href="../RiwayatBooking.php" class="btn btn-sm btn-riwayat d-inline-flex align-items-center gap-2">
                    <i class="bi bi-clock-history"></i> Riwayat Pemesanan
                </a>
                
                <a href="javascript:void(0);" onclick="showLogoutModal();" class="btn btn-sm btn-logout-red d-inline-flex align-items-center gap-2">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
            </div>
        </div>
    </div>
</nav>

    <div class="loading" id="loading">
        <div class="loading-spinner"></div>
    </div>

    <div class="container py-5">
        <div class="profile-card">
            <div class="profile-header">
                <div class="profile-photo-container">
                    <div class="profile-photo" id="profilePhoto">
                        <?php 
                        if (!empty($userData['UserPhoto']) && file_exists($photoPath)): 
                        ?>
                            <img src="<?php echo $photoUrl; ?>" alt="Profile Photo" id="photoPreview">
                        <?php else: ?>
                            <span class="photo-placeholder" id="photoPlaceholder"><i class="bi bi-person-fill"></i></span>
                            <img id="photoPreview" src="" style="display: none;">
                        <?php endif; ?>
                        <div class="photo-overlay" onclick="document.getElementById('photoInput').click()">
                            <span><i class="bi bi-camera-fill"></i></span>
                            <span>Ubah Foto</span>
                        </div>
                    </div>
                    <input type="file" id="photoInput" accept="image/jpeg,image/jpg,image/png">
                </div>
                <div class="profile-name" id="displayName"><?php echo htmlspecialchars($userData['UserNama']); ?></div>
                <div class="profile-email"><?php echo htmlspecialchars($userData['UserEmail']); ?></div>
            </div>

            <form id="profileForm" class="profile-body" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="idUser" value="<?php echo $userData['IDUser']; ?>"> 

                <div class="form-section">
                    <div class="section-title">
                        <span><i class="bi bi-person-badge-fill"></i></span> Informasi Personal
                    </div>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Nama Lengkap</label>
                            <input type="text" id="userNama" name="userNama" value="<?php echo htmlspecialchars($userData['UserNama']); ?>" readonly required>
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" id="userEmail" name="userEmail" value="<?php echo htmlspecialchars($userData['UserEmail']); ?>" readonly required data-original-email="<?php echo htmlspecialchars($userData['UserEmail']); ?>">
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <div class="section-title">
                        <span><i class="bi bi-geo-alt-fill"></i></span> Kontak & Alamat
                    </div>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>No. Handphone</label>
                            <input type="text" id="userNoHp" name="userNoHp" class="phone-masked" value="<?php echo maskPhone($userData['UserNoHp']); ?>" data-original="<?php echo htmlspecialchars($userData['UserNoHp']); ?>" readonly>
                            <span class="info-text"><i class="bi bi-lock-fill"></i> Nomor disamarkan untuk keamanan</span>
                        </div>
                        <div class="form-group full-width">
                            <label>Alamat Lengkap</label>
                            <textarea id="userAddress" name="userAddress" placeholder="Masukkan alamat lengkap Anda..." readonly><?php echo htmlspecialchars($userData['UserAlamat']); ?></textarea>
                        </div>
                    </div>
                </div>

                <div class="button-group">
                    <div class="view-mode-controls">
                        <a href="reset_password.php" class="btn btn-warning btn-action" style="text-decoration: none;">
                            <i class="bi bi-key-fill"></i> Ubah Password
                        </a>
                        <button type="button" class="btn btn-primary btn-action" onclick="enableEditMode()">
                            <i class="bi bi-pencil-fill"></i> Edit Profile
                        </button>
                    </div>
                    <div class="edit-mode-controls">
                        <button type="button" class="btn btn-secondary btn-action" onclick="cancelEdit()">
                            <i class="bi bi-x-lg"></i> Batal
                        </button>
                        <button type="submit" class="btn btn-primary btn-action">
                            <i class="bi bi-save-fill"></i> Simpan Perubahan
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="toast" id="toast"></div>
    
    <div class="modal fade" id="logoutConfirmModal" tabindex="-1" aria-labelledby="logoutConfirmModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header modal-header-minimal">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body modal-body-minimal">
                    <i class="bi bi-box-arrow-right modal-icon-minimal"></i>
                    <h5 class="modal-title-minimal mb-2" id="logoutConfirmModalLabel">Konfirmasi Logout</h5>
                    <p class="text-muted mb-0 small">Apakah Anda yakin ingin mengakhiri sesi?</p>
                </div>
                <div class="modal-footer modal-footer-minimal justify-content-center pt-0 pb-3">
                    <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Batal</button>
                    <a id="confirmLogoutButton" href="../logout.php" class="btn btn-danger">Ya, Keluar</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let originalData = {};
        let photoChanged = false;

        document.addEventListener('DOMContentLoaded', function() {
            const noHpInput = document.getElementById('userNoHp');
            const emailInput = document.getElementById('userEmail');
            
            originalData = {
                name: document.getElementById('userNama').value,
                email: emailInput.value,
                noHp: noHpInput.dataset.original || '',
                address: document.getElementById('userAddress').value
            };
        });

        // FUNGSI BARU: Menampilkan modal konfirmasi logout
        function showLogoutModal() {
            const logoutModal = new bootstrap.Modal(document.getElementById('logoutConfirmModal'));
            logoutModal.show();
        }

        document.getElementById('photoInput').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                if (file.size > 2 * 1024 * 1024) {
                    showToast('Ukuran file terlalu besar! Maksimal 2MB', true);
                    this.value = '';
                    return;
                }

                if (!['image/jpeg', 'image/jpg', 'image/png'].includes(file.type)) {
                    showToast('Format file tidak valid! Gunakan JPG atau PNG', true);
                    this.value = '';
                    return;
                }

                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('photoPreview').src = e.target.result;
                    document.getElementById('photoPreview').style.display = 'block';
                    document.getElementById('photoPlaceholder').style.display = 'none';
                    photoChanged = true;
                };
                reader.readAsDataURL(file);
            }
        });

        function enableEditMode() {
            const noHpInput = document.getElementById('userNoHp');
            const emailInput = document.getElementById('userEmail');
            
            originalData.name = document.getElementById('userNama').value;
            originalData.noHp = noHpInput.dataset.original; 
            originalData.address = document.getElementById('userAddress').value;

            noHpInput.value = originalData.noHp;
            noHpInput.classList.remove('phone-masked');
            emailInput.readOnly = false;
            
            document.getElementById('profileForm').classList.add('edit-mode');
            document.getElementById('userNama').readOnly = false;
            document.getElementById('userNoHp').readOnly = false;
            document.getElementById('userAddress').readOnly = false;
        }

        function cancelEdit() {
            if (originalData.name) {
                document.getElementById('userNama').value = originalData.name;
                document.getElementById('userEmail').value = originalData.email;
                
                const noHpInput = document.getElementById('userNoHp');
                noHpInput.value = maskPhone(originalData.noHp);
                noHpInput.classList.add('phone-masked');
                
                document.getElementById('userAddress').value = originalData.address;
            }

            if (photoChanged) {
                location.reload(); 
                return; 
            }

            document.getElementById('profileForm').classList.remove('edit-mode');
            document.getElementById('userNama').readOnly = true;
            document.getElementById('userEmail').readOnly = true; 
            document.getElementById('userNoHp').readOnly = true;
            document.getElementById('userAddress').readOnly = true;
            photoChanged = false; 
        }

        function maskPhone(phone) {
            if (!phone) return "****";
            const length = phone.length;
            if (length <= 4) return phone;
            return "*".repeat(length - 4) + phone.slice(-4);
        }

        function showToast(message, isError = false) {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.className = 'toast' + (isError ? ' error' : '');
            toast.style.display = 'flex';
            setTimeout(() => {
                toast.style.display = 'none';
            }, 3000);
        }

        document.getElementById('profileForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData();
            const currentEmail = document.getElementById('userEmail').value;
            const currentNoHp = document.getElementById('userNoHp').value;

            formData.append('idUser', <?php echo $userData['IDUser']; ?>);
            formData.append('userNama', document.getElementById('userNama').value);
            formData.append('userEmail', currentEmail); 
            formData.append('userNoHp', currentNoHp); 
            formData.append('userAddress', document.getElementById('userAddress').value);
            
            let shouldReload = false; 
            if (photoChanged) {
                const photoFile = document.getElementById('photoInput').files[0];
                if (photoFile) {
                    formData.append('userPhoto', photoFile);
                    shouldReload = true; 
                }
            }

            document.getElementById('loading').style.display = 'flex';

            fetch('update_profil.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Server returned ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                document.getElementById('loading').style.display = 'none';
                
                if (data.success) {
                    showToast('Profile berhasil diperbarui!');
                    
                    document.getElementById('displayName').textContent = data.userName;
                    document.getElementById('userEmail').value = currentEmail;
                    document.getElementById('userNoHp').dataset.original = data.userNoHp;
                    
                    if (shouldReload || currentEmail !== originalData.email) {
                        location.reload(); 
                        return;
                    }
                    
                    const noHpInput = document.getElementById('userNoHp');
                    noHpInput.value = maskPhone(data.userNoHp);
                    noHpInput.classList.add('phone-masked');
                    
                    document.getElementById('profileForm').classList.remove('edit-mode');
                    document.getElementById('userNama').readOnly = true;
                    document.getElementById('userEmail').readOnly = true;
                    document.getElementById('userNoHp').readOnly = true;
                    document.getElementById('userAddress').readOnly = true;
                    photoChanged = false; 
                    
                    originalData.name = data.userName;
                    originalData.email = currentEmail;
                    originalData.noHp = data.userNoHp;
                    originalData.address = document.getElementById('userAddress').value;

                } else {
                    showToast(' ' + data.message, true);
                }
            })
            .catch(error => {
                document.getElementById('loading').style.display = 'none';
                showToast('Terjadi kesalahan pada koneksi atau server! (' + error.message + ')', true);
                console.error('Error:', error);
                
                document.getElementById('profileForm').classList.remove('edit-mode');
                document.getElementById('userNama').readOnly = true;
                document.getElementById('userEmail').readOnly = true;
                document.getElementById('userNoHp').readOnly = true;
                document.getElementById('userAddress').readOnly = true;
            });
        });
    </script>
</body>
</html>