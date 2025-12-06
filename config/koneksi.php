<?php
// File: config/koneksi.php
class Database {
    private $host = "localhost";
    private $db_name = "artefaxdummy";
    private $username = "root";
    private $password = "";
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new mysqli($this->host, $this->username, $this->password, $this->db_name);
            if ($this->conn->connect_error) {
                throw new Exception("Connection failed: " . $this->conn->connect_error);
            }
            $this->conn->set_charset("utf8mb4");
        } catch (Exception $e) {
            error_log("Database Error: " . $e->getMessage());
            return null;
        }
        return $this->conn;
    }
}
?>