<?php
class PaketJasa
{
    private $conn;
    private $table = "paketjasa";

    // Kolom Paket Jasa
    public $IDPaket;
    public $PaketNama;
    public $PaketDirGbr;
    public $PaketKategori;
    public $PaketDeskripsi;
    public $PaketHarga;
    public $PaketDurasi;
    public $PaketStatus;
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
                  (PaketNama, PaketDirGbr, PaketKategori, PaketDeskripsi, PaketHarga, PaketDurasi, PaketStatus, CreatedAt)
                  VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";

        $stmt = $this->conn->prepare($query);

        if (!$stmt) {
            return false;
        }

        $stmt->bind_param(
            "ssssiss",
            $this->PaketNama,
            $this->PaketDirGbr,
            $this->PaketKategori,
            $this->PaketDeskripsi,
            $this->PaketHarga,
            $this->PaketDurasi,
            $this->PaketStatus
        );

        return $stmt->execute();
    }
    // =============================
    // Total Layanan 
    // =============================
    public function TotalLayanan()
    {
        $query = "SELECT COUNT(*) AS total FROM {$this->table}";
        $result = $this->conn->query($query);
        if ($result && $row = $result->fetch_assoc()) {
            return (int)$row['total'];
        }
        return 0;
    }
    // =============================
    // READ (TAMPIL SEMUA DATA)
    // =============================
    public function readAll($limit = null, $offset = null)
    {
        $query = "SELECT IDPaket, PaketNama, PaketDirGbr, PaketKategori, PaketDeskripsi, PaketHarga, PaketDurasi, PaketStatus 
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
    // READ BY ID
    // =============================
    public function readOne()
    {
        $query = "SELECT * FROM " . $this->table . " WHERE IDPaket = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $this->IDPaket);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            $this->IDPaket = $row['IDPaket'];
            $this->PaketNama = $row['PaketNama'];
            $this->PaketKategori = $row['PaketKategori'];
            $this->PaketDeskripsi = $row['PaketDeskripsi'];
            $this->PaketHarga = $row['PaketHarga'];
            $this->PaketDurasi = $row['PaketDurasi'];
            $this->PaketStatus = $row['PaketStatus'];
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
                  SET PaketNama = ?, PaketDirGbr = ?, PaketKategori = ?, PaketDeskripsi = ?, 
                      PaketHarga = ?, PaketDurasi = ?, PaketStatus = ?, UpdatedAt = NOW()
                  WHERE IDPaket = ?";

        $stmt = $this->conn->prepare($query);
        if (!$stmt) return false;

        $stmt->bind_param(
            "ssssissi",
            $this->PaketNama,
            $this->PaketDirGbr,
            $this->PaketKategori,
            $this->PaketDeskripsi,
            $this->PaketHarga,
            $this->PaketDurasi,
            $this->PaketStatus,
            $this->IDPaket
        );

        return $stmt->execute();
    }

    // =============================
    // DELETE DATA
    // =============================
    public function delete()
    {
        $query = "DELETE FROM " . $this->table . " WHERE IDPaket = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $this->IDPaket);
        return $stmt->execute();
    }

    // =============================
    // SEARCH DATA
    // =============================
    public function search($keyword)
    {
        $keyword = "%" . $this->conn->real_escape_string($keyword) . "%";
        $query = "SELECT * FROM " . $this->table . " 
                  WHERE PaketNama LIKE ? OR PaketKategori LIKE ? OR PaketDeskripsi LIKE ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("sss", $keyword, $keyword, $keyword);
        $stmt->execute();
        return $stmt->get_result();
    }
}
