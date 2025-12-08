<?php
session_start();
date_default_timezone_set('Asia/Jakarta');

$pathUserClass = __DIR__ . "/../class/Users.php";
$pathKoneksi   = __DIR__ . "/../config/koneksi.php";

if (!file_exists($pathUserClass) || !file_exists($pathKoneksi)) {
    die("<p style='color:red; text-align:center;'>File sistem tidak ditemukan.</p>");
}

require_once $pathKoneksi;
require_once $pathUserClass;

$message = '';
$message_type = 'danger';

try {
    $db = new Database();
    $conn = $db->getConnection();
    $user = new User($conn);
} catch (Throwable $e) {
    error_log("Koneksi gagal: " . $e->getMessage());
    $message = "Sistem sedang maintenance. Coba lagi nanti.";
    $message_type = 'danger';
}

if (isset($_GET['logout']) && $_GET['logout'] === 'success') {
    $message = "Anda berhasil logout.";
    $message_type = 'success';
}

$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Format email tidak valid!";
    } elseif (empty($password)) {
        $message = "Password tidak boleh kosong!";
    } else {
        $user->UserEmail = $email;
        $user->UserPassword = $password;

        $login = $user->login();

        if ($login) {
            $_SESSION['user'] = [
                'IDUser'    => $login['IDUser'],
                'UserNama'  => $login['UserNama'],
                'UserEmail' => $login['UserEmail'],
                'UserRole'  => $login['UserRole']
            ];

            $role = strtolower($login['UserRole']);
            switch ($role) {
                case 'customer':
                    header("Location: ../Paket/Services.php"); exit;
                case 'service':
                    header("Location: ../service/index.php"); exit;
                case 'karyawan':
                    header("Location: ../dasboardKaryawan/index.html"); exit;
                case 'admin':
                default:
                    header("Location: ../adminArtefax/index.html"); exit;
            }
        } else {
            $message = "Email atau password salah!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Artefax</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        html, body {
            height: 100%;
            margin: 0;
            background: linear-gradient(135deg, #5c99ee 0%, #4c89de 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 1rem;
        }

        .auth-card {
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            max-width: 420px;
            width: 100%;
            animation: fadeIn 0.7s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .card-header {
            background: linear-gradient(135deg, #5c99ee, #4c89de);
            color: white;
            text-align: center;
            padding: 3rem 1.5rem 2.5rem;
            border: none;
        }

        .card-header h3 {
            margin: 0;
            font-weight: 700;
            font-size: 1.8rem;
            letter-spacing: 0.5px;
        }

        .card-header p {
            margin: 0.7rem 0 0;
            opacity: 0.95;
            font-size: 1rem;
        }

        .card-body {
            padding: 2.5rem;
        }

        .form-control {
            border-radius: 50px;
            padding: 15px 22px;
            border: 1.5px solid #e0e0e0;
            font-size: 1rem;
            transition: all 0.3s;
            box-shadow: 0 3px 8px rgba(0,0,0,0.06);
        }

        .form-control:focus {
            border-color: #5c99ee;
            box-shadow: 0 0 0 4px rgba(92, 153, 238, 0.25);
            outline: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, #5c99ee, #4c89de);
            border: none;
            border-radius: 50px;
            padding: 15px;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.4s;
            box-shadow: 0 6px 20px rgba(92, 153, 238, 0.4);
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #4c89de, #3a78cd);
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(92, 153, 238, 0.5);
        }

        .alert {
            border-radius: 15px;
            font-size: 0.95rem;
            margin: 1rem 0;
            border: none;
            padding: 1.2rem 1.5rem;
        }

        .link-group a {
            color: #8a9ab0;
            text-decoration: none;
            font-size: 0.95rem;
            transition: all 0.3s;
        }

        .link-group a:hover {
            color: #5c99ee;
            text-decoration: underline;
        }

        .link-group {
            display: flex;
            justify-content: space-between;
            margin-top: 1.5rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        @media (max-width: 480px) {
            .card-header {
                padding: 2.5rem 1rem;
            }
            .card-body {
                padding: 2rem 1.5rem;
            }
            .link-group {
                flex-direction: column;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="auth-card">
        <div class="card-header">
            <h3>Login Akun Artefax</h3>
            <p class="mb-0">Masuk untuk melanjutkan</p>
        </div>
        <div class="card-body">
            <?php if ($message): ?>
                <div class="alert alert-<?= $message_type ?> alert-dismissible fade show">
                    <?= htmlspecialchars($message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form method="POST" novalidate>
                <div class="mb-3">
                    <input 
                        type="email" 
                        name="email" 
                        class="form-control" 
                        required 
                        placeholder="email@contoh.com"
                        value="<?= htmlspecialchars($email) ?>"
                        autocomplete="email"
                    >
                </div>
                <div class="mb-4">
                    <input 
                        type="password" 
                        name="password" 
                        class="form-control" 
                        required 
                        placeholder="Password"
                        autocomplete="current-password"
                    >
                </div>
                <button type="submit" class="btn btn-primary w-100">
                    Login Sekarang
                </button>
            </form>

            <div class="link-group">
                <a href="register.php">Belum punya akun? Daftar</a>
                <a href="forgot_password.php">Lupa Password?</a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const emailInput = document.querySelector('input[name="email"]');
            if (emailInput && !emailInput.value) emailInput.focus();
        });
    </script>
</body>
</html>