<?php
class Pembayaran {
    private $conn;
    private $table = "pembayaran";

    // tabel pembayaran
    public $IDPembayaran;
    public $IDBooking;
    public $PbrMetode;
    public $PbrJumlah;
    public $PbrStatus;
    public $PbrConfirmed;
    public $PbrBukti;
    public $CreatedAt;
    public $UpdatedAt;

    // tabel booking
    public $IDUser;
    public $BkgJenis;
    public $IDPaket;
    public $IDAlat;
    public $BkgAlamat;
    public $BkgTglMulai;
    public $BkgTglSelesai;
    public $BkgTotalHarga;
    public $BkgStatus;

    public $UserNama;
  
    public function __construct($db) {
        $this->conn = $db;
    }

    // =============================
    // READ DATA
    // =============================
    public function readJoin() {
        $query = "
            SELECT 
                p.IDPembayaran, p.IDBooking, p.PbrMetode, p.PbrJumlah, 
                p.PbrStatus, p.PbrConfirmed, p.CreatedAt,
                b.IDUser, b.BkgJenis, b.IDPaket, b.BkgTotalHarga
            FROM " . $this->table . " p
            JOIN booking b ON p.IDBooking = b.IDBooking
            JOIN users u ON b.IDUser = u.IDUser
            ORDER BY p.CreatedAt DESC
        ";

        $result = $this->conn->query($query); 
        
        if ($result && $result->num_rows > 0) {
            return $result->fetch_all(MYSQLI_ASSOC);
        }
        return [];
    }
    // =============================
    // READ Status Pending
    // =============================
    public function readPending() {
        // Query dimodifikasi: menambahkan klausa WHERE dan mengurutkan berdasarkan CreatedAt DESC
        $query = "
            SELECT 
                p.IDPembayaran, p.IDBooking, p.PbrMetode, p.PbrJumlah, 
                p.PbrStatus, p.PbrConfirmed, p.CreatedAt,
                b.IDUser, b.BkgJenis, b.IDPaket, b.BkgTotalHarga,
                u.UserNama 
            FROM " . $this->table . " p
            JOIN booking b ON p.IDBooking = b.IDBooking
            JOIN users u ON b.IDUser = u.IDUser
            WHERE p.PbrStatus = 'Pending' 
            ORDER BY p.CreatedAt DESC
        ";

        
        $result = $this->conn->query($query);
        
        if ($result && $result->num_rows > 0) {
            return $result->fetch_all(MYSQLI_ASSOC);
        }
        
        return []; 
    }

    // =============================
    // READ DETAIL DATA
    // =============================
    public function readJoinFull() {
        $query = "
            SELECT 
                p.*, 
                b.IDUser, b.BkgJenis, b.IDPaket, b.IDAlat, b.BkgAlamat,
                b.BkgTglMulai, b.BkgTglSelesai, b.BkgTotalHarga, b.BkgStatus
            FROM " . $this->table . " p
            JOIN booking b ON p.IDBooking = b.IDBooking
            ORDER BY p.CreatedAt DESC
        ";

         $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    // =============================
    // CREATE DATA
    // =============================
    public function create() {
        $query = "
            INSERT INTO " . $this->table . " 
                (IDBooking, PbrMetode, PbrJumlah, PbrStatus, PbrConfirmed, PbrBukti, CreatedAt, UpdatedAt)
            VALUES 
                (:IDBooking, :PbrMetode, :PbrJumlah, 'Pending', 0, :PbrBukti, NOW(), NOW())
        ";

        $stmt = $this->conn->prepare($query);

        // Sanitasi
        $this->IDBooking = htmlspecialchars(strip_tags($this->IDBooking));
        $this->PbrMetode = htmlspecialchars(strip_tags($this->PbrMetode));
        $this->PbrJumlah = htmlspecialchars(strip_tags($this->PbrJumlah));
        $this->PbrBukti  = htmlspecialchars(strip_tags($this->PbrBukti));

        // Bind
        $stmt->bindParam(':IDBooking', $this->IDBooking);
        $stmt->bindParam(':PbrMetode', $this->PbrMetode);
        $stmt->bindParam(':PbrJumlah', $this->PbrJumlah);
        $stmt->bindParam(':PbrBukti', $this->PbrBukti);

        return $stmt->execute();
    }

    // =============================
    // UPDATE DATA
    // =============================
    public function updateStatus() {
        $query = "
            UPDATE " . $this->table . " 
            SET 
                PbrStatus = :PbrStatus,
                PbrConfirmed = :PbrConfirmed,
                UpdatedAt = NOW()
            WHERE IDPembayaran = :IDPembayaran
        ";

        $stmt = $this->conn->prepare($query);

        // Sanitasi
        $this->IDPembayaran = htmlspecialchars(strip_tags($this->IDPembayaran));
        $this->PbrStatus = htmlspecialchars(strip_tags($this->PbrStatus));
        $this->PbrConfirmed = htmlspecialchars(strip_tags($this->PbrConfirmed));

        // Bind
        $stmt->bindParam(':IDPembayaran', $this->IDPembayaran);
        $stmt->bindParam(':PbrStatus', $this->PbrStatus);
        $stmt->bindParam(':PbrConfirmed', $this->PbrConfirmed);

        return $stmt->execute();
    }

    // =============================
    // DELETE DATA
    // =============================
    public function delete() {
        $query = "DELETE FROM " . $this->table . " WHERE IDPembayaran = :IDPembayaran";
        $stmt = $this->conn->prepare($query);

        $this->IDPembayaran = htmlspecialchars(strip_tags($this->IDPembayaran));
        $stmt->bindParam(':IDPembayaran', $this->IDPembayaran);

        return $stmt->execute();
    }

    // =============================
    // SEARCH ID DATA
    // =============================
    public function getSingle() {
        $query = "SELECT * FROM " . $this->table . " WHERE IDPembayaran = :IDPembayaran LIMIT 1";
        $stmt = $this->conn->prepare($query);

        $this->IDPembayaran = htmlspecialchars(strip_tags($this->IDPembayaran));
        $stmt->bindParam(':IDPembayaran', $this->IDPembayaran);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $this->IDBooking = $row['IDBooking'];
            $this->PbrMetode = $row['PbrMetode'];
            $this->PbrJumlah = $row['PbrJumlah'];
            $this->PbrStatus = $row['PbrStatus'];
            $this->PbrConfirmed = $row['PbrConfirmed'];
            $this->PbrBukti = $row['PbrBukti'];
            $this->CreatedAt = $row['CreatedAt'];
            $this->UpdatedAt = $row['UpdatedAt'];
            return true;
        }
        return false;
    }
}
?>