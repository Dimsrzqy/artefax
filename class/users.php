<?php
class User {
    private $conn;
    private $table = "users";

    // Kolom utama
    public $IDUser;
    public $UserNama;
    public $UserEmail;
    public $UserPassword;
    public $UserRole;
    public $UserNoHP;
    public $UserAlamat;
    public $CreatedAt;
    public $UpdatedAt;

    public function __construct($db) {
        $this->conn = $db;
    }

    /* ==========================================================
       🔹 REGISTER USER BARU
    ========================================================== */
public function register($forceRole = null) {
    // Cek apakah email sudah terdaftar
    $checkQuery = "SELECT UserEmail FROM {$this->table} WHERE UserEmail = ? LIMIT 1";
    $checkStmt = $this->conn->prepare($checkQuery);
    if (!$checkStmt) {
        error_log("Prepare check failed: " . $this->conn->error);
        return false;
    }

    $this->UserEmail = filter_var(trim($this->UserEmail), FILTER_SANITIZE_EMAIL);
    $checkStmt->bind_param("s", $this->UserEmail);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();

    if ($checkResult->num_rows > 0) {
        $checkStmt->close();
        return false; // Email sudah digunakan
    }
    $checkStmt->close();

    // Query INSERT
    $query = "INSERT INTO {$this->table} 
              (UserNama, UserEmail, UserPassword, UserRole, UserNoHP, UserAlamat, CreatedAt, UpdatedAt)
              VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $this->conn->prepare($query);
    if (!$stmt) {
        error_log("Prepare insert failed: " . $this->conn->error);
        return false;
    }

    // Sanitasi
    $this->UserNama   = htmlspecialchars(trim($this->UserNama));
    $this->UserEmail  = filter_var(trim($this->UserEmail), FILTER_SANITIZE_EMAIL);
    $this->UserPassword = password_hash($this->UserPassword, PASSWORD_DEFAULT);
    $this->UserNoHP   = htmlspecialchars(trim($this->UserNoHP));
    $this->UserAlamat = htmlspecialchars(trim($this->UserAlamat));
    $this->CreatedAt  = date('Y-m-d H:i:s');
    $this->UpdatedAt  = date('Y-m-d H:i:s');

    // PAKSA ROLE JIKA ADA
    if ($forceRole && in_array($forceRole, ['Karyawan', 'Customer', 'Admin'])) {
        $this->UserRole = $forceRole;
    } else {
        $this->UserRole = 'Customer'; // default
    }

    $stmt->bind_param(
        "ssssssss",
        $this->UserNama,
        $this->UserEmail,
        $this->UserPassword,
        $this->UserRole,
        $this->UserNoHP,
        $this->UserAlamat,
        $this->CreatedAt,
        $this->UpdatedAt
    );

    $result = $stmt->execute();
    $stmt->close();
    return $result;
}

    /* ==========================================================
       🔹 LOGIN USER
    ========================================================== */
    public function login() {
        $query = "SELECT * FROM " . $this->table . " WHERE UserEmail = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        if (!$stmt) return false;

        $stmt->bind_param("s", $this->UserEmail);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if ($user && password_verify($this->UserPassword, $user['UserPassword'])) {
            return $user;
        }
        return false;
    }

    /* ==========================================================
       🔹 AMBIL USER BERDASARKAN EMAIL
    ========================================================== */
    public function getUserByEmail($email) {
        $query = "SELECT * FROM " . $this->table . " WHERE UserEmail = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        if (!$stmt) {
            error_log("Prepare getUserByEmail failed: " . $this->conn->error);
            return false;
        }
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();
        return $user;
    }

    /* ==========================================================
       🔹 SIMPAN TOKEN RESET PASSWORD
    ========================================================== */
    public function saveResetToken($email, $token, $expires) {
        // Hapus token lama jika ada
        $deleteQuery = "DELETE FROM password_resets WHERE Reset_Email = ?";
        $delStmt = $this->conn->prepare($deleteQuery);
        if ($delStmt) {
            $delStmt->bind_param("s", $email);
            $delStmt->execute();
            $delStmt->close();
        }

        // Simpan token baru
        $query = "INSERT INTO password_resets (Reset_Email, ResetToken, ResetExpires, created_) 
                  VALUES (?, ?, ?, NOW())";
        $stmt = $this->conn->prepare($query);
        if (!$stmt) {
            error_log("Prepare saveResetToken failed: " . $this->conn->error);
            return false;
        }
        $stmt->bind_param("sss", $email, $token, $expires);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    /* ==========================================================
       🔹 VERIFIKASI TOKEN RESET
    ========================================================== */
    public function verifyResetToken($token) {
        $query = "SELECT * FROM password_resets WHERE ResetToken = ? AND ResetExpires > NOW() LIMIT 1";
        $stmt = $this->conn->prepare($query);
        if (!$stmt) {
            error_log("Prepare verifyResetToken failed: " . $this->conn->error);
            return false;
        }
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();
        $stmt->close();
        return $data;
    }

    /* ==========================================================
       🔹 HAPUS TOKEN SETELAH RESET SELESAI
    ========================================================== */
    public function deleteResetToken($email) {
        $query = "DELETE FROM password_resets WHERE Reset_Email = ?";
        $stmt = $this->conn->prepare($query);
        if (!$stmt) return false;
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->close();
        return true;
    }

    /* ==========================================================
       🔹 AMBIL SEMUA USER
    ========================================================== */
    public function getAllUsers() {
    $query = "SELECT IDUser, UserNama, UserEmail, UserNoHP, UserAlamat, UserRole, CreatedAt 
              FROM " . $this->table . " 
              ORDER BY CreatedAt DESC";

    $result = $this->conn->query($query);

    if ($result && $result->num_rows > 0) {
        return $result->fetch_all(MYSQLI_ASSOC); 

    return []; 
}
}

    /* ==========================================================
       🔹 UPDATE PROFIL USER
    ========================================================== */
    public function updateProfile() {
        $query = "UPDATE " . $this->table . " 
                 SET UserNama=?, UserEmail=?, UserRole=?, UserNoHP=?, UserAlamat=?, UpdatedAt=? 
                 WHERE IDUser=?";
        $stmt = $this->conn->prepare($query);
        if (!$stmt) return false;

        $this->UpdatedAt = date('Y-m-d H:i:s');
        $stmt->bind_param(
            "ssssssi",
            $this->UserNama,
            $this->UserEmail,
            $this->UserRole,
            $this->UserNoHP,
            $this->UserAlamat,
            $this->UpdatedAt,
            $this->IDUser
        );
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    /* ==========================================================
       🔹 HAPUS USER
    ========================================================== */
    public function deleteUser($id) {
        $query = "DELETE FROM " . $this->table . " WHERE IDUser = ?";
        $stmt = $this->conn->prepare($query);
        if (!$stmt) return false;
        $stmt->bind_param("i", $id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    /* ==========================================================
       🔹 AMBIL SEMUA KARYAWAN
    ========================================================== */
        public function getKaryawan() {
            $query = "SELECT IDUser, UserNama, UserEmail, UserNoHP, UserAlamat, UserRole 
                    FROM users 
                    WHERE UserRole IN ('Karyawan')
                    ORDER BY UserNama ASC";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $result = $stmt->get_result();
            return $result->fetch_all(MYSQLI_ASSOC);
        }
}
?>
