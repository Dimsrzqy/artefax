<?php
class Alat
{
    private $conn;
    private $table = "alat";

    public $IDAlat;         
    public $AlatNama;       
    public $AlatDirGbr;    
    public $AlatKategori;   
    public $AlatDeskripsi;
    public $AlatHarga;     
    public $AlatStok;       
    public $AlatStatus;     
    public $CreatedAt;      
    public $UpdatedAt;      

    public function __construct($db)
    {
        $this->conn = $db;
    }

    // =============================
    // CREATE DATA
    // =============================
    public function create()
    {
        $query = "INSERT INTO " . $this->table . " 
                  (AlatNama, AlatDirGbr, AlatKategori, AlatDeskripsi, AlatHarga, AlatStok, AlatStatus, CreatedAt)
                  VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";

        $stmt = $this->conn->prepare($query);

        if (!$stmt) {
            return false;
        }

       
        $stmt->bind_param(
            "ssssdis", 
            $this->AlatNama,
            $this->AlatDirGbr,
            $this->AlatKategori,
            $this->AlatDeskripsi,
            $this->AlatHarga, 
            $this->AlatStok, 
            $this->AlatStatus
        );

        return $stmt->execute();
    }
    
    // =============================
    // TOTAL ALAT
    // =============================
    public function TotalAlat()
    {
        $query = "SELECT COUNT(*) AS total FROM {$this->table}";
        $result = $this->conn->query($query);
        if ($result && $row = $result->fetch_assoc()) {
            return (int)$row['total'];
        }
        return 0;
    }
    // =============================
    // Read Keseluruhan
    // =============================
    public function readAll($limit = null, $offset = null)
    {
        $query = "SELECT IDAlat, AlatNama, AlatDirGbr, AlatKategori, AlatDeskripsi, AlatHarga, AlatStok, AlatStatus 
                  FROM " . $this->table . " 
                  ORDER BY CreatedAt ASC";
        
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
     // =============================
    // Read Satu Data
    // =============================
    public function readOne()
    {
        $query = "SELECT * FROM " . $this->table . " WHERE IDAlat = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $this->IDAlat);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            $this->IDAlat = $row['IDAlat'];
            $this->AlatNama = $row['AlatNama'];
            $this->AlatDirGbr = $row['AlatDirGbr'];
            $this->AlatKategori = $row['AlatKategori'];
            $this->AlatDeskripsi = $row['AlatDeskripsi'];
            $this->AlatHarga = $row['AlatHarga'];
            $this->AlatStok = $row['AlatStok'];
            $this->AlatStatus = $row['AlatStatus'];
            $this->CreatedAt = $row['CreatedAt'];
            $this->UpdatedAt = $row['UpdatedAt'];
            return true;
        }
        return false;
    }
    // =============================
    // UPDATE DATA
    // =============================
    public function update()
    {
        $query = "UPDATE " . $this->table . " 
                  SET AlatNama = ?, AlatDirGbr = ?, AlatKategori = ?, AlatDeskripsi = ?, 
                      AlatHarga = ?, AlatStok = ?, AlatStatus = ?, UpdatedAt = NOW()
                  WHERE IDAlat = ?";

        $stmt = $this->conn->prepare($query);
        if (!$stmt) return false;

        $stmt->bind_param(
            "ssssdisi",
            $this->AlatNama,
            $this->AlatDirGbr,
            $this->AlatKategori,
            $this->AlatDeskripsi,
            $this->AlatHarga, 
            $this->AlatStok, 
            $this->AlatStatus,
            $this->IDAlat 
        );

        return $stmt->execute();
    }
    // =============================
    // DELETE DATA
    // =============================
    public function delete()
    {
        $query = "DELETE FROM " . $this->table . " WHERE IDAlat = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $this->IDAlat); 
        return $stmt->execute();
    }

    // =============================
    // Search DATA
    // =============================
    public function search($keyword)
    {

        $search_term = "%" . $keyword . "%"; 
        
        $query = "SELECT * FROM " . $this->table . " 
                  WHERE AlatNama LIKE ? OR AlatKategori LIKE ? OR AlatDeskripsi LIKE ?";
        
        $stmt = $this->conn->prepare($query);

        $stmt->bind_param("sss", $search_term, $search_term, $search_term); 
        $stmt->execute();
        
        return $stmt->get_result();
    }
}