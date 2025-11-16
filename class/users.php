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
       REGISTER USER BARU
    ========================================================== */
    public function register($forceRole = null) {
        // Cek email sudah ada
        $checkQuery = "SELECT UserEmail FROM {$this->table} WHERE UserEmail = ? LIMIT 1";
        $checkStmt = $this->conn->prepare($checkQuery);
        if (!$checkStmt) return false;

        $email = filter_var(trim($this->UserEmail), FILTER_SANITIZE_EMAIL);
        $checkStmt->bind_param("s", $email);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        if ($checkResult->num_rows > 0) {
            $checkStmt->close();
            return false;
        }
        $checkStmt->close();

        // INSERT
        $query = "INSERT INTO {$this->table} 
                  (UserNama, UserEmail, UserPassword, UserRole, UserNoHP, UserAlamat, CreatedAt, UpdatedAt)
                  VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())";
        $stmt = $this->conn->prepare($query);
        if (!$stmt) return false;

        $this->UserNama     = htmlspecialchars(trim($this->UserNama));
        $this->UserEmail    = $email;
        $this->UserPassword = password_hash($this->UserPassword, PASSWORD_DEFAULT);
        $this->UserNoHP     = preg_replace('/[^0-9+]/', '', $this->UserNoHP); // hanya angka & +
        $this->UserAlamat   = htmlspecialchars(trim($this->UserAlamat));

        // Role: Admin, Karyawan, Customer
        $allowed = ['Admin', 'Karyawan', 'Customer'];
        $this->UserRole = ($forceRole && in_array($forceRole, $allowed)) ? $forceRole : 'Customer';

        $stmt->bind_param(
            "ssssss",
            $this->UserNama,
            $this->UserEmail,
            $this->UserPassword,
            $this->UserRole,
            $this->UserNoHP,
            $this->UserAlamat
        );

        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    /* ==========================================================
       LOGIN USER
    ========================================================== */
    public function login() {
        $query = "SELECT * FROM {$this->table} WHERE UserEmail = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        if (!$stmt) return false;

        $email = filter_var($this->UserEmail, FILTER_SANITIZE_EMAIL);
        $stmt->bind_param("s", $email);
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
       AMBIL USER BY EMAIL
    ========================================================== */
    public function getUserByEmail($email) {
        $query = "SELECT * FROM {$this->table} WHERE UserEmail = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        if (!$stmt) return false;

        $email = filter_var($email, FILTER_SANITIZE_EMAIL);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();
        return $user;
    }

    /* ==========================================================
       SAVE RESET TOKEN
    ========================================================== */
    public function saveResetToken($email, $token, $expires) {
        $this->deleteResetToken($email); // hapus dulu

        $query = "INSERT INTO password_resets (Reset_Email, ResetToken, ResetExpires, created_) 
                  VALUES (?, ?, ?, NOW())";
        $stmt = $this->conn->prepare($query);
        if (!$stmt) return false;

        $stmt->bind_param("sss", $email, $token, $expires);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    /* ==========================================================
       VERIFY RESET TOKEN
    ========================================================== */
    public function verifyResetToken($token) {
        $query = "SELECT * FROM password_resets WHERE ResetToken = ? AND ResetExpires > NOW() LIMIT 1";
        $stmt = $this->conn->prepare($query);
        if (!$stmt) return false;

        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();
        $stmt->close();
        return $data;
    }

    /* ==========================================================
       DELETE RESET TOKEN
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
       AMBIL SEMUA USER (DIPERBAIKI!)
    ========================================================== */
    public function getAllUsers() {
        $query = "SELECT IDUser, UserNama, UserEmail, UserNoHP, UserAlamat, UserRole, CreatedAt 
                  FROM {$this->table} 
                  ORDER BY CreatedAt DESC";

        $result = $this->conn->query($query);

        if ($result && $result->num_rows > 0) {
            return $result->fetch_all(MYSQLI_ASSOC);
        }
        return []; // PERBAIKAN: return di akhir!
    }

    /* ==========================================================
       UPDATE PROFIL
    ========================================================== */
    public function updateProfile() {
        $query = "UPDATE {$this->table} 
                  SET UserNama=?, UserEmail=?, UserRole=?, UserNoHP=?, UserAlamat=?, UpdatedAt=NOW() 
                  WHERE IDUser=?";
        $stmt = $this->conn->prepare($query);
        if (!$stmt) return false;

        $this->UserNama   = htmlspecialchars(trim($this->UserNama));
        $this->UserEmail  = filter_var(trim($this->UserEmail), FILTER_SANITIZE_EMAIL);
        $this->UserNoHP   = preg_replace('/[^0-9+]/', '', $this->UserNoHP);
        $this->UserAlamat = htmlspecialchars(trim($this->UserAlamat));

        $allowed = ['Admin', 'Karyawan', 'Customer'];
        $this->UserRole = in_array($this->UserRole, $allowed) ? $this->UserRole : 'Customer';

        $stmt->bind_param(
            "sssssi",
            $this->UserNama,
            $this->UserEmail,
            $this->UserRole,
            $this->UserNoHP,
            $this->UserAlamat,
            $this->IDUser
        );

        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    /* ==========================================================
       HAPUS USER
    ========================================================== */
    public function deleteUser($id) {
        $query = "DELETE FROM {$this->table} WHERE IDUser = ?";
        $stmt = $this->conn->prepare($query);
        if (!$stmt) return false;

        $stmt->bind_param("i", $id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    /* ==========================================================
       AMBIL SEMUA KARYAWAN
    ========================================================== */
    public function getKaryawan() {
        $query = "SELECT IDUser, UserNama, UserEmail, UserNoHP, UserAlamat, UserRole 
                  FROM {$this->table} 
                  WHERE UserRole = 'Karyawan'
                  ORDER BY UserNama ASC";
        $stmt = $this->conn->prepare($query);
        if (!$stmt) return [];

        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $data;
    }

    /* ==========================================================
       UBAH PASSWORD
    ========================================================== */
    public function changePassword($newPassword) {
        $query = "UPDATE {$this->table} SET UserPassword = ?, UpdatedAt = NOW() WHERE IDUser = ?";
        $stmt = $this->conn->prepare($query);
        if (!$stmt) return false;

        $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt->bind_param("si", $hashed, $this->IDUser);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
}
?>