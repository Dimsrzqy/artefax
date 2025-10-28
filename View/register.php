<?php
session_start();

// === ZONA WAKTU ===
date_default_timezone_set('Asia/Jakarta');

require_once "../config/koneksi.php";
require_once "../class/Users.php";

$db = new Database();
$conn = $db->getConnection();

$message = '';
$message_type = 'danger';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = new User($conn);

    // Ambil & sanitasi input
    $user->UserNama     = trim($_POST['nama'] ?? '');
    $user->UserEmail    = trim($_POST['email'] ?? '');
    $user->UserPassword = $_POST['password'] ?? '';
    $user->UserNoHP     = trim($_POST['nohp'] ?? '');
    $user->UserAlamat   = trim($_POST['alamat'] ?? '');

    // Validasi
    if (empty($user->UserNama) || empty($user->UserEmail) || empty($user->UserPassword)) {
        $message = "Nama, Email, dan Password wajib diisi!";
    } elseif (!filter_var($user->UserEmail, FILTER_VALIDATE_EMAIL)) {
        $message = "Format email tidak valid!";
    } elseif (strlen($user->UserPassword) < 6) {
        $message = "Password minimal 6 karakter!";
    } else {
        // Register sebagai Customer
        if ($user->register('Customer')) {
            $message = "Registrasi berhasil! Silakan login.";
            $message_type = 'success';
            // Reset form
            $user->UserNama = $user->UserEmail = $user->UserPassword = $user->UserNoHP = $user->UserAlamat = '';
        } else {
            $message = "Registrasi gagal! Email mungkin sudah digunakan.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Artefax</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* === FULL CENTER LAYOUT === */
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 1rem;
        }

        /* === CARD === */
        .auth-card {
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
            overflow: hidden;
            max-width: 460px;
            width: 100%;
            animation: fadeIn 0.6s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .card-header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            text-align: center;
            padding: 2.5rem 1.5rem;
            border: none;
        }

        .card-header h3 {
            margin: 0;
            font-weight: 600;
            font-size: 1.5rem;
        }

        .card-header p {
            margin: 0.5rem 0 0;
            opacity: 0.9;
            font-size: 0.95rem;
        }

        .card-body {
            padding: 2rem;
        }

        .form-control, .form-control:focus {
            border-radius: 50px;
            padding: 14px 20px;
            border: 1px solid #e0e0e0;
            font-size: 1rem;
            transition: all 0.3s;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }

        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.2);
            outline: none;
        }

        .btn-primary {
            background: #667eea;
            border: none;
            border-radius: 50px;
            padding: 14px;
            font-weight: 600;
            font-size: 1.05rem;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .btn-primary:hover {
            background: #5a6fd8;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }

        .alert {
            border-radius: 12px;
            font-size: 0.95rem;
            margin: 1rem 0;
            border: none;
            padding: 1rem 1.25rem;
        }

        .text-center a {
            color: #adb5bd;
            text-decoration: none;
            font-size: 0.95rem;
            transition: color 0.3s;
        }

        .text-center a:hover {
            color: #667eea;
            text-decoration: underline;
        }

        /* Responsif */
        @media (max-width: 480px) {
            .card-header {
                padding: 2rem 1rem;
            }
            .card-body {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="auth-card">
        <div class="card-header">
            <h3>Register Akun Artefax</h3>
            <p class="mb-0">Daftar untuk mulai menggunakan layanan</p>
        </div>
        <div class="card-body">
            <!-- PESAN -->
            <?php if ($message): ?>
                <div class="alert alert-<?= $message_type ?> alert-dismissible fade show">
                    <?= htmlspecialchars($message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- FORM REGISTER -->
            <form method="POST" novalidate>
                <div class="mb-3">
                    <label for="nama" class="form-label visually-hidden">Nama Lengkap</label>
                    <input 
                        type="text" 
                        id="nama"
                        name="nama" 
                        class="form-control" 
                        required 
                        placeholder="Nama Lengkap"
                        value="<?= htmlspecialchars($user->UserNama ?? '') ?>"
                        autocomplete="name"
                    >
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label visually-hidden">Email</label>
                    <input 
                        type="email" 
                        id="email"
                        name="email" 
                        class="form-control" 
                        required 
                        placeholder="email@contoh.com"
                        value="<?= htmlspecialchars($user->UserEmail ?? '') ?>"
                        autocomplete="email"
                    >
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label visually-hidden">Password</label>
                    <input 
                        type="password" 
                        id="password"
                        name="password" 
                        class="form-control" 
                        required 
                        minlength="6"
                        placeholder="Password (min. 6 karakter)"
                        autocomplete="new-password"
                    >
                </div>

                <div class="mb-3">
                    <label for="nohp" class="form-label visually-hidden">No HP</label>
                    <input 
                        type="text" 
                        id="nohp"
                        name="nohp" 
                        class="form-control" 
                        placeholder="No HP"
                        value="<?= htmlspecialchars($user->UserNoHP ?? '') ?>"
                        pattern="[0-9]{10,15}"
                        autocomplete="tel"
                    >
                </div>

                <div class="mb-3">
                    <label for="alamat" class="form-label visually-hidden">Alamat</label>
                    <textarea 
                        id="alamat"
                        name="alamat" 
                        class="form-control" 
                        rows="2"
                        placeholder="Alamat"
                        autocomplete="street-address"
                    ><?= htmlspecialchars($user->UserAlamat ?? '') ?></textarea>
                </div>

                <button type="submit" class="btn btn-primary w-100">
                    Daftar Sekarang
                </button>
            </form>

            <div class="text-center mt-4">
                <a href="login.php">Sudah punya akun? Login</a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Fokus otomatis ke nama
        document.addEventListener('DOMContentLoaded', () => {
            const namaInput = document.getElementById('nama');
            if (namaInput && !namaInput.value) {
                namaInput.focus();
            }
        });
    </script>
</body>
</html>