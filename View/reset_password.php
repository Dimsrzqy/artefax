<?php
session_start();
require "../class/users.php";
require "../config/koneksi.php";
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require '../vendor/autoload.php'; // Path ke autoload dari /View/

$db = new Database();
$conn = $db->getConnection();

ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'C:\laragon\log\php_error.log');
error_log("Reset password process started for token: " . ($_GET['token'] ?? 'no token'));

$message = '';

if ($conn === null) {
    $message = "Gagal terhubung ke database. Silakan coba lagi nanti.";
} else {
    $user = new User($conn);

    if (isset($_GET['token']) && !empty($_GET['token'])) {
        $token = htmlspecialchars(trim(strip_tags($_GET['token'])));
        
        // Verifikasi token
        $query = "SELECT email, expires FROM password_resets WHERE token = ? AND expires > NOW()";
        $stmt = $conn->prepare($query);
        if ($stmt === false) {
            error_log("Prepare failed: " . $conn->error);
            $message = "Gagal memverifikasi token. Periksa log.";
        } else {
            $stmt->bind_param("s", $token);
            $stmt->execute();
            $result = $stmt->get_result();
            $resetData = $result->fetch_assoc();
            error_log("Query result: " . json_encode($resetData));
            if ($resetData) {
                error_log("Valid token found for email: " . $resetData['email']);
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $newPassword = $_POST['password'];
                    $confirmPassword = $_POST['confirm_password'];

                    if ($newPassword !== $confirmPassword) {
                        $message = "Kata sandi baru dan konfirmasi tidak cocok!";
                    } elseif (strlen($newPassword) < 6) {
                        $message = "Kata sandi harus minimal 6 karakter!";
                    } else {
                        // Hash password baru
                        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                        
                        // Update password di tabel users
                        $email = $resetData['email'];
                        $updateQuery = "UPDATE users SET Password = ? WHERE Email = ?"; // Sesuaikan nama kolom
                        $updateStmt = $conn->prepare($updateQuery);
                        if ($updateStmt === false) {
                            error_log("Prepare failed: " . $conn->error);
                            $message = "Gagal menyiapkan query. Periksa log.";
                        } else {
                            $updateStmt->bind_param("ss", $hashedPassword, $email);
                            if ($updateStmt->execute()) {
                                error_log("Password updated successfully for email: $email");
                                // Hapus token dan session
                                session_destroy();
                                error_log("Session destroyed");
                                $deleteQuery = "DELETE FROM password_resets WHERE token = ?";
                                $deleteStmt = $conn->prepare($deleteQuery);
                                if ($deleteStmt === false) {
                                    error_log("Delete prepare failed: " . $conn->error);
                                } else {
                                    $deleteStmt->bind_param("s", $token);
                                    $deleteStmt->execute();
                                    $deleteStmt->close();
                                }
                                // Redirect otomatis ke login.php dengan path absolut
                                header("Location: http://localhost/Artefax/view/login.php");
                                exit();
                            } else {
                                error_log("Execute failed: " . $updateStmt->error . " for email: $email");
                                $message = "Gagal mengubah kata sandi. Error: " . $updateStmt->error;
                            }
                            $updateStmt->close();
                        }
                    }
                }
            } else {
                $message = "Token tidak valid atau telah kedaluwarsa. Periksa log untuk detail.";
            }
            $stmt->close(); // Tutup statement verifikasi token
        }
    } else {
        $message = "Token tidak ditemukan. Silakan minta ulang link reset.";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Artefax</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" crossorigin="anonymous">
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card mt-5">
                    <div class="card-body">
                        <h2 class="card-title text-center mb-4">Reset Password</h2>
                        <p class="text-center">Masukkan kata sandi baru untuk akun Anda.</p>
                        <?php if ($message): ?>
                            <div class="alert <?php echo strpos($message, 'Gagal') !== false || strpos($message, 'tidak') !== false ? 'alert-danger' : 'alert-success'; ?> mt-3" role="alert">
                                <?php echo htmlspecialchars($message); ?>
                            </div>
                        <?php endif; ?>
                        <?php if (empty($message) || strpos($message, 'berhasil') === false): ?>
                            <form method="POST">
                                <div class="mb-3">
                                    <label for="password" class="form-label">Kata Sandi Baru</label>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                </div>
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Konfirmasi Kata Sandi</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                </div>
                                <button type="submit" class="btn btn-primary w-100">Ubah Kata Sandi</button>
                            </form>
                        <?php endif; ?>
                        <div class="text-center mt-3">
                            <a href="login.php" class="link-primary">Kembali ke Login</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-geWF76RCwLtnZ8qwWowPQNguL3RmwHVBC9FhGdlKrxdiJJigb/j/68SIy3Te4Bkz" crossorigin="anonymous"></script>
</body>
</html>