<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Artefax</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" crossorigin="anonymous">
</head>
<body>
<?php
session_start();

// Paths (sesuaikan jika struktur folder beda)
$pathUserClass = __DIR__ . "/../class/users.php";
$pathKoneksi   = __DIR__ . "/../config/koneksi.php";

// Jika file include tidak ada => 404
if (!file_exists($pathUserClass) || !file_exists($pathKoneksi)) {
    header("Location: /404.html");
    exit;
}

// Include dengan aman
require_once $pathKoneksi;
require_once $pathUserClass;

$message = '';

try {
    // Inisialisasi DB dan User (asumsi kelas sesuai)
    $db = new Database();
    $conn = $db->getConnection();
    $user = new User($conn);
} catch (Throwable $e) {
    // Log error server-side (file log), jangan tampilkan ke user
    error_log("Init error on login page: " . $e->getMessage());
    header("Location: /error.html");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitasi input
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';

    // Validasi email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Format email tidak valid!";
    } elseif (empty($password)) {
        $message = "Password tidak boleh kosong!";
    } else {
        try {
            // Asumsi: $user->login($email, $password) mengembalikan data user atau false
            // Jika implementasimu berbeda, sesuaikan pemanggilan berikut
            $user->Email = $email;          // jika class mengandalkan properti
            $user->Password = $password;    // atau panggil method login dengan argumen
            $login = $user->login();        // asumsi internal melakukan verifikasi

            if ($login) {
                // Set session
                $_SESSION['user'] = [
                    'IDUser'   => $login['IDUser'],
                    'NamaUser' => $login['NamaUser'],
                    'Email'    => $login['Email'],
                    'Role'     => $login['Role']
                ];

                // Redirect berdasar role
                if (strtolower($login['Role']) === 'customer') {
                    header("Location: ../index.php");
                } else {
                    header("Location: list.php");
                }
                exit;
            } else {
                // Pilihan: tampilkan pesan biasa (UX) atau redirect ke error.html (amankan info)
                // Saya rekomendasikan menampilkan pesan singkat:
                $message = "Email atau password salah!";
                // Jika tetap ingin redirect ke halaman umum:
                // header("Location: /error.html"); exit;
            }
        } catch (Throwable $e) {
            // Log error untuk developer; redirect ke halaman error umum untuk user
            error_log("Login error: " . $e->getMessage());
            header("Location: /error.html");
            exit;
        }
    }
}
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card mt-5">
                <div class="card-body">
                    <h2 class="card-title text-center mb-4">Login</h2>

                    <form method="POST" novalidate>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email"
                                   required value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>">
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Login</button>

                        <?php if ($message): ?>
                            <div class="alert alert-danger mt-3" role="alert">
                                <?php echo htmlspecialchars($message); ?>
                            </div>
                        <?php endif; ?>

                        <div class="text-center mt-3">
                            <a href="register.php" class="link-primary">Belum punya akun? Register</a>
                        </div>
                        <div class="text-center mt-2">
                            <a href="forgot_password.php" class="link-primary">Lupa Password?</a>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-geWF76RCwLtnZ8qwWowPQNguL3RmwHVBC9FhGdlKrxdiJJigb/j/68SIy3Te4Bkz" crossorigin="anonymous"></script>
</body>
</html>
