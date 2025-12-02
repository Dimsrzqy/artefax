<?php
session_start();
date_default_timezone_set('Asia/Jakarta');

require_once "../config/koneksi.php";
require_once "../class/users.php";

$db = new Database();
$conn = $db->getConnection();

$message = '';
$success = false;
$token_valid = false;
$email = '';

if (empty($_SESSION['reset_csrf'])) {
    $_SESSION['reset_csrf'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['reset_csrf'];

$token_input = $_GET['token'] ?? '';

if (!$conn) {
    $message = "Gagal terhubung ke database.";
} elseif (empty($token_input)) {
    $message = "Token tidak ditemukan.";
} else {
    $token = trim($token_input);

    $query = "SELECT Reset_Email, ResetExpires FROM password_resets WHERE ResetToken = ? AND ResetExpires > NOW()";
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        $message = "Sistem error.";
    } else {
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $row = $result->fetch_assoc();
            $email = $row['Reset_Email'];
            $token_valid = true;

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                if (!hash_equals($csrf_token, $_POST['csrf_token'] ?? '')) {
                    $message = "Permintaan tidak valid.";
                } else {
                    $password = trim($_POST['password'] ?? '');
                    $confirm  = trim($_POST['confirm'] ?? '');

                    if ($password !== $confirm) {
                        $message = "Password tidak cocok!";
                    } elseif (strlen($password) < 6) {
                        $message = "Password minimal 6 karakter!";
                    } else {
                        $hashed = password_hash($password, PASSWORD_DEFAULT);

                        $updateQuery = "UPDATE users SET UserPassword = ?, UpdatedAt = NOW() WHERE UserEmail = ?";
                        $updateStmt = $conn->prepare($updateQuery);
                        if ($updateStmt) {
                            $updateStmt->bind_param("ss", $hashed, $email);
                            if ($updateStmt->execute()) {
                                $deleteQuery = "DELETE FROM password_resets WHERE ResetToken = ?";
                                $deleteStmt = $conn->prepare($deleteQuery);
                                if ($deleteStmt) {
                                    $deleteStmt->bind_param("s", $token);
                                    $deleteStmt->execute();
                                    $deleteStmt->close();
                                }
                                $success = true;
                                $message = "Password berhasil diubah!";
                            } else {
                                $message = "Gagal mengubah password.";
                            }
                            $updateStmt->close();
                        }
                    }
                }
            }
        } else {
            $message = "Token tidak valid atau sudah kadaluarsa.";
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Artefax</title>
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
            margin-bottom: 1.2rem;
        }

        .form-control:focus {
            border-color: #5c99ee;
            box-shadow: 0 0 0 4px rgba(92, 153, 238, 0.25);
            outline: none;
        }

        /* IKON MATA */
        .password-wrapper {
            position: relative;
        }

        .password-wrapper .form-control {
            padding-right: 65px;
        }

        .toggle-password {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #777;
            font-size: 1.3rem;
            cursor: pointer;
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.3s;
        }

        .toggle-password:hover {
            background: rgba(92,153,238,0.15);
            color: #5c99ee;
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

        .btn-success {
            background: linear-gradient(135deg, #28a745, #20c997);
            border: none;
            border-radius: 50px;
            padding: 15px;
            font-weight: 600;
            font-size: 1.1rem;
            box-shadow: 0 6px 20px rgba(40,167,69,0.4);
        }

        .btn-success:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(40,167,69,0.5);
        }

        .alert {
            border-radius: 15px;
            font-size: 0.95rem;
            margin: 1rem 0;
            padding: 1.2rem 1.5rem;
        }

        .text-center a {
            color: #8a9ab0;
            text-decoration: none;
            font-size: 0.95rem;
            transition: all 0.3s;
        }

        .text-center a:hover {
            color: #5c99ee;
            text-decoration: underline;
        }

        @media (max-width: 480px) {
            .card-header {
                padding: 2.5rem 1rem;
            }
            .card-body {
                padding: 2rem 1.5rem;
            }
            .form-control {
                padding: 14px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="auth-card">
        <div class="card-header">
            <h3>Reset Password</h3>
            <p class="mb-0">Masukkan kata sandi baru Anda</p>
        </div>

        <div class="card-body">
            <?php if ($message): ?>
                <div class="alert alert-<?= $success ? 'success' : 'danger' ?> alert-dismissible fade show">
                    <?= htmlspecialchars($message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($token_valid && !$success): ?>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">

                    <div class="password-wrapper">
                        <input type="password" name="password" id="password" class="form-control" placeholder="Password Baru (min. 6 karakter)" required minlength="6">
                        <button type="button" class="toggle-password" onclick="togglePass('password')">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>

                    <div class="password-wrapper">
                        <input type="password" name="confirm" id="confirm" class="form-control" placeholder="Konfirmasi Password" required minlength="6">
                        <button type="button" class="toggle-password" onclick="togglePass('confirm')">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        Ubah Password
                    </button>
                </form>
            <?php elseif ($success): ?>
                <a href="login.php" class="btn btn-success w-100">Login Sekarang</a>
            <?php else: ?>
                <a href="forgot_password.php" class="btn btn-outline-primary w-100">Minta Ulang Link Reset</a>
            <?php endif; ?>

            <div class="text-center mt-4">
                <a href="login.php">Kembali ke Login</a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function togglePass(id) {
            const field = document.getElementById(id);
            const icon = field.parentElement.querySelector('.toggle-password i');
            if (field.type === 'password') {
                field.type = 'text';
                icon.classList.replace('bi-eye', 'bi-eye-slash');
            } else {
                field.type = 'password';
                icon.classList.replace('bi-eye-slash', 'bi-eye');
            }
        }
    </script>
</body>
</html>