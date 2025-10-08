<?php
session_start();
require "../class/users.php";
require "../config/koneksi.php";

$db = new Database();
$conn = $db->getConnection();

$message = '';

if ($conn === null) {
    $message = "Gagal terhubung ke database. Silakan coba lagi nanti.";
} else {
    $user = new User($conn);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $email = htmlspecialchars(trim(strip_tags($_POST['email'])));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = "Format email tidak valid!";
        } else {
            $userData = $user->getUserByEmail($email);
            if ($userData) {
                $token = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', strtotime('+24 hours'));

                if ($user->saveResetToken($email, $token, $expires)) {
                    // Redirect otomatis ke halaman reset dengan token
                    $resetLink = "http://artefax.test/View/reset_password.php?token=" . $token;
                    header("Location: " . $resetLink);
                    exit(); // Pastikan script berhenti setelah redirect
                } else {
                    $message = "Gagal menyimpan token di database.";
                }
            } else {
                $message = "Email tidak ditemukan!";
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
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" crossorigin="anonymous">
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card mt-5">
                    <div class="card-body">
                        <h2 class="card-title text-center mb-4">Lupa Password</h2>
                        <p class="text-center">Masukkan alamat email Anda untuk menerima link reset password.</p>
                        <form method="POST">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Kirim Link Reset</button>
                            <?php if ($message): ?>
                                <div class="alert <?php echo strpos($message, 'Gagal') !== false || strpos($message, 'tidak') !== false ? 'alert-danger' : 'alert-success'; ?> mt-3" role="alert">
                                    <?php echo htmlspecialchars($message); ?>
                                </div>
                            <?php endif; ?>
                            <div class="text-center mt-3">
                                <a href="login.php" class="link-primary">Kembali ke Login</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-geWF76RCwLtnZ8qwWowPQNguL3RmwHVBC9FhGdlKrxdiJJigb/j/68SIy3Te4Bkz" crossorigin="anonymous"></script>
</body>
</html>