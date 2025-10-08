<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Artefax</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" crossorigin="anonymous">
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card mt-5">
                    <div class="card-body">
                        <h2 class="card-title text-center mb-4">Login</h2>
                        <?php
                        session_start();

                        // Sesuaikan path berdasarkan struktur folder
                        require "../class/users.php";
                        require "../config/koneksi.php";

                        $db = new Database();
                        $conn = $db->getConnection();
                        $user = new User($conn);

                        $message = '';

                        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                            // Sanitasi input
                            $user->Email = htmlspecialchars(trim(strip_tags($_POST['email'])));
                            $user->Password = $_POST['password'];

                            // Validasi email
                            if (!filter_var($user->Email, FILTER_VALIDATE_EMAIL)) {
                                $message = "Format email tidak valid!";
                            } else {
                                $login = $user->login();
                                if ($login) {
                                    $_SESSION['user'] = [
                                        'IDUser' => $login['IDUser'],
                                        'NamaUser' => $login['NamaUser'],
                                        'Email' => $login['Email'],
                                        'Role' => $login['Role']
                                    ];
                                    // Redirect based on role
                                    if (strtolower($login['Role']) === 'admin') {
                                        header("Location: ../index.php");
                                    } else {
                                        header("Location: list.php");
                                    }
                                    exit;
                                } else {
                                    $message = "Email atau password salah!";
                                }
                            }
                        }
                        ?>

                        <form method="POST">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" required>
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-geWF76RCwLtnZ8qwWowPQNguL3RmwHVBC9FhGdlKrxdiJJigb/j/68SIy3Te4Bkz" crossorigin="anonymous"></script>
</body>
</html>