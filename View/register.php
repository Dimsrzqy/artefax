<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Artefax</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" crossorigin="anonymous">
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card mt-5">
                    <div class="card-body">
                        <h2 class="card-title text-center mb-4">Register</h2>
                        <?php
                        session_start();
                        require "../config/koneksi.php";
                        require "../class/users.php";

                        $db = new Database();
                        $conn = $db->getConnection();
                        $user = new User($conn);

                        $message = '';
                        $message_type = '';

                        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                            // Sanitasi input
                            $user->NamaUser = htmlspecialchars(trim(strip_tags($_POST['nama'])));
                            $user->Email = htmlspecialchars(trim(strip_tags($_POST['email'])));
                            $user->Password = $_POST['password']; // Akan di-hash di metode register
                            $user->NoHP = htmlspecialchars(trim(strip_tags($_POST['nohp'])));
                            $user->Alamat = htmlspecialchars(trim(strip_tags($_POST['alamat'])));
                            $user->Role = 'Customer'; // Set role otomatis ke 'Customer'

                            // Validasi sederhana
                            if (!filter_var($user->Email, FILTER_VALIDATE_EMAIL)) {
                                $message = "Format email tidak valid!";
                                $message_type = 'danger';
                            } else {
                                if ($user->register()) {
                                    $message = "Registrasi berhasil! Silakan login.";
                                    $message_type = 'success';
                                    // Reset form (opsional)
                                    $user->NamaUser = $user->Email = $user->Password = $user->NoHP = $user->Alamat = '';
                                } else {
                                    $message = "Registrasi gagal! Email mungkin sudah digunakan atau terjadi kesalahan lain. Periksa log untuk detail.";
                                    $message_type = 'danger';
                                    error_log("Registration failed for email: " . $user->Email);
                                }
                            }
                        }
                        ?>

                        <form method="POST">
                            <div class="mb-3">
                                <label for="nama" class="form-label">Nama</label>
                                <input type="text" class="form-control" id="nama" name="nama" value="<?php echo htmlspecialchars($user->NamaUser ?? ''); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user->Email ?? ''); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <div class="mb-3">
                                <label for="nohp" class="form-label">No HP</label>
                                <input type="text" class="form-control" id="nohp" name="nohp" value="<?php echo htmlspecialchars($user->NoHP ?? ''); ?>">
                            </div>
                            <div class="mb-3">
                                <label for="alamat" class="form-label">Alamat</label>
                                <textarea class="form-control" id="alamat" name="alamat"><?php echo htmlspecialchars($user->Alamat ?? ''); ?></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Register</button>
                            <?php if ($message): ?>
                                <div class="alert alert-<?php echo $message_type; ?> mt-3" role="alert">
                                    <?php echo htmlspecialchars($message); ?>
                                </div>
                            <?php endif; ?>
                            <div class="text-center mt-3">
                                <a href="login.php" class="link-primary">Sudah punya akun? Login</a>
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