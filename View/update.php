<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update User - Artefax</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" crossorigin="anonymous">
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card mt-5">
                    <div class="card-body">
                        <h2 class="card-title text-center mb-4">Update User</h2>
                        <?php
                        require_once __DIR__ . "/../config/koneksi.php"; // Diperbarui dari Database.php ke koneksi.php
                        require_once __DIR__ . "/../class/users.php";

                        $db = new Database();
                        $conn = $db->getConnection();
                        $user = new User($conn);

                        $message = '';
                        $message_type = '';

                        if (isset($_GET['id'])) {
                            $id = (int)$_GET['id'];
                            $user->IDUser = $id;
                            $result = $conn->query("SELECT * FROM users WHERE IDUser = $id LIMIT 1");
                            if ($result && $result->num_rows > 0) {
                                $userData = $result->fetch_assoc();
                            } else {
                                $message = "User tidak ditemukan!";
                                $message_type = 'danger';
                            }
                        }

                        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
                            $user->IDUser = (int)$_POST['id'];
                            $user->NamaUser = htmlspecialchars(trim(strip_tags($_POST['nama'])));
                            $user->Email = htmlspecialchars(trim(strip_tags($_POST['email'])));
                            $user->Role = htmlspecialchars(trim(strip_tags($_POST['role'])));
                            $user->NoHP = htmlspecialchars(trim(strip_tags($_POST['nohp'])));
                            $user->Alamat = htmlspecialchars(trim(strip_tags($_POST['alamat'])));

                            $validRoles = ['Admin', 'Karyawan', 'Customer'];
                            $user->Role = ucfirst(strtolower($user->Role));
                            if (!in_array($user->Role, $validRoles)) {
                                $message = "Role harus 'Admin', 'Karyawan', atau 'Customer'!";
                                $message_type = 'danger';
                            } else {
                                if ($user->updateProfile()) {
                                    $message = "Update berhasil!";
                                    $message_type = 'success';
                                    header("Location: list.php");
                                    exit();
                                } else {
                                    $message = "Update gagal! Periksa log untuk detail.";
                                    $message_type = 'danger';
                                    error_log("Update failed for ID: " . $user->IDUser . ", Error: " . $conn->error);
                                }
                            }
                        }
                        ?>

                        <?php if ($message): ?>
                            <div class="alert alert-<?php echo $message_type; ?> mt-3" role="alert">
                                <?php echo htmlspecialchars($message); ?>
                            </div>
                        <?php endif; ?>

                        <?php if (isset($userData)): ?>
                            <form method="POST">
                                <input type="hidden" name="id" value="<?php echo htmlspecialchars($userData['IDUser']); ?>">
                                <div class="mb-3">
                                    <label for="nama" class="form-label">Nama</label>
                                    <input type="text" class="form-control" id="nama" name="nama" value="<?php echo htmlspecialchars($userData['NamaUser']); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($userData['Email']); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="role" class="form-label">Role</label>
                                    <select class="form-select" id="role" name="role" required>
                                        <option value="Admin" <?php echo ($userData['Role'] === 'Admin') ? 'selected' : ''; ?>>Admin</option>
                                        <option value="Karyawan" <?php echo ($userData['Role'] === 'Karyawan') ? 'selected' : ''; ?>>Karyawan</option>
                                        <option value="Customer" <?php echo ($userData['Role'] === 'Customer') ? 'selected' : ''; ?>>Customer</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="nohp" class="form-label">No HP</label>
                                    <input type="text" class="form-control" id="nohp" name="nohp" value="<?php echo htmlspecialchars($userData['NoHP'] ?? ''); ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="alamat" class="form-label">Alamat</label>
                                    <textarea class="form-control" id="alamat" name="alamat"><?php echo htmlspecialchars($userData['Alamat'] ?? ''); ?></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary w-100">Update</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-geWF76RCwLtnZ8qwWowPQNguL3RmwHVBC9FhGdlKrxdiJJigb/j/68SIy3Te4Bkz" crossorigin="anonymous"></script>
</body>
</html>