<?php
session_start();
date_default_timezone_set('Asia/Jakarta');

require_once "../class/Users.php";
require_once "../config/koneksi.php";

$db = new Database();
$conn = $db->getConnection();

// === INISIALISASI VARIABEL ===
$message      = '';
$message_type = '';

// === CSRF TOKEN ===
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// === PROSES FORM ===
if (!$conn) {
    $message      = "Gagal terhubung ke database.";
    $message_type = 'danger';
} else {
    $user = new User($conn);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!hash_equals($csrf_token, $_POST['csrf_token'] ?? '')) {
            $message      = "Permintaan tidak valid.";
            $message_type = 'danger';
        } else {
            $email = trim($_POST['email'] ?? '');

            if (empty($email)) {
                $message      = "Email wajib diisi!";
                $message_type = 'danger';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $message      = "Format email tidak valid!";
                $message_type = 'danger';
            } else {
                $userData = $user->getUserByEmail($email);

                if ($userData) {
                    // Hapus token lama
                    $stmt = $conn->prepare("DELETE FROM password_resets WHERE Reset_Email = ?");
                    $stmt?->bind_param("s", $email);
                    $stmt?->execute();
                    $stmt?->close();

                    // Buat token baru
                    $token    = bin2hex(random_bytes(32));
                    $expires  = date('Y-m-d H:i:s', strtotime('+30 minutes'));

                    $stmt = $conn->prepare("INSERT INTO password_resets (Reset_Email, ResetToken, ResetExpires) VALUES (?, ?, ?)");
                    if ($stmt) {
                        $stmt->bind_param("sss", $email, $token, $expires);
                        $stmt->execute();
                        $stmt->close();

                        $resetLink = "http://localhost/Artefax/view/reset_password.php?token=" . $token;
                        header("Location: " . $resetLink);
                        exit();
                    }
                }

                // Selalu kasih pesan sukses (keamanan)
                $message      = "Jika email terdaftar, link reset telah dikirim ke email Anda.";
                $message_type = 'success';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password - Artefax</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
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
            border-radius: 24px;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.25);
            overflow: hidden;
            max-width: 520px;
            width: 100%;
            animation: fadeIn 0.8s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(40px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .card-header {
            background: linear-gradient(135deg, #5c99ee, #4c89de);
            color: white;
            text-align: center;
            padding: 3.5rem 2rem 2.8rem;
        }

        .card-header h3 {
            margin: 0;
            font-weight: 700;
            font-size: 2rem;
            letter-spacing: 0.6px;
        }

        .card-header p {
            margin: 0.8rem 0 0;
            opacity: 0.95;
            font-size: 1.05rem;
        }

        .card-body {
            padding: 3rem 3rem 3.5rem;
        }

        .form-control {
            height: 62px;
            border-radius: 50px;
            padding: 0 28px;
            border: 1.8px solid #e0e0e0;
            font-size: 1.1rem;
            transition: all 0.3s;
            box-shadow: 0 4px 12px rgba(0,0,0,0.07);
            background: #fafbff;
        }

        .form-control:focus {
            border-color: #5c99ee;
            box-shadow: 0 0 0 5px rgba(92, 153, 238, 0.25);
            background: white;
        }

        .btn-primary {
            height: 64px;
            border-radius: 50px;
            background: linear-gradient(135deg, #5c99ee, #4c89de);
            border: none;
            color: white;
            font-size: 1.25rem;
            font-weight: 600;
            letter-spacing: 0.7px;
            box-shadow: 0 8px 25px rgba(92,153,238,0.45);
            transition: all 0.4s;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #4c89de, #3a78cd);
            transform: translateY(-4px);
            box-shadow: 0 15px 35px rgba(92,153,238,0.55);
        }

        .alert {
            border-radius: 16px;
            padding: 1.2rem 1.6rem;
            font-size: 0.98rem;
            margin-bottom: 1.5rem;
        }

        .text-center a {
            color: #777;
            font-weight: 600;
            font-size: 1rem;
            text-decoration: none;
        }

        .text-center a:hover {
            color: #5c99ee;
            text-decoration: underline;
        }

        @media (max-width: 576px) {
            .auth-card { max-width: 94%; border-radius: 20px; }
            .card-header { padding: 2.8rem 1.5rem 2.2rem; }
            .card-header h3 { font-size: 1.8rem; }
            .card-body { padding: 2.5rem 2rem; }
            .form-control, .btn-primary { height: 58px; }
        }
    </style>
</head>
<body>
    <div class="auth-card">
        <div class="card-header">
            <h3>Lupa Password?</h3>
            <p class="mb-0">Masukkan email untuk mendapatkan link reset</p>
        </div>

        <div class="card-body">
            <?php if ($message): ?>
                <div class="alert alert-<?= $message_type ?> alert-dismissible fade show">
                    <?= htmlspecialchars($message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form method="POST" novalidate>
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">

                <input 
                    type="email" 
                    name="email" 
                    class="form-control text-center" 
                    placeholder="email@contoh.com"
                    required 
                    value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                    autocomplete="email"
                >

                <button type="submit" class="btn btn-primary w-100 mt-4">
                    Kirim Link Reset
                </button>
            </form>

            <div class="text-center mt-4">
                <a href="login.php">Kembali ke Login</a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const input = document.querySelector('input[name="email"]');
            if (input && !input.value.trim()) input.focus();
        });
    </script>
</body>
</html>