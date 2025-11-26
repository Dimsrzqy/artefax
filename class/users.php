<?php
class User
{
    private $conn;
    private $table = "users";

    // Kolom utama (public biar bisa di-set dari luar)
    public $IDUser;
    public $UserNama;
    public $UserEmail;
    public $UserPassword;
    public $UserRole;
    public $UserNoHP;
    public $UserAlamat;
    public $CreatedAt;
    public $UpdatedAt;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    /* ==========================================================
       REGISTER USER BARU
    ========================================================== */
    public function register($forceRole = null)
    {
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
            return false; // email sudah terdaftar
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
        $this->UserNoHP     = preg_replace('/[^0-9+]/', '', $this->UserNoHP);
        $this->UserAlamat   = htmlspecialchars(trim($this->UserAlamat));

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
    public function login()
    {
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
    public function getUserByEmail($email)
    {
        $query = "SELECT * FROM {$this->table} WHERE UserEmail = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        if (!$stmt) return false;

        $email = filter_var($email, FILTER_SANITIZE_EMAIL);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();
        return $user ?: false;
    }

    /* ==========================================================
       AMBIL USER BY ID
    ========================================================== */
    public function getUserByID($id)
    {
        $query = "SELECT IDUser, UserNama, UserEmail, UserNoHP, UserAlamat, UserRole, CreatedAt, UpdatedAt 
                  FROM {$this->table} WHERE IDUser = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        if (!$stmt) return false;

        $id = (int)$id;
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();
        return $user ?: false;
    }

    /* ==========================================================
       TOTAL JUMLAH KARYAWAN (UNTUK PAGINATION)
    ========================================================== */
    public function getTotalKaryawan()
    {
        $query = "SELECT COUNT(*) AS total FROM {$this->table} WHERE UserRole = 'Karyawan'";
        $result = $this->conn->query($query);
        if ($result && $row = $result->fetch_assoc()) {
            return (int)$row['total'];
        }
        return 0;
    }

    /* ==========================================================
       AMBIL KARYAWAN + SUPPORT PAGINATION (INI YANG BARU!)
    ========================================================== */
    public function getKaryawan($limit = null, $offset = null)
    {
        $query = "SELECT IDUser, UserNama, UserEmail, UserNoHP, UserAlamat, UserRole 
                  FROM {$this->table} 
                  WHERE UserRole = 'Karyawan'
                  ORDER BY UserNama ASC";

        if ($limit !== null && $offset !== null) {
            $query .= " LIMIT ? OFFSET ?";
        }

        $stmt = $this->conn->prepare($query);
        if (!$stmt) return [];

        if ($limit !== null && $offset !== null) {
            $stmt->bind_param("ii", $limit, $offset);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $data;
    }

    /* ==========================================================
       AMBIL SEMUA USER (untuk admin)
    ========================================================== */
    public function getAllUsers()
    {
        $query = "SELECT IDUser, UserNama, UserEmail, UserNoHP, UserAlamat, UserRole, CreatedAt 
                  FROM {$this->table} 
                  ORDER BY CreatedAt DESC";

        $result = $this->conn->query($query);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    /* ==========================================================
       UPDATE PROFIL
    ========================================================== */
    public function updateProfile()
    {
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
    public function deleteUser($id)
    {
        $query = "DELETE FROM {$this->table} WHERE IDUser = ?";
        $stmt = $this->conn->prepare($query);
        if (!$stmt) return false;

        $id = (int)$id;
        $stmt->bind_param("i", $id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    /* ==========================================================
       UBAH PASSWORD
    ========================================================== */
    public function changePassword($newPassword)
    {
        $query = "UPDATE {$this->table} SET UserPassword = ?, UpdatedAt = NOW() WHERE IDUser = ?";
        $stmt = $this->conn->prepare($query);
        if (!$stmt) return false;

        $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt->bind_param("si", $hashed, $this->IDUser);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    // Reset password token (tetap sama, sudah bagus)
    public function saveResetToken($email, $token, $expires)
    { /* ... tetap sama ... */
    }
    public function verifyResetToken($token)
    { /* ... tetap sama ... */
    }
    public function deleteResetToken($email)
    { /* ... tetap sama ... */
    }
}
