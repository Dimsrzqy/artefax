<?php
session_start();

// === ZONA WAKTU ===
date_default_timezone_set('Asia/Jakarta');

require_once "../class/users.php";
require_once "../config/koneksi.php";

$db = new Database();
$conn = $db->getConnection();

$message = '';
$message_type = '';

// === CSRF TOKEN ===
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// === LOG ===
error_log("FORGOT PASSWORD START | time: " . date('Y-m-d H:i:s'));

if (!$conn) {
    $message = "Gagal terhubung ke database.";
    $message_type = 'danger';
} else {
    $user = new User($conn);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!hash_equals($csrf_token, $_POST['csrf_token'] ?? '')) {
            $message = "Permintaan tidak valid.";
            $message_type = 'danger';
        } else {
            $email = trim($_POST['email'] ?? '');

            if (empty($email)) {
                $message = "Email wajib diisi!";
                $message_type = 'danger';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $message = "Format email tidak valid!";
                $message_type = 'danger';
            } else {
                $userData = $user->getUserByEmail($email);

                if ($userData) {
                    // Hapus token lama
                    $stmtDelete = $conn->prepare("DELETE FROM password_resets WHERE Reset_Email = ?");
                    if ($stmtDelete) {
                        $stmtDelete->bind_param("s", $email);
                        $stmtDelete->execute();
                        $stmtDelete->close();
                    }

                    // Buat token baru
                    $token = bin2hex(random_bytes(32));
                    $expires = date('Y-m-d H:i:s', strtotime('+30 minutes'));

                    $stmtInsert = $conn->prepare("INSERT INTO password_resets (Reset_Email, ResetToken, ResetExpires) VALUES (?, ?, ?)");
                    if ($stmtInsert) {
                        $stmtInsert->bind_param("sss", $email, $token, $expires);
                        if ($stmtInsert->execute()) {
                            $stmtInsert->close();
                            $resetLink = "http://artefax.test/View/reset_password.php?token=" . $token;
                            header("Location: " . $resetLink);
                            exit();
                        } else {
                            $message = "Gagal menyimpan token.";
                            $message_type = 'danger';
                        }
                    } else {
                        $message = "Sistem error.";
                        $message_type = 'danger';
                    }
                } else {
                    $message = "Jika email terdaftar, Anda akan diarahkan.";
                    $message_type = 'info';
                }
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

        /* === CARD CENTERED === */
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
            margin-bottom: 1.5rem;
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
            <h3>Lupa Password?</h3>
            <p class="mb-0">Masukkan email untuk reset kata sandi</p>
        </div>
        <div class="card-body">
            <!-- PESAN -->
            <?php if ($message): ?>
                <div class="alert alert-<?= $message_type ?> alert-dismissible fade show">
                    <?= htmlspecialchars($message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- FORM -->
            <form method="POST" novalidate>
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                <div class="mb-3">
                    <label for="email" class="form-label visually-hidden">Email</label>
                    <input 
                        type="email" 
                        id="email"
                        name="email" 
                        class="form-control" 
                        required 
                        placeholder="email@contoh.com"
                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                        autocomplete="email"
                    >
                </div>
                <button type="submit" class="btn btn-primary w-100">
                    Lanjutkan ke Reset Password
                </button>
            </form>

            <div class="text-center mt-4">
                <a href="../view/login.php">Kembali ke Login</a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Fokus otomatis
        document.addEventListener('DOMContentLoaded', () => {
            const emailInput = document.getElementById('email');
            if (emailInput && !emailInput.value) {
                emailInput.focus();
            }
        });
    </script>
</body>
</html>