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
                  VALUES
                  (:PaketNama, :PaketKategori, :PaketDeskripsi, :PaketHarga, :PaketDurasi, :PaketStatus, NOW())";

        $stmt = $this->conn->prepare($query);

        // masih auto increment *) $stmt->bindParam(':IDPaket', $this->IDPaket);
        $stmt->bindParam(':PaketNama', $this->PaketNama);
        $stmt->bindParam(':PaketKategori', $this->PaketKategori);
        $stmt->bindParam(':PaketDeskripsi', $this->PaketDeskripsi);
        $stmt->bindParam(':PaketHarga', $this->PaketHarga);
        $stmt->bindParam(':PaketDurasi', $this->PaketDurasi);
        $stmt->bindParam(':PaketStatus', $this->PaketStatus);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    // =============================
    // READ (TAMPIL SEMUA DATA)
    // =============================
    public function readAll() {
        $query = "SELECT * FROM " . $this->table . " ORDER BY CreatedAt DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt;
    }

    // =============================
    // READ BY ID
    // =============================
    public function readOne() {
        $query = "SELECT * FROM " . $this->table . " WHERE IDPaket = :IDPaket LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':IDPaket', $this->IDPaket);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
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
                  SET PaketNama = :PaketNama,
                      PaketKategori = :PaketKategori,
                      PaketDeskripsi = :PaketDeskripsi,
                      PaketHarga = :PaketHarga,
                      PaketDurasi = :PaketDurasi,
                      PaketStatus = :PaketStatus,
                      UpdatedAt = NOW()
                  WHERE IDPaket = :IDPaket";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':PaketNama', $this->PaketNama);
        $stmt->bindParam(':PaketKategori', $this->PaketKategori);
        $stmt->bindParam(':PaketDeskripsi', $this->PaketDeskripsi);
        $stmt->bindParam(':PaketHarga', $this->PaketHarga);
        $stmt->bindParam(':PaketDurasi', $this->PaketDurasi);
        $stmt->bindParam(':PaketStatus', $this->PaketStatus);
        $stmt->bindParam(':IDPaket', $this->IDPaket);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    // =============================
    // DELETE DATA
    // =============================
    public function delete() {
        $query = "DELETE FROM " . $this->table . " WHERE IDPaket = :IDPaket";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':IDPaket', $this->IDPaket);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    // =============================
    // SEARCH DATA
    // =============================
    public function search($keyword) {
        $query = "SELECT * FROM " . $this->table . "
                  WHERE PaketNama LIKE :keyword
                  OR PaketKategori LIKE :keyword
                  OR PaketDeskripsi LIKE :keyword";

        $stmt = $this->conn->prepare($query);
        $keyword = "%{$keyword}%";
        $stmt->bindParam(':keyword', $keyword);
        $stmt->execute();

        return $stmt;
    }
}
?>
  