<?php
session_start();

// === ZONA WAKTU ===
date_default_timezone_set('Asia/Jakarta');

// Path ke file class & koneksi
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

// Notifikasi logout
if (isset($_GET['logout']) && $_GET['logout'] === 'success') {
    $message = "Anda berhasil logout.";
    $message_type = 'success';
}

$email = '';

// Proses Login
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
                    header("Location: ../index.php");
                    break;
                case 'service':
                    header("Location: ../service/index.php");
                    break;
                case 'admin':
                default:
                    header("Location: ../adminArtefax/index.html");
                    break;
            }
            exit;
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
            max-width: 420px;
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

        .form-control {
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

        .link-group {
            display: flex;
            justify-content: space-between;
            margin-top: 1rem;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        @media (max-width: 480px) {
            .card-header {
                padding: 2rem 1rem;
            }
            .card-body {
                padding: 1.5rem;
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
            <!-- PESAN -->
            <?php if ($message): ?>
                <div class="alert alert-<?= $message_type ?> alert-dismissible fade show">
                    <?= htmlspecialchars($message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- FORM LOGIN -->
            <form method="POST" novalidate>
                <div class="mb-3">
                    <label for="email" class="form-label visually-hidden">Email</label>
                    <input 
                        type="email" 
                        id="email"
                        name="email" 
                        class="form-control" 
                        required 
                        placeholder="email@contoh.com"
                        value="<?= htmlspecialchars($email) ?>"
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
                        placeholder="Password"
                        autocomplete="current-password"
                    >
                </div>
                <button type="submit" class="btn btn-primary w-100">
                    Login
                </button>
            </form>

            <!-- LINKS -->
            <div class="link-group">
                <a href="register.php">Belum punya akun? Daftar</a>
                <a href="forgot_password.php">Lupa Password?</a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Fokus otomatis ke email
        document.addEventListener('DOMContentLoaded', () => {
            const emailInput = document.getElementById('email');
            if (emailInput && !emailInput.value) {
                emailInput.focus();
            }
        });
    </script>
</body>
</html>