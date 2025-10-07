<?php
class User {
    private $conn;
    private $table = "users";

    public $IDUser;
    public $NamaUser;
    public $Email;
    public $Password;
    public $Role;
    public $NoHP;
    public $Alamat;
    public $CreatedAt;
    public $UpdatedAt;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Register
    public function register() {
        // Check if email already exists
        $checkQuery = "SELECT Email FROM " . $this->table . " WHERE Email = ? LIMIT 1";
        $checkStmt = $this->conn->prepare($checkQuery);
        if (!$checkStmt) {
            error_log("Prepare check failed: " . $this->conn->error);
            return false;
        }
        $this->Email = htmlspecialchars(trim(strip_tags($this->Email)));
        $checkStmt->bind_param("s", $this->Email);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        if ($checkResult->num_rows > 0) {
            error_log("Duplicate email attempted: " . $this->Email);
            return false;
        }

        $query = "INSERT INTO " . $this->table . 
                 " (NamaUser, Email, Password, Role, NoHP, Alamat, CreatedAt, UpdatedAt) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($query);

        if (!$stmt) {
            error_log("Prepare failed: " . $this->conn->error);
            return false;
        }

        // Sanitasi input
        $this->NamaUser = htmlspecialchars(trim(strip_tags($this->NamaUser)));
        $this->Email = htmlspecialchars(trim(strip_tags($this->Email)));
        $this->Role = htmlspecialchars(trim(strip_tags($this->Role)));
        $this->NoHP = htmlspecialchars(trim(strip_tags($this->NoHP)));
        $this->Alamat = htmlspecialchars(trim(strip_tags($this->Alamat)));
        $this->CreatedAt = date('Y-m-d H:i:s');
        $this->UpdatedAt = date('Y-m-d H:i:s');

        // Debug input values
        error_log("Register input: NamaUser=" . $this->NamaUser . ", Email=" . $this->Email . 
                  ", Role=" . $this->Role . ", NoHP=" . $this->NoHP . ", Alamat=" . $this->Alamat);

        // Validasi role sesuai ENUM
        $validRoles = ['Admin', 'Karyawan', 'Customer'];
        $this->Role = ucfirst(strtolower(trim($this->Role)));
        if (!in_array($this->Role, $validRoles)) {
            $this->Role = 'Customer'; // Default to 'Customer' if invalid
        }
        error_log("Validated Role: " . $this->Role);

        $hashed = password_hash($this->Password, PASSWORD_DEFAULT);
        $stmt->bind_param(
            "ssssssss",
            $this->NamaUser,
            $this->Email,
            $hashed,
            $this->Role,
            $this->NoHP,
            $this->Alamat,
            $this->CreatedAt,
            $this->UpdatedAt
        );

        $result = $stmt->execute();
        if (!$result) {
            error_log("Execute failed: " . $stmt->error);
        }
        return $result;
    }

    // Login
    public function login() {
        $query = "SELECT * FROM " . $this->table . " WHERE Email = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);

        if (!$stmt) {
            return false;
        }

        $this->Email = htmlspecialchars(trim(strip_tags($this->Email)));
        $stmt->bind_param("s", $this->Email);
        $stmt->execute();

        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user && password_verify($this->Password, $user['Password'])) {
            $this->IDUser = $user['IDUser'];
            $this->NamaUser = $user['NamaUser'];
            $this->Role = $user['Role'];
            return $user;
        }
        return false;
    }

    // Ambil semua user
    public function getAllUsers() {
        $query = "SELECT * FROM " . $this->table . " ORDER BY CreatedAt DESC";
        $result = $this->conn->query($query);

        if (!$result) {
            return false;
        }
        return $result;
    }

    // Update user
    public function updateProfile() {
        $query = "UPDATE " . $this->table . 
                 " SET NamaUser=?, Email=?, Role=?, NoHP=?, Alamat=?, UpdatedAt=? 
                  WHERE IDUser=?";
        $stmt = $this->conn->prepare($query);

        if (!$stmt) {
            return false;
        }

        // Sanitasi input
        $this->NamaUser = htmlspecialchars(trim(strip_tags($this->NamaUser)));
        $this->Email = htmlspecialchars(trim(strip_tags($this->Email)));
        $this->Role = htmlspecialchars(trim(strip_tags($this->Role)));
        $this->NoHP = htmlspecialchars(trim(strip_tags($this->NoHP)));
        $this->Alamat = htmlspecialchars(trim(strip_tags($this->Alamat)));
        $this->UpdatedAt = date('Y-m-d H:i:s');

        // Validasi role sesuai ENUM
        $validRoles = ['Admin', 'Karyawan', 'Customer'];
        $this->Role = ucfirst(strtolower(trim($this->Role)));
        if (!in_array($this->Role, $validRoles)) {
            return false;
        }

        $stmt->bind_param(
            "ssssssi",
            $this->NamaUser,
            $this->Email,
            $this->Role,
            $this->NoHP,
            $this->Alamat,
            $this->UpdatedAt,
            $this->IDUser
        );

        return $stmt->execute();
    }

    // Hapus user
    public function deleteUser($id) {
        // Cek apakah user ada
        $query = "SELECT IDUser FROM " . $this->table . " WHERE IDUser = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            return false;
        }

        $query = "DELETE FROM " . $this->table . " WHERE IDUser=?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }
}
?>