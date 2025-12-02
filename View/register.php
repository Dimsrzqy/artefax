<?php
session_start();
date_default_timezone_set('Asia/Jakarta');

require_once "../config/koneksi.php";
require_once "../class/Users.php";

$db = new Database();
$conn = $db->getConnection();

$message = '';
$message_type = 'danger';

$user = new User($conn);
$user->UserNama = $user->UserEmail = $user->UserPassword = $user->UserNoHP = $user->UserAlamat = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $inputNama     = trim($_POST['nama'] ?? '');
    $inputEmail    = trim($_POST['email'] ?? '');
    $inputPassword = $_POST['password'] ?? '';
    $inputNoHP     = trim($_POST['nohp'] ?? '');
    $inputAlamat   = trim($_POST['alamat'] ?? '');

    $user->UserNama     = $inputNama;
    $user->UserEmail    = $inputEmail;
    $user->UserNoHP     = $inputNoHP;
    $user->UserAlamat   = $inputAlamat;

    if (empty($inputNama) || empty($inputEmail) || empty($inputPassword)) {
        $message = "Nama, Email, dan Password wajib diisi!";
    } elseif (!filter_var($inputEmail, FILTER_VALIDATE_EMAIL)) {
        $message = "Format email tidak valid!";
    } elseif (strlen($inputPassword) < 6) {
        $message = "Password minimal 6 karakter!";
    } elseif (!empty($inputNoHP) && !preg_match("/^[0-9]{8,15}$/", $inputNoHP)) {
        $message = "Format Nomor HP tidak valid!";
    } else {
        $user->UserPassword = $inputPassword;
        if ($user->register('Customer')) {
            $message = "Registrasi berhasil! Silakan login.";
            $message_type = 'success';
            $user->UserNama = $user->UserEmail = $user->UserNoHP = $user->UserAlamat = '';
        } else {
            $message = "Registrasi gagal! Email sudah terdaftar.";
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --blue: #5c99ee;
            --blue-dark: #4c89de;
        }

        html, body {
            height: 100%;
            margin: 0;
            background: linear-gradient(135deg, var(--blue) 0%, var(--blue-dark) 100%);
            font-family: 'Segoe UI', sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        /* EKSTRIM: PALING LEBAR & PALING PENDEK */
        .auth-card {
            background: white;
            border-radius: 32px;
            box-shadow: 0 35px 90px rgba(0,0,0,0.35);
            overflow: hidden;
            max-width: 1000px;     /* PALING LEBAR */
            width: 100%;
        }

        .card-header {
            background: linear-gradient(135deg, var(--blue), var(--blue-dark));
            color: white;
            text-align: center;
            padding: 1rem 2rem 0.9rem;   /* SUPER PENDEK */
            border: none;
        }

        .card-header h1 {
            margin: 0;
            font-weight: 900;
            font-size: 2.3rem;
        }

        .card-header p {
            margin: 0.2rem 0 0;
            font-size: 0.95rem;
            opacity: 0.9;
        }

        .card-body {
            padding: 1.4rem 4.5rem 1.8rem;   /* KIRI-KANAN SUPER LEBAR, ATAS-BAWAH SUPER RENDAH */
        }

        .form-control, textarea.form-control {
            height: 56px;
            border-radius: 50px;
            padding: 0 32px;
            border: 1.7px solid #d8e0f0;
            font-size: 1.08rem;
            box-shadow: 0 5px 16px rgba(0,0,0,0.08);
            margin-bottom: 0.85rem;
        }

        textarea.form-control {
            height: 76px;
            border-radius: 24px;
            padding: 14px 32px;
            resize: none;
        }

        .form-control:focus, textarea.form-control:focus {
            border-color: var(--blue);
            box-shadow: 0 0 0 5.5px rgba(92,153,238,0.32);
        }

        /* IKON MATA BESAR */
        .password-wrapper {
            position: relative;
        }

        .password-wrapper .form-control {
            padding-right: 80px;
        }

        .toggle-password {
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #444;
            font-size: 1.5rem;
            cursor: pointer;
            z-index: 10;
            width: 52px;
            height: 52px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.3s;
        }

        .toggle-password:hover {
            background: rgba(92,153,238,0.3);
            color: var(--blue);
        }

        .btn-register {
            height: 58px;
            border-radius: 50px;
            background: linear-gradient(135deg, var(--blue), var(--blue-dark));
            border: none;
            color: white;
            font-weight: 800;
            font-size: 1.28rem;
            letter-spacing: 0.8px;
            box-shadow: 0 12px 35px rgba(92,153,238,0.55);
            transition: all 0.4s;
            margin-top: 0.5rem;
        }

        .btn-register:hover {
            background: linear-gradient(135deg, var(--blue-dark), #3a78cd);
            transform: translateY(-6px);
            box-shadow: 0 20px 50px rgba(92,153,238,0.65);
        }

        .alert {
            border-radius: 18px;
            padding: 0.9rem 1.5rem;
            font-size: 0.98rem;
            margin-bottom: 1rem;
        }

        .text-center a {
            color: #333;
            font-weight: 700;
            font-size: 1.05rem;
            text-decoration: none;
        }

        .text-center a:hover {
            color: var(--blue);
        }

        /* MOBILE: TETAP AMAN */
        @media (max-width: 900px) {
            .auth-card { max-width: 92%; }
        }

        @media (max-width: 576px) {
            .card-header { padding: 1rem 1.5rem 0.9rem; }
            .card-header h1 { font-size: 2rem; }
            .card-body { padding: 1.3rem 2.5rem 1.6rem; }
            .form-control, .btn-register { height: 54px; font-size: 1rem; }
            textarea.form-control { height: 72px; }
            .toggle-password { font-size: 1.35rem; right: 18px; }
        }
    </style>
</head>
<body>
    <div class="auth-card">
        <div class="card-header">
            <h1>Register</h1>
            <p>Buat akun Artefax Anda sekarang</p>
        </div>

        <div class="card-body">
            <?php if ($message): ?>
                <div class="alert alert-<?= $message_type ?> alert-dismissible fade show">
                    <?= htmlspecialchars($message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form method="POST" novalidate>
                <input type="text" name="nama" class="form-control" placeholder="Nama Lengkap" required value="<?= htmlspecialchars($user->UserNama) ?>">

                <input type="email" name="email" class="form-control" placeholder="Email" required value="<?= htmlspecialchars($user->UserEmail) ?>">

                <div class="password-wrapper">
                    <input type="password" name="password" id="password" class="form-control" placeholder="Password (min. 6 karakter)" required minlength="6">
                    <button type="button" class="toggle-password" onclick="togglePass()">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>

                <input type="text" name="nohp" class="form-control" placeholder="No HP (Opsional)" value="<?= htmlspecialchars($user->UserNoHP) ?>">

                <textarea name="alamat" class="form-control" placeholder="Alamat Lengkap (Opsional)"><?= htmlspecialchars($user->UserAlamat) ?></textarea>

                <button type="submit" class="btn btn-register w-100">
                    Daftar Sekarang
                </button>
            </form>

            <div class="text-center mt-3">
                <a href="login.php">Sudah punya akun? Masuk di sini</a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function togglePass() {
            const field = document.getElementById('password');
            const icon = document.querySelector('.toggle-password i');
            if (field.type === 'password') {
                field.type = 'text';
                icon.classList.replace('bi-eye', 'bi-eye-slash');
            } else {
                field.type = 'password';
                icon.classList.replace('bi-eye-slash', 'bi-eye');
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            const nama = document.querySelector('input[name="nama"]');
            if (nama && !nama.value.trim()) nama.focus();
        });
    </script>
</body>
</html>