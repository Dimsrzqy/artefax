<?php
class PaketJasa {
    private $conn;
    private $table = "paketjasa";

    // Kolom Paket Jasa
    public $IDPaket;
    public $PaketNama;
    public $PaketKategori;
    public $PaketDeskripsi;
    public $PaketHarga;
    public $PaketDurasi;
    public $PaketStatus;
    public $CreatedAt;
    public $UpdatedAt;

    public function __construct($db) {
        $this->conn = $db;
    }

    // =============================
    // CREATE DATA
    // =============================
    public function create() {
        $query = "INSERT INTO " . $this->table . " 
                  (PaketNama, PaketKategori, PaketDeskripsi, PaketHarga, PaketDurasi, PaketStatus, CreatedAt)
                  VALUES (?, ?, ?, ?, ?, ?, NOW())";

        $stmt = $this->conn->prepare($query);

        if (!$stmt) {
            return false;
        }

        $stmt->bind_param(
            "sssiss", 
            $this->PaketNama,
            $this->PaketKategori,
            $this->PaketDeskripsi,
            $this->PaketHarga,
            $this->PaketDurasi,
            $this->PaketStatus
        );

        return $stmt->execute();
    }

    // =============================
    // READ (TAMPIL SEMUA DATA)
    // =============================
    public function readAll() {
        $query = "SELECT IDPaket, PaketNama, PaketDirGbr, PaketKategori, PaketDeskripsi, PaketHarga, PaketDurasi, PaketStatus 
                  FROM " . $this->table . " 
                  ORDER BY CreatedAt DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    // =============================
    // READ BY ID
    // =============================
    public function readOne() {
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
    public function update() {
        $query = "UPDATE " . $this->table . " 
                  SET PaketNama = ?, PaketKategori = ?, PaketDeskripsi = ?, 
                      PaketHarga = ?, PaketDurasi = ?, PaketStatus = ?, UpdatedAt = NOW()
                  WHERE IDPaket = ?";

        $stmt = $this->conn->prepare($query);
        if (!$stmt) return false;

        $stmt->bind_param(
            "sssissi",
            $this->PaketNama,
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
    public function delete() {
        $query = "DELETE FROM " . $this->table . " WHERE IDPaket = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $this->IDPaket);
        return $stmt->execute();
    }

    // =============================
    // SEARCH DATA
    // =============================
    public function search($keyword) {
        $keyword = "%" . $this->conn->real_escape_string($keyword) . "%";
        $query = "SELECT * FROM " . $this->table . " 
                  WHERE PaketNama LIKE ? OR PaketKategori LIKE ? OR PaketDeskripsi LIKE ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("sss", $keyword, $keyword, $keyword);
        $stmt->execute();
        return $stmt->get_result();
    }
}?>