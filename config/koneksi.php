<?php
class Database {
    private $host = "localhost";
    private $db_name = "artefax";
    private $username = "root";
    private $password = "";
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new mysqli($this->host, $this->username, $this->password, $this->db_name);

            // Cek koneksi
            if ($this->conn->connect_error) {
                throw new Exception("Koneksi gagal: " . $this->conn->connect_error);
            }

            // Set charset ke UTF-8
            if (!$this->conn->set_charset("utf8mb4")) {
                throw new Exception("Error setting charset: " . $this->conn->error);
            }

            return $this->conn;
        } catch (Exception $e) {
            // Log error untuk debugging (misalnya ke file log)
            error_log("Database Connection Error: " . $e->getMessage());
            // Opsional: Kembalikan null atau lempar ulang exception tergantung kebutuhan
            return null;
        }
    }
}
?>