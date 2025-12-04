<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// PERBAIKAN: Cek session sesuai dengan struktur login
if (!isset($_SESSION['user']['UserEmail'])) {
    header("Location: login.php");
    exit();
}

// Koneksi database - SESUAIKAN DENGAN CLASS DATABASE
require_once __DIR__ . '/../config/koneksi.php';

$database = new Database();
$connection = $database->getConnection();

// CEK APAKAH KONEKSI BERHASIL
if (!$connection) {
    die("
    <div style='padding:20px;background:red;color:white;font-family:Arial;'>
        <h2>❌ Error Koneksi Database</h2>
        <p>Koneksi database gagal! Silakan cek:</p>
        <ul>
            <li>File <code>config/koneksi.php</code> sudah ada dan benar</li>
            <li>MySQL sudah running (jalankan Laragon)</li>
            <li>Database 'artefax' sudah dibuat di phpMyAdmin</li>
            <li>Username: root, Password: (kosong)</li>
        </ul>
        <p><strong>Path file koneksi:</strong> " . __DIR__ . "/../config/koneksi.php</p>
    </div>
    ");
}

// Ambil email dari session (sesuai struktur login)
$userEmail = $_SESSION['user']['UserEmail'];

// Ambil data user berdasarkan email yang login
try {
    $query = "SELECT UserNama, UserEmail, UserNoHp, UserAlamat, UserPhoto FROM users WHERE UserEmail = ?";
    $stmt = $connection->prepare($query);
    
    if (!$stmt) {
        die("<div style='padding:20px;background:red;color:white;'>Error preparing statement: " . $connection->error . "</div>");
    }
    
    $stmt->bind_param("s", $userEmail);
    $stmt->execute();
    $result = $stmt->get_result();
    $userData = $result->fetch_assoc();
    
    // Jika user tidak ditemukan
    if (!$userData) {
        die("
        <div style='padding:20px;background:orange;color:white;font-family:Arial;'>
            <h2>⚠️ User Tidak Ditemukan</h2>
            <p>User dengan email '<strong>$userEmail</strong>' tidak ditemukan di database!</p>
            <p>Silakan cek:</p>
            <ul>
                <li>Tabel 'users' sudah ada di database 'artefax'</li>
                <li>Email user sudah terdaftar di database</li>
                <li>Session login sudah benar</li>
            </ul>
            <a href='logout.php' style='color:white;text-decoration:underline;'>← Logout dan Login Ulang</a>
        </div>
        ");
    }
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
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile User - ARTEFAX.ID</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #0891B2 0%, #1E40AF 100%);
            min-height: 100vh;
        }

        .navbar {
            background: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 15px 0;
        }

        .navbar-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .navbar-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            color: #0891B2;
            font-size: 20px;
            font-weight: bold;
        }

        .navbar-menu {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .btn-nav {
            padding: 8px 20px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-back {
            background: #E5E7EB;
            color: #374151;
        }

        .btn-back:hover {
            background: #D1D5DB;
        }

        .btn-logout {
            background: linear-gradient(135deg, #EF4444 0%, #DC2626 100%);
            color: white;
        }

        .btn-logout:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(239, 68, 68, 0.4);
        }

        .container {
            max-width: 900px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .profile-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }

        .profile-header {
            background: linear-gradient(135deg, #0891B2 0%, #1E40AF 100%);
            padding: 40px 30px 80px;
            position: relative;
            text-align: center;
        }

        .profile-photo-container {
            position: relative;
            display: inline-block;
            margin-bottom: 20px;
        }

        .profile-photo {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            border: 5px solid white;
            object-fit: cover;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 60px;
            color: #0891B2;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }

        .profile-photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .photo-placeholder {
            font-size: 60px;
        }

        .photo-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            border-radius: 50%;
            display: none;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 14px;
            cursor: pointer;
            flex-direction: column;
            gap: 5px;
        }

        .profile-photo-container:hover .photo-overlay {
            display: flex;
        }

        .profile-name {
            color: white;
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .profile-email {
            color: rgba(255, 255, 255, 0.9);
            font-size: 16px;
        }

        .profile-body {
            padding: 40px 30px;
            margin-top: -40px;
            background: white;
            border-radius: 20px 20px 0 0;
        }

        .form-section {
            margin-bottom: 30px;
        }

        .section-title {
            font-size: 20px;
            color: #1E40AF;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #0891B2;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        label {
            font-size: 14px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 8px;
        }

        input, textarea {
            padding: 12px 15px;
            border: 2px solid #E5E7EB;
            border-radius: 10px;
            font-size: 15px;
            transition: all 0.3s;
            font-family: inherit;
        }

        input:focus, textarea:focus {
            outline: none;
            border-color: #0891B2;
            box-shadow: 0 0 0 3px rgba(8, 145, 178, 0.1);
        }

        input[readonly] {
            background-color: #F3F4F6;
            cursor: not-allowed;
        }

        textarea {
            resize: vertical;
            min-height: 100px;
        }

        .button-group {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
            margin-top: 30px;
            flex-wrap: wrap;
        }

        button, .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            justify-content: center;
        }

        .btn-primary {
            background: linear-gradient(135deg, #F97316 0%, #EA580C 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(249, 115, 22, 0.4);
        }

        .btn-secondary {
            background: #E5E7EB;
            color: #374151;
        }

        .btn-secondary:hover {
            background: #D1D5DB;
        }

        .btn-warning {
            background: linear-gradient(135deg, #EAB308 0%, #CA8A04 100%);
            color: white;
        }

        .btn-warning:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(234, 179, 8, 0.4);
        }

        .edit-mode-controls {
            display: none;
        }

        .edit-mode .edit-mode-controls {
            display: flex;
        }

        .view-mode-controls {
            display: flex;
        }

        .edit-mode .view-mode-controls {
            display: none;
        }

        .edit-mode input:not([readonly]) {
            background-color: #FEF3C7;
        }

        .edit-mode textarea:not([readonly]) {
            background-color: #FEF3C7;
        }

        input[type="file"] {
            display: none;
        }

        .info-text {
            font-size: 12px;
            color: #6B7280;
            margin-top: 5px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .phone-masked {
            font-family: 'Courier New', monospace;
            letter-spacing: 2px;
        }

        @media (max-width: 768px) {
            .navbar-container {
                flex-direction: column;
                gap: 15px;
            }

            .navbar-menu {
                width: 100%;
                justify-content: center;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .button-group {
                flex-direction: column;
            }

            button, .btn {
                width: 100%;
            }
        }

        .toast {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #10B981;
            color: white;
            padding: 15px 25px;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);
            display: none;
            align-items: center;
            gap: 10px;
            z-index: 1000;
            animation: slideIn 0.3s ease-out;
        }

        .toast.error {
            background: #EF4444;
        }

        @keyframes slideIn {
            from {
                transform: translateX(400px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        .loading {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }

        .loading-spinner {
            width: 50px;
            height: 50px;
            border: 5px solid #f3f3f3;
            border-top: 5px solid #0891B2;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="navbar-container">
            <a href="../index.php" class="navbar-brand">
                <img src="assets/img/tambahan/logo Artefax.png" alt="Artefax Logo" style="height: 40px;">
            </a>
            <div class="navbar-menu">
                <a href="../index.php" class="btn-nav btn-back">
                    ← Kembali ke Beranda
                </a>
                <a href="../logout.php" class="btn-nav btn-logout" onclick="return confirm('Yakin ingin logout?')">
                    🚪 Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="loading" id="loading">
        <div class="loading-spinner"></div>
    </div>

    <div class="container">
        <div class="profile-card">
            <div class="profile-header">
                <div class="profile-photo-container">
                    <div class="profile-photo" id="profilePhoto">
                        <?php if (!empty($userData['UserPhoto']) && file_exists(__DIR__ . "/../uploads/profile/" . $userData['UserPhoto'])): ?>
                            <img src="../uploads/profile/<?php echo htmlspecialchars($userData['UserPhoto']); ?>" alt="Profile Photo" id="photoPreview">
                        <?php else: ?>
                            <span class="photo-placeholder" id="photoPlaceholder">👤</span>
                            <img id="photoPreview" style="display: none;">
                        <?php endif; ?>
                        <div class="photo-overlay" onclick="document.getElementById('photoInput').click()">
                            <span>📷</span>
                            <span>Ubah Foto</span>
                        </div>
                    </div>
                    <input type="file" id="photoInput" accept="image/jpeg,image/jpg,image/png">
                </div>
                <div class="profile-name" id="displayName"><?php echo htmlspecialchars($userData['UserNama']); ?></div>
                <div class="profile-email"><?php echo htmlspecialchars($userData['UserEmail']); ?></div>
            </div>

            <form id="profileForm" class="profile-body" method="POST" enctype="multipart/form-data">
                <div class="form-section">
                    <div class="section-title">
                        <span>👤</span> Informasi Personal
                    </div>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Nama Lengkap</label>
                            <input type="text" id="userNama" name="userNama" value="<?php echo htmlspecialchars($userData['UserNama']); ?>" readonly required>
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" id="userEmail" name="userEmail" value="<?php echo htmlspecialchars($userData['UserEmail']); ?>" readonly required>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <div class="section-title">
                        <span>📞</span> Kontak & Alamat
                    </div>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>No. Handphone</label>
                            <input type="text" id="userNoHp" name="userNoHp" class="phone-masked" value="<?php echo maskPhone($userData['UserNoHp']); ?>" data-original="<?php echo htmlspecialchars($userData['UserNoHp']); ?>" readonly>
                            <span class="info-text">🔒 Nomor disamarkan untuk keamanan</span>
                        </div>
                        <div class="form-group full-width">
                            <label>Alamat Lengkap</label>
                            <textarea id="userAddress" name="userAddress" placeholder="Masukkan alamat lengkap Anda..." readonly><?php echo htmlspecialchars($userData['UserAlamat']); ?></textarea>
                        </div>
                    </div>
                </div>

                <div class="button-group">
                    <div class="view-mode-controls">
                        <a href="reset_password.php" class="btn btn-warning" style="text-decoration: none;">
                            🔑 Ubah Password
                        </a>
                        </button>
                        <button type="button" class="btn btn-primary" onclick="enableEditMode()">
                            ✏️ Edit Profile
                        </button>
                    </div>
                    <div class="edit-mode-controls">
                        <button type="button" class="btn btn-secondary" onclick="cancelEdit()">
                            ✖️ Batal
                        </button>
                        <button type="submit" class="btn btn-primary">
                            💾 Simpan Perubahan
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="toast" id="toast"></div>

    <script>
        let originalData = {};
        let currentPhoto = null;
        let photoChanged = false;

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
                    currentPhoto = e.target.result;
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
            originalData = {
                name: document.getElementById('userNama').value,
                email: document.getElementById('userEmail').value,
                noHp: noHpInput.dataset.original || noHpInput.value,
                address: document.getElementById('userAddress').value
            };

            noHpInput.value = originalData.noHp;
            noHpInput.classList.remove('phone-masked');

            document.getElementById('profileForm').classList.add('edit-mode');
            document.getElementById('userNama').readOnly = false;
            document.getElementById('userEmail').readOnly = false;
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
            }

            document.getElementById('profileForm').classList.remove('edit-mode');
            document.getElementById('userNama').readOnly = true;
            document.getElementById('userEmail').readOnly = true;
            document.getElementById('userNoHp').readOnly = true;
            document.getElementById('userAddress').readOnly = true;
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
            formData.append('userNama', document.getElementById('userNama').value);
            formData.append('userEmail', document.getElementById('userEmail').value);
            formData.append('userNoHp', document.getElementById('userNoHp').value);
            formData.append('userAddress', document.getElementById('userAddress').value);
            
            if (photoChanged) {
                const photoFile = document.getElementById('photoInput').files[0];
                if (photoFile) {
                    formData.append('userPhoto', photoFile);
                }
            }

            document.getElementById('loading').style.display = 'flex';

            fetch('update_profil.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                document.getElementById('loading').style.display = 'none';
                
                if (data.success) {
                    showToast('✓ Profile berhasil diperbarui!');
                    document.getElementById('displayName').textContent = data.userName;
                    photoChanged = false;
                    cancelEdit();
                    
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    showToast('✗ ' + data.message, true);
                }
            })
            .catch(error => {
                document.getElementById('loading').style.display = 'none';
                showToast('✗ Terjadi kesalahan! Silakan coba lagi', true);
                console.error('Error:', error);
            });
        });
    </script>
</body>
</html>