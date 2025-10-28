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
error_log("RESET START | token: $token_input | time: " . date('Y-m-d H:i:s'));

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
        error_log("Prepare failed: " . $conn->error);
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

                        // PERBAIKAN: UserPassword + UserEmail
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
                                header("Location: http://localhost/Artefax/view/login.php");
                                exit();
                            } else {
                                $message = "Gagal mengubah password.";
                            }
                            $updateStmt->close();
                        } else {
                            $message = "Sistem error.";
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
    <title>Reset Password</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; }
        .card { max-width: 420px; margin: auto; border-radius: 16px; box-shadow: 0 15px 35px rgba(0,0,0,0.1); }
        .card-header { background: linear-gradient(135deg, #667eea, #764ba2); color: white; text-align: center; padding: 2rem; }
        .form-control { border-radius: 50px; padding: 12px 20px; }
        .btn-primary { background: #667eea; border: none; border-radius: 50px; padding: 12px; font-weight: 600; }
        .btn-primary:hover { background: #5a6fd8; }
        .btn-success { background: #28a745; border-radius: 50px; padding: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h3>Reset Password</h3>
                <p class="mb-0 opacity-75">Masukkan kata sandi baru</p>
            </div>
            <div class="card-body p-4">
                <?php if ($message): ?>
                    <div class="alert alert-<?= $success ? 'success' : 'danger' ?> alert-dismissible fade show">
                        <?= htmlspecialchars($message) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($token_valid && !$success): ?>
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                        <div class="mb-3">
                            <label class="form-label">Password Baru</label>
                            <input type="password" name="password" class="form-control" required minlength="6">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Konfirmasi</label>
                            <input type="password" name="confirm" class="form-control" required minlength="6">
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Ubah Password</button>
                    </form>
                <?php elseif ($success): ?>
                    <a href="login.php" class="btn btn-success w-100">Login Sekarang</a>
                <?php else: ?>
                    <a href="forgot_password.php" class="btn btn-outline-secondary w-100">Minta Ulang Token</a>
                <?php endif; ?>

                <div class="text-center mt-3">
                    <a href="login.php" class="text-muted small">Kembali ke Login</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>